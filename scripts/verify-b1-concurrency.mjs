import {spawn,execFileSync} from 'node:child_process';
import {existsSync,readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {randomUUID} from 'node:crypto';
import assert from 'node:assert/strict';
const dir='docs/screenshots/b1-decision-foundation';mkdirSync(dir,{recursive:true});
const control=(...args)=>JSON.parse(execFileSync('php',['scripts/Prepare-B1.php',...args],{encoding:'utf8'}));
const start=(mode,run)=>{const p=spawn('php',['scripts/B1DecisionWorker.php',mode,run]);let out='';p.stdout.on('data',d=>out+=d);p.stderr.on('data',d=>out+=d);return new Promise((resolve,reject)=>p.on('exit',code=>code===0?resolve(JSON.parse(out)):reject(Error(out))));};
async function until(fn){const end=Date.now()+25000;while(!fn()){if(Date.now()>end)throw Error('Database barrier not observed');await new Promise(r=>setTimeout(r,30));}}
const results=[];
for(const mode of ['A','permission','inactive','assignment','scope']){
 const fixture=control('prepare'),before=control('inspect'),run=randomUUID(),barrier='storage/app/private/b1-'+run,workers=[];
 try{
  workers.push(start(mode,run));await until(()=>existsSync(barrier+'-ready'));workers.push(start('B',run));await until(()=>existsSync(barrier+'-B-started'));
  const first=JSON.parse(readFileSync(barrier+'-ready')),second=JSON.parse(readFileSync(barrier+'-B-started'));assert.notEqual(first.pid,second.pid);await until(()=>control('blocked',String(second.pid)).blocked);
  const during=control('inspect');assert.deepEqual(during,before);
  writeFileSync(barrier+'-release','release');const outcomes=await Promise.all(workers),after=control('inspect');
  if(mode==='A'){assert.equal(outcomes[0].outcome,'committed');assert.equal(outcomes[1].outcome,'stale');assert.equal(after.state,'approved');assert.equal(after.stages.filter(s=>s.stage_role==='director').length,1);assert.equal(after.audits.filter(a=>a==='application.approved').length,1);assert(!after.audits.includes('application.rejected'));}
  else{assert.equal(outcomes[1].outcome,'denied');assert.deepEqual(after,before);}
  results.push({mode,passed:true,fixture:{case:fixture.case,module:fixture.module},first,second,observedDatabaseWait:true,uncommittedStateInvisible:true,outcomes,before,during,after});console.log('Passed B1 concurrency: '+mode);
 }finally{writeFileSync(barrier+'-release','release');await Promise.allSettled(workers);writeFileSync(dir+'/concurrency.json',JSON.stringify(results,null,2));}
}
