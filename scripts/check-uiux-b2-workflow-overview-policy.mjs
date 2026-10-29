import fs from 'node:fs';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {workflowWidget,validateWorkflowRequest,createWorkflowRouteCandidate} from './uiux-b2-workflow-overview-policy-candidate.mjs';
import {createFirstFatalCandidate} from './uiux-b2-first-fatal-candidate.mjs';
const fixtures=JSON.parse(fs.readFileSync('storage/app/private/uiux-consolidated-acceptance/gate-b2-workflow-overview-validation/synthetic-snapshots.json'));
let checks=0;const eq=(a,b)=>{assert.deepEqual(a,b);checks++;},clone=x=>JSON.parse(JSON.stringify(x));
const witness=(loaded=false)=>({dashboardVerified:true,documentVerified:true,widgetCount:1,currentSnapshot:JSON.stringify(loaded?fixtures.loaded:fixtures.initial),lazyToken:fixtures.token});
const body=(loaded=false)=>({components:[{snapshot:JSON.stringify(loaded?fixtures.loaded:fixtures.initial),updates:{},calls:[{method:loaded?'$refresh':'__lazyLoad',params:loaded?[]:[fixtures.token]}]}]});
eq(validateWorkflowRequest(body(),'login',witness()),'workflow-lazy');eq(validateWorkflowRequest(body(true),'idle',witness(true)),'workflow-refresh');
const negatives=[
 ['WRONG_COMPONENT',b=>{const s=JSON.parse(b.components[0].snapshot);s.memo.name='OTHER';b.components[0].snapshot=JSON.stringify(s);}],
 ['WRONG_METHOD',b=>b.components[0].calls[0].method='advance'],
 ['WRONG_ARGS',b=>b.components[0].calls[0].params=[]],
 ['UPDATES',b=>b.components[0].updates={data:1}],
 ['MULTIPLE',b=>b.components.push(clone(b.components[0]))],
 ['MALFORMED',b=>b.components[0].snapshot='{'],
 ['TAMPERED_TOKEN',b=>b.components[0].calls[0].params=[Buffer.from(JSON.stringify({...fixtures.mount,data:{...fixtures.mount.data,forComponent:'OTHER'}})).toString('base64')]],
 ['BAD_CHECKSUM',b=>{const s=JSON.parse(b.components[0].snapshot);s.checksum='0'.repeat(64);b.components[0].snapshot=JSON.stringify(s);}],
 ['MULTIPLE_CALLS',b=>b.components[0].calls.push({method:'$refresh',params:[]})],
 ['NOTIFY_WRONG_EVENT',b=>b.components[0].calls[0]={method:'__dispatch',params:['OTHER',[]]}],
];
for(const [name,mutate] of negatives){const b=body();mutate(b);let rejected=false;try{validateWorkflowRequest(b,'login',witness());}catch{rejected=true;}eq(rejected,true);}
for(const w of [{...witness(),dashboardVerified:false},{...witness(),documentVerified:false},{...witness(),widgetCount:2}])assert.throws(()=>validateWorkflowRequest(body(),'login',w)),checks++;
assert.throws(()=>validateWorkflowRequest(body(),'other',witness()));checks++;
const login={components:[{snapshot:JSON.stringify({memo:{id:'synthetic-login',name:'Filament\\Auth\\Pages\\Login'}}),updates:{},calls:[{method:'authenticate',params:[]}]}]};
const notification={components:[{snapshot:JSON.stringify({memo:{id:'synthetic-notification',name:'Filament\\Livewire\\Notifications'}}),updates:{},calls:[{method:'__dispatch',params:['notificationsSent',[]]}]}]};
eq(validateWorkflowRequest(login,'login'), 'login');eq(validateWorkflowRequest(notification,'idle'),'notification');
const lifecycle={components:[{snapshot:JSON.stringify({memo:{id:'synthetic-personnel',name:'App\\Filament\\Pages\\CaseDemonstrationDocuments'}}),updates:{},calls:[{method:'advance',params:[47,1,'reviewed']}]}]};
assert.throws(()=>validateWorkflowRequest(lifecycle,{id:47,lock:1,to:'reviewed'}));checks++;
for(const spec of ['FLOW','DUPLICATE_LOGIN','DUPLICATE_LAZY','POLL_CAP','BEFORE_LOGIN','CONTEXT_FAIL','FATAL','UNKNOWN']){
 let submitted=false,aborted=false,loaded=false,forwarded=0,aborts=0,loginForwards=0;const kinds=[],result={authenticationConfirmed:false};
 const attr=createFirstFatalCandidate({context:()=>({base:'http://synthetic.invalid',url:'http://synthetic.invalid/admin',phase:'login-verifying'}),signal:()=>{aborted=true;}});
 const route=createWorkflowRouteCandidate({base:'http://synthetic.invalid',getEndpoint:()=>'/livewire-test/update',getPhase:()=> 'login',getSubmitted:()=>submitted,setSubmitted:x=>submitted=x,
  onLoginPost:()=>{},mark:kind=>{if(kind==='login-post-forward')loginForwards++;},fatal:attr.fatal,hasFailed:()=>aborted,
  observer:{observe:async()=>{if(spec==='CONTEXT_FAIL')throw Error('Synthetic');return witness(loaded);}},onWidget:kind=>kinds.push(kind)});
 const send=b=>route({request:()=>({url:()=> 'http://synthetic.invalid/livewire-test/update',method:()=> 'POST',postDataJSON:()=>b}),continue:async()=>forwarded++,abort:async()=>aborts++});
 if(spec!=='BEFORE_LOGIN')await send(login);
 if(spec==='FATAL')attr.fatal('PAGE_ERROR',Error('Synthetic'));
 const b=body();if(spec==='UNKNOWN')b.components[0].calls[0].method='publish';
 await send(b);
 if(spec==='DUPLICATE_LAZY')await send(body());
 if(!aborted){loaded=true;for(let i=0;i<(spec==='POLL_CAP'?13:2);i++)await send(body(true));}
 if(spec==='DUPLICATE_LOGIN')await send(login);
 eq(loginForwards,spec==='BEFORE_LOGIN'?0:1);eq(result.authenticationConfirmed,false);
 if(spec==='FLOW'){eq(aborted,false);eq(kinds,['workflow-lazy','workflow-refresh','workflow-refresh']);eq(forwarded,4);eq(aborts,0);}
 else{eq(aborted,true);eq(aborts>0,true);eq(attr.first.reason,spec==='DUPLICATE_LOGIN'?'CONSEQUENTIAL_REPEAT_BLOCKED':spec==='FATAL'?'PAGE_ERROR':'COMPONENT_ACTION_BLOCKED');}
}
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
eq(hash('scripts/uiux-b2-reviewer-policy.mjs'),'46f4c674034ac598fe3b8b598f780b071304c4c52830d39db5665595b2ccbc31');
eq(hash('scripts/uiux-b2-first-fatal-candidate.mjs'),'7f53af6bd54f80ad92b5297d5a81d517f49905ac365bde38806dff544e17c484');
console.log(JSON.stringify({status:'PASS',checks,negativeContracts:negatives.length,routeCases:8,scope:'isolated policy/accounting; synthetic signed fixtures; no protected runtime'}));
