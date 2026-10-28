import fs from 'node:fs';
const sourcePins=JSON.parse(fs.readFileSync('scripts/uiux-b2-attempt-06-pins.json'));

import {chromium,expect} from '@playwright/test';
import {pageName,validateRequest,componentSnapshots} from './uiux-b2-reviewer-policy.mjs';
import {sanitizeException,createEventRecorder} from './uiux-b2-error-diagnostics.mjs';
import crypto from 'node:crypto';
import {assertLoginRuntimePins,inspectLoginReadiness,requireLoginReadiness} from './uiux-b2-login-readiness.mjs';
for(const pin of sourcePins.sources)if(crypto.createHash('sha256').update(fs.readFileSync(pin.path)).digest('hex')!==pin.sha256)throw Error('Attempt 06 source changed');
const dir='storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-06';
if(process.argv.length!==2||!fs.existsSync(dir+'/reserved.json')||fs.existsSync(dir+'/browser-started.json'))throw Error('Reserved single execution required');
const installed=JSON.parse(fs.readFileSync('vendor/composer/installed.json')).packages;
const version=name=>installed.find(p=>p.name===name)?.version;
const sourceHash=path=>crypto.createHash('sha256').update(fs.readFileSync(path)).digest('hex');
assertLoginRuntimePins({laravel:version('laravel/framework'),filament:version('filament/filament'),livewire:version('livewire/livewire'),
 alpine:fs.readFileSync('vendor/livewire/livewire/dist/livewire.js','utf8').match(/version:\s*["']([^"']+)/)?.[1],
 livewireHash:sourceHash('vendor/livewire/livewire/dist/livewire.js'),supportHash:sourceHash('vendor/filament/support/dist/index.js'),
 textInputHash:sourceHash('vendor/filament/forms/src/Components/TextInput.php'),loginHash:sourceHash('vendor/filament/filament/src/Auth/Pages/Login.php'),
 passwordHash:sourceHash('public/js/filament/forms/components/text-input/password-reveal.js')});
if(sourceHash('public/js/filament/support/support.js')!==sourceHash('vendor/filament/support/dist/index.js'))throw Error('Published support changed');
fs.writeFileSync(dir+'/browser-started.json',JSON.stringify({time:new Date().toISOString()}),{flag:'wx'});
const pre=JSON.parse(fs.readFileSync(dir+'/preflight.json'));
if(pre.status!=='PASS'||pre.personnel.map(v=>v.id).join()!=='47,48'||pre.personnel.some(v=>v.state!=='draft'||v.lock_version!==1))throw Error('Exact untouched drafts required');
const credentials=JSON.parse(fs.readFileSync('storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/write-attempt/credentials.json'))['config-reviewer'];
const base='http://127.0.0.1:8044',result={status:'BLOCKED',phases:[],steps:[]};
let browser,context,page,phase='login',endpoint=null,submitted=false,stop;
let loginStage='login-get';
const loginState={loginFormDisplayed:false,loginPostAttempted:false,authenticationConfirmed:false};
const events=createEventRecorder();
const diagnosticContext=()=>({...loginState,phase:phase==='login'?loginStage:phase,url:page?.url()??base});
const mark=(type,extra={})=>events.record(type,{...diagnosticContext(),...extra});
const diagnostic=(error,event)=>({...sanitizeException(error,diagnosticContext(),[credentials.email,credentials.password]),eventSequence:event.sequence,elapsed_ms:event.elapsed_ms});
const failed=new Promise((_,reject)=>{stop=reject;});failed.catch(()=>{});
let readinessAborted=false;
const fatal=message=>{readinessAborted=true;stop(Error(message));};
async function workflow(){
 browser=await chromium.launch({channel:'chrome',headless:true});context=await browser.newContext({viewport:{width:1440,height:1000}});
 await context.route('**/*',async route=>{try{
  const req=route.request(),url=new URL(req.url());
  if(url.origin!==base){fatal('External request blocked');return route.abort();}
  if(req.method()==='POST'){
   if(url.pathname!==endpoint)throw Error('Unexpected POST endpoint');
   const kind=validateRequest(req.postDataJSON(),phase);
   if(kind==='login')loginState.loginPostAttempted=true;
   if(kind!=='notification'){if(submitted)throw Error('Repeated consequential request blocked');submitted=true;}
   mark(kind==='login'?'login-post-forward':kind==='notification'?'notification-post-forward':'lifecycle-post-forward');
  }else if(req.method()!=='GET')throw Error('Unexpected HTTP method');
  if(req.method()==='GET'&&url.pathname.startsWith('/admin')&&!['/admin','/admin/login','/admin/case-demonstration-documents'].includes(url.pathname))throw Error('Unexpected admin navigation');
  return route.continue();
 }catch(error){fatal(error.message);return route.abort();}});
 page=await context.newPage();page.setDefaultTimeout(30000);
 page.on('pageerror',error=>{if(!result.pageError){const event=mark('page-error');result.pageError=diagnostic(error,event);}fatal('JavaScript page error');});
 page.on('response',response=>{if(new URL(response.url()).origin===base){const event=mark('http-response',{url:response.url(),method:response.request().method(),status:response.status()});if(result.phases.length<80)result.phases.push(event);if(response.status()>=400)fatal('HTTP error '+response.status());}});
 page.on('requestfailed',()=>{mark('request-failed');fatal('Browser request failed');});
 mark('login-get-start');
 await page.goto(base+'/admin/login',{waitUntil:'domcontentloaded'});
 mark('login-get-complete');
 const uri=await page.locator('script[data-update-uri]').getAttribute('data-update-uri');const u=new URL(uri,base);
 if(u.origin!==base||!/^\/livewire-[a-f0-9]+\/update$/.test(u.pathname))throw Error('Invalid rendered endpoint');endpoint=u.pathname;
 await expect(page.locator('input[type=email]')).toBeVisible();
 await expect(page.locator('input[type=password]')).toBeVisible();
 await expect(page.getByRole('button',{name:'Sign in',exact:true})).toBeVisible();
 loginState.loginFormDisplayed=true;
 mark('controls-visible');mark('component-check-start');
 const loginComponents=await page.locator('[wire\\:snapshot]').evaluateAll(es=>es.map(e=>JSON.parse(e.getAttribute('wire:snapshot')).memo?.name).filter(n=>n==='Filament\\Auth\\Pages\\Login'));
 if(loginComponents.length!==1)throw Error('Expected one Filament login component');
 mark('component-check-complete');
 loginStage='login-ready';
 const loginNodes=await page.evaluateHandle(inspectLoginReadiness,{mode:'capture'});
 const ready=()=>requireLoginReadiness({sample:()=>page.evaluate(inspectLoginReadiness,{nodes:loginNodes}),
  waitUntilLoaded:async()=>{const value=await page.waitForFunction(inspectLoginReadiness,{nodes:loginNodes,mode:'wait'},{timeout:30000,polling:'raf'});await value.dispose();},
  failed,hasFailed:()=>readinessAborted});
 try{
 await ready();
 mark('email-fill-start');await page.locator('input[type=email]').fill(credentials.email);mark('email-fill-complete');
 mark('password-fill-start');await page.locator('input[type=password]').fill(credentials.password);mark('password-fill-complete');
 await ready();
 loginStage='login-submit';mark('sign-in-click-start');await page.getByRole('button',{name:'Sign in',exact:true}).click();mark('sign-in-click-complete');loginStage='login-verifying';
 }finally{await loginNodes.dispose();}
 mark('navigation-check-start');
 await page.waitForURL(u=>u.origin===base&&u.pathname==='/admin',{waitUntil:'domcontentloaded'});
 mark('navigation-check-complete');mark('auth-link-check-start');
 await expect(page.getByRole('link',{name:'Case-linked demonstration PDFs',exact:true})).toBeVisible();
 mark('auth-link-check-complete');
 loginState.authenticationConfirmed=true;
 mark('authentication-confirmed');
 result.steps.push({action:'real reviewer login',status:'PASS'});phase='idle';
 // Read-only reviewer navigation. No lifecycle action, trace or screenshot.
 await page.getByRole('link',{name:'Case-linked demonstration PDFs',exact:true}).click();
 await expect(page).toHaveURL(base+'/admin/case-demonstration-documents');
 const snapshots=await page.locator('[wire\\:snapshot]').evaluateAll(es=>es.map(e=>e.getAttribute('wire:snapshot')));
 componentSnapshots(snapshots);
 for(const v of pre.personnel){
  const heading=page.getByRole('heading',{name:'Personnel #'+v.id+' · version 1 · draft',exact:true});
  await expect(heading).toHaveCount(1);
 }
 result.steps.push({action:'authorized reviewer page, unchanged drafts',status:'PASS',ids:[47,48]});
 result.status='PASS';
}
try{await Promise.race([workflow(),failed]);}catch(error){result.error='Reviewer workflow stopped';result.failure=diagnostic(error,mark('workflow-failure'));}
finally{if(context){await context.close();}if(browser)await browser.close();result.events=events.events;result.droppedEvents=events.dropped;fs.writeFileSync(dir+'/browser-result.json',JSON.stringify(result,null,2),{flag:'wx'});}
console.log(JSON.stringify({status:result.status,steps:result.steps}));if(result.status!=='PASS')process.exitCode=1;
