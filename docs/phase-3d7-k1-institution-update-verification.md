# K1 / A27 — Institution scope-bearing Update verification

## Subsequent targeted remediation — original discovery retained

**21 October 2026. Targeted integrity/security verification passed under separate authorization.** The actual Edit persistence now owns a transaction spanning the unchanged model/sole Core audit, with fresh existing authority and editor destination constraints. Original regression and controls remain; expanded focused matrix **22 / 189**, six real concurrency scenarios, actual browser **7 interactions / 8 captures**, preservation and full PostgreSQL **274 / 3,020** passed. Pint/build/diff passed. [Exact correction, evidence and limits](phase-3d7-k1-institution-update-remediation.md).

The initial FAIL and before-values below remain historical evidence. A noncritical Institution Edit target-size finding remains; no full accessibility or Batch G completion claim. Pause for review; no K2–K5, Batch H or Phase 4.

**21 October 2026. Disposition: FAIL — confirmed critical operation/audit integrity defect. Batch G stopped.** No runtime remediation. G-INT-001–007 are preserved. K2–K5, Batch H, Phase 4 and deployment are not started.

## Existing contract and actual path

Inspected `InstitutionResource`, registered `EditInstitution`, `InstitutionForm`, `InstitutionPolicy`, `Institution`, global observer registration in `AppServiceProvider`, `CoreIdentityObserver`, `AuditRecorder` and installed Filament EditRecord/transaction support.

- Resource query uses `StaffListScope::institutions`; current-record editing requires `institutions.manage` plus canonical `ScopeAuthorizer::canAccessInstitution`. Canonical scope includes its existing effective regional/nationwide/representation alternatives and active identity; staff-panel admission remains separate.
- The actual form exposes `name`, `official_id`, `primary_region_id` and `status`. The primary-region relationship query is filtered by existing `StaffListScope::regions` with `institutions.manage`. Installed Select validates the selected option against that relationship query.
- **Destination contract:** the exposed form constrains options to existing effective scope; the manipulated raw Livewire destination was rejected by that validation. The model's `updating` guard does not independently define a destination-region predicate, unlike its `creating` guard. This report establishes the actual form boundary, not a new universal service/model permission. No blanket region-transfer authority or destination rule is invented.
- `organization_type` and `is_synthetic` are not fields in this form. Injected raw form-state values for them were not persisted. The existing model separately blocks organization-type changes once an applicant draft exists; no draft-bearing direct-model mutation was tested here.
- `EditInstitution` inherits `EditRecord::handleRecordUpdate`, which updates Eloquent. The registered synchronous Core observer writes **`App\Models\Institution.updated`**, with current actor/resource, institution and the institution's post-update region. Metadata records changed field names, not field values.
- Neither this page nor the panel enables an encompassing operation transaction. Installed Filament's begin/rollback helpers return without starting/rolling back when optional transactions are disabled. AuditRecorder inserts on the normal connection and does not own the institution update transaction.

Existing application submissions retain separately captured routing-region snapshots; this assessment does not change or test their routing behavior. Changing an institution's primary region is not evidence that immutable submissions were rerouted.

## Fixtures and method

`tests/Feature/InstitutionUpdateVerificationTest.php` uses RefreshDatabase and explicitly asserts **`osds_test`** before seeding. Actor and institutions are synthetic. The actor holds existing `platform.admin.access`, `institutions.view`, `institutions.manage` permissions and effective fixture regional grants in **K1-A and K1-B**; **K1-C** is outside scope. No permission definitions or policies are changed. Events are suppressed only for disposable fixture regional-grant setup, never for the tested institution edits.

Each probe invokes the actual registered `EditInstitution` through Livewire, its real form/validation, inherited persistence handler, Eloquent and registered observer. Only the downstream audit sink is faulted in the final probe. No replacement form/model/persistence handler or reflection bypass. Four test invocations were serialized; the normal control completed before interpreting the fault probe.

## PASS / FAIL / CONTRACT-AMBIGUOUS matrix

| Probe | Disposition | Actual outcome and evidence |
| --- | --- | --- |
| Authorized normal Edit | **PASS** | **1 test / 14 assertions**. Name, official ID, primary region A→B and status active→inactive persist exactly. One matching existing Core audit, correct actor/institution/destination region/outcome. |
| Existing denied controls | **PASS** | **1 test / 6 assertions**. Out-of-scope current record is not found (**404**). Removing fixture management permission after mount causes Save **403**. Both institutions and audit count unchanged. |
| Manipulated form state | **PASS for actual form boundary** | **1 test / 13 assertions**. Raw out-of-scope destination C plus a name edit produces `primary_region_id` validation failure; neither value partially persists and no audit is added. Injected non-schema organization type/synthetic fields are not persisted; an otherwise unchanged save produces no update audit. |
| Model-wide destination authority | **CONTRACT-AMBIGUOUS / not established beyond form** | No independent destination predicate is defined in the model update guard. Actual form restriction is verified; no direct-model bypass or new governance rule is asserted. |
| Mandatory Core audit failure | **FAIL — critical integrity** | **1 test / 8 assertions / 1 failure**. Expected audit sink is reached exactly once and throws the synthetic RuntimeException. Audit count stays **6→6**, but the institution remains updated before PHPUnit cleanup. Original no-change assertion fails and is retained. |

Totals across the four separate invocations: **4 tests / 41 assertions; 3 passed, 1 failed**. This is not a combined suite run or a full-current-tree pass.

## Precise persistence evidence

The normal and fault probes each have their own isolated fixture; IDs shown here are disposable IDs, not historical/manual records. [Complete safe before/after evidence](evidence/phase-3d7-k1-institution-update.json) contains `authorized`, `denied`, `manipulated` and `audit_fault` observations.

| Field | Before faulted Save | After exception, before fixture cleanup |
| --- | --- | --- |
| `name` | `Disposable K1 before` | `Disposable K1 audit fault` |
| `official_id` | `K1-BEFORE` | `K1-FAULT` |
| `primary_region_id` | **1 / K1-A** | **2 / K1-B** |
| `status` | `active` | `inactive` |
| `organization_type` | `hei` | `hei` |
| `is_synthetic` | `true` | `true` |
| Total audit rows | **6** | **6** |

The permitted normal-control values were `Disposable K1 edited`, `K1-EDITED`, region **2**, `inactive`, with exactly **one new `App\Models\Institution.updated` audit**. Thus the failing fault case is not explained by inability to submit the real form, incorrect fixture permission or an unreachable observer.

Retained regression: **`InstitutionUpdateVerificationTest::test_actual_institution_update_rolls_back_when_mandatory_core_audit_fails`**. It catches only the expected isolated RuntimeException marker, requires the audit sink once, inspects fresh persisted fields and audit count, then asserts exact rollback. It fails on actual field differences. Fixture teardown rolls back the wrapping PHPUnit transaction later; that cleanup is not an operation transaction and does not negate the observed defect.

Private outputs under `storage/app/private`: `k1-authorized.txt`, `k1-denied.txt`, `k1-manipulated.txt`, `k1-audit-fault.txt`. No initial fixture/harness failure occurred in these probes. Tests ran only in PostgreSQL `osds_test`; no real browser, outage frequency or historical incident is claimed.

## Impact, stop and proposed separate remediation scope

An unsuccessful mandatory-audit operation leaves an **unaudited institution update**, including the primary-region binding and status used by other scope/availability checks. This is a critical authority-history/data-integrity defect. The test does **not** prove unauthorized destination access, cross-user disclosure, corruption of existing submissions, a historical incident or production exposure.

**Stop for separate targeted remediation authorization.** Proposed narrow scope: establish an operation-owned transaction for the actual Institution Edit save and its sole existing Core audit, rechecking existing current/destination form authority and relevant state at execution. Preserve current catalogue/scope semantics, validation, model guards, audit attribution and immutable routing snapshots. Assess narrowly necessary locking without importing new governance rules. Retain the actual-action regression and expand only the targeted success/fault/denial/recovery matrix under that separate approval. No automatic fix, blanket panel transaction, new audit event, migration or history repair is authorized here.

No runtime file was changed. Only this diagnostic test, safe evidence and checkpoint documentation were added/updated. Earlier selector work, recovery evidence, staged state and historical reports are preserved. No manual database inspection/mutation, commit/push, deployment, K2 or broader verification. No full-suite, browser, Pint or build acceptance was attempted after the critical stop. The earlier **252 / 2,831** full-suite result predates this new retained failure and is not a passing current-tree result. **Batch G remains NOT READY; pause for review.**
