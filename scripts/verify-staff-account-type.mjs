import {chromium,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
const dir='docs/screenshots/phase-3d7-f';mkdirSync(dir,{recursive:true});
const control=(action,email)=>{execFileSync('php',['scripts/Prepare-StaffJourney.php',action,...(email?[email]:[])],{stdio:'pipe'});return JSON.parse(readFileSync('storage/app/private/staff-journey.json'));};
const browser=await chromium.launch({channel:'chrome',headless:true}),result={cases:[],errors:[],requests:[]};let page,context;
try{
 const fixture=control('prepare');context=await browser.newContext({viewport:{width:1440,height:1000}});await context.route('**/*',r=>new URL(r.request().url()).origin==='http://127.0.0.1:8017'?r.continue():r.abort());page=await context.newPage();page.on('pageerror',e=>result.errors.push(e.message));
 page.on('request',r=>{if(r.method()==='POST'&&r.url().includes('livewire')){for(const c of r.postDataJSON()?.components||[]){const updates=c.updates||{};result.requests.push({keys:Object.keys(updates),role:updates['data.account_role']??updates.data?.account_role??null,methods:c.calls?.map(a=>a.method)});}}});
 await page.goto('http://127.0.0.1:8017/admin/login');await page.locator('input[type=email]').fill(fixture.overlap.email);await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.waitForURL(u=>!u.pathname.endsWith('/login'));
 for(const mode of ['rapid','held-first-reply','rapid-repeat']){
  await page.goto('http://127.0.0.1:8017/admin/users/create');await page.getByLabel(/Account type/).selectOption('SCHOOL');await expect(page.getByLabel(/Represented institution/)).toBeVisible();await page.getByLabel(/Represented institution/).selectOption(String(fixture.organization.id));await page.getByLabel(/Account type/).selectOption('OSDS');await expect(page.getByLabel(/Authorized region/)).toBeVisible();await page.getByLabel(/Authorized region/).selectOption(String(fixture.region));await page.waitForLoadState('networkidle');
  let release,arrived,heldOnce=false;const held=new Promise(r=>arrived=r),resume=new Promise(r=>release=r);
  if(mode==='held-first-reply')await page.route('**/livewire*/update',async route=>{if(heldOnce)return route.continue();heldOnce=true;const response=await route.fetch();arrived();await resume;await route.fulfill({response});});
  await page.getByLabel(/Account type/).selectOption('SCHOOL');
  if(mode==='held-first-reply')await Promise.race([held,new Promise((_,reject)=>setTimeout(()=>reject(Error('No model request reached reply hold')),10000))]);
  await page.getByLabel(/Account type/).selectOption('OSDS');await page.getByLabel(/Account type/).selectOption('SCHOOL');
  if(mode==='held-first-reply')release();await page.waitForLoadState('networkidle');if(mode==='held-first-reply')await page.unrouteAll({behavior:'wait'});
  const visible=await page.getByLabel(/Account type/).inputValue();const client=await page.evaluate(()=>{const e=document.querySelector('.fi-resource-create-record-page').closest('[wire\\:id]');return window.Livewire.find(e.getAttribute('wire:id')).data.account_role;});
  await page.screenshot({path:dir+'/account-type-'+mode+'-before-save.png'});
  const email='staff-f-race-'+Date.now()+'@example.invalid';await page.getByRole('textbox',{name:/^Name/}).fill('Synthetic account type probe');await page.getByRole('textbox',{name:/^Email address/}).fill(email);
  if(await page.getByLabel(/Authorized region/).isVisible())await page.getByLabel(/Authorized region/).selectOption(String(fixture.region));
  if(await page.getByLabel(/Represented institution/).isVisible())await page.getByLabel(/Represented institution/).selectOption(String(fixture.organization.id));
  await page.getByLabel(/Password/).fill('StaffF-PasswordOnly!');await page.getByRole('button',{name:'Create',exact:true}).click();
  await expect.poll(()=>control('inspect-account-type',email).account_type_probe,{timeout:20000}).not.toBeNull();const persisted=control('inspect-account-type',email).account_type_probe;
  result.cases.push({mode,lastIntent:'SCHOOL',visible,client,persisted});
  expect(visible,'Last selected account type must survive rendering').toBe('SCHOOL');expect(client).toBe('SCHOOL');expect(persisted.roles,'Persisted account must match the last selected type').toEqual(['SCHOOL']);console.log('Passed: '+mode+' retains SCHOOL through actual Create');
 }
 expect(result.errors).toEqual([]);
}catch(e){result.failure=String(e).replaceAll('StaffF-PasswordOnly!','[redacted]').replaceAll('UiBrowserOnly-2026!','[redacted]').slice(0,2000);result.labels=await page.locator('label').allTextContents();result.path=new URL(page.url()).pathname;result.heading=await page.locator('h1').allTextContents();console.error(result.failure);process.exitCode=1;}
finally{writeFileSync(dir+'/account-type-integrity.json',JSON.stringify(result,null,2));await browser.close();}
