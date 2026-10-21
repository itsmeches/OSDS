# K5 / A28 — Atomic Region Create remediation

**21 October 2026. Targeted development verification: PASS for integrity/security, recovery and regression gates.** Region Create only. Batch G remains incomplete; pause for review. No Batch H, Phase 4, deployment, new inventory or historical repair. Sampled automated accessibility checks do not constitute full human/WCAG acceptance.

## Root cause and preserved contract

The [original diagnostic](phase-3d7-k5-region-create-verification.md) completed **nine cases / 99 assertions, eight passed and one failed**, over six separate invocations. Actual Create inserted the Region before its registered synchronous Core observer wrote audit. Disabled optional page/panel transactions supplied no operation rollback: Region count **0→1**, audit count **1→1** when the mandatory sink failed.

Reviewed CreateRegion, RegionResource/Form/Policy/Model/UUID hook, global observer registration, CoreIdentityObserver, AuditRecorder, installed Filament creation/transaction support and accepted K3/K4 patterns. Preserved:

- Active authenticated staff identity, panel admission and explicit **regions.manage** through existing Create policy. No role-name bypass.
- **No regional/nationwide grant prerequisite for Create.** No current-region Update or institution representation rule imported.
- Exactly code/name/is_active writable: required unique code max 32; required name max 255; existing boolean Toggle/default true and explicit false; generated public UUID and PostgreSQL uniqueness.
- Existing **App\Models\Region.created** only, success/current actor/resource attribution, empty creation changed_fields metadata and **null region_id/institution_id** audit bindings.
- No automatic scope grant, assignment or institution relationship; no new duplicate-name restriction or idempotency promise. Separate valid codes may create Regions with the same name under the existing contract.

## Exact correction

`RegionCreationService::create` owns one default PostgreSQL transaction with **one attempt**. It locks/reloads the actor and relevant current role/direct-permission/role-permission memberships in established order, checks fresh authenticated identity, active status, panel permission and existing Create policy after waits, validates the three-field whitelist, then creates through unchanged Eloquent/model UUID hook. The unchanged synchronous Core observer is the sole mandatory audit writer. Both inserts commit or roll back together.

No current Region or regional-grant locks/prerequisites are added to Create. Existing code validation/unique constraint arbitrates competing Creates; no advisory uniqueness policy, blanket panel transaction, automatic retry, compensation delete, duplicate event, migration or new permission.

Actual `CreateRegion::handleRecordCreation` delegates only persistence and uses existing StaffActionFeedback. Unexpected failures produce a fixed persistent **data.name** explanation, retained local entries and redacted support-reference feedback. Authorization/validation preserve normal denials. Existing staff recovery hook is additionally scoped to this Create page; network/session/uncertain responses offer authorized saved-list inspection without replaying Create.

RegionResource/Form/Policy/Model, Core observer/registration, AuditRecorder, ScopeAuthorizer/StaffListScope, K1–K4 and G-INT services are unchanged. Locking establishes authority at the operation check, not a lifetime governance dependency or guarantee under every external SQL/deadlock schedule.

## Retained diagnostics and PostgreSQL matrix

All **nine original diagnostic cases**, including the exact named rollback regression, remain on the registered Filament form/Livewire Create/model/observer. Original fault method still requires the exact sink once and the Region absent; only acknowledgement changes from a raw escaping exception to safe persistent field/notification feedback. No persistence path is mocked away.

Original [before evidence](evidence/phase-3d7-k5-region-create.json) remains unchanged; [after evidence](evidence/phase-3d7-k5-region-create-after.json) is separate. The same pre-audit fault now leaves Regions **0→0**, audits **1→1**, with fresh query returning no Region.

**Focused matrix: 18 tests / 204 assertions passed** across RegionCreateVerificationTest and RegionCreateRemediationTest. Includes normal exact one-audit creation; pre-audit fault; real audit insertion then exception; genuine PostgreSQL audit foreign-key rejection; operation depth above PHPUnit fixture transaction and identical PDO; clean outer transaction after rejected SQL; mounted permission/panel/status/deactivation changes with intentionally stale authenticated objects; detached identity; removal of existing role membership; zero-scope creation; required/oversized code/name and duplicate-code input; default true/explicit false; forged protected fields/actor; UUID uniqueness; two intentional different-code/same-name Creates with exactly one audit each and no new regional/nationwide/institution relationships.

Only the downstream audit sink injects faults. Real Eloquent, observer and actual PostgreSQL audit insert execute. Fresh database counts before fixture cleanup establish rollback, rather than relying on outer teardown. Transport/log assertions reject injected marker, SQLSTATE and credential hash; logs contain only fixed message, support reference and exception class. No focused regression failure or assertion weakening occurred. Private output: storage/app/private/k5-remediation-focused.txt.

## Independent PostgreSQL concurrency

[Four scenarios passed](screenshots/phase-3d7-k5-remediation/concurrency.json) using independent PHP workers/distinct backend PIDs, real `pg_blocking_pids` waits and a third connection that sees zero uncommitted Regions.

| Scenario | Outcome |
| --- | --- |
| Two different authorized actors create the same code | First commits one Region/audit; second waits at PostgreSQL uniqueness and rejects duplicate; final one Region/one audit. |
| Management permission deletion before waiting Create resumes | Denied; zero Region/audit from Create. |
| Actor inactivation before waiting Create resumes | Denied; zero Region/audit from Create. |
| Existing role-membership removal before waiting Create resumes | Denied; zero Region/audit from Create. |

The role case uses the seeded Admin permission bundle as a disposable source of existing regions.manage, retaining direct panel/read permissions after removal. No new role hierarchy or role-name authority. Workers create with no regional/nationwide grants. Barriers and authority changes are fixture controls, not runtime delays or claims of testing management UIs. Not every role-permission/cache schedule, grant change or arbitrary SQL race is certified. Private log: storage/app/private/k5-concurrency.txt.

## Browser and recovery

Final target browser: **eight interaction checks / eight light/dark 1440px/375px captures passed**, guarded local port **8031**, Chrome/Playwright/axe and osds_ui_browser_test only. Real Region Create form, no replacement handler. Covers normal Create, required/duplicate validation, audit failure with retained entries/exact rollback, double click, aborted request, committed-response uncertainty, authorized saved-list popup inspection, permission and session loss. No automatic consequential replay. Browser fixture has a pre-existing effective nationwide grant solely for **saved-list reading**; Create itself requires none, as feature controls and concurrency workers prove. No grant is created by the application operation.

Initial attempt passed validation, audit rollback, delivered double click and duplicate-code checks, and confirmed two committed Regions with one audit each after the intentionally lost response. It then timed out searching the saved-list popup. [Retained first result/screens](screenshots/phase-3d7-k5-remediation/first-saved-list-attempt/results.json), private log k5-browser.txt. An explicit popup network/hydration wait was added before search, without changing any persistence/authorization assertion or retrying a mutation. Exact original timeout cause remains unestablished; no application defect or harness-only diagnosis is claimed from that timeout alone.

[Final browser results](screenshots/phase-3d7-k5-remediation/results.json), [light desktop rollback](screenshots/phase-3d7-k5-remediation/create-rollback-light-1440.png), [dark mobile uncertainty](screenshots/phase-3d7-k5-remediation/create-uncertain-dark-375.png). Developer inspected both representative images. All eight captures passed unrestricted default axe plus explicitly enabled target-size, with zero violations, page overflow, uncaught page errors or checked response leaks. No transient-notification axe resampling was required. Final log: storage/app/private/k5-browser-final.txt. External avatar requests are blocked. Automated checks do not replace human screen-reader/native-zoom/keyboard/touch/usability or timed-animation contrast assessment. Unrelated known Institution selector findings remain separate; no styling correction is included.

Eight recorded console warnings concern the harness canvas readback performance; they are not uncaught application errors. The retained first popup timeout is not diagnosed as a production reliability defect or conclusively dismissed as harness-only by the later passing run.

Human retest remains: with disposable staff authority, create once with default/explicit activity, inspect the saved row and single existing audit where separately authorized, and check duplicate/required-field guidance. After uncertainty, keep the tab and inspect saved Regions before deciding on a new explicit Create. Saved-list reading remains separately scoped; creation does not grant that access. Check keyboard/touch, focus, native zoom and screen-reader announcements. Inject faults only in approved isolated automation.

## Preservation and final gates

All seven serial preservation journeys passed with unchanged assertions and separate artifact directories: original applicant reproduction plus **11 expanded cases**; original builder reproduction plus **14 selection cases**; **seven condition/preview/publication checks / six screens**; CHEDRO **12 interactions / 30 screens**; staff **15 interactions / 94 screens**. [Preservation index](screenshots/phase-3d7-k5-remediation/preservation/results.json). No preservation case failed. Existing staff notification-close polling is reused before measurement without mutation retries. Previous dedicated recovery, modal/timing, workload, selector and concurrency evidence is preserved rather than redundantly broadened.

**Complete current-tree PostgreSQL: 345 tests / 3,779 assertions passed**, duration **247,300 ms**. Includes all retained K5 diagnostics/expanded matrix, K1–K4, G-INT-001–007, SEC-001–008, applicant/builder, UTC/exact-scope and immutability regressions. This is the executed dirty working tree based on Git HEAD **37084d1**, not a committed tree or reused historical green checkpoint. Output: storage/app/private/k5-full-final.txt. Earlier 327 / 3,575 remains historical.

Existing K1–K4 diagnostic JSON writers ran unchanged in the suite. Their fresh outputs are [archived separately](screenshots/phase-3d7-k5-remediation/preservation/unit-evidence/results.json); the four prior published files were restored byte-for-byte from pre-suite snapshots, with matching SHA-256 hashes. K5 after evidence is separate from the original failure. No prior test outcome was rewritten.

Full Pint --test, production Vite build, final tracked-diff and new-file whitespace checks passed; existing CRLF advisories remain. Private records: k5-preservation.txt, k5-pint.txt, k5-build.txt and k5-diff-final.txt under storage/app/private. Database-backed preparation/tests were serialized. Both task-owned browser servers are stopped and temporary K5 wrappers removed. IDE diagnostics tooling was unavailable; no IDE-clean claim. No independent critical defect outside K5 was confirmed during this bounded remediation.

## Files and boundaries

- `app/Services/Authorization/RegionCreationService.php`: narrow creation operation/transaction.
- `app/Filament/Resources/Regions/Pages/CreateRegion.php`: real persistence integration and safe persistent feedback.
- `app/Providers/Filament/AdminPanelProvider.php`: scope existing recovery hook to CreateRegion.
- Original RegionCreateVerificationTest and new RegionCreateRemediationTest: retained original actual-action comparisons and bounded expanded matrix.
- `scripts/Prepare-RegionCreation.php`, RegionCreationWorker.php, verify-region-creation-concurrency.mjs, k5-create-browser-server.php, verify-region-creation.mjs, verify-k5-preservation.mjs: guarded disposable fixture/fault/worker/browser/preservation tooling.
- This report, checkpoint documentation and safe result artifacts; original discovery remains historical.

Only osds_test and osds_ui_browser_test are used. No manual-testing database inspection/reset/reseed/mutation, historical repair, staging/index operation, commit, push, deployment, new schema/migration/permission/event/governance, Batch H, Phase 4 or another inventory. Existing accepted protections/evidence and staged changes are preserved. After all targeted gates, pause for review; **do not declare Batch G complete automatically**.

Broader Batch G risk/disposition acceptance, the separate Institution selector finding and human acceptance remain open. The exact first popup-timeout cause remains a recorded limitation. This targeted K5 pass does not approve those residuals, complete Batch G, authorize another inventory or begin Batch H/Phase 4. **Stop for user review after K5.**
