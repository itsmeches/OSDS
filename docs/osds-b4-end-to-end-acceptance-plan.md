# B4 — Complete COR and ACSA UI acceptance plan

26 October 2026. Authorized isolated technical rehearsal. Supervisor/business acceptance remains pending. Batch G remains CONDITIONAL CLOSE.

## Boundaries and provenance

Use only `osds_ui_browser_test`, checking both configured PostgreSQL connection and `current_database()` before foundational preparation. Preserve existing records. No manual database activation, runtime fixes, migrations, role-bundle synchronization, full regression rerun, commit, push or deployment is authorized by this rehearsal.

Before preparation, source/Git/historical hashes were captured in the private B4 baseline. All ten published evidence JSON files were copied privately for byte-preserving comparison. New evidence belongs only in `screenshots/b4-end-to-end/`; credentials, session state and detailed browser traces stay private.

## Foundational fixtures

Namespace `b4_20261026_01`: nine distinct identities — OSDS author/generator, configuration reviewer, evaluator, Chief, Director, applicant, South-region probe, COR-only probe and ACSA-only probe. Explicit individual grants, North/South region assignments and exact module assignments apply. Two fictional HEIs have verified applicant representation and effective synthetic eligibility. Existing module references are reused. The private fixture ledger records IDs; it creates no questionnaires, applications, decisions, issuance definitions/requests, personnel versions or documents.

## Acceptance matrix

Repeat each positive scenario independently for COR and ACSA through actual UI controls and handlers.

| Stage | Required evidence |
| --- | --- |
| Questionnaire | Author creates questions and evidence requirement; separate reviewer approves exact pair; author publishes applicant intake. Existing publication conflicts must be retired through authorized UI. |
| Application | Applicant selects institution/questionnaire, enters fictional R1 organization/address and synthetic Drive locator, saves/submits. |
| Correction | Evaluator starts screening and requests correction; applicant enters corrected R2 organization/address and resubmits. |
| Three-stage decision | Evaluator attests exact R2; distinct Chief recommends; distinct Director approves; applicant sees public history without internal notes. |
| Issuance definition | Supplementary configuration created/reviewed through UI; definition binds exact intake organization/address questions, reviewed and published. |
| Issuance request | Applicant reviews protected R2 fields, saves draft, submits original request. |
| Document configuration | Fictional directory and versioned template/personnel mapping created, reviewed and published through UI. |
| PDF | Staff explicitly generates; applicant downloads exact stored bytes. Inspect checksum, linkage, module title, corrected R2 details and mandatory fixed markings on every page. |

## Negative scenarios

Wrong-region/module access; case-wide same-person role mixing; stale submitted revision after correction; duplicate request/generation; effective permission revocation; template/personnel withdrawal; uncertain browser response followed by explicit read-only inspection. Verify rejected operations persist no unintended stage/request/document. Never bypass unavailable UI stages through service preparation.

## Capture matrix

Eight checkpoints per module: questionnaire authoring, applicant submission, correction/resubmission, evaluation, recommendation/decision, issuance definition, applicant issuance request, stored document/download. Capture each at light desktop 1440px and dark mobile 375px: 32 representative screenshots total. Run unrestricted default axe with target-size enabled, overflow and uncaught-error checks after finite animations/notifications settle. These sampled automated checks cannot establish human accessibility acceptance.

## Supervisor demonstration script

1. Explain synthetic-only scope and nonofficial outputs.
2. Show questionnaire creation/review/publication and applicant intake.
3. Show R1 correction and immutable R2 resubmission history.
4. Switch among evaluator, Chief and Director accounts; explain separation.
5. Show versioned issuance definition and protected applicant details.
6. Generate explicitly and download the stored watermarked PDF.
7. Repeat independently for the second module.
8. Show representative denials and immutable completed-document behavior.
9. Record supervisor findings and explicit acceptance separately.

## Business acceptance checklist

- [ ] COR business terminology/checklist approved by OSDS.
- [ ] ACSA business terminology/checklist approved by OSDS.
- [ ] Actor responsibilities and correction summaries accepted.
- [ ] Output wording, date/validity treatment and fictional personnel understood.
- [ ] Both full UI journeys witnessed and accepted.
- [ ] Keyboard, zoom, screen-reader and document print review completed by people.
- [ ] Unresolved defects assigned owners and disposition.

Any application limitation blocks the affected scenario. Record actual results and retained failure evidence; request separate authorization for runtime fixes. Reuse recorded B1–B3 regression results only as historical evidence for unchanged behavior, never as a new B4 pass.

## Executed outcome

See the [B4 execution report](osds-b4-end-to-end-acceptance-report.md). Both functional journeys passed. The 32-capture accessibility matrix failed in five samples; runtime fixes and actual supervisor/business review remain outstanding.
