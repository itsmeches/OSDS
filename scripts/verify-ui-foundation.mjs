import { chromium } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';

const baseline = process.argv.includes('--baseline');
const debug = process.argv.includes('--debug');
const baseURL = 'http://127.0.0.1:8017';
const fixtures = JSON.parse(readFileSync('storage/app/private/ui-browser-fixtures.json', 'utf8'));
const output = debug ? 'storage/app/private/ui-browser-debug' : baseline ? 'storage/app/private/ui-browser-baseline' : 'docs/screenshots/phase-3d7-b';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];

async function localContext(options) {
    const context = await browser.newContext(options);
    await context.route('**/*', route => new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort());
    return context;
}

async function inspect(page, name, theme, width, screenshot = true) {
    await page.waitForTimeout(350);
    const metrics = await page.evaluate(() => {
        const rgba = (color) => {
            const canvas = document.createElement('canvas');
            canvas.width = canvas.height = 1;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.fillStyle = color;
            ctx.fillRect(0, 0, 1, 1);
            return [...ctx.getImageData(0, 0, 1, 1).data].map((value, index) => index === 3 ? value / 255 : value);
        };
        const blend = (top, bottom) => [0, 1, 2].map(i => top[i] * top[3] + bottom[i] * (1 - top[3])).concat(1);
        const backdrop = (element) => {
            const chain = [];
            for (let node = element; node; node = node.parentElement) chain.unshift(node);
            return chain.reduce((color, node) => blend(rgba(getComputedStyle(node).backgroundColor), color), [255, 255, 255, 1]);
        };
        const luminance = color => color.slice(0, 3).map(v => v / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4).reduce((value, v, i) => value + v * [.2126, .7152, .0722][i], 0);
        const controls = [...document.querySelectorAll('button[type="submit"], .primary, .intake-primary-action, .builder-stages button, .fi-btn, .secondary, .form-stack input, .fi-input, .intake-field textarea')].filter(el => el.getBoundingClientRect().width && el.getBoundingClientRect().height).map(el => {
            const style = getComputedStyle(el);
            const background = backdrop(el);
            const foreground = blend(rgba(style.color), background);
            const a = luminance(foreground), b = luminance(background);
            return { text: (el.textContent || el.getAttribute('aria-label') || el.tagName).trim().slice(0, 55), foreground: style.color, background: style.backgroundColor, ratio: +((Math.max(a, b) + .05) / (Math.min(a, b) + .05)).toFixed(2), fontSize: style.fontSize };
        });
        const covering = document.elementFromPoint(innerWidth / 2, innerHeight / 2);
        return { overflow: document.documentElement.scrollWidth > innerWidth + 1, viewport: innerWidth, documentWidth: document.documentElement.scrollWidth, dark: document.documentElement.classList.contains('dark'), controls, centerElement: covering?.className };
    });
    const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
    if (name === 'form-builder') console.log({ name, theme, width, center: metrics.centerElement, violations: axe.violations.map(v => v.id) });
    results.push({ name, theme, width, ...metrics, axe: axe.violations.map(v => ({ id: v.id, impact: v.impact, count: v.nodes.length, targets: v.nodes.slice(0, 6).map(n => n.target) })) });
    if (screenshot) await page.screenshot({ path: `${output}/${name}-${theme}-${width}.png`, fullPage: false });
    await page.keyboard.press('Tab');
    results.at(-1).keyboardFocus = await page.evaluate(() => {
        const element = document.activeElement;
        const style = getComputedStyle(element);
        return { element: element.tagName, outlineWidth: style.outlineWidth, outlineStyle: style.outlineStyle, outlineColor: style.outlineColor };
    });
    writeFileSync(`${output}/measurements.json`, JSON.stringify(results, null, 2));
}

try {
    for (const theme of ['light', 'dark']) {
        for (const width of baseline || debug ? [1440] : [1440, 1024, 768, 375, 320]) {
            if (!process.argv.includes('--staff-only')) {
            const context = await localContext({ viewport: { width, height: width < 768 ? 900 : 1000 }, colorScheme: theme });
            await context.addInitScript((value) => { localStorage.setItem('osds-theme', value); localStorage.setItem('theme', value); }, theme);
            const page = await context.newPage();
            page.on('response', response => { if (response.status() >= 400) console.log(`HTTP ${response.status()} ${new URL(response.url()).pathname}`); });
            page.on('pageerror', error => console.log(`Browser script error: ${error.name}`));
            await page.goto(`${baseURL}/applicant/login`);
            await inspect(page, 'applicant-login', theme, width);
            await page.getByLabel('Email address', { exact: true }).fill('school@example.invalid');
            await page.getByLabel('Password', { exact: true }).fill('UiBrowserOnly-2026!');
            await page.getByRole('button', { name: 'Sign in', exact: true }).click();
            await page.waitForURL(`${baseURL}/applicant`, { waitUntil: 'domcontentloaded' });
            await inspect(page, 'applicant-home', theme, width);
            await page.goto(`${baseURL}${fixtures.draft}`);
            await inspect(page, 'applicant-editor', theme, width);
            await page.locator('.progress-rail button').nth(1).click();
            await page.waitForTimeout(1000);
            await page.getByRole('heading', { name: 'Document links', exact: true }).waitFor();
            await inspect(page, 'applicant-documents', theme, width);
            await context.close();
            }

            const staffContext = await localContext({ viewport: { width, height: width < 768 ? 900 : 1000 }, colorScheme: theme });
            await staffContext.addInitScript((value) => { localStorage.setItem('theme', value); }, theme);
            const staff = await staffContext.newPage();
            staff.on('response', response => { if (response.status() >= 400) console.log(`Staff HTTP ${response.status()} ${new URL(response.url()).pathname}`); });
            staff.on('pageerror', error => console.log(`Staff browser script error: ${error.name}: ${error.message.slice(0, 180)}`));
            await staff.goto(`${baseURL}/admin/login`);
            await staff.waitForFunction(() => window.Livewire?.all().length > 0);
            await inspect(staff, 'staff-login', theme, width);
            await staff.locator('input[type="email"]').fill('osds@example.invalid');
            await staff.locator('input[type="password"]').fill('UiBrowserOnly-2026!');
            await staff.getByRole('button', { name: 'Sign in', exact: true }).click();
            await staff.waitForURL(`${baseURL}/admin`, { waitUntil: 'domcontentloaded' });
            await staff.goto(`${baseURL}${fixtures.builder}`);
            await inspect(staff, 'form-builder', theme, width);
            await staff.goto(`${baseURL}/admin/users`);
            await inspect(staff, 'staff-users', theme, width);
            if (!baseline && !debug && [1440, 375].includes(width)) {
                await staff.goto(`${baseURL}/admin/questionnaires`);
                await staff.waitForFunction(() => window.Livewire?.all().length > 0);
                await staff.waitForTimeout(700);
                const create = staff.getByRole('button', { name: 'Create questionnaire', exact: true });
                await create.click();
                await staff.waitForTimeout(1200);
                await staff.locator('.fi-modal.fi-modal-open .fi-modal-window').waitFor({ state: 'visible' });
                await inspect(staff, 'staff-create-modal', theme, width);
                await staff.keyboard.press('Escape');
                await staff.locator('.fi-modal.fi-modal-open .fi-modal-window').waitFor({ state: 'hidden' });
                results.at(-1).modalKeyboardDismissed = true;
            }
            await staffContext.close();
            if (!baseline && !debug && [1440, 375].includes(width)) {
                const reviewerContext = await localContext({ viewport: { width, height: 1000 }, colorScheme: theme });
                await reviewerContext.addInitScript(value => localStorage.setItem('theme', value), theme);
                const reviewer = await reviewerContext.newPage();
                await reviewer.goto(`${baseURL}/admin/login`);
                await reviewer.waitForFunction(() => window.Livewire?.all().length > 0);
                await reviewer.locator('input[type="email"]').fill('chedro@example.invalid');
                await reviewer.locator('input[type="password"]').fill('UiBrowserOnly-2026!');
                await reviewer.getByRole('button', { name: 'Sign in', exact: true }).click();
                await reviewer.waitForURL(`${baseURL}/admin`, { waitUntil: 'domcontentloaded' });
                await reviewer.goto(`${baseURL}${fixtures.intake}`);
                await inspect(reviewer, 'chedro-intake', theme, width);
                await reviewer.goto(`${baseURL}${fixtures.evidenceReview}`);
                await inspect(reviewer, 'chedro-evidence-review', theme, width);
                await reviewerContext.close();
            }
            console.log(`Verified ${theme} at ${width}px`);
        }
    }
} finally {
    writeFileSync(`${output}/measurements.json`, JSON.stringify(results, null, 2));
    await browser.close();
}
console.log(`Browser evidence saved to ${output}`);
const failures = results.filter(result => result.overflow || result.axe.length > 0);
console.log(`${results.length} screen checks; ${failures.length} with accessibility or page-overflow findings.`);
if (failures.length > 0) process.exitCode = 1;
