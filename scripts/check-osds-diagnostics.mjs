// Offline only: pure helpers, synthetic values and source inspection. No browser import.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import {assetFamily,createEventRecorder,sanitizeException} from './uiux-b2-error-diagnostics.mjs';
import {validateRequest} from './uiux-b2-reviewer-policy.mjs';

let checks=0;
function equal(actual,expected){assert.deepEqual(actual,expected);checks++;}
function rejects(callback){assert.throws(callback);checks++;}
const context={phase:'login-verifying',url:'http://127.0.0.1:8044/admin/login?token=SYNTHETIC_SECRET',loginFormDisplayed:true,loginPostAttempted:true,authenticationConfirmed:false};
const symbols=[['passwordRevealFormComponent','PASSWORD_REVEAL_FACTORY'],['isPasswordRevealed','PASSWORD_REVEAL_STATE'],['Alpine','ALPINE_GLOBAL'],['Livewire','LIVEWIRE_GLOBAL']];
for(const [symbol,code] of symbols){
 for(const [message,suffix] of [[`${symbol} is not defined`,'UNDEFINED'],[`Cannot access '${symbol}' before initialization`,'BEFORE_INITIALIZATION']]){
  const out=sanitizeException({name:'ReferenceError',message},context);
  equal(out.category,`${code}_${suffix}`);equal(out.message,'[restricted exception details]');
  equal(sanitizeException({name:'ReferenceError',message},context,[symbol]).category,'RESTRICTED');
 }
}
for(const message of ['secretIdentifier is not defined','passwordRevealFormComponent is not defined; extra','passwordRevealFormComponent is not defined\nCookie: secret','Authorization: Bearer SYNTHETIC_SECRET','csrf_token=SYNTHETIC_SECRET','session=SYNTHETIC_SECRET','email@example.invalid','x'.repeat(3000)]){
 const out=sanitizeException({name:'ReferenceError',message},context,['SYNTHETIC_SECRET']);
 equal(out.category,'RESTRICTED');equal(JSON.stringify(out).includes('SYNTHETIC_SECRET'),false);
}
equal(sanitizeException({name:'TypeError',message:'Alpine is not defined'},context).category,'RESTRICTED');
equal(sanitizeException({name:'unknown-secret-class',message:'hidden'},context).name,'RestrictedError');
equal(sanitizeException({name:'Error',stack:'x'.repeat(9000)},context).stack,[{category:'oversized-stack'}]);
equal(sanitizeException({name:'Error',stack:'Error\n'+Array(20).fill(' at http://127.0.0.1:8044/js/filament/support/support.js:1:2').join('\n')},context).stack.length,8);
const families=[['/livewire-6d26c714/livewire.min.js','livewire-core'],['/js/filament/support/support.js','filament-support'],['/js/filament/filament/app.js','filament-panel'],['/js/filament/tables/tables.js','filament-tables'],['/js/filament/forms/components/text-input/password-reveal.js','filament-password-reveal']];
for(const [pathname,family] of families){
 const url='http://127.0.0.1:8044'+pathname+'?token=SYNTHETIC_SECRET';
 equal(assetFamily(url),family);
 const out=sanitizeException({name:'ReferenceError',message:'Alpine is not defined',stack:`ReferenceError\n at secretFunction (${url}:5:1165)`},context);
 equal(out.stack[0].assetFamily,family);equal(JSON.stringify(out).includes('SYNTHETIC_SECRET'),false);equal(JSON.stringify(out).includes('secretFunction'),false);
 equal(sanitizeException({name:'Error',stack:`Error\n at ${url}:5:1165`},context,['SYNTHETIC_SECRET']).stack,[{category:'restricted-frame'}]);
}
for(const url of ['https://outside.invalid/js/filament/support/support.js','http://127.0.0.1:8044/js/filament/support/support.js.evil','http://user:secret@127.0.0.1:8044/js/filament/support/support.js','http://127.0.0.1:8044/private/secret.js'])equal(assetFamily(url),'unknown');
let tick=100;const recorder=createEventRecorder({clock:()=>tick,limit:3});
const start=recorder.record('login-post-forward',context);tick=102;
const response=recorder.record('http-response',{...context,method:'POST',status:200,url:'http://127.0.0.1:8044/livewire-6d26c714/update?token=SYNTHETIC_SECRET'});tick=101;
const error=recorder.record('page-error',context);tick=104;const overflow=recorder.record('workflow-failure',context);
equal([start.sequence,response.sequence,error.sequence,overflow.sequence],[1,2,3,4]);equal([start.elapsed_ms,response.elapsed_ms,error.elapsed_ms,overflow.elapsed_ms],[0,2,2,4]);
equal(recorder.events.length,3);equal(recorder.dropped,1);equal(JSON.stringify(recorder.events).includes('SYNTHETIC_SECRET'),false);
equal(response.pathname,'/[livewire-update]');rejects(()=>recorder.record('arbitrary-secret-marker',context));rejects(()=>createEventRecorder({limit:513}));
const driver=fs.readFileSync(new URL('./verify-uiux-b2-reviewer-only.mjs',import.meta.url),'utf8');
for(const marker of ['email-fill-start','password-fill-start','sign-in-click-start','navigation-check-start','auth-link-check-start','authentication-confirmed','page-error','http-response'])equal(driver.includes(`mark('${marker}'`),true);
equal(driver.includes("const dir='storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-05'"),true);
equal(driver.includes("Promise.race([workflow(),failed])"),true);
const page='App\\Filament\\Pages\\CaseDemonstrationDocuments';
const envelope=(method,params)=>({components:[{snapshot:JSON.stringify({memo:{id:'synthetic',name:page}}),calls:[{method,params}],updates:{}}]});
equal(validateRequest(envelope('advance',[47,1,'reviewed']),{id:47,lock:1,to:'reviewed'}),'advance');
for(const [method,params] of [['createPersonnel',[]],['generate',[47]],['advance',[49,1,'reviewed']],['advance',[47,2,'reviewed']],['__lazyLoad',[]],['__dispatch',['anything',[]]]])rejects(()=>validateRequest(envelope(method,params),{id:47,lock:1,to:'reviewed'}));
console.log(JSON.stringify({status:'PASS',checks,scope:'offline diagnostic/policy/source tests only'}));
