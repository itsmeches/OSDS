# Gate B2 — Isolated browser/DOM adapter preparation

**28 October 2026. PASS for source-only preparation and offline checks. Browser execution NOT RUN; DOM/native-module behavior remains unverified.** No OSDS attempt, application runtime change, server or database access occurred. This adapter is an isolated framework experiment, not Attempt 06. B3/B4 remain closed.

## Architecture and source scope

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Used the [isolated reproduction](osds-ui-ux-gate-b2-isolated-framework-reproduction-readiness.md) and preceding feasibility evidence. All new adapter artifacts reside inside the existing private directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/`

| Prepared file | Purpose |
| --- | --- |
| `browser-fixture.html` | Synthetic blank password field and replacement template, no form, credentials, `wire:id`, CSRF token or application endpoint |
| `browser-adapter.js` | Private page observer, fixed wrapper identity, actual-state sampling, explicit disconnection/morph trigger, bounded markers and observer cleanup |
| `browser-policy.mjs` | Pure fixed-origin/resource policy and exact-once private instrumentation transformations |
| `prepare-browser.mjs` | Source-only asset copying and provenance generation; no browser import |
| Five `browser-*.js` asset copies | Full Livewire/support comparison and instrumented copies, plus password-reveal module |
| `browser-provenance.json` | Installed source hashes, generated copy hashes, fixture-input hashes and transformation descriptions |
| `pin-browser.mjs` / `browser-entrypoints.json` | Source-only entry-point hash recording; future runner checks these before reserving evidence |
| `run-isolated-browser.mjs` | Fixed future one-shot runner with in-memory resource fulfillment, native module gate, scenario controls and context cleanup |
| `check-browser-preparation*.mjs` / results | Pure policy/provenance, mock-observer, promise-gate and static runner-contract checks; final run uses a distinct output |
| `browser-repeat-*.mjs`, `browser-offline-repeat/` | Existing offline suites rerun with relocated output, preserving earlier results |
| `browser-preservation.cjs` and records | Fresh pre-stage baseline and final comparison |

No vendor/public/app file, reviewer script, launcher, authentication, permission or database configuration was edited. The earlier inert specimen, plan, extracted snapshots and results remain unchanged. The new browser fixture is distinct from that earlier inert specimen.

## Installed assets and initialization

The full copied implementations come from installed Filament 5.10.1 and Livewire 4.4.7, with Alpine 3.17.4 embedded in Livewire. The exact AsyncAlpine implementation is the installed support bundle; no unverified upstream version is assigned.

| Source | SHA-256 | Copy behavior |
| --- | --- | --- |
| `vendor/livewire/livewire/dist/livewire.js` | `fa03bcdfae501c8ef5c93e525ce0f5a658929accd98758651203c1e242dab3d3` | Both copies expose the internal `morph2` through the same private bridge; instrumented copy additionally marks the exact password-factory evaluation entry |
| `vendor/filament/support/dist/index.js` | `31c6fb14c605144dbce0235ee32879d27ac78ddb52a4e5ddc74b12ada548acb3` | Comparison copy is byte-identical; instrumented copy marks download invocation/settlement, registration return and loader initialization return |
| Published password-reveal component | `3ec263cf243077557155d8ceddc0e1ec3b7073419cb441ae79ea37e5506c8cc0` | Byte-identical standalone native ES module copy |

Generated hashes and exact transformation contracts are private in `browser-provenance.json`. Offline tests regenerate each expected output in memory and compare complete bytes. Transformations require exactly one source occurrence and refuse a changed contract.

Classic scripts in the synthetic fixture load the adapter, support and Livewire copies. The support copy registers AsyncAlpine through its actual `alpine:init` listener; Livewire's installed DOMContentLoaded path calls its real `start()`, including real Alpine/plugin startup. No missing factory global, replacement provider, synthetic login or application script-order fix is introduced.

The private bridge calls installed **Livewire `morph2`**, which uses its real morph configuration and Alpine morph integration. It supplies a small synthetic in-memory component context: element, empty effects and fixed synthetic snapshot metadata. This is **not server hydration, a real PHP component or a Livewire request/response test**. Whether this facade satisfies all exercised framework paths remains unverified until browser execution. Any incompatible assumption fails closed; no backend fallback exists.

## Native-module control and resource isolation

Fixed origin: **`https://password-repro.invalid`**. No listener, DNS probe, HTTP server or OSDS endpoint is required by the proposed runner. Every admitted resource is fulfilled by Playwright routing from fixed private bytes:

| Resource | Content |
| --- | --- |
| `/fixture.html` | Synthetic fixture with closed comparison/instrumented mode substitution |
| `/adapter.js` | Private page adapter |
| `/support.js` | Selected private support copy |
| `/livewire.js` | Selected private Livewire copy |
| `/password-reveal.js` | Held native dynamic-import response; released once as the copied module or an intentional 404 rejection |

Only exact-origin **GET** requests on that list are allowed. Credentials in URLs, queries, fragments, unexpected ports, encoded/unlisted paths, POSTs, OSDS and external origins are rejected. Responses use no-store headers and a restrictive CSP; `unsafe-eval` is allowed solely for the actual Alpine evaluator. Connections and form submission are forbidden. A gate request is accepted once per context; duplicate module requests fail rather than being replayed.

Fresh contexts block service workers and downloads. A WebSocket routing rule closes unexpected sockets without connecting a server; missing support for that isolation API blocks execution. Unexpected resources, popups, HTTP errors, page errors or observation overflow fail through the shared failure promise. Contexts and the task-owned browser are closed in `finally`. No existing user browser/profile, cookies, sessions, storage or credentials are imported.

The runner holds the **real native module response** until a controlled scenario boundary. This differs from the prior pure fixture's substituted downloader. An HTTP response still is not registration evidence: instrumented markers are emitted after the installed loader's promise continuation and after the real `Alpine.data` assignment call returns. No timing inference is made from HTTP ordering alone.

## Diagnostics and controls

Private browser records use fixed event names: `IMPORT_START`, `IMPORT_OK`, `IMPORT_FAIL`, `ASSIGNED`, `INITIALIZED`, `EVAL_ENTER`, `CLONE`, `MORPH`, `MUTATION`. Each event contains a local monotonic sequence, assignment-observed boolean and same-instance boolean. Clone records additionally contain source-initialized, awaiting and transferred-state booleans. Maximum **128 events**; overflow fails.

State export is limited to wrapper count (zero/one; duplicates fail), loaded/initialized/connected booleans and those bounded events. Raw DOM, selectors, source expressions, URL strings, request bodies, form values, cookies, console logs and stacks are not captured. Node retains at most eight closed expected error categories per scenario; unexpected errors retain only a closed error-name enum and restricted/public-state category before immediate failure. There are no screenshots or traces.

Instrumented evaluation markers execute synchronously before the installed evaluator call; registration markers follow the assignment call. This supports same-fixture ordering without confusing later page-error delivery with original evaluation time. `MutationObserver` records are delivery observations, not initialization completion. Node page-error delivery and browser marker sequences are not combined into a fabricated cross-process clock. No server-commit time exists in this fixture.

| Prepared scenario | Control / intended observation |
| --- | --- |
| A — pending import then morph | Confirm one connected uninitialized wrapper while native module is held; invoke copied Livewire morph once; release module; inspect clone/evaluation markers and factory error category |
| B — initialized before morph | Release module, require loaded state, then morph once; require initialized result with no unexpected error |
| C — native import rejection | Release held module as 404; require the expected native import-rejection category; instrumented run must also reach import-failure marker, without loaded state |
| D — disconnect during import | Remove original wrapper while import is held; release module; instrumented run observes assignment without connected original wrapper |
| N — success with no morph | Release module and require initialized/error-free state; contrasts A and B |

Each scenario runs once in a fresh comparison context and once in a fresh instrumented context: **ten planned contexts**, no automatic retry. Comparison copies contain the shared morph-access bridge; “comparison” does not mean the full Livewire file is byte-identical. The support comparison copy is unchanged. The runner compares bounded outcome/state summaries and reports `INSTRUMENTATION_DIFFERENCE` if they differ, not a manufactured PASS.

The candidate mechanism is marked observed only if both A runs report the factory category and the instrumented run records an awaiting/uninitialized clone and evaluation before assignment. Otherwise it reports `NOT_ESTABLISHED_AT_CAPTURE`; absence at capture is not proof no later error could occur. The runner does not prove that errors and markers belong to the same exact underlying evaluator call in every possible case; the single-wrapper experiment bounds that ambiguity.

Only explicitly expected synthetic factory/import errors are admitted as scenario observations; they are not suppressed (`preventDefault`/global error-handler replacement are absent). An additional state error, unknown message/category or incompatible native rejection text stops for review. No request permission is expanded to continue.

## Offline verification

No Playwright runtime, browser MCP, DOM engine or application bootstrap was invoked. Checks used portable Node, file hashes, a mocked adapter environment and scoped source checks.

| Check | Actual result |
| --- | --- |
| New preparation checks | **PASS: 63 checks**, resource denial controls, provenance/output equality, mock observer/cleanup, controlled promise gate and runner contracts |
| Existing extracted-framework reproduction | **PASS: 35 checks**, output-relocated copy |
| Existing feasibility suite | **PASS: 28 checks**, output-relocated copy |
| Existing observation-recorder suite | **PASS: 44 checks** |
| Existing diagnostic suite | **PASS: 98 checks** |
| Existing request-policy/selector suite | **PASS: 294 checks** |
| JavaScript syntax | **PASS** for runner, preparation/policy/adapter/check files and all five prepared asset copies |
| Scoped whitespace / source format | **PASS** for new handwritten source and report; generated framework bytes retain source formatting |
| Browser output reservation / browser execution / server | **NOT RUN** |

The 63 checks test the adapter in a mock document, not actual DOM scheduling or native imports. The prior 35/28 suites were copied with only their output locations adapted; original results were not rewritten. No assertion was weakened to pass a browser scenario. The two offline preparation-check result files preserve their respective runs; neither is live browser evidence.

## Preservation

Fresh focused baseline: **4,151 files**, including the preceding reproduction artifacts/report, attempts 01–05, historical logs/identity records, foundation credentials/ledger, runtime/configuration sources, selected installed/public assets, historical reports and Batch G. This is not a complete vendor/node_modules inventory.

Final comparison: **4,151/4,151 unchanged**, zero changed/missing. All **81 current PDF-named artifacts**, including the original **73**, and all **ten historical diagnostic JSON files** remain byte-identical. Earlier identity-log append history and the known missing temporary fake-upload metadata remain documented exceptions. Logs/caches were not cleared, restored or truncated.

New artifacts are this report and private adapter/copy/provenance/offline-check files inside the existing reproduction directory. Neither `isolated-browser-execution-01/` nor Gate B2 `attempt-06/` exists. Database records were not queried or written; no fresh row-integrity assertion is made.

## Requirements, limitations and proposed command

Source/offline preparation **PASS**; actual DOM, native ES-module behavior and end-to-end framework compatibility are **NOT RUN**. A later isolated execution needs the installed Playwright Node package plus a locally available compatible Chromium executable. Missing browser dependencies must block; no automatic download is authorized. No extra MCP permission is needed. Playwright MCP or Chrome DevTools MCP could inspect a separately authorized isolated fixture but are unnecessary for this fixed runner and were not used.

Separate Alpine registries and duplicate wrappers remain covered by the existing offline suites, not additional native-browser cases in this runner. Disconnected comparison mode observes module resource completion and detached-wrapper state, **not** uninstrumented internal assignment completion. Instrumentation can perturb microtask scheduling even when outcome summaries match; matching outputs cannot prove zero timing effect. A normal-error delivery beyond final capture also remains possible. None of these results can establish Attempt 05's historical causal path.

Recommend **one separately authorized execution of this isolated fixture only**, preserving its output and stopping on any unexpected resource, initialization failure or instrumentation difference. This would authorize no OSDS navigation, login, database, personnel action or Attempt 06. The fixed runner atomically creates a new, non-overwriting `isolated-browser-execution-01/` under the private reproduction root only when invoked; a collision blocks execution.

Proposed command — **NOT EXECUTED**:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/run-isolated-browser.mjs run
```

A completed observation run is not automatically a mechanism PASS. The runner separately records conditional-mechanism evidence, instrumentation differences and blocked assumptions. No reload/retry/fallback path exists.

**STOP.** No browser/server, Laravel, PostgreSQL, authentication, Attempt 06 preparation/reservation, personnel transition, directory recreation, PDF, migration, B3/B4, commit, push or deployment occurred.
