# G-INT-006 - Atomic regional authority creation

**20 October 2026. Targeted development acceptance: PASS for integrity/security, with the noncritical accessibility follow-up below.** Regional-assignment creation only; Batch G is not complete. Pause for user review. No Batch H, Phase 4 or production work.

## Existing contract established before implementation

Inspected `CreateUserRegionAssignment`, its resource/form/policy, `UserRegionAssignment`, `CoreIdentityObserver`, its registration in `AppServiceProvider`, `AuditRecorder`, `AccessGovernance`, `AccountGovernance` and `ScopeAuthorizer`.

The existing resource/policy admits an active actor with `region_scopes.assign`. Staff-panel admission separately requires active staff and `platform.admin.access`. The model checks effective access to the exact selected region, rejects self-grants and checks `AccountGovernance::canManageTargetRole`: the actor must hold the configured creation permission for the target's existing role. Role names alone grant no authority. `ScopeAuthorizer` retains effective regional or effective explicit nationwide access, with existing start/end/revocation rules and UTC session behavior. No target-active or region-catalogue-active policy is invented.

The database already prohibits a second **unrevoked** user/region assignment, even if its dates have expired, and has the existing user/region/start uniqueness rule. Revoked history permits another grant with a different start. No new duplicate policy or request-idempotency guarantee is introduced.

`AccessGovernance::grantRegion` already owns a different service transaction and writes an additional domain event with a required reason; the actual generic Create page previously bypassed that method. Reusing it would change this form's fields and audit semantics. The new boundary preserves this path's existing single global-observer event instead.

## Root cause and narrow implementation

Originally the inherited Filament handler inserted the row before the registered synchronous observer audit, without an operation transaction. The real Create-action regression confirmed a grant surviving a thrown mandatory audit: **1 test / 7 assertions / 1 failure**. Discovery evidence remains unchanged.

`RegionalGrantCreationService::create` owns one default PostgreSQL transaction, without automatic retries. It validates the existing four creation fields; locks actor/target users in ID order, actor regional/nationwide authority rows and the selected region; locks existing role/permission membership rows; then rereads current actor/target authority before insertion. These locks concern the exact operation's identity, delegation and scope. Current membership deletions/revocations that began first must finish before authorization; deletions of locked existing memberships wait until this operation commits or rolls back. Fresh user instances avoid previously loaded Spatie relations. Existing user/grant governance lock ordering is reused where applicable; this is not a claim that every external SQL client follows a universal locking protocol.

The service checks authenticated actor identity, active state, explicit panel/assignment permissions, no self-grant, canonical region scope and target-role delegation. The unchanged model guard runs again. Caller-supplied attribution/revocation fields cannot enter the insertion; `granted_by` is the authorized actor. `CoreIdentityObserver` remains the sole mandatory writer, on the same PDO and operation transaction. There is no duplicate manual event, compensation delete, blanket panel transaction, schema change or historical repair.

The actual Create page delegates its protected persistence handler to the service. The existing support-reference handler redacts unexpected exceptions and logs only a fixed message, reference and exception class. A persistent `data.starts_at` explanation preserves entered fields. Normal authorization/validation denials retain their semantics. The existing staff network/session/uncertainty notice is additionally scoped to this page, offering saved-record inspection in another tab; it never automatically replays creation.

## Regression and concurrency evidence

The original named regression retains its actual Filament `create`, Eloquent model, registered observer, required once-only audit sink and exact no-persist rollback comparison. Its acknowledgement now checks the safe persistent form error/notification, rather than expecting the raw injected exception to escape. After the fix it passed **1 test / 13 assertions**.

The first expanded matrix passed **20 tests / 206 assertions**. Final expanded matrix, including additional explicit denial/lifecycle assertions: **21 tests / 226 assertions passed**. Coverage includes attributed success/one event; pre-audit failure; actual audit insert then exception; real PostgreSQL audit foreign-key rejection; same PDO and operation depth; rollback from a failed database statement; permission/panel/status/deactivation/region expiry/future/revocation/target-role changes after mount; service reauthorization; invalid input; self/cross-region/detached actor denial; forged attribution/revocation fields; direct URL denial; valid nationwide authority and duplicate constraints. Responses/snapshots/log checks exclude injected messages, SQL diagnostics and credential hashes.

[Real concurrency evidence](screenshots/phase-3d7-g-int006/concurrency.json): **five scenarios passed**, each using two independent PHP/PostgreSQL workers and database-confirmed blocking (`pg_blocking_pids`). Competing creates serialize: one commit, one existing unique-constraint rejection, one grant/one audit. In four other scenarios, scope revocation, direct permission removal, actor deactivation or target-role change begins first, holds its transaction, and commits while creation is waiting. The waiting creation then denies with zero new grants. A third connection observes zero uncommitted grants. Disposable file barriers synchronize tests only; no runtime correctness delays or retries are added.

The final lock-set refinement limits role-permission row locks to the actor's roles, while retaining target-role membership locks. All five concurrency cases and the 21-test matrix passed again afterward. Earlier concurrency evidence is separately retained in `concurrency-first-pass.json`.

Authority is checked at the operation's authorization point; continuous authority for the entire lifetime of an already committed grant is not a new policy. Natural expiry, arbitrary external transaction schedules and all possible deadlocks are not exhaustively certified. A database error rolls back this operation and follows safe recovery rather than silently retrying.

## Actual browser and accessibility

Guarded local port **8026**, `osds_ui_browser_test`, Chrome/Playwright/axe: **seven functional checks passed**, with **eight** light/dark 1440px/375px screen checks. The real page performs delegated-account search/selection, region search/selection, native required-field validation, actual Create audit rollback with retained fields, delivered success/double click, explicit duplicate Create rejection, committed-response loss/saved-list inspection, post-mount permission denial and cookie-loss recovery. No replacement form or test-only persistence handler is used. Uncertain committed responses remain uncertain until inspection; no privileged replay is added. Separate creates are not idempotent.

Eight screen checks report no page overflow, uncaught page errors or checked response leaks. Measured sampled foreground/background minima are **6.15:1 light / 8.72:1 dark**, meeting the applicable normal-text threshold for those sampled pairs only. **Axe is not all-green:** both existing selector clear buttons are 16px targets; `target-size` is reported for two nodes in each of the eight sampled states. This is a medium accessibility follow-up, not an integrity failure; no shared styling change is included in this targeted fix. The first browser attempt stopped on it and is retained as `browser-target-size-first-attempt.json`. The subsequent harness keeps all axe rules and recorded violations, permits only this exact clear-button finding, and fails any other violation. This explicit known-defect disposition must not be described as zero axe findings or full accessibility acceptance.

Representative developer screenshot inspection: [light desktop rollback](screenshots/phase-3d7-g-int006/regional-creation-rollback-light-1440.png), [dark mobile uncertainty](screenshots/phase-3d7-g-int006/regional-creation-uncertain-dark-375.png). All screenshots/results remain under [target results](screenshots/phase-3d7-g-int006/results.json). External avatar requests are blocked; broken avatar images are an isolated fixture limitation. Native zoom, screen readers, human usability and contrast throughout transient animations remain unverified.

## Implementation/test corrections

- First implementation used an unsupported `lockForShare` method; the once-required audit mock correctly exposed that execution had not reached audit. Corrected to installed Laravel's `sharedLock`; original regression then passed. No failing attempt is counted as successful verification.
- One expanded-test setup omitted required attribution on its manually arranged nationwide fixture. Added fixture `granted_by`; no production policy changed.
- Initial direct server launch hit PowerShell's native-stderr handling. The local helper was launched through `cmd.exe` with output redirection; its port/database/config guards remain unchanged.
- Full Pint initially found formatting in the prior untracked Batch G workload assessment script. Only formatter changes were applied there; no workload or runtime behavior changed. Existing staged content was not altered.
- The first staff preservation journey stopped during screenshot stabilization after a successful SCHOOL verification: one notification remained after a one-shot dismissal. The saved page showed the expected verification-success notification. The original attempt/results remain retained. A separate full staff rerun uses the already established repeated-dismiss/check pattern (15-second bound) in its temporary harness only; workflow, persistence, route, contrast and axe assertions remain unchanged. No action is replayed by that helper and no runtime notification fix is claimed. The full rerun passed **15 interactions / 94 screen checks**. This resolves the preservation-run obstruction, not every possible notification animation/timing concern.

## Final gates

Final focused PostgreSQL: **21 tests / 226 assertions passed**. Complete final current-tree PostgreSQL: **230 tests / 2,615 assertions passed**, including G-INT-001 through G-INT-005, SEC-001 through SEC-008, applicant/builder integrity, UTC, exact-scope, immutability and account-governance regressions. Full Pint `--test`, production Vite build and working-tree diff checks passed (existing CRLF advisories). Build emitted bundler timing advice, not a build failure. No parallel test-schema preparation was used.

Private logs: `storage/app/private/g-int-006-original-final.txt`, `g-int-006-matrix-final-expanded.txt`, `g-int-006-concurrency-final.txt`, `g-int-006-full-final.txt`, `g-int-006-browser-final.txt`, `g-int-006-preservation.txt`, `g-int-006-staff-preservation-final.txt`, `g-int-006-pint-final.txt` and `g-int-006-build.txt` in the same directory. Public artifacts contain synthetic fixture data and safe result summaries only.

Serialized browser preservation passed: original applicant answer-race reproduction plus **11 expanded cases**; original builder isolation reproduction plus **14 selection cases**; **seven** condition/preview/applicant-publication checks with six screen checks; CHEDRO **12 interactions / 30 screens**, including correction/resubmission, real assignment expiry/revocation and permission/scope denial; and the separate final staff rerun **15 interactions / 94 screens**, including account activation, creator/verifier separation, explicit scope/permissions, keyboard System theme, pagination and session recovery. No new application runtime change was needed for preservation. The first staff stabilization failure is explicitly retained, not reported as a pass. Evidence: [initial preservation index](screenshots/phase-3d7-g-int006/preservation/results.json), [separate staff rerun](screenshots/phase-3d7-g-int006/preservation/staff-retry/results.json).

## Affected files

- `app/Services/Authorization/RegionalGrantCreationService.php` (new operation).
- `app/Filament/Resources/UserRegionAssignments/Pages/CreateUserRegionAssignment.php` (actual persistence integration/safe feedback).
- `app/Providers/Filament/AdminPanelProvider.php` (existing recovery hook scoped to Create).
- `tests/Feature/RegionalGrantCreationAtomicityTest.php` (retained reproduction/rollback assertion), `RegionalGrantCreationWorkflowTest.php` (expanded matrix).
- `scripts/Prepare-RegionalGrantCreation.php`, `RegionalGrantCreationWorker.php`, `verify-regional-grant-concurrency.mjs`, `g-int-006-browser-server.php`, `verify-regional-grant-creation.mjs`, `verify-g-int-006-preservation.mjs` (strict disposable fixture/browser/concurrency/preservation tooling).
- Remediation/checkpoint/issue documentation and safe artifacts; formatter-only adjustment to the earlier workload script.

Resource policy/form, assignment model, observer, AuditRecorder, AccessGovernance, prior G-INT services, permissions, schema and lifecycle guards are unchanged. Fault injection is local test-server code only, not application routes/providers.

## Manual retesting and preservation

1. With disposable accounts, create one delegated regional grant inside effective actor scope; inspect exact target, region, dates, actor attribution and one matching audit.
2. Try self/cross-region/unmanageable-role selections and revoke permission/scope in isolated fixtures after mount: no grant should be created.
3. Try a duplicate unrevoked assignment: no extra grant or audit. Do not infer a new policy from dates alone.
4. In isolated fault automation only, fail mandatory audit and verify no grant/audit, retained local inputs and safe persistent feedback.
5. After lost response/session, keep the tab open and inspect saved assignments in a separately authorized tab before deciding on any new explicit Create.
6. Human keyboard/screen-reader/native-zoom/first-time usability checks remain pending; record selector target-size and unclear recovery language.

Only `osds_test` and `osds_ui_browser_test` are used. Manual-testing databases and historical accounts/grants/audits are not inspected or modified. Prior fixes/evidence and staged changes are preserved. No commit, push, deployment, migration, history repair, Batch H or Phase 4. After targeted acceptance, pause for review; do not declare Batch G complete or resume its inventory automatically.

All targeted integrity/security gates passed; no independent critical defect was confirmed in this bounded remediation. The known selector target-size defect, broader Batch G assessment and outstanding human acceptance remain open. This result does not authorize Batch H or production and does not certify full accessibility.
