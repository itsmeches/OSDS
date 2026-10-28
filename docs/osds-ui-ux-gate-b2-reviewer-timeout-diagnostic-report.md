# Gate B2 — Reviewer timeout and identity-log routing diagnosis

**28 October 2026. Source-only diagnosis and bounded verification corrections completed. No server, browser, authentication, Laravel bootstrap or database execution. Reviewer-only continuation remains BLOCKED pending the preparation described below and separate authorization. B3/B4 remain closed.**

## Evidence and confidence

Reviewed the committed driver, public and retained private router/bootstrap sources, prior results, preservation exception and the retained author-context Playwright trace. Extracted trace copies and offline diagnostics are private under `storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-diagnostic/`. Original archives were not edited.

| Question | Finding / confidence |
| --- | --- |
| Last completed work before reviewer login | Author saved directory revision **26** and created COR draft **47** and ACSA draft **48**; the driver then inspected drafts, captured the drafts screenshot and called `login('config-reviewer')`. High confidence from source and prior results. |
| Exact timeout operation | `idle(page)` calls `waitForLoadState('networkidle')` in two places inside login: after opening login and after the URL changes. The retained error does not distinguish those call sites. Exact reviewer URL/stage is **UNKNOWN**. |
| Reviewer login POST sent / accepted | **UNKNOWN**. Tracing starts only after login and idle complete. Only the author context has a retained trace; it contains no reviewer login request or response. Absence of a completed-login result is not proof of rejected credentials. |
| Inappropriate readiness condition | **Confirmed infrastructure weakness:** network-wide silence is unrelated to whether the required login/page controls are usable. The author context continued producing blocked notification requests while reviewer setup began. Contribution to the specific reviewer timeout is plausible, not conclusively demonstrated. |
| Unexpected redirect / JavaScript failure | No HTTP error response was found in the retained author network entries. This does not establish reviewer redirect behavior or absence of reviewer JavaScript errors. The original driver did not attribute accumulated errors to contexts/phases. |

Do not infer successful reviewer authentication or an application authorization defect from this evidence.

## Request filtering

The author network trace contains **319 network entries**, including **280 failed entries**. Request bodies are stored as archive resources referenced by `postData._file`, rather than inline text. Offline decoding found these blocked operations on the generated same-origin endpoint `/livewire-6d26c714/update`:

| Component / operation | Observed shape | Decision |
| --- | --- | --- |
| `Filament\Livewire\Notifications`, `__dispatch` | `['notificationsSent', []]`, empty updates | Allow only this exact framework notification pull. |
| Same component, `__dispatch` | `notificationSent` with a notification object, including “Error while loading page” notifications and varying IDs | Continue blocking. Treat the failure promptly; do not admit arbitrary event payloads to suppress visible errors. |
| `__lazyLoad` | Earlier results recorded one blocked call; its exact component/payload is not established by the retained author network projection | Continue blocking. No broad lazy-hydration exception is justified. |

The decoded projection has **277 distinct internal-call entries**: one session-notification pull and 276 distinct notification payloads. These are not the total call counts across all contexts; the prior result's 3,843 dispatch blocks covers a different scope. No count is substituted for another.

`vendor/filament/notifications/src/NotificationsServiceProvider.php` dispatches `notificationsSent` when session notifications exist. `Notifications.php::pullNotificationsFromSession()` listens for that event. Its `notificationSent` listener instead accepts an object and pushes a notification. These operations are distinct. Blocking the first caused an infrastructure error-notification cycle in the retained author context; admitting every dispatch would conceal the original error and widen the action surface.

Changed only verification-driver behavior:

- Validate the Livewire envelope and snapshot identity before applying the existing application-action restriction.
- Admit the exact Notifications component/event/empty-parameter/empty-update combination above; keep other internal calls blocked.
- Reject the workflow through a shared failure promise when an unexpected POST/action or page error is observed. Cleanup closes contexts instead of allowing thousands of errors to accumulate until a generic timeout.
- Replace `networkidle` with DOM-content readiness, retain explicit visible-state assertions and URL checks, and race workflow/URL waits against infrastructure failure.

The endpoint validation, external-request blocking and existing application action restrictions remain. No server handler or runtime component changed. The old author-creation workflow remains in the driver and its evidence directory already exists: **this script must not be reused to resume the committed drafts**.

## Identity logging

**Confirmed source defect:** `b2Identity()` reads `global $dir`; the public bootstrap initializes that directory to the earlier attempt. The public router requires that public bootstrap. The retained private bootstrap substitutes a continuation-local directory but still imports a function dependent on the same mutable global. Path selection was therefore implicit and differed between entry points.

The 101 appended HTTP observations prove that those calls resolved the old destination. The retained sources alone do **not** prove whether the running HTTP process used the public router/bootstrap or whether later code changed its global directory. No retained process launch record has established that final causal distinction. It remains unresolved rather than attributed to PostgreSQL, timezone handling or Laravel without evidence.

Corrected the public verification bootstrap to select the fixed future directory `gate-b2-reviewer-only/attempt-01/`, require its prior reservation before autoload/connection, and bind the imported identity function locally to `UIUX_B2_IDENTITY_LOG`. Import fails if the expected single global declaration changes. Database target and resolver checks are unchanged. The log function can no longer be redirected by changing global `$dir` after bootstrap.

The public router already requires this bootstrap, so future HTTP requests and CLI users of that bootstrap select the same literal destination. Old private snapshots remain historical evidence and must not be launched. A future launcher must reserve the new directory atomically, use this corrected entry point and verify the earlier log's whole-file hash before and after execution. No new live-attempt directory or server was created here.

An isolated mock-PDO check evaluated only the extracted identity function: no PDO connection constructor, Composer or Laravel ran. It wrote a new diagnostic mock log, left a decoy global-directory log unchanged and selected the fixed constant path. This proves path selection under the mock, not live process isolation or server identity.

## Offline verification and preservation

| Check | Result |
| --- | --- |
| JavaScript syntax | **PASS**, changed driver |
| PHP syntax | **PASS**, changed verification bootstrap |
| Scoped Pint `--test` | **PASS**, changed bootstrap; no formatter rewrite |
| Whitespace | **PASS**, `git diff --check` |
| Retained-request policy replay | **PASS**, exact observed notification pull admitted, 276 distinct payload dispatches rejected |
| Negative filter controls | **PASS**, wrong component, arbitrary notification event, lazy load and nonempty updates rejected |
| Path-isolation mock | **PASS**, fixed log selected despite conflicting global; historical logs not used as test destinations |
| Existing selector | Unchanged; the reviewed exact page-component selector remains in place |
| Protected source / documentation / PDFs | **PASS**, 2,525 files compared with the prior recorded baseline; no differences, including all 81 current PDF-named files and therefore the original 73 |
| Historical private evidence | **PASS**, 205 files checked against the pre-continuation baseline; only the already documented old identity-log append differs. Its original 61-record prefix hash still matches and its 101-record tail matches the retained copy exactly. |
| Broad fresh file baseline | **INCOMPLETE**: exhaustive hashing did not complete and was stopped. No whole-working-tree byte-identical claim is made. |

The existing preservation exception remains historical fact. The earlier identity log was not truncated, renamed, restored or used as a mock output. The known missing temporary fake-upload metadata remains a prior exception; it was not reconstructed. Historical JSON, gate reports, runtime source and Batch G are not correction targets. New files are this report and private diagnostic/extracted-trace/mock-check artifacts; the only edited public tools are `UiUxB2PersonnelBootstrap.php` and `verify-uiux-b2-personnel.mjs`.

## Remaining blockers and stopping point

No current database state was queried. Revision **26** and drafts **47/48** retain their previously observed states; this report does not assert a fresh state verification. They must not be recreated.

Before a separately authorized reviewer-only continuation, prepare a fixed non-overwriting launcher/driver that omits author creation, revalidates those exact existing drafts and old roots, records safe phase-specific login response diagnostics without credentials, and uses the corrected bootstrap. Resolve any newly encountered lazy component through evidence rather than adding a global allow rule. Exact reviewer login outcome and live logging-path behavior still require authorized observation.

**STOP. No review, publication, configuration change, authentication, database connection, PDF, B3/B4, commit, push or deployment occurred.**
