# Gate B2 — Root-availability disposable validation

**28 October 2026. PASS.** One fresh disposable-browser execution completed **24/24 cases**, covering all 20 requested categories and their subcases. The unchanged helper collected actual Chromium DOM metadata, distinguished unavailable observations from measured zero counts, and preserved authoritative fatal status. It is suitable for separately authorized Attempt 10 integration review; no protected package or execution was prepared.

## Source, command and runtime

Applied OSDS Verification Gates and Evidence Preservation. Used the exact existing `inspectRootAvailability` collector and `createRootAvailabilityObserver` adapter from `scripts/uiux-b2-root-availability.mjs`, without replacing their logic.

Helper SHA-256 matched before and after:

`68cc4348d7666fbe6c88b328b8972c84c0c5255c4c273c684f4fa55e3215fa4c`

Executed exactly once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-root-availability-validation/run.mjs run
```

| Fact | Actual result |
| --- | --- |
| Exit / result | **0 / PASS** |
| Start / finish | **2026-10-28 09:42:30.418 / 09:42:34.947 UTC** |
| Asia/Manila | **17:42:30.418 / 17:42:34.947** |
| Recorded interval | **4.529 seconds** |
| Node / local Playwright | **v24.20.0 / 1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Contexts / cleanup | **24 fresh nonpersistent contexts; 24 closed; browser closed** |
| Unexpected browser errors / isolation violations | **0 / 0** |
| Recorded unhandled rejections | **0** |
| Retries / helper edits | **0 / 0** |

Local Chromium availability, source hash and unused evidence directory were checked before launch. No package/browser was installed or downloaded.

## Isolation and evidence

The fixed helper compares destination against `http://127.0.0.1:8044/admin`. The runner required port 8044 to be free, then fulfilled that synthetic origin **entirely through Playwright's in-memory routes**; no OSDS server was started or contacted. A second fixed synthetic loopback origin at port 18110 was used only for wrong-origin testing and was also checked free.

The closed policy admitted only GET `/admin`, `/away` and, in the readiness fixtures only, `/gate.js` at those two origins. All other origins, ports, methods, queries and paths were rejected. WebSockets were closed and treated as violations. Service workers/downloads were disabled; no existing browser profile, credentials, cookies or sessions were loaded. No external network or database/application bootstrap occurred.

Fresh exclusive evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-root-availability-validation/browser-validation-01/`

Each bounded observation was checkpointed before comparison with its expected result. The evidence includes expected/actual closed fields, availability/fatal flags, scenario identifier, unchanged false authentication flag and navigation/business-action counts. Final aggregate status marks each case PASS/FAIL; pre-comparison checkpoints are labelled OBSERVED. Both samples in the two-phase cases were checkpointed separately. No raw DOM, snapshot text, arbitrary component name, ID value, exception, cookie/session data, screenshot or unrestricted trace was retained.

New files are the private runner, filesystem preservation helper/baseline/comparison, exclusive validation evidence and this report. No existing source or evidence file changed.

## Actual scenarios

All rows below passed; related subcases account for the 24 contexts.

| Requested coverage | Observed result |
| --- | --- |
| 1. No roots | ID and snapshot counts zero; **availability true** |
| 2. ID root without snapshot | ID 1, snapshot 0, missing-snapshot 1 |
| 3. Dashboard | Exact Dashboard count 1 |
| 4. Login | Exact Login count 1, Dashboard 0 |
| 5. OTHER | Other count 1; arbitrary identity not exported |
| 6. Dashboard plus OTHER | Both classified independently; total counts capped correctly |
| 7. Snapshot-only root | ID 0, snapshot 1, Dashboard 1 |
| 8. Malformed JSON | Malformed true; no valid component count invented |
| 9. Missing/non-string memo name | Both separate cases set malformed true |
| 10. Multiple roots | ID, snapshot, missing-snapshot and Dashboard/Login/OTHER counts all cap at 2 |
| 11. Document readiness | Actual **loading** and **interactive** states observed in two controlled cases; ordinary completed documents reported **complete** |
| 12. Wrong origin/path | Separate cases returned destination false; no navigation acceptance |
| 13. Fatal before evaluation | Fatal true, unavailable; **zero collector evaluations** |
| 14. Fatal during evaluation boundary | Sample measured once; fatal true before adapter completion |
| 15. Evaluation failure/invalid output | Separate closed-page and extra-field controls returned explicitly unavailable records |
| 16. Repeated phase | Repeat and unknown phase rejected; no extra collector evaluation |
| 17. Both fixed phases | First sample observed no roots; synthetic DOM was changed; second observed one Dashboard root |
| 18. Unavailable versus observed zero | First sample zero roots/available; after page closure second sample zero defaults/**unavailable** |
| 19. Data minimization/shape | Exact closed keys and expected values matched; synthetic private payload, arbitrary identity and ID values absent from output |
| 20. Negative authentication/action effects | **All 24 cases retained false authentication confirmation, zero workflow navigation and zero business actions** |

The helper is diagnostic: a successful observation of Login, malformed metadata or a wrong destination is not acceptance. Tests verified matching diagnostic facts and no side effects; they did not turn those facts into an authentication decision.

### Readiness and fault-control fidelity

Parser-blocking and deferred-script gates held synthetic resources explicitly. The real collector observed `loading` while a parser gate was held and `interactive` while a deferred script prevented DOMContentLoaded. Gates were released and normal loading completed before cleanup. Fixture synchronization used bounded semantic state waiting, not arbitrary sleeps; no observer polling or timeout increase was added.

The during-evaluation fatal case collected real DOM data, delivered a deliberate browser page error while the adapter's evaluation dependency remained pending, then allowed it to settle. It verifies authoritative Node fatal status across that async boundary; it does not claim an event interleaved inside the collector's synchronous JavaScript instructions. Fatal-before used a real page error before observation and confirmed collection was skipped.

Evaluation failure used a closed disposable page, causing an actual Playwright evaluation rejection. Invalid output injected one extra field after the real collector returned, verifying adapter shape rejection without altering the helper or DOM engine. Synthetic errors were negative controls, not expanded protected error permissions.

## Two-phase behavior and limits

The two-phase case invoked `AFTER_ADMIN_DESTINATION` and `DASHBOARD_FAILURE` once each on **one adapter instance**. Exactly two evaluations captured distinct document states. Repeated/invalid phase attempts were rejected without collecting again. The unavailable-versus-zero case used the same two-phase pattern with page closure between samples. Fatal-before performed no evaluation, and fatal-during retained the measured sample with fatal true.

Main-frame navigation was counted after fixture setup; every count remained zero during observation. No business action was available or performed. The frozen false authentication state was untouched in all cases. After context cleanup and a Node microtask drain, no authentication change or unhandled rejection was observed. These tests do not establish universal scheduling/cancellation guarantees.

Default unavailable counts/booleans remain unobserved, not measured absence. Readiness or root counts cannot establish authentication, predict later mounting or identify an arbitrary OTHER component. The helper still does not traverse frames/shadow roots, schedule its own boundaries, change waits or accept a different page identity.

## Regression and preservation

Executed the required unchanged suite:

```powershell
node scripts/check-uiux-b2-root-availability.mjs
```

**PASS: 68 checks**, before browser execution. Helper hash remained identical afterward; runner syntax passed. No broad regression suite or protected entry point ran.

Fresh focused baseline: **5,005/5,005 files unchanged**, zero modified/missing. Scope preserves attempts 01–09 and session/evidence bytes, historical identity records, original credentials/ledger, existing drivers/helpers/pins and acceptance conditions, application/framework/public sources, protected logs/caches/artifacts, Batch G, all 81 previously inventoried PDF-named files and ten historical diagnostic JSON files. Credentials/session bytes were hashed only.

No evidence/log/cache was cleared, restored or truncated. Prior historical append/missing-metadata exceptions remain unchanged. This is a focused inventory, not a whole vendor/node_modules or database-state assertion.

## Decision and stop

**PASS: suitable for separately authorized Attempt 10 integration review.** Actual DOM tests support the existing helper/adapter semantics without source changes. A future integration must keep both fixed observation boundaries, retain availability flags, preserve fatal precedence and leave every authentication/Dashboard/sidebar/personnel acceptance condition unchanged. This report does not authorize or prepare that integration or any protected execution.

Attempt 09's historical cause, real reviewer authentication and actual protected Dashboard rendering remain unresolved. No observation result alone authorizes a retry or personnel lifecycle work.

**STOP.** No protected browser/authentication, PostgreSQL/Laravel bootstrap, Attempt 09 reuse, Attempt 10 preparation/execution, COR/ACSA lifecycle, migration/seeding/repair, PDF/directory recreation, B3/B4, commit, push or deployment occurred.
