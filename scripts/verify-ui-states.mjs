import { chromium } from '@playwright/test';
import { mkdirSync, writeFileSync, readFileSync } from 'node:fs';
import AxeBuilder from '@axe-core/playwright';

const baseURL = 'http://127.0.0.1:8017';
const output = 'docs/screenshots/phase-3d7-b';
const fixtures = JSON.parse(readFileSync('storage/app/private/ui-browser-fixtures.json', 'utf8'));
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const results = [];

async function pair(locator, foregroundProperty = 'color', pseudo = null) {
    return locator.evaluate((element, settings) => {
        const style = getComputedStyle(element);
        const foregroundStyle = settings.pseudo ? getComputedStyle(element, settings.pseudo) : style;
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 1;
        const context = canvas.getContext('2d');
        const color = value => { context.clearRect(0, 0, 1, 1); context.fillStyle = value; context.fillRect(0, 0, 1, 1); return [...context.getImageData(0, 0, 1, 1).data].slice(0, 3); };
        const luminance = value => color(value).map(v => v / 255).map(v => v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4).reduce((sum, v, i) => sum + v * [.2126, .7152, .0722][i], 0);
        const chain = [];
        for (let node = element; node; node = node.parentElement) chain.unshift(node);
        const backdrop = chain.reduce((base, node) => {
            context.clearRect(0, 0, 1, 1);
            context.fillStyle = getComputedStyle(node).backgroundColor;
            context.fillRect(0, 0, 1, 1);
            const rgba = [...context.getImageData(0, 0, 1, 1).data];
            const alpha = rgba[3] / 255;
            return base.map((value, i) => rgba[i] * alpha + value * (1 - alpha));
        }, [255, 255, 255]);
        const backgroundColor = `rgb(${backdrop.join(', ')})`;
        const foreground = luminance(foregroundStyle[settings.foregroundProperty]), background = luminance(backgroundColor);
        return { foreground: foregroundStyle[settings.foregroundProperty], background: backgroundColor, ratio: +((Math.max(foreground, background) + .05) / (Math.min(foreground, background) + .05)).toFixed(2), outlineColor: style.outlineColor, outlineWidth: style.outlineWidth, outlineStyle: style.outlineStyle };
    }, { foregroundProperty, pseudo });
}

try {
    for (const theme of ['light', 'dark']) {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, colorScheme: theme });
        await context.addInitScript(value => localStorage.setItem('osds-theme', value), theme);
        const page = await context.newPage();
        await page.goto(`${baseURL}/applicant/login`);
        const button = page.getByRole('button', { name: 'Sign in', exact: true });
        const record = { theme, normal: await pair(button) };
        record.inputBorder = await pair(page.locator('#email'), 'borderTopColor');
        await button.hover();
        record.hover = await pair(button);
        await page.locator('#password').focus();
        await page.keyboard.press('Tab');
        record.keyboardReachedSignIn = await button.evaluate(element => element === document.activeElement);
        record.focus = await pair(button);
        await button.evaluate(element => element.disabled = true);
        record.disabled = await pair(button);
        await button.evaluate(element => element.disabled = false);
        await page.getByLabel('Email address', { exact: true }).fill('school@example.invalid');
        await page.getByLabel('Password', { exact: true }).fill('Incorrect-synthetic-password');
        await button.click();
        await page.getByRole('alert').waitFor();
        record.errorVisible = await page.getByRole('alert').isVisible();
        const errorStyle = await page.getByRole('alert').evaluate(el => ({ color: getComputedStyle(el).color, background: getComputedStyle(el).backgroundColor }));
        record.error = { ...await pair(page.getByRole('alert')), ...errorStyle };
        await page.screenshot({ path: `${output}/login-error-${theme}.png` });
        await page.getByRole('button', { name: 'System', exact: true }).click();
        await page.emulateMedia({ colorScheme: 'dark' });
        await page.waitForFunction(() => document.documentElement.classList.contains('dark'));
        record.systemDark = await page.evaluate(() => document.documentElement.classList.contains('dark'));
        await page.emulateMedia({ colorScheme: 'light' });
        await page.waitForFunction(() => !document.documentElement.classList.contains('dark'));
        record.systemLight = await page.evaluate(() => !document.documentElement.classList.contains('dark'));
        await page.getByRole('button', { name: theme === 'dark' ? 'Dark' : 'Light', exact: true }).click();
        await page.getByLabel('Password', { exact: true }).fill('UiBrowserOnly-2026!');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(`${baseURL}/applicant`);
        await page.goto(`${baseURL}${fixtures.draft}`);
        await page.locator('.progress-rail button').nth(1).click();
        const evidenceInput = page.locator('.evidence-input-row input').first();
        await evidenceInput.waitFor();
        record.placeholder = await pair(evidenceInput, 'color', '::placeholder');
        record.placeholderTextVisible = await evidenceInput.evaluate(element => element.value === '' && !!element.placeholder);
        await page.locator('.progress-rail button').nth(2).click();
        await page.getByRole('button', { name: 'Submit test application', exact: true }).waitFor();
        const submit = page.getByRole('button', { name: 'Submit test application', exact: true });
        await page.waitForTimeout(400);
        await submit.evaluate(element => { element.setAttribute('aria-busy', 'true'); element.disabled = true; });
        record.loadingDisabled = await pair(submit);
        await submit.evaluate(element => { element.removeAttribute('aria-busy'); element.disabled = false; });
        record.reflow = [];
        for (const width of [720, 360]) {
            await page.setViewportSize({ width, height: 1000 });
            record.reflow.push(await page.evaluate(() => ({ width: innerWidth, documentWidth: document.documentElement.scrollWidth, overflow: document.documentElement.scrollWidth > innerWidth + 1 })));
        }
        await page.emulateMedia({ reducedMotion: 'reduce' });
        record.reducedMotion = await page.evaluate(() => ({ preference: matchMedia('(prefers-reduced-motion: reduce)').matches, duration: getComputedStyle(document.querySelector('button')).transitionDuration }));
        results.push(record);
        await context.close();
    }
    // Additional theme-aware shared shells, using synthetic-only routes.
    const context = await browser.newContext({ viewport: { width: 375, height: 900 } });
    const page = await context.newPage();
    for (const theme of ['light', 'dark']) {
        await page.goto(`${baseURL}/`);
        await page.getByRole('button', { name: theme === 'dark' ? 'Dark' : 'Light', exact: true }).click();
        const axe = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
        results.push({ name: 'public-home', theme, width: 375, overflow: await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), violations: axe.violations.map(v => v.id) });
        await page.screenshot({ path: `${output}/public-home-${theme}-375.png` });
    }
    await context.close();
    for (const theme of ['light', 'dark']) {
        const staffContext = await browser.newContext({ viewport: { width: 1440, height: 1000 }, colorScheme: theme });
        await staffContext.addInitScript(value => localStorage.setItem('theme', value), theme);
        const staff = await staffContext.newPage();
        await staff.goto(`${baseURL}/admin/login`);
        await staff.waitForFunction(() => window.Livewire?.all().length > 0);
        await staff.locator('input[type="email"]').fill('osds@example.invalid');
        await staff.locator('input[type="password"]').fill('Incorrect-synthetic-password');
        await staff.getByRole('button', { name: 'Sign in', exact: true }).click();
        await staff.locator('.fi-fo-field-wrp-error-message').first().waitFor();
        const invalidInput = staff.locator('.fi-input-wrp.fi-invalid').first();
        const axe = await new AxeBuilder({ page: staff }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
        results.push({ name: 'staff-login-error', theme, error: await pair(staff.locator('.fi-fo-field-wrp-error-message').first()), invalidInputVisible: await invalidInput.isVisible(), overflow: await staff.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), violations: axe.violations.map(v => v.id) });
        await staff.screenshot({ path: `${output}/staff-login-error-${theme}.png` });
        await staffContext.close();
    }
} finally {
    writeFileSync(`${output}/interaction-states.json`, JSON.stringify(results, null, 2));
    await browser.close();
}
console.log('Interaction, system-theme, keyboard, and reflow evidence saved.');
