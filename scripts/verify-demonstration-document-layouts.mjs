import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

// Fixed fictional renderer only. No login, database preparation or case mutation.
const output = 'docs/screenshots/demonstration-documents';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext();
const results = [];
try {
    for (const kind of ['cor', 'acsa']) {
        const php = `require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo $app->make(App\\Services\\Documents\\DemonstrationCertificateRenderer::class)->render('${kind}', 'demo-v1');`;
        const html = execFileSync(process.env.OSDS_PHP, ['-r', php], { encoding: 'utf8' });
        const page = await context.newPage();
        await page.setContent(html);
        for (const width of [1440, 375]) {
            await page.setViewportSize({ width, height: 1100 });
            await expect(page.locator('footer')).toContainText('DRAFT / DEMONSTRATION ONLY');
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
            expect(overflow).toBe(false);
            const axe = await new AxeBuilder({ page }).options({ rules: { 'target-size': { enabled: true } } }).analyze();
            expect(axe.violations).toEqual([]);
            await page.screenshot({ path: `${output}/${kind}-${width}.png`, fullPage: true });
            results.push({ kind, width, overflow, axeViolations: axe.violations.length });
        }
        const pdf = await page.pdf({ path: `${output}/${kind}-demo-v1-DEMONSTRATION.pdf`, preferCSSPageSize: true, printBackground: true });
        expect((pdf.toString('latin1').match(/\/Type \/Page\b/g) || []).length).toBe(1);
        await page.close();
    }
    writeFileSync(`${output}/results.json`, JSON.stringify({ scope: 'Fixed fictional layout rendering; not authenticated workflow or official issuance', captures: results }, null, 2));
} finally {
    await browser.close();
}
