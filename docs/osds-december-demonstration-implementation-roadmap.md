# OSDS December 2026 — Demonstration implementation roadmap

**21 October 2026. Confirmed delivery target: a working end-to-end demonstration using synthetic data only, for both COR and ACSA. Production deployment is not required.** This is a documentation plan, not implementation authorization. Batch G remains **CONDITIONAL CLOSE pending separate management sign-offs**. No Batch H or Phase 4 begins through this plan.

## 1. Fixed scope and remaining decisions

**CMO/context update:** [consolidated findings and alignment](osds-cmo-artifacts-and-project-alignment.md) distinguish technical evaluation, Chief recommendation and Regional Director decision from the generic CHEDRO labels below. Confirm the synthetic stage/authority adaptation before B1; do not equate recommendation with approval. National-association OSDS jurisdiction, source terminology/checklist discrepancies and missing final output template remain explicit questions. The infographic's nine-day timeline is not an implemented or approved SLA; Annex A differs.

The confirmed journey for **each module** is: authorized OSDS demonstration questionnaire configuration/publication → synthetic HEI answers/evidence/submission → scoped CHEDRO screening/correction → immutable revised submission → authorized exact-revision approval/rejection → versioned case-linked **DRAFT / DEMONSTRATION ONLY** document → HEI result viewing and CHEDRO history inspection. Reuse current publication authorization; OSDS responsibility does not imply silently granting all publication permissions to the OSDS role. Fixture staff must have explicitly authorized existing configuration permissions.

The draft output, synthetic-only boundary and second-module acceptance are settled scope, not questions to reopen. First module, terminology, actual demonstration approval/rejection rules and template content remain **PROPOSED — REQUIRES OSDS CONFIRMATION**. Keep both modules distinct; no COR prerequisite for ACSA is assumed. Use the [essential decision checklist](osds-first-slice-supervisor-decision-package.md) and [first-module technical contract](osds-first-slice-implementation-contract-draft.md).

## 2. Reuse map and bounded changes

| Existing component | Reuse / required addition |
| --- | --- |
| [FormBuilder](../app/Filament/Pages/FormBuilder.php), [Questionnaires](../app/Filament/Pages/Questionnaires.php), QuestionnaireAuthoringService/QuestionnaireDraftService/ConfigurationValidator/ConditionalLogic under app/Services/Questionnaires | Reuse accepted question/document selection, conditions and validation. Configure owner-confirmed demonstration content, not a new form engine. |
| [QuestionnairePublicationService](../app/Services/Questionnaires/QuestionnairePublicationService.php), [ViewConfigurationReviewRequest](../app/Filament/Resources/ConfigurationReviewRequests/Pages/ViewConfigurationReviewRequest.php) | Reuse exact-pair configuration review, explicit publication and applicant_sandbox channel. Configuration approval remains separate from application decision authority. |
| [DraftEditor](../app/Livewire/Applicant/DraftEditor.php), [ApplicantDraftService](../app/Services/Applicants/ApplicantDraftService.php), [EvidenceReferenceService](../app/Services/Evidence/EvidenceReferenceService.php), [applicant routes](../routes/web.php) | Reuse answers, synthetic file-link evidence, completeness, Save and submission UI. Do not add uploads or live onboarding for this target. |
| [ApplicationLifecycleService](../app/Services/Applications/ApplicationLifecycleService.php), [SyntheticApplicationIntake](../app/Filament/Pages/SyntheticApplicationIntake.php) | Retain submit/screen/correct/resubmit behavior. Add only confirmed exact-revision final outcomes, decision history/audit and scoped UI. |
| [ApplicationPolicy](../app/Policies/ApplicationPolicy.php), [ReviewerQueueScope](../app/Services/Authorization/ReviewerQueueScope.php), ScopeAuthorizer and synthetic reviewer assignments | Preserve owner tracking and exact region/module pair plus effective regional scope. Define explicit new decision/document authority after OSDS confirmation; no role-name or configuration-review shortcut. |
| [lifecycle migration](../database/migrations/2026_10_19_400000_create_phase_3d_application_lifecycle.php), [binding guards](../database/migrations/2026_10_19_400001_harden_phase_3d_application_guards.php), [coherence guards](../database/migrations/2026_10_19_400002_enforce_phase_3d_state_coherence.php) | Preserve immutable submissions, version increments, consumed-draft/history protection and routing snapshots. Future approved forward migration must extend final states/coherence narrowly and explicitly dispose repeat-case uniqueness. No migration runs in planning. |
| [AuditRecorder](../app/Services/Auditing/AuditRecorder.php), existing safe feedback/recovery patterns | Reuse redaction and operation-owned transaction patterns. New decision and document audit meanings need confirmation; do not assume Application has a Core observer or every history event is an AuditEvent. |
| [WorkflowOverview](../app/Filament/Widgets/WorkflowOverview.php) | Optional P2 scoped case counts only after final states settle; existing counts concern configuration work. |
| ApplicantSandboxTest, QuestionnaireConfigurationTest, FormMakerTest, StaffRefinementTest, StaffListScopeTest, IdentityAuthorizationTest under tests/Feature; accepted K/G-INT tests | Preserve current regression coverage. Add bounded decision/document tests and separate COR/ACSA acceptance. Recorded full baseline is 345 tests / 3,779 assertions; no new run claimed. |

## 3. Dependency-ordered backlog and effort

Estimates are focused developer person-days including targeted verification/documentation, assuming one developer familiar with this repository and prompt OSDS review. They are not commitments. Rule-review latency is additional. No production integration or official issuance contingency is hidden in the total.

| ID / priority | Deliverable and exit condition | Depends on | Effort |
| --- | --- | --- | --- |
| B0 / P0 | Confirm first module/name, demo decision/actor matrix, two module checklists and output template variants; exact December date/reviewer availability. Record unresolved answers explicitly. | Supervisor meeting | 1–2 days preparation; target owner response within one week |
| B1 / P1 | First-module exact-submission decision record/service/UI, approved outcome constraints, history/audit atomicity, fresh authority/stale-version checks. Approval and rejection controls pass. | B0 and explicit implementation authorization | 5–8 days |
| B2 / P1 | Versioned private draft generation/retrieval, embedded watermark, exact decision/submission/template linkage, controlled generation failure/recovery and access tests. | B0 template/authority; B1 decision binding | 4–6 days |
| B3 / P1 | Second-module approved configuration and genuine workflow differences; independent submission→correction→decision→document acceptance. Shared engine reused. | B1/B2; second-module rules from B0 | 3–5 days |
| B4 / P1 | Guarded deterministic fixtures and repeatable browser runner/runbook; denied actor/pair, stale tab, interruption/uncertainty and document recovery; both-module evidence. Prepare fixture design during B1, complete against B3. | B0, final integration B3 | 2–3 days |
| B5 / P1 | Serialized final regression/preservation, representative accessibility checks, OSDS/CHEDRO rehearsal, defect triage and demonstration freeze. Exact results and known limitations recorded. | B1–B4 | 3–5 days plus user-review availability |
| B6 / P2 | Minimal authorized case counts by module/state and narrowly needed usability/accessibility corrections. No broad reporting/export redesign. | B3 states; approved scope/time | 1–3 days if capacity remains |

**P1 total: 17–27 developer days; P0 preparation: 1–2 additional days.** Optional P2 is separate. Reserve calendar buffer for review/findings; do not replace P1 evidence with optional dashboard work. Accessibility defects affecting completion of the required journey must be triaged even if broader polish is P2. If decisions arrive late, rebaseline dates with OSDS rather than inventing policy or quietly dropping a module.

These estimates remain provisional pending the CMO actor-stage mapping. If distinct recommendation/validation states or extra document actions are required, revise B1/B3/B4 and milestones explicitly; the earlier compact decision slice does not already include an unestimated multi-stage workflow. Downloadable ZIP forms are inventoried source assets only; their review and any future download feature are separate from case-generated draft outputs and are not silently added to this estimate.

## 4. Proposed milestones

| Target window | Reviewable outcome |
| --- | --- |
| 22–28 October | B0 meeting, confirmed first-module contract/template and second-module differences; implementation authorization sought only after decisions. |
| 29 October–11 November | B1 first-module final decisions verified against preserved screening/correction loop. B4 fixture preparation alongside. |
| 12–25 November | B2 linked draft output and B3 second-module integration; first complete two-module demonstration. |
| 26 November–9 December | B4/B5 repeatable browser recovery, scoped authorization tests and representative user acceptance; P2 only if core journey is stable. |
| 10–16 December | Demonstration scope freeze, executed final gates under later authorization, approved runbook and exact-environment rehearsal. |
| 17–31 December | Demonstration on the supervisor-confirmed date, contingency and agreed findings. Adjust earlier if the actual deadline precedes this window or holiday availability is limited. |

No milestone includes production deployment, official issuance or permission to start a new development phase.

## 5. Proposed deterministic synthetic fixture plan

This section is a preparation design; **no fixtures have been created or reset**. Reuse patterns in [DevelopmentFixtureSeeder](../database/seeders/DevelopmentFixtureSeeder.php), [SyntheticQuestionnaireSeeder](../database/seeders/SyntheticQuestionnaireSeeder.php), QuestionnaireReferenceSeeder and existing isolated browser preparation scripts. SyntheticQuestionnaireSeeder currently contains provisional content; replace assumptions only through approved demo configuration, not by treating its questions as business policy.

| Fixture group | Proposed contents and invariants |
| --- | --- |
| Guarded environment | Tests only on osds_test; browser rehearsal only on approved osds_ui_browser_test. Assert environment/database before setup; retain ApplicantSandboxGuard and synthetic identity/org restrictions. Do not inspect/reset manual-testing databases. |
| Identities and scope | Fictional OSDS configuration staff with approved existing publication permissions, synthetic HEI representative with effective eligibility/representation, CHEDRO with exact module/region assignment and scope, plus a second region/actor for denials. Separate validator/decider only if confirmed. No hard-coded reliance on numeric IDs or role names alone. |
| Module data | Fixed symbolic fixture keys per run/module/case; resolve generated UUIDs dynamically. Versioned registration/acsa questionnaire+requirements pair, conditional question, selected document and safe fictional file locator. Approved template version/checksum for each module/outcome. |
| Cases | Per module, separate approval and rejection cases proposed to cover both supported branches; use the full correction/resubmission journey for each scripted branch. Never mutate one terminal case into another outcome. Confirm whether both branches must be shown live; retain automated acceptance for both. |
| Repeatability | Stable labels/order, frozen test clock where appropriate, effective dates spanning rehearsal, known answer/evidence content and expected revision counts/hashes. Publish exact configurations through existing services, not direct bypasses. Use unique run namespaces and explicit isolated preparation rather than repeating consequential actions. |
| Evidence | Record module, run key, source commit/dirty checkpoint, submission public ID/sequence/hash, decision, template/output checksum, actor/scope and browser screenshots/results. Exclude credentials, private locators and sensitive answers from reports/logs. |
| Failure controls | Fault injection only in approved isolated harnesses; serial database preparation. Retain failed attempts. Never retry a decision/submission merely to get a passing screenshot. Saved-state inspection determines uncertain outcomes. |

Preparation must be bounded to disposable fixtures, with cleanup/recreation instructions and before/after assertions. Versioned ready documents/history are not overwritten to reset a demonstration; prepare new fixture cases or an explicitly disposable environment. All setup/execution requires later authorization.

## 6. End-to-end acceptance checklist — separate module evidence

Mark PASS / FAIL / NOT VERIFIED **independently for registration and acsa**, with artifact references. All entries currently **NOT VERIFIED for the new final-decision/document journey**; existing component evidence remains reusable, not a new integrated pass.

| Acceptance item | COR/registration evidence | ACSA evidence |
| --- | --- | --- |
| Confirmed terminology, questionnaire/checklist, outcomes and template version recorded | Pending | Pending |
| Authorized configuration staff edit/review/publish exact applicant_sandbox pair through real UI; no application decision authority inferred | Pending | Pending |
| Synthetic HEI fills required/conditional answers, supplies permitted evidence, saves and submits immutable sequence 1 | Pending | Pending |
| Assigned CHEDRO screens exact case and requests targeted correction; unassigned/out-of-region actor denied | Pending | Pending |
| HEI corrects/resubmits sequence 2; sequence 1 answers/evidence/hashes/routing and history unchanged | Pending | Pending |
| Authorized CHEDRO decides exact current revision; stale/foreign revision, missing authority and invalid state denied without partial persistence | Pending | Pending |
| Approved and rejected branch controls exercised on separate cases; correct actor/reason/state/history and one approved audit event | Pending | Pending |
| Private versioned draft generated from exact decision/submission/template; embedded DRAFT / DEMONSTRATION ONLY watermark and checksum; no official identifiers/signature claims | Pending | Pending |
| HEI views own result/draft under approved visibility; CHEDRO inspects history; other HEI/region and direct artifact access denied | Pending | Pending |
| Audit faults/DB rejection roll back decision; rendering/finalization failure never exposes falsely ready draft; decision remains inspectable | Pending | Pending |
| Double click, aborted request, lost committed response, session/permission loss show safe feedback and inspection; no automatic consequential replay | Pending | Pending |
| Repeatable fixture/browser journey, representative keyboard/axe/reflow review and executed regression/preservation results recorded | Pending | Pending |

Shared service tests may verify common mechanics, but each module needs its own full browser/persistence acceptance. No synthetic demonstration is labelled a legally issued certificate or production-ready service.

## 7. Essential supervisor checklist and Batch G disposition

Use D1–D10 in the [updated decision package](osds-first-slice-supervisor-decision-package.md). Confirm only demonstration essentials: first module/name, exact date/acceptance owner, decision entry/prerequisites/actors/separation/reasons, template fields/variants/audience, reapplication behavior and new audit/recovery expectations. Synthetic-only, both modules, watermarked draft and exclusion of production are already confirmed.

Batch G management sign-offs remain separate. Before rehearsal, obtain disposition for the Institution selector target and record demonstration-critical accessibility limitations. If eligibility administration is shown, disclose its current audit contract and obtain the relevant decision. Rehearse K5 saved-list uncertainty recovery; do not declare its original timeout diagnosed. Nine service-only deferrals, broader human accessibility and bounded performance limits remain tracked with their existing owners/approval needs, not silently accepted or reopened.

## 8. Exclusions and deferred production backlog

The following are **not December demonstration requirements**: production deployment/cutover, live applicant onboarding, official registry or number allocation, legally issued certificates, external/digital signatures, production identity/storage/other integrations, SMS/email delivery, historical migration/repair and production capacity certification. Do not weaken sandbox guards or add public artifact links to simulate delivery.

Keep a separate future backlog for approved production identity/recovery, real enrollment/jurisdiction, evidence custody/retention, official registry/signatory/issuance, delivery channels, hosting/security clearance/backup/support, migration/cutover and capacity. No estimates, approval or deadline for these are inferred from this demonstration plan. RA 11180 and unrelated programs are not added to this COR/ACSA plan; any separate commitment needs its own supervisor scope decision.

**Implementation authorization gate:** confirmed essential decisions + reviewed first-module contract + explicit permission to implement/test that slice. This plan authorizes no code, tests, migrations, database operations, commit/push/deployment, Batch H, Phase 4 or new security inventory. **Stop for review.**
