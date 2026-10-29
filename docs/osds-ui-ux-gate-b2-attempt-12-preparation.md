# Gate B2 — Attempt 12 first-fatal integration and guarded preparation

**29 October 2026. READY at this snapshot for separately authorized reviewer login/read-only-navigation execution.** Source integration, focused offline tests, source pins and guarded read-only preparation inspection PASS. No protected browser/server or authentication occurred. Attempt 12's execution directory remains absent. COR 47 and ACSA 48 remain draft/lock 1; no lifecycle authority is implied.

## New package and final hashes

Applied OSDS Verification Gates, Evidence Preservation and Livewire Diagnostics. Reused the reviewed Attempt 11 architecture and [validated first-fatal candidate](osds-ui-ux-gate-b2-first-fatal-diagnostic-validation.md). No historical package/evidence was edited or reused as a new output destination.

| New file under `scripts/` | Final SHA-256 |
| --- | --- |
| `Run-UiUxB2Attempt12.mjs` | `2351f21191e3fc45a6c39120837c1641d7ddc9045869c0972d3ac439cb4be2f4` |
| `UiUxB2Attempt12Bootstrap.php` | `23fdee28796b00e1af0fe3db325eb80a31b679dd10c12f3103b014c51decceb5` |
| `Inspect-UiUxB2Attempt12.php` | `fc7113883dc96d701728f68dca186367e3e1a0b44e951d4767903077c47736cf` |
| `uiux-b2-attempt-12-server.php` | `422c6da2745696a4c532a5da46fb2c50237922cd91a45ab05c170b3019279622` |
| `verify-uiux-b2-attempt-12.mjs` | `b9bbb63c07bcafc8bcd5f5f95f44fb7dce32f22cfffd5602828c5a70a4e84dfe` |
| `uiux-b2-attempt-12-navigation.mjs` | `2436e68736854d06d3936d9658041f5a8f0f300a79462b10f1667069946757d8` |
| `uiux-b2-attempt-12-fatal.mjs` | `5374e4d5ce33a590555f98690c58bade181c396adc7a8aead22b8bc2904e2fa1` |
| `uiux-b2-attempt-12-pins.json` | `8d6113a3021069cee7a966382f1422110bc17306270afaa64cf1fadec0fb6a46` |
| `check-uiux-b2-attempt-12.mjs` | `daba0c045c8d2458789eb83781e331ee778a472f11414ec5e1ebcbcc2343b110` |

Required candidate hashes remain unchanged:

- First fatal: `7f53af6bd54f80ad92b5297d5a81d517f49905ac365bde38806dff544e17c484`.
- Shared Dashboard readiness: `49f91c5697e392a85198c8c3ba423e5927765bccb6e131772ac3cf57c4b6861a`.

All **26 source/framework pins match**. They extend the prior 24-source contract with the first-fatal candidate and new persistence-orchestration module. The manifest retains passing validation provenance, including the exact 17-case first-fatal result-file hash. The checker is separately hashed, outside runtime dependencies. Existing protected dependencies were verified before copying; none was changed or automatically repinned. Private initial/finalization records are retained; only the final manifest hash above applies to future execution. Future operators must verify that hash externally as well as all source pins.

Launcher/bootstrap/inspector/router/navigation copies are byte-identical to Attempt 11 after necessary attempt names, constants, function identifiers and path substitutions. The new driver replaces only fatal routing/listener wiring, adds persistence/cleanup coordination and shares an explicit caller clock. The candidate, policy validator, readiness/root helpers, shared Dashboard logic and application/framework remain unchanged.

## First-fatal integration

The driver uses the unchanged candidate's `createAttributedRoute` and `installAttributedListeners`, and the new attempt-specific `createAttempt12Fatal` wrapper. The original validator remains the authorization authority. Attribution inspects a rejected shape only after the original policy rejects; no error-message parsing grants or classifies access.

The first immutable record is captured before the original fatal flag/rejection. The driver preserves the old signal's Error wrapping (`Error(error.message)`), including when a caught parser error was a SyntaxError. Every later producer still signals; none overwrites the first cause. Request continuation/abort promises retain the existing unawaited return behavior. All direct route/listener producers use closed reason categories.

The recorder and first-fatal candidate receive the same `diagnosticClock`, whose first call establishes one monotonic caller origin. This avoids introducing a second diagnostic timestamp origin. It is independent of, and does not alter, the shared readiness candidate's existing monotonic budget.

`firstFatal.startCleanup()` runs at driver cleanup entry. Started verification/root operations are still joined; returned candidate disposal and task-owned context/browser cleanup remain reachable. Fatal callbacks remain active and their later closed records stay observable in bounded `fatalObservations` (maximum 64). Cleanup does not replace an earlier record.

## Closed evidence and write-failure behavior

On the first fatal invocation, the driver starts exactly one exclusive write to future attempt-local **`first-fatal.json`**, before returning to the producer. It is not deferred until successful workflow exit. The synchronous writer is:

```javascript
fs.writeFileSync(dir + '/first-fatal.json', JSON.stringify(record), {flag: 'wx'});
```

Its payload has exactly:

| Field | Restriction |
| --- | --- |
| `reason` | One of the 13 validated fixed categories |
| `sequence` | Bounded integer, maximum 65,535 |
| `elapsedMs` | Shared caller monotonic clock, clamped to 0–86,400,000 |
| `phase` | Existing closed login/idle phases or `restricted-phase` |
| `pathname` | Three allowlisted current-page admin paths or `/[other]`; no query/fragment/host |
| `cleanupStarted` | Boolean |

The reason set remains exactly `EXTERNAL_ORIGIN_BLOCKED`, `POST_ENDPOINT_BLOCKED`, `POST_BODY_INVALID`, `COMPONENT_COUNT_INVALID`, `SNAPSHOT_OR_CALL_INVALID`, `COMPONENT_ACTION_BLOCKED`, `CONSEQUENTIAL_REPEAT_BLOCKED`, `HTTP_METHOD_BLOCKED`, `ADMIN_PATH_BLOCKED`, `ROUTE_HANDLER_EXCEPTION`, `PAGE_ERROR`, `HTTP_ERROR`, `REQUEST_FAILED`. Unknown reason input retains the candidate's fixed fail-closed rejection.

No raw request URL/hostname/body, arbitrary component/method name, DOM/snapshot, credential, cookie, session, exception text or stack is added to this record. Existing sanitized failure reporting is unchanged. The same closed first record is retained on the final result object, with later closed observations separately bounded.

The wrapper sets an attempt-once flag before writing. A writer failure sets **`firstFatalEvidencePersistenceFailed=true`** and BLOCKED status, preserving the original first reason and `verificationConditionFailure`. No overwrite or retry of the failed file occurs, including during cleanup. The driver settles persistence at cleanup entry and again before final result writing; the latter enforces BLOCKED even if later code had assigned PASS. These are completion checks for the same write, not retries or UI waits.

Exclusive creation is not an atomic-content or crash-survival guarantee. A partial file on a real filesystem fault must remain preserved; failure flags cannot manufacture missing bytes. No such write was performed against a protected attempt during preparation. Offline faults used synthetic filesystem dependencies.

## Acceptance and request boundaries retained

Byte comparisons verify the workflow from Login navigation through personnel assertions is unchanged after attempt substitutions. It retains:

- Both readiness checkpoints, one email fill, one password fill and exactly one Sign in click.
- Fixed origin, methods, page-advertised endpoint and exact component/action/argument/update policy; no broadened notification exemption or additional consequential submission.
- Exact `/admin` state and the one shared **30,000 ms** destination/Dashboard metadata budget; no additional independent metadata timeout.
- Exact unique Dashboard identity, no unexpected Login/form/malformed metadata, exact visible level-one **Your workspace**, and unchanged heading bounds.
- Unique sidebar container/link, exact role/name/href, visibility/actionability and the same validated single-click closure.
- Personnel URL/component and existing 47/48 draft/version heading checks.
- Root observations at `AFTER_ADMIN_DESTINATION` and applicable `DASHBOARD_FAILURE`, at most once each; original closed schema and availability semantics.
- Existing fatal/document/destination precedence and no lifecycle action/phase in the driver.

Authentication confirmation still follows complete Dashboard **and sidebar** success. New diagnostic records, HTTP 200, URL equality and root availability cannot confirm it. No retry, navigation bypass, sleep or larger timeout was added.

## Focused validation

| Actual execution | Result |
| --- | --- |
| `node scripts/check-uiux-b2-attempt-12.mjs` | **PASS: 308 checks** |
| First-fatal suite | **PASS: 270 checks**, 36 policy comparisons / 12 route cases |
| First-fatal/shared-readiness replay | **PASS: 66 checks / 11 orchestration cases** |
| Unchanged shared readiness regression | **PASS: 65 checks / 11 cases** |
| Diagnostics/request-policy/source | **PASS: 98 checks** |
| Login readiness / sidebar navigation | **PASS: 70 / 20 checks** |
| Root availability / dashboard diagnostics | **PASS: 68 / 56 checks** |
| JavaScript syntax | **PASS**, new launcher/driver/navigation/fatal modules |
| PHP syntax | **PASS**, new bootstrap/inspector/router |
| Guarded preparation inspection | **PASS**, one invocation, exit 0 |
| Fresh browser validation | **NOT RUN** |

The new integration suite executes the **actual driver wiring extracted as text**, with synthetic page, filesystem and fatal-signal dependencies; it does not import the protected driver. It covers every first-reason category, all three listener producers, immediate write initiation, capture-before-signal behavior via the unchanged candidate, generic route catch, cleanup-first-cause preservation and write failure. It compares **11 route cases against the actual extracted Attempt 11 route handler**, including repeated submission, notification and allowed traffic; rejection/continuation decisions match. Negative cases retain false authentication and no navigation/business actions. Old Error wrapping and exclusive `wx` writes are checked explicitly.

No new disposable matrix was necessary: routing/listener/latch code and DOM predicates are unchanged from the validated candidate; integration adds caller wiring, a common clock and filesystem persistence, tested offline with the actual extracted code. The existing **17/17 Chromium PASS**, candidate hash and result-file provenance were verified, not rerun or counted as new browser results. These checks do not certify protected rendering or credential acceptance.

## Actual guarded read-only preparation inspection

Separate preparation reservation/session directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-12-preparation/inspection-01/`

Executed once using portable PHP in the host-reachable context, after `pg_ctl status` verified the original cluster PID **10380** and a bounded TCP connection passed:

```powershell
php scripts/Inspect-UiUxB2Attempt12.php prepare
```

| Boundary | Actual result |
| --- | --- |
| Exit/status | **0 / PASS** |
| Fixed configured/observed connection | **pgsql / 127.0.0.1:55432 / osds_ui_browser_test** |
| Identity observations | **3/3 PASS**, **2026-10-29 05:04:27 UTC / 13:04:27 Asia/Manila** |
| Inspection backend PID | **11660** |
| CLI default read-only | **on**, before Laravel bootstrap |
| Transaction | Explicit **REPEATABLE READ READ ONLY**, observed `repeatable read` |
| Effective read-only before/after | **on / on** |
| Closure | **ROLLBACK** |

Pre-PDO target validation, actual identity before handoff/reconnect, cached-config/URL/environment/alias/routing rejection and denial of other drivers are unchanged. Guarded Laravel CLI bootstrap was used solely for the reviewed model-based verifier. No service operation, authentication/business handler, alternate database or mutation probe ran.

| Protected material | Fresh finding |
| --- | --- |
| COR 47 / module 1 | **draft / lock 1**, creation history **237**, actor **1191** |
| ACSA 48 / module 2 | **draft / lock 1**, creation history **238**, actor **1191** |
| Original histories/audits, content and immutable bindings | Prepared exact comparisons **PASS**; no review/publication history |
| Directory 26 | Latest/exact office map check **PASS** |
| Protected B0 roots, unrelated rows/business counts | **PASS**, unchanged under reviewed inspector criteria |
| Fixed roots | COR **198/591/54/43**, ACSA **199/592/55/45**, preview **128**, historical mappings preserved |
| Foundation unions | **18 accounts, 154 permission pivots, 2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments, 20 reviewer assignments** |

Reviewer **1192** matched the existing active synthetic identity and exact foundation manifest: `platform.admin.access`, `questionnaires.view`, `questionnaires.review`, `questionnaires.publication.view`, `issuance.definitions.review`, `issuance.definitions.publish`, `documents.view`, `documents.personnel.review`, `documents.personnel.publish`. No permission, account, role or assignment changed. Possessing lifecycle permissions does not authorize their use. This verifies the prepared inspector's scope, not an independent full database audit.

## Preservation and execution boundary

Fresh focused comparison: **8,017/8,017 baseline files unchanged**, zero changed/missing/unavailable. It includes attempts 01–11 and all historical execution/session bytes, preparation/disposable evidence, original credentials/ledger and identity records, existing protected helpers/pins, application/framework/public sources, logs/caches/artifacts and prior PDF/diagnostic inventories. Credentials/session files were hashed only. No log/cache/evidence was cleared, restored, truncated or reconstructed; prior documented exceptions remain unchanged. No whole-cluster or whole vendor/node_modules byte claim is made.

Historical identity-log SHA-256 remains `5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B`. Fresh observations went only to the preparation inspection file. New artifacts are the nine package/check files, this report and private preparation/source/finalization/baseline/inspection/comparison records. Existing unrelated dirty files were left untouched. No protected source dependency was repinned.

Fixed future execution evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-12/`

**Absent before and after preparation; never reserved.** Only a separately authorized future launcher run may reserve it atomically. Previous attempts remain permanently occupied. Original credentials remain at their original foundation path, with no copy in new metadata or reports.

Future one-shot command — **not executed**:

```powershell
node scripts/Run-UiUxB2Attempt12.mjs run
```

**READY at this snapshot.** Future execution must freshly verify final manifest/pins, historical integrity, host reachability, dependencies, free port 8044 and unused path, then pass the launcher's fresh guarded preflight. Actual reviewer authentication, Dashboard/sidebar/personnel behavior and Attempt 11's exact initiating cause remain unresolved. The new diagnostic can distinguish an observable future producer; it does not recover historical missing evidence or authorize lifecycle operations.

**One recommended next action:** separately authorize exactly one unchanged Attempt 12 reviewer login/read-only-navigation execution, retaining all prerequisites, first-cause persistence, one-shot action limits and guarded postflight.

**STOP.** No protected browser/server/authentication, Attempt 12 execution reservation, Attempt 11 modification/reuse, COR/ACSA lifecycle, migration/seeding/repair/reset, PDF/applicant workflow, B3/B4, service restart, commit, push or deployment occurred.
