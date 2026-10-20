# G-INT-005 - Phase 3C creation remediation

**20 October 2026. Targeted development acceptance: PASS.** Backend atomicity and the actual Phase 3C Create workflow are verified separately below. Batch G remains incomplete. Pause for user review; H, Phase 4 and production are not authorized.

## Independently established existing creation contract

Inspected the actual Phase 3C resource/Create page, inherited Filament handler, assignment model/observer, AuditRecorder, staff-panel admission, policy/service references, Phase 3C plan and G-INT-002 decision record. Creation currently admits an active staff-panel user with explicit `evidence.reviewer.assignments.manage`; the model enforces sandbox, that permission and a synthetic reviewer. There is no creation-specific manager-region/module predicate or assignment-management policy in this path. This is permission-based global creation in the implemented sandbox contract, independently established from Phase 3C code; it is not imported from Phase 3D. The Phase 3C plan names Admin for provisional sandbox setup without adding a creation-region restriction. The G-INT-002 approved regional predicate explicitly concerns revocation, not creation.

Preserve that implemented contract. This does not settle a new production policy or broaden authority. Introducing a new creation-region rule would require separate approval. The existing assignment table has a reviewer/region/module lookup index, not a unique exclusion constraint; separate intentional grants remain allowed. No duplicate-grant policy or idempotency guarantee will be added.

The chronology below distinguishes the original reflection regression from subsequent real UI search/selection/Create and recovery verification. Initial mount alone did not establish callback behavior.

## Reproduced callback defect before correction

Severity: high functional workflow blocker; no persistence demonstrated by this failure. The actual browser reviewer search reaches `callSchemaComponentMethod` and returns HTTP 500; no reviewer option can be selected. A real Livewire filled-form probe independently fails with `Call to a member function where() on null` at the resource callback (**1 test / 2 assertions / 1 error**). Empty initial mount alone had succeeded. Filament supplies a named `query` argument; the untyped `$q` callback receives null. Proposed correction: name that existing callback argument `$query`, preserving its `is_synthetic` filter. No query scope, authority or form bypass is introduced. Original browser attempt remains `screenshots/phase-3d7-g-int005/browser-before-callback.json`; private server-side probe is `storage/app/private/g-int-005-ui-before.txt`.

The callback is now corrected as proposed. Its synthetic filter remains in place. The real final browser searches, selects, validates and submits through the actual form; no form subclass or persistence bypass is used for that result.

## Authoritative transaction and safe recovery

`EvidenceReviewerCreationService::create` owns one PostgreSQL operation transaction. It rereads the current actor, verifies authenticated identity, active status, staff-panel and Phase 3C management permissions, and sandbox availability. It validates only the existing creation fields, fixes grant attribution to the authorized actor and ignores forged identity/revocation/attribution fields. The unchanged model continues enforcing synthetic reviewer restrictions and supplying identity; its unchanged synchronous observer remains the sole grant audit writer. Assignment and audit insert share the operation transaction/PDO. No blanket panel transaction, second manual audit, automatic transaction retry, compensation delete, migration or account/history repair.

The actual Create page overrides only `handleRecordCreation`, delegates to the service and uses existing redacted support-reference feedback. Audit failure leaves a fixed persistent `data.reason` explanation and preserves entered fields. Authorization/validation denials retain their normal semantics. The existing staff recovery hook is additionally scoped to this Create page, explaining failed networks, sessions and uncertain responses and linking to the saved list without automatic replay. The selector reuses the existing local 24px clear-action hit-area pattern, scoped to its own class, without changing palette, filtering or unrelated components.

G-INT-002's revocation service/region rule remains unchanged; G-INT-001, G-INT-003 and G-INT-004 services and contracts are unchanged. Phase 3C and Phase 3D assignments are not interchangeable.

## Retained regression and focused PostgreSQL gates

Original discovery: **2 probes / 11 assertions; 1 passed, 1 failed**, with the reflected inherited handler leaving a grant after its mandatory audit fault. The original named test and exact rollback comparison remain. Its reflection now resolves the actual authoritative Create-page override, rather than asserting that the vulnerable inherited implementation must remain installed. Its acknowledgement checks safe field feedback instead of the injected raw exception. The audit sink is still required once; Eloquent persistence and the observer are not mocked. Separate workflow tests exercise actual Livewire forms/actions.

Final focused matrix: **17 tests / 157 assertions passed** across `EvidenceReviewerCreationAtomicityTest` and `EvidenceReviewerCreationWorkflowTest`. Covers success with exact attribution/dates and one matching audit; intentional separate duplicate creation; fault before audit; real audit insert then exception; genuine PostgreSQL audit foreign-key rejection and nested-transaction recovery; actual same PDO/operation transaction depth; permission/panel/status/sandbox loss after mount and fresh service denial; invalid fields/dates/IDs; forged attribution/identity/revocation fields; non-synthetic reviewer denial; direct unauthorized URL/detached actor; retained input and redacted Livewire/log responses. Successful creation fixtures have no regional manager grant, verifying preservation of this independently established creation contract. Logs contain only fixed support message, reference and exception class, not SQL, injected messages or credential hashes.

## Real concurrency and duplicate semantics

[Concurrency evidence](screenshots/phase-3d7-g-int005/concurrency.json): two independent PHP workers with different PostgreSQL backend PIDs overlap after real assignment and audit insertion, inside separate operation transactions. A third connection sees zero uncommitted assignments before test barriers release. Both commit, leaving **two grants / two audits**, one per separate operation. This preserves existing duplicate-grant behavior; no new scope exclusion or identical-retry guarantee is claimed. Harness barriers are disposable synchronization, not runtime correctness delays.

The first worker attempt imported the new service from the copied Phase 3D namespace and failed before entering it, producing non-JSON console output. That harness import was corrected to `App\Services\Evidence\EvidenceReviewerCreationService`; the subsequent real-worker run above passed. The concurrency and target browser harnesses share a fixture-control file and are run serially to avoid harness interference; no application locking/authority semantics were changed for this purpose.

## Actual browser, accessibility and failure evidence

Guarded local port **8025**, Chrome/Playwright/axe, `osds_ui_browser_test` only: **6 interactions / 8 light/dark desktop/mobile screen checks passed**. Real reviewer search/selection, native required-field validation, actual Create submission, downstream audit rollback with retained entries, delivered success, rapid double click, committed response loss, saved-list inspection, post-mount permission denial and cookie-loss recovery execute. The double click yields one grant/audit through existing pending behavior; a later separate explicit Create may produce another grant. A lost committed response remains uncertain; local input is retained and the saved list confirms the two successful grants with one matching audit each. No privileged request is automatically replayed.

The eight checked states at **1440px / 375px** record zero axe violations, page overflow, uncaught page errors or checked response leaks. Sampled computed contrast minima are **6.15:1 light / 8.72:1 dark** for measured selectors/states. Developer screenshot inspection includes light desktop rollback and dark mobile uncertainty. These are representative automated checks, not full WCAG, screen-reader, native zoom or human usability acceptance.

An earlier post-fix capture caught a notification fade with measured foreground/background **2.09:1** during the transition. That failed attempt remains [browser-transient-toast-attempt.json](screenshots/phase-3d7-g-int005/browser-transient-toast-attempt.json). The harness may resample a whole page after normal notification dismissal only when every reported violation belongs to a transient notification; it records any such resample and does not exclude elements/rules or retry mutations. **The final passing run required zero such resamples.** Inline recovery/error feedback remains checked. No runtime toast-animation fix or contrast compliance throughout animation is claimed.

Evidence: [final browser results](screenshots/phase-3d7-g-int005/results.json), [light desktop rollback](screenshots/phase-3d7-g-int005/creation-rollback-light-1440.png), [dark desktop rollback](screenshots/phase-3d7-g-int005/creation-rollback-dark-1440.png), [light mobile uncertainty](screenshots/phase-3d7-g-int005/creation-uncertain-light-375.png), [dark mobile uncertainty](screenshots/phase-3d7-g-int005/creation-uncertain-dark-375.png).

## Final gates and preservation

Complete current-tree PostgreSQL: **209 tests / 2,389 assertions passed**, including the preserved G-INT-001–004, SEC-001–008, applicant/builder integrity, UTC, exact-scope and immutability regressions. Full Pint `--test`, production Vite build and working diff check passed with existing CRLF advisories. Private final suite: `storage/app/private/g-int-005-full-final.txt`; focused matrix: `storage/app/private/g-int-005-matrix-first.txt`; Pint/build: `g-int-005-pint-final.txt` / `g-int-005-build.txt` in the same private directory. The earlier 192-test checkpoint is historical.

Serial browser preservation passed: original applicant answer-race reproduction plus **11 expanded cases**; original builder isolation reproduction plus **14 selection cases**; **7 condition/preview/publication checks** with 6 screen checks; and CHEDRO evidence/intake **12 interactions / 30 screens**, including correction/resubmission, assignment expiry/revocation/no-permission, scoped search/count/pagination and direct URL denial. No preservation attempt failed in this run. The new preservation runner reuses existing assertions with new artifact directories and removes its temporary wrapper on completion, preserving earlier evidence. [Preservation index](screenshots/phase-3d7-g-int005/preservation/results.json).

The held-response preservation cases passed unchanged. This does not diagnose/fix earlier broad Batch G modal/timing observations or complete performance assessment. All G-INT-005 targeted gates pass; no new independent critical defect was confirmed within this bounded remediation.

## Affected files

- `app/Services/Evidence/EvidenceReviewerCreationService.php`: new narrowly owned creation transaction.
- `app/Filament/Resources/SyntheticEvidenceReviewerAssignments/Pages/CreateSyntheticEvidenceReviewerAssignment.php`: real Create integration and safe persistent feedback.
- `SyntheticEvidenceReviewerAssignmentResource.php`: correct callback injection name and scoped clear-control class only; revoke action unchanged.
- `app/Providers/Filament/AdminPanelProvider.php`: scope existing recovery hook to the Phase 3C Create page.
- `resources/css/filament/admin/theme.css`: extend the existing local clear-action hit-area pattern to this selector.
- `tests/Feature/EvidenceReviewerCreationAtomicityTest.php`: retained original handler/mount probes, current handler/feedback acknowledgement.
- `tests/Feature/EvidenceReviewerCreationWorkflowTest.php`: actual Livewire action/authorization/failure matrix.
- `scripts/Prepare-EvidenceReviewerCreation.php`, `EvidenceReviewerCreationWorker.php`, `g-int-005-browser-server.php`, `verify-evidence-reviewer-creation.mjs`, `verify-evidence-reviewer-creation-concurrency.mjs`, `verify-g-int-005-preservation.mjs`: isolated fixtures, local fault server, concurrency/browser/preservation checks.
- Discovery follow-up, issue-register/Batch G checkpoint documentation and safe artifacts.

The assignment model, observer, AuditRecorder, prior operation services, permissions, database schema and lifecycle policies are unchanged. Fault injection is registered only by the isolated local test script, not application routes/providers.

## Limits, preservation and manual review

Only `osds_test` and `osds_ui_browser_test` are used. No `osds_sandbox`/`osds_dev` inspection/reset/reseed/mutation, historical grant/audit repair, staging/index operation, commit, push or deployment. Existing G-INT-004 changes and earlier evidence remain preserved. Browser external requests, including avatars, are blocked; broken external avatars are fixture limitations.

Manual retest with disposable synthetic accounts:

1. Use a staff actor with explicit Phase 3C management permission; search for the synthetic reviewer, select exact region/module and dates, enter a reason and create once. Inspect the saved row and separately authorized audit history.
2. Keep creation's implemented management contract distinct from G-INT-002's regional revocation requirement and from CHEDRO case-review assignments/scope. Do not infer production authority.
3. In isolated fault automation only, fail audit and verify no grant/audit persists, local entries remain and safe persistent feedback appears.
4. After uncertain connectivity/session loss, inspect saved assignments in another authorized tab before deciding whether a new explicit create is needed. Separate creation is not idempotent.
5. Human keyboard/screen-reader/native-zoom/first-time usability and complete timed-animation contrast assessment remain pending. Record unclear labels or recovery steps; automated axe checks do not replace these tasks.

Broader Batch G timing, workload and cross-workflow inventory remain outside this targeted remediation. No new independent critical defect has been confirmed so far. After all targeted gates pass, pause for user review; do not declare Batch G complete or resume its remaining inventory without review. Batch H, Phase 4 and production remain excluded.
