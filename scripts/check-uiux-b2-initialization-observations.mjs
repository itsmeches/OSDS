import assert from 'node:assert/strict';
import { createInitializationObservations } from './uiux-b2-initialization-observations.mjs';
let checks = 0;
function check(value) { assert(value); checks++; }
function scenario(steps) {
  const run = () => { const o = createInitializationObservations(); for (const [type, fields] of steps) o.record(type, fields); return o; };
  const o = run(); assert.deepEqual(o.snapshot(), run().snapshot()); checks++;
  check(o.snapshot().every((e, i) => e.sequence === i + 1));
  check(o.assess().causalConclusion === 'UNRESOLVED'); return o;
}
const start = [['LOADER_OBSERVED', { installed: true }], ['WRAPPER_OBSERVED', { count: 1, state: 'await', initialized: false }], ['IMPORT_STARTED', {}]];
const registration = [['IMPORT_SETTLED', { outcome: 'success' }], ['PROVIDER_REGISTERED', {}], ['WRAPPER_INITIALIZED', {}]];
const error = ['FIRST_PAGE_ERROR', { category: 'PASSWORD_REVEAL_FACTORY_UNDEFINED' }];
const a = scenario([...start, ['MORPH_STARTED', {}], ['CLONE_OBSERVED', { sourceInitialized: false, awaiting: true, transferredState: false }], error]);
check(a.assess().pendingCloneObserved && !a.assess().initializedCloneObserved);
const b = scenario([...start, ...registration, ['MORPH_STARTED', {}], ['CLONE_OBSERVED', { sourceInitialized: true, awaiting: false, transferredState: true }]]);
check(b.assess().initializedCloneObserved && !b.assess().pendingCloneObserved);
const c = scenario([...start, ['IMPORT_SETTLED', { outcome: 'failure' }], error]);
check(c.assess().importFailureWithoutRegistration && !c.assess().undefinedObservedAfterRegistration);
const d = scenario([...start, ...registration]); check(d.assess().registeredWithoutObservedClone);
const e = scenario([...start, ...registration, error]); check(e.assess().undefinedObservedAfterRegistration && !e.assess().pendingCloneObserved);
const incomplete = scenario([error]); check(Object.entries(incomplete.assess()).filter(([k]) => k !== 'causalConclusion').every(([,v]) => !v));
for (const [type, fields] of [['secret-value', {}], ['IMPORT_STARTED', { url: 'secret' }], ['WRAPPER_OBSERVED', { count: 3, state: 'await', initialized: false }], ['FIRST_PAGE_ERROR', { category: 'arbitrary-identifier' }], ['LOADER_OBSERVED', { installed: 'secret' }], ['IMPORT_SETTLED', { outcome: 'secret' }], ['IMPORT_STARTED', null]]) {
  const o = createInitializationObservations(); assert.throws(() => o.record(type, fields)); checks++; check(o.snapshot().length === 0);
}
const first = createInitializationObservations(); first.record(...error); check(first.record('FIRST_PAGE_ERROR', { category: 'RESTRICTED' }) === false); check(first.snapshot().length === 1);
const limit = createInitializationObservations(); for (let i = 0; i < 64; i++) limit.record('MORPH_STARTED'); assert.throws(() => limit.record('MORPH_STARTED')); checks++; check(limit.snapshot().length === 64);
const copy = d.snapshot(); copy[0].installed = 'secret'; check(d.snapshot()[0].installed === true);
check(JSON.stringify([a,b,c,d,e].map(o => o.snapshot())).length < 10000);
console.log(JSON.stringify({ status: 'PASS', checks, scenarios: ['A','B','C','D','E'], scope: 'synthetic observation traces; no live adapter or causal proof' }));
