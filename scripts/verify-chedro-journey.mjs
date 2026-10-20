import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const base = 'http://127.0.0.1:8017';
const dir = 'docs/screenshots/phase-3d7-d';
mkdirSync(dir, { recursive: true });
const fixture = () => JSON.parse(readFileSync('storage/app/private/chedro-journey.json', 'utf8'));
const control = action => {execFileSync('php', ['scripts/Prepare-ChedroJourney.php', action], {stdio:'pipe'}); return fixture();};
const data = fixture();
const result = { interactions: [], screens: [], errors: [] };
const pass = name => {result.interactions.push(name); console.log('Passed: '+name);};
const browser = await chromium.launch({channel:'chrome', headless:true});
const context = await browser.newContext({viewport:{width:1440,height:1000}});
await context.route('**/*', route => new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort());
const page = await context.newPage();
page.on('dialog', dialog => dialog.accept());
page.on('pageerror', error => result.errors.push(error.name+': '+error.message.slice(0,160)));
async function login(p, applicant=false, emailOverride=null) {
    await p.goto(base+(applicant?'/applicant/login':'/admin/login'));
    if (process.argv.includes('--debug')) console.log('Login page', p.url(), await p.locator('label').allTextContents(), (await p.locator('body').innerText()).slice(0,350));
    await p.locator('input[type="email"]').fill(emailOverride || (applicant?'school@example.invalid':data.reviewer));
    await p.locator('input[type="password"]').fill('UiBrowserOnly-2026!');
    await p.getByRole('button',{name:'Sign in',exact:true}).click();
    await p.waitForURL(url => !url.pathname.endsWith('/login'));
}
async function screens(name) {
    // Exercise dismissal before measuring stable pages. Auto-dismiss fade opacity
    // can cross axe's contrast threshold during a multi-viewport scan.
    await page.locator('.fi-no-notification-close-btn').evaluateAll(buttons=>buttons.forEach(button=>button.click()));
    await expect(page.locator('.fi-no-notification')).toHaveCount(0);
    for (const theme of ['light','dark']) for (const width of [1440,768,375]) {
        await page.setViewportSize({width,height:1000});
        await page.evaluate(theme => {localStorage.setItem('theme',theme); document.documentElement.classList.toggle('dark',theme==='dark');},theme);
        await page.waitForTimeout(150);
        const axe = await new AxeBuilder({page}).withTags(['wcag2a','wcag2aa','wcag21aa','wcag22aa']).analyze();
        const overflow = await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+1);
        const contrast = await page.evaluate(() => {
            const canvas=document.createElement('canvas'); canvas.width=canvas.height=1; const ctx=canvas.getContext('2d',{willReadFrequently:true});
            const rgba=color=>{ctx.clearRect(0,0,1,1);ctx.fillStyle=color;ctx.fillRect(0,0,1,1);const c=[...ctx.getImageData(0,0,1,1).data];c[3]/=255;return c;};
            const blend=(a,b)=>[0,1,2].map(i=>a[i]*a[3]+b[i]*(1-a[3])).concat(1);
            const lum=c=>c.slice(0,3).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4).reduce((n,v,i)=>n+v*[.2126,.7152,.0722][i],0);
            return ['.intake-primary-action','.intake-field input','.intake-field select','.intake-field textarea','.staff-queue-table td','.intake-banner'].flatMap(selector=>[...document.querySelectorAll(selector)].filter(e=>e.getBoundingClientRect().height&&!e.disabled).slice(0,3).map(e=>{const nodes=[];for(let n=e;n;n=n.parentElement)nodes.unshift(n);const bg=nodes.reduce((c,n)=>blend(rgba(getComputedStyle(n).backgroundColor),c),[255,255,255,1]);const fg=blend(rgba(getComputedStyle(e).color),bg);const a=lum(bg),b=lum(fg);return {selector,ratio:+((Math.max(a,b)+.05)/(Math.min(a,b)+.05)).toFixed(2)};}));
        });
        result.screens.push({name,theme,width,overflow,contrast,violations:axe.violations.map(v=>({id:v.id,targets:v.nodes.map(n=>n.target)}))});
        await page.screenshot({path:`${dir}/${name}-${theme}-${width}.png`});
    }
    await page.setViewportSize({width:1440,height:1000});
}
async function apply() { await page.getByRole('button',{name:'Apply filters',exact:true}).click(); await expect(page.getByText('Updating queue…',{exact:true})).toBeHidden(); }
async function openDocument() {
    let authorizedDestination=false;
    const inspect=request=>{if(new URL(request.url()).hostname==='docs.google.com') authorizedDestination=true;};
    context.on('request',inspect);
    const opened=page.waitForEvent('popup');
    await page.getByRole('link',{name:/Open document/}).first().click();
    const popup=await opened;
    await expect.poll(()=>authorizedDestination).toBe(true);
    await popup.close(); context.off('request',inspect);
}
try {
    await login(page);
    for (const [path,name] of [['/admin/synthetic-application-intake','intake-queue'],['/admin/synthetic-evidence-reviews','evidence-queue']]) {
        await page.goto(base+path);
        await expect(page.locator('.staff-queue-table tbody tr')).toHaveCount(10);
        await expect(page.getByText(/12 (applications|test reviews) found/)).toBeVisible();
        await page.getByRole('button',{name:'Next page',exact:true}).click();
        await expect(page.locator('.staff-queue-table tbody tr')).toHaveCount(2);
        await page.getByLabel('Search organization',{exact:true}).fill('Batch D school 01');
        await apply();
        await expect(page.locator('.staff-queue-table tbody tr')).toHaveCount(1);
        await expect(page.locator('.staff-queue-table')).toContainText('Batch D school 01');
        await page.locator('.staff-queue-advanced summary').click();
        await page.getByLabel('Service',{exact:true}).selectOption('registration');
        await apply();
        await expect(page.getByText(/1 (applications|test reviews) found/)).toBeVisible();
        await page.getByLabel('Search organization',{exact:true}).fill('not a matching school');
        await apply();
        await expect(page.getByText('No matching assigned', {exact:false})).toBeVisible();
        await page.getByRole('button',{name:'Clear filters',exact:true}).click();
        await expect(page.locator('.staff-queue-table tbody tr')).toHaveCount(10);
        await page.getByLabel('Search organization',{exact:true}).focus();
        await page.keyboard.press('Tab');
        await expect(page.locator('.staff-queue-advanced summary')).toBeFocused();
        if(await page.locator('.staff-queue-advanced').evaluate(e=>e.open)) await page.locator('.staff-queue-advanced summary').click();
        await screens(name);
        pass(name+': scoped counts, search, service filter, empty result, pagination reset and keyboard');
    }
    if (!process.argv.includes('--queues-only')) {
    await page.goto(base+'/admin/synthetic-application-intake?application='+data.applications[0]);
    await expect(page.getByText('Exact Mixed_CASE answer 1',{exact:true})).toBeVisible();
    await page.getByRole('button',{name:'Start screening',exact:true}).click();
    await expect(page.getByRole('heading',{name:'Request a correction',exact:true})).toBeVisible();
    await openDocument();
    pass('Application document opens through the authorized route; external content blocked');
    await page.getByRole('button',{name:'Send correction request',exact:true}).click();
    await expect(page.locator('.intake-field').filter({hasText:'Applicant summary'}).getByRole('alert')).toBeVisible();
    await page.getByLabel('Applicant summary').fill('Please clarify the organization name.');
    await page.getByLabel('Correction applies to').selectOption('question');
    await page.getByLabel('Question',{exact:true}).selectOption(String(data.questions.name));
    await page.getByLabel('Instruction for the applicant').fill('Change the organization name to Corrected Mixed_CASE answer.');
    await screens('intake-correction');
    await page.route('**/livewire-*/*', async route => {
        const payload=route.request().postDataJSON();
        if(payload?.components?.some(c=>c.calls?.some(call=>call.method==='requestCorrection'))) {
            await route.fetch(); await route.abort();
        } else await route.continue();
    });
    await page.getByRole('button',{name:'Send correction request',exact:true}).click();
    await expect(page.locator('[data-reviewer-recovery]')).toBeVisible();
    expect(control('inspect').state).toBe('correction_requested');
    expect(fixture().correctionCount).toBe(1);
    await page.unroute('**/livewire-*/*');
    await page.getByRole('button',{name:'Refresh and check saved state',exact:true}).click();
    await expect(page.getByText('Waiting for the applicant',{exact:true})).toBeVisible();
    expect(control('inspect').state).toBe('correction_requested');
    pass('Actual screening, correction validation and committed-response-loss recovery without automatic retry');

    const schoolContext = await browser.newContext({viewport:{width:1440,height:1000}});
    const school = await schoolContext.newPage();
    school.on('dialog',dialog=>dialog.accept());
    await login(school,true);
    await school.goto(base+fixture().draftPath);
    await school.getByRole('textbox',{name:'Organization name'}).fill('Corrected Mixed_CASE answer');
    await school.getByRole('button',{name:'Save draft',exact:true}).click();
    await expect(school.locator('.server-save-state')).toContainText('Saved');
    await school.locator('#applicant-steps > button').last().click();
    await school.getByRole('button',{name:'Check for missing information',exact:true}).click();
    
    await school.getByRole('button',{name:'Send corrected test application',exact:true}).click();
    await school.waitForURL(/\/applicant\/applications\//);
    expect(control('inspect').submissionCount).toBe(2);
    expect(fixture().originalAnswer).toBe('Exact Mixed_CASE answer 1');
    await page.reload();
    await expect(page.getByText('Corrected Mixed_CASE answer',{exact:true})).toBeVisible();
    const historical = await page.getByLabel('Submitted copy',{exact:true}).locator('option').last().getAttribute('value');
    await page.getByLabel('Submitted copy',{exact:true}).selectOption(historical);
    await expect(page.getByText('Exact Mixed_CASE answer 1',{exact:true})).toBeVisible();
    await expect(page.getByRole('heading',{name:'Request a correction',exact:true})).toHaveCount(0);
    await screens('intake-history');
    await schoolContext.close();
    pass('SCHOOL correction/resubmission integration and unchanged earlier submission');

    await page.goto(base+'/admin/synthetic-evidence-reviews?case='+data.cases[0]);
    await expect(page.getByText('Exact Mixed_CASE answer 1',{exact:true})).toHaveCount(0);
    await openDocument();
    pass('Separate evidence-review document opens through its own authorized route');
    const form = page.locator('.intake-form').first();
    await form.getByRole('button',{name:/Save finding/}).click();
    await expect(form.getByRole('alert')).toHaveCount(2);
    for (const type of ['accessibility','completeness','document_match']) {
        await form.getByLabel('Check to record').selectOption(type);
        await form.getByLabel('Result',{exact:true}).selectOption('meets_test_check');
        await form.getByLabel('Applies to',{exact:true}).selectOption({label:'Document 1'});
        await form.getByLabel('Review remarks (optional)').fill('Synthetic check: '+type);
        await form.getByRole('button',{name:/Save finding/}).click();
        await expect(form.getByLabel('Review remarks (optional)')).toHaveValue('');
    }
    expect(control('inspect').findingCount).toBe(3);
    expect(fixture().sessionTimezone).toBe('UTC');
    expect(fixture().clockSkewSeconds).toBeLessThan(5);
    expect(Number(fixture().findingDelaySeconds)).toBeGreaterThanOrEqual(0);
    pass('New review/finding timestamps agree with the real database clock');
    await page.getByText('Finding history (3)',{exact:true}).click();
    await expect(page.locator('.intake-history[open]').getByText('Synthetic regional reviewer',{exact:false}).first()).toBeVisible();
    await screens('evidence-findings');
    pass('Separate evidence copy, explicit finding validation, three checks and attributed history');

    // Interrupt a consequential request before transport. The UI must preserve remarks, not retry.
    await form.getByLabel('Review remarks (optional)').fill('Unsaved interrupted finding');
    await page.route('**/livewire-*/*',route=>route.abort());
    await form.getByRole('button',{name:/Save finding/}).click();
    await expect(page.locator('[data-reviewer-recovery]')).toBeVisible();
    await expect(form.getByLabel('Review remarks (optional)')).toHaveValue('Unsaved interrupted finding');
    expect(control('inspect').findingCount).toBe(3);
    await page.unroute('**/livewire-*/*');
    pass('Interrupted finding retains local remarks, shows uncertainty and performs no automatic retry');

    for(const kind of ['expired','noPermission']) {
        const ctx=await browser.newContext();
        const limited=await ctx.newPage();
        await login(limited,false,data[kind]);
        for(const path of ['/admin/synthetic-application-intake','/admin/synthetic-evidence-reviews']) {
            const response=await limited.goto(base+path);
            if(kind==='expired') await expect(limited.getByText(/0 (applications|test reviews) found/)).toBeVisible();
            else expect(response.status()).toBe(403);
            await expect(limited.locator('body')).not.toContainText('Batch D school 01');
        }
        if(kind==='expired') {
            expect((await limited.goto(base+'/admin/synthetic-application-intake?application='+data.applications[0])).status()).toBe(404);
            expect((await limited.goto(base+'/admin/synthetic-evidence-reviews?case='+data.cases[0])).status()).toBe(403);
        }
        await ctx.close();
        pass(kind+': actual signed-in browser has no unauthorized queue visibility');
    }

    control('revoke');
    for (const path of ['/admin/synthetic-application-intake','/admin/synthetic-evidence-reviews']) {
        await page.goto(base+path+'?search=Batch&page=2');
        await expect(page.getByText(/0 (applications|test reviews) found/)).toBeVisible();
        await expect(page.locator('.staff-queue-table')).not.toContainText('Batch D school');
        await expect(page.getByLabel('Service',{exact:true}).locator('option')).toHaveCount(1);
    }
    expect((await page.goto(base+'/admin/synthetic-application-intake?application='+data.applications[0])).status()).toBe(404);
    expect((await page.goto(base+'/admin/synthetic-evidence-reviews?case='+data.cases[0])).status()).toBe(403);
    pass('Revoked assignments remove scoped counts, options and rows; direct URLs denied');
    }
} catch (error) {
    result.failure = {name:error.name,message:error.message.slice(0,2500)};
    console.error(result.failure);
    if (process.argv.includes('--debug')) console.log('Stopped page', page.url(), (await page.locator('body').innerText()).slice(-3500));
    process.exitCode=1;
} finally {
    writeFileSync(`${dir}/${process.argv.includes('--queues-only')?'queue-layout-results':'results'}.json`,JSON.stringify(result,null,2));
    await browser.close();
}
if(result.screens.some(s=>s.overflow||s.violations.length)||result.errors.length) {console.error('Visual/accessibility/error gate failed');process.exitCode=1;}
