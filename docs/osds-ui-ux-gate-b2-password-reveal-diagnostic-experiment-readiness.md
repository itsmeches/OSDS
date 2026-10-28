# Gate B2 — Initialization/morph diagnostic design readiness

**28 October 2026. PASS: source-only schema preparation and offline tests. BLOCKED: complete live instrumentation readiness.** The five synthetic cases are distinguishable when the required observations are supplied. Several observations cannot currently be obtained through passive installed framework hooks. No live adapter, Attempt 06 path, reservation or execution was prepared. The cause of Attempt 05 remains unconfirmed; B3/B4 remain closed.

## Evidence and scope

Applied [OSDS Verification Gates](../.agents/skills/osds-verification-gates/SKILL.md) and [Livewire Browser Diagnostics](../.agents/skills/osds-livewire-diagnostics/SKILL.md). Reviewed the [source investigation](osds-ui-ux-gate-b2-password-reveal-root-cause-investigation.md), [Attempt 05 report](osds-ui-ux-gate-b2-reviewer-only-attempt-05-report.md), its retained safe events, existing diagnostic helper, reviewer driver and request policy. Inspected installed Alpine initialization/clone and Filament support plugin code again.

Attempt 05's sequence 25 forwarded the permitted login POST, 26 observed POST HTTP 200, 27 observed password-reveal asset HTTP 200, and 28 captured the first sanitized factory ReferenceError. These positions order recorder observations. They do not establish original expression-evaluation time, import completion, accepted authentication or server commit time. The historical read-only postflight supplies the last verified draft/lock 1 states for 47/48; no fresh state verification occurred here.

New source files:

- `scripts/uiux-b2-initialization-observations.mjs`: pure bounded event schema and conservative observation analysis. No browser/global hooks, filesystem/network access, attempt path or credentials.
- `scripts/check-uiux-b2-initialization-observations.mjs`: synthetic A–E traces, privacy/limit controls and deterministic ordering checks. It imports only the pure module and Node assertions.

Existing driver, sanitizer, policy, launcher, bootstrap, vendor files and published assets remain unchanged. This is a separate offline diagnostic core, not a runnable browser experiment. No global Alpine/Livewire function was wrapped or replaced.

## Observation locations and feasibility

| Requested observation | Minimal proposed location / safe projection | Actual limitation and disposition |
| --- | --- | --- |
| Loader installation | Filament support's `alpine:init` callback installs AsyncAlpine. A future reviewed adapter could emit `LOADER_OBSERVED` only on evidence of installation. | `alpine:init` alone does not prove that particular plugin ran. No stable passive plugin-install completion event was found; do not substitute asset delivery or `window.Alpine` presence. **Not instrumented.** |
| Wrapper discovery | A future adapter must use one fixed public password-component identity, internally match its exact known `x-data`, and emit count/state/initialized booleans without exporting attributes or selectors. | Zero or duplicate wrappers must stop attribution. Count bins are 0, 1, 2 (2 means multiple). Matching expression text stays inside a future adapter. No DOM reader was added here. |
| Import start/outcome | The installed bundled AsyncAlpine loader's internal download/await boundary; output only start and success/failure. | No passive settlement hook is exposed. A network start/200 is **not** this marker. Instrumenting that internal boundary would require separately reviewed interception or an isolated reproduction; no patch implemented. |
| Provider registration | Immediately after AsyncAlpine's successful `Alpine.data(name,factory)` return, for the exact public provider and intended Alpine instance. | No passive registration-completion hook was established. Calling `Alpine.data(name)` to inspect it can change registration. Do not do so. **Not instrumented.** |
| Wrapper initialization | Observe only state enum, presence of initialized Alpine state, and ignore-state consistency after the loader's initialization completes. | Installed `interceptInit` runs **before directives**, not at completion. A callback there cannot truthfully emit completion. DOM mutation delivery can also lag. No completion hook fabricated. |
| Morph start | A narrowly filtered `Livewire.hook('morph.updating', ...)` on the exact wrapper/component can signal entry to element updating. | It is an element-level hook, not proof that a clone follows, and must be correlated with exact-wrapper identity. Proposed only. |
| Clone invocation/source state | Installed `Alpine.interceptClone(from,to)` can observe a targeted invocation and booleans for source `_x_dataStack`, source `_x_async==='await'`, and transferred-state marker. | This callback executes before clone initialization. Its registration order relative to Alpine state-transfer interceptors must be verified. A transfer marker observed early is not proof of final transfer; no live hook added. |
| First sanitized page error | Reuse existing first-error sanitizer/category handling and fail-fast path. | Delivery can be delayed by Alpine rethrow. Do not mark this event as expression execution. No console or raw-stack listener is needed. |

The installed clone interceptor and Livewire hook are additive observation APIs, not a reason to monkey-patch global functions. Their existence does not solve import/registration visibility. Reliable full discrimination currently needs more than these passive observations. This task stops short of adding interception to protected runtime components.

## Fixed event schema

The pure recorder accepts only the following exact event names and fields. Every record adds a strictly increasing local integer `sequence`, starting at 1. There are no timestamps, URLs, selectors, expression strings, raw errors, arbitrary identifiers or extra fields. Fields are copied into newly constructed records. Snapshots are detached copies.

| Event | Allowed fields |
| --- | --- |
| `LOADER_OBSERVED` | `installed`: boolean |
| `WRAPPER_OBSERVED` | `count`: integer 0–2; `state`: `init`, `await`, `loaded`, `unknown`; `initialized`: boolean |
| `IMPORT_STARTED` | None |
| `IMPORT_SETTLED` | `outcome`: `success` or `failure` |
| `PROVIDER_REGISTERED` | None |
| `WRAPPER_INITIALIZED` | None |
| `MORPH_STARTED` | None |
| `CLONE_OBSERVED` | `sourceInitialized`, `awaiting`, `transferredState`: booleans |
| `FIRST_PAGE_ERROR` | `category`: `PASSWORD_REVEAL_FACTORY_UNDEFINED` or `RESTRICTED` |

Limits: **64 records**; overflow throws a fixed error rather than silently losing discriminating observations. Subsequent page errors are ignored after the first valid page-error record. Invalid events, extra fields, invalid enum values and non-boolean state flags fail closed with fixed messages. No input payload is included in those messages. Existing secret-aware sanitization must run before any future error category reaches this recorder; this module does not accept raw exceptions.

These markers describe one exact wrapper/provider observation stream. They do not support attribution across multiple wrappers, navigations, Alpine instances or repeated imports. A future adapter must fail on ambiguous identity or start a separately defined observation scope; it must not splice unrelated events into one sequence. The recorder validates representation, not the truth or completeness of its producer's observations.

The analyzer emits five booleans describing supplied observations and always returns `causalConclusion: UNRESOLVED`. It does not interpret absence of an event as proof that the operation never occurred. `registeredWithoutObservedClone` deliberately says **observed**; an uninstrumented clone cannot be ruled out.

## Offline discrimination and results

| Scenario | Synthetic observations / result | What this establishes |
| --- | --- | --- |
| A — clone before registration | Awaiting wrapper, morph, clone with no source initialization/transfer, factory error | Pending-clone finding true, initialized-clone finding false. Compatible with the proposed race, not proof of the live cause. |
| B — clone after initialization | Import success, registration, initialization, clone with source state and transfer | Initialized-clone finding true, pending-clone finding false. |
| C — import failure | Import failure, no registration marker, factory error | Import-failure-without-registration finding true. Cannot infer failure solely from missing registration. |
| D — success without cloning | Import success, registration and initialization; no clone observation | Successful registration without an observed clone distinguished from A–C. Requires complete clone coverage to exclude cloning. |
| E — undefined after registration | Registration/initialization followed by factory-error delivery | Distinguishes an **observed** post-registration error. Instance/scope mismatch is a candidate; a delayed pre-registration error remains possible. This trace does not prove E's cause. |

**Important limit:** bounded delivery-order markers alone cannot conclusively distinguish an actual instance/scope mismatch from a delayed error originally evaluated before registration. Direct same-instance expression-entry evidence would be required for that conclusion. The pure fixtures prove schema/analysis distinctions, not that a passive live harness can obtain every marker or identify all causal alternatives.

Commands used only portable Node, pure local files and scoped Git checks:

| Check | Actual result |
| --- | --- |
| `node scripts/check-uiux-b2-initialization-observations.mjs` | **PASS: 44 checks**, A–E, incomplete evidence, deterministic sequences, invalid/extra fields, bounded output, first-error retention and detached snapshots |
| `node scripts/check-osds-diagnostics.mjs` | **PASS: 98 checks** |
| Existing private pure request-policy/selector test | **PASS: 294 checks**; no driver or browser import |
| Node `--check`, both new modules | **PASS** |
| Scoped whitespace/source-format inspection | **PASS**; no runtime formatter rewrite |
| PostgreSQL / Laravel / HTTP server / browser / authentication | **NOT RUN** |
| Attempt 06 reservation or execution | **NOT RUN**, directory absent |

The new tests use explicitly synthetic event traces. They do not replace the prior investigation's extracted installed-code fixture or imply a new browser reproduction. No existing assertions or permission checks were changed.

## Preservation

Fresh focused baseline: **4,118 files**, including the preceding investigation artifacts, attempts 01–05, historical identity observations, foundation credentials/ledger, application/runtime sources, selected installed/public assets, historical reports and Batch G artifacts. Final comparison: **4,118/4,118 unchanged**, zero missing/changed.

All **81 current PDF-named artifacts**, including the original **73**, and the ten historical diagnostic JSON files remain byte-identical. Earlier identity-log append history is preserved, not truncated or reconstructed. The known missing temporary fake-upload metadata remains the prior exception. No logs or Blade caches were cleared or restored. The focused scope is not an exhaustive vendor/node_modules inventory.

New artifacts are the two pure source files, this report and private baseline/preservation helper/results under `storage/app/private/uiux-consolidated-acceptance/gate-b2-password-reveal-experiment-design/`. No attempt-local path or credential binding was added.

## Readiness and next authorization

**Offline design/core PASS; full live discriminating experiment BLOCKED.** No concrete runtime correction is justified. Before requesting live execution:

1. Resolve import/registration and initialization-completion observation gaps through a separately reviewed source-only adapter design or isolated framework reproduction. If this requires protected runtime changes, obtain explicit authorization; do not patch vendor or published assets under the present scope.
2. Offline-test any adapter against the installed implementation, including instance consistency, hook ordering, duplicate wrappers, error-delivery lag, rejected imports, overflow and cleanup. Keep marker collection behaviorally passive; no artificial waits or provider injection.
3. Define one shared observation sequence and explicit origin for browser callbacks and driver observations. If sequences cross process boundaries, preserve local execution order and mark ingestion order separately; do not treat transport arrival as expression time.
4. Seek separate authorization for a **single diagnostic-only login experiment**, with no personnel transitions. Any later runtime access must retain fixed browser-database identity guards, fresh read-only preflight/postflight, non-overwriting reservation, private session provisioning, historical log protection, exact request policy, one login submission and fail-fast cleanup. Real login may have normal session/audit effects; authorize those explicitly. No automatic retry or progression to publication.

No experiment launcher, live adapter, attempt identifier or Attempt 06 path was prepared. Versions 47/48, directory 26 and protected configuration are preservation targets, not actions for this design.

**STOP.** No live execution, reviewer authentication, lifecycle transition, directory recreation, PDF, migration, B3/B4, commit, push or deployment occurred.
