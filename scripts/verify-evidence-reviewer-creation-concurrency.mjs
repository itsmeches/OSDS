import {spawn,execFileSync} from 'node:child_process';
import {existsSync,readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {randomUUID} from 'node:crypto';
import assert from 'node:assert/strict';
const dir='docs/screenshots/phase-3d7-g-int005';mkdirSync(dir,{recursive:true});
const control=()=>{execFileSync('php',['scripts/Prepare-EvidenceReviewerCreation.php','inspect']);return JSON.parse(readFileSync('storage/app/private/evidence-reviewer-creation.json'));};
execFileSync('php',['scripts/Prepare-EvidenceReviewerCreation.php','prepare']);
const run=randomUUID(),barrier='storage/app/private/gint005-'+run;
const start=side=>{const p=spawn('php',['scripts/EvidenceReviewerCreationWorker.php',side,run]);let output='';p.stdout.on('data',d=>output+=d);p.stderr.on('data',d=>output+=d);return new Promise((resolve,reject)=>p.on('exit',code=>code===0?resolve(JSON.parse(output.trim())):reject(new Error(output))));};
const workers=[start('A'),start('B')];
try{const deadline=Date.now()+15000;while(!existsSync(barrier+'-A')||!existsSync(barrier+'-B')){if(Date.now()>deadline)throw Error('Workers did not overlap');await new Promise(r=>setTimeout(r,10));}
const connections=['A','B'].map(s=>JSON.parse(readFileSync(barrier+'-'+s)));assert.notEqual(connections[0].pid,connections[1].pid);assert.equal(control().assignments.length,0);
writeFileSync(barrier+'-release','release');const outcomes=await Promise.all(workers),saved=control();assert.equal(saved.assignments.length,2);assert(saved.assignments.every(r=>r.audits===1));
writeFileSync(dir+'/concurrency.json',JSON.stringify({passed:true,independentConnections:connections,uncommittedRowsVisible:0,outcomes,assignmentCount:2,auditCount:2,semantics:'Existing separate creates allowed; no idempotency guarantee or new exclusion policy'},null,2));console.log('Concurrent creation: two overlapping PostgreSQL transactions, two grants and two audits passed');
}finally{writeFileSync(barrier+'-release','release');await Promise.allSettled(workers);}
