import {chromium,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync} from 'node:fs';
const f=JSON.parse(readFileSync('storage/app/private/b3-scenario.json')),base='http://127.0.0.1:8039';
const control=(...args)=>JSON.parse(execFileSync('php',['scripts/Prepare-B3.php',...args],{encoding:'utf8'}));
const document=control('inspect').documents[0];expect(document.state).toBe('completed');
const url=base+'/case-demonstration-documents/'+document.public_id+'/download';
const browser=await chromium.launch({channel:'chrome',headless:true}),result={checks:[]};
async function login(email,applicant=false){const context=await browser.newContext();await context.route('**/*',r=>new URL(r.request().url()).origin===base?r.continue():r.abort());const page=await context.newPage();await page.goto(base+(applicant?'/applicant/login':'/admin/login'));await page.waitForLoadState('networkidle');await page.locator('input[type=email]').fill(email);await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');await page.getByRole('button',{name:'Sign in',exact:true}).click();await page.waitForURL(u=>!u.pathname.endsWith('/login'));return context;}
try {
 const staff=await login(f.operator.email);expect((await staff.request.get(url)).status()).toBe(200);control('revoke-download');expect((await staff.request.get(url)).status()).toBe(403);await staff.close();result.checks.push({staffMountedDownloadPermissionLoss:true,passed:true});
 const applicant=await login(f.applicant.email,true);expect((await applicant.request.get(url)).status()).toBe(200);control('revoke-representation');expect((await applicant.request.get(url)).status()).toBe(403);await applicant.close();result.checks.push({applicantMountedRepresentationLoss:true,passed:true});
 const unrelated=await login(f.manager.email);expect((await unrelated.request.get(url)).status()).toBe(403);expect((await unrelated.request.get(base+'/case-demonstration-documents/00000000-0000-4000-8000-000000000000/download')).status()).toBe(404);await unrelated.close();result.checks.push({unrelatedStaffAndForgedIdDenial:true,passed:true});
 expect(control('inspect').attempts).toBe(2);
} catch(e){result.failure=String(e);process.exitCode=1;console.error(e);}
finally {writeFileSync('docs/screenshots/b3-demonstration-pdfs/access-results.json',JSON.stringify(result,null,2));await browser.close();}
