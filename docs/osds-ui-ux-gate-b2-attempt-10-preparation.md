# Gate B2 — Attempt 10 root-availability integration and preparation

**28 October 2026. READY for separately authorized reviewer login/read-only-navigation execution.** Integration, offline checks and guarded read-only preparation inspection PASS. No protected browser/server or authentication ran. Attempt 10's execution directory remains absent. COR 47 and ACSA 48 remain draft/lock 1; lifecycle acceptance is incomplete and B3/B4 remain closed.

## New files and final source identity

Applied OSDS Verification Gates and Evidence Preservation. Reused the reviewed Attempt 09 architecture and [validated root observer](osds-ui-ux-gate-b2-root-availability-disposable-validation.md). No completed investigation or disposable browser validation was repeated.

| New file | Purpose / final SHA-256 |
| --- | --- |
| `scripts/Run-UiUxB2Attempt10.mjs` | Atomic reservation, private sessions, fresh preflight, one-shot owned server/browser workflow, postflight and cleanup. `903c5db9c55899e6b560a739905e98d7093ed86bfc4242c162d5cc919e3b4997` |
| `scripts/UiUxB2Attempt10Bootstrap.php` | Fixed target, resolver/session/identity and read-only CLI guards. `e476224f71a8c93af580b8f4ca61290224407f325f8af27a94ef57222eca1861` |
| `scripts/Inspect-UiUxB2Attempt10.php` | Preparation/preflight/postflight with unchanged draft-only integrity criteria. `ac643e9dec204b77b0fbdec832acd62ce8976335a655c1b8c3c4342f0908b2e1` |
| `scripts/uiux-b2-attempt-10-server.php` | Future loopback router using the new bootstrap. `f044990c3aba1856b825a9e14f864f460ee66fede6916a6e437e9ab64841298a` |
| `scripts/verify-uiux-b2-attempt-10.mjs` | One observer, destination-boundary sample, bounded persistence and settlement before final reporting. `dfccc8407bd16f4d2795d21bb69908b43d728c2105a8c6c1b9a7f747f3a29c67` |
| `scripts/uiux-b2-attempt-10-navigation.mjs` | Dashboard-failure sample/rethrow; sidebar stage and click tail unchanged. `b45d7d9b7556050221f1261fd89e0011c03a7deeb60547fe202770cfebc8e8cd` |
| `scripts/uiux-b2-attempt-10-pins.json` | Fixed identity/path/scope, 23 source/framework pins and exact retained validation provenance. `c8953ddad0b7857ee4d1c774ef857d122d0b227ffbff5844394629e9423a3238` |
| `scripts/check-uiux-b2-attempt-10.mjs` | Static package checks and isolated orchestration/write-failure tests. `7e1e5e01ce43fb0eeb15a24c8cfc3b8f8da5f984e24f10012e1bc7838e6188f7` |

The root helper is unchanged at the required SHA-256:

`68cc4348d7666fbe6c88b328b8972c84c0c5255c4c273c684f4fa55e3215fa4c`

The validated dashboard candidate is also unchanged at `42090bfca5026a5a8461d825d7743a57fdd736e9eb1b7d142e68bfbf2cb8ae8d`. Readiness, original sidebar/collector, request-policy and framework sources remain unchanged and pinned. The manifest adds the root helper to Attempt 09's 22-source contract, for **23 matching pins**. The checker is not a runtime dependency; its hash is separately recorded above.

Launcher/bootstrap/inspector/router copies differ only by necessary attempt-specific names, paths, constants and entry points. Initial/finalization records are archived privately; only the final hashes above apply. The manifest's own final hash must be checked externally before future execution. Source pinning and non-overwrite rules do not imply filesystem immutability or authorize automatic repinning.

## Exact observation call sites

| Location | Integration |
| --- | --- |
| Driver line 55 | Creates **one** `createRootAvailabilityObserver({page, hasFailed})` inside the protected workflow. Both phases share it. |
| Driver line 108 | Calls `recordRoots('AFTER_ADMIN_DESTINATION')` immediately after the existing exact destination assertion/completion marker, before dashboard/sidebar verification. |
| Navigation helper line 35 | Calls `observeRoots('DASHBOARD_FAILURE')` only in the catch surrounding `diagnoseDashboardCandidate`, then rethrows the original dashboard failure. Sidebar-only failure does not trigger this phase. |

The unchanged adapter permits at most one invocation per fixed phase, including an unavailable observation. An unexecuted phase stays absent. No placeholder observation is inserted, no phase is retried, and no polling, UI wait, timeout extension, navigation or authentication action is added.

The driver retains an already-started navigation-verification promise so its dashboard-failure boundary can settle before final reporting. It closes the context and joins any already-started root-observation settlement before writing the final browser result. These are completion/cleanup coordination for existing operations, not new readiness waits, sleeps or additional samples.

## Closed persistence and error precedence

Each executed phase appends only the helper's closed record to the result and exclusively writes:

```text
root-availability-AFTER_ADMIN_DESTINATION.json
root-availability-DASHBOARD_FAILURE.json
```

Only executed phases have files/records. Contents remain capped ID/snapshot/missing-snapshot and Dashboard/Login/OTHER counts, malformed boolean, closed readiness, destination/fatal/availability flags and fixed phase. No arbitrary names, IDs, raw attributes/DOM/snapshots, credentials, cookies, session contents, exception text, screenshot or unrestricted trace is added.

On an observation-file write failure, the driver sets BLOCKED and `rootEvidencePersistenceFailed`; it throws a fixed persistence error. At the initial boundary, `ROOT_OBSERVATION_EVIDENCE_WRITE_FAILED` is assigned only if no existing verification code exists. At the dashboard-failure boundary, the original failure/code is preserved and rethrown even if persistence fails. Existing dashboard-checkpoint write failure likewise records its own boolean and preserves an already established verification code. Task-owned cleanup remains reachable.

A root record's availability/fatal values describe that observation boundary. Unavailable zero/default fields are not measured absence, and a prior nonfatal record is not rewritten when a later fatal event occurs. Observer results never determine authentication or PASS; evidence-write failure can only block the workflow. Existing fatal and destination precedence remains authoritative.

## Acceptance contract preserved

- Both password-readiness checkpoints; exactly one fill per credential field and one Sign in click.
- Exact `/admin` origin/path state assertion and existing bounds.
- Exact `App\Filament\Pages\Dashboard`, visible exact level-one **Your workspace**, hidden-inclusive heading classification, Login-component/form and malformed-metadata rejection.
- Existing request/endpoint policy, fatal listeners, no-retry handling and login/idle lifecycle boundaries.
- The exact unique sidebar locator, href/visibility/actionability checks and shared validated click closure; this entire sidebar stage/click tail is byte-identical to Attempt 09.
- Personnel URL, exact component and existing draft/version headings for 47/48.
- Authentication confirmation remains after complete dashboard **and sidebar** acceptance. Neither root counts nor HTTP 200 can confirm it.

## Fixed directories and actual inspection

Future execution evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-10/`

**Absent before and after preparation; not reserved.** Only a separately authorized future launcher run may atomically reserve it. Earlier attempts remain untouched. Original credentials stay at their original protected foundation path; no credential copy was created.

The authorized preparation inspection used its own exclusive reservation/private sessions at:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-10-preparation/inspection-01/`

Executed once with portable PHP:

```powershell
php scripts/Inspect-UiUxB2Attempt10.php prepare
```

**Exit 0 / PASS.** The launcher was not invoked. Guarded Laravel CLI bootstrap was required for the reviewed model-based verifier, after read-only defaults and actual identity checks. No authentication or business handler ran.

| Boundary | Fresh result |
| --- | --- |
| Fixed configured/observed target | **pgsql / 127.0.0.1:55432 / osds_ui_browser_test** |
| Identity observations | **3/3 PASS**, **2026-10-28 09:56:33–09:56:34 UTC / 17:56:33–17:56:34 Asia/Manila** |
| Inspection backend PID | **26448** |
| CLI default / effective read-only before and after | **on / on / on** |
| Transaction | Explicit **REPEATABLE READ READ ONLY**, observed `repeatable read` |
| Closure | **ROLLBACK** |

Pre-PDO configuration checks, actual identity before handoff/reconnect, cached-config/URL/alias/connector-routing rejection and other-driver denial remain unchanged. No forbidden/fallback database, mutation probe, migration, seeding, repair or recreation was used.

| Protected material | Fresh result |
| --- | --- |
| COR 47 / module 1 | **draft / lock 1**; history 237, `version_created`, actor 1191 |
| ACSA 48 / module 2 | **draft / lock 1**; history 238, `version_created`, actor 1191 |
| Original histories/audits and immutable bindings | Reviewed exact criteria passed; no review/publication history |
| Directory 26 | Latest revision/exact office map comparison **PASS** |
| B0 roots/protected rows/business counts | **PASS** |
| Fixed roots | COR 198/591/54/43, ACSA 199/592/55/45, preview 128 and historical mappings preserved |
| Foundation unions | 18 accounts, 154 permission pivots, 2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments, 20 reviewer assignments |

Reviewer **1192** matched the existing active synthetic account and exact permissions: `platform.admin.access`, `questionnaires.view`, `questionnaires.review`, `questionnaires.publication.view`, `issuance.definitions.review`, `issuance.definitions.publish`, `documents.view`, `documents.personnel.review`, `documents.personnel.publish`. No grant/role/assignment changed; possessing lifecycle permissions does not authorize using them.

Historical identity-log SHA-256 remains `5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B`. All fresh identity observations went to the separate preparation file.

## Offline verification and preservation

| Check | Actual result |
| --- | --- |
| Attempt 10 package/integration tests | **PASS: 88 checks / 7 orchestration cases** |
| Root availability | **PASS: 68 checks** |
| Dashboard diagnostics | **PASS: 56 checks** |
| Sidebar navigation | **PASS: 20 checks** |
| Login readiness | **PASS: 70 checks** |
| Diagnostics/request-policy/source tests | **PASS: 98 checks** |
| PHP syntax | **PASS**, bootstrap/inspector/router |
| JavaScript syntax | **PASS**, driver/launcher/navigation helper |
| Scoped whitespace | **PASS** |
| Guarded read-only preparation | **PASS**, exit 0 and rollback |
| Browser/protected authentication | **NOT RUN** |

Package checks verify all 23 pins, byte comparisons, one observer/call sites and unchanged request/login/sidebar/personnel boundaries. Seven pure orchestration cases execute the actual new navigation helper and extracted evidence-write function with controlled page/assertion/filesystem dependencies: valid flow, missing Dashboard, failure-boundary write error, initial-boundary write error, destination change, fatal event and sidebar-only failure. They confirm original dashboard failure survives write failure and negative cases cannot confirm authentication or click navigation. They do not import the protected driver or launch Playwright.

The prior 24-case browser result/helper hash and passing diagnostic provenance were verified, not rerun. No collection schema or validated diagnostic/sidebar predicate changed. No historical test result was overwritten.

Fresh focused baseline: **5,038/5,038 unchanged**, zero modified/missing. Attempts 01–09, credentials/ledger, identity history, protected application/framework/public sources, prior reports/disposable evidence, logs/compiled views, Batch G, all 81 previously inventoried PDF-named files and ten historical diagnostic JSON files remain preserved. No evidence/log/cache was restored, truncated or cleared; prior append/missing temporary-metadata exceptions remain unchanged. This is a focused inventory, not a whole vendor/node_modules claim.

New artifacts are the eight listed source/check files, this report and private preparation/generation/finalization/integrity/baseline/inspection/preservation records under `gate-b2-attempt-10-preparation/`. No pre-existing source or historical evidence changed.

## Future command and decision

Exact future command — **not executed**:

```powershell
node scripts/Run-UiUxB2Attempt10.mjs run
```

**READY at this snapshot, subject to separate one-shot execution authorization.** Future execution must reverify the final manifest/pins, historical log, dependencies, free port 8044 and unused path, then atomically provision and pass fresh guarded preflight before any browser action. No repair, repinning, reload, second submission or fallback attempt is allowed. Read-only postflight must preserve both drafts, directory and protected roots.

The new observations can distinguish observable root availability, not prove why Attempt 09 lacked its expected Dashboard. Real credential acceptance, session identity, Dashboard/sidebar/personnel behavior and the historical cause remain unresolved. A passing login/navigation run would not authorize any personnel lifecycle work or complete all B2 acceptance.

**STOP.** No Attempt 10 execution reservation, protected browser/server, authentication/reviewer POST, COR/ACSA transition, migration/seeding/repair, directory recreation, PDF/applicant workflow, B3/B4, commit, push or deployment occurred.
