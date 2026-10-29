import fs from 'node:fs';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {EventEmitter} from 'node:events';
import {createAttempt13Fatal} from './uiux-b2-attempt-13-fatal.mjs';
import {fatalReasons,createAttributedRoute,installAttributedListeners} from './uiux-b2-first-fatal-candidate.mjs';
import {validateRequest} from './uiux-b2-reviewer-policy.mjs';
let checks=0;const eq=(a,b)=>{assert.deepEqual(a,b);checks++;};
const read=p=>fs.readFileSync(p,'utf8'),hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const rename=s=>s.replaceAll('attempt-11','attempt-13').replaceAll('Attempt11','Attempt13').replaceAll('Attempt 11','Attempt 13').replaceAll('UIUX_B211','UIUX_B213').replaceAll('b211','b213');
const pins=JSON.parse(read('scripts/uiux-b2-attempt-13-pins.json'));
eq(pins.sources.length,29);for(const pin of pins.sources)eq(hash(pin.path),pin.sha256);
eq(hash('scripts/uiux-b2-first-fatal-candidate.mjs'),'7f53af6bd54f80ad92b5297d5a81d517f49905ac365bde38806dff544e17c484');
eq(hash('scripts/uiux-b2-shared-dashboard-readiness.mjs'),'49f91c5697e392a85198c8c3ba423e5927765bccb6e131772ac3cf57c4b6861a');
eq(fs.existsSync(pins.path),false);
for(const file of ['Run-UiUxB2Attempt11.mjs','UiUxB2Attempt11Bootstrap.php','Inspect-UiUxB2Attempt11.php','uiux-b2-attempt-11-server.php','uiux-b2-attempt-11-navigation.mjs'])eq(read('scripts/'+rename(file)),rename(read('scripts/'+file)));
const driver=read('scripts/verify-uiux-b2-attempt-13.mjs'),old=rename(read('scripts/verify-uiux-b2-attempt-11.mjs'));
const span=(s,a,b)=>s.slice(s.indexOf(a),s.indexOf(b,s.indexOf(a)));
eq(span(driver," mark('login-get-start');",'\ntry{await Promise.race([workflow(),failed])'),span(old," mark('login-get-start');",'\ntry{await Promise.race([workflow(),failed])'));
eq(span(driver,' const observeRoots=',' installAttributedListeners'),span(old,' const observeRoots='," page.on('pageerror'"));
eq((driver.match(/await ready\(\)/g)||[]).length,2);
eq((driver.match(/\.fill\(credentials.email\)/g)||[]).length,1);eq((driver.match(/\.fill\(credentials.password\)/g)||[]).length,1);
eq((driver.match(/name:'Sign in',exact:true}\)\.click\(\)/g)||[]).length,1);
eq(driver.includes('createEventRecorder({clock:diagnosticClock})'),true);
eq(driver.includes('result,clock:diagnosticClock'),true);
eq(driver.includes('firstFatal.startCleanup();\n await firstFatal.settle();\n if(navigationVerificationPending)'),true);
eq(driver.includes('await browser.close();await firstFatal.settle();result.events='),true);
const provenance=pins.approvedFirstFatal.validation;eq(hash(provenance.path),provenance.sha256);
const v=JSON.parse(read(provenance.path));eq(v.status,'PASS');eq(v.cases.length,17);eq(v.cases.every(c=>c.status==='PASS'),true);
// Execute the actual driver's wiring with injected filesystem and page objects,
// not its protected imports, credentials or workflow.
const setup=span(driver,'const firstFatal=','async function workflow(){');
const routeSetup=span(driver," await context.route('**/*',",' page=await context.newPage();');
const listenerSetup=span(driver,' installAttributedListeners'," mark('login-get-start');");
const wire=new Function('createAttempt13Fatal','createAttributedRoute','installAttributedListeners','fs','result','page','stop','mark','diagnostic',`return (async()=>{
 const base='http://synthetic.invalid',dir='SYNTHETIC_EVIDENCE';let phase='login',loginStage='login-verifying',submitted=false,readinessAborted=false;
 const endpoint='/livewire-test/update',loginState={loginPostAttempted:false,authenticationConfirmed:false};const diagnosticClock=()=>50;
 ${setup}
 const context={route:async(pattern,handler)=>{context.handler=handler;}};
 ${routeSetup}
 ${listenerSetup}
 return {firstFatal,fatal,handler:context.handler,loginState,hasFailed:()=>readinessAborted};})();`);
async function make(failWrite=false){
 const result={status:'BLOCKED',verificationConditionFailure:'EXISTING_FATAL_EVENT'},writes=[],signals=[],page=new EventEmitter();page.url=()=> 'http://synthetic.invalid/admin';
 let wiring;
 const fakeFs={writeFileSync:(path,text,options)=>{writes.push({path,record:JSON.parse(text),options});eq(wiring.hasFailed(),true);if(failWrite)throw Error('PRIVATE_SYNTHETIC');}};
 wiring=await wire(createAttempt13Fatal,createAttributedRoute,installAttributedListeners,fakeFs,result,page,error=>signals.push(error),()=>{},()=>({category:'RESTRICTED'}));
 return {result,writes,signals,page,...wiring};
}
for(const reason of fatalReasons){
 const x=await make();x.fatal(reason,SyntaxError('PRIVATE_SYNTHETIC'));
 eq(x.hasFailed(),true);eq(x.writes.length,1);eq(x.result.firstFatal.reason,reason);eq(x.signals[0].name,'Error');
 eq(x.writes[0].options,{flag:'wx'});eq(x.writes[0].record,x.result.firstFatal);
 x.firstFatal.startCleanup();x.page.emit('requestfailed');await x.firstFatal.settle();
 eq(x.writes.length,1);eq(x.result.firstFatal.reason,reason);eq(x.result.fatalObservations[1].cleanupStarted,true);eq(x.signals.length,2);eq(x.loginState.authenticationConfirmed,false);
 eq(JSON.stringify(x.result).includes('PRIVATE_SYNTHETIC'),false);
}
for(const [producer,expected] of [['pageerror','PAGE_ERROR'],['response','HTTP_ERROR'],['requestfailed','REQUEST_FAILED']]){
 const x=await make();
 x.page.emit(producer,producer==='pageerror'?Error('PRIVATE_SYNTHETIC'):producer==='response'?{url:()=> 'http://synthetic.invalid/error',request:()=>({method:()=> 'GET'}),status:()=>500}:undefined);
 await x.firstFatal.settle();eq(x.result.firstFatal.reason,expected);eq(x.writes.length,1);eq(x.loginState.authenticationConfirmed,false);
}
const failed=await make(true);failed.fatal('EXTERNAL_ORIGIN_BLOCKED',Error('PRIVATE_SYNTHETIC'));
failed.page.emit('requestfailed');await failed.firstFatal.settle();
eq(failed.writes.length,1);eq(failed.result.firstFatalEvidencePersistenceFailed,true);eq(failed.result.verificationConditionFailure,'EXISTING_FATAL_EVENT');eq(failed.result.firstFatal.reason,'EXTERNAL_ORIGIN_BLOCKED');
failed.result.status='PASS';await failed.firstFatal.settle();eq(failed.result.status,'BLOCKED');eq(failed.loginState.authenticationConfirmed,false);
const generic=await make();let aborted=0;
await generic.handler({request:()=>{throw Error('PRIVATE_SYNTHETIC');},abort:async()=>aborted++});await generic.firstFatal.settle();
eq(generic.result.firstFatal.reason,'ROUTE_HANDLER_EXCEPTION');eq(aborted,1);eq(generic.loginState.authenticationConfirmed,false);
const clean=await make();await clean.firstFatal.settle();eq(clean.writes.length,0);eq(clean.result.firstFatal,undefined);eq(clean.hasFailed(),false);
const oldRoute=span(old," await context.route('**/*',",' page=await context.newPage();');
const oldWire=new Function('validateRequest','fatal','mark',`return (async()=>{
 const base='http://synthetic.invalid',endpoint='/livewire-test/update',phase='login';let submitted=false;const loginState={loginPostAttempted:false};
 const context={route:async(pattern,handler)=>{context.handler=handler;}};
 ${oldRoute}
 return context.handler;})();`);
const login={components:[{snapshot:JSON.stringify({memo:{id:'SYNTHETIC',name:'Filament\\Auth\\Pages\\Login'}}),calls:[{method:'authenticate',params:[]}],updates:{}}]};
const notification={components:[{snapshot:JSON.stringify({memo:{id:'SYNTHETIC',name:'Filament\\Livewire\\Notifications'}}),calls:[{method:'__dispatch',params:['notificationsSent',[]]}],updates:{}}]};
for(const spec of ['EXTERNAL','ENDPOINT','BODY','COUNT','SHAPE','ACTION','METHOD','ADMIN','REPEAT','NOTIFICATION','ALLOWED']){
 const x=await make();let oldFatal=0,newAbort=0,oldAbort=0,newContinue=0,oldContinue=0;
 const original=await oldWire(validateRequest,()=>oldFatal++,()=>{});
 const req={url:()=>spec==='EXTERNAL'?'http://external.invalid/':'http://synthetic.invalid'+(spec==='ENDPOINT'?'/wrong':spec==='ADMIN'?'/admin/other':'/livewire-test/update'),method:()=>spec==='METHOD'?'DELETE':spec==='ADMIN'||spec==='EXTERNAL'?'GET':'POST',postDataJSON:()=>{
  if(spec==='BODY')throw SyntaxError('PRIVATE_SYNTHETIC');
  if(spec==='COUNT')return {components:[]};if(spec==='SHAPE')return {components:[{snapshot:'{}',calls:[]}]};
  if(spec==='ACTION')return {components:[{snapshot:JSON.stringify({memo:{id:'SYNTHETIC',name:'OTHER'}}),calls:[{method:'OTHER',params:[]}],updates:{}}]};
  return spec==='NOTIFICATION'?notification:login;
 }};
 const a={request:()=>req,abort:async()=>newAbort++,continue:async()=>newContinue++};
 const b={request:()=>req,abort:async()=>oldAbort++,continue:async()=>oldContinue++};
 await x.handler(a);await original(b);if(spec==='REPEAT'){await x.handler(a);await original(b);}
 await x.firstFatal.settle();eq(newAbort,oldAbort);eq(newContinue,oldContinue);eq(x.hasFailed(),oldFatal>0);eq(x.loginState.authenticationConfirmed,false);
 if(oldFatal)eq(x.writes.length,1);else eq(x.writes.length,0);
}

// All runtime package files are exact Attempt 12 copies after fixed naming changes.
const copyName=s=>s.replaceAll('attempt-12','attempt-13').replaceAll('Attempt12','Attempt13').replaceAll('Attempt 12','Attempt 13').replaceAll('UIUX_B212','UIUX_B213').replaceAll('b212','b213');
for(const p of ['scripts/Run-UiUxB2Attempt12.mjs','scripts/UiUxB2Attempt12Bootstrap.php','scripts/Inspect-UiUxB2Attempt12.php','scripts/uiux-b2-attempt-12-server.php','scripts/verify-uiux-b2-attempt-12.mjs','scripts/uiux-b2-attempt-12-navigation.mjs','scripts/uiux-b2-attempt-12-fatal.mjs'])eq(read(copyName(p)),copyName(read(p)));
for(const pin of pins.approvedAvatar.sources)eq(hash(pin.path),pin.sha256);
const avatarValidation=pins.approvedAvatar.validation;eq(hash(avatarValidation.path),avatarValidation.sha256);
const avatarResult=JSON.parse(read(avatarValidation.path));eq(avatarResult.status,'PASS');eq(avatarResult.cases.length,4);eq(avatarResult.cases.every(c=>c.status==='PASS'),true);
const exception=pins.documentedLogException,log=fs.readFileSync(exception.path),digest=b=>crypto.createHash('sha256').update(b).digest('hex');
eq(log.length,exception.completeBytes);eq(digest(log.subarray(0,exception.prefixBytes)),exception.prefixSha256);eq(digest(log.subarray(exception.prefixBytes)),exception.suffixSha256);eq(digest(log),exception.completeSha256);
eq(exception.classification,'APPLICATION ERROR INDICATED — PHP_EXECUTION_TIME_LIMIT — ATTRIBUTION NARROWED');

console.log(JSON.stringify({status:'PASS',checks,categories:13,directListenerCases:3,routeEquivalenceCases:11,persistenceFailureCases:1,scope:'actual driver wiring with synthetic dependencies; no browser/application/database'}));
