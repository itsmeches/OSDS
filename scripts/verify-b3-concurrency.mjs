import {spawn,execFileSync} from 'node:child_process';
import {existsSync,readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {randomUUID} from 'node:crypto';
import assert from 'node:assert/strict';
const dir='docs/screenshots/b3-demonstration-pdfs';mkdirSync(dir,{recursive:true});
const control=(...args)=>JSON.parse(execFileSync('php',['scripts/Prepare-B3.php',...args],{encoding:'utf8'}));
const start=(mode,run)=>{const p=spawn('php',['scripts/B3GenerationWorker.php',mode,run]);let out='';p.stdout.on('data',d=>out+=d);p.stderr.on('data',d=>out+=d);return new Promise((resolve,reject)=>p.on('exit',code=>code===0?resolve(JSON.parse(out)):reject(Error(out))));};
const results=[];
for(const mode of ['duplicate','permission','withdraw','personnel','scope','inactive']) {
 const f=control('prepare'),run=randomUUID(),barrier='storage/app/private/b3-'+run;let worker;
 try {
  worker=start('A',run);const deadline=Date.now()+35000;
  while(!existsSync(barrier+'-ready')) {if(Date.now()>deadline)throw Error('Worker not ready');await new Promise(r=>setTimeout(r,50));}
  const ready=JSON.parse(readFileSync(barrier+'-ready'));assert.equal(ready.transactionDepth,0);
  const during=control('inspect');assert.equal(during.documents[0].state,'processing');assert.equal(during.documents[0].checksum,null);assert(!during.events.includes('generation_completed'));
  let second;
  if(mode==='duplicate'){second=await start('B',run);assert.notEqual(second.pid,ready.pid);assert.equal(second.outcome,'rejected');}
  else control(mode==='permission'?'revoke':mode);
  writeFileSync(barrier+'-release','release');const outcome=await worker,after=control('inspect');
  if(mode==='duplicate'){assert.equal(outcome.outcome,'completed');assert.equal(after.documents.length,1);assert.equal(after.events.filter(e=>e==='generation_completed').length,1);assert.equal(after.attempts,1);}
  else {assert.notEqual(outcome.outcome,'completed');assert.equal(after.documents[0].state,'failed');assert(!after.events.includes('generation_completed'));}
  results.push({mode,request:f.request,ready,second,during,after,outcome,passed:true});console.log('B3 concurrency passed: '+mode);
 } finally {writeFileSync(barrier+'-release','release');if(worker)await worker.catch(()=>{});writeFileSync(dir+'/concurrency.json',JSON.stringify(results,null,2));}
}
