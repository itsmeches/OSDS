# Gate B2 — Isolated framework reproduction preparation

**28 October 2026. PASS: source-only preparation and offline mechanism tests. Browser/DOM validation NOT RUN; live causal attribution remains BLOCKED.** This reproduction demonstrates conditional behavior in installed algorithms with explicit dependency substitutions. It does not prove Attempt 05 followed the same path. No Attempt 06 or application runtime modification was prepared.

## Isolation and architecture

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reviewed the [observation-gap feasibility report](osds-ui-ux-gate-b2-observation-gap-feasibility.md) and its extracted-code fixtures, existing observation recorder, diagnostic checks and request policy. The previous reports and attempts remain historical evidence.

Dedicated private fixture directory, outside every attempt root:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/`

| New artifact | Responsibility |
| --- | --- |
| `reproduce.mjs` | Offline Node runner: extracts installed clauses, verifies exact private snapshots, builds isolated registry/clone/evaluator instances and asserts observed boundaries |
| Eight named `.txt` snapshots | Exact extracted AsyncAlpine, provider, expression compiler, error, clone, clone-mode, state-transfer and clone-predicate clauses |
| `password-reveal-copy.mjs` | Byte-identical copy of the installed published password component, imported locally by Node |
| `provenance.json` | Source paths, whole-source hashes, extraction offsets and extracted hashes |
| `offline-results.json` | Actual 35-check result with bounded observed traces for all scenarios |
| `dom-specimen.html` | Inert password-wrapper template: no scripts, form submission, populated values or network actions |
| `browser-plan.md` | DOM/native-import validation requirements and isolated follow-up plan; no runnable browser command |
| `existing-feasibility-checks.mjs` / its result | Prior 28-check suite copied with only its result destination relocated, so historical evidence is not rewritten |
| `preserve.cjs`, baseline and comparison | Focused pre-task preservation records and final comparison |

No OSDS credential, session, environment or route is consumed by the reproduction runner. The preservation helper hashes protected files without publishing their contents. The fixture imports neither Playwright nor Laravel and opens no database or HTTP connection. All executable fixture work runs in Node; no DOM engine was started.

The installed framework is Filament 5.10.1 / Livewire 4.4.7, with Alpine 3.17.4 embedded in the Livewire bundle. AsyncAlpine's actual installed bundled implementation is reused; an exact upstream release number is not inferred.

## Installed source reused and substitutions

| Installed source | Exact reused behavior | Explicit substitution / limit |
| --- | --- | --- |
| `vendor/filament/support/dist/index.js`, `function po(t)` | Loader directive, clone skip, strategy wait, download/export selection, registration, connected-element checks and initialization ordering | Strategy resolves deterministically. Its existing `asyncData` interface receives a controlled deferred downloader. This tests the installed await/export/registration path, **not native dynamic-import transport/cache semantics**. |
| `vendor/livewire/livewire/dist/livewire.js`, `datas`, `data`, `injectDataProviders` around 2768 | Actual closure-local provider assignment and injected provider getters | Independent fixture closures receive fixed synthetic instance identities A/B. No global Alpine replacement. |
| Same bundle, `generateFunctionFromString` around 1526 | Actual async expression compiler, `with(scope)` evaluation and finished/result semantics | Fixture directly supplies the exact injected provider scope and invokes the generated function. The complete evaluator/merge-proxy/reactivity path is not executed. |
| Same bundle, `skipDuringClone`, `cloneNode`, clone predicate and state-transfer callback around 1800 / 4848 | Actual clone-mode suppression, interceptor order, state transfer and decision to skip cloned data evaluation | Element operations and `initTree` are a minimal synchronous adapter; reactive side effects are a no-op. Direct clone is triggered explicitly. No full Livewire response or DOM morph is claimed. |
| Same bundle, `normalErrorHandler` around 1471 | Actual scheduled asynchronous throw | Fake timer queue and non-exporting mock console. Scheduled throws are delivered deterministically and converted only to fixed trace markers. No live error handler is replaced. |
| `public/js/filament/forms/components/text-input/password-reveal.js` | Actual module default export/factory, copied byte-for-byte | Imported locally in Node. Reveal methods requiring browser facilities are not invoked. |

Source and extraction hashes are recorded privately. Repeated preparation refuses changed snapshot contents rather than overwriting them; the result file is created exclusively. Installed/vendor/public files are never written. A duplicate local declaration in the initial harness assembly was corrected before the successful run; no framework or assertion was weakened.

## Deterministic observations

The fixture records only actual calls made by the extracted algorithms or their declared boundary adapters. It does not feed predetermined event lists into the analyzer to manufacture a result.

Closed trace fields are `sequence`, fixed `type`, synthetic `instance` (A/B), synthetic `wrapper` (W1/W2/C1/C2/NONE), and one boolean `state`. Event types cover import call/resolution/rejection, assignment, init entry/return, clone entry/transfer, evaluation entry/return, scheduled/delivered error and disconnection. Maximum **128 records per scenario recorder**; overflow throws. No raw errors, stack dumps, DOM, expression text, URL or arbitrary identifier is exported in traces.

Assignment is observed immediately after the extracted `data` function returns. Expression entry is observed immediately before invoking the installed generated function, with same-engine assignment state. Initialization entry/return surrounds the **mock DOM adapter**, so it is not misreported as full installed `initTree` completion. Import outcome markers come from the controlled promise, not HTTP responses. Instance identities describe the controlled closures, not recovered identities from Attempt 05.

## Scenario coverage and actual results

| Scenario / controls | Assertions on actual observed behavior |
| --- | --- |
| **A — pending provider, uninitialized clone** | The installed loader reaches await with no source stack. Installed clone logic invokes the adapter; clone expression entry precedes provider assignment and has `state=false`. The generated expression rejects, the normal handler schedules delivery, and the controlled import can register before that delayed delivery. B is the initialized-source negative control. |
| **B — initialized before clone** | After controlled import resolution, actual registration precedes clone. Source is loaded with state; clone transfer marker is true; the predicate prevents clone expression evaluation. No error timer exists. A supplies the contrasting failure path. |
| **C — import rejection** | Controlled download rejects; no provider assignment or initialized source stack appears and the wrapper remains awaiting. B's successful resolution is the paired positive control. Internal rejection is collected in the fixture's dependency adapter; this is not a newly discovered passive live hook. |
| **D — disconnection during import** | Disconnection occurs after invocation but before resolution. Provider assignment still completes, but original wrapper does not initialize and remains awaiting. B is the connected-wrapper control. This tests the loader's two distinct connectivity checks. |
| **E — separate registries** | A registers and its later expression succeeds. B's independently extracted registry remains unregistered and its expression schedules the only error. The closed instance markers distinguish this case from A's pending-registration evaluation. |
| **Duplicate wrappers** | Two wrappers awaiting the same controlled provider cause two download calls and two assignments in this fixture. Single-wrapper B is the control. No in-flight deduplication is assumed or imposed. |
| **Delayed delivery** | A records expression entry before assignment, then delayed error delivery after assignment. This shows why observer delivery order alone cannot prove an instance mismatch. It establishes fixture evaluation order, not historical browser evaluation time. |
| **Cleanup / output limits** | Callback recording is deactivated; later controlled evaluation adds no records. Known tasks settle and timer counts are checked. At record 129 the recorder throws. Cleanup is fixture callback/timer disposal, **not** certification of real DOM listeners, native imports or Alpine component destruction. |

The runner's primary assertions use event positions and actual state, including absence of calls for negative controls. Cloning is explicit through the installed `cloneNode` algorithm; no artificial sleep, fabricated network response or OSDS login is involved.

| Offline command / scope | Actual result |
| --- | --- |
| Private `reproduce.mjs` | **PASS: 35 checks**, A–E plus duplicate/cleanup/limit controls |
| Existing feasibility suite, output-relocated copy | **PASS: 28 checks** |
| `scripts/check-uiux-b2-initialization-observations.mjs` | **PASS: 44 checks** |
| `scripts/check-osds-diagnostics.mjs` | **PASS: 98 checks** |
| Existing private pure policy/selector suite | **PASS: 294 checks** |
| Node syntax / scoped whitespace | **PASS**, new fixture sources and report |
| Browser, DOM engine, HTTP server, PostgreSQL, Laravel | **NOT RUN** |

The 28-check source copy changes only the output destination, apart from file newline representation. Its original source and historical result remain unchanged. The new mechanism runner does not alter the existing 44/98/294 suites, recorder, sanitizer or request policy.

## Browser/DOM boundary

Full validation still requires native ES modules and rejection handling, actual DOM connection/clone semantics, MutationObserver/microtask ordering, Alpine directives/reactivity/`initTree`, and a complete morph path. The minimal adapter cannot certify these. The inert HTML specimen and private plan prepare the markup and design requirements; **a runnable browser adapter has not been implemented or executed**.

A later reviewed adapter should use only private copies and synthetic markup. A controlled module gate must distinguish dependency substitution from native import tests. Compare instrumented-copy results with uninstrumented-copy controls, since instrumentation can perturb scheduling. It must not initialize missing OSDS globals, modify real scripts or turn these conditions into a speculative application fix.

Prefer a fresh isolated browser context with a fixed synthetic intercepted origin and a closed in-memory resource map. Reject all OSDS/external requests. Review module-origin/CSP behavior before execution; a `file:` URL is not assumed equivalent to a served module. No OSDS environment, cookies, sessions, credential entry or application route belongs in that fixture. Any local server would need separate authorization and an isolated copy-only root.

Playwright's local runner would support deterministic controls and context disposal; Playwright MCP could operate a separately reviewed isolated fixture but is not necessary now. Chrome DevTools MCP might help later with a specific module-execution gap; it does not add value to these pure checks. No MCP permission was enabled and neither browser tool was called. Browser/DOM-engine execution and any server require separate authorization. No executable browser command is provided in this task.

## Preservation and conclusion

Fresh focused baseline: **4,130 files**, extending the preceding preservation scope with feasibility artifacts and report. It includes attempts 01–05, identity records, foundation credentials/ledger, runtime sources, historical reports/evidence, selected installed/public assets and Batch G artifacts. It is not a complete vendor/node_modules inventory. Final comparison is recorded below.

Final preservation: **4,130/4,130 unchanged**, zero changed or missing. All **81 current PDF-named artifacts**, including the original **73**, and all **ten historical diagnostic JSON files** remain byte-identical. Attempt 06 remains absent. The source-delta audit confirms that the reused 28-check suite differs only by its result destination and newline representation.

No database was accessed: database-record preservation is a no-access statement, not fresh row verification. The prior identity-log append exception and missing temporary fake-upload metadata remain documented history. No logs/caches were cleared, restored or truncated. New files are this report and the dedicated private reproduction/provenance/check artifacts; no public verification runner or application source changed.

**Source/offline reproduction readiness PASS; full DOM reproduction and historical attribution remain BLOCKED.** Recommend one separately authorized **source-only DOM-adapter preparation and review** using these private copies, followed by a distinct decision on isolated browser execution. This keeps native-import/full-morph verification separate from OSDS authentication or personnel publication. No protected runtime instrumentation is presently justified.

**STOP.** No browser, HTTP server, PostgreSQL, Laravel bootstrap, authentication, Attempt 06 reservation/preparation, versions 47/48 transition, directory 26 recreation, PDF, migration, B3/B4, commit, push or deployment occurred.
