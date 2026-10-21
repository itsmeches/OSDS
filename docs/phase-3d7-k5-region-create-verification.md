# K5 / A28 — Region Create diagnostic

**Follow-up:** [Targeted K5 remediation](phase-3d7-k5-region-create-remediation.md) now passes its integrity/security, recovery and regression gates. Original failure/results below remain historical and unchanged; the Region Create transaction defect is no longer unresolved. Batch G remains incomplete; pause for review.

**21 October 2026. Disposition: FAIL — confirmed critical operation/audit integrity defect. Batch G stopped / NOT READY; NO-GO for Batch H.** No runtime remediation. Accepted G-INT-001–007 and K1–K4 protections and prior recovery/accessibility evidence are preserved. No Batch H or Phase 4.

## Established creation contract

Inspected RegionResource and registered CreateRegion, RegionForm, RegionPolicy, Region model/creation hook, StaffListScope/ScopeAuthorizer where applicable, CoreIdentityObserver/global registration, AuditRecorder, migration constraints and installed Filament CreateRecord/CanUseDatabaseTransactions.

| Aspect | Existing implemented contract |
| --- | --- |
| Entry | Registered `/admin/regions/create`; CreateRegion inherits CreateRecord's real form validation and `handleRecordCreation` model save. No creation-service/transaction override. |
| Actor | Create policy requires active identity and explicit **regions.manage**. Role labels alone confer nothing. HTTP staff admission separately requires active account/panel permission; tested actor has existing platform.admin.access, regions.view and regions.manage. regions.view is fixture/list permission, not a new Create policy prerequisite. |
| Scope | **No creation-specific existing-region or nationwide grant is required.** Actual authorized control has zero regional and nationwide grants and creates successfully. Scoped resource queries constrain reading existing rows; K4 Update's current-record scope is not imported. No institution/representation/region-selection form exists here. |
| Fields | Required code, max 32, unique; required name, max 255; required boolean is_active toggle, default true. Installed Toggle supplies its boolean rule/cast. Model fillable fields are exactly code/name/is_active. Name has no declared uniqueness rule. |
| Identity/defaults | Model generates public UUID on creation; PostgreSQL enforces unique UUID and code. Database/form activity default true. Explicit false is accepted by the real form. No synthetic flag or creator column exists on this form; attribution is in audit. |
| Guards | Model creating hook generates UUID. No additional Create actor/region-scope guard is implemented in that model. Catalogue creation does not grant the creator regional authority or create a user assignment. |
| Mandatory audit | Global AppServiceProvider registers CoreIdentityObserver. Synchronous created callback writes **App\Models\Region.created**, success, current actor and Region resource/type/ID, existing changed-field-name metadata. Existing **region_id and institution_id are null** for Region resources; no new audit binding inferred. |
| Transaction | Actual mounted page reports optional database transactions disabled. Inherited handler saves Eloquent before synchronous post-insert Core audit. Filament begin/rollback helpers are no-ops when disabled; AuditRecorder inserts on its normal connection without owning the preceding Region transaction. |

Creation is global by existing explicit permission in this implemented path, not role-name authority or an approval of new production governance. No new creation-region restriction is invented. No mandatory event is fabricated: the existing event/writer is established in source and reached by both normal and fault actions.

## Disposable fixtures and actual-action method

`tests/Feature/RegionCreateVerificationTest.php` asserts **osds_test** before seeding. Actors are flagged synthetic; all Region values are fictional disposable catalogue fixtures. Region has no synthetic form field; no manual/real catalogue is used. No event suppression, substitute form/model, reflection bypass, test-only persistence handler or whole-service mock is used. The tested actions use registered CreateRegion, unchanged schema/validation, real Livewire Create, Eloquent and registered observer.

The authorized control ran first. Permission/identity, actual HTTP panel admission, invalid fields and forged/default-state controls completed before one isolated fault at the actual mandatory AuditRecorder sink. All DB-backed invocations were serialized. Only disposable fixture identity/permission state changes, not runtime policy or permission definitions, arrange denied cases.

## Actual-action matrix

| Probe | Result | Persistence outcome |
| --- | --- | --- |
| Normal authorized Create | **PASS — 1 test / 16 assertions** | Exact code/name/false activity, valid generated UUID, no form errors and successful redirect. Exactly one matching attributed Core creation audit. Zero actor regional/nationwide grants. |
| Missing management permission initially | **PASS** | Actual page mount 403, zero Regions/action audit delta. |
| Management permission removed after mount | **PASS** | Actual Create 403, zero Regions/action audit delta. |
| Inactive/deactivated identity | **PASS** | Current authenticated fixture identity refreshed before page mount; each denied 403, zero Regions/action audit delta. Fixture identity-update audit is counted before testing the denial. |
| Permission/identity aggregate | **4 tests / 17 assertions passed** | No unintended Region creation in any denied case. |
| Missing panel admission | **PASS — 1 test / 4 assertions** | Real HTTP GET Create route 403 with management permission retained; zero Regions/audit delta. Not a claim that isolated component tests alone run all HTTP middleware. |
| Missing/oversized code or name; duplicate code | **PASS — corrected 1 test / 41 assertions** | Five actual-form validation cases; no extra Region or action audit. Existing uniqueness fixture remains exactly unchanged, including timestamps. No duplicate-name rule invented. |
| Default activity and forged protected attributes | **PASS — 1 test / 15 assertions** | Default true persists. Raw ID/public UUID/actor/region/institution/created timestamp cannot replace generated identity, current audit actor, null audit bindings or timestamps. Exactly one new existing audit. |
| Creation-specific current-region scope denial | **CONTRACT-N/A** | No such rule or input exists; positive no-scope control proves current behavior. Reading/editing a created row remains separately scoped. |
| Mandatory Core audit failure | **FAIL — 1 test / 6 assertions / one failure** | Exact expected sink once; expected synthetic RuntimeException propagates. Region count **0→1** and audit count **1→1** before fixture cleanup. Retained absence assertion fails. |

Final bounded outcomes across six separate serialized invocations: **9 tests / 99 assertions; eight passed, one failed**. This is not a combined suite or green current-tree result. The earlier input-harness attempt below is separately retained, not included as another successful check.

## Audit-failure before/after evidence

[Complete safe persistence observations](evidence/phase-3d7-k5-region-create.json) retain authorized, denied, input, default/forged and fault cases from independent transaction-cleaned fixtures. IDs are disposable IDs, not historical/manual records.

| Field / count | Before faulted Create | After exception, before cleanup |
| --- | --- | --- |
| Matching Region | absent | exists, ID 1 |
| code | absent | K5-CONTROL |
| name | absent | Disposable K5 region |
| is_active | absent | false |
| public_id | absent | 6f987e37-8deb-4f4a-aee4-87560c1206c5 |
| Region count | 0 | 1 |
| Total audit rows | 1 | 1 |

Normal control independently records exact same intended fields with its own generated UUID and audit **1→2**, actor 1, Region resource 1, success, null region/institution audit columns and existing empty changed_fields creation metadata. The authorized workflow and observer were therefore proven reachable before the fault.

Retained regression: **RegionCreateVerificationTest::test_actual_region_creation_rolls_back_when_mandatory_core_audit_fails**. It requires the exact existing `.created` sink once, catches only the expected isolated marker, queries fresh persisted Region/audit counts, writes evidence and requires the Region absent. The final absence assertion fails on the real saved row. Later PHPUnit wrapping-transaction cleanup is not application-operation rollback and does not negate the observed defect.

Private logs under storage/app/private: k5-authorized.txt, k5-denied.txt, k5-panel.txt, k5-inputs.txt, k5-inputs-corrected.txt, k5-forged-default.txt, k5-audit-fault.txt.

## Harness reconciliation

First invalid-input probe: **1 test / 8 assertions / one failure**. It compared an immediately created model's raw-original associative array with a freshly queried row; column order differed while every value matched. This was an exact-array-order harness assumption, not an application mutation. The baseline now comes from a fresh database query too; the exact same-value/timestamp comparisons remain. Corrected five-case input probe passes **1 / 41**. No runtime validation, policy or persistence changed, and the initial failure log remains retained.

No other control/harness failure occurred. No runtime repair or weakened rollback comparison was used to make the fault reachable.

## Impact, stop and proposed separate remediation scope

**Confirmed critical integrity defect:** an unsuccessful mandatory-audit Create leaves an unaudited Region catalogue row with code/name/activity and generated identity. This does not demonstrate a user-scope grant, unauthorized regional access, historical incident, production outage or changed immutable submissions. The fault fixture's row is inactive; no active-row exploitation chain is claimed.

**Stop for separate targeted remediation authorization.** Proposed narrow scope: one operation-owned transaction around the actual Region Create insert and unchanged sole Core audit, with execution-time revalidation of existing authenticated/active staff and explicit management authority. Preserve the three fields, defaults, UUID generation, validation/code uniqueness, global permission-based creation contract and null audit bindings. Retain original actual-action success/fault tests; separately authorize bounded partial-audit/real PostgreSQL failure, stale-authority, concurrency and browser recovery checks. Do not import K4 current-region scope, add a new permission/event, duplicate audit writer, blanket transaction, automatic replay/retry, migration or historical repair. No such remediation is implemented here.

No further tests, full-suite, browser/concurrency, axe, Pint or build acceptance were attempted after confirmation. These are actual Filament/Livewire and one HTTP admission probe, not Playwright/human tests. Direct-model callers, arbitrary identity-cache schedules, every boolean coercion, duplicate race, partial-audit insertion, real database audit rejection and uncertain-response recovery are not independently certified by K5 diagnostics.

Only this diagnostic test, safe evidence and checkpoint documentation changed. K1–K4, G-INT-001–007, staged content and previous recovery/accessibility/historical evidence remain preserved. Only osds_test was used; no manual database inspection/reset/reseed/mutation, runtime fix, schema/governance change, commit/push/deployment or historical repair. The earlier **327 / 3,575** full-suite checkpoint predates this retained failure and is historical, not a passing current tree. **Batch G NOT READY; pause for review. No Batch H or Phase 4.**
