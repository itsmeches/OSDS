import {chromium,expect as baseExpect} from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import {execFileSync} from 'node:child_process';
import {mkdirSync,writeFileSync} from 'node:fs';
const dir='docs/screenshots/b1-decision-foundation';mkdirSync(dir,{recursive:true});
const control=(...args)=>JSON.parse(execFileSync('php',['scripts/Prepare-B1.php',...args],{encoding:'utf8'}));
const expect=baseExpect.configure({timeout:30000});
const browser=await chromium.launch({channel:'chrome',headless:true}),result={checks:[],captures:[],errors:[]};
let context,page;const sessions=new Map();
async function login(email,applicant=false){if(sessions.has(email)){({context,page}=sessions.get(email));return;}context=await browser.newContext({viewport:{width:1440,height:1000}});await context.route('**/*',r=>new URL(r.request().url()).origin==='http://127.0.0.1:8037'?r.continue():r.abort());page=await context.newPage();page.setDefaultTimeout(60000);page.setDefaultNavigationTimeout(60000);page.on('pageerror',e=>result.errors.push(e.message));page.on('dialog',d=>d.accept());await page.goto('http://127.0.0.1:8037'+(applicant?'/applicant/login':'/admin/login'));await page.waitForLoadState('networkidle');if(!applicant)await page.waitForFunction(()=>window.Livewire?.all().length>0);await page.locator('input[type=email]').fill(email);await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');await page.getByRole('button',{name:/sign in/i}).click();await page.waitForURL(u=>!u.pathname.endsWith('/login'),{waitUntil:'domcontentloaded'});sessions.set(email,{context,page});}
async function open(f){await page.goto('http://127.0.0.1:8037/admin/synthetic-application-intake?application='+f.public_id);await expect(page.getByRole('heading',{name:'Three-stage demonstration decision'})).toBeVisible();await page.waitForLoadState('networkidle');await page.waitForFunction(()=>window.Livewire?.all().length>0);}
async function capture(name){await expect.poll(async()=>{await page.locator('.fi-no-notification-close-btn').evaluateAll(bs=>bs.forEach(b=>b.click()));return page.locator('.fi-no-notification').count();},{timeout:15000}).toBe(0);await page.evaluate(async()=>{await Promise.all(document.getAnimations().filter(a=>a.effect?.getTiming().iterations!==Infinity).map(a=>a.finished.catch(()=>{})));});const a=await new AxeBuilder({page}).options({rules:{'target-size':{enabled:true}}}).analyze();const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);await page.screenshot({path:dir+'/'+name+'.png',fullPage:true});result.captures.push({name,violations:a.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)})),overflow});expect(a.violations).toEqual([]);expect(overflow).toBe(false);}
async function stage(f,role,button){await login(f.staff[role].email);await open(f);await page.getByRole('textbox',{name:'Applicant-facing stage summary (required)',exact:true}).fill('Synthetic '+button);await page.getByRole('textbox',{name:'Internal review notes (staff only)',exact:true}).fill('PRIVATE_B1_BROWSER_NOTE');if(role==='evaluator')await page.getByRole('checkbox',{name:/I reviewed this exact/}).check();await page.getByRole('button',{name:button,exact:true}).click();await expect(page.getByText('Demonstration decision stage recorded',{exact:true})).toBeVisible();}
try{
 for(const [module,outcome] of [['registration','approved'],['acsa','rejected']]){
  const f=control('browser',module);
  await login(f.staff.director.email);await open(f);await expect(page.getByRole('button',{name:'Approve demonstration application',exact:true})).toHaveCount(0);
  await stage(f,'evaluator','Record technical evaluation');
  await stage(f,'chief','Return for reevaluation');
  await stage(f,'evaluator','Record technical evaluation');
  await stage(f,'chief','Recommend approval');
  await stage(f,'director',outcome==='approved'?'Approve demonstration application':'Reject demonstration application');
  const saved=control('inspect');expect(saved.state).toBe(outcome);expect(saved.stages).toHaveLength(5);expect(saved.stages.filter(s=>s.stage_role==='director')).toHaveLength(1);
  await capture(module+'-staff-light');
  await page.setViewportSize({width:375,height:900});await page.evaluate(()=>{localStorage.setItem('theme','dark');document.documentElement.classList.add('dark');});await capture(module+'-staff-dark-mobile');
  await login(f.applicant.email,true);await page.goto('http://127.0.0.1:8037/applicant/applications/'+f.public_id);await expect(page.getByText('Synthetic '+(outcome==='approved'?'Approve demonstration application':'Reject demonstration application'),{exact:true})).toBeVisible();expect(await page.locator('body').innerText()).not.toContain('PRIVATE_B1_BROWSER_NOTE');await capture(module+'-applicant-light');
  await page.setViewportSize({width:375,height:900});await page.evaluate(()=>{localStorage.setItem('theme','dark');document.documentElement.classList.add('dark');});await capture(module+'-applicant-dark-mobile');
  await login(f.staff.evaluator.email);await open(f);await expect(page.getByRole('button',{name:'Record technical evaluation',exact:true})).toHaveCount(0);
  result.checks.push({module,outcome,chiefReturn:true,missingPrerequisiteHidden:true,immutableTerminal:true,applicantNotesPrivate:true,passed:true});
 }
 const f=control('browser','registration');await login(f.staff.evaluator.email);await open(f);control('revoke');await page.getByRole('textbox',{name:'Applicant-facing stage summary (required)',exact:true}).fill('Denied stale authority');await page.getByRole('checkbox',{name:/I reviewed this exact/}).check();const deniedResponse=page.waitForResponse(r=>r.url().includes('livewire')&&r.request().method()==='POST');await page.getByRole('button',{name:'Record technical evaluation',exact:true}).click();expect((await deniedResponse).status()).toBe(403);expect(control('inspect').stages).toHaveLength(0);result.checks.push({mountedPermissionLoss:true,passed:true});
 expect(result.errors).toEqual([]);
}catch(e){result.failure=String(e);result.url=page?.url();if(page)writeFileSync('storage/app/private/b1-browser-page.txt',await page.locator('body').innerText().catch(()=>''));console.error(e);process.exitCode=1;}
finally{writeFileSync(dir+'/browser-results.json',JSON.stringify(result,null,2));await browser.close();}
