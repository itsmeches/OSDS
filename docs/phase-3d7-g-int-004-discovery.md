# G-INT-004 - Application-reviewer creation survives mandatory audit failure

**Follow-up:** Targeted remediation was subsequently authorized. Implementation and verification are recorded in [G-INT-004 remediation](phase-3d7-g-int-004-remediation.md). The discovery and pre-fix evidence below are retained unchanged as the initial stop record.

**20 October 2026. Critical integrity gate confirmed; resumed Batch G stopped.** No runtime remediation applied. G-INT-001/002/003 acceptance remains preserved; Batch G is NOT READY.

## Reproduction and persistence impact

`ReviewerAssignmentCreationAtomicityTest::test_application_assignment_creation_rolls_back_when_mandatory_audit_fails` mounts the actual `CreateSyntheticApplicationReviewerAssignment` Filament page as an active synthetic Admin with its existing explicit management permission, fills valid synthetic reviewer/region/module/effective-date/reason fields, then calls the actual Livewire `create` action. Only the mandatory AuditRecorder sink is faulted; authorization, validation, Eloquent insertion and the synchronous created observer execute.

The audit sink is reached exactly once with `application.reviewer_assignment_granted` and throws the expected isolated exception. Audit count remains unchanged. Assignment count nevertheless increases from zero to one. The final rollback assertion fails: **1 test / 8 assertions / 1 failure**. The test asserts database `osds_test` before seeding. PHPUnit fixture cleanup isolates the reproduction; no manual records are involved. Private result: `storage/app/private/batch-g-creation-atomicity.txt`.

This confirms an unaudited grant surviving an unsuccessful create operation. Such a row can satisfy the assignment portion of review authority when all other existing checks also hold. It does not prove access without other required permissions/scope, a historical incident, or a Phase 3C creation failure. No browser result or real audit-outage incidence is claimed.

## Root cause and affected paths

- `CreateSyntheticApplicationReviewerAssignment` inherits CreateRecord without enabling operation transactions or owning a transactional creation service.
- Installed CreateRecord calls `handleRecordCreation`; Eloquent inserts before the model's `created` observer records audit.
- `SyntheticApplicationReviewerAssignment` calls AuditRecorder from that observer; AuditRecorder inserts on its normal connection without an encompassing owner transaction.
- Filament's transaction trait does nothing when optional panel/page transactions are disabled. The application panel does not enable blanket transactions.

The management contract remains global by existing explicit Phase 3D permission, active staff admission and sandbox restrictions. No Phase 3C regional management rule is imported.

## Proposed separate remediation scope - not implemented

Authorize a narrow creation transaction spanning the actual grant and its sole mandatory observer audit, with fresh execution-time authorization under the existing contract. Preserve attribution, validation, synthetic restrictions and all existing governance. Verify the actual Create page, successful grant, failures before/after audit insertion, real database audit rejection, stale authority, safe feedback, duplicate/uncertain responses and concurrent behavior. Do not infer a new duplicate-grant/idempotency policy or silently retry privileged creation. If those semantics need a decision, stop for clarification. No schema change, historical repair or blanket panel transaction is proposed.

## Remaining Batch G evaluation at stop

| Requirement | Current evidence / disposition |
| --- | --- |
| G-INT-001 finding atomicity | Completed targeted development verification; prior evidence retained, no runtime change this turn |
| G-INT-002 Phase 3C revocation | Completed targeted development verification; separate approved regional rule preserved |
| G-INT-003 Phase 3D revocation | Accepted targeted development verification; existing global permission contract preserved |
| Applicant save/conflict/network/session/uncertain-response recovery | Prior Batch C actual browser coverage; broader G final-tree rerun pending |
| Builder conditions/selection and staff recovery | Prior E/F and preservation evidence; broader G final-tree rerun pending |
| Rapid modal cancel/open | Earlier timeout plus fresh-GET independent-case passes; controlled same-page timing investigation untested this turn |
| Held-response builder selection | Earlier timeout, unchanged isolated and full 14-case passes; repeated controlled timing investigation untested this turn |
| Assignment creation and mandatory audit | Phase 3D actual Create action now fails integrity gate; Phase 3C null callback and creation atomicity remain separate unresolved leads |
| Broader error/serialization inventory | Partial prior source/service/browser evidence; not comprehensively verified |
| Performance | Prior F measurements and D guarded pagination exist; remaining G workload/query/latency measurements unperformed |
| Final focused/full PostgreSQL/browser/axe/Pint/build gates | Not rerun after discovery; new retained regression fails. Prior 177 / 2,087 pass predates it |
| Human testing/native zoom/screen readers | Outstanding; no new claim |

No timing root cause, harness-only classification, performance acceptance or Batch G completion is asserted. The creation check was prioritized because it could persist authority without mandatory audit; the stop condition prevents further inventory or remediation. Separate targeted authorization is required. Batch H, Phase 4, deployment, production operations and historical repair remain excluded.

Only the regression and discovery/checkpoint documentation changed. No existing staged changes, runtime protections, schema, historical data, `osds_sandbox` or `osds_dev` were inspected or modified. No commit or push in this resumed inventory turn.
