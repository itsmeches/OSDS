    # Gate B2 — WorkflowOverview policy candidate validation

    **29 October 2026. BLOCKED for protected integration.** The isolated correction candidate passed 62 offline checks and four installed Livewire checksum checks. One disposable Chromium execution passed 19 cases, then stopped on a fixture navigation-accounting invariant in case 20. Its request was correctly rejected; the fixture counted its own deliberate history change as an unauthorized navigation. Two remaining cases were not run. No candidate defect was demonstrated by that failure, but the browser matrix is incomplete. No retry or Attempt 14 preparation occurred.

    ## Exact candidate changes

    Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation. Added:

    - `scripts/uiux-b2-workflow-overview-policy-candidate.mjs` — isolated validator, Dashboard/document witness and route-accounting candidate.
    - `scripts/check-uiux-b2-workflow-overview-policy.mjs` — focused offline checks.
    - Private synthetic fixtures, runner and preservation records under `storage/app/private/uiux-consolidated-acceptance/gate-b2-workflow-overview-validation/`.

    Candidate SHA-256:

    `8eecd269b80509afa02485eda84d0808f76b1e7d3d9ad98ce12175f632561c6e`

    The original validator and first-fatal adapter remain unchanged because historical packages pin them. The new candidate wraps their existing behavior and supplies a separate route implementation for validation; no protected caller imports it. No historical manifest was edited or repinned.

    Only exact `App\Filament\Widgets\WorkflowOverview` receives the new `workflow-lazy` and `workflow-refresh` classifications. Other requests retain the existing validator/attribution path; the candidate additionally rejects personnel `advance`, including an object-phase invocation. Widget requests require exactly one component and call, an empty updates object, a structurally valid snapshot and the current document's matching widget identity/snapshot.

    The witness requires a previously submitted Login, no fatal flag, exact configured origin and `/admin`, one exact Dashboard, no Login component/form or malformed metadata, one widget and the unique visible exact level-one **Your workspace** heading. An opaque document handle is checked before/after collection. This establishes a bounded Dashboard context, **not independent reviewer authentication**. The caller must dispose the observer and retain existing authentication/sidebar acceptance. These observations add no polling, sleep or timeout allowance.

    ## Installed request contracts and signature limits

    The installed source supports these contracts:

    | Contract | Source evidence and candidate restriction |
    | --- | --- |
    | Lazy initialization | `vendor/livewire/livewire/src/Features/SupportLazyLoading/SupportLazyLoading.php:178` encodes the mount snapshot; line 192 emits the intersection/init `__lazyLoad` call; line 139 handles it. Require one canonical base64 argument, identical to the current DOM-advertised token, initial lazy flags and no updates. |
    | Mount container | The same file, line 230, restores the signed snapshot through `fromSnapshot()`; line 237 checks its component binding. Require decoded `__mountParamsContainer`, exact WorkflowOverview `forComponent`, array `forMount`, and the expected container data shape. |
    | Polling | `vendor/filament/widgets/src/Concerns/CanPoll.php:7` defaults to 5 seconds; `resources/views/stats-overview-widget.blade.php:17` emits expressionless polling. Installed `vendor/livewire/livewire/dist/livewire.js:17322` selects `$refresh`; line 7414 sends the action. Require zero parameters, no updates and the loaded widget state. |
    | Updated snapshots | Installed client line 7522 replaces its encoded runtime snapshot after responses. The witness reads that current in-memory snapshot when available, falling back to the initial DOM attribute. It does not reuse an obsolete initial snapshot for later polling. |

    The policy does **not** possess the protected signing key or replace Livewire's server verification. Snapshot/checksum structure and document/token equality reject request-only alterations; they are not cryptographic proof of an attacker-controlled document. Installed `Mechanisms/HandleComponents/Checksum.php` remains authoritative, as do widget authorization and mount/hydrate checks. No server protection, permission or business logic changed.

    A standalone fixture exercised the actual installed Checksum implementation with a synthetic key and minimal container: three valid synthetic snapshots verified and a changed component binding failed verification (**4 checks PASS**). It used vendor autoload only, with no Laravel application bootstrap, real key or database.

    ## Separate route accounting

    Widget traffic is never classified as notifications. The existing exact notification exemption remains unchanged. Login still sets the consequential-submission flag; a duplicate Login is rejected. Verified widget traffic requires that flag already set and does not reset or consume another Login allowance.

    The candidate binds one widget ID/document, permits at most one lazy initialization and caps refreshes at **12 per route instance**. The cap derives from the reviewed maximum Dashboard allowance of 60 seconds and the installed 5-second poll interval; it introduces no waiting or longer execution allowance. This is a count bound, not a timer or proof of actual poll cadence. A future caller must retain its existing stage deadlines and dispose this instance.

    Origin, method, validated update endpoint and admin-path restrictions remain enforced. Unknown components/actions, nonempty updates, malformed bodies and lifecycle methods remain denied. Rejections use the unchanged first-fatal diagnostic; later request failures do not overwrite the first reason.

    ## Actual validation

    Executed:

    ```powershell
    node scripts/check-uiux-b2-workflow-overview-policy.mjs
    ```

    **PASS: 62 checks**, including ten negative contracts and eight route cases. Coverage includes exact lazy/refresh shapes, wrong component/method/arguments/updates, multiple components/calls, malformed and tampered snapshots/tokens, lifecycle denial, wrong Dashboard context/phase, exact notifications, Login followed by widget traffic, duplicate Login/lazy, refresh cap, pre-Login widget rejection and first-fatal precedence. These controlled dependencies do not establish real protected rendering.

    One disposable execution:

    ```powershell
    node storage/app/private/uiux-consolidated-acceptance/gate-b2-workflow-overview-validation/run.mjs run
    ```

    | Runtime fact | Actual result |
    | --- | --- |
    | Exit / aggregate | **1 / FAIL**, stopped at first unexpected invariant |
    | Start / finish UTC | **2026-10-29 10:06:46.667 / 10:07:02.311** |
    | Asia/Manila | **18:06:46.667 / 18:07:02.311** |
    | Node / Playwright | **24.20.0 / 1.64.1** |
    | Actual `browser.version()` | **156.0.8078.4** |
    | Cases | **20 entered; 19 PASS; case 20 fixture FAIL; 2 NOT RUN** |
    | Isolation violations / unexpected page errors / unhandled rejections | **0 / 0 / 0** |
    | Retry / post-failure source correction | **0 / 0** |

    Fresh exclusive evidence: `gate-b2-workflow-overview-validation/browser-validation-01/`. Fresh nonpersistent contexts were closed in cleanup, and the browser was closed. The runner used only in-memory synthetic resources at `http://127.0.0.1:18160`; no HTTP server, protected application, real credentials/session, database or external network continuation was used. Service workers/downloads were disabled and WebSockets blocked. Closed observations were written before outcome assertions; no raw body/snapshot/DOM, arbitrary component name, exception text, screenshot or unrestricted trace was retained as browser observations. Synthetic fixture snapshots remain private test inputs.

    | Completed coverage | Actual outcome |
    | --- | --- |
    | Actual installed client lazy mount then poll | **PASS**, one lazy and one refresh forwarded through candidate; synthetic signed backend response processed by installed client |
    | One synthetic Login then actual client widget traffic | **PASS**, one Login, one lazy, one refresh |
    | Repeated polling | **PASS**, three refreshes |
    | Wrong component, method, arguments, updates | **PASS**, each blocked |
    | Multiple components / malformed body | **PASS**, expected count/parse categories |
    | Tampered lazy token / request snapshot | **PASS**, blocked |
    | Lifecycle method / duplicate Login / unexpected notification | **PASS**, blocked; duplicate Login retains one forwarded Login |
    | Duplicate lazy / refresh cap | **PASS**, excess request blocked |
    | Missing Dashboard / hidden heading / Login form | **PASS**, widget traffic blocked |
    | Destination departure | Expected `COMPONENT_ACTION_BLOCKED` observed, zero forwarded posts, false confirmation; **fixture invariant failed** |
    | Deliberate original page-error precedence / external-origin case | **NOT RUN**; offline precedence checks are not browser substitutes |

    All entered cases retained false test-only authentication confirmation and zero business actions. The two actual-client positives establish installed transport compatibility using synthetic snapshots, not a protected reviewer session or real widget statistics execution.

    ### Fixture failure and attribution

    The destination case deliberately executes `history.replaceState(..., '/admin/login')`. The runner's main-frame navigation listener counts this fixture-owned transition while tracking is enabled, then asserts that its combined navigation count is zero. The retained count is **1**. The candidate correctly returned the expected rejection, with `COMPONENT_ACTION_BLOCKED` first, cleanup false, zero widget/Login/backend forwarding and false confirmation. No candidate navigation API was invoked.

    This is a confirmed **fixture accounting defect**, not evidence of unauthorized candidate navigation. The original observation and aggregate FAIL remain intact. The runner/candidate was not changed or rerun after failure. The remaining first-fatal/external-origin browser controls are incomplete; a full browser PASS cannot be claimed.

    ## Preservation and decision

    Fresh focused comparison: **8,172/8,172 baseline entries unchanged**, zero differences/unavailable. Attempts 01–13, their evidence/session bytes, original credentials/identities, protected sources/helpers/manifests, avatar correction, historical logs/caches and artifacts remain preserved. Credentials/session bytes were hashed only. All **29 historical source pins** still match; Attempt 13 manifest remains `f5426fa2a5f8c01468de383b98ff612c275880c40580816b45c4013ff27e1e09`.

    Laravel log remains **17,218,391 bytes**, SHA-256 `a71c2bd7f7d4fb8180decea1d7bbc13cf6b943b4c9a8a5fc428d8a0b3182e44b`: **zero new bytes**. The documented 370-byte exception remains **APPLICATION ERROR INDICATED — PHP_EXECUTION_TIME_LIMIT — ATTRIBUTION NARROWED**. No file was restored, truncated, cleared or overwritten to obtain preservation. This is a focused inventory, not whole-cluster or whole vendor/node_modules verification.

    **BLOCKED for Attempt 14 integration:** the narrow candidate has useful positive and negative evidence, but its full disposable matrix has not completed. It does not identify Attempt 13's exact historical rejected request, and cannot be called its proven fix.

    **One recommended next action:** separately authorize a fixture-only correction distinguishing deliberate fixture navigation from candidate actions, followed by one fresh full-matrix validation. Preserve this failed run and keep the candidate unchanged unless a demonstrated candidate defect requires separate review.

    **STOP.** No protected browser/login, database operation, application business-logic change, historical repinning, Attempt 14 preparation/execution, service operation, lifecycle action, migration/seeding/repair, B3/B4, commit, push or deployment occurred.
