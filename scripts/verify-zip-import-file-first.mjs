import { chromium, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const fixture = JSON.parse(execFileSync(process.env.OSDS_PHP, ['scripts/Prepare-DownloadableForms.php', 'prepare'], { encoding: 'utf8' }));
const dir = 'docs/screenshots/multipart-zip-import';
mkdirSync(dir, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await browser.newPage();
const result = { errors: [], invalid: [], requests: [] };
page.on('pageerror', error => result.errors.push(error.message));
page.on('request', request => {
    if (request.method() === 'POST') result.requests.push(new URL(request.url()).pathname);
});
try {
    await page.goto('http://127.0.0.1:8035/admin/login');
    await page.locator('input[type=email]').fill(fixture.staff);
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('http://127.0.0.1:8035/admin/downloadable-forms');
    await page.locator('.downloads-editor > summary').filter({ hasText: 'Import forms from a ZIP' }).click();
    if (!await page.locator('#download-upload').isVisible()) await page.locator('.downloads-editor > summary').filter({ hasText: 'Add a downloadable form' }).click();
    await page.locator('#forms-archive').setInputFiles(resolve('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip'));
    await page.waitForLoadState('networkidle');
    await expect(page.getByRole('button', { name: 'Import and publish forms', exact: true })).toBeEnabled();
    await page.locator('#archive-version').fill('File first ZIP regression');
    result.invalid = await page.locator('form[enctype="multipart/form-data"]').evaluate(form => ({
        fields: [...form.elements].map(input => ({ id: input.id, required: input.required, valid: input.validity?.valid, files: input.files?.length })),
        parentForm: form.parentElement.closest('form')?.id ?? null,
    }));
    await page.getByRole('button', { name: 'Import and publish forms', exact: true }).click();
    await expect(page.getByText('5 forms imported', { exact: true })).toBeVisible();
    expect(result.requests.filter(path => path.includes('upload-file'))).toEqual([]);
    expect(result.requests).toContain('/admin/downloadable-forms/import-zip');
    result.pass = true;
} finally {
    await page.screenshot({ path: dir + '/state.png', fullPage: true });
    writeFileSync(dir + '/results.json', JSON.stringify(result, null, 2));
    await browser.close();
}
