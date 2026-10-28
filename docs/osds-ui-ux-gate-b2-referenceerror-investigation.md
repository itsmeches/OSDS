# Gate B2 — Login ReferenceError investigation

**28 October 2026. Source-only investigation complete. Recommendation B: DIAGNOSTIC GAP CONFIRMED.** The application/JavaScript root cause remains **UNKNOWN**. No application or verification code was changed. Attempt 05 was not prepared, reserved or executed. B3/B4 remain closed.

## Evidence timeline and limits

Reviewed attempt-03/04 reports, attempt-04 sanitized browser/server records, reviewer driver, request policy and exception helper. No session contents, raw login body or discarded exception text was recovered.

1. Attempt 04's provisioning and guarded read-only preflight passed historically; versions 47/48 were draft/lock 1.
2. Login GET returned 200 and the driver observed email/password/sign-in controls plus the expected login component.
3. The permitted login POST was attempted. The retained response list records POST 200 for the generated Livewire endpoint.
4. The first captured page error was **ReferenceError**, phase **login-verifying**, pathname **/admin/login**, displayed-form/attempted-POST flags true, authentication-confirmed false.
5. The workflow stopped without a completed step. Postflight historically confirmed both drafts unchanged and no review/publication events.

The attempted-POST flag establishes that the policy accepted the login call before the error snapshot. The response list and error snapshot do not have a shared timestamp/sequence marker, so they cannot independently order the POST response against the exact first-error event. Later response events can also arrive during shutdown. Credential acceptance, redirect effects and authenticated identity remain unconfirmed. HTTP 200 alone proves none of these.

The stack retained one restricted frame and seven generic asset locations. It omitted original filenames, functions, expression and identifier. Those omissions prevent exact source attribution. Matching a current file's line/column is an investigative clue, not recovery of the original stack.

## Source and asset families inspected

| Family / source | Findings |
| --- | --- |
| `app/Providers/Filament/AdminPanelProvider.php` | Uses the installed Filament login and CSS-only Vite theme. Custom table/recovery hooks are scoped to listed staff pages, not Login. No panel app-JavaScript registration found here. |
| Filament `Auth/Pages/Login.php` and `Auth/Http/Responses/LoginResponse.php` | Email/password controls use TextInput; password reveal is conditional on panel configuration. Authentication performs credential/panel/challenge checks and redirects to the intended/panel URL. These are not replaced by HTTP-status checks. |
| Filament panel `components/layout/base.blade.php` | Renders content, Notifications and `@filamentScripts(withCore: true)`, with script hooks and theme initialization. No live HTML render performed. |
| `support/src/Assets/AssetManager.php`, `Js.php`, support assets Blade | Non-core/core asset ordering and script attributes are defined here; default script flags are not async/deferred. Runtime delivery/order still depends on the actual page. |
| `support/resources/js/index.js`, published `support.js` | Registers AsyncAlpine and other plugins at `alpine:init`. Password component loading relies on framework initialization. |
| `forms/src/Components/TextInput.php` | Emits `x-data="passwordRevealFormComponent()"`, `x-load`, a registered password-reveal source and expressions using `isPasswordRevealed`. These are public source identifiers, not account values. |
| `forms/src/FormsServiceProvider.php`, published password-reveal module | Registers `text-input/password-reveal`; its default factory supplies `isPasswordRevealed`, a focus counter and methods. The module's methods access Alpine/browser facilities when invoked. |
| Installed Livewire `dist/livewire.js` / `livewire.min.js` | Includes Alpine evaluation, generated functions and error handling. Its normal error handler attaches expression/element information and asynchronously rethrows errors. No console/element payload was recovered or captured. |
| Published Filament panel app/tables/support assets | Inspected public bundles and plausible stack coordinates. Server evidence confirms public support/app/tables/password-reveal requests; successful delivery does not prove initialization. |
| `resources/js/app.js`, `applicant.js`, built app bundle and Vite manifest | Portal theme/recovery code exists, but the inspected panel registration/layout does not establish that this app bundle was included on Login. It is not a supported cause based on its mere presence in the repository. |

Published password-reveal, support and panel-app bundles each compare **byte-identical** with their corresponding installed package `dist` files. This rules out a byte difference for those three pairs at this source snapshot; it does not certify all dependencies or eliminate a framework defect.

The retained line **5:1165** is in evaluator-related code in the current Livewire minified bundle, and **1:21263** is near its error-handling code. Other public bundles also have positions within some retained coordinates. Since every asset was collapsed to `/[asset]`, no filename match is established. The private `asset-clues.json` records bounded excerpts from public source only.

## Candidate classification

| Candidate | Evidence / assessment |
| --- | --- |
| Password-reveal component/expression context missing during initialization or update | **Plausible.** Source explicitly uses asynchronous loading and public scope identifiers; the server recorded a password-reveal asset request. Visible controls do not prove AsyncAlpine initialization. A synthetic missing-scope expression raises ReferenceError. This does not show that the live error used that expression or explain why a scope would be missing. |
| Other Alpine expression or dependency initialization failure | **Plausible.** Current framework error/evaluator source is consistent with some generic stack clues; original expression and asset identity are unavailable. No exact dependency or failure is confirmed. |
| Delayed framework error raised after an earlier operation | **Plausible.** The installed normal Alpine error handler rethrows through `setTimeout`; capture during login-verifying need not identify the expression's original execution phase. |
| Driver's synchronous login-component evaluation | **Not supported as the immediate stopping operation.** It completed before the accepted login POST. Its callback binds its parameters and parses only snapshot memo names. An offline fixture executes it successfully. It contains no asynchronous work. |
| Later personnel-page Playwright evaluation | **Not reached.** It occurs after confirmed login, which the retained flags do not show. |
| Node diagnostic listener or URL-check callback itself | **No confirmed defect.** Those run in the driver process; the first error arrived through the page-error listener. Local failures have a separate workflow-catch path. No page script is injected by that listener, and the URL predicate binds its arguments. This does not exclude indirect interactions caused by ordinary UI input/clicks. |
| Duplicate Alpine from custom portal JS, stale published bundle or bad credentials | **Unsupported by current evidence.** No duplicate registration was established; the three inspected asset pairs match. HTTP 200 and a ReferenceError do not establish credential failure. |

No confirmed application-side defect or safe runtime correction was found. Do not disable password reveal, change script order, initialize missing globals manually or alter authentication merely to make a test pass.

## Confirmed diagnostic gap

The helper's credential-pattern gate matches the substring `password` before its generic ReferenceError template. Consequently, synthetic public messages such as **`isPasswordRevealed is not defined`** and **`passwordRevealFormComponent is not defined`** are restricted. A synthetic **`Livewire is not defined`** retains only the generic undefined-identifier category. Temporal-dead-zone messages such as `Cannot access 'publicSymbol' before initialization` are also restricted because they are outside the current template set.

These are controlled examples drawn from public source or synthetic fixtures, **not guesses or reconstructions of the attempt-04 message**. The helper's conservative privacy behavior follows its design; it leaves a confirmed observability gap rather than proving which template actually occurred.

Asset-family collapse, no first-error sequence/time marker and limited operation markers further constrain attribution. The two response/error records cannot establish whether a framework exception was generated by initialization, field interaction, response handling or a delayed rethrow.

## Proposed next action — B only

Recommend a **separately authorized diagnostic-only correction**, then offline privacy tests and review before any new execution decision:

- Map a closed set of exact public-source error templates/identifiers to fixed codes, such as a password-component-factory or reveal-scope undefined category. Never export an arbitrary missing identifier, expression or error string. Preserve known-secret checks and the restricted fallback.
- Record a closed asset-family enum from verified public same-origin paths (Livewire core, Filament support, panel app, tables, password-reveal, unknown), not filenames/URLs/query strings. Keep line/column bounds.
- Add a shared monotonic sequence and fixed operation markers around login-component inspection, field filling, click, POST forwarding/response and URL/navigation checks. Record the first page-error position in that sequence.
- If separately approved, inspect only boolean framework-presence/initialization indicators at safe operation boundaries. No storage, DOM form values, cookies or whole snapshots. Presence alone must not be called readiness or used to suppress an error.

Retain fail-fast behavior, exact request policy and one-shot workflow. Do not add console listeners, raw stacks, request bodies, pre-login tracing or retries. These changes would make a future observation informative by distinguishing a known public symbol family and execution boundary; another identical live attempt without them is not recommended.

No such correction or attempt-path change was made here. Attempt 05 is neither prepared nor reserved.

## Offline checks and preservation

| Check | Actual result |
| --- | --- |
| Pure source/diagnostic tests | **PASS: 11 checks**, including factory shape, synthetic scoped expression, missing-scope ReferenceError, current sanitizer restrictions and the extracted driver callback/order |
| Existing request-policy/selector tests | **PASS: 294 checks**, policy-only run without historical-output rewrites or browser import |
| Published/package asset comparisons | **PASS: 3/3 identical pairs** |
| JavaScript syntax / scoped whitespace | **PASS**, inspected verification tools |
| Runtime/verification source edits | **NONE**; no formatting rewrite required |
| Database / Laravel / server / browser | **NOT RUN** |

A focused **3,009-file** baseline and final comparison show **3,009 unchanged**, zero missing/changed, including selected installed/public assets, attempts 01–04/session bytes, prior reports, identity logs, foundation credentials/ledger, protected runtime, current Laravel log and Batch G artifacts. All **81 current PDF-named files**, including the original **73**, and ten historical diagnostic JSON files remain byte-identical. Logs/caches were not cleared or restored. The known missing temporary fake-upload metadata remains the prior exception.

New files are this report and private investigation/baseline/source-excerpt/test artifacts under `storage/app/private/uiux-consolidated-acceptance/gate-b2-referenceerror-investigation/`. No historical evidence output was overwritten.

**STOP. Recommendation B; live cause UNKNOWN.** No attempt 05, database connection, authentication, review/publication, office/personnel creation, migration, destructive test, PDF, B3/B4, runtime change, commit, push or deployment occurred. Await separate authorization.
