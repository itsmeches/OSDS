import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const dir = 'docs/screenshots/downloadable-forms-ui';
mkdirSync(dir, { recursive: true });
const control = mode => JSON.parse(execFileSync(process.env.OSDS_PHP, ['scripts/Prepare-DownloadableForms.php', mode], { encoding: 'utf8' }));
const fixture = control('prepare');
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const result = { checks: [], screens: [], errors: [] };
const context = await browser.newContext();
await context.route('**/*', route => new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort());
const page = await context.newPage();
page.on('pageerror', error => result.errors.push(error.message));
const base = 'http://127.0.0.1:8032';
const catalogue = base + '/admin/downloadable-forms';
const source = name => resolve('docs/source-artifacts/downloadable-forms', name);
async function login(email, staff) {
    await page.goto(base + (staff ? '/admin/login' : '/applicant/login'));
    await page.waitForLoadState('networkidle');
    await page.locator('input[type=email]').fill(email);
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
}
async function capture(name) {
    await expect.poll(async () => {
        await page.locator('.fi-no-notification-close-btn').evaluateAll(buttons => buttons.forEach(button => button.click()));
        return page.locator('.fi-no-notification').count();
    }).toBe(0);
    for (const theme of ['light', 'dark']) for (const width of [1440, 375]) {
        await page.setViewportSize({ width, height: 1000 });
        await page.evaluate(theme => { localStorage.setItem('theme', theme); document.documentElement.classList.toggle('dark', theme === 'dark'); }, theme);
        await page.waitForLoadState('networkidle');
        await expect.poll(async () => {
            await page.locator('.fi-no-notification-close-btn').evaluateAll(buttons => buttons.forEach(button => button.click()));
            return page.locator('.fi-no-notification').count();
        }, { timeout: 15000 }).toBe(0);
        await page.waitForFunction(() => document.getAnimations().every(animation => animation.playState !== 'running' || animation.effect?.getTiming().iterations === Infinity));
        const axe = await new AxeBuilder({ page }).options({ rules: { 'target-size': { enabled: true } } }).analyze();
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
        result.screens.push({ name, theme, width, overflow, violations: axe.violations.map(v => ({ id: v.id, targets: v.nodes.map(n => n.target) })) });
        await page.screenshot({ path: `${dir}/${name}-${theme}-${width}.png` });
        expect(overflow).toBe(false);
        expect(axe.violations).toEqual([]);
    }
}
try {
    await login(fixture.staff, true);
    await page.goto(catalogue);
    const addForm = page.locator('.downloads-editor > summary');
    await addForm.focus(); await page.keyboard.press('Enter');
    await expect(page.getByLabel(/^Form title/)).toBeVisible();
    result.checks.push('Keyboard opens the collapsed add-form editor');
    await capture('editor');
    await page.getByLabel(/^Form title/).fill(fixture.title);
    await page.getByLabel(/^Version label/).fill('Source version 1');
    await page.getByLabel(/^Blank form file/).setInputFiles(source('CERTIFICATE OF UNDERTAKING.docx'));
    await expect(page.getByRole('button', { name: 'Publish form', exact: true })).toBeEnabled();
    await page.getByRole('button', { name: 'Publish form', exact: true }).click();
    await expect(page.getByRole('link', { name: 'Download form: ' + fixture.title, exact: true })).toBeVisible();
    const oldUrl = await page.getByRole('link', { name: 'Download form: ' + fixture.title, exact: true }).getAttribute('href');
    expect(control('inspect').forms[0].versions).toBe(1);
    result.checks.push('Real DOCX upload publishes one version');
    await page.getByRole('button', { name: 'Replace file: ' + fixture.title, exact: true }).click();
    await expect(page.locator('.downloads-editor > summary').filter({ hasText: 'Replace form with a new version' })).toBeVisible();
    await page.getByLabel(/^Version label/).fill('Source version 2');
    await page.getByLabel(/^Blank form file/).setInputFiles(source('LIST OF MEMBER HEIs.xlsx'));
    await expect(page.getByRole('button', { name: 'Publish replacement', exact: true })).toBeEnabled();
    await page.getByRole('button', { name: 'Publish replacement', exact: true }).click();
    await expect(page.locator('.downloads-card').filter({ has: page.getByRole('heading', { name: fixture.title, exact: true }) }).getByText('2 versions', { exact: true })).toBeVisible();
    expect((await context.request.get(oldUrl)).status()).toBe(404);
    const currentUrl = await page.getByRole('link', { name: 'Download form: ' + fixture.title, exact: true }).getAttribute('href');
    expect((await context.request.get(currentUrl)).status()).toBe(200);
    result.checks.push('Replacement retains history and invalidates old download URL');
    await page.locator('.downloads-card').filter({ has: page.getByRole('heading', { name: fixture.title, exact: true }) }).locator('.downloads-history > summary').click();
    await capture('staff');
    await context.clearCookies();
    await login(fixture.reader, false);
    await page.goto(base + '/applicant/forms');
    await expect(page.getByRole('heading', { name: fixture.title, exact: true })).toBeVisible();
    expect((await context.request.get(currentUrl)).status()).toBe(200);
    await capture('applicant');
    result.checks.push('Signed-in applicant reads current catalogue and file');
    await context.clearCookies();
    await login(fixture.viewer, true);
    await page.goto(catalogue);
    await expect(page.getByRole('heading', { name: fixture.title, exact: true })).toBeVisible();
    await expect(page.locator('.downloads-editor')).toHaveCount(0);
    await expect(page.getByRole('button', { name: 'Replace file: ' + fixture.title, exact: true })).toHaveCount(0);
    expect((await context.request.get(currentUrl)).status()).toBe(200);
    await capture('chedro');
    result.checks.push('CHEDRO catalogue is read-only with downloads and no editor');
    await context.clearCookies();
    await login(fixture.staff, true);
    await page.goto(catalogue);
    page.once('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: 'Withdraw ' + fixture.title, exact: true }).click();
    await expect(page.getByRole('link', { name: 'Download form: ' + fixture.title, exact: true })).toHaveCount(0);
    expect((await context.request.get(currentUrl)).status()).toBe(404);
    expect(control('inspect').forms[0].versions).toBe(2);
    result.checks.push('Withdrawal denies download without deleting history');
    control('revoke');
    await page.reload();
    await expect(page.getByRole('button', { name: 'Publish form', exact: true })).toHaveCount(0);
    result.checks.push('Management permission loss removes controls');
    await context.clearCookies();
    expect((await context.request.get(currentUrl, { maxRedirects: 0 })).status()).toBe(302);
    result.checks.push('Session loss prevents download');
    expect(result.errors).toEqual([]);
    result.passed = true;
} catch (error) {
    result.failure = String(error);
    await page.screenshot({ path: dir + '/failure.png' });
    process.exitCode = 1;
} finally {
    writeFileSync(dir + '/results.json', JSON.stringify(result, null, 2));
    await browser.close();
}
