# Gate B2 — First-fatal diagnostic validation

**29 October 2026. PASS.** An isolated diagnostic candidate passed focused offline checks and one fresh **17/17-case Chromium matrix**. The first closed cause is captured before the existing fatal signal, survives later callbacks and persistence failure, and leaves request authorization unchanged. Suitable for separately authorized Attempt 12 integration review; no protected integration or successor package was prepared.

## New files and source identity

Applied OSDS Verification Gates, Evidence Preservation and Livewire Diagnostics. New public tooling:

- `scripts/uiux-b2-first-fatal-candidate.mjs`: first-fatal latch, rejection attribution adapter and browser-listener adapter.
- `scripts/check-uiux-b2-first-fatal.mjs`: deterministic latch/policy/route checks and shared-readiness replay.
- This report. New private fixture, baseline/comparison and bounded browser results are under `gate-b2-first-fatal-validation/`.

Candidate SHA-256, identical before and after browser validation:

`7f53af6bd54f80ad92b5297d5a81d517f49905ac365bde38806dff544e17c484`

No protected attempt imports it. Attempt 11 and earlier packages/evidence, the original request policy, shared Dashboard candidate, application/framework and credentials remain unchanged. The existing event recorder was not edited; this separate candidate has an explicitly closed record schema.

## Closed schema and signal behavior

Each record contains exactly `reason`, `sequence`, `elapsedMs`, `phase`, `pathname`, `cleanupStarted`. Reason is one of:

```text
EXTERNAL_ORIGIN_BLOCKED
POST_ENDPOINT_BLOCKED
POST_BODY_INVALID
COMPONENT_COUNT_INVALID
SNAPSHOT_OR_CALL_INVALID
COMPONENT_ACTION_BLOCKED
CONSEQUENTIAL_REPEAT_BLOCKED
HTTP_METHOD_BLOCKED
ADMIN_PATH_BLOCKED
ROUTE_HANDLER_EXCEPTION
PAGE_ERROR
HTTP_ERROR
REQUEST_FAILED
```

The first immutable record is latched synchronously **before** calling the supplied existing fatal signal. Every subsequent valid invocation still calls that signal; it does not overwrite the first record. Later records remain observable in a separate bounded list (maximum 64); callbacks beyond the record cap still signal. Sequence caps at 65,535; the monotonic clock value is clamped to 0–86,400,000 ms. `elapsedMs` uses the supplied monotonic clock, not a browser/server timestamp or guaranteed workflow-relative origin. The production caller must preserve its chosen origin when integrating.

Phase is restricted to existing login/idle diagnostic phases or `restricted-phase`. Pathname is restricted to the three existing admin paths at the fixed caller base; query/fragment, external origin and all other paths are omitted or reduced to `/[other]`. Cleanup is a boolean. Unknown reason input throws a fixed error before recording or signaling; it cannot be recorded as arbitrary text or produce a successful verification return. Future callers must preserve ordinary fail-closed exception handling.

No request URL/body, component/method name, DOM/snapshot, credential, cookie, session, error text or stack is returned in a diagnostic record. Original policy errors are rethrown in memory; they are not serialized. Fixed failure categories are never derived from exception-message parsing.

### Request-policy preservation

`validateRequestAttributed()` delegates to the **original `validateRequest()`**. Only after that function rejects does it classify the rejected shape; it never makes a new authorization decision. Invalid body/snapshot JSON maps to `POST_BODY_INVALID`, cardinality to `COMPONENT_COUNT_INVALID`, invalid memo/call shape to `SNAPSHOT_OR_CALL_INVALID`, and otherwise disallowed component/action contracts to `COMPONENT_ACTION_BLOCKED`.

The isolated route adapter retains the existing origin, POST endpoint, method, admin-path, single consequential submission and exact notification exemption checks. The handler still returns the original route-operation promises without awaiting them inside its try; asynchronous continuation/abort rejection is not reclassified as a synchronous catch. The generic catch covers unexpected synchronous handler faults. Browser listener ordering retains the existing marker/error callback before its fatal signal.

The candidate does not set authentication, change a Dashboard predicate, wait, navigate, fill credentials, click Sign in or grant lifecycle authority. It depends on the future caller's unchanged fatal signal and reviewed phase/endpoint state. This task did not build that protected caller.

## Offline validation

Executed `node scripts/check-uiux-b2-first-fatal.mjs`:

- **PASS: 270 focused checks**, including every category, capture-before-signal order, immutable first cause, later/near-simultaneous callbacks, route rejection followed by requestfailed, cleanup annotation, unknown category rejection, bounded schema/caps and private-data exclusion.
- **36 policy comparisons** against the original validator across login, idle and the existing object-phase contract. Acceptance/rejection and returned kinds matched. These are focused examples, not an exhaustive proof over every JavaScript value.
- **12 controlled route cases** verified rejection/abort or allowed continuation, including generic caught exceptions and unchanged notification behavior.
- **66 checks across 11 shared-readiness orchestration cases** passed when replaying the actual unchanged shared candidate with the new latch providing its existing fatal flag. Fatal still prevailed; negative cases did not confirm acceptance or navigate.

Existing regressions also passed: **65 shared-readiness checks / 11 cases**, and **98 diagnostics/request-policy/source checks**. Candidate/runner syntax passed. Additional offline schema tests were added after the browser run; the candidate itself was not edited or rerun in a browser. No protected driver, credentials, application module or database was executed by these suites.

## One disposable Chromium execution

Executed exactly once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-first-fatal-validation/run.mjs run
```

| Runtime / isolation | Actual result |
| --- | --- |
| Exit / aggregate | **0 / PASS** |
| Start / finish UTC | **2026-10-29 01:03:28.993 / 01:03:31.599** |
| Asia/Manila | **09:03:28.993 / 09:03:31.599** |
| Recorded interval | **2.606 seconds** |
| Node / installed Playwright | **v24.20.0 / 1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Contexts / cleanup | **17 fresh nonpersistent contexts; 17 closed; browser closed** |
| Unexpected page errors / isolation violations / unhandled rejections | **0 / 0 / 0** |
| Retries / candidate edits after browser run | **0 / 0** |

All documents/resources were synthetic and fulfilled through in-memory routes at `http://127.0.0.1:18140`; no HTTP server ran. The fixture had a closed same-origin resource list. Deliberate external-origin probes were intercepted and aborted before network continuation. Service workers/downloads were disabled; WebSockets were blocked. No OSDS origin, real credentials, existing profile/session, PostgreSQL or Laravel was contacted.

Fresh exclusive evidence: `gate-b2-first-fatal-validation/browser-validation-01/`. Case observations were checkpointed before comparison with the expected reason and acceptance invariants. A failed assertion would retain its bounded actual state and stop the matrix. Individual checkpoint files retain `OBSERVED`; the aggregate records final PASS status. No raw exceptions, DOM, snapshots, arbitrary names/hosts, screenshots or unrestricted traces were persisted.

## Browser outcomes

Every actual first reason matched the expected reason:

| Case | Actual first reason | Result |
| --- | --- | --- |
| External-origin request | `EXTERNAL_ORIGIN_BLOCKED` | PASS |
| Wrong POST endpoint | `POST_ENDPOINT_BLOCKED` | PASS |
| Malformed POST body | `POST_BODY_INVALID` | PASS |
| Invalid component count | `COMPONENT_COUNT_INVALID` | PASS |
| Invalid memo/call shape | `SNAPSHOT_OR_CALL_INVALID` | PASS |
| Disallowed component/action | `COMPONENT_ACTION_BLOCKED` | PASS |
| Second synthetic consequential request | `CONSEQUENTIAL_REPEAT_BLOCKED` | PASS |
| Unsupported HTTP method | `HTTP_METHOD_BLOCKED` | PASS |
| Unexpected admin path | `ADMIN_PATH_BLOCKED` | PASS |
| Deliberately thrown synchronous route callback error | `ROUTE_HANDLER_EXCEPTION` | PASS |
| Deliberate page error | `PAGE_ERROR` | PASS |
| Synthetic HTTP 500 | `HTTP_ERROR` | PASS |
| Deliberate request failure | `REQUEST_FAILED` | PASS |
| External rejection, request failure, then page error | `EXTERNAL_ORIGIN_BLOCKED` | PASS |
| Cleanup-owned abort of held synthetic request | `REQUEST_FAILED`, cleanup true | PASS |
| First-cause persistence callback fails | `EXTERNAL_ORIGIN_BLOCKED` retained | PASS |
| Exact allowed notification and synthetic navigation | No fatal record | PASS |

All **16 negative cases** retained false frozen test-only authentication state and zero completed navigation. All cases performed zero protected navigation/business actions. The positive case completed one allowed synthetic navigation, retained no fatal signal and left authentication false; it is not a real authentication test. Synthetic Login bodies were fixtures without credentials and never reached a server. The repeated-request case proves the second request was rejected rather than admitting another protected submission.

## Precedence, cleanup and persistence

Route-rejection cases observed the original policy abort, then a real Playwright requestfailed event. Both invoked the fatal signal, but the route reason remained first. The multiple-signal case recorded three invocations and retained its original external-origin category through a later real page error. This validates the specific ambiguity exposed by Attempt 11 without claiming its historical trigger was external.

The cleanup control explicitly marked cleanup started and aborted a held synthetic request, producing a real requestfailed event tagged cleanup true. It does not claim every `context.close()` schedule emits that event. Context closure was also performed after verification in each case; the first record remained unchanged. Later callbacks are not suppressed.

`persistFirst()` gives the writer only the immutable closed first record. A writer rejection sets a bounded persistence-failed flag and returns false, leaving the fatal reason and signal unchanged. The browser test injects a rejecting callback, not a real disk/permission failure. A future integration must treat that false result as blocked evidence persistence while preserving the original fatal classification. The candidate does not guarantee evidence survival after process death or independently implement protected filesystem writes.

The helper cannot confirm authentication, cancel arbitrary losing operations or identify historical unrecorded requests. Trusted caller context and normal existing exception/fatal handling remain integration prerequisites.

## Preservation, suitability and stop

Fresh focused preservation: **7,992/7,992 baseline files unchanged**, zero changed/missing/unavailable. Attempts 01–11 and all execution/session evidence, preparation records, credentials/ledger and identity history, protected helpers/policy/shared candidate, application/framework sources, logs/caches/artifacts and earlier PDF/diagnostic inventories remain preserved. Credentials/session bytes were hashed only. No historical file was edited, appended to, cleared, restored or reconstructed; prior documented exceptions remain unchanged. This is a focused inventory, not a whole vendor/node_modules or live database-state claim.

**PASS: suitable for separately authorized Attempt 12 integration review.** No protected package was changed or prepared, and no real authentication result or Attempt 11 cause was inferred.

**One recommended next action:** separately authorize source integration and guarded preparation of a new one-shot package using this unchanged candidate, retaining all existing acceptance/policy predicates and explicitly handling evidence-write failure. No protected execution is implied.

**STOP.** No protected browser/authentication, database login/SQL, Laravel bootstrap, PostgreSQL operation, Attempt 11 reuse, Attempt 12 preparation/execution, personnel lifecycle, migration/seeding/repair, B3/B4, commit, push or deployment occurred.
