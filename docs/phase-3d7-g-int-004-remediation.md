# G-INT-004 - Atomic application-reviewer assignment creation

**20 October 2026. Targeted development acceptance: PASS.** Phase 3D creation only; no Batch G completion claim. No migration, governance change, historical repair, deployment, commit or push. Pause for user review before resuming broader G inventory.

## Contract and implementation

Management remains global by active staff admission, explicit `applications.reviewer.assignments.manage` and sandbox restrictions. No Phase 3C regional rule is imported. The existing table has a scope lookup index, not a reviewer/region/module uniqueness constraint; existing creation permits separate repeated grants. This behavior is preserved, not replaced by a new duplicate-grant policy or idempotency token.

`ApplicationReviewerCreationService::create` owns one default-PostgreSQL transaction. It reloads the actor and checks authenticated identity equality, active status, staff-panel permission, management permission and sandbox before insertion. It validates the existing creation fields and ignores forged identity/revocation/attribution fields. `granted_by` is set from the current authorized actor. The unchanged model enforces synthetic reviewer eligibility, generates identity and synchronously records the sole existing mandatory audit through its unchanged observer/AuditRecorder. The assignment and audit share the same PDO and operation transaction. There is no retry, compensation delete, manual second audit, blanket panel transaction or row lock inventing an exclusion rule for distinct new assignments.

The actual Filament Create page delegates `handleRecordCreation` to that service. Existing validation/authorization failures retain their behavior. Unexpected failure uses StaffActionFeedback's redacted support-reference notification plus a fixed persistent `reason` field error; entries remain local and no creation success is reported. The existing staff session/network recovery hook is scoped additionally to this Create page. It preserves local input and offers saved-list inspection after uncertainty, without automatically replaying creation.

## Retained reproduction and PostgreSQL verification

The original actual Create-page regression is retained in `ReviewerAssignmentCreationAtomicityTest`. Before: **1 test / 8 assertions / 1 failure**, with assignment count increasing despite audit failure. After: **1 test / 14 assertions passed**. Its real page, form, create action, observer, once-required audit fault and rollback comparison remain; the exception acknowledgement now asserts safe notification/field feedback. Private original evidence remains `storage/app/private/batch-g-creation-atomicity.txt`.

Expanded final matrix: **15 tests / 145 assertions passed**. Covers exact success attribution/dates and one matching audit; independent deliberate duplicate creates; before-audit failure; real audit insert followed by fault; genuine PostgreSQL audit foreign-key rejection; operation transaction depth above the test fixture transaction and identical PDO; rollback with clean outer transaction; invalid dates/IDs/reason; forged attribution/identity/revocation state; non-synthetic reviewer denial; permission/panel/status/sandbox loss; detached identity and direct unauthorized Create URL; input preservation and response/log redaction. Logs are asserted to contain support reference and exception class only, not SQL, injected message or password hash.

The first implementation matrix failed because new validation referenced `osds_modules` instead of the existing `modules` table. The required once-called audit mock exposed that the audit sink had not been reached. The table name was corrected; the mock and rollback assertions were retained and all final cases pass.

## Concurrent and repeated creation

[Concurrency evidence](screenshots/phase-3d7-g-int004/concurrency.json): two real PHP workers with distinct PostgreSQL backend PIDs both pause after their actual audit insert inside their independent operation transactions. A third connection sees zero uncommitted assignments. Releasing both yields **two assignments / two audits**, one per successful distinct operation. Test barriers are isolated harness synchronization only, not runtime delays. No uniqueness/idempotency guarantee is claimed or added. The browser's rapid double click produced one grant/audit through existing pending-page behavior; a separate new Create operation is still allowed to create another grant.

## Actual browser and accessibility

Local Chrome/Playwright/axe against guarded port **8024**, exclusively `osds_ui_browser_test`: **6 interactions / 8 screen checks passed**. Real controls exercise native required-field validation, post-audit rollback and retained reason, delivered success, rapid duplicate click, committed response loss, saved-list inspection without replay, permission loss after mount, and session-cookie loss. The committed lost response leaves uncertain feedback and local entries; the authorized list confirms exactly the two successful grants and one audit per grant. No automatic retry occurs. The final cookie-loss check confirms no new assignment. Server tests separately cover inactive identity and genuine database failure.

Eight light/dark captures at **1440px and 375px** record zero axe WCAG-tag violations, page overflow, uncaught page errors or checked response leaks. Sampled rendered contrast minima are **6.15:1 light / 8.72:1 dark** for the tested selectors/states; this is not a measurement of every control/state or full WCAG certification. Developer image inspection includes light desktop rollback and dark mobile uncertainty.

Evidence: [browser results](screenshots/phase-3d7-g-int004/results.json), [light desktop rollback](screenshots/phase-3d7-g-int004/creation-rollback-light-1440.png), [dark desktop rollback](screenshots/phase-3d7-g-int004/creation-rollback-dark-1440.png), [light mobile recovery](screenshots/phase-3d7-g-int004/creation-uncertain-light-375.png), [dark mobile recovery](screenshots/phase-3d7-g-int004/creation-uncertain-dark-375.png).

Initial harness failures are retained separately: custom-selector search used a nonexistent searchbox role; exact field labels omitted Filament's required marker; native HTML validation was incorrectly expected to produce a server message. These locators/assertions were corrected to real controls/native validity without changing server validation. A later capture encountered a notification arriving after a one-time dismissal; the capture helper now polls normal close controls until transient notifications finish. It does not hide persistent inline recovery/errors, alter application state or retry creation. `browser-first-attempt.json`, `browser-second-attempt.json`, `browser-third-attempt.json`, `browser-a11y-before.json` and `browser-toast-attempt.json` retain these failed attempts.

## Confirmed local accessibility issue before correction

Severity: Medium. On the actual Create page, choose a reviewer, submit with injected downstream audit failure and inspect with axe WCAG 2.2 AA. The custom selector's Clear selection control is 16px square and fails target-size spacing against the neighboring combobox. Persistence impact: none; transactional rollback succeeds. Proposed correction: scope a 24px minimum hit area to this Create page's clear control. Preserve selection, validation, tokens, other pages and governance. The first browser attempts also used incorrect locator assumptions and expected a server error for native required-field validation; those are harness corrections, not application failures. Failed attempts remain separate evidence.

The selector now has a scoped 24px minimum clear-control hit area. Final eight screens pass the same axe checks; no global selector override or component replacement was added.

## Preservation and final gates

Final complete PostgreSQL suite: **192 tests / 2,232 assertions passed**, including G-INT-001/002/003, SEC-001–008, C-INT-001, E-INT-001/002 and UTC/exact-pair/immutable-history regressions. An earlier 191 / 2,228 checkpoint preceded the added direct-route/detached-identity test. Full Laravel Pint `--test`, production Vite build and tracked working diff check pass; existing CRLF advisories remain. The first build invocation was interrupted by PowerShell treating Vite stderr timing warnings as terminating errors; rerunning with normal native-command redirection produced a successful build, not a change to the build requirements.

Preservation browsers all passed: original applicant answer-race reproduction plus **11 expanded cases**; original builder isolation reproduction plus **14 expanded selection cases**; **7 builder condition/preview/publication checks** with 6 light/dark screens; and CHEDRO intake/evidence **12 interactions / 30 screens**, including correction/resubmission, exact-scope search/count/pagination and expired/revoked/no-permission/direct-route denial. The tested builder held-response cases pass unchanged on this run; that is preservation evidence, not a root-cause determination or new fix for earlier timing observations. No preservation attempt failed in this run.

The new preservation runner reuses existing assertions and changes only artifact output directories to `screenshots/phase-3d7-g-int004/preservation`, preserving earlier evidence. It runs fixture preparation and journeys serially and removes its temporary wrapper on completion. No unrelated runtime change is made to pass preservation. [Preservation run index](screenshots/phase-3d7-g-int004/preservation/results.json). Complete PostgreSQL regressions retain the separate G-INT-001/002/003 protections; those services were not modified.

All required targeted gates pass. No independent critical defect was confirmed during this remediation. This conclusion does not complete Batch G or authorize broader inventory continuation before user review.

## Changed files

- `app/Services/Applications/ApplicationReviewerCreationService.php`: new narrow creation transaction.
- `app/Filament/Resources/SyntheticApplicationReviewerAssignments/Pages/CreateSyntheticApplicationReviewerAssignment.php`: actual Create integration and safe persistent feedback.
- `app/Providers/Filament/AdminPanelProvider.php`: scope existing recovery hook to Create page.
- `SyntheticApplicationReviewerAssignmentResource.php` and `resources/css/filament/admin/theme.css`: scoped selector accessibility correction only; revoke action unchanged.
- `tests/Feature/ReviewerAssignmentCreationAtomicityTest.php`: retained original and expanded matrix.
- `scripts/Prepare-ApplicationReviewerCreation.php`, `ApplicationReviewerCreationWorker.php`, `g-int-004-browser-server.php`, `verify-application-reviewer-creation.mjs`, `verify-application-reviewer-creation-concurrency.mjs`, `verify-g-int-004-preservation.mjs`: isolated fixtures, fault server, workers and verification. Test fault injection is not registered in production routes/providers.
- Discovery/remediation, issue-register/Batch G checkpoint documentation and new safe evidence.

The assignment model, observer, AuditRecorder and G-INT-001/002/003 services remain unchanged.

## Limitations and manual retest

1. With a disposable synthetic authorized global manager, create one assignment, inspect its effective dates/attribution and authorized audit, and verify it appears on the saved list.
2. Keep Phase 3C manager-region governance and Phase 3D case-review exact assignment/scope checks separate from global Phase 3D assignment management.
3. In isolated automation only, fail mandatory audit and verify neither grant nor audit persists, local entries remain, and fixed feedback makes no success claim.
4. On uncertain connectivity, inspect saved assignments in another authorized tab before deciding whether another create is needed. Separate explicit repeated creation is not idempotent and may create another valid grant.
5. Test keyboard, native 200% zoom, screen readers, themes and first-time human task completion. Human usability, native zoom, screen-reader and full accessibility acceptance remain pending.

Only `osds_test` and `osds_ui_browser_test` were used. No manual-testing database inspection/reset/reseed/mutation, historical assignment/audit repair, schema change, deployment, commit/push or staging operation. Existing evidence is retained. External browser requests including avatars are blocked; broken external avatar images are a fixture limitation.

Phase 3C creation/callback and broader Batch G timing/performance inventory remain outside this targeted remediation. No general creation-audit/historical integrity claim is made. Pause for user review after targeted acceptance; Batch G is not complete, and H/Phase 4/production remain excluded.
