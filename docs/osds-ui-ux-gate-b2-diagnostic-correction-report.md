# Gate B2 — Source-only diagnostic correction

**28 October 2026. PASS for the authorized verification-tool correction and offline tests. Live acceptance remains BLOCKED pending separate authorization.** The original JavaScript cause is still **UNKNOWN**. No attempt-05 path was introduced, reserved or executed.

## Contract and scope

The [ReferenceError investigation](osds-ui-ux-gate-b2-referenceerror-investigation.md) established an observability gap: the sanitizer masked known public password-component symbols, collapsed asset families and lacked shared ordering. Those were source-confirmed diagnostic limitations, not proof of an application bug.

Changed only existing verification helper `scripts/uiux-b2-error-diagnostics.mjs` and reviewer driver `scripts/verify-uiux-b2-reviewer-only.mjs`. Added pure test runner `scripts/check-osds-diagnostics.mjs`. The accompanying skill refinements and MCP inventory are documented in the [integration report](osds-mcp-skills-integration-report.md).

No authentication, Livewire, Filament asset, lifecycle service, request policy, database guard, launcher or inspector behavior was changed. Existing version IDs, states/locks, action arguments, same-origin checks, one-shot policy and fail-fast rejection remain intact.

## Closed public error categories

Only exact **ReferenceError** messages for these public-source identifiers receive fixed categories:

| Public identifier | Fixed category prefix / source basis |
| --- | --- |
| `passwordRevealFormComponent` | `PASSWORD_REVEAL_FACTORY`; Filament TextInput factory expression |
| `isPasswordRevealed` | `PASSWORD_REVEAL_STATE`; TextInput expression and published factory state |
| `Alpine` | `ALPINE_GLOBAL`; installed framework/plugin initialization |
| `Livewire` | `LIVEWIRE_GLOBAL`; installed frontend integration |

For each, the only recognized templates are exact `identifier is not defined` and `Cannot access 'identifier' before initialization`, mapped to `_UNDEFINED` or `_BEFORE_INITIALIZATION`. No substring, arbitrary identifier or interpolated observed text is exported. Category output is drawn from the fixed dictionary; the message field remains restricted for these mappings.

Known credential values are checked **before** category assignment. Extra text, different casing, unknown symbols, token/header-like strings and oversized messages retain restricted fallback. A different exception name does not become a ReferenceError by message resemblance. Existing generic safe normalizations remain available; no raw message logging was added.

This does not reconstruct attempt-04's discarded message or assert that any new category actually occurred live.

## Closed asset families

Same-origin, query-free public path matching yields only:

- `livewire-core`: the installed hashed Livewire JS route pattern.
- `filament-support`: published support bundle.
- `filament-panel`: published panel app bundle.
- `filament-tables`: published table bundle.
- `filament-password-reveal`: published password-reveal module.
- `unknown`: every other path/origin, URL-credential case or invalid/oversized value.

Evidence contains enum values and bounded numeric line/column locations, not original asset filenames, full URLs, query strings, functions or raw stack frames. Exact public-family classification can handle a public password-reveal path without treating its public filename as an exported credential. Known-secret matches still force restricted frames. Unrecognized sensitive locations stay restricted.

Message input limit remains **2,048 characters**; stack input limit **8,192**; at most **eight** sanitized frames. Unknown names and unexpected getters fall back safely. No unrestricted console listener, browser storage inspection, raw body recording or pre-login tracing was added.

## Monotonic observation ordering

The pure `createEventRecorder()` supplies one shared sequence for response callbacks, fixed browser-operation markers and the first page-error capture. It uses the Node monotonic performance clock, clamps backward readings to the last elapsed value and increments sequence numbers strictly.

Fixed markers surround:

1. Login navigation and control visibility.
2. Exact login-component inspection.
3. Email/password fills, without field values.
4. Sign-in click.
5. Policy-approved POST forwarding and HTTP responses.
6. Authenticated URL and authorized-navigation checks.
7. Confirmed authentication or workflow failure.

The recorder rejects arbitrary marker names and copies only selected safe phase/path/method/status fields. Default event storage is capped at **256 entries**, with a dropped-event count. Response history retains its **80-record** cap. If the overall cap is reached, the first-error diagnostic still retains its own returned sequence/time metadata even if that event is outside the stored array.

The first page error is captured once before fail-fast rejection. It retains login displayed/POST-attempted/authentication-confirmed booleans and now links to the shared sequence. The terminal workflow error also receives a safe sequence-linked diagnostic.

These are **driver observation times and await boundaries**, not proof of browser expression-creation time or server commit time. An asynchronously rethrown Alpine exception can originate earlier; the new ordering must not be overinterpreted.

## Preserved workflow

The driver still points at already-used **attempt-04**, so its existing collision/start checks prevent replay. No attempt-05 preparation occurred. Session provisioning and historical identity-log protection are unchanged because their launcher/bootstrap were not edited.

`scripts/uiux-b2-reviewer-policy.mjs` remains byte-identical. No new POST, component, `__dispatch`, `__lazyLoad`, version, lock, creation, generation or withdrawal action is admitted. The original fail-fast race and listener remain; only their safe evidence and markers changed. No credentials, tokens, cookies, raw bodies, storage or arbitrary exceptions are written into the new diagnostic structure.

## Actual offline checks

| Check | Result |
| --- | --- |
| `node scripts/check-osds-diagnostics.mjs` | **PASS: 98 checks** — exact mappings, known-secret precedence, malformed/extended messages, asset-family bounds, privacy, stack cap, clock rollback, sequence/cap behavior, markers and negative policy controls |
| Existing pure policy/selector suite | **PASS: 294 checks**, no historical result file overwritten |
| Integration/skill/config checks | **PASS: 56 checks**, including unchanged request-policy hash and no attempt-05 directory |
| JavaScript syntax | **PASS**, both changed files and the new test runner |
| Scoped formatting / whitespace | **PASS**, relevant JS whitespace and inspected PHP Pint checks; unrelated pre-existing documentation issue untouched |
| Database / Laravel / server / browser / authentication | **NOT RUN** |

The new test runner imports only pure helpers and reads source. It uses synthetic strings and controlled clock values, not live account/session contents. No new initialization probe was injected into the browser.

## Preservation, limits and recommendation

The common task baseline covers **3,025 existing files**. Final comparison: **3,017 unchanged**, eight authorized modifications (the two verification files and six existing skill files), zero missing. All historical attempts and identity logs, original PDFs, historical JSON, credentials/ledger, protected runtime and Batch G remain unchanged. New test/report/private artifacts are additions; logs/caches were neither cleared nor restored. The known missing temporary fake-upload metadata remains the prior exception.

The diagnostic gap is corrected **in source**, with offline privacy/order tests passing. Actual exception identity, current credentials/configuration and live timing remain unknown. No application fix or complete B2 acceptance is claimed.

**Recommendation:** separately authorized Attempt-05 preparation is justified because the next observation could distinguish exact public error families, asset families and observed operation order. Preparation should review the new evidence format and fixed non-overwriting paths before any separate execution approval. Neither preparation nor execution is included here.

**STOP. No attempt 05, version 47/48 mutation, directory-26 recreation, PostgreSQL, Laravel bootstrap, server, browser, login, PDF, migration, B3/B4, commit, push or deployment occurred.**
