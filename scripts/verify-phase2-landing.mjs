// Source-derived landing preview only. No Laravel bootstrap, account or database.
import fs from 'node:fs';
import { chromium } from 'playwright';
import AxeBuilder from '@axe-core/playwright';

const output = 'docs/screenshots/phase2-landing';
fs.mkdirSync(output, { recursive: true });
const manifest = JSON.parse(fs.readFileSync('public/build/manifest.json', 'utf8'));
const css = fs.readFileSync('public/build/' + manifest['resources/css/app.css'].file, 'utf8');
const js = fs.readFileSync('public/build/' + manifest['resources/js/app.js'].file, 'utf8');
const source = fs.readFileSync('resources/views/welcome.blade.php', 'utf8');
if (!source.includes("{{ route('applicant.login') }}") || !source.includes("{{ url('/admin') }}")) throw Error('Sign-in bindings changed');
const theme = fs.readFileSync('resources/views/components/theme-switcher.blade.php', 'utf8')
    .replace(/\{\{ \$attributes[^\n]+?\}\}/, 'class="theme-switcher auth-theme"');
const html = source
    .replace('<x-theme-init />', fs.readFileSync('resources/views/components/theme-init.blade.php', 'utf8'))
    .replace('<x-theme-switcher />', theme)
    .replace("@vite(['resources/css/app.css', 'resources/js/app.js'])", `<style>${css}</style><script type="module">${js}</script>`)
    .replace("{{ route('applicant.login') }}", '/applicant/login')
    .replace("{{ url('/admin') }}", '/admin');
if (/\{\{|@vite|<x-/.test(html)) throw Error('Unresolved template syntax');

const results = { scope: 'Source-derived static landing page with built application CSS/JS and shared theme components. Laravel helpers are replaced with the existing destinations; no authentication or application server is exercised.', captures: [], supplemental: [] };
const browser = await chromium.launch({ headless: true });
async function setup(width, mode, options = {}) {
    const context = await browser.newContext({ viewport: { width, height: 1000 }, colorScheme: mode });
    const page = await context.newPage();
    const errors = [];
    const unexpectedRequests = [];
    page.on('pageerror', e => errors.push(e.message));
    await context.route('**/*', route => {
        if (route.request().url() === 'http://phase2.osds.test/') return route.fulfill({ contentType: 'text/html', body: html });
        unexpectedRequests.push(route.request().url());
        return route.abort();
    });
    await context.addInitScript(({ mode, blocked }) => {
        if (blocked) {
            Object.defineProperty(Storage.prototype, 'getItem', { value() { throw new Error('Storage unavailable'); } });
            Object.defineProperty(Storage.prototype, 'setItem', { value() { throw new Error('Storage unavailable'); } });
        } else {
            localStorage.setItem('osds-theme', mode);
        }
    }, { mode, blocked: options.blocked ?? false });
    await page.goto('http://phase2.osds.test/');
    await page.waitForFunction(() => document.querySelector('[data-theme-option][aria-pressed="true"]'));
    if (options.textScale) await page.addStyleTag({ content: 'html { font-size: 200%; }' });
    return { page, context, errors, unexpectedRequests };
}
async function measure(page) {
    return page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth > innerWidth,
        links: [...document.querySelectorAll('a')].map(el => ({ text: el.textContent.trim(), href: el.getAttribute('href') })),
        controls: [...document.querySelectorAll('a:not(.landing-skip), button')].map(el => ({ label: el.textContent.trim(), width: el.getBoundingClientRect().width, height: el.getBoundingClientRect().height })),
        headerOverlap: (() => {
            const boxes = [...document.querySelector('.landing-header-inner').children].map(el => el.getBoundingClientRect());
            return boxes.some((a, i) => boxes.slice(i + 1).some(b => Math.min(a.right, b.right) - Math.max(a.left, b.left) > 1 && Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top) > 1));
        })(),
        dark: document.documentElement.classList.contains('dark'),
    }));
}
try {
    for (const width of [375, 768, 1024, 1440]) {
        for (const mode of ['light', 'dark']) {
            const { page, context, errors, unexpectedRequests } = await setup(width, mode);
            await page.keyboard.press('Tab');
            if (await page.locator('.landing-skip').evaluate(el => el !== document.activeElement)) throw Error('Skip link not first');
            await page.keyboard.press('Enter');
            if (!await page.locator('#landing-content').evaluate(el => el === document.activeElement)) throw Error('Skip target did not receive focus');
            await page.keyboard.press('Tab');
            if (await page.evaluate(() => document.activeElement.textContent.trim()) !== 'Applicant sign in') throw Error('Unexpected focus after skip');
            const measured = await measure(page);
            for (const link of measured.links.filter(link => link.href.startsWith('#'))) {
                if (!await page.locator(link.href).count()) throw Error('Missing anchor: ' + link.href);
            }
            if (measured.dark !== (mode === 'dark')) throw Error('Initial theme mismatch');
            const axe = await new AxeBuilder({ page }).options({ rules: { 'target-size': { enabled: true } } }).analyze();
            for (const anchor of ['about', 'process', 'access']) {
                await page.locator(`.landing-navigation a[href="#${anchor}"]`).click();
                if (await page.evaluate(() => location.hash) !== '#' + anchor) throw Error('Section link failed: ' + anchor);
            }
            await page.locator('body').click({ position: { x: 2, y: 2 } });
            await page.evaluate(() => scrollTo(0, 0));
            const capture = `${mode}-${width}.png`;
            await page.screenshot({ path: output + '/' + capture, fullPage: true });
            results.captures.push({ width, mode, capture, ...measured, errors, unexpectedRequests, keyboard: 'PASS: skip link first, Enter focuses main, subsequent Tab reaches applicant sign-in', violations: axe.violations, incomplete: axe.incomplete });
            await context.close();
        }
    }
    for (const mode of ['light', 'dark']) {
        for (const scenario of [{ width: 320, textScale: false }, { width: 375, textScale: true }, { width: 1440, textScale: true }]) {
            const { page, context, errors } = await setup(scenario.width, mode, scenario);
            const measured = await measure(page);
            const axe = await new AxeBuilder({ page }).options({ rules: { 'target-size': { enabled: true } } }).analyze();
            const capture = `${mode}-${scenario.width}-${scenario.textScale ? 'text-200' : 'reflow-320'}.png`;
            await page.screenshot({ path: output + '/' + capture, fullPage: true });
            results.supplemental.push({ ...scenario, mode, capture, ...measured, errors, violations: axe.violations, incomplete: axe.incomplete });
            await context.close();
        }
    }
    const { page, context, errors } = await setup(375, 'light');
    const transitions = [];
    for (const mode of ['dark', 'light', 'system']) {
        await page.locator(`[data-theme-option="${mode}"]`).click();
        transitions.push({ mode, pressed: await page.locator(`[data-theme-option="${mode}"]`).getAttribute('aria-pressed'), dark: await page.locator('html').evaluate(el => el.classList.contains('dark')) });
    }
    await page.emulateMedia({ colorScheme: 'dark' });
    await page.waitForFunction(() => document.documentElement.classList.contains('dark'));
    const followsSystemDark = await page.locator('html').evaluate(el => el.classList.contains('dark'));
    await page.emulateMedia({ colorScheme: 'light' });
    await page.waitForFunction(() => !document.documentElement.classList.contains('dark'));
    results.theme = { transitions, followsSystemDark, followsSystemLight: !await page.locator('html').evaluate(el => el.classList.contains('dark')), errors };
    await context.close();
    const blocked = await setup(375, 'light', { blocked: true });
    await blocked.page.locator('[data-theme-option="dark"]').click();
    results.blockedStorage = { darkControlWorks: await blocked.page.locator('html').evaluate(el => el.classList.contains('dark')), errors: blocked.errors };
    await blocked.context.close();
} finally {
    await browser.close();
    fs.writeFileSync(output + '/results.json', JSON.stringify(results, null, 2));
}
const failures = [...results.captures, ...results.supplemental].filter(r => r.overflow || r.headerOverlap || r.errors.length || r.violations.length || r.controls.some(c => c.height < 44 || c.width < 24));
const themePassed = results.theme?.transitions.every(t => t.pressed === 'true' && t.dark === (t.mode === 'dark')) && results.theme.followsSystemDark && results.theme.followsSystemLight && !results.theme.errors.length && results.blockedStorage?.darkControlWorks && !results.blockedStorage.errors.length;
console.log(JSON.stringify({ primaryCaptures: results.captures.length, supplementalCaptures: results.supplemental.length, failingSamples: failures.length, themePassed }));
if (failures.length || !themePassed) process.exitCode = 1;
