import fs from 'node:fs';import crypto from 'node:crypto';import assert from 'node:assert/strict';
import {createWorkflowRouteCandidate} from './uiux-b2-workflow-overview-policy-candidate.mjs';
import {createAttempt14Fatal} from './uiux-b2-attempt-14-fatal.mjs';
const read=p=>fs.readFileSync(p,'utf8'),hash=p=>crypto.createHash('sha256').update(fs.readFileSync(p)).digest('hex');
let checks=0;const eq=(a,b)=>{assert.deepEqual(a,b);checks++;};
const rename=s=>s.replaceAll('attempt-13','attempt-14').replaceAll('Attempt13','Attempt14').replaceAll('Attempt 13','Attempt 14').replaceAll('UIUX_B213','UIUX_B214').replaceAll('b213','b214');
const manifest=JSON.parse(read('scripts/uiux-b2-attempt-14-pins.json'));
eq(manifest.sources.length,30);for(const p of manifest.sources)eq(hash(p.path),p.sha256);
const historical=JSON.parse(read('scripts/uiux-b2-attempt-13-pins.json'));for(const p of historical.sources)eq(hash(p.path),p.sha256);
eq(hash('scripts/uiux-b2-attempt-13-pins.json'),'f5426fa2a5f8c01468de383b98ff612c275880c40580816b45c4013ff27e1e09');
eq(hash('scripts/uiux-b2-workflow-overview-policy-candidate.mjs'),'8eecd269b80509afa02485eda84d0808f76b1e7d3d9ad98ce12175f632561c6e');
eq(fs.existsSync(manifest.path),false);
for(const n of ['Run-UiUxB2Attempt13.mjs','UiUxB2Attempt13Bootstrap.php','Inspect-UiUxB2Attempt13.php','uiux-b2-attempt-13-server.php','uiux-b2-attempt-13-navigation.mjs','uiux-b2-attempt-13-fatal.mjs'])eq(read('scripts/'+rename(n)),rename(read('scripts/'+n)));
const driver=read('scripts/verify-uiux-b2-attempt-14.mjs'),old=rename(read('scripts/verify-uiux-b2-attempt-13.mjs'));
const span=(s,a,b)=>{const i=s.indexOf(a),j=s.indexOf(b,i);assert(i>=0&&j>i);return s.slice(i,j);};
eq(span(driver," mark('login-get-start');",'\ntry{await Promise.race'),span(old," mark('login-get-start');",'\ntry{await Promise.race'));
eq(driver.includes('createAttributedRoute'),false);
eq(driver.includes("from './uiux-b2-workflow-overview-policy-candidate.mjs'"),true);
eq(driver.includes('workflowObserver.dispose()'),true);
eq(driver.indexOf('verifiedNavigation=await navigationVerificationPending;')<driver.indexOf('loginState.authenticationConfirmed=true;'),true);
eq(driver.includes("if(readinessAborted)throw Error('Existing fatal event');"),true);
const provenance=manifest.approvedWorkflowOverview.validation;eq(hash(provenance.path),provenance.sha256);const v=JSON.parse(read(provenance.path));eq(v.status,'PASS');eq(v.cases.length,22);
const log=fs.readFileSync(manifest.documentedLogException.path),e=manifest.documentedLogException;
eq(log.length,e.completeBytes);eq(crypto.createHash('sha256').update(log).digest('hex'),e.completeSha256);
const fixture=JSON.parse(read('storage/app/private/uiux-consolidated-acceptance/gate-b2-workflow-overview-validation/synthetic-snapshots.json'));
const body=loaded=>({components:[{snapshot:JSON.stringify(loaded?fixture.loaded:fixture.initial),updates:{},calls:[{method:loaded?'$refresh':'__lazyLoad',params:loaded?[]:[fixture.token]}]}]});
const login={components:[{snapshot:JSON.stringify({memo:{id:'synthetic-login',name:'Filament\\Auth\\Pages\\Login'}}),updates:{},calls:[{method:'authenticate',params:[]}]}]};
// Execute exactly the route/observer wiring extracted from the actual driver.
// Only page/observer observations and persistence are synthetic; protected driver is never imported.
const wiring=span(driver,' workflowObserver=createWorkflowDashboardObserver', ' const observeRoots=');
const wire=new Function('createWorkflowDashboardObserver','createWorkflowRouteCandidate','page','base','context','fatal','loginState','mark','state',`return (async()=>{let workflowObserver;const endpoint='/livewire-test/update';let phase='login';let submitted=false;let readinessAborted=false;
 const wrappedFatal=(...args)=>{readinessAborted=true;fatal(...args);};
 ${wiring.replace('mark,fatal,','mark,fatal:wrappedFatal,')}
 return {observer:workflowObserver,submitted:()=>submitted,fatal:wrappedFatal};})();`);
const scenarios=['FLOW','DUPLICATE_LOGIN','LIFECYCLE','UNKNOWN_COMPONENT','UNKNOWN_METHOD','UPDATES','EXTERNAL','CONTEXT','FATAL','CAP'];
for(const scenario of scenarios){let handler,loaded=false,observations=0,forwarded=0,aborts=0,loginMarks=0;
 const result={status:'BLOCKED'},loginState={loginPostAttempted:false,authenticationConfirmed:false},signals=[];
 const first=createAttempt14Fatal({result,clock:()=>1,context:()=>({base:'http://synthetic.invalid',url:'http://synthetic.invalid/admin',phase:'login-verifying'}),signal:e=>signals.push(e),writeFirst:()=>{}});
 const context={route:(pattern,h)=>{eq(pattern,'**/*');handler=h;}};
 const page={};
 const createObserver=args=>{eq(args.page,page);return {observe:async()=>{observations++;eq(args.getSubmitted(),true);if(scenario==='CONTEXT'||args.hasFailed())throw Error('Synthetic context rejected');return {dashboardVerified:true,documentVerified:true,widgetCount:1,currentSnapshot:JSON.stringify(loaded?fixture.loaded:fixture.initial),lazyToken:fixture.token};}};};
 const runtime=await wire(createObserver,createWorkflowRouteCandidate,page,'http://synthetic.invalid',context,first.fatal,loginState,type=>{if(type==='login-post-forward')loginMarks++;},{});
 const send=(b,url='http://synthetic.invalid/livewire-test/update')=>handler({request:()=>({url:()=>url,method:()=> 'POST',postDataJSON:()=>b}),continue:()=>{forwarded++;return Promise.resolve();},abort:()=>{aborts++;return Promise.resolve();}});
 await send(login);eq(loginMarks,1);eq(loginState.loginPostAttempted,true);
 if(scenario==='FATAL')runtime.fatal('PAGE_ERROR',Error('Synthetic'));
 let b=body(false);
 if(scenario==='LIFECYCLE')b.components[0].calls[0].method='advance';
 if(scenario==='UNKNOWN_METHOD')b.components[0].calls[0].method='OTHER';
 if(scenario==='UNKNOWN_COMPONENT'){const s=JSON.parse(b.components[0].snapshot);s.memo.name='OTHER';b.components[0].snapshot=JSON.stringify(s);}
 if(scenario==='UPDATES')b.components[0].updates={unexpected:true};
 await send(b,scenario==='EXTERNAL'?'http://external.invalid/probe':undefined);
 if(['FLOW','DUPLICATE_LOGIN','CAP'].includes(scenario)){loaded=true;for(let i=0;i<(scenario==='CAP'?13:2);i++)await send(body(true));}
 if(scenario==='DUPLICATE_LOGIN')await send(login);
 await first.settle();eq(loginState.authenticationConfirmed,false);eq(loginMarks,1);
 const expected=scenario==='FLOW'?null:scenario==='DUPLICATE_LOGIN'?'CONSEQUENTIAL_REPEAT_BLOCKED':scenario==='EXTERNAL'?'EXTERNAL_ORIGIN_BLOCKED':scenario==='FATAL'?'PAGE_ERROR':'COMPONENT_ACTION_BLOCKED';
 eq(result.firstFatal?.reason??null,expected);
 if(scenario==='FLOW'){eq(forwarded,4);eq(aborts,0);eq(observations,3);}else eq(aborts>0,true);
}
console.log(JSON.stringify({status:'PASS',checks,integrationCases:scenarios.length,pins:30,protectedDriverImported:false}));
