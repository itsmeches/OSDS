import {spawn,execFileSync} from 'node:child_process';
import {existsSync,readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {randomUUID} from 'node:crypto';
import assert from 'node:assert/strict';
const dir='docs/screenshots/b2-case-issuance';mkdirSync(dir,{recursive:true});
const control=(...args)=>JSON.parse(execFileSync('php',['scripts/Prepare-B2.php',...args],{encoding:'utf8'}));
const start=(mode,run)=>{const p=spawn('php',['scripts/B2RequestWorker.php',mode,run]);let out='';p.stdout.on('data',d=>out+=d);p.stderr.on('data',d=>out+=d);return new Promise((resolve,reject)=>p.on('exit',code=>code===0?resolve(JSON.parse(out)):reject(Error(out))));};
async function until(fn){const end=Date.now()+30000;while(!fn()){if(Date.now()>end)throw Error('Database wait not observed');await new Promise(r=>setTimeout(r,30));}}
const results=[];
for(const mode of ['A','representation','permission','withdrawal']){
 const fixture=control('prepare'),before=control('inspect'),run=randomUUID(),barrier='storage/app/private/b2-'+run,workers=[];
 try{workers.push(start(mode,run));await until(()=>existsSync(barrier+'-ready'));workers.push(start('B',run));await until(()=>existsSync(barrier+'-B-started'));const first=JSON.parse(readFileSync(barrier+'-ready')),second=JSON.parse(readFileSync(barrier+'-B-started'));assert.notEqual(first.pid,second.pid);await until(()=>control('blocked',String(second.pid)).blocked);const during=control('inspect');assert.deepEqual(during,before);writeFileSync(barrier+'-release','release');const outcomes=await Promise.all(workers),after=control('inspect');
 if(mode==='A'){assert.equal(outcomes[0].outcome,'committed');assert.equal(outcomes[1].outcome,'rejected');assert.equal(after.requests.length,1);assert.equal(after.requests[0].state,'submitted');assert.deepEqual(after.audits,['issuance.request_submitted']);assert.equal(after.history,1);}else{assert.equal(outcomes[1].outcome,mode==='withdrawal'?'rejected':'denied');assert.deepEqual(after,before);}
 results.push({mode,passed:true,case:fixture.case,first,second,observedDatabaseWait:true,uncommittedRequestInvisible:true,outcomes,before,during,after});console.log('Passed B2 concurrency: '+mode);
 }finally{writeFileSync(barrier+'-release','release');await Promise.allSettled(workers);writeFileSync(dir+'/concurrency.json',JSON.stringify(results,null,2));}
}
