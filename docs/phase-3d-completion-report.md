# Phase 3D completion report

**Date:** 19 October 2026  
**Scope:** Synthetic application submission, immutable revision snapshots, applicant tracking, CHEDRO sandbox intake, correction requests, and resubmission. Phase 4 decisions, official processing, approval, rejection, validation, registry, certificates, issuance, and RA 11180 reporting remain excluded.

## Implemented work packages

### 3D.1 — Application identity and working revisions

- Added a stable synthetic `applications` record above mutable `application_drafts`.
- Added one open `application_working_revision` per application and preserved every consumed working revision.
- Enforced one active application for an organization, module, and exact publication manifest with a PostgreSQL partial unique index.
- Backfilled existing Phase 3B drafts without changing their public IDs, owners, manifests, organizations, answers, or evidence.
- Kept manifest, module, organization, and applicant bindings immutable.

### 3D.2 — Atomic synthetic submission

- Added `ApplicationLifecycleService::submit()` as the sole submission transaction.
- Rechecks the sandbox environment, synthetic identity, permission, ownership, verified representation, manifest state, completeness, application lock version, and draft lock version.
- Creates an immutable submission header plus exact answer, documentary requirement, and evidence-revision snapshots.
- Stores hidden saved answers with an applicability flag for reproducibility. Ordinary CHEDRO intake displays only applicable answers and only with the dedicated permission.
- Consumes the working revision only after the immutable snapshot succeeds.
- Uses applicant/application/idempotency-key uniqueness so a retry returns the original submission without a second state change.

### 3D.3 — Applicant tracking

- Renamed the applicant landing workflow to **My applications**.
- Added state-aware **Resume draft**, **Make corrections**, and **View status** actions.
- Added a synthetic tracking page with plain-language state history and correction instructions.
- Added a final Review step and explicit **Submit test application** action to the applicant editor.
- Retired or expired manifests remain readable but cannot be submitted, corrected, or resubmitted.

### 3D.4 — Region-scoped CHEDRO intake

- Added **Application intake** to Filament for explicitly authorized CHEDRO reviewers.
- Added Admin-managed, effective-dated synthetic reviewer assignments bound to an exact reviewer, region, and module.
- Reviewer access requires all of: nonproduction sandbox guard, active synthetic identity, explicit permission, effective assignment, and matching regional scope.
- Added secure submitted-evidence opening. The route accepts public UUIDs and verifies exact snapshot membership before decrypting the locator.
- The intake screen carries a persistent notice: **Test intake only — no official decision or issuance**.
- No approval, rejection, official validation, routing decision, certificate, registry, or issuance control exists.

### 3D.5 — Corrections and resubmission

- CHEDRO can start screening and request targeted synthetic corrections during screening.
- Correction targets must belong to the exact immutable submission. Both the service and PostgreSQL enforce this.
- A correction creates a new mutable working revision and copies answers plus current evidence references.
- Evidence lineage uses `source_evidence_revision_id`; ciphertext, key version, and fingerprint are copied without decrypting the locator.
- Earlier submissions and correction requests are append-only and remain unchanged.
- Resubmission creates the next immutable submission sequence and records both the prior submission and answered correction request.

### 3D.6 — PostgreSQL and security hardening

- Added exact application/working/draft/manifest binding triggers.
- Added exact form-question, requirement-set, source-draft evidence, and correction-target membership triggers.
- Added append-only protection for submissions, snapshots, history, screening events, and correction records.
- Added consumed-draft answer/evidence guards and application transition/pointer coherence guards.
- Added immutable reviewer-grant and one-time complete revocation guards.
- Audit records contain safe IDs, state, sequence, manifest, and correlation data. Answer values and evidence locators are excluded.

## Database migrations

1. `2026_10_19_400000_create_phase_3d_application_lifecycle.php`
2. `2026_10_19_400001_harden_phase_3d_application_guards.php`
3. `2026_10_19_400002_enforce_phase_3d_state_coherence.php`

New tables:

- `applications`
- `application_working_revisions`
- `application_submissions`
- `application_submission_answers`
- `application_submission_requirements`
- `application_submission_evidence_items`
- `application_state_history`
- `application_screening_events`
- `application_correction_requests`
- `application_correction_items`
- `synthetic_application_reviewer_assignments`

Existing tables receive only compatibility links: `application_drafts.application_id` and `evidence_reference_revisions.source_evidence_revision_id`.

## Permissions

SCHOOL receives explicit submit, own tracking, own corrections, and resubmission permissions. CHEDRO receives separate intake, answer-view, evidence-open, screening, correction-request, and history permissions. Admin receives synthetic application-reviewer assignment management. Role membership alone never satisfies the resource checks.

## Verification results

| Check | Result |
| --- | --- |
| Clean PostgreSQL migration | Passed from an empty testing database; all migrations through `400002` applied |
| Forward upgrade | Passed on the existing development database; `400000`, `400001`, and `400002` applied without rebuilding earlier data |
| Focused Phase 3B–3D applicant/lifecycle tests | Passed after stabilization: 18 tests, 94 assertions |
| Complete PostgreSQL PHPUnit suite | Passed after stabilization: 72 tests, 288 assertions |
| Laravel Pint check | Passed |
| Frontend production build | Passed with Vite 8.3.4 |
| Git whitespace check | Passed |

Focused coverage confirms initial submission, duplicate retries, immutable snapshots, consumed-draft rejection, exact evidence lineage, correction/resubmission sequence, assignment and region denial, hidden-answer suppression, and the earlier Phase 3B/3C authorization and evidence guarantees.

## Manual synthetic acceptance procedure

1. As Admin, create an effective **Test application reviewer** assignment for a synthetic CHEDRO user, region, and module.
2. Confirm that the reviewer also has an active regional assignment.
3. As a verified synthetic SCHOOL user, open **My applications** and start a questionnaire from an applicant-sandbox manifest.
4. Complete all applicable questions and required document links, then use **Check progress**.
5. Open Review and select **Submit test application**. Confirm that the application opens in read-only tracking and that no official tracking number or decision appears.
6. As the assigned CHEDRO reviewer, open **Application intake**, select the application, and choose **Start screening**.
7. Inspect only applicable questionnaire answers and exact submitted document references.
8. Request a correction with a plain-language summary and exact target.
9. Return as the applicant, open **Make corrections**, edit and save the new working revision, then resubmit.
10. Confirm the earlier submission remains unchanged and the status history shows the new resubmission.
11. Retire the manifest and confirm tracking remains readable while new editing, correction, and resubmission are unavailable.
12. Attempt access as another SCHOOL user and an unassigned or out-of-region CHEDRO user; access must fail.

Automated acceptance is complete. First-time testing with actual OSDS/CHEDRO personnel is pending. Representative screenshots were not captured because the development database did not contain a complete synthetic 3D intake scenario after the isolated test database was reset; no visual verification is claimed for those screens in this report.

## Known limitations and stakeholder decisions

- All data and workflows are synthetic and nonofficial.
- There is no final acceptance, rejection, withdrawal, routing decision, acknowledgment number, official tracking number, validation, registry, certificate, signature, issuance, or RA 11180 function.
- One active application per organization/module/manifest is a provisional sandbox rule.
- Correction categories and operational service levels need stakeholder approval before real use.
- General questionnaire-answer disclosure remains limited to applicable answers and a dedicated permission; privacy classification and field-level rules remain production blockers.
- Production privacy notice, lawful basis, retention, records disposition, identity assurance, MFA, hosting, monitoring, recovery, VAPT, and accessibility assessment remain unresolved.
- External Google links remain references; syntax and authorized opening do not prove accessibility, authenticity, ownership, or document integrity.

Phase 3D is complete. Phase 4 has not started.

