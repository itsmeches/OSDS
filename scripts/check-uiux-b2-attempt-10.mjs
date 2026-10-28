// Offline package/orchestration tests. Never import the protected driver.
import fs from 'node:fs';
import crypto from 'node:crypto';
import assert from 'node:assert/strict';
import {createRootAvailabilityObserver} from './uiux-b2-root-availability.mjs';
import {verifySidebarDashboard} from './uiux-b2-attempt-10-navigation.mjs';
const hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
const read=p=>fs.readFileSync(p,'utf8');let checks=0;
const equal=(a,b)=>{assert.deepEqual(a,b);checks++;};
const pins=JSON.parse(read('scripts/uiux-b2-attempt-10-pins.json'));
for(const pin of pins.sources)equal(hash(pin.path),pin.sha256);
equal(pins.sources.length,23);equal(hash('scripts/uiux-b2-root-availability.mjs'),'68cc4348d7666fbe6c88b328b8972c84c0c5255c4c273c684f4fa55e3215fa4c');
const rename=s=>s.replaceAll('attempt-09','attempt-10').replaceAll('Attempt09','Attempt10').replaceAll('Attempt 09','Attempt 10').replaceAll('UIUX_B209','UIUX_B210').replaceAll('b209','b210');
for(const path of ['scripts/Run-UiUxB2Attempt09.mjs','scripts/UiUxB2Attempt09Bootstrap.php','scripts/Inspect-UiUxB2Attempt09.php','scripts/uiux-b2-attempt-09-server.php'])equal(read(rename(path)),rename(read(path)));
const driver=read('scripts/verify-uiux-b2-attempt-10.mjs'),old=rename(read('scripts/verify-uiux-b2-attempt-09.mjs'));
const route=s=>s.slice(s.indexOf(" await context.route("),s.indexOf(' page=await context.newPage()'));
equal(route(driver),route(old));
const login=s=>s.slice(s.indexOf(" page.on('pageerror'"),s.indexOf(" mark('navigation-check-start');"));equal(login(driver),login(old));
equal(driver.split('await ready();').length,3);equal(driver.split("name:'Sign in',exact:true}).click()").length,2);
equal(driver.split('createRootAvailabilityObserver({').length,2);equal(driver.split("await recordRoots('AFTER_ADMIN_DESTINATION')").length,2);
equal(driver.indexOf("await recordRoots('AFTER_ADMIN_DESTINATION')")>driver.indexOf("mark('navigation-check-complete')"),true);
equal(driver.indexOf("await recordRoots('AFTER_ADMIN_DESTINATION')")<driver.indexOf("mark('auth-link-check-start')"),true);
equal(driver.includes("if(navigationVerificationPending)await navigationVerificationPending.catch(()=>{});"),true);
const nav=read('scripts/uiux-b2-attempt-10-navigation.mjs'),oldNav=read('scripts/uiux-b2-attempt-09-navigation.mjs');
const tail=s=>s.slice(s.indexOf("  await check('SIDEBAR_CONDITION_FAILED'"));equal(tail(nav),tail(oldNav));
equal(nav.split("observeRoots('DASHBOARD_FAILURE')").length,2);equal(nav.includes('throw originalFailure;'),true);
const personnel=s=>s.slice(s.indexOf(' await expect(page).toHaveURL(base+'),s.indexOf('try{await Promise.race([workflow(),failed])'));
equal(personnel(driver),personnel(old));equal(driver.includes("'advance'"),false);equal(fs.existsSync(pins.path),false);
const start=driver.indexOf(' const recordRoots=async phase=>{'),end=driver.indexOf('\n };',start)+4;assert(start>0&&end>start);
const build=new Function('observeRoots','result','fs','dir','let rootObservationPending;'+driver.slice(start,end)+';return recordRoots;');
const base='http://127.0.0.1:8044',specs=['VALID','DASHBOARD_MISSING','FAILURE_WRITE','AFTER_WRITE','DESTINATION_CHANGE','FATAL','SIDEBAR_MISSING'];
for(const spec of specs){
 let fatal=false,path='/admin',auth=false,clicks=0,trial=0;const phases=[];const result={status:'BLOCKED'};
 const failed=new Promise(()=>{});
 const page={url:()=>base+path,evaluate:async fn=>fn.name==='inspectRootAvailability'?{
  idRoots:0,snapshotRoots:0,idRootsWithoutSnapshot:0,dashboardSnapshots:0,loginSnapshots:0,otherSnapshots:0,malformed:false,readyState:'complete',destinationMatch:path==='/admin',fatalEvent:false,observationAvailable:true,phase:phases.at(-1)
 }:{dashboard:['DASHBOARD_MISSING','FAILURE_WRITE'].includes(spec)?0:1,login:0,loginForm:false,malformed:false},
 getByRole:()=>({count:async()=>1,isVisible:async()=>true}),locator:()=>({kind:'sidebar',getByRole:()=>link})};
 const link={kind:'link',getAttribute:async()=>base+'/admin/case-demonstration-documents',click:async options=>{if(options?.trial)trial++;else clicks++;}};
 const expect=value=>({toHaveCount:async n=>{if(value.kind==='link'&&spec==='SIDEBAR_MISSING')throw Error('Synthetic missing link');assert.equal(n,1);},toBeVisible:async()=>{}});
 expect.poll=callback=>({toBe:async value=>assert.equal(await callback(),value)});
 const observer=createRootAvailabilityObserver({page:{evaluate:async(fn,args)=>{phases.push(args.phase);return page.evaluate(fn);}},hasFailed:()=>fatal});
 const record=build(observer,result,{writeFileSync:(filename,json,options)=>{
  equal(options.flag,'wx');JSON.parse(json);
  if((spec==='FAILURE_WRITE'&&filename.includes('DASHBOARD_FAILURE'))||spec==='AFTER_WRITE')throw Error('Synthetic write failure');
 }},'synthetic-private');
 let failure;
 try{
  await record('AFTER_ADMIN_DESTINATION');
  if(spec==='DESTINATION_CHANGE')path='/away';if(spec==='FATAL')fatal=true;
  const verified=await verifySidebarDashboard({page,expect,base,failed,hasFailed:()=>fatal,result,observeRoots:record});
  if(fatal)throw Error('Fatal');auth=true;await verified.click();
 }catch(error){failure=error;}
 equal(auth,spec==='VALID');equal(clicks,spec==='VALID'?1:0);
 equal(result.rootAvailability.length,['DASHBOARD_MISSING','FAILURE_WRITE','DESTINATION_CHANGE','FATAL'].includes(spec)?2:1);
 if(spec==='FAILURE_WRITE'){equal(result.verificationConditionFailure,'DASHBOARD_COMPONENT_MISSING');equal(failure.message,'Dashboard diagnostic blocked');equal(result.rootEvidencePersistenceFailed,true);}
 if(spec==='AFTER_WRITE'){equal(result.verificationConditionFailure,'ROOT_OBSERVATION_EVIDENCE_WRITE_FAILED');equal(trial,0);}
 if(spec==='SIDEBAR_MISSING')equal(result.verificationConditionFailure,'SIDEBAR_CONDITION_FAILED');
 if(spec==='FATAL'){equal(result.verificationConditionFailure,'EXISTING_FATAL_EVENT');equal(result.rootAvailability.at(-1).fatalEvent,true);equal(result.rootAvailability.at(-1).observationAvailable,false);}
}
equal(hash('storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel/identities.jsonl').toUpperCase(),'5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B');
equal(driver.includes("result.dashboardEvidencePersistenceFailed=true;result.verificationConditionFailure??='DIAGNOSTIC_EVIDENCE_WRITE_FAILED'"),true);
equal(driver.includes('if(rootObservationPending)await rootObservationPending.catch(()=>{});'),true);
const summary={status:'PASS',checks,orchestrationCases:specs.length,manifest:hash('scripts/uiux-b2-attempt-10-pins.json')};
fs.writeFileSync('storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-10-preparation/integrity-final.json',JSON.stringify(summary,null,2),{flag:'wx'});console.log(JSON.stringify(summary));
