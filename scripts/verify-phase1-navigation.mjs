// Database-free component inspection, not authenticated application acceptance.
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';

const output = 'docs/screenshots/phase1-navigation';
fs.mkdirSync(output, { recursive: true });
const manifest = JSON.parse(fs.readFileSync('public/build/manifest.json', 'utf8'));
const css = fs.readFileSync('public/build/' + manifest['resources/css/app.css'].file, 'utf8');
const js = fs.readFileSync('public/build/' + manifest['resources/js/app.js'].file, 'utf8');
let markup = fs.readFileSync('resources/views/layouts/applicant.blade.php', 'utf8');
markup = markup.slice(markup.indexOf('<body>') + 6, markup.indexOf('</body>'));
markup = markup.replace(/@php\([^\n]+\)/g, '').replace(/@class\([^\n]+?\)\]\)/g, '');
// Only render the already-present sandbox menu branch; no policy is evaluated.
markup = markup.replace(/@class\(\['active'[^\n]*?\]\)/g, '');
markup = markup.replace(/@if\(request\(\)->routeIs\([^)]*\)\) aria-current="page" @endif/g, '');
markup = markup.replaceAll('@if($applicationSandbox)', '').replaceAll('@endif', '').replaceAll('@csrf', '');
markup = markup.replace(/\{\{ route\([^\n]*?\) \}\}/g, '#component-preview');
markup = markup.replace(/<x-filament::icon icon="heroicon-([^"]+)" aria-hidden="true" \/>/g, (_, name) => {
    const svg = fs.readFileSync(path.join('vendor/blade-ui-kit/blade-heroicons/resources/svg', name + '.svg'), 'utf8');
    return svg.replace('<svg ', '<svg aria-hidden="true" ');
});
const theme = fs.readFileSync('resources/views/components/theme-switcher.blade.php', 'utf8')
    .replace(/\{\{ \$attributes[^\n]+?\}\}/, 'class="theme-switcher auth-theme"');
markup = markup.replace('<x-theme-switcher />', theme);
markup = markup.replace("{{ $slot ?? '' }}@yield('content')", '<h1>Navigation component preview</h1><p>Static presentation only. No account, case or database is loaded.</p>');
if (/@(?:if|endif|class|php|yield|csrf)|\{\{|<x-/.test(markup)) throw Error('Unresolved template syntax');
const browser = await chromium.launch({ headless: true });
const results = [];
try {
    for (const width of [375, 768, 1024, 1440]) {
        for (const mode of ['light', 'dark']) {
            const context = await browser.newContext({ viewport: { width, height: 1000 } });
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/*', route => route.abort());
            await page.setContent(`<!doctype html><html lang="en" class="${mode === 'dark' ? 'dark' : ''}" data-theme="${mode}"><head><title>Navigation component preview</title><style>${css}</style></head><body>${markup}</body></html>`);
            await page.addScriptTag({ content: js });
            await page.evaluate(theme => document.querySelector(`[data-theme-option="${theme}"]`).click(), mode);
            const toggle = page.locator('[data-applicant-menu]');
            let keyboard = 'not applicable: desktop navigation remains visible';
            if (width <= 800) {
                if (await page.locator('#applicant-navigation').isVisible()) throw Error('Collapsed menu visible');
                await toggle.focus();
                await page.keyboard.press('Enter');
                if (!await page.locator('#applicant-navigation').isVisible()) throw Error('Menu failed to open');
                await page.keyboard.press('Tab');
                if (await page.evaluate(() => document.activeElement.textContent.trim()) !== 'My applications') throw Error('Unexpected first navigation focus');
                await page.keyboard.press('Escape');
                if (!await toggle.evaluate(el => el === document.activeElement)) throw Error('Focus not restored');
                if (await page.locator('#applicant-navigation').isVisible()) throw Error('Escape failed to close');
                await toggle.click();
                keyboard = 'PASS: collapsed, Enter opens, Tab reaches first link, Escape closes and restores focus';
            }
            const axe = await new AxeBuilder({ page }).withRules(['target-size']).analyze();
            const fullAxe = await new AxeBuilder({ page }).analyze();
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth);
            const capture = `${mode}-${width}.png`;
            await page.screenshot({ path: output + '/' + capture, fullPage: true });
            const result = { width, mode, capture, keyboard, overflow, errors, violations: fullAxe.violations, targetSizeViolations: axe.violations, incomplete: fullAxe.incomplete };
            results.push(result);
            await context.close();
        }
    }
} finally {
    await browser.close();
    fs.writeFileSync(output + '/component-results.json', JSON.stringify({ scope: 'Database-free applicant navigation component with actual source markup, built CSS and JS; all sandbox links shown. No authorization or authenticated staff UI coverage.', results }, null, 2));
}
const failures = results.filter(r => r.overflow || r.errors.length || r.violations.length || r.targetSizeViolations.length);
console.log(JSON.stringify({ captures: results.length, failingSamples: failures.length, incompleteSamples: results.filter(r => r.incomplete.length).length }));
if (failures.length) process.exitCode = 1;
