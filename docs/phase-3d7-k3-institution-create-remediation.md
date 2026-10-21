# K3 / A26 — Atomic Institution Create remediation

**21 October 2026. Targeted development verification: PASS for integrity/security, recovery and regression gates.** Institution Create only. The existing selector target-size finding remains separate; full accessibility acceptance is not claimed. Batch G remains incomplete. Pause for review; no K4–K5, Batch H, Phase 4, production approval or historical repair.

## Root cause and preserved contract

The [original diagnostic](phase-3d7-k3-institution-create-verification.md) proved normal actual Create **1 / 15**, permission/identity **1 / 8**, input controls **1 / 52**, then a mandatory-audit fault **1 / 6 / one failure**. The inherited Filament handler inserted the institution before its synchronous registered Core observer wrote audit. Disabled optional page/panel transactions provided no operation rollback: institutions **0→1**, audits **3→3**.

Reviewed CreateInstitution, InstitutionResource/Form/Policy/Model, StaffListScope/ScopeAuthorizer, Core observer/registration, AuditRecorder, accepted K1 update service and retained diagnostics. Create retains active authenticated staff admission, `platform.admin.access`, explicit `institutions.manage` through the existing create policy, and its existing selected-region relationship constraint/model creating guard. No role-name authority or K1 current-institution Update requirement is imported.

Only name, official ID, primary region and active/inactive status are accepted. Existing required/max-length/official-ID uniqueness rules remain; primary region and official ID remain optional. The editor's scoped relationship query is rechecked inside the operation. Existing effective regional and nationwide alternatives remain; null-region creation does not acquire a new regional-grant prerequisite.

Organization type remains **hei**, **is_synthetic=false**, and public UUID remains model-generated. The generic form cannot create a flagged-synthetic institution. All diagnostic/browser catalogue values are fictional disposable fixtures in approved test databases, even though the real form preserves this false flag. No form replacement or protected-field bypass is used to manufacture a different result.

The unchanged Core observer remains the sole writer of **`App\Models\Institution.created`**, success outcome, current authenticated actor/resource/institution/region and existing changed-field-name metadata (empty on the recorded creation). No new event or raw values are added. Creating a catalogue row does not create representation, eligibility, account activation or a user scope grant.

## Exact correction

`InstitutionCreationService::create` owns one default PostgreSQL transaction with **one attempt**, spanning the existing Eloquent creation and synchronous observer audit. It locks/reloads the actor, existing regional/nationwide authority rows, and relevant role/direct-permission/role-permission membership rows using the established governance ordering. After possible waits it checks current identity, active status, staff permission and existing create policy, validates the four-field whitelist, and rechecks the existing scoped region option constraint. The unchanged model guard executes again during insertion.

No institution current-record/representation scope rule from K1 is imported. Locks establish authority at the operation check, not a new lifetime dependency. Existing official-ID validation and PostgreSQL partial unique index remain the duplicate boundary; optional null official IDs allow separate intentional creates. There is no idempotency token or promise that arbitrary repeated requests return identical success. No automatic retry, compensation delete, blanket panel transaction, schema change or second audit writer.

The actual Create page overrides only `handleRecordCreation`, delegates to this operation and uses the existing StaffActionFeedback support-reference handler. Unexpected failure yields fixed persistent `data.name` guidance and retains local entries. Authorization/validation retain normal denials. The existing recovery hook is additionally scoped to this Create page; it links to the authorized saved list after session/network/uncertain responses without replaying Create.

InstitutionResource/Form/Policy/Model, ScopeAuthorizer/StaffListScope, Core observer/registration, AuditRecorder, K1 and G-INT services are unchanged. No routing/submission workflow is modified.

## Retained regression and focused PostgreSQL evidence

The four original diagnostic methods remain on the actual registered Filament page, real form, Livewire Create, Eloquent and registered observer. The original fault method still requires the sink once and retains the exact absent-institution assertion; acknowledgement now checks persistent safe field/notification feedback instead of requiring the raw exception to escape.

Original [before evidence](evidence/phase-3d7-k3-institution-create.json) is preserved separately from [after evidence](evidence/phase-3d7-k3-institution-create-after.json). After the same pre-audit fault, institutions stay **0→0**, audits **3→3**, and the fresh institution query is null.

**Final focused matrix: 16 tests / 211 assertions passed**, across InstitutionCreateVerificationTest and InstitutionCreateRemediationTest. It covers exact one-audit success; retained original pre-audit fault; real audit insertion then exception; genuine PostgreSQL audit foreign-key rejection; identical PDO/operation transaction depth and clean outer transaction; mounted permission/panel/status/deactivation/regional revocation/expiry/future changes; detached identity; invalid/forged fields; official-ID duplicate rejection; null-region creation with and without regional authority; effective nationwide creation; and separate allowed null-official-ID creates. Rejected/faulted operations leave no institution or action audit. Audit-fault transport/log assertions reject injected message, SQLSTATE and credential hash; logs carry only fixed support message, reference and exception class.

Chronology: first **15 / 198 passed**. An added nationwide-alternative fixture omitted its required `granted_by`, producing **16 tests / 205 assertions; 15 passed, one error**, during fixture setup. Adding that required fixture attribution produced the final **16 / 211** pass; no runtime policy or assertion was weakened. Private logs: `k3-remediation-focused-first.txt`, `k3-remediation-focused-final.txt` (failed additional fixture), `k3-remediation-focused-complete.txt` under storage/app/private.

## Real PostgreSQL concurrency

[Four independent-worker scenarios passed](screenshots/phase-3d7-k3-remediation/concurrency.json). Distinct backend PIDs and `pg_blocking_pids` establish actual waits. A third connection sees zero uncommitted institutions before barriers release.

| Earlier operation / competing create | Waiting Create outcome |
| --- | --- |
| Authorized Create using the same official ID | First commits one institution/audit; second rejects duplicate official ID after its wait. |
| Regional authority revocation | Existing selected-region constraint rejects; zero created institutions/audits. |
| Explicit management permission deletion | Denied; zero created institutions/audits. |
| Actor inactivation | Denied; zero created institutions/audits. |

Synchronization barriers and authority-state manipulations are disposable test controls, not runtime delays or historical repair. This is independent service/DB concurrency evidence, not browser testing of authority-management UIs. It does not exhaust every lock schedule, role-permission change, different-actor race or arbitrary direct SQL. Existing uniqueness remains the final database boundary. Private log: k3-concurrency.txt.

## Actual browser and recovery

Guarded local **port 8029**, Chrome/Playwright, osds_ui_browser_test only: **eight interaction checks / eight light/dark desktop/mobile captures passed for integrity/recovery**. The real form searches/selects region, validates required input, retains entries after actual post-audit failure, completes successful/double-click Create, rejects duplicate official ID, handles precommit interruption, retains uncertainty after committed response loss, inspects the saved list, and denies mounted permission/cookie loss. There is no consequential replay.

[Browser results](screenshots/phase-3d7-k3-remediation/results.json), [light desktop rollback](screenshots/phase-3d7-k3-remediation/create-rollback-light-1440.png), [dark mobile uncertainty](screenshots/phase-3d7-k3-remediation/create-uncertain-dark-375.png). Developer inspected both representative images. At 1440/375px the eight captures record no page overflow, uncaught page errors or checked response leaks. The fixture actor has explicit permissions; screen metadata correctly says Staff creator. No browser harness failure occurred in the target run. Eight recorded console warnings concern the harness canvas readback performance; these are not uncaught application errors. No transient-notification axe resampling was needed in this run.

**Separate accessibility residual:** unrestricted default axe plus explicitly enabled target-size records **one existing Institution primary-region selector clear-control target-size node per capture**, and no other violations. No axe rule is suppressed. This control was already recorded in K1 and is distinct from the four earlier corrected selectors. No styling change is included. Screenshots retain a stale prior duplicate validation message during committed-response uncertainty because the new response was intentionally dropped; saved-list persistence establishes the outcome, not the retained local error. No general validation-message recovery fix is claimed.

External avatar requests are blocked by the fixture. Timed animations, native zoom, screen readers and human keyboard/touch/usability acceptance remain open. Automated axe/screenshots are not full accessibility certification.

## Preservation and final gates

All seven serial browser preservation journeys passed with unchanged workflow assertions and separate artifact directories: original applicant reproduction plus **11 expanded cases**; original builder reproduction plus **14 selection cases**; **seven condition/preview/publication checks / six screens**; CHEDRO **12 interactions / 30 screens**; staff **15 interactions / 94 screens**. [Preservation index](screenshots/phase-3d7-k3-remediation/preservation/results.json). No preservation case failed in this run. The existing staff notification-close polling is reused before measurement, without mutation retries. Previously completed dedicated builder recovery, timing, workload, selector and concurrency evidence is preserved, not redundantly rerun or broadened.

**Complete current-tree PostgreSQL: 307 tests / 3,365 assertions passed**, including the retained K3 regression, K2 diagnostics, K1, G-INT-001–007, SEC-001–008, applicant/builder, UTC/exact-scope and immutability protections. Duration **269,222 ms**. This is the executed dirty working-tree checkpoint based on Git HEAD **37084d1**, not a committed tree or a reused historical suite result. Private output: storage/app/private/k3-full-final.txt.

Full Pint --test and production Vite build passed; initial Pint import-order finding was corrected in the touched provider. Final working diff/new-file whitespace checks passed. Private records: k3-preservation.txt, k3-pint-first.txt, k3-pint-final.txt, k3-build.txt and k3-diff-final.txt under storage/app/private. Database-backed preparation and suites were serialized. Both task-owned browser servers are stopped and temporary K3 wrappers removed. IDE diagnostics tooling was unavailable; no IDE-clean claim is made. Existing CRLF advisories remain. No independent critical defect outside K3 was confirmed during this remediation.

## Affected files and completion boundary

- `app/Services/Authorization/InstitutionCreationService.php`: narrow transaction and fresh existing Create authority.
- `app/Filament/Resources/Institutions/Pages/CreateInstitution.php`: persistence delegation and safe persistent failure feedback.
- `app/Providers/Filament/AdminPanelProvider.php`: existing recovery hook additionally scoped to Create.
- `tests/Feature/InstitutionCreateVerificationTest.php`: retained original controls/rollback assertion and separate after evidence; current safe acknowledgement.
- `tests/Feature/InstitutionCreateRemediationTest.php`: bounded expanded actual-action/fault/authority matrix.
- `scripts/Prepare-InstitutionCreation.php`, `InstitutionCreationWorker.php`, `verify-institution-creation-concurrency.mjs`, `k3-create-browser-server.php`, `verify-institution-creation.mjs`, `verify-k3-preservation.mjs`: guarded disposable fixtures, local fault server and verification harnesses.
- This report, checkpoint documentation and safe result/screenshot artifacts.

No manual-testing database inspection/reset/reseed/mutation, historical repair, index operation, commit, push, deployment, migration, new policy/permission/audit event, K4–K5, Batch H or Phase 4. Only osds_test and osds_ui_browser_test are used; accepted evidence remains preserved. After targeted gates, pause for review. Broader inventory dispositions, K4/K5, residual-risk acceptance, final Batch G closure and human acceptance remain separate.

## Targeted manual review

1. With disposable scoped staff authority, select a permitted region and create once. Inspect exact name/official ID/status, hei/false defaults, generated UUID and the single Core event where audit access permits inspection.
2. Check required/duplicate official-ID feedback and permission/session loss while preserving local entries. After uncertainty, inspect the saved list before any new explicit Create; a null official ID does not provide idempotency.
3. Keep fault injection confined to approved isolated automation. Native zoom, screen readers, keyboard/touch usability, timed-state contrast and the existing clear-target finding require separate human/accessibility disposition. Earlier four-selector corrections remain intact.

**Stop for review after K3.** This targeted pass does not approve broader Batch G completion, K4/K5 execution, Batch H or production readiness.
