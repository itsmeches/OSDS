import fs from 'node:fs';
import assert from 'node:assert/strict';
import {dashboardMetadataFailure, diagnoseDashboardCandidate} from './uiux-b2-dashboard-diagnostics-candidate.mjs';
let checks = 0;
const test = (actual, expected) => {assert.deepEqual(actual, expected); checks++;};
const snapshot = name => JSON.stringify({memo: {name}});
const dashboard = 'App\\Filament\\Pages\\Dashboard', login = 'Filament\\Auth\\Pages\\Login';
const specs = [
  ['VALID', [snapshot(dashboard)], false, 1, true, 'NONE'],
  ['NESTED_WIDGET', [snapshot(dashboard), snapshot('App\\Filament\\Widgets\\TaskOrientation')], false, 1, true, 'NONE'],
  ['MISSING', [], false, 1, true, 'DASHBOARD_COMPONENT_MISSING'],
  ['DUPLICATE', [snapshot(dashboard), snapshot(dashboard)], false, 1, true, 'DASHBOARD_COMPONENT_DUPLICATED'],
  ['LOGIN', [snapshot(dashboard), snapshot(login)], false, 1, true, 'UNEXPECTED_LOGIN_COMPONENT'],
  ['FORM', [snapshot(dashboard)], true, 1, true, 'LOGIN_FORM_PRESENT'],
  ['MALFORMED', [snapshot(dashboard), '{invalid'], false, 1, true, 'MALFORMED_COMPONENT_METADATA'],
  ['MISSING_NAME', ['{"memo":{}}'], false, 1, true, 'MALFORMED_COMPONENT_METADATA'],
  ['NO_HEADING', [snapshot(dashboard)], false, 0, false, 'HEADING_MISSING'],
  ['HIDDEN_HEADING', [snapshot(dashboard)], false, 1, false, 'HEADING_INVISIBLE'],
  ['DUPLICATE_HEADING', [snapshot(dashboard)], false, 2, true, 'HEADING_DUPLICATED'],
  ['DESTINATION', [snapshot(dashboard)], false, 1, true, 'DESTINATION_CHANGED'],
  ['PAGE_FATAL', [snapshot(dashboard)], false, 1, true, 'EXISTING_FATAL_EVENT'],
  ['REQUEST_FATAL', [snapshot(dashboard)], false, 1, true, 'EXISTING_FATAL_EVENT'],
];
const results = [];
for (const [name, names, form, count, visible, code] of specs) {
  let fatal = false, reject;
  const failed = new Promise((_, r) => {reject = r;}); failed.catch(() => {});
  const result = {authenticationConfirmed: false};
  const page = {
    url: () => name === 'DESTINATION' ? 'http://127.0.0.1:18108/away' : 'http://127.0.0.1:18108/admin',
    evaluate: async collector => {
      if (name.endsWith('FATAL')) {fatal = true; reject(Error('synthetic private error'));}
      globalThis.document = {querySelectorAll: () => names.map(text => ({getAttribute: () => text})), forms: form ? [{querySelector: () => true}] : []};
      try {return collector();} finally {delete globalThis.document;}
    },
    getByRole: (role, options) => {
      test([role, options], ['heading', {name: 'Your workspace', exact: true, level: 1, includeHidden: true}]);
      // Node fixtures model the role filter explicitly, including a visible h2
      // beside the hidden h1. Real Playwright coverage is required separately.
      const nodes = [...Array.from({length: count}, () => ({level: 1, visible})), {level: 2, visible: true}];
      const matches = nodes.filter(node => node.level === options.level && (options.includeHidden || node.visible));
      if (count === 1 && !visible) test(nodes.filter(node => node.level === 1 && node.visible).length, 0);
      return {count: async () => matches.length, isVisible: async () => matches.length === 1 && matches[0].visible};
    },
  };
  // Explicit deterministic assertion stand-in: actual collector/candidate run;
  // no Playwright, DOM engine or wall-clock timeout is executed.
  const expect = {poll: callback => ({toBe: async expected => {
    const value = await callback(); if (value !== expected) throw Error('synthetic assertion detail');
  }})};
  try {await diagnoseDashboardCandidate({page, expect, base: 'http://127.0.0.1:18108', failed, hasFailed: () => fatal, result});
    if (fatal) throw Error('Fatal'); result.authenticationConfirmed = true;
  } catch {}
  test(result.dashboardDiagnostic.code, code);
  test(result.authenticationConfirmed, code === 'NONE');
  test(JSON.stringify(result).includes('synthetic'), false);
  results.push({name, code, authenticationConfirmed: result.authenticationConfirmed});
}
test(dashboardMetadataFailure({dashboard: 1, login: 0, loginForm: false, malformed: false, secret: 'synthetic'}), 'INVALID_DIAGNOSTIC_METADATA');
test(dashboardMetadataFailure({dashboard: 0, login: 1, loginForm: true, malformed: true}), 'MALFORMED_COMPONENT_METADATA');
test(dashboardMetadataFailure({dashboard: 0, login: 1, loginForm: true, malformed: false}), 'UNEXPECTED_LOGIN_COMPONENT');
test(dashboardMetadataFailure({dashboard: 0, login: 0, loginForm: true, malformed: false}), 'LOGIN_FORM_PRESENT');
const app = fs.readFileSync('app/Filament/Pages/Dashboard.php', 'utf8');
test(app.includes("return 'Your workspace';"), true);
test(fs.readFileSync('vendor/filament/filament/resources/views/components/header/index.blade.php', 'utf8').includes('<h1 class="fi-header-heading">'), true);
test(fs.readFileSync('vendor/filament/filament/src/Panel/Concerns/HasComponents.php', 'utf8').includes('$this->livewireComponents[$componentName] = $component;'), true);
test(fs.readFileSync('vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php', 'utf8').includes("'name' => $component->getName()"), true);
console.log(JSON.stringify({status: 'PASS', checks, cases: results.length, results, scope: 'offline candidate and node-double fixtures only'}));
