import assert from 'node:assert/strict';
import fs from 'node:fs';
import {inspectRootAvailability, createRootAvailabilityObserver} from './uiux-b2-root-availability.mjs';
const D = 'App\\Filament\\Pages\\Dashboard', L = 'Filament\\Auth\\Pages\\Login';
let checks = 0;
const equal = (actual, expected) => {assert.deepEqual(actual, expected); checks++;};
const node = ({id = true, name, raw} = {}) => ({
  id, snapshot: raw !== undefined || name !== undefined,
  hasAttribute: key => {assert.equal(key, 'wire:snapshot'); return raw !== undefined || name !== undefined;},
  getAttribute: key => {assert.equal(key, 'wire:snapshot'); return raw ?? JSON.stringify({memo: {name}, data: {private: 'DO_NOT_EXPORT'}});},
  get value() {throw Error('Private property read');},
});
function sample(nodes, {ready = 'complete', origin = 'http://127.0.0.1:8044', path = '/admin', unavailable = false, phase = 'AFTER_ADMIN_DESTINATION'} = {}) {
  globalThis.document = {readyState: ready, querySelectorAll: selector => {
    if (unavailable) throw Error('DO_NOT_EXPORT');
    assert(['[wire\\:id]', '[wire\\:snapshot]'].includes(selector));
    return nodes.filter(n => selector === '[wire\\:id]' ? n.id : n.snapshot);
  }};
  globalThis.location = {origin, pathname: path};
  try {return inspectRootAvailability({phase});} finally {delete globalThis.document; delete globalThis.location;}
}
const cases = [
  ['NO_ROOTS', [], {}, [0, 0, 0, 0, 0, 0, false]],
  ['ID_ONLY', [node()], {}, [1, 0, 1, 0, 0, 0, false]],
  ['DASHBOARD', [node({name: D})], {}, [1, 1, 0, 1, 0, 0, false]],
  ['LOGIN', [node({name: L})], {}, [1, 1, 0, 0, 1, 0, false]],
  ['OTHER', [node({name: 'PRIVATE_ARBITRARY_NAME'})], {}, [1, 1, 0, 0, 0, 1, false]],
  ['MIXED', [node({name: D}), node({name: 'OTHER'})], {}, [2, 2, 0, 1, 0, 1, false]],
  ['MALFORMED_JSON', [node({raw: '{private invalid'})], {}, [1, 1, 0, 0, 0, 0, true]],
  ['MISSING_NAME', [node({raw: '{"memo":{}}'})], {}, [1, 1, 0, 0, 0, 0, true]],
  ['NULL_METADATA', [node({raw: 'null'})], {}, [1, 1, 0, 0, 0, 0, true]],
  ['CAPPED', [...Array.from({length: 4}, () => node({name: D})), ...Array.from({length: 4}, () => node())], {}, [2, 2, 2, 2, 0, 0, false]],
  ['SNAPSHOT_ONLY', [node({id: false, name: D})], {}, [0, 1, 0, 1, 0, 0, false]],
];
for (const [name, nodes, options, expected] of cases) {
  const authentication = Object.freeze({authenticationConfirmed: false});
  const observed = sample(nodes, options);
  equal(['idRoots', 'snapshotRoots', 'idRootsWithoutSnapshot', 'dashboardSnapshots', 'loginSnapshots', 'otherSnapshots', 'malformed'].map(k => observed[k]), expected);
  equal(observed.observationAvailable, true);
  equal(authentication.authenticationConfirmed, false);
  equal(/DO_NOT_EXPORT|PRIVATE_ARBITRARY_NAME|private/.test(JSON.stringify(observed)), false);
}
for (const ready of ['loading', 'interactive', 'complete', 'invalid']) equal(sample([], {ready}).readyState, ready === 'invalid' ? 'unavailable' : ready);
equal(sample([], {path: '/admin/'}).destinationMatch, false);
equal(sample([], {origin: 'http://127.0.0.1:9999'}).destinationMatch, false);
equal(sample([], {path: '/admin'}).destinationMatch, true);
equal(sample([], {unavailable: true}).observationAvailable, false);
equal(sample([]).observationAvailable, true); // Observed zero differs from unavailable zero.
let calls = 0, fatal = false;
const observe = createRootAvailabilityObserver({page: {evaluate: async (collector, args) => {calls++; equal(collector, inspectRootAvailability); return sample([node({name: D})], {phase: args.phase});}}, hasFailed: () => fatal});
equal((await observe('AFTER_ADMIN_DESTINATION')).dashboardSnapshots, 1);
fatal = true;
const stopped = await observe('DASHBOARD_FAILURE');
equal(stopped.fatalEvent, true); equal(stopped.observationAvailable, false); equal(calls, 1);
await assert.rejects(observe('DASHBOARD_FAILURE'), /Invalid or repeated/); checks++;
await assert.rejects(observe('ARBITRARY_PRIVATE_PHASE'), /Invalid or repeated/); checks++;
const mid = createRootAvailabilityObserver({page: {evaluate: async (_, args) => {fatal = true; return sample([], {phase: args.phase});}}, hasFailed: () => fatal});
fatal = false; const later = await mid('AFTER_ADMIN_DESTINATION'); equal(later.observationAvailable, true); equal(later.fatalEvent, true);
const unavailable = createRootAvailabilityObserver({page: {evaluate: async () => {throw Error('DO_NOT_EXPORT');}}, hasFailed: () => false});
const missing = await unavailable('DASHBOARD_FAILURE'); equal(missing.observationAvailable, false); equal(missing.readyState, 'unavailable'); equal(JSON.stringify(missing).includes('DO_NOT_EXPORT'), false);
const invalid = createRootAvailabilityObserver({page: {evaluate: async () => ({...sample([]), arbitrary: 'DO_NOT_EXPORT'})}, hasFailed: () => false});
equal((await invalid('AFTER_ADMIN_DESTINATION')).observationAvailable, false);
const source = fs.readFileSync('scripts/uiux-b2-root-availability.mjs', 'utf8');
equal(/authenticationConfirmed\s*=|\.click\(|\.goto\(|setTimeout|waitFor|expect\.poll/.test(source), false);
equal(source.includes("getAttribute('wire:id')"), false);
console.log(JSON.stringify({status: 'PASS', checks, syntheticCases: cases.length, scope: 'pure collector/node fixtures and one-shot adapter checks; no browser or application'}));
