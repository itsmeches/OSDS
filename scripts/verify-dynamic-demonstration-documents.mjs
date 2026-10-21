import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';

const fixture = JSON.parse(execFileSync(process.env.OSDS_PHP, ['scripts/Prepare-DemonstrationDocuments.php'], { encoding: 'utf8' }));
const output = 'docs/screenshots/dynamic-demonstration-documents';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext();
await context.route('**/*', route => new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort());
const page = await context.newPage();
const errors = [];
page.on('pageerror', error => errors.push(error.message));
const results = [];
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
        results.push({ name, theme, width, axeViolations: axe.violations.length });
    }
}
try {
    await page.goto('http://127.0.0.1:8033/admin/login');
    await page.locator('input[type=email]').fill(fixture.email);
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('http://127.0.0.1:8033/admin/demonstration-documents');
    for (const number of ['One', 'Two']) {
        await page.getByLabel('Office label (required)', { exact: true }).fill(`Demo Office ${number} ${fixture.suffix}`);
        await page.getByLabel('Director name (required)', { exact: true }).fill(`Fictional Director ${number}`);
        await page.getByLabel('Designation (required)', { exact: true }).fill(`Demo Designation ${number}`);
        await page.getByRole('button', { name: 'Add demonstration office', exact: true }).click();
        await expect(page.getByRole('heading', { name: `Demo Office ${number} ${fixture.suffix}`, exact: true })).toBeVisible();
    }
    await expect.poll(async () => {
        await page.locator('.fi-no-notification-close-btn').evaluateAll(buttons => buttons.forEach(button => button.click()));
        return page.locator('.fi-no-notification').count();
    }, { timeout: 15000 }).toBe(0);
    await page.getByLabel('Name of HEI / Association (fictional, required)').fill('Fictional Browser HEI');
    await page.getByLabel('Address (fictional, required)', { exact: true }).fill('123 Fictional Avenue');
    await page.getByLabel('Demonstration date (required)').fill('2026-12-01');
    await page.getByLabel('CHED Regional Office (required)').selectOption({ label: `Demo Office One ${fixture.suffix}` });
    await expect(page.locator('#resolved-director')).toHaveValue('Fictional Director One');
    await page.getByLabel('CHED Regional Office (required)').selectOption({ label: `Demo Office Two ${fixture.suffix}` });
    await expect(page.locator('#resolved-director')).toHaveValue('Fictional Director Two');
    await expect(page.locator('#resolved-designation')).toHaveValue('Demo Designation Two');
    await page.getByRole('checkbox').check();
    await capture(page, 'issuance-form', ['light', 'dark']);
    for (const kind of ['cor', 'acsa']) {
        await page.locator('#issuance-kind').selectOption(kind);
        const popupPromise = page.waitForEvent('popup');
        await page.getByRole('button', { name: 'Preview filled document (new tab)' }).click();
        const popup = await popupPromise;
        await popup.waitForLoadState('networkidle');
        await expect(popup.locator('.signatory')).toContainText('Fictional Director Two');
        await expect(popup.locator('.recipient')).toHaveText('Fictional Browser HEI');
        await expect(popup.locator('footer')).toContainText('demo-v2');
        await capture(popup, kind);
        await popup.close();
    }
    expect(errors).toEqual([]);
    writeFileSync(`${output}/results.json`, JSON.stringify({ checks: ['Two offices saved through actual Livewire actions', 'Office selection resolves corresponding director and designation', 'COR and ACSA POST previews merge form values with server-side office snapshot'], screens: results, pageErrors: errors }, null, 2));
} finally {
    await browser.close();
}
