# Gate B2 — Attempt 11 fatal-signal attribution

**29 October 2026. Assessment PASS. Attribution NARROWED, not confirmed:** the pinned driver and complete bounded event ordering support the route handler's unrecorded rejection/exception paths as the initiating producer family. They do not distinguish an external-origin rejection, a particular request-policy rejection or another caught route-handler exception. Authentication remains unconfirmed. No correction, browser, database access or successor attempt was prepared or executed.

## Observed timeline and first fatal observation

Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation. Read only the prepared source and existing bounded browser/shared/root results. Attempt 11 remains immutable. Times are Node observer milliseconds; checkpoint elapsed times have the separate candidate monotonic origin.

| Order | Observation |
| --- | --- |
| 23–24, 15,056.376–15,097.510 ms | Single Sign in click after both readiness checkpoints |
| 25, 15,097.856 ms | Shared destination/root verification started |
| 26, 15,106.682 ms | Single permitted login POST forwarded |
| 27, 17,442.700 ms | Login POST response 200 |
| 28, 18,906.093 ms | `/admin` GET response 200 |
| 29, 19,005.390 ms | Exact destination completion; candidate destination checkpoint reports 3,907 ms |
| First root sample | Available, destination true, `loading`, all selected root counts zero, **fatal false**; no independent sample timestamp |
| 30, 19,010.157 ms | Dashboard metadata stage entered |
| 31–36, 19,406.305–22,562.544 ms | Six HTTP responses, all 200; no initiating fatal category |
| Failure-boundary sample/checkpoint | **fatal true**, observation unavailable; shared stage `DASHBOARD_METADATA`, last metadata condition `DASHBOARD_COMPONENT_MISSING`, failure code `EXISTING_FATAL_EVENT`, candidate elapsed 8,048 ms |
| 37, 23,145.001 ms | Workflow-failure marker; safe current page `/admin`, authentication false |
| 38, 23,152.176 ms | Request-failed marker, **7.175 ms after** workflow failure |

The first explicit retained true fatal observation is the failure-boundary record. Its exact event timestamp and position relative to marker 37 were not retained: the outer failure race can report before the inner candidate finishes its checkpoint. Source nevertheless places the failure observation before caller context closure: the driver's `finally` joins `navigationVerificationPending` before closing the context.

Thus fatal status became true after the initial nonfatal sample and before the failure-boundary sample, while readiness was unfinished. The 30-second shared maximum was not exhausted. The last valid metadata sample had Dashboard/Login counts 0/0, no Login-form signature and malformed false. Unavailable failure-boundary zero/default fields are not a second measured absence. No heading or sidebar observation occurred.

## Complete direct fatal-producer inventory

In [the driver](../scripts/verify-uiux-b2-attempt-11.mjs:36), `fatal(message)` is the **only assignment of true** to `readinessAborted`; it sets that flag synchronously and rejects the shared failure promise. There are five call sites, listed below. Repeated calls keep the flag true; rejecting an already-settled promise does not replace its first rejection. There is no retained first-producer latch.

| Direct producer / source | Trigger and loading applicability | Initiating evidence / race / cleanup |
| --- | --- | --- |
| Route external-origin branch, driver line 43 | A routed request's parsed origin differs from fixed base; can occur while destination resources load | Calls fatal **before** `route.abort()` with no category/event. Can interrupt any pending metadata evaluation. An abort may yield a later request-failed event; source does not identify the historical request |
| Route catch, driver line 53 | Any synchronous throw in the guarded handler, including all policy branches below; active during document/resource/request processing | Calls fatal with exception message, then aborts, **without a bounded initiating category**. May race metadata. Cleanup-related route API failures are conceivable in general; caller context closure occurs after this run's failure-boundary fatal observation |
| `pageerror`, driver line 67 | Unexpected page JavaScript error, including during loading | First error records `page-error` and sanitized `pageError` **before fatal**. Can race metadata. No such record exists here; an already-retained first error would still be present |
| `response`, driver line 68 | Same-origin HTTP response status >=400 | Records response method/status/safe path **before fatal**. Can race metadata; server/bootstrap failures may feed it indirectly. All retained responses were 200, so this does not fit the initiating evidence |
| `requestfailed`, driver line 69 | Browser reports any failed request; handler takes no request argument | Records `request-failed` **before fatal**. Can occur during loading, after a policy abort or on context cleanup. Only marker 38 exists, after workflow failure; it cannot be the initial producer that already caused termination |

### Every route rejection and caught-input path

The route handler covers the entire context, not just the main document. While Dashboard verification is pending, `phase` remains the string **`login`**; transition to `idle` follows complete acceptance and was not reached.

| Route condition | Source and precise rule | Evidence distinction |
| --- | --- | --- |
| Invalid request access/URL parsing | Driver line 42: `route.request()`, `req.url()`, `new URL(...)` may throw | Generic route catch; no closed cause retained |
| Wrong POST endpoint | Driver line 45: POST pathname must equal the page-advertised validated endpoint | Silent catch/fatal; no rejected POST-forward marker |
| Unparseable POST body/snapshot | Driver line 46, `req.postDataJSON()`; [policy](../scripts/uiux-b2-reviewer-policy.mjs:3) uses `JSON.parse(c.snapshot)` | Generic parse/access exception reaches same catch |
| Wrong component cardinality | Policy: body components must be an array of exactly one | Same silent catch |
| Invalid snapshot/call shape | Policy: memo ID present, memo name string, calls array exactly one; malformed/null call accesses can also throw | Same silent catch |
| Disallowed component/action/arguments/updates | Policy permits only exact notification dispatch with zero updates, or exact Login `authenticate`, empty params and only the three allowed Login updates while phase is `login` | An automatic component call outside these contracts would be rejected; its occurrence, identity and method are **not observed** here |
| Consequential resubmission | Driver line 48: non-notification request rejected if `submitted` already true | Login forwarding set submitted true; another qualifying login would fail. Notification exemption remains exact; no evidence of a second submission |
| Unsupported HTTP method | Driver line 50: methods other than GET/POST rejected | No rejected method retained; do not infer one |
| Unexpected admin GET | Driver line 51: pathname starts `/admin` but is not one of the three allowed exact paths | No rejected pathname retained |
| Other synchronous handler failure | Includes synchronous request API/marker/route-call exceptions within the try block | Cannot distinguish policy rejection from an incidental synchronous exception using current evidence |

The policy also contains an `advance` branch requiring an object phase with fixed IDs/locks/transitions. This driver only sets string phases `login` and `idle`; it never supplies that object or calls the lifecycle action. The branch is therefore not an active lifecycle allowance in Attempt 11. A lifecycle-style POST during this run would fail policy, not gain permission.

`return route.continue()` and `return route.abort()` are **not awaited inside the try**. Their later asynchronous promise rejections are not automatically caught by that lexical catch. Such a rejection is not independently a demonstrated call to `fatal`; a subsequent requestfailed callback could be one. Do not classify every route-operation rejection as a silent catch without this distinction.

## Propagators and non-producers

| Source | Role and limits |
| --- | --- |
| Shared candidate `basic()`, `check()`, catch and `Promise.race` | Reads existing fatal flag / races existing failure promise, promotes `EXISTING_FATAL_EVENT` ahead of other classification. It never sets the driver's flag. Missing metadata, document change, destination change, deadline and persistence errors can fail verification independently; none manufactures fatal true |
| Attempt 11 navigation adapter | Invokes fixed root diagnostics and bounded persistence. Does not set fatal; failure sample may be unavailable because the caller flag is already true |
| Root observer | Reads fatal before/after collection; skips evaluation on an already-set flag. A true record is an observation, not a new fatal event |
| Login readiness helper | Races/checks the same signal. No fatal assignment; readiness had passed before the observed failure |
| Workflow outer race/catch | Reports the first workflow/shared failure. Sanitization collapses many different Error messages to RESTRICTED; that category cannot recover the producer |
| Driver cleanup | Joins started verification, disposes returned candidate resources, closes context/browser. Closing a context can lead to requestfailed callbacks; it cannot explain the already-true failure-boundary flag in this run |
| Router/bootstrap | [Router](../scripts/uiux-b2-attempt-11-server.php:9) bootstraps guards before static bypass/application dispatch. Guard/identity/session/target exceptions or request handling failures may indirectly cause HTTP/request failures, but PHP has no reference to Node's fatal closure. Source overhead alone does not set fatal |
| Launcher | Handles pins, server ownership, child exits and postflight; no access to driver flag. It stops its server after browser child exit. That later shutdown cannot initiate this driver's earlier fatal observation |

This inventory covers all direct setters in the pinned driver and the surrounding reviewed propagators. It does not infer unretained framework/runtime exceptions or claim an arbitrary external fault is impossible.

## Coverage, attribution and cleanup interpretation

**Classification: NARROWED — multiple specific route-handler candidates remain.** Confidence is high in the source-level narrowing under the retained pinned workflow; confidence in any particular route reason is insufficient.

Reasoning: fatal true requires one of the five call sites. The three listener-based producers record their marker before calling fatal. There are **38 events, zero dropped**, no page-error record, no HTTP error and no requestfailed marker before workflow termination. The remaining producer family is the unmarked route external-origin branch or route catch. That supports narrowing to a family, not confirmation of a request, policy rule, asset, origin or method. Restricted Error metadata cannot distinguish their messages.

Marker 38 is **shutdown-correlated and not initiating**. It could be a delayed effect of the earlier route abort or a result of context closure; the retained evidence cannot assign it uniquely to cleanup. Its recorded pathname is the **current page path** from `diagnosticContext()`, not the failed request URL: the listener does not receive/retain the failed request. `/admin` therefore cannot identify the failed resource. No raw URL, resource type, request body, missing DOM or exception was reconstructed.

No application defect, authentication result, particular resource-delivery failure or policy relaxation is justified by this assessment. The final fatal code says the fail-fast signal prevailed, not why that signal was first raised.

## Smallest diagnostic-only proposal — not implemented

Add a single **first-fatal record**, synchronously before setting the existing flag/rejecting the existing promise. Producers supply only fixed reason enums; retain the first record unchanged when later callbacks arrive. Proposed closed reasons:

- `EXTERNAL_ORIGIN_BLOCKED`, `POST_ENDPOINT_BLOCKED`, `POST_BODY_INVALID`.
- `COMPONENT_COUNT_INVALID`, `SNAPSHOT_OR_CALL_INVALID`, `COMPONENT_ACTION_BLOCKED`.
- `CONSEQUENTIAL_REPEAT_BLOCKED`, `HTTP_METHOD_BLOCKED`, `ADMIN_PATH_BLOCKED`, `ROUTE_HANDLER_EXCEPTION`.
- `PAGE_ERROR`, `HTTP_ERROR`, `REQUEST_FAILED`.

Differentiate explicit validation branches at their existing checks; use the generic route-handler category for unexpected failures. Do not classify by parsing an arbitrary exception message or export observed component/method names. Persist only a bounded sequence/monotonic time, closed reason, existing safe phase, allowlisted current-page pathname, and a cleanup-started boolean. The first-fatal latch must not be overwritten by shutdown events. A cleanup flag can annotate later requestfailed observations without suppressing them or changing their fatal behavior.

The existing event recorder rejects unknown event types, so a future reviewed implementation would need an explicit closed recorder/schema update or a separately validated closed field. It must retain existing fatal precedence, signal timing, policy restrictions, original error handling and all acceptance conditions. It adds no waits, retries, actions, credentials or raw diagnostic capture. Bounded persistence faults must not replace the original failure. This is a proposal only; no helper, producer, policy or recorder was changed.

## Static validation and preservation

**PASS: 45 text/hash/retained-evidence assertions**, using private `gate-b2-attempt-11-fatal-attribution/check.mjs check`. These verify all 24 runtime pins and manifest, five fatal call sites, the sole true assignment, route-catch and listener ordering, policy rejection presence, retained event/error coverage, fatal observations and unfinished shared stage. No protected source module was imported or executed; no browser/mock-browser, database, Laravel or service command ran. No broad regression suite was repeated.

Manifest remains `b93c98c5227d5987687b1f271c4956252e88eaa9c9a565ab52315cdc10f16b8a`; shared candidate remains `49f91c5697e392a85198c8c3ba423e5927765bccb6e131772ac3cf57c4b6861a`.

Fresh focused preservation: **7,987/7,987 unchanged**, zero changed/missing/unavailable. It includes all Attempt 11 execution/session bytes, earlier attempts and preparation records, original credentials/ledger and identity history, protected application/framework sources, logs/caches/artifacts and previous PDF/diagnostic inventories. Credentials/session files were hashed only. Historical exceptions remain unchanged. This is not a whole-cluster or whole vendor/node_modules claim. No source correction or database-state assertion was made.

New artifacts are this report and private filesystem/static-check/baseline/comparison records outside the immutable attempt directory. Attempt 11 was not edited, appended to or reused.

**One recommended next action:** separately authorize implementation and focused offline/disposable validation of the first-fatal closed-category diagnostic proposal, leaving all request restrictions and acceptance behavior unchanged. Do not prepare or execute another protected attempt yet.

**STOP.** No protected browser/authentication, database login/SQL, Laravel bootstrap, service operation, source correction, Attempt 12 preparation/execution, lifecycle action, migration/PDF, B3/B4, commit, push or deployment occurred.
