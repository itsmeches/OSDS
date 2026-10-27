# Gate B1 attempt 02 — Remediation and expectation audit

**27 October 2026. Source-only review complete. One justified test expectation corrected; static checks PASS. No PHPUnit, Laravel, database or browser execution.** Both failed attempts remain unchanged historical evidence. Gate B2–B4 remain closed; Batch G remains CONDITIONAL CLOSE.

## Contract and confirmed root cause

Reviewed the failing test and surrounding recovery assertions, the complete `app/Livewire/Applicant/IssuanceRequests.php` component, its request Blade view, the approved [Phase 3 plan](osds-ui-ux-phase-3-dashboard-plan.md) and [implementation report](osds-ui-ux-phase-3-dashboard-report.md). The report's prior component results are historical, not new authenticated verification.

The approved plan, section 4, requires separate application/request/document guidance. A submitted request is read-only; staff must explicitly prepare its PDF. A draft is not a submitted request, and request submission must not imply PDF completion or official issuance. The implementation report explicitly records this presentation change while retaining handlers, service reasons, queries and document lifecycle protections.

The view's absent-document branch at `resources/views/livewire/applicant/issuance-requests.blade.php:39` says:

> No demonstration PDF has been generated. A saved draft is not a submitted request. Authorized staff must explicitly prepare the PDF after a qualifying request is submitted.

That wording implements the approved separation. The failed assertion expected the obsolete page wording “No certificate has been generated.” This is a **confirmed test expectation mismatch**. No requirement for restoring the old wording was found. No runtime correction is justified by this failure.

Importantly, the component's immediate submission feedback still says “Demonstration request submitted. No certificate has been generated.” This is a separate existing message and remains covered by its matching test. A broad replacement would incorrectly change that valid expectation.

## Exact correction

Only `tests/Feature/CaseIssuanceRequestTest.php` changed. In `test_draft_recovery_uses_explicit_new_version_and_submitted_snapshot_survives_withdrawal`, the former line 219 now reads across lines 219–221:

```php
Livewire::actingAs($s['applicant'])->withQueryParams(['application' => $s['case']->public_id])->test(IssuanceRequests::class)
    ->assertSee('No demonstration PDF has been generated. A saved draft is not a submitted request. Authorized staff must explicitly prepare the PDF after a qualifying request is submitted.')
    ->assertSee('Fictional contact');
```

The literal full-sentence expectation independently checks the absent PDF, draft/submission distinction and explicit staff-generation prerequisite. It does not read the expected string from the implementation or remove the fictional-answer assertion.

All prior scenario setup and assertions remain unchanged: old draft retention, explicit replacement definition creation/review/publication, two saved request rows, definition withdrawal and same-key retrieval of the original submitted request. Existing submission-state, immutable snapshot, authorization, idempotency, audit and database assertions elsewhere in the file are untouched. No new state or generation action was introduced.

## Nine-file source audit

Reviewed text-facing assertions (`assertSee`/negative variants, string comparisons, guidance data-provider labels, feedback expectations and related action-state labels) across the complete fixed selection. Distinguished UI copy from fixture-provided answers/titles, service errors, privacy probes, database values and exact PDF bytes. Assertions in those latter categories were not rewritten to match presentation text.

| Targeted file | Source-level finding / disposition |
| --- | --- |
| `DashboardGuidanceTest.php` | Reviewed recommendation sentence at 59 and evaluation/final-decision phrases at 60–61; match permission descriptions. No-task and zero-stat phrases at 68, 90 and 92 remain implemented. Previous reviewed remediation retained. |
| `ApplicantApplicationGuidanceTest.php` | Reviewed all state-label/meaning data rows, publication notice, final outcomes, null/unknown fallbacks and negative Chief/Director substage checks. These match the pure helper and Phase 3 plan; no change. |
| `FormMakerTest.php` | Builder labels at 43, failure/retained-entry messages at 93–94 and action-state labels remain in the existing builder contract. Exact saved labels and privacy/denial assertions are not obsolete navigation wording; no change. |
| `QuestionnaireConfigurationTest.php` | “Synthetic staff review only” at 133 belongs to the existing preview; no obsolete Phase 1–3 dashboard/navigation wording identified. Configuration, hash and authority assertions retained. |
| `ApplicantSandboxTest.php` | Resume draft, document guidance, read-only editor, history, availability notices and screening headings remain implemented. Fixture labels/answers and disclosure probes retained; no additional confirmed copy mismatch. |
| `ApplicationDecisionFoundationTest.php` | Public outcome at 72 is the fixture's history summary, not a fixed page status label. Internal-note exclusion and fault redaction remain security assertions; no change. |
| `CaseIssuanceRequestTest.php` | Confirmed absent-document mismatch corrected. Submission feedback, preview notice, service reasons, empty-case prefixes and readable supplementary values remain implemented and were not changed. |
| `DemonstrationDocumentTest.php` | No obsolete page-copy expectation identified. Exact downloaded bytes, PDF inspection, marks, immutable state/history, generation failures and authorization checks are not interchangeable with UI wording; retained unchanged. |
| `DownloadableFormsTest.php` | Fixture title “Blank undertaking” and negative manager controls still correspond to existing catalogue actions. Phase 1's navigation-label change does not justify altering these controls or access assertions; no change. |

This is a source audit, not proof that all branches render or all tests pass. No additional confirmed obsolete wording expectation was found in the nine files. No unresolved wording conflict requiring a runtime change was established.

### Suspected candidates and decisions

Line numbers below refer to the pre-edit source unless explicitly stated. Cleared candidates are recorded to explain why similar strings were not mechanically replaced.

| File / line | Expected text | Implemented text / source | Classification and approved basis | Smallest action |
| --- | --- | --- | --- | --- |
| `CaseIssuanceRequestTest.php:219` | “No certificate has been generated.” | Full absent-document sentence quoted above; request view:39 | **Confirmed mismatch.** Phase 3 plan §4 and implementation report require separate demonstration-PDF availability and explicit staff action. | Replace only this page-text expectation with the complete sentence; implemented. |
| `CaseIssuanceRequestTest.php:67` | “Demonstration request submitted. No certificate has been generated.” | Exact same string in `IssuanceRequests.php:94` | **Possible mismatch, ruled out.** Phase 3 preserves existing handlers/feedback; this is immediate feedback, not the absent-document panel. | None; retain. |
| `CaseIssuanceRequestTest.php:61` | “Preview only. No request or certificate is created.” | Exact same notice in `issuance-definitions.blade.php:26` | **Possible mismatch, ruled out.** Existing preview remains non-mutating and distinct from submitted requests. | None; retain preview boundary. |
| `CaseIssuanceRequestTest.php:271` (now 273) | “No accessible COR application.” / “No accessible ACSA application.” | “No accessible {{ $label }} application. Start with an available application; a qualifying Director approval is required before submitting a demonstration-document request.” | **Possible mismatch, ruled out.** Phase 3 empty-case guidance expands these module-specific prefixes without removing them. | None; retain both module assertions. |
| `ApplicantSandboxTest.php:782` | “editing and resubmission are unavailable” | “Questionnaire availability ended. Saved information is preserved; editing and resubmission are unavailable under the current publication.” in the pure helper | **Possible mismatch, ruled out.** Phase 3 plan §3 separates publication notice from lifecycle outcome and preserves restrictions. | None; retain restriction check. |
| `DashboardGuidanceTest.php:59` | Full recommendation sentence | Exact same sentence in `TaskOrientation::responsibilityDescriptions()` | **Previously confirmed mismatch already remediated; currently aligned.** Phase 3 plan §2 specifies this responsibility text. | None; preserve approved correction and combined-permission checks. |
| `DownloadableFormsTest.php:445` | Negative “Publish form” / “Withdraw Blank undertaking” | “Publish form” and dynamic `Withdraw {{ $form->title }}` remain manager-only controls in the catalogue view | **Possible mismatch, ruled out.** Phase 1 changes navigation/orientation, not catalogue authorization/actions. | None; never remove negative checks merely because a menu label changed. |

Service-provided definition/address failure reasons at `CaseIssuanceRequestTest.php:188/198` still match their service messages. The approved Phase 3 contract explicitly preserves those reasons. “None selected” and “Not confirmed” (formerly line 355, now 357) remain implemented answer formatting, not obsolete application statuses.

## Static checks and preservation

Before editing, recorded HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty tree, the original test snapshot and a **26,744-file** hash baseline. Private records are under:

`storage/app/private/uiux-consolidated-acceptance/gate-b1-attempt-02-remediation/`

They include the complete assertion-source inventory, original test snapshot, baseline, Git records and preservation comparison. The inventory is supporting source evidence, not a test result.

| Check | Actual result |
| --- | --- |
| PHP syntax | **PASS: all nine selected files**, `php -l` only |
| Scoped formatting | **PASS:** Pint `--test` on the changed test; no formatter rewrites |
| Before/after test diff | **PASS:** only the obsolete text assertion and multiline layout changed |
| `git diff --check` | **PASS**, existing CRLF advisories remain |
| Baseline comparison | **26,743 unchanged; exactly one changed file**, the authorized test; zero missing |
| Original PDFs | **73/73 unchanged** |
| Historical diagnostic JSON | **10/10 unchanged** |
| Both B1 attempts, reports, traces, identity logs and preservation manifests | **Unchanged** |
| Runtime sources, Phase 1–3 evidence and unrelated Batch G artifacts | **Unchanged** |
| Existing generated log/Blade caches | **Unchanged from this task's baseline**; not cleared or restored |

The earlier rerun report retains its separate account of seven generated-runtime changes caused by attempt 02. This static review neither hides those differences nor reclassifies them as historical-source changes. No new application logs, compiled views or PDFs were generated here. New files consist of this report and task-private source-review/preservation artifacts.

## Recommendation and stop

Recommend a separately authorized **`b1-attempt-03`** using the same nine-file selection, unchanged PostgreSQL guards, 512 MB memory, stop-on-failure/error and a new fixed, non-overwriting evidence directory. The runner still targets the already-used attempt-02 path and must not be invoked now. A future authorization must explicitly cover the minimal attempt-path/evidence extension and the single destructive `osds_test` rerun; both earlier attempts must remain independently auditable.

No test result is inferred from matching strings or syntax checks. The latest executed result remains **117 tests / 116 passed / 1 failed / 1,444 assertions**. Complete B1 acceptance remains pending.

**STOP. No PHPUnit, Laravel bootstrap, database connection, migration, browser automation, third attempt, runtime edit, B2/B3/B4, commit, push or deployment occurred.**
