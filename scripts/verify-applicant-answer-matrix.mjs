import { chromium, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';

const output = 'docs/screenshots/phase-3d7-c';
mkdirSync(output, { recursive: true });
execFileSync('php', ['scripts/Prepare-ApplicantJourney.php', 'prepare-matrix'], { stdio: 'pipe' });
const fixture = JSON.parse(readFileSync('storage/app/private/applicant-journey.json', 'utf8'));
const database = () => {
    execFileSync('php', ['scripts/Prepare-ApplicantJourney.php', 'matrix-inspect'], { stdio: 'pipe' });
    return JSON.parse(readFileSync('storage/app/private/applicant-journey.json', 'utf8'));
};
const browser = await chromium.launch({ channel: 'chrome', headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await context.newPage();
const lifecycle = [];
let currentCase = 'setup';
if (process.argv.includes('--trace')) {
    await page.exposeFunction('__recordSyntheticAnswer', record => lifecycle.push({ case: currentCase, ...record }));
    await page.addInitScript(id => {
        const record = (component, phase, extra = {}) => window.__recordSyntheticAnswer({ t: Math.round(performance.now()), phase, answer: component?.$wire.answers[id] ?? null, selected: component?.el.querySelector(`[data-question-id="${id}"] input:checked`)?.value ?? null, ...extra });
        document.addEventListener('livewire:init', () => Livewire.hook('component.init', ({ component }) => {
            if (!component.el.matches('[data-applicant-editor]')) return;
            component.el.addEventListener('change', event => { if (event.target.closest(`[data-question-id="${id}"]`)) record(component, 'native-change', { value: event.target.value }); }, true);
            const merge = component.mergeNewSnapshot.bind(component);
            component.mergeNewSnapshot = (...args) => {
                record(component, 'merge-before', { update: args[2]?.[`answers.${id}`] ?? null, rootUpdate: args[2]?.answers?.[id] ?? null, incoming: JSON.parse(args[0]).data.answers?.[0]?.[id] ?? null });
                const result = merge(...args); record(component, 'merge-after'); return result;
            };
            component.$wire.$interceptMessage(({ message, onSend }) => onSend(() => record(component, 'send', { update: message.updates?.[`answers.${id}`] ?? null, rootUpdate: message.updates?.answers?.[id] ?? null, actions: [...message.actions].map(action => action.name) })));
        }));
    }, fixture.questions.has_activity);
}
page.on('dialog', dialog => dialog.accept());
const pending = new Set();
page.on('request', request => { if (request.method() === 'POST' && new URL(request.url()).pathname.endsWith('/update')) pending.add(request); });
page.on('requestfinished', request => pending.delete(request));
page.on('requestfailed', request => pending.delete(request));
const results = { cases: [], failures: [] };
const field = key => page.locator(`[data-question-id="${fixture.questions[key]}"]`);
const radio = (key, value) => field(key).getByRole('radio', { name: value === 'yes' ? 'Yes' : 'No', exact: true });
const choose = (key, value) => radio(key, value).check();
const answer = key => page.evaluate(id => Livewire.find(document.querySelector('[data-applicant-editor]').getAttribute('wire:id')).answers[id], fixture.questions[key]);
const save = async () => {
    const response = page.waitForResponse(response => response.request().method() === 'POST' && (response.request().postData() || '').includes('"save"'));
    await page.getByRole('button', { name: 'Save draft', exact: true }).click();
    await response;
    await expect(page.locator('.server-save-state')).toContainText('Saved');
};
const step = async index => {
    const response = page.waitForResponse(response => response.request().method() === 'POST' && (response.request().postData() || '').includes('goToStep'));
    await page.locator('#applicant-steps > button').nth(index).click();
    await response;
    await expect(page.getByRole('heading', { name: index === 0 ? 'Organization details' : 'Contact details', exact: true })).toBeVisible();
};
const state = async expected => {
    for (const [key, value] of Object.entries(expected)) {
        await expect.poll(() => answer(key)).toEqual(value);
        if (['has_activity', 'has_team'].includes(key)) await expect(radio(key, value)).toBeChecked();
        if (key === 'level') await expect(field(key).getByRole('radio', { name: value === 'primary' ? 'Primary' : 'Secondary', exact: true })).toBeChecked();
        if (key === 'programs') for (const option of ['primary', 'secondary']) {
            await expect(field(key).getByRole('checkbox', { name: option === 'primary' ? 'Primary' : 'Secondary', exact: true })).toBeChecked({ checked: value.includes(option) });
        }
    }
    await expect(field('details')).toBeVisible({ visible: expected.has_activity === 'yes' });
    await expect(field('team_details')).toBeVisible({ visible: expected.has_team === 'yes' });
};
const run = async (name, exercise, expected = { has_activity: 'yes', has_team: 'no', level: 'primary', programs: ['primary'] }, alreadySaved = false) => {
    const focusedCase = process.argv.find(argument => argument.startsWith('--case='))?.slice(7);
    if (focusedCase && !name.includes(focusedCase)) return;
    currentCase = name;
    await page.reload();
    await choose('has_activity', 'no');
    await choose('has_team', 'no');
    await field('level').getByRole('radio', { name: 'Primary', exact: true }).check();
    await field('programs').getByRole('checkbox', { name: 'Primary', exact: true }).check();
    await field('programs').getByRole('checkbox', { name: 'Secondary', exact: true }).uncheck();
    await save();
    const extra = await exercise();
    if (!alreadySaved) await save();
    await expect.poll(() => pending.size).toBe(0);
    await state(expected);
    const saved = database();
    for (const [key, value] of Object.entries(expected)) expect(saved.answerValues[fixture.questions[key]], `${name}: database ${key}`).toEqual(value);
    for (const [parent, child, label] of [['has_activity', 'details', 'Activity details'], ['has_team', 'team_details', 'Team details']]) {
        const empty = !saved.answerValues[fixture.questions[child]];
        expect(saved.missingAnswers.includes(label), `${name}: conditional applicability ${child}`).toBe(expected[parent] === 'yes' && empty);
    }
    await page.reload();
    await state(expected);
    results.cases.push({ name, expected, visible: expected, client: expected, persisted: Object.fromEntries(Object.keys(expected).map(key => [key, saved.answerValues[fixture.questions[key]]])), reloaded: Object.fromEntries(await Promise.all(Object.keys(expected).map(async key => [key, await answer(key)]))), applicabilityVerified: true, ...extra });
    writeFileSync(`${output}/answer-matrix.json`, JSON.stringify(results, null, 2));
    if (process.argv.includes('--trace')) writeFileSync(`${output}/answer-matrix-lifecycle.json`, JSON.stringify(lifecycle, null, 2));
    console.log(`Passed: ${name}`);
};

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
    await page.getByRole('textbox', { name: 'Organization name' }).fill('Synthetic answer matrix');
    await choose('has_activity', 'no'); await choose('has_team', 'no');
    await field('level').getByRole('radio', { name: 'Primary', exact: true }).check();
    await field('programs').getByRole('checkbox', { name: 'Primary', exact: true }).check();
    await step(1);
    await page.getByRole('textbox', { name: 'Contact email' }).fill('matrix@example.invalid');
    await step(0); await save();

    await run('Yes → No → Yes with rendered conditional transitions', async () => {
        await choose('has_activity', 'yes'); await expect(field('details')).toBeVisible();
        await field('details').getByRole('textbox').fill('Retained child');
        await choose('has_activity', 'no'); await expect(field('details')).toBeHidden();
        await choose('has_activity', 'yes'); await expect(field('details').getByRole('textbox')).toHaveValue('Retained child');
    });
    await run('No → Yes → No', async () => {
        await choose('has_activity', 'no'); await choose('has_activity', 'yes'); await choose('has_activity', 'no');
    }, { has_activity: 'no', has_team: 'no', level: 'primary', programs: ['primary'] });
    await run('Yes → No → Yes without deliberate pauses', async () => {
        await choose('has_activity', 'yes'); await choose('has_activity', 'no'); await choose('has_activity', 'yes');
    });
    await run('Rapid input immediately before Save', async () => {
        await choose('has_activity', 'yes'); await choose('has_activity', 'no'); await choose('has_activity', 'yes'); await save();
    }, undefined, true);
    await run('Rapid input followed by section navigation', async () => {
        await choose('has_activity', 'yes'); await choose('has_activity', 'no'); await choose('has_activity', 'yes');
        await step(1); await step(0);
    });
    await run('Conditional child hide and reshow retains its current text', async () => {
        await choose('has_activity', 'yes'); await expect(field('details')).toBeVisible();
        await field('details').getByRole('textbox').fill('Latest conditional child');
        await choose('has_activity', 'no'); await expect(field('details')).toBeHidden();
        await choose('has_activity', 'yes'); await expect(field('details').getByRole('textbox')).toHaveValue('Latest conditional child');
    });
    await run('Multiple question and checkbox changes', async () => {
        for (let index = 0; index < 3; index++) {
            await choose('has_activity', 'yes'); await choose('has_team', 'yes');
            await field('level').getByRole('radio', { name: 'Secondary', exact: true }).check();
            await field('programs').getByRole('checkbox', { name: 'Secondary', exact: true }).check();
            await choose('has_activity', 'no'); await choose('has_team', 'no');
            await field('programs').getByRole('checkbox', { name: 'Secondary', exact: true }).uncheck();
        }
        await choose('has_activity', 'yes'); await choose('has_team', 'yes');
        await field('programs').getByRole('checkbox', { name: 'Secondary', exact: true }).check();
    }, { has_activity: 'yes', has_team: 'yes', level: 'secondary', programs: ['primary', 'secondary'] });
    await run('Keyboard radio group input', async () => {
        await radio('has_activity', 'no').focus();
        await page.keyboard.press('ArrowLeft'); await page.keyboard.press('ArrowRight'); await page.keyboard.press('ArrowLeft');
    });

    await run('In-flight reply delay preserves rapid input and serializes later requests', async () => {
        let releaseOlder, olderDispatched;
        const gate = new Promise(resolve => releaseOlder = resolve);
        const sent = new Promise(resolve => olderDispatched = resolve);
        let first = true, sequence = 0;
        const dispatch = [], release = [];
        const handler = async route => {
            const id = ++sequence;
            dispatch.push(id);
            const older = first; first = false;
            if (older) olderDispatched();
            try {
                const response = await route.fetch();
                if (older) await gate;
                await route.fulfill({ response });
                release.push(id);
            } catch { /* A superseded browser request may already have been cancelled. */ }
        };
        await page.route('**/update', handler);
        await choose('has_activity', 'yes'); await sent;
        await choose('has_activity', 'no'); await choose('has_activity', 'yes');
        expect(dispatch).toEqual([1]);
        expect(await answer('has_activity')).toBe('yes');
        releaseOlder();
        await expect.poll(() => dispatch.length).toBeGreaterThan(1);
        await expect.poll(() => pending.size).toBe(0);
        await page.unroute('**/update', handler);
        expect(dispatch.length).toBeGreaterThan(1);
        expect(release[0]).toBe(1);
        return { dispatchOrder: dispatch, responseReleaseOrder: release, sameComponentOvertakingPrevented: true };
    });

    await run('Input changed while Save is in flight is not falsely marked saved', async () => {
        await choose('has_activity', 'yes');
        let releaseSave, saveDispatched;
        const gate = new Promise(resolve => releaseSave = resolve);
        const sent = new Promise(resolve => saveDispatched = resolve);
        let first = true;
        const handler = async route => {
            if (first && (route.request().postData() || '').includes('"save"')) {
                first = false;
                const response = await route.fetch(); saveDispatched(); await gate;
                await route.fulfill({ response });
            } else await route.continue();
        };
        await page.route('**/update', handler);
        await page.getByRole('button', { name: 'Save draft', exact: true }).click(); await sent;
        await choose('has_activity', 'no');
        releaseSave();
        await expect.poll(() => pending.size).toBe(0);
        await expect(page.locator('[data-client-save-state]')).toContainText('Unsaved changes');
        await expect(page.locator('.server-save-state')).toBeHidden();
        await page.unroute('**/update', handler);
    }, { has_activity: 'no', has_team: 'no', level: 'primary', programs: ['primary'] });

    await run('New tab cannot overwrite a newer saved answer', async () => {
        const stale = await context.newPage(); stale.on('dialog', dialog => dialog.accept());
        await stale.goto(page.url());
        await choose('has_activity', 'yes');
        let releaseSavedReply, saveCommitted;
        const gate = new Promise(resolve => releaseSavedReply = resolve);
        const committed = new Promise(resolve => saveCommitted = resolve);
        let first = true;
        const handler = async route => {
            if (first && (route.request().postData() || '').includes('"save"')) {
                first = false;
                const response = await route.fetch(); saveCommitted(); await gate;
                await route.fulfill({ response });
            } else await route.continue();
        };
        await page.route('**/update', handler);
        await page.getByRole('button', { name: 'Save draft', exact: true }).click(); await committed;
        await stale.locator(`[data-question-id="${fixture.questions.has_activity}"]`).getByRole('radio', { name: 'No', exact: true }).check();
        await stale.getByRole('textbox', { name: 'Organization name' }).fill('Stale synthetic local input');
        await stale.getByRole('button', { name: 'Save draft', exact: true }).click();
        await expect(stale.getByText('Your input has not replaced the newer saved copy.', { exact: true })).toBeVisible();
        await expect(stale.getByRole('textbox', { name: 'Organization name' })).toHaveValue('Stale synthetic local input');
        expect(database().answerValues[fixture.questions.has_activity]).toBe('yes');
        releaseSavedReply();
        await expect(page.locator('.server-save-state')).toContainText('Saved');
        await page.unroute('**/update', handler);
        await stale.close();
        return { responseReleaseOrder: ['stale-tab-conflict', 'saved-tab-confirmation'] };
    });
    await page.screenshot({ path: `${output}/answer-matrix-after-reload.png` });
} catch (error) {
    results.failures.push({ name: error.name, message: error.message.slice(0, 1600), validation: await page.locator('.field-error').allTextContents(), checkboxValueType: typeof await answer('programs') });
    console.error(results.failures.at(-1)); process.exitCode = 1;
} finally {
    writeFileSync(`${output}/answer-matrix.json`, JSON.stringify(results, null, 2));
    if (process.argv.includes('--trace')) writeFileSync(`${output}/answer-matrix-lifecycle.json`, JSON.stringify(lifecycle, null, 2));
    await browser.close();
}
console.log(`${results.cases.length} complete answer-integrity cases; ${results.failures.length} failures.`);
