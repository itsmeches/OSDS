# B4 — Complete COR and ACSA UI acceptance report

**26 October 2026. Functional technical rehearsal: PASS. Sampled automated accessibility: FAIL. Supervisor/business acceptance: BLOCKED pending actual review.** B4 execution is stopped for review. No application runtime fix was made. Batch G remains CONDITIONAL CLOSE with its separate sign-offs pending.

## What was proved

Both modules completed independent journeys through real user interfaces and existing application handlers. Neither main journey started from a service-prepared questionnaire, application, decision, issuance request or document.

| Required journey | COR | ACSA |
| --- | --- | --- |
| OSDS creates/saves questionnaire; separate reviewer approves; OSDS publishes applicant intake | PASS | PASS |
| Applicant creates draft, supplies answers/evidence reference and submits R1 | PASS | PASS |
| Evaluator requests correction; applicant changes organization/address and resubmits R2 | PASS | PASS |
| Technical assessment, distinct Chief recommendation, distinct Director approval on R2 | PASS | PASS |
| Supplementary questionnaire/configuration review and exact issuance-definition publication | PASS | PASS |
| Applicant saves/resumes and submits a case-linked issuance request | PASS | PASS |
| OSDS configures fictional personnel and bounded template; reviewer publishes exact versions | PASS | PASS |
| Explicit OSDS generation; applicant downloads exact privately stored PDF bytes | PASS | PASS |
| Actual PDF: corrected R2 identity/address, module title, immutable linkage and every-page markings | PASS | PASS |

The successful cases each have two immutable submissions, one submitted issuance request, one completed original PDF and **one render attempt**. The evaluator, Chief and Director are distinct synthetic account IDs. This proves account separation in this rehearsal; it does not identify a person operating multiple accounts.

Primary evidence: [browser results and exact linkage records](screenshots/b4-end-to-end/browser-results.json), [actual PDF inspection](screenshots/b4-end-to-end/pdf-inspection.json), [32-capture manifest](screenshots/b4-end-to-end/capture-manifest.json).

## Environment, preparation and source preservation

- Database: **`osds_ui_browser_test` only**. Preparation checks both configured PostgreSQL identity and actual `current_database()`. The isolated request router checks both before application handling. No migration, reset, synchronizing seeder or manual database operation was run.
- Namespace: `b4_20261026_01`. Nine new synthetic identities received explicit individual permissions: OSDS, configuration reviewer, evaluator, Chief, Director, applicant, South-region probe, COR-only probe and ACSA-only probe. Two fictional HEIs, verified representation, effective eligibility and exact region/module assignments were prepared. No existing role bundle was expanded.
- Backend preparation created foundational records only. Every successful business/configuration mutation used real UI controls. Read-only observers inspected resulting rows, private file hashes and linkage. Named permission-revocation/separation probes changed only the approved namespace's foundational grants and restored them afterward.
- Existing isolated applicant publications and conflicting issuance/template publications were retired/withdrawn through authorized UI controls. Records and completed artifacts were retained. No records were reset or deleted.
- Git HEAD remained **`3448f26801f1dc90aa7b890771a137b567b92ea6`**. The initial dirty tree already contained B3 changes. **All 357 baselined application/configuration/migration/view/route/test/manifest files and all 1,563 baselined historical artifacts remained hash-identical. All ten published evidence JSON files remained byte-for-byte unchanged; restoration was unnecessary.** See [preservation checks and before/after Git state](screenshots/b4-end-to-end/preservation.json) and [source hashes](screenshots/b4-end-to-end/source-hashes.json).

Private provenance is retained under `storage/app/private/b4/`: baseline JSON, initial Git diff, original evidence copies, fixture/journey ledgers and full browser traces. Full traces contain sessions/request data and stay private; safe failure summaries and synthetic screenshots are in the evidence directory.

## Negative scenarios

| Scenario | Actual outcome |
| --- | --- |
| South-region access to both North cases | PASS — HTTP 404; no case disclosure through intake. |
| COR-only actor opens ACSA; ACSA-only actor opens COR | PASS — HTTP 404. |
| Evaluator receives recommendation permission and tries to act as Chief | PASS — actual mounted UI POST returns HTTP 403; stage counts unchanged. Temporary permission removed. |
| Chief permission revoked after mounting the action | PASS — actual UI POST returns HTTP 403; no stage persisted. Permission restored before the real Chief journey. |
| Chief action mounted on R1, then correction and real R2 resubmission occur in other UI sessions | PASS — stale action rejected with “The submitted revision changed”; case/stage data unchanged. Fresh R2 view has no inherited recommendation readiness. |
| R1 evaluator attempts Chief on R2, after someone else evaluates R2 | PASS — HTTP 403; no additional stage. This exercises separation across superseded revisions. |
| Mounted generation form selects a subsequently withdrawn COR template or ACSA personnel mapping | PASS — exact-version validation rejects generation; zero document rows for those attempts. Replacements were created/reviewed/published through UI. |
| Explicit duplicate generation handler request after original completion | PASS — existing original returned without a second render attempt or changed bytes/snapshots. Integrity-verified access correctly adds an authorized-read audit event. |
| Submission response lost after real ACSA issuance submission | PASS — server commits once; response is deliberately dropped. Read-only observation/reload finds one submitted original; no consequential replay is performed. |
| Completed document's template and personnel versions are later withdrawn | PASS — applicant UI downloads remain checksum-identical; original bindings/snapshots remain unchanged. |
| Wrong-region/module or unpermitted staff downloads private PDF | PASS — five actual HTTP 403 denials. |

The auxiliary COR case used for stale/case-wide separation probes was also created, corrected and resubmitted through UI. It intentionally remains under screening after a different evaluator's R2 assessment; it is not one of the two successful main cases. See [stale UI results](screenshots/b4-end-to-end/stale-ui-results.json).

These are named browser interleavings and handler probes, not a new universal concurrency guarantee. Existing B1–B3 independent PostgreSQL contention evidence remains historical evidence for unchanged services and constraints.

## PDF inspection

Each output is **two pages**. PDF.js text inspection, rendered-page images and private-file/download SHA-256 comparisons confirm:

- Correct R2 organization and **2 Revised Lane**, with no R1 address.
- COR's existing B3 title **CERTIFICATE OF RECOGNITION**, and ACSA's **AUTHORITY TO CONDUCT SPORTS ACTIVITY**.
- Exact internal numeric case, submission, Director decision, template and personnel mapping references. These are immutable technical bindings, not official registry numbers.
- Fictional director/office details, no signature and no legal-validity claim.
- Fixed **DRAFT — DEMONSTRATION ONLY** and the nonofficial disclaimer on **every page**, including the supplementary appendix.

Artifacts: [COR PDF](screenshots/b4-end-to-end/registration-DEMONSTRATION.pdf), [ACSA PDF](screenshots/b4-end-to-end/acsa-DEMONSTRATION.pdf). Representative certificate/appendix raster images were visually inspected. This does not certify PDF accessibility, native print acceptance or legal wording.

## Accessibility result — acceptance gate failed

**32 representative captures: 27 passed, 5 failed. All 32 had no measured horizontal overflow.** Successful phase runs recorded no uncaught page errors. Every capture used unrestricted default axe plus enabled target-size; no rule allowances or suppressions were added.

Failures remain visible in the manifest:

1. Four questionnaire-builder readback captures report nested/duplicate main landmarks (`landmark-main-is-top-level`, `landmark-no-duplicate-main`, `landmark-unique`) and button visible-label/accessible-name mismatches (`label-content-name-mismatch`). Original editable-builder captures show the same findings.
2. One ACSA evaluator light-desktop capture reports notification-title contrast (`color-contrast`, `.fi-no-notification-title`). Its cause has not been independently diagnosed. It remains a failure; no timing-based waiver is claimed.

The 32-capture matrix covers eight checkpoints per module at 1440px light desktop and 375px dark mobile. The author checkpoint uses final immutable readback captures with explicit theme selection. Earlier editable captures and an earlier inherited-theme attempt are retained separately, not relabelled as the final matrix. Notifications and finite animations are settled before sampling; this does not certify every timed state.

**Proposed follow-up, requiring separate runtime-fix authorization:** correct builder landmark structure and accessible button names; reproduce/diagnose the notification contrast finding; rerun affected browser/accessibility checks and the regression gates appropriate to any approved fix. No such fix was implemented during B4. Human keyboard, zoom, screen-reader and business acceptance remain pending.

## Retained attempts and verification limits

The rehearsal harness evolved during execution. Retained raw `run-*.json`, safe failure screenshots and private traces include incorrect dialog-role/label locators, actions before hydration/debounce completion, redirects not yet settled and a stale withdrawal-button locator after withdrawal had committed. Earlier rapid authoring saved incorrect labels/question counts; those unused configurations remain in history, and fresh configurations were saved, reloaded and checked before applicant publication. They were not silently repaired through database writes.

Other retained attempts include:

- An initial COR response-loss interceptor did not match Livewire's hashed URL. The request submitted normally once. Inspection preserved it; the corrected interceptor was exercised on ACSA.
- The first duplicate-generation comparison incorrectly expected no history change. The actual change was the required authorized-read event; document bytes, bindings and render-attempt count were unchanged.
- The first PDF inspector expected a public case UUID in the printed footer. Existing B3 prints immutable numeric IDs. The corrected inspector checks the actual exact case/stage/version bindings; no PDF or renderer changed. See [retained inspection attempt](screenshots/b4-end-to-end/pdf-inspection-first-attempt.json).

No application handler, authorization guard, audit contract or persistence assertion was replaced to obtain success. Unexpected outcomes were inspected before further explicit actions. There is no claim that all intermediate attempts passed or used the final harness bytes.

Full Pint `--test`, syntax checks for the two new PHP helpers and four JavaScript helpers, and tracked/new-file whitespace checks passed. Application assets and runtime behavior were unchanged, so no new asset build or complete PostgreSQL regression run was performed solely for the harness. The existing B3 **431 tests / 4,627 assertions** checkpoint is reused as recorded historical regression evidence, not a new B4 test pass. The task-owned port-8040 server was stopped; unrelated servers were preserved.

## Files delivered

- [Acceptance plan, supervisor script and business checklist](osds-b4-end-to-end-acceptance-plan.md).
- This report and safe evidence under `docs/screenshots/b4-end-to-end/`.
- `scripts/Prepare-B4.php`: guarded foundational preparation, scoped grant probes and read-only inspection.
- `scripts/b4-browser-server.php`: isolated request router; no substituted business handlers.
- `scripts/rehearse-b4.mjs`: staged real-UI rehearsal and negative probes.
- `scripts/verify-b4-stale-ui.mjs`: auxiliary multi-session revision/separation journey.
- `scripts/inspect-b4-pdfs.mjs`: actual output inspection/rasterization.
- `scripts/package-b4-evidence.mjs`: assertions and safe evidence packaging.

The helpers are controlled rehearsal tooling, not deployment/seed instructions. Fixture ledgers prevent silent preparation replay; inspect uncertain outcomes before selecting a subsequent phase.

## Supervisor review and next authorization

Use the plan's demonstration script and unchecked business checklist. The two main cases and completed PDFs provide a reviewable result, but **no supervisor has accepted them in this rehearsal**. Intake's Registration label versus B3's Recognition title still needs business disposition. Real checklists, jurisdiction/signatory policy, legal wording and processing-clock semantics are not approved by technical success. B2's confirmed synthetic eligibility contract remains unchanged; B4 adds no nine-day or both-modules gate.

Final exact document configuration versions were withdrawn during the retention probe; completed originals remain readable. Another isolated rehearsal can publish separately reviewed replacements through UI. Do not republish or rewrite historical versions automatically.

`osds_dev` and every manual database remain unchanged. Manual supervisor-demo activation, migrations and individually scoped permission provisioning require separate authorization. No production capacity certification, official issuance, registry numbers, signatures, seals, notification delivery, deployment, commit, push, Batch H or Phase 4 is included.

**Stop after B4 technical rehearsal and evidence packaging. Functional journeys passed; accessibility fixes and actual supervisor/business review remain outstanding.**
