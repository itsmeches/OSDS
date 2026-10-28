# Gate B2 — Reviewer login readiness guard

**28 October 2026. PASS for verification-tool implementation and offline tests. Ready for separately authorized disposable validation; browser behavior NOT RUN.** This corrects a harness readiness gap, not the confirmed cause of the historical JavaScript failure. No production behavior changed. Protected B2 execution remains BLOCKED; B3/B4 remain closed.

## Exact changes

| Source file | Change |
| --- | --- |
| `scripts/uiux-b2-login-readiness.mjs` — new | Pure readiness predicate, exact runtime-pin assertion, read-only browser metadata collector and asynchronous guard orchestration |
| `scripts/check-uiux-b2-login-readiness.mjs` — new | Focused offline predicate, metadata, timeout/failure, action-count and integration checks |
| `scripts/verify-uiux-b2-reviewer-only.mjs` — edited | Validate installed pins before execution evidence/credentials; capture fixed login-node references; guard before credential entry and immediately before the existing single Sign in click; dispose references afterward |

The existing fatal path additionally records an internal abort boolean, so a page/request/HTTP failure takes precedence over readiness success. Its rejection and fail-fast behavior remain. No error category, request permission or expected lifecycle transition was added or removed.

Source comparison confirms the original attempt-path refusal, complete request handler and authenticated-navigation/lifecycle/reporting tail are unchanged. The driver still points to **already-used attempt-05** and refuses reuse. It must not be invoked now; no new attempt path or reservation was prepared.

## Readiness contract

The predicate accepts only the exact closed metadata shape. Missing/extra keys, unknown values or invalid types fail closed. It distinguishes `READY`, `PENDING` and `INVALID`:

- Exactly one expected `Filament\Auth\Pages\Login` component and one known password-reveal wrapper within it.
- Both nodes connected and identical to the opaque DOM references captured before credential entry. Missing, duplicate, disconnected or replaced nodes stop; replacements are not silently reacquired.
- Loader state is one of the pinned `init` / `await` / `loaded` values. Pending states may wait; unknown states stop.
- At readiness, loader is `loaded`, ignore is false/absent, the wrapper's first Alpine state scope is initialized, and its **own** `isPasswordRevealed` property exists and has boolean type. An inherited property or loaded-but-missing state is rejected.
- Browser Alpine version matches the pinned version.

The collector reads only fixed component identity and known wrapper/state metadata. It exports counts, booleans and closed loader/version categories. It does **not** export the reveal-state value, scope object, credentials, snapshot content or raw DOM. Captured DOM-node references use an opaque Playwright handle solely for identity comparison; no `jsonValue()` or state-object handle is used. The reference is disposed in `finally` around entry/submission.

The guard runs twice against those same identities: before filling either field and immediately before the original click. It waits only if the installed loader is pending, using Playwright `waitForFunction` with **30,000 ms** and `polling: 'raf'`, the same bound as the driver's existing default. There are no sleeps, longer timeout settings, repeated clicks or POST retries. Every wait frame rechecks identities/connectivity/known metadata; loaded state is followed by the full Node-side predicate before proceeding.

Page/request/HTTP failures race each readiness operation through the existing shared failure promise. An abort check before/after each awaited boundary prevents a completed readiness sample from overriding an already observed fatal event. A timeout or invalid state permits no subsequent sign-in action.

## Installed implementation pins

The guard pins installed Laravel **13.35.0**, Filament **5.10.1**, Livewire **4.4.7**, and embedded/runtime Alpine **3.17.4**. Before browser activity it checks actual `vendor/composer/installed.json` metadata and exact SHA-256 values for:

- Installed Livewire bundle and Filament support bundle.
- Installed TextInput implementation and default Filament Login implementation.
- Published password-reveal module; published support must also match installed support bytes.

Pin assertion and current installed-file comparisons pass offline. Reading metadata/source hashes does not bootstrap Composer, Laravel or a database. A changed installed contract blocks until separately reviewed; the guard does not automatically adopt new versions or private field names.

The collector's private metadata assumptions (`_x_async`, `_x_ignore`, `_x_dataStack`) are therefore deliberate version-bound verification dependencies. A loaded marker indicates the installed loader passed its initialization call, not proof of every asynchronous effect or authentication success.

## Offline verification

Executed with portable Node v24.20.0, without importing the reviewer driver entry point or launching Playwright:

| Check | Actual result |
| --- | --- |
| `node scripts/check-uiux-b2-login-readiness.mjs` | **PASS: 70 checks** |
| Existing diagnostic checks | **PASS: 98 checks**, unchanged suite |
| Existing pure request-policy/selector checks | **PASS: 294 checks**, unchanged suite |
| Existing observation-recorder checks | **PASS: 44 checks** |
| JavaScript syntax | **PASS**, new helper/tests and edited driver |
| Integration source audit | **PASS**, original request policy block and post-login/lifecycle assertions unchanged; original non-reuse guard retained |
| Scoped whitespace/source-format checks | **PASS**, all three source files and report; no runtime formatter rewrite |
| Browser / authentication / Laravel / PostgreSQL | **NOT RUN** |

The 70 checks cover ready metadata, loader init/await, missing/invalid Alpine state, absent/inherited/non-boolean reveal state, unknown metadata, duplicate/missing components/wrappers, disconnect/replacement, changed versions/hashes, unknown ignore state, bounded-wait timeout propagation, page-error precedence and action counts. They demonstrate no action after failed readiness, readiness becoming true after a simulated semantic wait, and exactly one action after successful checks. Replacement between entry and submission permits entry but prevents submission.

The serialized collector executes against in-memory node doubles only. A poison credential-property getter verifies that unrelated state values are not read. Tests also confirm the driver has exactly two readiness checks, retains one Sign in click and uses no sleep or scope serialization. Timeout tests inject rejection at the wait boundary; actual browser RAF/timeout scheduling remains unverified.

## Preservation

Before changing source, recorded a fresh focused **4,219-file** SHA-256 baseline and the original driver snapshot. Final comparison: **4,218 unchanged; exactly one changed baseline file**, the authorized reviewer driver; zero missing. New helper/tests and this report are new deliverables, not historical rewrites.

All historical attempts, Experiment 01, reports, identity records, credentials/ownership ledger, application/runtime and selected framework/public files remain unchanged, including **81/81 PDF-named artifacts** (original 73 included), ten historical diagnostic JSON files and Batch G artifacts. No logs/caches were cleared or restored. Known historical identity-log append and missing temporary fake-upload metadata exceptions remain preserved. This is not an exhaustive vendor/node_modules inventory or a new database-row verification.

Private baseline, original driver, integration audit and comparison are under:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/`

with `login-readiness-*` filenames. No previous experiment's output or pinned private framework copy was modified. Attempt 06 remains absent.

## Minimal disposable validation and decision

Recommend separate authorization to prepare a **small disposable validation of this exact guard**, not another broad framework investigation. Use the private synthetic fixture and closed origin/resource policy; never the occupied Experiment 01 directory:

1. Hold native import: guard must remain pending and permit neither credential-entry stand-in nor sign-in stand-in. Release it: actual initialization should permit the existing single controlled action.
2. Reject import, disconnect/replace wrapper, duplicate it or omit state: guard must stop and no action occur. A page error must still fail immediately; do not expand the error allowance or suppress the known factory/state errors to make this pass.
3. Verify both guard locations and retained real DOM node identities, plus context cleanup and bounded phase evidence. Preserve a fast-action control as a separate limitation; do not claim the readiness guard fixes that production path.

This validation has **not** been prepared or executed in a new browser runner. A future reviewer execution additionally needs explicit authorization, a fresh fixed non-overwriting evidence path and guarded database/configuration preflight. No lifecycle action follows merely from a login or disposable validation result.

Remaining limits: readiness can change after a sample and during Playwright actionability handling; this patch is not an atomic lock on DOM evolution. It may avoid exposing a fast-user framework race rather than repair it. Unknown initialization behavior fails closed. Historical root cause, credential acceptance and actual authenticated reviewer navigation remain unverified.

**Decision: implementation/offline PASS; ready for separately authorized disposable validation. Protected Gate B2 execution remains BLOCKED. STOP.** No browser, server, Laravel/PostgreSQL, reviewer authentication, Attempt 06 preparation/execution, lifecycle transition, directory recreation, PDF, migration, B3/B4, commit, push or deployment occurred.
