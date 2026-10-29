import assert from 'node:assert/strict';
import fs from 'node:fs';
import crypto from 'node:crypto';
import {fatalReasons,createFirstFatalCandidate,validateRequestAttributed,createAttributedRoute} from './uiux-b2-first-fatal-candidate.mjs';
import {validateRequest} from './uiux-b2-reviewer-policy.mjs';
let checks=0;const eq=(a,b)=>{assert.deepEqual(a,b);checks++;};
const base='http://127.0.0.1:18140';
for(const reason of fatalReasons){
 let signaled=0,aborted=false;const error=Error('PRIVATE_SYNTHETIC');let latch;
 latch=createFirstFatalCandidate({context:()=>({base,url:base+'/admin?PRIVATE_SYNTHETIC',phase:'login-verifying'}),clock:()=>25,
  signal:value=>{eq(latch.first.reason,reason);eq(value,error);aborted=true;signaled++;}});
 latch.fatal(reason,error);eq(aborted,true);eq(signaled,1);eq(latch.first.pathname,'/admin');
 eq(Object.isFrozen(latch.first),true);eq(JSON.stringify(latch.first).includes('PRIVATE_SYNTHETIC'),false);
}
let signals=0;const latch=createFirstFatalCandidate({signal:()=>signals++,context:()=>({base,url:base+'/admin',phase:'login-verifying'})});
latch.fatal('ADMIN_PATH_BLOCKED',Error());const first=latch.first;
latch.startCleanup();latch.fatal('REQUEST_FAILED',Error());latch.fatal('PAGE_ERROR',Error());
eq(latch.first,first);eq(latch.first.cleanupStarted,false);eq(latch.events[1].cleanupStarted,true);eq(signals,3);
eq(await latch.persistFirst(async()=>{throw Error('PRIVATE_SYNTHETIC');}),false);eq(latch.persistenceFailed,true);eq(latch.first,first);
assert.throws(()=>latch.fatal('PRIVATE_UNKNOWN',Error()));checks++;eq(latch.events.length,3);eq(signals,3);
await Promise.all([Promise.resolve().then(()=>latch.fatal('HTTP_ERROR',Error())),Promise.resolve().then(()=>latch.fatal('REQUEST_FAILED',Error()))]);eq(latch.first,first);
const snapshot=name=>JSON.stringify({memo:{id:'SYNTHETIC',name}});
const body=(name,method,params=[],updates={})=>({components:[{snapshot:snapshot(name),calls:[{method,params}],updates}]});
const login=body('Filament\\Auth\\Pages\\Login','authenticate');
const notification=body('Filament\\Livewire\\Notifications','__dispatch',['notificationsSent',[]]);
const cases=[login,notification,{},null,{components:[]},{components:[null]},{components:[{snapshot:'INVALID'}]},
 {components:[{snapshot:'{}',calls:[]}]},body('OTHER','authenticate'),body('Filament\\Auth\\Pages\\Login','authenticate',[],{bad:true}),
 body('Filament\\Livewire\\Notifications','__dispatch',['notificationsSent',[]],{bad:true}),
 body('App\\Filament\\Pages\\CaseDemonstrationDocuments','advance',[47,1,'reviewed'])];
for(const phase of ['login','idle',{id:47,lock:1,to:'reviewed'}])for(const value of cases){
 let a,b,ar=false,br=false;
 try{a=validateRequest(value,phase);}catch{ar=true;}
 try{b=validateRequestAttributed(value,phase,reason=>eq(fatalReasons.includes(reason),true));}catch{br=true;}
 eq(ar,br);eq(a,b);
}
for(const [spec,expected] of [['external','EXTERNAL_ORIGIN_BLOCKED'],['endpoint','POST_ENDPOINT_BLOCKED'],['body','POST_BODY_INVALID'],['count','COMPONENT_COUNT_INVALID'],['shape','SNAPSHOT_OR_CALL_INVALID'],['action','COMPONENT_ACTION_BLOCKED'],['repeat','CONSEQUENTIAL_REPEAT_BLOCKED'],['method','HTTP_METHOD_BLOCKED'],['admin','ADMIN_PATH_BLOCKED'],['generic','ROUTE_HANDLER_EXCEPTION'],['allowed',null],['notification',null]]){
 let submitted=spec==='repeat',aborts=0,continued=0,auth=false;const records=[];
 const req={url:()=>spec==='external'?'http://external.invalid/':base+(spec==='admin'?'/admin/other':spec==='endpoint'?'/wrong':'/livewire-test/update'),
  method:()=>spec==='method'?'DELETE':spec==='admin'||spec==='external'?'GET':'POST',postDataJSON:()=>{
   if(spec==='body')throw Error('PRIVATE_SYNTHETIC');
   return spec==='count'?{components:[]}:spec==='shape'?{components:[{snapshot:'{}'}]}:spec==='action'?body('OTHER','wrong'):spec==='notification'?notification:login;
  }};
 const route={request:()=>{if(spec==='generic')throw Error('PRIVATE_SYNTHETIC');return req;},abort:async()=>{aborts++;},continue:async()=>{continued++;}};
 await createAttributedRoute({base,getEndpoint:()=>'/livewire-test/update',getPhase:()=> 'login',getSubmitted:()=>submitted,setSubmitted:v=>submitted=v,onLoginPost:()=>{},mark:()=>{},fatal:(reason)=>records.push(reason)})(route);
 eq(records[0]??null,expected);eq(aborts,expected?1:0);eq(continued,expected?0:1);eq(auth,false);
}
// First-cause persistence never changes the fatal signal or downstream precedence.
const result={},failure=Promise.reject(Error('SYNTHETIC'));failure.catch(()=>{});
try{await Promise.race([new Promise(()=>{}),failure]);}catch{result.code='EXISTING_FATAL_EVENT';}
eq(result.code,'EXISTING_FATAL_EVENT');eq(latch.first,first);
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
eq(hash('scripts/uiux-b2-shared-dashboard-readiness.mjs'),'49f91c5697e392a85198c8c3ba423e5927765bccb6e131772ac3cf57c4b6861a');
eq(hash('scripts/uiux-b2-reviewer-policy.mjs'),'46f4c674034ac598fe3b8b598f780b071304c4c52830d39db5665595b2ccbc31');
const empty=createFirstFatalCandidate({context:()=>{throw Error('PRIVATE_SYNTHETIC');},clock:()=>NaN,signal:()=>{}});
eq(await empty.persistFirst(async()=>{throw Error('Must not write');}),false);
assert.throws(()=>empty.fatal('PRIVATE_UNKNOWN',Error()));checks++;eq(empty.first,null);
empty.fatal('ROUTE_HANDLER_EXCEPTION',Error('PRIVATE_SYNTHETIC'));
eq(Object.keys(empty.first).sort(),['reason','sequence','elapsedMs','phase','pathname','cleanupStarted'].sort());
eq(empty.first.phase,'restricted-phase');eq(empty.first.pathname,'/[other]');eq(empty.first.elapsedMs,0);
assert.throws(()=>{empty.first.reason='PRIVATE_UNKNOWN';});checks++;
let capSignals=0;
const cap=createFirstFatalCandidate({context:()=>({base,url:'http://external.invalid/PRIVATE_SYNTHETIC',phase:'PRIVATE_SYNTHETIC'}),clock:()=>1e12,signal:()=>capSignals++});
for(let i=0;i<80;i++)cap.fatal('REQUEST_FAILED',Error());
eq(cap.events.length,64);eq(capSignals,80);eq(cap.first.elapsedMs,86400000);eq(cap.first.pathname,'/[other]');
eq(JSON.stringify(cap.events).includes('PRIVATE_SYNTHETIC'),false);
// Replay actual shared readiness with the candidate latch supplying its existing
// fatal flag. No protected workflow, browser or altered Dashboard predicate.
let suite=fs.readFileSync('scripts/check-uiux-b2-shared-dashboard-readiness.mjs','utf8');
suite=suite.replace("from './uiux-b2-shared-dashboard-readiness.mjs'",`from '${new URL('./uiux-b2-shared-dashboard-readiness.mjs',import.meta.url).href}'`);
suite=`import {createFirstFatalCandidate} from '${new URL('./uiux-b2-first-fatal-candidate.mjs',import.meta.url).href}';\n`+suite;
suite=suite.replace('let verified;',"const attribution=createFirstFatalCandidate({context:()=>({base:'http://synthetic.invalid',url:page.url(),phase:'login-verifying'}),signal:()=>{fatal=true;}});\n let verified;");
suite=suite.replace("if(spec==='FATAL')fatal=true;","if(spec==='FATAL')attribution.fatal('REQUEST_FAILED',Error('Synthetic')); ");
suite=suite.replace("equal(auth,spec==='VALID');","if(spec==='FATAL')equal(attribution.first.reason,'REQUEST_FAILED');\n equal(auth,spec==='VALID');");
await import('data:text/javascript;base64,'+Buffer.from(suite).toString('base64'));
console.log(JSON.stringify({status:'PASS',checks,categories:fatalReasons.length,policyComparisons:36,routeCases:12}));
