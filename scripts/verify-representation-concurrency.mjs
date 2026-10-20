import {spawn,execFileSync} from 'node:child_process';
import {existsSync,readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {randomUUID} from 'node:crypto';
import assert from 'node:assert/strict';
const dir='docs/screenshots/phase-3d7-g-int007';mkdirSync(dir,{recursive:true});
const control=(action,...args)=>{const output=execFileSync('php',['scripts/Prepare-RepresentationCreation.php',action,...args],{encoding:'utf8'});return action==='blocked'?JSON.parse(output):JSON.parse(readFileSync('storage/app/private/representation-creation.json'));};
const start=(mode,run)=>{const p=spawn('php',['scripts/RepresentationCreationWorker.php',mode,run]);let output='';p.stdout.on('data',d=>output+=d);p.stderr.on('data',d=>output+=d);return new Promise((resolve,reject)=>p.on('exit',code=>code===0?resolve(JSON.parse(output.trim())):reject(Error(output))));};
async function until(fn){const deadline=Date.now()+18000;while(!fn()){if(Date.now()>deadline)throw Error('Controlled PostgreSQL wait not observed');await new Promise(r=>setTimeout(r,25));}}
const results=[];
for(const mode of ['A','scope','permission','inactive','target-role','institution']){
 control('prepare');const run=randomUUID(),barrier='storage/app/private/gint007-'+run;const workers=[];
 try{
 workers.push(start(mode,run));await until(()=>existsSync(barrier+'-ready'));
 workers.push(start('B',run));await until(()=>existsSync(barrier+'-B-started'));
 const first=JSON.parse(readFileSync(barrier+'-ready')),second=JSON.parse(readFileSync(barrier+'-B-started'));
 assert.notEqual(first.pid,second.pid);await until(()=>control('blocked',String(second.pid)).blocked);
 assert.equal(control('inspect').assignments.length,0);
 writeFileSync(barrier+'-release','release');const outcomes=await Promise.all(workers),saved=control('inspect');
 if(mode==='A'){assert.equal(outcomes[0].outcome,'created');assert.equal(outcomes[1].outcome,'duplicate-rejected');assert.equal(saved.assignments.length,1);assert.equal(saved.assignments[0].audits,1);}
 else{assert.equal(outcomes[1].outcome,'denied');assert.equal(saved.assignments.length,0);}
 results.push({mode,passed:true,first,second,observedDatabaseLockWait:true,uncommittedGrantsVisible:0,outcomes,saved});console.log('Passed PostgreSQL concurrency: '+mode);
 }finally{writeFileSync(barrier+'-release','release');await Promise.allSettled(workers);writeFileSync(dir+'/concurrency.json',JSON.stringify(results,null,2));}
}
