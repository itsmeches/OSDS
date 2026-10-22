import { chromium, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const fixture = JSON.parse(execFileSync(process.env.OSDS_PHP, ['scripts/Prepare-DownloadableForms.php', 'prepare'], { encoding: 'utf8' }));
const output = 'docs/screenshots/form-upload-recovery';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await browser.newPage();
const requests = [];
const errors = [];
page.on('pageerror', error => errors.push(error.message));
page.on('response', response => {
    if (new URL(response.url()).pathname.includes('upload-file')) requests.push({ path: new URL(response.url()).pathname, status: response.status() });
});
try {
    await page.goto('http://127.0.0.1:8035/admin/login');
    await page.locator('input[type=email]').fill(fixture.staff);
    await page.locator('input[type=password]').fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL(url => !url.pathname.endsWith('/login'));
    await page.goto('http://127.0.0.1:8035/admin/downloadable-forms');
    if (!await page.locator('#download-upload').isVisible()) await page.locator('.downloads-editor > summary').filter({ hasText: 'Add a downloadable form' }).click();
    await page.locator('#download-title').fill(fixture.title);
    await page.locator('#download-version').fill('Upload recovery');
    await page.route('**/*upload-file*', route => route.fulfill({ status: 422, contentType: 'application/json', body: JSON.stringify({ errors: { 'files.0': ['The file failed to upload.'] } }) }), { times: 1 });
    await page.locator('#download-upload').setInputFiles(resolve('docs/source-artifacts/downloadable-forms/CERTIFICATE OF UNDERTAKING.docx'));
    await expect(page.getByText('File upload failed. Keep your entries', { exact: false })).toBeVisible();
    await expect(page.locator('#download-title')).toHaveValue(fixture.title);
    await page.locator('#download-upload').setInputFiles([]);
    await page.locator('#download-upload').setInputFiles(resolve('docs/source-artifacts/downloadable-forms/CERTIFICATE OF UNDERTAKING.docx'));
    await expect(page.getByRole('button', { name: 'Publish form', exact: true })).toBeEnabled();
    await page.getByRole('button', { name: 'Publish form', exact: true }).click();
    await expect(page.locator('.downloads-card').filter({ hasText: fixture.title })).toBeVisible();
    await page.locator('.downloads-editor > summary').filter({ hasText: 'Import forms from a ZIP' }).click();
    await page.locator('#archive-version').fill('Original source ZIP');
    await page.locator('#forms-archive').setInputFiles(resolve('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip'));
    await expect(page.getByRole('button', { name: 'Import and publish forms', exact: true })).toBeEnabled();
    await page.getByRole('button', { name: 'Import and publish forms', exact: true }).click();
    await expect(page.getByText('5 forms imported', { exact: true })).toBeVisible();
    // Exercise a file above PHP's former 2 MB limit through the same multipart route.
    if (!await page.locator('#download-upload').isVisible()) await page.locator('.downloads-editor > summary').filter({ hasText: 'Add a downloadable form' }).click();
    await page.locator('#download-title').fill(fixture.title + ' large');
    await page.locator('#download-version').fill('3 MB sample');
    const pdf = Buffer.concat([Buffer.from('%PDF-1.4\n'), Buffer.alloc(3 * 1024 * 1024, 32), Buffer.from('\n%%EOF')]);
    await page.locator('#download-upload').setInputFiles({ name: 'large-sample.pdf', mimeType: 'application/pdf', buffer: pdf });
    await expect(page.getByRole('button', { name: 'Publish form', exact: true })).toBeEnabled();
    await page.getByRole('button', { name: 'Publish form', exact: true }).click();
    await expect(page.locator('.downloads-card').filter({ hasText: fixture.title + ' large' })).toBeVisible();
    expect(errors).toEqual([]);
    // ZIP now uses its own normal multipart submission, not Livewire's
    // temporary-upload endpoint. Only the DOCX and PDF take that path.
    expect(requests.filter(item => item.status === 200).length).toBe(2);
    await page.screenshot({ path: output + '/uploaded.png', fullPage: true });
    writeFileSync(output + '/results.json', JSON.stringify({ checks: ['Rejected temporary upload shows fixed guidance and retains title', 'Original DOCX upload and publication', 'Original ZIP upload and five-entry import', '3 MB PDF upload above former PHP limit'], requests, pageErrors: errors }, null, 2));
} finally {
    await browser.close();
}
