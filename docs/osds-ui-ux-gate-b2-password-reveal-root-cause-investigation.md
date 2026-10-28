# Gate B2 — Password-reveal factory investigation

**28 October 2026. Source investigation PASS; live causal attribution remains BLOCKED.** Attempt 05 confirms an undefined password-reveal factory reference, but no concrete application defect or runtime correction is established. No Attempt 06 was prepared, reserved or executed. B3/B4 remain closed.

## Evidence and limits

Reviewed the [Attempt 05 report](osds-ui-ux-gate-b2-reviewer-only-attempt-05-report.md), its safe ordered `browser-result.json`, the [earlier investigation](osds-ui-ux-gate-b2-referenceerror-investigation.md), [diagnostic correction](osds-ui-ux-gate-b2-diagnostic-correction-report.md), current reviewer driver and pure request policy. Applied OSDS Verification Gates and Livewire Browser Diagnostics.

Attempt 05 recorded login POST forwarding at sequence 25, its HTTP 200 response at 26, password-reveal asset HTTP 200 at 27, and the first `PASSWORD_REVEAL_FACTORY_UNDEFINED` page error at 28. The browser was in `login-verifying`; authentication was not confirmed. These are observer event positions, **not timestamps of the original expression execution**. Alpine's asynchronous error rethrow can separate evaluation from page-error delivery. An asset response does not establish successful import, registration or initialization.

The prior postflight recorded COR 47 and ACSA 48 as draft/lock 1 with creation-only history. This investigation made no fresh database assertion. Directory 26 and both versions were untouched.

## Installed implementation and initialization contract

Composer lock records Filament **5.10.1**, Livewire **4.4.7** and Laravel **13.35.0**. The installed Livewire bundle embeds Alpine **3.17.4**. Filament support requires Livewire `^4.4.7`; installed Illuminate/PHP constraints admit this Laravel/PHP combination. No declared Composer incompatibility was identified. The shipped support files do not establish an exact AsyncAlpine release number; its **actual bundled implementation**, rather than an assumed version, was inspected and exercised.

| Step | Installed source and guarantee | Remaining live uncertainty |
| --- | --- | --- |
| Render | `vendor/filament/forms/src/Components/TextInput.php` builds `x-data="passwordRevealFormComponent()"`, `x-load`, and `x-load-src` for revealable passwords; the type binding uses `isPasswordRevealed`. Login uses this existing field implementation. | The exact wrapper's initialization state at submission/error was not captured. |
| Register asset | `FormsServiceProvider.php` registers `text-input/password-reveal` under `filament/forms`. `AlpineComponent.php` and AssetManager resolve its public component URL with a version query. | HTTP 200 alone does not verify response execution or module namespace. |
| Install loader | Support `resources/js/index.js` registers AsyncAlpine on `alpine:init`. The distributed implementation installs an `x-load` directive before `ignore`. | Actual listener execution/order and number of Alpine instances are unobserved. |
| Suspend initial evaluation | Bundled AsyncAlpine inline handling sets `_x_async='init'`, `_x_ignore=true` and `x-ignore`. Its load handler moves to `await`, parses the factory name and uses the eager strategy by default. | Eager is asynchronous; visible controls are not evidence that this work has finished. |
| Import and register | It imports the module, selects a named/default/first export, calls `Alpine.data(name, factory)`, then marks its registry entry loaded. The published module default export returns the reveal state and methods. | Import rejection, disconnected elements or an interrupted initialization remain possible. The factory is an **Alpine data provider**, not a required `window` global. |
| Initialize | While the element remains connected, the loader destroys the ignored tree, removes ignore and invokes `Alpine.initTree`. Registration precedes this explicit initialization. | This guarantee does not automatically cover every concurrent DOM clone. |
| Morph/clone | Livewire's installed morph implementation calls `Alpine.cloneNode(from,to)`. Both AsyncAlpine directive handlers skip during cloning. Alpine transfers `_x_dataStack` and adds `data-has-alpine-state` only when source state exists. | Whether this login response actually cloned an awaiting wrapper is unknown. |
| Evaluate | Alpine's `x-data` handler injects registered data providers into expression scope. During cloning it skips registration/evaluation for transferred state; without that marker it can evaluate the expression. | The retained stack lacks the specific element and clone state. Undefined factory scope is confirmed; its causal path is not. |

Relevant installed locations include Livewire `dist/livewire.js` around 1800 (clone mode), 4787–4860 (`x-data` and state transfer), 15112 (morph clone), and 15965 (Livewire morph updating). The AsyncAlpine implementation is bundled in `vendor/filament/support/dist/index.js`; no package source was modified.

Public Context7 Livewire 4.x documentation was consulted using only public framework terms. Its component-script loading guarantee concerns Livewire-managed component scripts; it does not prove that Filament's separate AsyncAlpine import has completed. Branch documentation is not exact-release certification. See [Livewire JavaScript documentation](https://github.com/livewire/livewire/blob/4.x/docs/javascript.md). No browser MCP, Boost runtime tool or additional permission was used.

## Ranked mechanisms and discriminating tests

Ranking reflects explanatory source evidence, not measured probability or confirmed causation.

| Rank / candidate | Supporting and contradictory evidence | Unknown / smallest discriminating observation |
| --- | --- | --- |
| 1 — Morph clone before async provider registration | Installed loader skips clone handling; Alpine can evaluate a clone lacking transferred state. The extracted-code fixture reproduces this conditional undefined reference. Conversely normal loader initialization registers before evaluation, and an initialized clone carries state safely. | Need evidence that the real password wrapper was awaiting and lacked state when a morph clone occurred. Under separate authorization, record only fixed clone/start/registration markers and bounded booleans/enums for that wrapper. |
| 2 — Module import or initialization failure | HTTP 200 does not prove dynamic import resolution. The loader awaits import and checks connectivity; an unavailable provider can persist if the path aborts. The checked module is valid JavaScript and exports the expected factory. | A closed import-success/failure category and provider-registration marker would distinguish failure from a pending import. No raw exception/module content is needed. |
| 3 — Loader/script dependency ordering or missing registration | Support plugin registration depends on Alpine initialization; a missing loader could leave `x-data` unprotected. Successful support/core delivery and the expected installed registration code weigh against assuming a missing dependency. | Safe counts/booleans for loader installation and Alpine-instance identity at fixed boundaries; presence alone must not be called readiness. |
| 4 — Reinitialization, stale or duplicate registration | AsyncAlpine caches names and Alpine scopes can be replaced during morphing. Multiple instances could register against one scope and evaluate in another. No duplicate instance or stale registration is observed. | Bounded instance-consistency and matching-wrapper-count checks; do not call `Alpine.data(name)` as a getter because this API registers providers. |
| 5 — Driver timing | Driver fills and clicks after visible controls/component identity, without proving async wrapper initialization. This can expose a pending-load race. It does not establish invalid application use or justify arbitrary delays. | A separately authorized diagnostic-only login observation correlating fixed operation, clone and initialization markers; no polling delay, resubmission or personnel action. |
| 6 — Published mismatch / framework compatibility | Password-reveal, support and panel app public files are byte-identical to installed dist files; declared Composer constraints align. This contradicts a stale-byte explanation for those three pairs only. Bundled JS integration bugs remain possible. | Exact AsyncAlpine release provenance and a minimal upstream isolated reproduction may clarify compatibility. Do not update dependencies speculatively. |

The strongest candidate is therefore a **conditional initialization/morph race**, not a confirmed root cause. No evidence justifies disabling reveal, injecting a global, reordering scripts, changing authentication, suppressing errors or widening Livewire permissions.

## Offline verification

Private fixtures are under `storage/app/private/uiux-consolidated-acceptance/gate-b2-password-reveal-investigation/`.

| Check | Actual result |
| --- | --- |
| Diagnostic categorization/redaction/order suite | **PASS: 98 checks**, `scripts/check-osds-diagnostics.mjs` |
| Existing request-policy/selector suite | **PASS: 294 checks**, pure policy test; no driver entry point |
| Extracted installed AsyncAlpine fixture | **PASS:** pending state is ignored; registration precedes explicit initialization; actual module supplies reveal state |
| Extracted clone predicate/state-transfer controls | **PASS:** uninitialized source has no transferred-state marker, clone directive skips, known factory expression raises ReferenceError without provider; initialized-state control skips reevaluation |
| Public/package SHA-256 comparisons | **PASS: 3/3**, password-reveal, support, panel app |
| JavaScript syntax / scoped whitespace | **PASS:** Node `--check` for reviewer driver, diagnostic helper and private fixture; scoped `git diff --check` |
| Database, Laravel, HTTP server, browser, authentication | **NOT RUN** |

The fixture executes the installed plugin and clone predicate/state-transfer clauses with mocked element methods, strategy and deferred import. It is **not** a complete Alpine DOM evaluator or Livewire browser reproduction. A test-harness extraction delimiter initially failed and was corrected privately; no application/test assertion was altered. No live failure was retried.

## Preservation and recommendation

A fresh focused **4,113-file** baseline includes the earlier preservation scope, Attempt 05 evidence/session bytes, selected installed framework trees and published assets. It is not a whole vendor/node_modules inventory. Final comparison and scoped static results are recorded below. Historical logs, reports, credentials, PDFs and JSON were neither edited nor restored. The original identity-log append exception and missing temporary fake-upload metadata remain historical exceptions.

Final comparison: **4,113/4,113 unchanged, zero missing or changed**, including all 81 current PDF-named artifacts (the original 73 included), ten historical diagnostic JSON files, previous attempts and reports, application runtime, historical identity log and Batch G artifacts. New files are this report and private investigation/fixture/baseline artifacts only. No verification driver, policy, bootstrap, framework asset or runtime file changed. Attempt 06 remains absent.

Recommend separate authorization to **design and offline-test a diagnostic-only initialization/morph experiment**, with fixed public markers, bounded wrapper state booleans/enums and no values, snapshots, console dumps, raw errors or request bodies. A future live experiment needs its own authorization and fresh integrity checks. No attempt path or runtime instrumentation was prepared here. Personnel transitions should remain outside that diagnostic experiment.

**STOP. Source defect not confirmed; live cause unresolved.** No Attempt 06, runtime change, database connection, server/browser, authentication, configuration mutation, PDF, migration, B3/B4, commit, push or deployment occurred.
