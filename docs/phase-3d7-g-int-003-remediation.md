# G-INT-003 - Atomic application-reviewer assignment revocation

**20 October 2026. Targeted acceptance: PASS for development.** This is Phase 3D assignment revocation only. Batch G overall remains incomplete; this is not production approval. No Phase 3C governance change, schema change, historical repair, deployment or staged-index operation.

## Authorization contract established before implementation

The actual resource admits active staff with `applications.reviewer.assignments.manage`, subject to existing staff-panel admission. No assignment-specific policy, regional manager predicate, module-manager permission or target-scope filter was found in this management path. The model's creation guard restricts sandbox use and synthetic reviewers. The existing Phase 3D management contract is therefore **global by explicit permission**, rather than a role-name bypass or region-scoped manager grant. This was reported before implementation; no further governance choice was inferred.

The separate CHEDRO case-review authority still requires effective exact reviewer region/module assignments and regional scope. Global assignment management does not authorize case review. The newly approved Phase 3C manager-region rule is not inherited here. Tests deliberately prove that absent, wrong, expired, future or revoked manager regional grants do not remove an otherwise active actor's Phase 3D management permission. Permission loss and inactive identity deny revocation.

## Root cause and authoritative transaction

The original actual revoke action directly updated Eloquent. Its synchronous updated observer subsequently inserted `application.reviewer_assignment_revoked`, without one encompassing transaction. The retained original regression failed **one test / eight assertions**: assignment revocation persisted while mandatory audit count stayed unchanged. Original private evidence remains `storage/app/private/batch-g-application-assignment-atomicity.txt`; no historical record inspection was performed.

`ApplicationReviewerAssignmentService::revoke` now owns one default-PostgreSQL transaction. It reloads the actor, locks the exact assignment, rereads identity after a possible lock wait, checks current authenticated/active actor, sandbox and existing explicit Phase 3D permission, rejects already-revoked state, validates the reason, and updates with existing actor/time conventions. The unchanged model observer remains the only mandatory audit writer. Both writes use the same connection and transaction. Partial audit insertion followed by an exception rolls both back. No compensation deletes, duplicate manual audit, automatic transaction retry or blanket panel transaction.

The row lock serializes overlapping attempts. An already-revoked row is rejected without changing its original actor/reason/time or adding an audit. This preserves the one-revocation lifecycle; it is not a new request-idempotency token or a guarantee of identical success responses to repeated calls.

## Actual action and recovery

The existing table action calls the dedicated service through the existing safe support-reference handler. Unexpected failure also leaves fixed persistent dialog feedback and retained local reason. Authorization exceptions retain denial behavior. The list has the existing session/network recovery notice. Committed response loss remains uncertain until the authorized saved record is inspected; no privileged action is automatically replayed. A refreshed revoked row has no normal Revoke control.

The original regression still uses the actual list/table action, observer, audit-boundary fault and PostgreSQL state comparison. Its acknowledgement now checks safe notification and persistent error state; the observer mock must still be reached exactly once and the rollback assertion is unchanged. Original regression after fix: **one test / 13 assertions passed**.

## Verification checkpoint

- Targeted original plus final expanded matrix: **15 tests / 112 assertions passed**. Includes exact attributed success, invalid reason, audit fault after actual insertion, a genuine PostgreSQL audit-insert foreign-key rejection, operation transaction depth above PHPUnit's fixture transaction, identical PDO, immutable earlier history, denial after permission/status changes, duplicate rejection and response/log serialization without injected exception text, SQL errors or credential hash. The genuine database failure is injected only at the audit sink; the actual action, assignment update and database insertion execute. The operation rolls back PostgreSQL's failed transaction state before the outer fixture transaction is inspected.
- Actual Admin browser: **eight interactions / eight light/dark desktop/mobile screen checks passed**. Exercises real table search/action/dialog, audit-side rollback with retained remarks, duplicate clicks, committed lost response, authorized Audit history, System keyboard preference changes, global management after regional-grant revocation, permission loss and expired session recovery. Final records are in `docs/screenshots/phase-3d7-g-int003`.
- Two pairs of real PostgreSQL workers verify actual row-lock waits: one commit plus one duplicate rejection; one commit plus denial of an actor made inactive while the second request waits. Each target ends with one revocation and one matching audit. Test synchronization/status changes apply only to disposable synthetic fixtures, not runtime correctness delays or historical accounts.
- Complete final current-tree PostgreSQL: **177 tests / 2,087 assertions passed**, including the genuine database-insert failure test, G-INT-001/002 and SEC-001–008 regressions. The earlier 176-test checkpoint predates that additional test. Pint, production Vite build and working-tree diff checks passed; pre-existing line-ending advisories remain.
- Preservation browsers: CHEDRO evidence/intake **12 interactions / 30 screen checks**; unchanged Phase 3C revocation **eight interactions / eight screens**; original applicant answer-race reproduction plus **11 expanded cases**; original builder isolation reproduction plus the final full **14-case** selection matrix; and **seven** condition/preview/applicant-publication checks all passed. These are automated browser checks, not human acceptance.

One matrix attempt queried the wrong workflow's audit action string; the test was corrected to the actual existing `application.reviewer_assignment_revoked` event. A copied browser harness initially wrote Phase 3D artifacts into the Phase 3C evidence directory. Phase 3D artifacts were separated, harness paths corrected, and the unchanged Phase 3C runtime passed a fresh browser preservation journey to restore distinct evidence. That restoration's first attempt timed out opening a subsequent modal; its harness now uses fresh GETs between independent action cases. Rapid back-to-back different-row modal transitions are not certified by that isolated-case rerun. The failed attempt is retained in `phase-c-preservation-first-attempt.json`. No manual-testing record or application runtime contract was altered for these corrections.

The first builder matrix attempt passed twelve cases, then timed out after five seconds waiting for another question to become selected following a held older save response. It stopped before typing or saving a new value; it does not establish an unintended persisted edit. The unchanged failing case and the following case passed in isolation, then the entire unchanged matrix passed all fourteen cases in a serialized run. No builder runtime or assertion was changed. The timeout, isolated result and final full result are retained respectively in `builder-preservation-first-attempt.json`, `builder-preservation-isolated.json` and `builder-preservation-final.json` in the G-INT-003 evidence directory. The timeout's cause is not established; arbitrary-load timing/rapid interaction behavior remains a broader reliability follow-up, not a claimed fix under this task.

Evidence: [target browser results](screenshots/phase-3d7-g-int003/results.json), [real PostgreSQL concurrency](screenshots/phase-3d7-g-int003/concurrency.json), [final builder preservation](screenshots/phase-3d7-g-int003/builder-preservation-final.json). Representative screenshots: [light desktop](screenshots/phase-3d7-g-int003/rollback-light-1440.png), [dark desktop](screenshots/phase-3d7-g-int003/rollback-dark-1440.png), [light mobile](screenshots/phase-3d7-g-int003/rollback-light-375.png), [dark mobile](screenshots/phase-3d7-g-int003/rollback-dark-375.png). The eight target screen checks recorded zero axe violations, page overflow, uncaught page errors or checked response leaks; these are representative checks, not a full WCAG assessment.

## Affected files

- `app/Services/Applications/ApplicationReviewerAssignmentService.php` (new authoritative operation).
- `app/Filament/Resources/SyntheticApplicationReviewerAssignments/SyntheticApplicationReviewerAssignmentResource.php` (service integration and persistent safe feedback).
- `app/Providers/Filament/AdminPanelProvider.php` (existing recovery view scoped to the Phase 3D assignment list).
- `tests/Feature/EvidenceAssignmentAtomicityTest.php` (retained original G-INT-003 regression) and `tests/Feature/ApplicationAssignmentAtomicityTest.php` (expanded matrix).
- `scripts/Prepare-ApplicationReviewerRevocation.php`, `ApplicationReviewerRevocationWorker.php`, `g-int-003-browser-server.php`, `verify-application-reviewer-revocation.mjs` (strict isolated browser fixture/server/worker verification).
- `scripts/verify-reviewer-revocation.mjs` (Phase 3C preservation harness only: fresh GET between independent cases; no Phase 3C runtime or policy change).
- Issue register, Batch G report/manual checklist, inventory/checkpoint documentation and safe screenshot/result artifacts.

The assignment model, observer and AuditRecorder are unchanged. Phase 3C finding/assignment services and their authorization contracts are unchanged. The fault server is local port 8023 only, refuses cached configuration and pins the browser test database; no production route/provider registers fault injection.

## Limits and completion boundary

Only `osds_test` and `osds_ui_browser_test` are used. No `osds_sandbox`/`osds_dev` inspection/reset/reseed/mutation, migration file, historical audit/credential/grant repair, commit, push or deployment. Existing staged changes remain preserved.

Creation audit risks and the previously documented Phase 3C Create callback defect are outside this revocation-only fix. No independent creation failure or historical audit completeness is claimed. Human usability, native zoom, screen-reader acceptance and full WCAG assessment remain open; automated axe/screen checks do not complete them. External requests, including avatars, are blocked in browser fixtures. Sampled screenshots are not a certification of every UI state.

All G-INT-003 targeted gates above passed. No independent critical defect was confirmed during this bounded remediation. Batch G's remaining broader reliability/performance inventory is incomplete, and the recorded browser timing observations remain follow-ups. This targeted-only authorization does not declare Batch G complete, authorize unrelated implementation, or authorize Batch H/Phase 4/production. Pause for user review.
