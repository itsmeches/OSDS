# B1 — Three-stage synthetic application decisions

**22 October 2026. B1 implementation and targeted development verification: PASS. Stop for review.** User-confirmed demonstration rules govern this slice. Batch G remains CONDITIONAL CLOSE pending its separate sign-offs. No official approval, certificate, registry, issuance request or PDF is implemented by B1.

## Implemented contract

The existing registered Application intake page now records technical evaluation, Chief recommendation/return and Regional Director approval/rejection against the exact current immutable submission. Existing draft, screening and correction/resubmission contracts remain the foundation.

| Action | Explicit additional permission | Prerequisite / result |
| --- | --- | --- |
| Record technical evaluation | `applications.evaluation.record` | Current screening submission, published synthetic questionnaire/checklist attestation; first evaluation or Chief-returned stage. |
| Request correction through B1 | `applications.evaluation.record`, existing `applications.corrections.request` | Existing correction service creates its single correction, working revision and history row inside the B1 outer transaction. |
| Recommend approval / return for reevaluation | `applications.recommendations.record` | Exact current evaluated stage. Return requires another evaluation before recommendation. |
| Approve / reject demonstration case | `applications.decisions.record` | Exact current recommendation; immutable final stage and matching terminal application commit together. |

Each stage also requires current authenticated active synthetic staff, panel/intake permissions, effective exact region/module reviewer assignment and canonical regional authority. Permissions are defined through the existing enum/seeder pattern; **no existing role bundle automatically receives a new stage permission**. A questionnaire configuration reviewer is not thereby an application approver.

Case-wide separation includes every revision and superseded stage: anyone who acted in one stage role cannot act in either other role for that case. Different people may occupy the same role over time. A resubmitted revision starts without evaluation/recommendation readiness; prior records remain immutable. Chief return changes stage readiness while retaining the existing `under_screening` lifecycle state. Only Director outcomes add `approved` / `rejected` states.

Separation is enforced by immutable account ID. It does not identify one human operating multiple accounts; synthetic account custody and assignment remain demonstration responsibilities.

Public stage summaries enter existing applicant-visible history. Internal notes remain in staff stage records and are selected for display only with existing screening-history permission. Applicant tracking uses its existing ownership/representation/eligibility policy; final approval does not grant permanent access or issuance authority.

## Persistence and recovery

`ApplicationDecisionService::act` owns a single-attempt transaction. It locks/reloads the actor, current grants/exact assignments, case and permission memberships, then checks authority after waits. It validates expected submission, case version and predecessor. State/history/stage and mandatory audit commit together. B1 correction wraps the unchanged correction service; unrelated correction callers retain their original contract.

Stage events are `application.evaluation_recorded`, `application.evaluation_corrections_requested`, `application.recommendation_recorded`, `application.reevaluation_requested`, `application.approved` and `application.rejected`. Audit resource is the immutable ApplicationDecisionStage, with case/submission/predecessor metadata, current actor and existing region/institution binding. Internal notes and applicant answer values are not copied into audit metadata.

The new migration adds append-only `application_decision_stages` and `applications.final_decision_id`; checks enforce stage/action/attestation, current-submission binding, predecessor order, case-wide actor separation and one final Director stage. A deferred constraint prevents an orphan final decision; the application transition trigger requires the matching immutable decision. New cases cannot start terminal. The existing institution/module/publication unique index retains terminal cases, so this adds no reopening, renewal or reapplication.

Existing submission immutability, hashes, routing snapshots, working-copy consumption and correction constraints remain. The downgrade refuses to discard recorded stage history. Migration delivery does not authorize applying it to a manual database.

Same-key/same-payload service retries return the existing stage only after fresh authority/current-submission checks. Changed payload, stale version/predecessor, invalid state or competing final decision denies. The UI retains entries and fixed support-reference feedback on unexpected failure. After an uncertain response, inspect current case history before an explicit action; no automatic mutation replay or general offline/idempotency guarantee is introduced.

## Verification

Targeted PostgreSQL: **10 tests / 155 assertions passed**, recorded in `storage/app/private/b1-focused-final.txt`. Actual registered Livewire actions cover three-actor approval and ACSA rejection, Chief return, correction/resubmission, lifetime role separation, stale revisions/versions, missing prerequisites, fresh identity/permission/effective-scope denials, wrong region/module and detached actor. Applicant tracking excludes internal notes.

Faults are injected only at the existing audit sink: before insert, real insert then exception, and genuine PostgreSQL foreign-key rejection across all three stages. Fresh row/history/audit counts and outer transaction depth establish operation rollback before PHPUnit cleanup. Correction failure proves the existing correction and working-copy writes roll back together. Direct SQL probes reject unbound terminal state, orphan decision, same-person role mixing, stage mutation and terminal duplicate/reapplication.

[Five independent PostgreSQL scenarios](screenshots/b1-decision-foundation/concurrency.json) pass: opposing Director decisions, permission deletion, actor inactivation, exact reviewer-assignment revocation and regional expiry. Distinct worker backend PIDs, observed `pg_blocking_pids` waits and a third-connection unchanged view establish real contention/uncommitted isolation. Exactly one opposing outcome and final audit commit. Other SQL/cache/deadlock schedules are not universally certified.

Harness issues encountered were a missing fixture assignment reason; attempted changes to immutable grant dates; missing test model import; a mass-assigned nonexistent audit actor ID that was discarded; and an unauthorized fixture delegation setup. These were corrected through valid disposable fixture arrangements, real time travel, proper import and forced test actor ID. Initial/expanded probe logs remain private; the final focused log records the successful rerun, not a transcript of every intermediate attempt. No runtime guard was weakened. The first concurrency observation queried the wrong audit resource type; corrected observation uses stage IDs and records the sole final event.

### Browser, accessibility and final gates

[Final browser results](screenshots/b1-decision-foundation/browser-results.json): **three grouped checks and eight captures passed**, local port 8037, Chrome/Playwright and `osds_ui_browser_test`. COR approves and ACSA rejects independently, each with Chief return, reevaluation, recommendation and Director outcome. Missing-prerequisite/terminal actions are hidden; applicant views show the public outcome and omit internal notes. A mounted evaluator permission removal returns HTTP 403 and persists no stage.

All eight sampled light desktop (1440px) and dark mobile (375px) staff/applicant states pass unrestricted default axe plus enabled target-size, with no page overflow or uncaught page errors. Notifications are dismissed and finite animations settled before measurement. External requests are blocked. Representative final staff and dark mobile applicant images were inspected. This is not human accessibility or timed-state contrast acceptance. Browser fixtures begin at a real submitted/screening application prepared through unchanged services; this slice does not claim a fresh full questionnaire-authoring/applicant browser journey. Actual correction/resubmission is covered by the focused Livewire/service matrix.

Four earlier browser attempts are retained alongside the final JSON. They include login navigation timeouts, five-second stage-feedback timeouts after partial progress and a login validation response reporting a missing password. The final harness waits for network/Livewire hydration, uses a correctly configured 30-second assertion timeout and reuses authenticated isolated role contexts. No workflow handler, persistence expectation or permission denial was replaced. Later success does not establish every earlier timeout's cause. Private logs `b1-browser-first.txt`, `-second.txt`, `-third.txt` and final `b1-browser-final.txt` retain the attempts; the fourth JSON also remains. No consequential action is automatically replayed in the application.

Full Pint `--test`, PHP/JavaScript syntax checks, Vite asset build and tracked/new-file whitespace checks passed. The first Pint probe identified formatting in the new browser-only router, corrected before the final pass. Existing CRLF advice and bundler timing advice remain. IDE diagnostic tooling was unavailable; no IDE-clean claim. Private records: `b1-pint-final.txt`, `b1-build.txt` and `b1-diff.txt`. The task-owned browser server is stopped; unrelated existing servers were preserved.

**Complete current-tree PostgreSQL: 395 tests / 4,332 assertions passed**, duration **753,511 ms**. Private result: `storage/app/private/b1-full-final.txt`. Executed on the dirty working tree based on Git HEAD **b3af4cf**, including pending downloadable-form upload fixes and the new B1 regressions. The full suite retains questionnaire, applicant/screening/correction, downloadable forms, K1–K5 and earlier integrity/security coverage. This is an actual new run, not a committed-tree certificate or reuse of the historical 345/3,779 or 358/3,893 checkpoints. No subsequent runtime/test change was made.

Two earlier full attempts stopped at the default **128 MB** PHP CLI limit in the existing multipart ZIP regression. The larger limit on Artisan itself was not inherited by its PHPUnit child. Both logs remain (`b1-full-memory-limit-attempt.txt`, `b1-full-artisan-child-limit-attempt.txt`); the successful final run invokes PHPUnit directly with `-d memory_limit=512M`. No upload or test assertion was changed to address these runner failures, and this is not production memory/capacity certification.

Existing diagnostic JSON writers ran unchanged. Regenerated outputs from the final and incomplete attempts are [archived separately with restoration hashes](screenshots/b1-decision-foundation/preservation/unit-evidence/final/results.json). All original published evidence files were restored byte-for-byte from pre-run snapshots, with matching SHA-256 hashes; no historical outcome was rewritten. No broad preservation browser suite was rerun: the complete PostgreSQL suite and scoped B1 staff/applicant browser paths are the executed preservation gates for this slice.

## Changed files

- `app/Services/Applications/ApplicationDecisionService.php`, `app/Models/ApplicationDecisionStage.php`: operation and append-only stage model.
- `database/migrations/2026_10_22_100000_create_synthetic_application_decision_stages.php`: stage/final-state integrity.
- `app/Models/Application.php`, `app/Support/FoundationPermission.php`: relation and explicit permissions.
- `app/Services/Applicants/ApplicantDraftService.php`: preserve terminal-case duplicate boundary in preflight.
- `app/Filament/Pages/SyntheticApplicationIntake.php`, `resources/views/filament/pages/synthetic-application-intake.blade.php`, `resources/views/filament/pages/partials/application-decision-stages.blade.php`: real stage actions and staff history.
- `resources/views/components/reviewer-queue.blade.php`, `resources/views/applicant/track.blade.php`: demonstration outcome labels and tracking.
- `tests/Feature/ApplicationDecisionFoundationTest.php`, `tests/Support/B1Scenario.php`: actual-action/security/atomicity fixtures and matrix.
- `scripts/Prepare-B1.php`, `scripts/B1DecisionWorker.php`, `scripts/verify-b1-concurrency.mjs`, `scripts/b1-browser-server.php`, `scripts/verify-b1-browser.mjs`: guarded disposable verification tooling.
- This report and bounded safe browser/concurrency/preservation artifacts.

Earlier pending downloadable-form upload fixes and source artifacts remain separate and preserved. No B1 edits to existing lifecycle/correction service, ScopeAuthorizer, audit writer, Batch G inventory or historical results.

## Boundaries and B2 prerequisites

Configuration was inspected without exposing credentials: default local database is `osds_dev`; automated PostgreSQL uses `osds_test`; guarded workers/browser use `osds_ui_browser_test`. Only these two approved disposable databases were used for B1 preparation/migrations/tests. Fixture tooling retires earlier disposable applicant publications through the real publication service before creating isolated synthetic configurations. It does not prepare or activate manual users.

B1 is delivered as code and a migration, **not activated in `osds_dev`**. Applying the migration and provisioning individually authorized stage permissions in any manual environment requires separate authorization. Do not reset or run a synchronizing authorization seeder against customized manual accounts to enable this feature.

B2 remains separate: confirm issuance eligibility/clock semantics, module prerequisites, case/revision binding, protected identity fields, authorized requester and duplicate policy. B1 final approval is a synthetic decision prerequisite only; it does not approve a nine-day issuance rule, official personnel/numbering, legal validity or automatic certificate generation. Existing document-preview features are outside this slice.

Human accessibility acceptance and existing separate Institution-selector finding remain open. Automated sampled checks cannot certify complete WCAG compliance or national production capacity. No official process acceptance, production rollout, manual database migration, commit/push, Batch H or Phase 4 is implied. **Stop after B1 for review.**
