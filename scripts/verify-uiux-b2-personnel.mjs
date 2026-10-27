import fs from 'node:fs';
import path from 'node:path';
import {execFileSync} from 'node:child_process';
import {chromium, expect as baseExpect} from '@playwright/test';

const expect=baseExpect.configure({timeout:30000});
const base='http://127.0.0.1:8044';
const dir='storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel-continuation';
fs.mkdirSync(dir+'/browser-attempt-01');
const credentialPath='storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/write-attempt/credentials.json';
const credentials=JSON.parse(fs.readFileSync(credentialPath,'utf8'));
const ledger=JSON.parse(fs.readFileSync('storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/write-attempt/ownership-ledger.json','utf8'));
const preflight=JSON.parse(fs.readFileSync(dir+'/preflight.json','utf8'));
if(preflight.status!=='PASS')throw new Error('Successful preflight required');
const php=path.join(process.env.TEMP,'osds-phase1-tools/php/php.exe');
const result={status:'BLOCKED',steps:[],errors:[]};
const browser=await chromium.launch({channel:'chrome',headless:true});
const contexts=[];
function inspect(stage){const out=execFileSync(php,[dir+'/Inspect-UiUxB2Personnel.php',stage],{encoding:'utf8'});const status=JSON.parse(out.trim());if(status.status!=='PASS')throw new Error('Inspection failed: '+stage);return JSON.parse(fs.readFileSync(dir+'/'+stage+'.json','utf8'));}
async function idle(page){await page.waitForLoadState('networkidle');}
async function login(role){
 const context=await browser.newContext({viewport:{width:1440,height:1000}});contexts.push(context);
 let updatePath=null;
 await context.route('**/*',async route=>{
  const req=route.request(),url=new URL(req.url());
  if(url.origin!==base)return route.abort();
  if(req.method()==='POST'){
   if(url.pathname!==updatePath){result.errors.push('Blocked unexpected POST path: '+url.pathname);return route.abort();}
   const data=req.postDataJSON();
   for(const component of data.components??[]){for(const call of component.calls??[]){if(!['authenticate','saveOffice','createPersonnel','advance','newOffice','$refresh'].includes(call.method)){result.errors.push('Blocked Livewire action: '+call.method);return route.abort();}}}
  }
  if(req.method()==='GET'&&url.pathname.startsWith('/admin/')&&!['/admin/login','/admin/demonstration-documents','/admin/case-demonstration-documents'].includes(url.pathname))return route.abort();
  return route.continue();
 });
 const page=await context.newPage();page.setDefaultTimeout(30000);page.on('pageerror',e=>result.errors.push(e.message));
 await page.goto(base+'/admin/login');await idle(page);
 const endpoint=new URL(await page.locator('script[data-update-uri]').getAttribute('data-update-uri'),base);
 if(endpoint.origin!==base||!/^\/livewire-[a-f0-9]+\/update$/.test(endpoint.pathname))throw new Error('Unexpected rendered Livewire endpoint');
 updatePath=endpoint.pathname;
 await page.locator('input[type=email]').fill(credentials[role].email);
 await page.locator('input[type=password]').fill(credentials[role].password);
 await page.getByRole('button',{name:'Sign in',exact:true}).click();
 await page.waitForURL(u=>!u.pathname.endsWith('/login'));await idle(page);
 result.steps.push({action:'real login',role,status:'PASS'});
 await context.tracing.start({screenshots:true,snapshots:true});
 return {page,context,role};
}
try{
 const author=await login('osds');const page=author.page;
 await page.goto(base+'/admin/demonstration-documents');await idle(page);
 const pageName='App\\Filament\\Pages\\DemonstrationDocuments';
 const matches=await page.locator('[wire\\:snapshot]').evaluateAll((elements,name)=>elements.flatMap(element=>{
  let snapshot;
  try{snapshot=JSON.parse(element.getAttribute('wire:snapshot'));}catch{throw new Error('Malformed mounted Livewire snapshot');}
  if(!snapshot||typeof snapshot!=='object'||!snapshot.memo||typeof snapshot.memo.name!=='string')throw new Error('Invalid Livewire snapshot identity');
  if(snapshot.memo.name!==name)return [];
  if(!snapshot.data||typeof snapshot.data!=='object'||!Number.isInteger(snapshot.data.expectedDirectory)||!Object.hasOwn(snapshot.data,'officeKey'))throw new Error('Invalid directory page snapshot data');
  return [{name:snapshot.memo.name,expectedDirectory:snapshot.data.expectedDirectory,officeKey:snapshot.data.officeKey}];
 }),pageName);
 if(matches.length!==1)throw new Error('Expected exactly one directory page component; found '+matches.length);
 const data=matches[0];
 if(data.expectedDirectory!==preflight.directory.id||data.officeKey!==null)throw new Error('Mounted directory revision/key mismatch');
 result.pageComponent={name:pageName,count:matches.length,expectedDirectory:data.expectedDirectory,officeKey:data.officeKey};
 await page.getByLabel('Office label (required)',{exact:true}).fill('uiux_20261027_a01 Fictional primary office');
 await page.getByLabel('Director name (required)',{exact:true}).fill('Fictional OSDS Primary Director');
 await page.getByLabel('Designation (required)',{exact:true}).fill('Synthetic demonstration director');
 await page.getByRole('button',{name:'Add demonstration office',exact:true}).click();await idle(page);
 await expect(page.getByRole('heading',{name:'uiux_20261027_a01 Fictional primary office',exact:true})).toBeVisible();
 const directory=inspect('directory');result.steps.push({action:'office saved via UI',id:directory.directory.id,status:'PASS'});
 await page.screenshot({path:dir+'/browser-attempt-01/office.png',fullPage:true});
 for(const code of ['registration','acsa']){
  await page.goto(base+'/admin/case-demonstration-documents');await idle(page);
  await page.getByRole('combobox',{name:'Module',exact:true}).selectOption(String(ledger.modules[code]));
  await page.getByRole('combobox',{name:'Routing region',exact:true}).selectOption(String(ledger.regions.primary));
  await page.getByRole('combobox',{name:'Directory revision',exact:true}).selectOption(String(directory.directory.id));
  await page.getByRole('combobox',{name:'Office entry',exact:true}).selectOption(directory.office_key);
  await page.getByLabel('Source / demonstration review note',{exact:true}).fill('uiux_20261027_a01 fictional primary-region personnel; demonstration only; '+code);
  await page.getByRole('button',{name:'Create personnel mapping',exact:true}).click();await idle(page);
  await expect(page.getByText('uiux_20261027_a01 fictional primary-region personnel; demonstration only; '+code,{exact:true})).toBeVisible();
  result.steps.push({action:'personnel draft via UI',module:code,status:'PASS'});
 }
 const drafts=inspect('drafts');await page.screenshot({path:dir+'/browser-attempt-01/drafts.png',fullPage:true});
 const reviewer=await login('config-reviewer');const review=reviewer.page;
 await review.goto(base+'/admin/case-demonstration-documents');await idle(review);
 for(const version of drafts.personnel){
  const row=review.locator('article').filter({has:review.getByRole('heading',{name:new RegExp('^Personnel #'+version.id+' ·')})});
  await expect(row).toContainText('uiux_20261027_a01 Fictional primary office');
  await row.getByRole('button',{name:'Review version #'+version.id,exact:true}).click();await idle(review);
  await expect(row.getByRole('heading')).toContainText('reviewed');
  await review.screenshot({path:dir+'/browser-attempt-01/reviewed-'+version.id+'.png',fullPage:true});
  result.steps.push({action:'review via UI',id:version.id,status:'PASS'});
  await row.getByRole('button',{name:'Publish version #'+version.id,exact:true}).click();await idle(review);
  await expect(row.getByRole('heading')).toContainText('published');
  result.steps.push({action:'publish via UI',id:version.id,status:'PASS'});
 }
 const published=inspect('published');result.directory=published.directory.id;result.personnel=published.personnel.map(v=>({id:v.id,module_id:v.module_id,state:v.state}));
 await review.screenshot({path:dir+'/browser-attempt-01/published.png',fullPage:true});
 if(result.errors.length)throw new Error('Uncaught browser errors recorded');
 result.status='PASS';
}catch(error){let message=error.message;for(const c of Object.values(credentials)){message=message.replaceAll(c.password,'[REDACTED]');}result.error=message;}
finally{for(let i=0;i<contexts.length;i++){try{await contexts[i].tracing.stop({path:dir+'/browser-attempt-01/context-'+i+'.zip'});}catch{}await contexts[i].close();}await browser.close();fs.writeFileSync(dir+'/browser-attempt-01/results.json',JSON.stringify(result,null,2));}
console.log(JSON.stringify({status:result.status,steps:result.steps,error:result.error??null}));
if(result.status!=='PASS')process.exitCode=1;
