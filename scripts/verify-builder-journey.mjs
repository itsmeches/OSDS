import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';

const base='http://127.0.0.1:8017', dir='docs/screenshots/phase-3d7-e';
mkdirSync(dir,{recursive:true});
const fixture=()=>JSON.parse(readFileSync('storage/app/private/builder-journey.json','utf8'));
const control=action=>{execFileSync('php',['scripts/Prepare-BuilderJourney.php',action],{stdio:'pipe'});return fixture();};
const original=fixture();
const results={checks:[],screens:[],errors:[]};
const pass=name=>{results.checks.push(name);console.log('Passed: '+name);};
const browser=await chromium.launch({channel:'chrome',headless:true});
const context=await browser.newContext({viewport:{width:1440,height:1000}});
await context.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
const page=await context.newPage();
page.on('pageerror',e=>results.errors.push(e.message));
page.on('dialog',d=>d.accept());
async function login(p,applicant=false){
    await p.goto(base+(applicant?'/applicant/login':'/admin/login'));
    await p.locator('input[type=email]').fill(applicant?'school@example.invalid':'osds@example.invalid');
    await p.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await p.getByRole('button',{name:'Sign in',exact:true}).click();
    await p.waitForURL(url=>!url.pathname.endsWith('/login'));
}
async function save(){
    const lock=await page.evaluate(()=>window.Livewire.find(document.querySelector('.form-builder').closest('[wire\\:id]').getAttribute('wire:id')).formLock);
    await page.getByRole('button',{name:'Save draft',exact:true}).click();
    await expect.poll(()=>control('inspect').locks[0],{timeout:15000}).toBe(lock+1);
    await expect(page.locator('.builder-header-actions')).toContainText('Saved');
    await expect(page.locator('.builder-alert:visible')).toHaveCount(0);
}
async function assertRules(){
    const current=control('inspect');
    expect(current.questionRules.dependent).toEqual(original.questionRules.dependent);
    expect(current.documentRules.support).toEqual(original.documentRules.support);
}
async function screens(name){
    await expect.poll(async()=>{await page.locator('.fi-no-notification-close-btn').evaluateAll(bs=>bs.forEach(b=>b.click()));return page.locator('.fi-no-notification').count();},{timeout:15000}).toBe(0);
    for(const theme of ['light','dark']) for(const width of [1440,768,375]){
        await page.setViewportSize({width,height:1000});
        await page.evaluate(t=>{localStorage.setItem('theme',t);document.documentElement.classList.toggle('dark',t==='dark');},theme);
        await page.waitForFunction(()=>document.getAnimations().every(a=>a.playState!=='running'||a.effect?.getTiming().iterations===Infinity));
        const axe=await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
        const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
        results.screens.push({name,theme,width,overflow,violations:axe.violations.map(v=>({id:v.id,nodes:v.nodes.map(n=>n.target)}))});
        await page.screenshot({path:`${dir}/${name}-${theme}-${width}.png`});
        expect(overflow).toBe(false);expect(axe.violations).toEqual([]);
    }
    await page.setViewportSize({width:1440,height:1000});
}
try{
    await login(page);
    await page.goto(base+original.builder);
    await page.locator('#question-dependent').click();
    await expect(page.getByLabel('Question title',{exact:true})).toHaveValue(/Conditional information/);
    await page.getByText('Instructions and advanced settings',{exact:true}).click();
    await expect(page.getByRole('note')).toContainText('All rules are preserved');
    await expect(page.locator('.focused-editor')).toContainText('At least one of these must match');
    await expect(page.locator('.focused-editor')).toContainText('Beta');
    await save(); await assertRules(); await page.reload(); await assertRules();
    pass('Complex question and document no-op save/reload preserve exact persisted trees');
    await page.locator('#question-dependent').click();
    await expect(page.getByLabel('Question title',{exact:true})).toHaveValue(/Conditional information/);
    await page.getByLabel('Question title',{exact:true}).fill('Conditional information updated');
    await page.getByRole('heading',{name:'Edit question',exact:true}).click();
    await page.getByRole('button',{name:/2 Required documents/}).click();
    await page.getByText('Applicability condition',{exact:true}).click();
    await expect(page.getByRole('note')).toBeVisible();
    await page.getByRole('button',{name:/3 Review and test/}).click();
    await page.getByRole('button',{name:/1 Build questionnaire/}).click();
    await save(); await assertRules(); pass('Unrelated title edit and all authoring stages preserve complex rules');
    await page.getByRole('button',{name:'Preview',exact:true}).click();
    await page.getByLabel('Test as applicant',{exact:true}).check();
    await expect(page.locator('.builder-preview')).toBeVisible();
    const input=page.locator('.builder-preview').getByLabel('Organization category',{exact:true});
    for(const [value,visible] of [['Alpha',true],['Beta',true],['Blocked',false]]){
        await input.fill(value); await input.blur();
        const field=page.locator('#preview-question-dependent');
        if(visible)await expect(field).toBeVisible();else await expect(field).toHaveCount(0);
        const doc=page.locator('.builder-preview').getByText('Conditional supporting document',{exact:true});
        if(visible)await expect(doc).toBeVisible();else await expect(doc).toHaveCount(0);
    }
    pass('Staff preview preserves nested alternative/conjunction behavior for questions and documents');
    await page.getByRole('button',{name:'Hide preview',exact:true}).click();
    await page.locator('#question-simple').click();
    await expect(page.getByLabel('Question title',{exact:true})).toHaveValue('Simple information');
    const details=page.locator('.focused-editor details');
    if(!await details.getAttribute('open'))await details.locator('summary').click();
    await page.getByLabel('Answer',{exact:true}).fill('Beta'); await page.getByLabel('Answer',{exact:true}).blur();
    await save(); expect(control('inspect').questionRules.simple.value).toBe('Beta');
    await page.getByRole('button',{name:'Remove condition',exact:true}).click();
    await save(); expect(control('inspect').questionRules.simple).toBeNull(); await assertRules();
    pass('Intentional simple edit and explicit clearing persist without rewriting other trees');
    await page.getByRole('button',{name:/2 Required documents/}).click();
    await page.evaluate(async()=>{const id=document.querySelector('.form-builder').closest('[wire\\:id]').getAttribute('wire:id');await window.Livewire.find(id).$set('documentRequirements.0.condition_source','source');});
    await page.getByRole('button',{name:'Save draft',exact:true}).click();
    await expect(page.locator('.builder-alert:visible')).toContainText('read-only'); await assertRules();
    await page.reload(); pass('Forged complex-rule projection is blocked without false save success');
    await page.locator('#question-dependent').click();
    await expect(page.getByLabel('Question title',{exact:true})).toHaveValue(/Conditional information/);
    await page.getByText('Instructions and advanced settings',{exact:true}).click();
    await screens('protected-condition');
    control('publish');
    const applicantContext=await browser.newContext();
    await applicantContext.route('**/*',r=>new URL(r.request().url()).hostname==='127.0.0.1'?r.continue():r.abort());
    const applicant=await applicantContext.newPage(); await login(applicant,true);
    await applicant.goto(base+fixture().draft);
    for(const [value,visible] of [['Beta',true],['Blocked',false]]){
        await applicant.getByLabel('Organization category',{exact:true}).fill(value);
        const child=applicant.getByLabel('Conditional information updated',{exact:true});
        if(visible)await expect(child).toBeVisible();else await expect(child).toHaveCount(0);
        await applicant.getByRole('button',{name:'Save draft',exact:true}).click();
        await expect.poll(()=>control('inspect').savedAnswers?.source,{timeout:15000}).toBe(value);
        expect(control('inspect').applicableDocuments.support).toBe(visible);
        await expect(applicant.locator('.server-save-state')).toContainText('Saved');
        await applicant.getByRole('button',{name:/Documents/}).first().click();
        const doc=applicant.getByText('Conditional supporting document',{exact:true});
        if(visible)await expect(doc).toBeVisible();else await expect(doc).toHaveCount(0);
        await applicant.getByRole('button',{name:/Applicant details/}).first().click();
    }
    await applicantContext.close(); pass('Published exact manifest applicant question/document applicability matches staff preview');
    await page.reload(); await expect(page.getByRole('button',{name:'Save draft',exact:true})).toBeDisabled();
    pass('Published configuration remains read-only'); expect(results.errors).toEqual([]);
}catch(error){writeFileSync('storage/app/private/builder-failure.txt',await page.locator('body').innerText());results.failure=String(error);console.error(error);process.exitCode=1;}
finally{writeFileSync(`${dir}/results.json`,JSON.stringify(results,null,2));await browser.close();}
