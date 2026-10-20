# G-INT-005 - Phase 3C creation handler does not roll back a failed mandatory audit

**Follow-up:** Targeted remediation was subsequently authorized. The independently established creation contract, reproduced search callback failure, implementation and current verification are recorded in [G-INT-005 remediation](phase-3d7-g-int-005-remediation.md). The initial stop record below is retained historical evidence.

**20 October 2026. Critical backend grant/audit integrity defect confirmed; Batch G stopped.** No remediation applied. G-INT-001 through G-INT-004 acceptance and runtime protections remain unchanged. This is not an end-to-end UI exploitation finding.

## Confirmed reproduction and its boundary

`EvidenceReviewerCreationAtomicityTest::test_inherited_creation_persistence_rolls_back_when_mandatory_audit_fails` uses only `osds_test` and the real `CreateSyntheticEvidenceReviewerAssignment` page's inherited `CreateRecord::handleRecordCreation` implementation. A reflection call isolates that protected persistence boundary; it does **not** mount, validate or invoke the public Livewire Create action. No test subclass replaces the form, handler, model, observer or persistence operation.

The fixture is an active synthetic Admin with the existing explicit management permission, a synthetic CHEDRO reviewer, and valid region/module/start/reason fields. Resource `canCreate` admits the actor. The test verifies the page's optional database transactions are disabled and that the handler actually comes from installed Filament `CreateRecord`. Only the downstream AuditRecorder sink is mocked to throw once for `evidence.reviewer_assignment_granted`.

The inherited handler saves the actual Eloquent model. Its real `created` observer reaches the mandatory audit sink, which throws. Audit count remains unchanged, but assignment count increases **0 → 1** inside the still-open PHPUnit fixture transaction. The rollback assertion fails before fixture cleanup. This establishes operation-level persistence surviving an audit failure, independently of eventual test teardown. It does not establish an actual historical audit outage or unauthorized access.

Final retained probe run: **2 tests / 11 assertions; 1 passed, 1 failed**. The passing test mounts the actual initial Livewire page and verifies no mutation. The failing test is the inherited persistence regression above. Private output: `storage/app/private/g-int-005-confirmation.txt`. Original attempt output remains `storage/app/private/batch-g-evidence-creation-discovery.txt`.

### Historical callback report not reproduced by initial mount

The first probe incorrectly expected the previously documented null-query callback failure on empty initial mount. Mount completed instead, making that expectation fail; this was a test assumption, not a second confirmed application defect. The probe was corrected to record the actual nonmutating initial mount. The callback still names its injected argument `$q`, but search, selection, field validation and a complete Create action were not exercised in this bounded run. Whether the earlier callback failure occurs in those states remains unresolved. No runtime callback correction was applied to make the persistence probe possible.

Accordingly, this finding must not be described as a successful full-form submission leaving an unaudited grant, a proven forged Livewire action, or a confirmed current UI callback crash. The **backend handler atomicity failure is confirmed**; full UI reachability/recovery remains unverified.

## Root cause

- `CreateSyntheticEvidenceReviewerAssignment` inherits the default CreateRecord persistence handler and has no local creation transaction/service override.
- Its actual panel/page transaction setting is false; the installed optional transaction trait therefore supplies no enclosing operation transaction.
- The handler performs `$record->save()` before `SyntheticEvidenceReviewerAssignment` invokes the mandatory audit in its synchronous `created` observer.
- AuditRecorder does not own a surrounding assignment transaction. A post-insert exception therefore leaves the grant present until an unrelated outer rollback, if any.

The separate Phase 3C revocation service added for G-INT-002 protects revocation only. Phase 3D's G-INT-004 creation service does not handle this model/page. Neither fix regressed or was changed.

## Authority and policy limits

Observed creation resource admission is active actor plus `evidence.reviewer.assignments.manage`, with staff-panel admission separately required. The model supplies sandbox/permission/synthetic-reviewer guards and grant attribution. No creation-specific manager-region predicate was found in this inspected path. G-INT-002's approved **revocation** region rule must not silently be imported into creation, nor may Phase 3D's global contract be treated as approval of a new Phase 3C policy.

This finding is an unaudited persisted authority row at the backend boundary. Other explicit review permissions, effective exact reviewer assignments, synthetic restrictions and ScopeAuthorizer still govern actual case review. No case-access bypass or historical exposure is claimed.

## Proposed separate targeted scope - not implemented

Authorize Phase 3C creation atomicity remediation and establish the intended existing creation-management contract before changes. Use one narrow operation transaction spanning assignment insertion and its sole existing mandatory observer audit, with fresh execution-time authorization and safe recovery. Preserve existing duplicate-grant behavior until verified; request a decision if policy is undefined. Verify the actual form/search/selection/Create path without concealing callback errors, partial-audit and real PostgreSQL failures, stale authority, duplicate/concurrent behavior and uncertain responses. No blanket panel transaction, new permission, migration, speculative regional rule, automatic replay or historical repair is proposed.

## Bounded inventory at the stop

| Path family inspected | Evidence in this resumed turn | Disposition |
| --- | --- | --- |
| G-INT-001 finding; G-INT-002/003 revocation; G-INT-004 creation | Source transaction ownership and previously accepted reports | Preserved; no new runtime change or full rerun |
| Account creation/edit/status/roles and regional/representation/nationwide governance | Source scan finds explicit transactions in AccountGovernance and AccessGovernance | Partial source inventory; not comprehensive failure injection or new acceptance |
| Questionnaire authoring, clone/create, review/publication/retirement | Source scan finds service transactions | Partial source inventory; audit sink/transaction connection failure matrix not rerun |
| Applicant draft/evidence/copy/submission/screening/corrections | Source scan finds service transactions | Prior evidence retained; broader resumed recovery/serialization assessment incomplete |
| Phase 3C reviewer creation | Actual initial mount plus inherited handler/observer fault probe | Backend critical defect confirmed; full UI reachability unverified |
| Applicant eligibility creation | Resource/page/model source inspected; no audit writer observed in those files | Audit requirement/other hooks not exhaustively established; source-level lead, not a confirmed second critical defect |
| Rapid modal transition / held-response builder | Prior reports retained | Controlled new timing assessment not performed before stop; no new application-vs-harness classification |
| Query counts, latency, isolated workloads | No new measurement performed | Pending; source transaction presence is not performance evidence |
| Error/serialization/session/uncertain response across workflows | Existing accepted targeted results retained | Remaining cross-workflow evaluation pending |

No transaction-presence scan proves every privileged mutation or mandatory audit is safe. Inventory remains incomplete. The new retained failing regression supersedes the prior green current-tree baseline for readiness: **192 tests / 2,232 assertions was the G-INT-004 checkpoint, not a pass after this discovery.** No new complete-suite, browser preservation, axe, Pint or build acceptance is claimed in this stopped turn.

## Preservation and readiness

Only the new test and discovery/checkpoint documentation change in this turn. Existing unstaged/untracked G-INT-004 work and prior evidence are preserved; no index operation, commit, push, migration, production operation or runtime remediation. No `osds_sandbox`/`osds_dev` inspection, reset, reseed, mutation or historical repair.

**Batch G NOT READY; Batch H is not authorized or ready to start.** Human usability, native zoom, screen readers and remaining manual checks stay open. Stop for separate targeted authorization under the independent critical-integrity rule. Phase 4, deployment and production readiness remain excluded.
