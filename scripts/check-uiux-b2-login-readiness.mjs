import assert from 'node:assert/strict';
import fs from 'node:fs';
import crypto from 'node:crypto';
import {loginRuntimePins,assertLoginRuntimePins,loginReadiness,inspectLoginReadiness,requireLoginReadiness} from './uiux-b2-login-readiness.mjs';
let checks=0;
const equal=(a,b)=>{assert.deepEqual(a,b);checks++;};
const rejects=async fn=>{await assert.rejects(fn);checks++;};
const ready={loginCount:1,wrapperCount:1,connected:true,sameComponent:true,sameWrapper:true,loader:'loaded',ignored:false,initialized:true,revealPresent:true,revealBoolean:true,alpineVersion:'3.17.4'};
equal(loginReadiness(ready),'READY');
for(const loader of ['init','await'])equal(loginReadiness({...ready,loader,ignored:true,initialized:false,revealPresent:false,revealBoolean:false}),'PENDING');
for(const update of [{loginCount:0},{loginCount:2},{wrapperCount:0},{wrapperCount:2},{connected:false},{sameComponent:false},{sameWrapper:false},{loader:'unknown'},{ignored:true},{initialized:false},{revealPresent:false},{revealBoolean:false},{alpineVersion:'changed'},{initialized:'secret'},{extra:'secret'}])equal(loginReadiness({...ready,...update}),'INVALID');
for(const value of [null,{},Object.fromEntries(Object.entries(ready).filter(([key])=>key!=='initialized'))])equal(loginReadiness(value),'INVALID');
assertLoginRuntimePins({...loginRuntimePins});checks++;
for(const key of Object.keys(loginRuntimePins)){assert.throws(()=>assertLoginRuntimePins({...loginRuntimePins,[key]:'changed'}));checks++;}
assert.throws(()=>assertLoginRuntimePins({...loginRuntimePins,extra:'unknown'}));checks++;
const installed=JSON.parse(fs.readFileSync('vendor/composer/installed.json')).packages;
const version=name=>installed.find(p=>p.name===name)?.version;
const hash=path=>crypto.createHash('sha256').update(fs.readFileSync(path)).digest('hex');
assertLoginRuntimePins({laravel:version('laravel/framework'),filament:version('filament/filament'),livewire:version('livewire/livewire'),alpine:fs.readFileSync('vendor/livewire/livewire/dist/livewire.js','utf8').match(/version:\s*["']([^"']+)/)?.[1],livewireHash:hash('vendor/livewire/livewire/dist/livewire.js'),supportHash:hash('vendor/filament/support/dist/index.js'),textInputHash:hash('vendor/filament/forms/src/Components/TextInput.php'),loginHash:hash('vendor/filament/filament/src/Auth/Pages/Login.php'),passwordHash:hash('public/js/filament/forms/components/text-input/password-reveal.js')});checks++;
equal(hash('public/js/filament/support/support.js'),loginRuntimePins.supportHash);

// Browser callback executed only against in-memory metadata/node doubles.
let wrappers=[],roots=[];
const scope={isPasswordRevealed:false};Object.defineProperty(scope,'password',{get(){throw Error('Sensitive property accessed');}});
const wrapper={isConnected:true,_x_async:'loaded',_x_ignore:false,_x_dataStack:[scope],getAttribute:key=>key==='x-data'?'passwordRevealFormComponent()':null};
const component={isConnected:true,getAttribute:()=>JSON.stringify({memo:{name:'Filament\\Auth\\Pages\\Login'}}),querySelectorAll:()=>wrappers};
roots=[component];wrappers=[wrapper];
const inspect=new Function('document','window','return ('+inspectLoginReadiness.toString()+')')({querySelectorAll:()=>roots},{Alpine:{version:'3.17.4'}});
const nodes=inspect({mode:'capture'});
equal(loginReadiness(inspect({nodes})),'READY');equal(inspect({nodes,mode:'wait'}),true);
wrapper._x_async='await';wrapper._x_dataStack=[];wrapper._x_ignore=true;
equal(loginReadiness(inspect({nodes})),'PENDING');equal(inspect({nodes,mode:'wait'}),false);
wrapper._x_async='loaded';wrapper._x_dataStack=[scope];wrapper._x_ignore=false;
wrappers=[{...wrapper}];equal(loginReadiness(inspect({nodes})),'INVALID');assert.throws(()=>inspect({nodes,mode:'wait'}));checks++;
wrappers=[wrapper];wrapper.isConnected=false;assert.throws(()=>inspect({nodes,mode:'wait'}));checks++;wrapper.isConnected=true;
wrappers=[wrapper,wrapper];assert.throws(()=>inspect({nodes}));checks++;wrappers=[wrapper];
roots=[component,component];assert.throws(()=>inspect({nodes}));checks++;roots=[component];
wrapper._x_async='changed';assert.throws(()=>inspect({nodes,mode:'wait'}));checks++;wrapper._x_async='loaded';
equal(Object.keys(inspect({nodes})).sort(),Object.keys(ready).sort());
wrapper._x_dataStack=[{}];equal(loginReadiness(inspect({nodes})),'INVALID');
wrapper._x_dataStack=[Object.create({isPasswordRevealed:false})];equal(loginReadiness(inspect({nodes})),'INVALID');
wrapper._x_dataStack=[scope];wrapper._x_ignore='unknown';assert.throws(()=>inspect({nodes}));checks++;wrapper._x_ignore=false;

const never=new Promise(()=>{});
async function actionWhenReady(options){let count=0;await requireLoginReadiness(options);count++;return count;}
const defaults={sample:async()=>ready,waitUntilLoaded:async()=>{throw Error('Unexpected wait');},failed:never,hasFailed:()=>false};
equal(await actionWhenReady(defaults),1);
let samples=0,waits=0;
equal(await actionWhenReady({...defaults,sample:async()=>++samples===1?{...ready,loader:'await'}:ready,waitUntilLoaded:async()=>{waits++;}}),1);equal([samples,waits],[2,1]);
let actions=0;
for(const update of [{connected:false},{sameWrapper:false},{initialized:false},{revealPresent:false}]){
 await rejects(async()=>{await requireLoginReadiness({...defaults,sample:async()=>({...ready,...update})});actions++;});
}
await rejects(async()=>{await requireLoginReadiness({...defaults,sample:async()=>({...ready,loader:'await'}),waitUntilLoaded:async()=>{throw Error('Semantic timeout');}});actions++;});
equal(actions,0);
let sampled=false;
await rejects(()=>requireLoginReadiness({...defaults,hasFailed:()=>true,sample:async()=>{sampled=true;return ready;}}));equal(sampled,false);
let aborted=false;
await rejects(()=>requireLoginReadiness({...defaults,sample:async()=>{aborted=true;return ready;},hasFailed:()=>aborted}));
let fail;
const failed=new Promise((_,reject)=>fail=reject);failed.catch(()=>{});
await rejects(()=>requireLoginReadiness({...defaults,sample:async()=>({...ready,loader:'await'}),waitUntilLoaded:()=>{fail(Error('Page error'));return never;},failed}));

// Guard both sides of credential entry: replacement before submission blocks it.
let phase='entry',fills=0,signIns=0;
const options={...defaults,sample:async()=>phase==='entry'?ready:{...ready,sameWrapper:false}};
await rejects(async()=>{await requireLoginReadiness(options);fills++;phase='submit';await requireLoginReadiness(options);signIns++;});equal([fills,signIns],[1,0]);
await requireLoginReadiness(defaults);await requireLoginReadiness(defaults);signIns++;equal(signIns,1);
const driver=fs.readFileSync(new URL('./verify-uiux-b2-reviewer-only.mjs',import.meta.url),'utf8');
equal((driver.match(/await ready\(\);/g)||[]).length,2);
equal(driver.indexOf('await ready();')<driver.indexOf("mark('email-fill-start')"),true);
equal(driver.lastIndexOf('await ready();')<driver.indexOf("mark('sign-in-click-start')"),true);
equal((driver.match(/getByRole\('button',\{name:'Sign in',exact:true\}\)\.click\(\)/g)||[]).length,1);
equal(driver.includes("{timeout:30000,polling:'raf'}"),true);
equal(driver.includes('waitForTimeout')||driver.includes('.jsonValue()'),false);
console.log(JSON.stringify({status:'PASS',checks,scope:'pure readiness, mock metadata and async failure controls; no browser or Laravel'}));
