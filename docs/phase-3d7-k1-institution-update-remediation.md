# K1 / A27 — Atomic Institution Edit remediation

**21 October 2026. Targeted development verification: PASS for integrity/security and regression gates.** Institution Edit only. Focused, concurrency, target-browser recovery, preservation and full-current-tree PostgreSQL gates passed. The target browser records a separate noncritical selector target-size finding; full accessibility acceptance is not claimed. Batch G remains incomplete; pause for review. K2–K5, Batch H and Phase 4 are not started.

## Root cause and preserved contract

The original actual Institution Edit diagnostic demonstrated authorized normal persistence **1 / 14**, denied/forged controls **2 / 19**, then a mandatory-audit fault **1 / 8 / 1 failure**. Filament's inherited update saved the institution before its registered synchronous Core observer wrote audit. Optional page/panel transactions were disabled, so that exception left name/official ID/primary region/status updated with unchanged audit count.

Existing contract was reviewed in EditInstitution, resource/form/policy, Institution, StaffListScope/ScopeAuthorizer, CoreIdentityObserver/registration, AuditRecorder and the retained diagnostic. Preserved boundaries:

- Current authenticated, active actor and existing staff-panel permission; `institutions.manage` through the existing `InstitutionPolicy::update` and canonical current-institution scope.
- Destination region follows this editor's existing scoped relationship query/validation. The new operation reuses **`StaffListScope::regions(..., 'institutions.manage')`** after locks; it does not introduce a universal Institution model destination policy. Optional null remains supported.
- Form whitelist remains name, official ID, primary region and active/inactive status; organization type, synthetic identity and actor fields are not editable through this operation. No model guard/status meaning is changed.
- Sole mandatory audit remains **`App\Models\Institution.updated`**, current authenticated actor, institution/post-update region and existing changed-field-name metadata. No duplicate writer, new event or raw-value metadata.
- Existing submission routing snapshots remain immutable. No catalogue-to-submission transfer or historical repair is introduced.

## Exact correction and locking

`InstitutionUpdateService::update` owns one default PostgreSQL transaction with its default **one attempt**. It reloads/locks the actor, existing actor regional/nationwide/representation authority, the exact institution and relevant existing role/permission memberships. It then checks fresh identity/policy and validates the four form fields, including existing official-ID uniqueness and the current scoped destination option constraint. Only the whitelist reaches the unchanged Eloquent model/observer; institution update and sole audit execute on the same connection/transaction.

Actor→authority→institution ordering follows the existing delegated-operation pattern. Lock waits are followed by fresh authorization checks. This establishes authority at the operation check; it does not invent permanent grantor-authority requirements or certify every external SQL schedule/deadlock. No automatic transaction retry, compensation update/delete, schema or blanket panel transaction.

`EditInstitution::handleRecordUpdate` delegates only persistence to the service, then synchronizes its component record with the fresh saved model. The existing safe support-reference handler maps unexpected failure to persistent `data.name` guidance while retaining entered values. Authorization/validation retain their normal denials. The existing staff recovery hook is scoped additionally to this Edit page; failed connectivity/session/uncertain responses link to the saved record in another tab without automatic replay. No shared recovery implementation was rewritten.

There is **no new optimistic-lock/idempotency policy**. Existing explicit authorized competing writes serialize; a later authorized writer can replace earlier values. An unchanged repeated Save creates no additional business update/audit in the verified case; identical successful responses for arbitrary retries are not promised.

## Retained and expanded PostgreSQL evidence

The original four named diagnostics retain the real Filament form/Livewire/model/registered-observer path. The original audit-failure method and exact fresh-state rollback comparison remain; only acknowledgement changes from an escaping synthetic exception to safe persistent field/notification feedback. Its mock still requires the sole audit sink exactly once. Original before evidence remains [unchanged](evidence/phase-3d7-k1-institution-update.json); [after evidence](evidence/phase-3d7-k1-institution-update-after.json) is separate.

**Final focused matrix: 22 tests / 189 assertions passed.** `InstitutionUpdateVerificationTest` plus `InstitutionUpdateRemediationTest` cover:

- Exact permitted field persistence and one matching attributed audit; original pre-audit rollback.
- Real audit insertion then exception, and a genuine PostgreSQL audit foreign-key rejection. The actual institution action/model/observer execute; only the audit sink injects the fault. Assertions verify operation depth above the test fixture transaction, identical PDO, exact rollback, retained input, clean outer transaction and redacted transport/logs.
- Mounted permission/panel/status/deactivation/current-scope expiry/revocation/future changes; destination-scope expiry/revocation; fresh institution move out of scope; no mutation on denial.
- Forged out-of-scope option rejection and non-schema/actor fields not persisted; original attribution remains current actor. Required input, optional null region, detached-actor denial, canonical nationwide/representation alternatives and explicit second-writer/no-op behavior.

First focused attempt: **20 tests / 165 assertions, 15 passed / five failed** because new denial tests expected 403 where scoped resource hydration after a form update returned 404. The actual denial occurred before persistence. Expectations were corrected to the observed path-specific status; exact no-mutation assertions were retained. Next checkpoint **20 / 175 passed**; two canonical-alternative cases brought the final matrix to **22 / 189**. Private logs: `k1-remediation-focused.txt`, `k1-remediation-focused-final.txt`, `k1-remediation-focused-complete.txt`. No runtime authorization was changed to satisfy these assertions.

## Real PostgreSQL concurrency

[Six scenarios passed](screenshots/phase-3d7-k1-remediation/concurrency.json) with independent PHP workers/distinct backend PIDs and database-confirmed `pg_blocking_pids` waits. A third connection sees the original institution and zero uncommitted update audits while barriers are held.

| Scenario | Observed outcome |
| --- | --- |
| Two authorized explicit updates | First commits; second waits then commits; final second-writer value and **two matching audits**, preserving existing last-writer semantics. |
| Earlier current regional revocation | Waiting update denies; no editor field/audit mutation. |
| Earlier destination regional revocation | Waiting update rejects existing destination constraint; no editor field/audit mutation. |
| Earlier permission deletion | Waiting update denies; no editor field/audit mutation. |
| Earlier actor inactivation | Waiting update denies; no editor field/audit mutation. |
| Earlier institution move outside current scope | Waiting update denies against fresh binding; its own field/audit changes are absent. |

Barrier waits and direct authority-state updates exist only in isolated fixture workers, not runtime correctness delays or claims of those management UIs being browser-tested. They are not historical repairs. Private log: `storage/app/private/k1-concurrency.txt`.

## Actual Edit browser, recovery and snapshot preservation

Guarded local port **8028**, Chrome/Playwright/axe, `osds_ui_browser_test` only: **seven interaction checks / eight Light/Dark 1440px/375px captures passed for integrity/recovery**. Real field search/selection, downstream post-audit failure with retained local form, delivered save/double click, committed-response loss, saved-record popup inspection, precommit request abort, permission loss and cookie loss execute. No action replays automatically.

The fixture uses existing CHEDRO/applicant setup services to create an actual immutable submission, then performs failed and successful institution primary-region/status edits. The selected submission **header's complete serialized hash, row count and routing-region array remain identical**. This does not claim independently rehashing every child answer/evidence relation or every historic submission. Existing full-suite immutable-history tests remain separate evidence.

[Target results and snapshot hashes](screenshots/phase-3d7-k1-remediation/results.json). Developer inspected [light desktop rollback](screenshots/phase-3d7-k1-remediation/edit-rollback-light-1440.png) and [dark mobile uncertainty](screenshots/phase-3d7-k1-remediation/edit-uncertain-dark-375.png). Eight captures record no overflow, uncaught page errors or checked response leaks.

**Accessibility residual:** unrestricted default axe plus explicitly enabled target-size records **one target-size node in each capture**, consistent with the existing Institution primary-region clear control's unadapted 16px style. This editor is distinct from the four selectors corrected earlier. The harness records findings rather than excluding the rule or claiming zero violations. No unrelated styling correction is made. Other recorded axe violations: none. Timed notification/animation contrast, native zoom, screen readers and human task acceptance remain open. External avatar requests are blocked in fixtures.

Before the final browser pass, harness locators for required Name and Status fields timed out; a regex resolved Name, and the actual native status select was exercised with `selectOption`. Three failed locator attempts are retained (`browser-name-locator-attempt.json`, `browser-status-locator-attempt.json`, `browser-status-label-attempt.json`). All stopped during form filling before the first tested update. No runtime labels/semantics were modified and no failed mutation was retried automatically. Private final log: `storage/app/private/k1-browser-final.txt`.

The copied helper's screen `role` metadata says CHEDRO, while the actual K1 fixture has explicit staff permissions and no assigned role. This is a metadata limitation, not an authority change: `Prepare-InstitutionUpdate.php` constructs the actor and actual actions use that actor. The runner's future label is corrected to Staff editor. Recorded screen/outcome evidence is retained unchanged; this label correction alone is not claimed as another browser run.

## Preservation and final gates

All seven existing preservation journeys passed serially through `verify-k1-preservation.mjs`, with unchanged assertions and separate output directories: original applicant answer-race reproduction plus **11 expanded cases**; original builder isolation reproduction plus **14 selection cases**; **seven condition/preview/publication checks / six screens**; CHEDRO **12 interactions / 30 screens**; staff **15 interactions / 94 screens**. [Preservation index](screenshots/phase-3d7-k1-remediation/preservation/results.json). No preservation attempt failed. The existing staff normal-notification-close polling is reused before measurement; no mutation is retried. The separately completed dedicated builder-recovery report remains evidence, not a newly repeated journey here.

No prior evidence is overwritten. Focused tests/concurrency/browser fixture preparation and the full suite were serialized. The existing browser setup may retire an earlier browser-only active synthetic publication through its normal service to prepare new fixtures; no manual/historical repair or deletion is performed.

**Complete current-tree PostgreSQL: 274 tests / 3,020 assertions passed**, after all preservation journeys. This includes the retained K1 diagnostic, expanded matrix, G-INT-001–007, SEC-001–008, C-INT-001, E-INT-001/002, UTC/exact-scope and immutability regressions. Full Pint `--test`, production Vite build and final working-tree diff checks passed; existing CRLF advisories remain. Private logs: `k1-preservation.txt`, `k1-pint-final.txt`, `k1-build.txt`, `k1-full-final.txt` under `storage/app/private`. Both task-owned browser servers are stopped. IDE diagnostics tooling was unavailable; no IDE-clean claim is made in addition to these executed gates.

## Affected files and boundaries

- `app/Services/Authorization/InstitutionUpdateService.php` — narrowly owned update transaction/fresh existing authority.
- `app/Filament/Resources/Institutions/Pages/EditInstitution.php` — persistence integration, record synchronization, safe persistent feedback.
- `app/Providers/Filament/AdminPanelProvider.php` — scope existing recovery hook to this Edit page.
- Original `InstitutionUpdateVerificationTest.php` and new `InstitutionUpdateRemediationTest.php` — real-action/fault/authority coverage.
- `Prepare-InstitutionUpdate.php`, `InstitutionUpdateWorker.php`, `verify-institution-update-concurrency.mjs`, `k1-update-browser-server.php`, `verify-institution-update.mjs`, `verify-k1-preservation.mjs` — strictly isolated verification tooling.
- Remediation/checkpoint documents and safe evidence. The original diagnostic failure record remains historical evidence.

InstitutionResource/Form/Policy/Model, ScopeAuthorizer/StaffListScope, Core observer/registration, AuditRecorder, previous G-INT services, permissions, schema and routing workflows are unchanged. Earlier selector CSS/schema changes are preserved. No manual-testing database inspection/reset/reseed/mutation, historical repair, index operation, commit/push or deployment. No separate critical defect was confirmed within this bounded remediation. **Pause for review; do not proceed to K2–K5, Batch H or Phase 4.**

## Targeted manual retest and remaining Batch G gates

1. With a disposable scoped staff actor, edit a synthetic institution to another permitted region and change name/official ID/status. Confirm the saved values and single Core update audit where audit permission permits inspection.
2. Verify forbidden current records and forged destination/non-schema inputs remain denied/ignored without partial changes. Keep this editor's destination restriction distinct from an unapproved universal model policy.
3. For uncertainty, keep local entries and inspect the saved record in another authorized tab before an explicit new action. There is no new version token or identical-retry guarantee; separate authorized edits retain existing last-writer behavior.
4. Test session/authority loss and persistent failure guidance with approved disposable automation. Do not inject faults into manual records or manually alter immutable submission snapshots.
5. Human screen-reader/native-zoom/keyboard/touch usability and timed-state contrast remain pending. The Institution Edit clear-target finding needs separate disposition; earlier four-selector corrections remain intact.

After targeted gates complete, K1 alone can be considered for development review. K2–K5, broader finite-risk acceptance, final Batch G closure and human acceptance remain separate open gates. This report does not approve Batch H, production operations or historical integrity.
