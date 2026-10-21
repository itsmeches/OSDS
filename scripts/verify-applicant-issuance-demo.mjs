import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const fixture = JSON.parse(execFileSync(process.env.OSDS_PHP, ['scripts/Prepare-DemonstrationDocuments.php'], { encoding: 'utf8' }));
const output = 'docs/screenshots/applicant-issuance-demo';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext();
await context.route('**/*', route => new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort());
const page = await context.newPage();
const base = 'http://127.0.0.1:8034';
const screens = [];
const errors = [];
page.on('pageerror', error => errors.push(error.message));
async function login(email, staff) {
    await page.goto(base + (staff ? '/admin/login' : '/applicant/login'));
    await page.locator('input[type=email]').fill(email);
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
}
async function settle() {
    await expect.poll(async () => {
        await page.locator('.fi-no-notification-close-btn').evaluateAll(buttons => buttons.forEach(button => button.click()));
        return page.locator('.fi-no-notification').count();
    }, { timeout: 15000 }).toBe(0);
}
async function capture(target, name, themes = ['light']) {
    for (const theme of themes) for (const width of [1440, 375]) {
        await target.setViewportSize({ width, height: 1000 });
        if (themes.length > 1) await target.evaluate(theme => { localStorage.setItem('theme', theme); document.documentElement.classList.toggle('dark', theme === 'dark'); }, theme);
        await target.waitForLoadState('networkidle');
        await target.evaluate(async () => { await Promise.all(document.getAnimations().filter(a => a.effect?.getTiming().iterations !== Infinity).map(a => a.finished.catch(() => {}))); });
        expect(await target.evaluate(() => document.documentElement.scrollWidth > innerWidth)).toBe(false);
        const axe = await new AxeBuilder({ page: target }).options({ rules: { 'target-size': { enabled: true } } }).analyze();
        expect(axe.violations).toEqual([]);
        await target.screenshot({ path: `${output}/${name}-${theme}-${width}.png`, fullPage: true });
        screens.push({ name, theme, width, axeViolations: axe.violations.length });
    }
}
try {
    await login(fixture.email, true);
    await page.goto(base + '/admin/demonstration-documents');
    await page.getByLabel('Office label (required)', { exact: true }).fill('Demo issuance office ' + fixture.suffix);
    await page.getByLabel('Director name (required)', { exact: true }).fill('Fictional Issuance Director');
    await page.getByLabel('Designation (required)', { exact: true }).fill('Demonstration Director');
    await page.getByRole('button', { name: 'Add demonstration office', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Demo issuance office ' + fixture.suffix, exact: true })).toBeVisible();
    await page.getByLabel('Applicant form title', { exact: true }).fill('Synthetic browser issuance form');
    await page.getByLabel('Available to authorized applicants', { exact: true }).check();
    await page.getByLabel('Label for Name of HEI / Association', { exact: true }).fill('Fictional institution name');
    // Existing browser fixtures may have prior extras; publish a clean example through the actual editor.
    const removeButtons = page.getByRole('button', { name: /^Remove question/ });
    for (let remaining = await removeButtons.count(); remaining > 0; remaining--) {
        await removeButtons.first().click();
        await expect(removeButtons).toHaveCount(remaining - 1);
    }
    await page.getByRole('button', { name: 'Add question', exact: true }).click();
    await page.getByLabel('Question label 1', { exact: true }).fill('Fictional sport');
    await page.getByLabel('Required answer 1', { exact: true }).check();
    await page.getByRole('button', { name: 'Save applicant form', exact: true }).click();
    await settle();
    await capture(page, 'osds-form-editor', ['light', 'dark']);
    await context.clearCookies();
    await login(fixture.applicant, false);
    await page.goto(base + '/applicant/issuance-demo');
    await expect(page.getByRole('heading', { name: 'Synthetic browser issuance form', exact: true })).toBeVisible();
    await page.getByLabel('Fictional institution name (required)', { exact: true }).fill('Fictional Applicant Institution');
    await page.getByLabel('Address (required)', { exact: true }).fill('123 Demo Street');
    await page.getByLabel('Demonstration date (required)', { exact: true }).fill('2026-12-01');
    await page.getByLabel('CHED Regional Office (required)', { exact: true }).selectOption({ label: 'Demo issuance office ' + fixture.suffix });
    await page.getByLabel('Fictional sport (required)', { exact: true }).fill('Fictional volleyball');
    await page.getByRole('checkbox').check();
    await capture(page, 'applicant-form', ['light', 'dark']);
    for (const kind of ['cor', 'acsa']) {
        await page.locator('#app-issuance-kind').selectOption(kind);
        const popupPromise = page.waitForEvent('popup');
        await page.getByRole('button', { name: 'Preview printable draft (new tab)', exact: true }).click();
        const popup = await popupPromise;
        await popup.waitForLoadState('networkidle');
        await expect(popup.locator('.recipient')).toHaveText('Fictional Applicant Institution');
        await expect(popup.locator('.signatory')).toContainText('Fictional Issuance Director');
        await expect(popup.getByRole('definition')).toContainText(['Fictional volleyball']);
        await capture(popup, kind);
        const pdf = await popup.pdf({ path: `${output}/${kind}-demo-v3-DEMONSTRATION.pdf`, preferCSSPageSize: true, printBackground: true });
        expect((pdf.toString('latin1').match(/\/Type \/Page\b/g) || []).length).toBe(2);
        await popup.close();
    }
    expect(errors).toEqual([]);
    writeFileSync(`${output}/results.json`, JSON.stringify({ checks: ['OSDS edits and publishes form and additional question', 'Applicant receives published labels and fields', 'Both layouts merge answers and office personnel', 'Printed output contains certificate and additional-answer appendix'], screens, pageErrors: errors }, null, 2));
} finally {
    await browser.close();
}
