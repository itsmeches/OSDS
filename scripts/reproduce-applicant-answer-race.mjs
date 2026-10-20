import { chromium, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

// Deliberately fails while C-INT-001 exists. Never targets a manual-testing database.
execFileSync('php', ['scripts/Prepare-ApplicantJourney.php', 'prepare'], { stdio: 'pipe' });
const fixture = JSON.parse(readFileSync('storage/app/private/applicant-journey.json', 'utf8'));
const output = 'docs/screenshots/phase-3d7-c';
mkdirSync(output, { recursive: true });
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
const trace = [];
let lifecycleBeforeReload = [];
if (process.argv.includes('--trace')) await page.addInitScript(id => {
    window.__answerTrace = [];
    const nodes = new WeakMap();
    let nextNode = 0;
    const push = (phase, extra = {}) => {
        const root = document.querySelector('[data-applicant-editor]');
        const wire = root && window.Livewire?.find(root.getAttribute('wire:id'));
        const radios = [...(root?.querySelectorAll('input[type=radio]') || [])];
        window.__answerTrace.push({ t: Math.round(performance.now()), phase, componentId: root?.getAttribute('wire:id'), nodes: radios.map(node => { if (!nodes.has(node)) nodes.set(node, ++nextNode); return { id: nodes.get(node), value: node.value, key: node.closest('[wire\\:key]')?.getAttribute('wire:key') }; }), answer: wire?.answers?.[id] ?? null, selected: root?.querySelector('input[type=radio]:checked')?.value ?? null, ...extra });
    };
    for (const type of ['click', 'input', 'change']) document.addEventListener(type, event => {
        if (event.target.matches('input[type=radio]')) {
            push(`native-${type}`, { value: event.target.value, trusted: event.isTrusted });
            queueMicrotask(() => push(`after-${type}`));
        }
    }, true);
    document.addEventListener('livewire:init', () => Livewire.hook('component.init', ({ component }) => {
        if (!component.el.matches('[data-applicant-editor]')) return;
        const merge = component.mergeNewSnapshot.bind(component);
        component.mergeNewSnapshot = (...args) => {
            push('before-snapshot-merge', { updates: args[2]?.[`answers.${id}`] ?? null, rootUpdate: args[2]?.answers?.[id] ?? null, updateKeys: Object.keys(args[2] || {}), oldKeys: Object.keys(component.canonical.answers || {}), clientKeys: Object.keys(component.ephemeral.answers || {}), incomingKeys: Object.keys(JSON.parse(args[0]).data.answers?.[0] || {}), incoming: JSON.parse(args[0]).data.answers?.[0]?.[id] ?? null });
            const result = merge(...args);
            push('after-snapshot-merge');
            return result;
        };
        component.$wire.$interceptMessage(({ message, onSend, onSuccess }) => {
            onSend(() => push('dispatch', { actions: [...message.actions].map(action => action.name), snapshotAnswer: JSON.parse(message.snapshot).data.answers?.[0]?.[id] ?? null, update: message.updates?.[`answers.${id}`] ?? null, rootUpdate: message.updates?.answers?.[id] ?? null }));
            onSuccess(({ onSync, onMorph, onMorphed }) => {
                push('response');
                onSync(() => push('synced'));
                onMorph(() => push('before-morph'));
                onMorphed(() => push('after-morph'));
            });
        });
    }));
}, fixture.questions.has_activity);
const record = async phase => trace.push({ phase, ...await page.evaluate(id => {
    const component = Livewire.find(document.querySelector('[data-applicant-editor]').getAttribute('wire:id'));
    return { answer: component.answers[id], selected: [...document.querySelectorAll('input[type=radio]')].find(input => input.checked)?.value ?? null };
}, fixture.questions.has_activity) });
try {
    await page.goto('http://127.0.0.1:8017/applicant/login');
    await page.getByLabel('Email address', { exact: true }).fill('school@example.invalid');
    await page.getByLabel('Password', { exact: true }).fill('UiBrowserOnly-2026!');
    await page.getByRole('button', { name: 'Sign in', exact: true }).click();
    await page.waitForURL('**/applicant');
    await page.goto('http://127.0.0.1:8017/applicant/start');
    await page.locator('[data-organization-select]').selectOption(String(fixture.institution));
    await page.locator('[data-questionnaire-select]').selectOption(String(fixture.manifest));
    await page.getByRole('button', { name: 'Create draft', exact: true }).click();
    await page.waitForURL('**/applicant/drafts/*');
    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic race probe');
    await page.getByRole('radio', { name: 'Yes', exact: true }).check();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toBeVisible();
    await record('first-yes');
    await page.getByRole('textbox', { name: 'Activity details' }).fill('Synthetic details');
    await page.getByRole('radio', { name: 'No', exact: true }).check();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toBeHidden();
    await record('no');
    await page.getByRole('radio', { name: 'Yes', exact: true }).check();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toHaveValue('Synthetic details');
    await record('second-yes-requested');
    await page.locator('#applicant-steps > button').nth(1).click();
    await expect(page.getByRole('heading', { name: 'Contact details', exact: true })).toBeVisible();
    await page.getByRole('textbox', { name: 'Contact email' }).fill('race@example.invalid');
    await page.locator('#applicant-steps > button').nth(0).click();
    await expect(page.getByRole('heading', { name: 'Organization details', exact: true })).toBeVisible();
    await record('returned-to-step');
    if (process.argv.includes('--trace')) writeFileSync(`${output}/answer-race-lifecycle.json`, JSON.stringify(await page.evaluate(() => window.__answerTrace), null, 2));
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await expect(page.locator('.server-save-state')).toContainText('Saved');
    if (process.argv.includes('--trace')) lifecycleBeforeReload = await page.evaluate(() => window.__answerTrace);
    execFileSync('php', ['scripts/Prepare-ApplicantJourney.php', 'inspect'], { stdio: 'pipe' });
    expect(JSON.parse(readFileSync('storage/app/private/applicant-journey.json', 'utf8')).activityIsYes).toBe(true);
    await page.reload();
    await record('persisted-and-reloaded');
    await page.screenshot({ path: `${output}/answer-race-after-reload.png` });
    expect(trace.at(-1).answer, 'The last intentional Yes must remain Yes after saving and reloading').toBe('yes');
    expect(trace.at(-1).selected).toBe('yes');
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Activity details' })).toHaveValue('Synthetic details');
} catch (error) {
    process.exitCode = 1;
    console.error('Applicant last-answer preservation regression failed; see answer-race.json.');
} finally {
    writeFileSync(`${output}/answer-race.json`, JSON.stringify({ expected: 'yes', trace, failed: process.exitCode === 1 }, null, 2));
    if (process.argv.includes('--trace')) writeFileSync(`${output}/answer-race-lifecycle.json`, JSON.stringify([...lifecycleBeforeReload, ...await page.evaluate(() => window.__answerTrace || [])], null, 2));
    await browser.close();
}
