import assert from 'node:assert/strict';
import fs from 'node:fs';
import {createSharedDashboardBudget, verifySharedDashboardCandidate} from './uiux-b2-shared-dashboard-readiness.mjs';
let checks=0;const equal=(a,b)=>{assert.deepEqual(a,b);checks++;};
let time=50;const budget=createSharedDashboardBudget(()=>time);
equal(budget.remaining(),30000);time+=5001;equal(budget.remaining(),24999);time+=24999;equal(budget.remaining(),0);time++;equal(budget.remaining(),0);
const specs=['VALID','LOGIN','MALFORMED','DUPLICATE','HEADING_HIDDEN','SIDEBAR_MISSING','FATAL','DESTINATION','DOCUMENT','EXPIRED','PERSISTENCE'];
for(const spec of specs){
 let fatal=false,same=true,path='/admin',clock=0,auth=false,actions=0,listener;
 const result={},records=[];const failed=new Promise(()=>{});
 const link={kind:'link',getAttribute:async()=>'/admin/case-demonstration-documents',click:async options=>{if(!options?.trial)actions++;}};
 const page={url:()=> 'http://synthetic.invalid'+path,mainFrame:()=>page,on:(_,fn)=>listener=fn,off:()=>listener=null,
  evaluateHandle:async()=>({dispose:async()=>{}}),evaluate:async fn=>{
   if(fn.name!=='inspectDashboardContract')return same;
   if(spec==='FATAL')fatal=true;if(spec==='DESTINATION'){path='/away';listener(page);}if(spec==='DOCUMENT')same=false;if(spec==='EXPIRED')clock=30001;
   return {dashboard:spec==='DUPLICATE'?2:1,login:spec==='LOGIN'?1:0,loginForm:false,malformed:spec==='MALFORMED'};
  },locator:()=>({kind:'sidebar',getByRole:()=>link}),getByRole:()=>({count:async()=>1,isVisible:async()=>spec!=='HEADING_HIDDEN'})};
 const expect=value=>({toHaveCount:async count=>{if(value.kind==='link'&&spec==='SIDEBAR_MISSING')throw Error('Synthetic');assert.equal(count,1);},toBeVisible:async()=>{}});
 expect.poll=callback=>({toBe:async value=>assert.equal(await callback(),value)});
 let verified;
 try{verified=await verifySharedDashboardCandidate({page,expect,base:'http://synthetic.invalid',failed,hasFailed:()=>fatal,result,clock:()=>clock,persist:async record=>{records.push(record);if(spec==='PERSISTENCE')throw Error('PRIVATE_SYNTHETIC');}});if(fatal)throw Error('Fatal');auth=true;}
 catch{}
 finally{if(verified)await verified.dispose();}
 equal(auth,spec==='VALID');equal(actions,0);equal(listener,null);
 if(spec==='VALID')equal(result.verificationConditionFailure,undefined);
 else equal(result.verificationConditionFailure,({LOGIN:'UNEXPECTED_LOGIN_COMPONENT',MALFORMED:'MALFORMED_COMPONENT_METADATA',DUPLICATE:'DASHBOARD_COMPONENT_DUPLICATED',HEADING_HIDDEN:'HEADING_INVISIBLE',SIDEBAR_MISSING:'SIDEBAR_CONDITION_FAILED',FATAL:'EXISTING_FATAL_EVENT',DESTINATION:'DESTINATION_CHANGED',DOCUMENT:'DOCUMENT_CHANGED',EXPIRED:'SHARED_DEADLINE_EXHAUSTED',PERSISTENCE:'EVIDENCE_PERSISTENCE_FAILED'})[spec]);
 equal(JSON.stringify([result,records]).includes('PRIVATE_SYNTHETIC'),false);
}
const source=fs.readFileSync('scripts/uiux-b2-shared-dashboard-readiness.mjs','utf8');
equal(source.includes('deadline = start + 30000'),true);equal(source.includes('timeout: budget.remaining()'),true);
equal(source.includes('includeHidden: true'),true);equal(source.includes("name: 'Your workspace', exact: true, level: 1"),true);
equal(/\.fill\(|Sign in|authenticationConfirmed\s*=|\.goto\(|\.reload\(/.test(source),false);
equal(source.includes("page.locator('#fi-main-sidebar')"),true);
console.log(JSON.stringify({status:'PASS',checks,cases:specs.length,scope:'pure budget and controlled orchestration; no browser or application'}));
