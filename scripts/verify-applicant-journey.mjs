import { chromium, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';

const base = 'http://127.0.0.1:8017';
const directory = 'docs/screenshots/phase-3d7-c';
mkdirSync(directory, { recursive: true });
const results = { interactions: [], screens: [], errors: [] };
const fixture = () => JSON.parse(readFileSync('storage/app/private/applicant-journey.json', 'utf8'));
const control = action => { execFileSync('php', ['scripts/Prepare-ApplicantJourney.php', action], { stdio: 'pipe' }); return fixture(); };
const passed = name => { results.interactions.push({ name, result: 'passed' }); console.log(`Passed: ${name}`); };
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
await context.route('**/*', route => new URL(route.request().url()).hostname === '127.0.0.1' ? route.continue() : route.abort());
async function pageFor(ctx = context) {
    const page = await ctx.newPage();
    page.on('dialog', dialog => { if (dialog.type() === 'beforeunload' && page.holdNavigation) { page.navigationWarningObserved = true; dialog.dismiss(); } else dialog.accept(); });
    page.on('pageerror', error => results.errors.push(error.name + ': ' + error.message.slice(0, 150)));
    return page;
}
async function login(page) {
    await page.goto(`${base}/applicant/login`);
    await page.getByLabel('Email address', { exact: true }).fill('school@example.invalid');
    await page.getByLabel('Password', { exact: true }).fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL(`${base}/applicant`);
}
async function step(page, index) {
    if (await page.locator('[data-steps-toggle]').isVisible() && await page.locator('#applicant-steps').isHidden()) await page.locator('[data-steps-toggle]').click();
    await page.locator('#applicant-steps > button').nth(index).click();
    await page.waitForTimeout(700);
}
async function save(page) {
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page.locator('.server-save-state')).toContainText('Saved');
    await expect(page.locator('[data-client-save-state]')).toBeHidden();
}
async function screen(page, name, theme, width) {
    await page.setViewportSize({ width, height: 1000 });
    await page.evaluate(value => { localStorage.setItem('osds-theme', value); document.documentElement.classList.toggle('dark', value === 'dark'); }, theme);
    await page.waitForTimeout(200);
    const analysis = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa', 'wcag22aa']).analyze();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
    const navigationHeight = await page.locator('.applicant-sidebar').evaluate(element => Math.round(element.getBoundingClientRect().height));
    results.screens.push({ name, theme, width, overflow, navigationHeight, violations: analysis.violations.map(item => ({ id: item.id, nodes: item.nodes.map(node => node.target) })) });
    await page.screenshot({ path: `${directory}/${name}-${theme}-${width}.png` });
}
async function recordScreens(page, name) {
    for (const theme of ['light', 'dark']) for (const width of [1440, 768, 375]) await screen(page, name, theme, width);
    await page.setViewportSize({ width: 1440, height: 1000 });
}

try {
    const page = await pageFor();
    await login(page);
    await recordScreens(page, 'my-applications');
    await page.goto(`${base}/applicant/start`);
    const data = fixture();
    await expect(page.locator('[data-questionnaire-select]')).toBeDisabled();
    await page.locator('[data-organization-select]').selectOption(String(data.institution));
    await expect(page.locator('[data-questionnaire-select]')).toBeEnabled();
    await page.locator('[data-questionnaire-select]').selectOption(String(data.manifest));
    passed('Organization selection narrows questionnaires; no implicit initial selection');
    await recordScreens(page, 'start-application');
    await page.getByRole('button', { name: 'Create draft', exact: true }).click();
    await page.waitForURL(/\/applicant\/drafts\//);
    const draftPath = new URL(page.url()).pathname;
    await step(page, 3);
    await page.getByRole('button', { name: 'Check for missing information' }).click();
    await expect(page.locator('.progress-errors')).toContainText('Organization name');
    passed('Actual required-answer and document completeness validation');
    await step(page, 0);
    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic original school');
    await page.getByRole('radio', { name: 'Yes', exact: true }).check();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toBeVisible();
    await page.getByRole('textbox', { name: 'Activity details' }).fill('Synthetic activity');
    await page.getByRole('radio', { name: 'No', exact: true }).check();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toBeHidden();
    await page.getByRole('radio', { name: 'Yes', exact: true }).check();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toHaveValue('Synthetic activity');
    await step(page, 1);
    await page.getByRole('textbox', { name: 'Contact email' }).fill('journey@example.invalid');
    await step(page, 0);
    await expect(page.getByRole('textbox', { name: 'Organization name' })).toHaveValue('Synthetic original school');
    passed('Conditional visibility and unsaved values survive steps and hide/show');
    page.holdNavigation = true;
    await page.getByRole('link', { name: 'My applications', exact: true }).last().click();
    expect(page.navigationWarningObserved).toBe(true);
    expect(new URL(page.url()).pathname).toBe(draftPath);
    page.holdNavigation = false;
    passed('Real unsaved-navigation warning can cancel leaving without losing input');
    await save(page);
    await page.reload();
    await expect(page.getByRole('textbox', { name: 'Organization name' })).toHaveValue('Synthetic original school');
    await expect(page.getByRole('radio', { name: 'Yes', exact: true })).toBeChecked();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toHaveValue('Synthetic activity');
    expect(control('inspect').activityIsYes).toBe(true);
    passed('Save and resume after real persistence');
    await recordScreens(page, 'questionnaire');

    const other = await pageFor();
    await other.goto(`${base}${draftPath}`);
    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic newer school');
    await save(page);
    await other.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic stale unsaved school');
    await other.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(other.getByText('Your input has not replaced the newer saved copy.')).toBeVisible();
    await expect(other.getByRole('textbox', { name: 'Organization name' })).toHaveValue('Synthetic stale unsaved school');
    await other.locator('[data-copy-local]').last().click();
    expect(await other.locator('[data-local-copy]').inputValue()).toContain('Synthetic stale unsaved school');
    await screen(other, 'conflict', 'dark', 375);
    await other.close();
    passed('Two real tabs: stale save rejected; local input remains available to copy');

    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic original school');
    await page.waitForTimeout(750);
    let abortSave = true;
    const abortHandler = async route => {
        if (abortSave && (route.request().postData() || '').includes('"save"')) { abortSave = false; await route.abort('connectionfailed'); }
        else await route.continue();
    };
    await page.route('**/update', abortHandler);
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page.locator('[data-recovery]')).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Organization name' })).toHaveValue('Synthetic original school');
    await page.unroute('**/update', abortHandler);
    await page.getByRole('button', { name: 'Retry same action' }).click();
    await expect(page.locator('[data-recovery]')).toBeHidden();
    passed('Actual aborted network request preserves input and explicit retry succeeds');

    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic service retry school');
    await expect(page.locator('.server-save-state')).toContainText('Unsaved changes');
    let unavailable = true;
    const unavailableHandler = async route => {
        if (unavailable && (route.request().postData() || '').includes('"save"')) { unavailable = false; await route.fulfill({ status: 503, contentType: 'text/plain', body: 'Temporary service unavailable' }); }
        else await route.continue();
    };
    await page.route('**/update', unavailableHandler);
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page.locator('[data-recovery]')).toBeVisible();
    await expect(page.locator('[data-recovery-message]')).toContainText('The service could not confirm');
    await expect(page.locator('.server-save-state')).toBeHidden();
    await page.unroute('**/update', unavailableHandler);
    await page.getByRole('button', { name: 'Retry same action' }).click();
    await expect(page.locator('[data-recovery]')).toBeHidden();
    passed('Injected HTTP 503 preserves input and suppresses false success until retry');

    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic response lost school');
    await page.waitForTimeout(750);
    let loseResponse = true;
    const lostHandler = async route => {
        if (loseResponse && (route.request().postData() || '').includes('"save"')) { loseResponse = false; await route.fetch(); await route.abort('connectionfailed'); }
        else await route.continue();
    };
    await page.route('**/update', lostHandler);
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page.locator('[data-recovery]')).toBeVisible();
    const committed = control('inspect').draftVersion;
    await page.unroute('**/update', lostHandler);
    await page.getByRole('button', { name: 'Retry same action' }).click();
    await expect(page.locator('[data-recovery]')).toBeHidden();
    expect(control('inspect').draftVersion).toBe(committed);
    passed('Committed save with lost response replays without a second version increment');
    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic original school');
    await save(page);

    await step(page, 2);
    const evidence = page.locator('.evidence-input-row input').first();
    await evidence.fill('https://docs.google.com/document/');
    await page.getByRole('button', { name: 'Add link', exact: true }).click();
    await expect(page.getByText('Link not added.', { exact: true })).toBeVisible();
    await expect(evidence).toHaveValue('https://docs.google.com/document/');
    await evidence.fill('https://docs.google.com/document/d/SyntheticJourneyDocument123/edit?usp=drive_link');
    await page.getByRole('button', { name: 'Add link', exact: true }).click();
    await expect(page.locator('.field-state.success')).toContainText('Link added');
    await expect(evidence).toHaveValue('');
    passed('Invalid evidence retains input; valid Google share URL persists and clears input');
    await evidence.fill('https://docs.google.com/document/d/SyntheticReplacementDocument123/edit');
    await page.getByRole('button', { name: 'Replace link', exact: true }).click();
    await expect(page.locator('.field-state.success')).toContainText('Link replaced');
    await page.getByRole('button', { name: 'Remove', exact: true }).click();
    await expect(page.locator('.field-state.success')).toContainText('Link removed');
    await evidence.fill('https://docs.google.com/document/d/SyntheticJourneyDocument123/edit?usp=drive_link');
    await page.getByRole('button', { name: 'Add link', exact: true }).click();
    await expect(page.locator('.field-state.success')).toContainText('Link added');
    passed('Actual document replacement and removal append history and allow a new link');
    await recordScreens(page, 'document-links');
    await step(page, 3);
    await page.getByRole('button', { name: 'Check for missing information' }).click();
    await expect(page.locator('.progress-errors')).toBeHidden();
    await page.locator('.review-answer-list summary').click();
    await expect(page.locator('.review-answer-list')).toContainText('Synthetic original school');
    await recordScreens(page, 'review-and-send');
    let loseSubmission = true;
    const submissionHandler = async route => {
        if (loseSubmission && (route.request().postData() || '').includes('submitTestApplication')) { loseSubmission = false; await route.fetch(); await route.abort('connectionfailed'); }
        else await route.continue();
    };
    await page.route('**/update', submissionHandler);
    await page.getByRole('button', { name: 'Submit test application', exact: true }).click();
    await expect(page.locator('[data-recovery]')).toBeVisible();
    expect(control('inspect').submissionCount).toBe(1);
    await page.unroute('**/update', submissionHandler);
    await page.getByRole('button', { name: 'Retry same action' }).click();
    await page.waitForURL(/\/applicant\/applications\//);
    expect(control('inspect').submissionCount).toBe(1);
    passed('Uncertain submission response and explicit duplicate retry create one submitted copy');
    await recordScreens(page, 'application-tracking');

    const corrected = control('correct');
    await page.reload();
    await expect(page.getByRole('heading', { name: 'What you need to change' })).toBeVisible();
    await recordScreens(page, 'correction-request');
    await page.getByRole('link', { name: 'Make corrections', exact: true }).click();
    await expect(page.getByRole('heading', { name: 'Changes requested by CHEDRO' })).toBeVisible();
    await recordScreens(page, 'correction-editor');

    await page.getByRole('textbox', { name: 'Organization name' }).fill('Unsaved expired-session answer');
    await expect(page.locator('.server-save-state')).toContainText('Unsaved changes');
    await page.waitForTimeout(300);
    await context.clearCookies();
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page.locator('[data-recovery]')).toBeVisible();
    await expect(page.locator('[data-recovery-message]')).toContainText('session');
    await page.getByRole('button', { name: 'Show unsaved answers to copy', exact: true }).click();
    expect(await page.locator('[data-local-copy]').inputValue()).toContain('Unsaved expired-session answer');
    expect(await page.evaluate(() => JSON.stringify([Object.entries(localStorage), Object.entries(sessionStorage)]).includes('Unsaved expired-session answer'))).toBe(false);
    passed('Actual session-cookie loss: no false save; RAM-only copy recovery is available');

    const resumed = await pageFor();
    await login(resumed);
    await resumed.goto(`${base}${corrected.draftPath}`);
    await resumed.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic corrected school');
    await save(resumed);
    await step(resumed, 3);
    await resumed.getByRole('button', { name: 'Send corrected test application', exact: true }).click();
    await resumed.waitForURL(/\/applicant\/applications\//);
    const final = control('inspect');
    expect(final.state).toBe('resubmitted'); expect(final.submissionCount).toBe(2); expect(final.originalAnswer).toBe('Synthetic original school');
    expect(final.activityIsYes).toBe(true);
    await recordScreens(resumed, 'corrections-sent');
    passed('Correction editing and resubmission preserve the original immutable answer');

    for (const action of ['expire', 'revoke']) {
        control(action);
        const response = await resumed.goto(`${base}${final.applicationPath}`);
        expect(response.status()).toBe(404);
        control('restore');
    }
    passed('Expired and revoked representation deny the real tracking URL');
    await resumed.goto(`${base}/applicant/account`);
    await recordScreens(resumed, 'account');
    await resumed.getByRole('button', { name: 'Menu', exact: true }).isVisible().catch(() => false);
    await resumed.setViewportSize({ width: 375, height: 900 });
    const menu = resumed.getByRole('button', { name: 'Menu', exact: true });
    await menu.focus(); await resumed.keyboard.press('Enter');
    await expect(menu).toHaveAttribute('aria-expanded', 'true');
    await resumed.keyboard.press('Escape'); await expect(menu).toBeFocused();
    await expect(menu).toHaveAttribute('aria-expanded', 'false');
    passed('Mobile menu opens by keyboard, closes with Escape and returns focus');
    await resumed.getByRole('button', { name: 'Menu', exact: true }).click();
    await resumed.getByRole('button', { name: 'System', exact: true }).click();
    await resumed.emulateMedia({ colorScheme: 'dark' });
    await expect(resumed.locator('html')).toHaveClass(/dark/);
    await resumed.emulateMedia({ colorScheme: 'light' });
    await expect(resumed.locator('html')).not.toHaveClass(/dark/);
    passed('System theme follows real browser media preference changes');
    const retired = control('retire');
    await resumed.goto(`${base}${retired.retiredPath}`);
    await expect(resumed.getByText('Read-only application', { exact: true })).toBeVisible();
    await expect(resumed.getByRole('textbox', { name: 'Organization name' })).toHaveValue('Preserved retired answer');
    await expect(resumed.getByRole('textbox', { name: 'Organization name' })).toBeDisabled();
    await expect(resumed.getByRole('button', { name: 'Save draft', exact: true })).toBeHidden();
    await recordScreens(resumed, 'retired-draft');
    passed('Retired manifest with an open working draft preserves answers and blocks editing');
    await page.close();
} catch (error) {
    results.failure = { name: error.name, message: error.message.slice(0, 2200) };
    console.error(results.failure);
    process.exitCode = 1;
} finally {
    writeFileSync(`${directory}/results.json`, JSON.stringify(results, null, 2));
    await browser.close();
}
if (results.screens.some(screen => screen.overflow || screen.violations.length)) process.exitCode = 1;
console.log(`${results.interactions.length} interaction checks; ${results.screens.length} screen checks.`);
