# K4 / A29 — Region Update diagnostic

**Follow-up:** [Targeted K4 remediation](phase-3d7-k4-region-update-remediation.md) now passes its integrity/security, recovery and regression gates. The original defect/results below remain historical and unchanged; the Region Edit transaction defect is no longer unresolved. Batch G remains incomplete; K5 is not started.

**21 October 2026. Disposition: FAIL — confirmed critical operation/audit integrity defect. Batch G stopped / NOT READY; NO-GO for Batch H.** No runtime remediation. G-INT-001–007, K1–K3 and prior recovery/accessibility evidence are preserved. K5 is not started.

## Established contract and persistence path

Inspected the registered RegionResource/EditRegion, RegionForm, RegionPolicy, Region model, StaffListScope/ScopeAuthorizer, CoreIdentityObserver/global registration, AuditRecorder and installed Filament EditRecord/CanUseDatabaseTransactions.

- Registered Edit route is `/admin/regions/{record}/edit`. Resource loading applies `StaffListScope::regions` with its default `regions.view`, active identity and effective region IDs. Update policy requires explicit `regions.manage` and `ScopeAuthorizer::canAccessRegion(actor, region.id)`. Normal HTTP staff-panel admission is separate. No role label alone authorizes editing.
- Effective regional grants or existing effective nationwide authority satisfy region scope. No Institution destination selector, current-institution representation alternative or transfer rule is imported. `Region.is_active` is a catalogue field; the inspected effective-scope predicate does not define toggling it as grant revocation. No new interpretation of activity is introduced.
- Actual form fields: required unique `code`, maximum 32, ignoring the edited row; required `name`, maximum 255; required `is_active` toggle. Region fillable fields match these three. Public UUID is generated on creation and is not editable here. No additional update guard or assignment-management service is invoked.
- EditRegion inherits installed `EditRecord::handleRecordUpdate`, which calls `$record->update($data)`. Its generated DeleteAction is policy-denied; no successful Delete operation was tested or added.
- AppServiceProvider registers the synchronous CoreIdentityObserver for Region. Exact mandatory event is **`App\Models\Region.updated`**, success outcome, authenticated actor and Region resource ID/type, with changed-field-name metadata. Under the current observer **region_id and institution_id are null for a Region resource**; the Region identity is in resource_id. The normal control verifies this existing behavior without inventing a self-region audit binding or changing audit semantics.
- Actual page optional transactions are disabled, verified in the mounted control. Neither the page nor the panel owns an encompassing update transaction. Filament begin/rollback helpers do nothing when disabled. The observer writes after Eloquent update; AuditRecorder does not own the preceding model transaction.

## Fixtures and actual-action method

`tests/Feature/RegionUpdateVerificationTest.php` asserts **osds_test** before seeding. All actors and catalogue values are fictional disposable fixtures. Actor is flagged synthetic and holds existing `platform.admin.access`, `regions.view`, `regions.manage`, plus an effective grant in K4-A. K4-B is outside scope. Region has no synthetic-flag form field; no real/manual catalogue is used. Fixture regional-grant setup alone suppresses model events; tested Region updates never do.

Tests use the actual registered page, unchanged form and validation, Livewire Save, inherited handler, real model and registered observer. No replacement form, reflection bypass, mock Region or test-only persistence handler. Only the final probe mocks the actual downstream mandatory audit sink. Authorized normal control passed first, followed by denial/input controls, then exactly one fault probe. Database-backed invocations were serialized.

## Actual-action PASS / FAIL matrix

| Probe | Result | Persistence outcome |
| --- | --- | --- |
| Authorized normal Edit | **PASS — 1 test / 17 assertions** | Exact code/name/active-flag changes; unchanged ID/UUID, no form errors, saved notification; exactly one correctly attributed existing Core update audit and expected metadata. |
| Out-of-scope current record | **PASS** | 404; both Region rows and audit count unchanged. |
| Management permission removed after mount | **PASS** | Save 403 with form filled before revocation; no Region/audit mutation. |
| Inactive current request identity | **PASS** | 404 through scoped loading; no Region/audit mutation. The request's authenticated test object is explicitly refreshed from persistence. |
| Regional grant revoked after mount | **PASS** | Save 404 through scoped loading; no Region/audit mutation. |
| Denial controls aggregate | **4 tests / 19 assertions passed** | Fresh before/after values and action audit deltas retained for each fixture. |
| Invalid and manipulated fields | **PASS — 1 test / 40 assertions** | Missing/oversized code, duplicate code, missing/oversized name rejected without mutation. Raw non-schema ID/public-ID/actor/region fields do not persist or rebind the record; unchanged save adds no update audit. |
| Mandatory Core audit fault | **FAIL — 1 test / 6 assertions / one failure** | Exact expected sink once; synthetic RuntimeException propagates. Code/name/is_active nevertheless remain changed; audit count **3→3**. Exact rollback assertion fails before cleanup. |

Final bounded outcomes across four separate serialized invocations: **7 tests / 82 assertions; six passed, one failed**. This is not a combined suite or a passing current-tree result.

## Before/after fault evidence

[Complete safe persisted observations](evidence/phase-3d7-k4-region-update.json) contain normal, per-denial, input and fault cases, each from separately cleaned fixtures. IDs below are disposable IDs, not historical records.

| Field | Before faulted Save | After exception, before fixture cleanup |
| --- | --- | --- |
| id | 1 | 1 |
| public_id | 80f0006d-da5e-4886-8d29-bfcf38372ca0 | unchanged |
| code | K4-A | K4-FAULT |
| name | Disposable K4 before | Disposable K4 audit fault |
| is_active | true | false |
| Total audit rows | 3 | 3 |

Normal control independently saved K4-EDITED / Disposable K4 edited / false, with audit **3→4**, actor 1, resource 1, success and changed fields code/name/is_active. This establishes the real workflow and observer are reachable before interpreting the fault.

Retained exact regression: **`RegionUpdateVerificationTest::test_actual_region_update_rolls_back_when_mandatory_core_audit_fails`**. It requires the expected `.updated` sink once, catches only the expected isolated marker, queries fresh persisted values and audit count, writes evidence, then requires exact pre-operation fields. The final comparison fails on all three editable fields. PHPUnit's later wrapping-transaction rollback does not supply operation-level atomicity.

Private logs under storage/app/private: `k4-authorized.txt`, `k4-denied.txt` (initial harness attempt), `k4-denied-corrected.txt`, `k4-inputs.txt`, `k4-audit-fault.txt`.

## Harness reconciliation

The initial denial attempt reported **four tests / 12 assertions; two passed, two failed**. A chained form-state update after permission revocation produced 404 before the expected Save 403; a raw actor-status fixture update left the component test's cached authenticated User active, yielding 200 where the test expected denial. These failures occurred before the final no-mutation assertions for those cases, so they are not claimed as completed denial/persistence evidence or a confirmed HTTP authorization bypass.

The corrected diagnostic fills before changing authority, refreshes the request's authenticated test identity as the HTTP authentication provider would, then invokes Save directly. All four corrected cases pass with exact no-mutation comparisons. Runtime authentication, policies and persistence were not changed. This explicitly does **not** prove fresh identity revalidation inside the inherited handler or every reused in-process component/cache schedule; genuine HTTP session/provider behavior and concurrent authority changes remain separate verification limits.

## Impact, stop and proposed separate remediation scope

Confirmed impact: an unsuccessful mandatory-audit operation leaves an **unaudited Region catalogue update**, including code, name and activity. Per the authorized stop rule this is a critical integrity defect. It does not demonstrate unauthorized region access, grant revocation semantics, rerouting of immutable submissions, historical exposure or production outage incidence.

**Stop for separate targeted remediation authorization.** Proposed narrow scope: an operation-owned transaction around the actual Region Edit update and unchanged sole Core audit, with fresh existing active identity, staff/explicit permission and exact-region scope checks and narrowly justified locking. Preserve the three-field validation/whitelist, audit attribution/metadata, activity semantics and immutable history. Retain the original actual-action rollback assertion; separately authorize bounded partial-audit/real PostgreSQL fault, stale-authority, concurrency and browser recovery verification. No automatic K1 pattern application, new governance/event, blanket panel transactions, retries, migration or historical repair is implemented here.

No tests, browser/concurrency, full-suite, Pint or build acceptance were run after critical confirmation. No real browser/axe/human testing, every toggle coercion/identity mutation, nationwide concurrency or fresh-provider HTTP journey is claimed. The earlier **307 / 3,365** full-suite pass predates this retained failure and is historical, not a green current tree.

Only diagnostic test/evidence and checkpoint documentation changed. Runtime code, staged content, prior evidence, historical/manual records and accepted remediations remain preserved. No manual database access, runtime fix, new permission/audit event, schema change, historical repair, commit/push/deployment, K5, Batch H or Phase 4. **Batch G remains NOT READY; pause for review.**
