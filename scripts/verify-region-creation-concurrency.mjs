import {spawn,execFileSync} from 'node:child_process';
import {existsSync,readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {randomUUID} from 'node:crypto';
import assert from 'node:assert/strict';
const dir='docs/screenshots/phase-3d7-k5-remediation';mkdirSync(dir,{recursive:true});
const control=(action,...args)=>JSON.parse(execFileSync('php',['scripts/Prepare-RegionCreation.php',action,...args],{encoding:'utf8'}));
const start=(mode,run)=>{const p=spawn('php',['scripts/RegionCreationWorker.php',mode,run]);let out='';p.stdout.on('data',d=>out+=d);p.stderr.on('data',d=>out+=d);return new Promise((resolve,reject)=>p.on('exit',code=>code===0?resolve(JSON.parse(out.trim())):reject(Error(out))));};
async function until(fn){const end=Date.now()+18000;while(!fn()){if(Date.now()>end)throw Error('Controlled PostgreSQL wait not observed');await new Promise(r=>setTimeout(r,25));}}
const results=[];
for(const mode of ['A','permission','inactive','role']){
 const before=control(mode==='role'?'prepare-role':'prepare'),run=randomUUID(),barrier='storage/app/private/k5create-'+run,workers=[];
 try{
  workers.push(start(mode,run));await until(()=>existsSync(barrier+'-ready'));workers.push(start('B',run));await until(()=>existsSync(barrier+'-B-started'));
  const first=JSON.parse(readFileSync(barrier+'-ready')),second=JSON.parse(readFileSync(barrier+'-B-started'));assert.notEqual(first.pid,second.pid);await until(()=>control('blocked',String(second.pid)).blocked);
  const during=control('inspect');assert.equal(during.regions.length,0);
  writeFileSync(barrier+'-release','release');const outcomes=await Promise.all(workers),after=control('inspect');
  if(mode==='A'){assert.equal(outcomes[0].outcome,'created');assert.equal(outcomes[1].outcome,'duplicate-rejected');assert.equal(after.regions.length,1);assert.equal(after.regions[0].audits.length,1);assert.equal(after.regions[0].audits[0].actor_id,before.actor);}
  else{assert.equal(outcomes[1].outcome,'denied');assert.equal(after.regions.length,0);}
  results.push({mode,passed:true,first,second,observedDatabaseLockWait:true,uncommittedStateInvisible:true,outcomes,before,during,after});console.log('Passed PostgreSQL concurrency: '+mode);
 }finally{writeFileSync(barrier+'-release','release');await Promise.allSettled(workers);writeFileSync(dir+'/concurrency.json',JSON.stringify(results,null,2));}
}
