# Gate B2 — Observation-gap feasibility

**28 October 2026. PASS for source-only feasibility investigation and offline checks. Complete passive live discrimination is NOT established; live readiness remains BLOCKED.** No runtime modification, live adapter or Attempt 06 preparation occurred. The password-reveal failure's live cause remains unresolved.

## Scope and evidence

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reviewed the [diagnostic design](osds-ui-ux-gate-b2-password-reveal-diagnostic-experiment-readiness.md), [installed-source investigation](osds-ui-ux-gate-b2-password-reveal-root-cause-investigation.md), Attempt 05 report/events and existing recorder/diagnostic/request-policy sources. Inspected installed Filament 5.10.1, Livewire 4.4.7 and its embedded Alpine 3.17.4. AsyncAlpine was tested from the installed Filament support bundle; no exact upstream release number is inferred.

Attempt 05's POST response, password asset response and page-error sequences remain **observer order**, not original expression-evaluation or server-commit time. No database state was rechecked. The last recorded directory 26 and personnel 47/48 draft/lock 1 observations remain historical.

## Observation-feasibility matrix

Classifications apply to the precise observation described, not to every fact about its framework subsystem. `REQUIRES_PROTECTED_INSTRUMENTATION` means exact live capture in the unmodified installed path has no verified passive hook; it is not permission to instrument it. `ISOLATED_REPRODUCTION_ONLY` means the current evidence can establish the boundary in a controlled fixture, without claiming the real login followed it.

| Observation / classification | Exact installed source and hook | Passive / timing | Proves, limits and modification requirement |
| --- | --- | --- | --- |
| Import start and promise settlement — **REQUIRES_PROTECTED_INSTRUMENTATION** for exact live path | `vendor/filament/support/dist/index.js`, bundled `function po(t)`: internal `S(O)` calls `a[O].download(O)`; `asyncUrl` supplies `import(B(N))`; `A(O)` awaits `S`. | No exposed passive lifecycle hook. Import invocation is synchronous; settlement/continuation asynchronous. | A network response proves neither namespace resolution nor promise success. `asyncData(name,callback)` could substitute an observed downloader in an isolated fixture, but changes the live provider path and is not passive. Capturing original promise boundaries would require reviewed interception/internal instrumentation, not an invented marker. |
| Exact provider-registration completion — **REQUIRES_PROTECTED_INSTRUMENTATION** for live path | Same bundle, `A(O)`: `t.data(O,N)` then `a[O].loaded=true`. Alpine `vendor/livewire/livewire/dist/livewire.js:2769` assigns `datas[name]=callback`. | Registration itself is synchronous; no registration-completion listener found. | An exact post-call observation proves assignment in that Alpine instance, not successful later evaluation or initialization. `Alpine.data(name)` is a setter, not a lookup. Wrapping it would alter a global function and was not implemented. |
| Wrapper completion at exact return boundary — **ISOLATED_REPRODUCTION_ONLY** | AsyncAlpine `x(O)` calls `destroyTree`, clears ignore, then `initTree`; caller assigns `_x_async='loaded'` after return. Alpine `initTree` at `:2026`; `interceptInit` at `:2022`. | `interceptInit` is additive and synchronous but **before directives**. `loaded` can be sampled passively afterward, not at a guaranteed completion callback. | A sampled `loaded`/state combination supports that the loader's synchronous init call returned. It does not prove every asynchronous descendant effect succeeded. Capturing exact return requires interception; no such hook was added. DOM mutation delivery is not completion. |
| Clone invocation and source state — **PASSIVE_OBSERVABLE** | Alpine `interceptClone` / `cloneNode`, `livewire.js:1808–1822`; data-stack transfer interceptor `:4848–4853`. | Additive callback; synchronous, before clone `initTree`. | Can inspect exact source/destination identity, source `_x_dataStack`, `_x_async` and destination marker without exporting them. Transfer visibility depends on callback order. Registering after built-in transfer exposes that marker; an earlier callback does not. Neither marker proves clone initialization completion. No patch needed in principle, but no live adapter or hook-registration timing proof was prepared. |
| Morph-to-clone correlation — **PASSIVE_OBSERVABLE** for matching observed pair; not universal coverage | Livewire `getMorphConfig` `:15947` onward emits `morph.updating` at `:15965`; morph `context.patch` invokes `window.Alpine.cloneNode` at `:15112`, after skip checks. | Additive hook plus clone interceptor; synchronous local identity comparison could correlate the same `el/toEl` pair. | A hook entry can be skipped/replaced and need not clone. An internally held WeakMap pair could correlate only matching callbacks; never correlate by temporal adjacency. Other clone paths and nested morphs need controls. Source order was checked, not a full morph engine executed. |
| Same Alpine reference over observations — **PASSIVE_OBSERVABLE**, limited claim | Source uses closure `t` in AsyncAlpine and `window.Alpine` in morph; Alpine provider registry `:2768–2783` is closure-local. | Comparing known references is passive and synchronous. | Can establish equality of references that are actually available. It cannot reveal the loader closure's original reference or read its private registry. Two isolated registries prove why successful registration on one does not establish availability on another. |
| Exact evaluator-instance/provider relation at failure — **UNRESOLVED** passively | Alpine provider injection `:2772`, `x-data` evaluation around `:4787`; normal error handler `:1471–1482`. | No verified additive callback spanning the private registry assignment and original failing expression evaluation. | A post-registration delivered error can have originated earlier. Replacing the evaluator/error handler would be behavior-changing instrumentation, not an approved passive hook. No live same-instance causal conclusion is supported. |
| Delayed page-error delivery — **PASSIVE_OBSERVABLE** as delivery only | `normalErrorHandler` schedules `setTimeout(() => { throw error2 },0)` at `:1480`; existing driver captures sanitized page error. | Asynchronous delivery; existing listener is passive and fail-fast. | Proves error delivery order, not original evaluation time. An isolated fake-timer fixture establishes that registration can occur between error scheduling and delivery. Recovering original evaluation time from delivery alone is impossible. |

Filament support plugin installation remains at `vendor/filament/support/resources/js/index.js:20–23`, its `alpine:init` callback. Seeing that browser event or receiving the support asset is not proof AsyncAlpine's installation completed. No global function replacement, vendor/public patch or request-filter expansion was made.

## Installed-implementation offline tests

New private fixture: `storage/app/private/uiux-consolidated-acceptance/gate-b2-observation-gap-feasibility/fixtures.mjs`. It extracts the installed AsyncAlpine plugin, Alpine clone/predicate/transfer clauses, data-provider registry functions and normal error handler. Dependencies are mocked: strategy, deferred module loader, element methods, initialization, timer and console. No browser, actual dynamic HTTP import, DOM engine or full Livewire morph engine runs.

The fixture's registration/import callbacks are **mock boundary observations**, not fabricated evidence of passive live hooks. Internal promise rejection is consumed by the mock solely to inspect its outcome safely; no live rejection handler or error suppression was added. Existing event schema is reused for overflow controls; no missing import or initialization events are manufactured to make the live recorder complete.

| Test | Actual finding |
| --- | --- |
| Clone with registration pending | Wrapper is awaiting/ignored with no initialized state; clone inline handler skips in clone mode; installed clone predicate permits evaluation without the transferred-state marker. A controlled factory expression fails when provider scope is absent. |
| Clone after initialization | Installed transfer callback supplies state/marker and installed predicate skips clone data registration. |
| Import rejection | Mock-observed internal promise rejects; provider never registers, initialization does not run, wrapper remains awaiting. This does not establish the real failure was an import rejection. |
| Registration completion | Mock call order is import-start → registration → destroy → init; loader reaches loaded after synchronous init returns. |
| Disconnection during import | **Provider can still register after disconnection**, because the first connectivity check precedes the awaited registration routine. The later check prevents wrapper initialization. State remains awaiting. Registration and connected-wrapper completion are distinct. |
| Two pending wrappers sharing provider | In this controlled simultaneous case the installed loader invokes the downloader twice and registers twice: it marks loaded after awaiting, rather than deduplicating an in-flight promise. This is an observed fixture behavior, not proof duplicate wrappers existed in Attempt 05. |
| Interceptor registration order | Executed installed `cloneNode` with observers before/after the installed transfer callback: earlier observer sees no marker, later observer sees transfer. FIFO order matters. |
| Different Alpine registries | Installed `data`/`injectDataProviders` functions instantiated in separate closures: provider exists in A's injected scope but not B's. No live duplicate Alpine instance is established. |
| Delayed error | Installed normal handler with fake timer schedules error; a registration marker can precede later delivery. No raw error/DOM is emitted. |
| Morph and init source contracts | Static checks establish morph skip-before-clone, hook-before-skip, and init-interceptor-before-directive ordering. These checks do not execute real morphing. |
| Overflow and cleanup | Existing recorder throws at 65th record, preserving 64. Mock callback deactivation stops further records. Installed `interceptClone` returns no disposer; deactivation is not removal. Live context disposal would be required to remove all installed observation callbacks, and was NOT tested. |

A private test-path error was corrected before fixture execution succeeded. An initial disconnection expectation also failed: source reinspection and the fixture showed registration can finish after disconnection. The corrected expectation reflects that measured behavior; no application assertion or evidence was rewritten. These were offline harness iterations, not live retries.

## Verification and preservation

| Check | Result |
| --- | --- |
| New installed-clause feasibility fixtures | **PASS: 28 checks** |
| Existing bounded recorder tests | **PASS: 44 checks** |
| Existing diagnostic tests | **PASS: 98 checks** |
| Existing pure policy/selector tests | **PASS: 294 checks** |
| Node syntax / scoped whitespace | **PASS**, new private fixture and report |
| PostgreSQL, Laravel bootstrap, HTTP server, browser, authentication | **NOT RUN** |
| Live readiness / causal root-cause confirmation | **BLOCKED / unresolved** |

Fresh focused baseline: **4,124 files**, extending the preceding scope with its recorder/tests/report and private design evidence. Preservation helper/results reside beside the fixture. This scope includes attempts 01–05, historical identity records, credentials/ownership ledger, application/runtime sources, published assets, reports and Batch G artifacts. It is not an exhaustive vendor/node_modules inventory. Final comparison is recorded below.

Final preservation: **4,124/4,124 unchanged**, zero changed or missing, including all **81 current PDF-named artifacts** (the original **73** included) and all **ten historical diagnostic JSON files**. Attempt 06 remains absent.

No database record was queried or written. Configuration preservation is a no-access/source-preservation statement in this task, not fresh verification of live row contents. Historical identity-log append and missing temporary fake-upload metadata remain prior exceptions; no log/cache was cleared, truncated or restored. New files are this report and private baseline/test/result artifacts only. Existing recorder, driver, policy, bootstrap and framework files are unchanged.

## Decision and one next authorization

**Complete causal discrimination through the verified passive hooks is not currently possible.** Clone/source state and matched morph observations are feasible; exact original import settlement, provider assignment and evaluation-instance correlation remain gaps. The current fixed recorder cannot represent independent instance/phase origins or prove causal identity across delayed delivery. No claim of live readiness follows from the passing fixtures.

Recommend **one separately authorized source-only isolated framework reproduction preparation**, before considering protected instrumentation: construct a minimal fake-form reproduction using copies of the installed bundles in a private disposable fixture, with deterministic deferred import and a controlled morph trigger. First offline-test any boundary probes and instance correlation in that isolated copy. Keep production/vendor/public assets unchanged, use no credentials or OSDS routes/database, and seek separate authorization before any browser execution. That next step should establish a reproducible framework mechanism, not prepare Attempt 06 or personnel publication.

**STOP.** No live experiment, Attempt 06 reservation/preparation, personnel action, directory recreation, runtime modification, migration, PDF, B3/B4, commit, push or deployment occurred.
