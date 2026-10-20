# G-INT-007 - Institutional representation creation survives mandatory audit failure

## Subsequent authorized remediation — discovery retained as history

**20 October 2026. Targeted development verification passed.** The actual Create now uses a narrowly owned transaction and fresh existing authority checks; model/observer/audit and selector semantics remain unchanged. Original real-action pair 2 / 39 and expanded matrix 22 / 216 pass; complete current-tree PostgreSQL 252 / 2,831 passes. Six concurrency scenarios, target browser 7 / 8 and relevant preservation passed. [Remediation, artifacts and limitations](phase-3d7-g-int-007-remediation.md).

The initial failure/outcomes below are preserved unchanged. Known selector target-size and human acceptance checks remain open. Pause for review; targeted acceptance does not complete Batch G or authorize H/Phase 4.

**20 October 2026. Critical operation/audit integrity defect confirmed by authorized A12 diagnostics; Batch G stopped.** No runtime remediation. G-INT-001 through G-INT-006 remain accepted and unchanged. Batch H, Phase 4 and deployment remain excluded.

## Authorized normal workflow verified first

`RepresentationCreationAtomicityTest::test_authorized_real_create_persists_representation_and_one_matching_audit` uses the actual `CreateInstitutionRepresentative` Filament page, its unchanged real schema/relationship selectors, Livewire `create`, Eloquent model, registered `CoreIdentityObserver` and real `AuditRecorder`.

Disposable synthetic fixtures use the **existing seeded CHEDRO permission bundle**, which includes active staff-panel admission, `institution_representatives.assign` and the SCHOOL target's configured `accounts.create.school` permission. Effective fixture regional scope authorizes the synthetic institution. Both actor and target have existing disposable regional bindings so the unchanged `StaffListScope::users` selector admits the target; representation alone is not its user-selector alternative. Event suppression applies only to these fixture scope rows, never the tested representation creation. No permission definition, selector filter or policy was widened.

The fixture explicitly asserts current identity/permissions, target-role delegation, institution scope and both scoped selector queries before testing persistence. The normal Create succeeds with no form errors and a redirect. It saves the exact target/institution, actor attribution and effective dates, with `verified_at` and `revoked_at` null. Exactly one new matching `App\Models\InstitutionRepresentative.created` audit has the correct resource, actor, institution and successful outcome.

**Normal operation: 1 test / 20 assertions passed.** Private output: `storage/app/private/a12-authorized-create.txt`.

## Mandatory-audit failure reproduction

The retained `test_actual_representation_creation_rolls_back_when_mandatory_audit_fails` repeats that authorized actual-page fixture in a separate serialized test invocation. Only `AuditRecorder::record` is mocked to throw once for this model's actual `.created` event. Page authorization, form validation, real model insertion, model authorization and registered observer still execute. No replacement form, reflection-only handler, mock representation model or test-only persistence handler is used.

Observed sequence:

1. No matching representation exists before the action.
2. Actual Livewire `create` reaches the expected audit sink exactly once.
3. The injected RuntimeException propagates with its expected synthetic marker.
4. Audit count remains unchanged.
5. The new target/institution representation **exists** when inspected before PHPUnit cleanup. The assertion requiring its absence fails.

**Fault regression: 1 test / 13 assertions / 1 failure.** Private output: `storage/app/private/a12-audit-failure.txt`. The two diagnostic invocations were serialized; no full-suite pass or combined-suite execution is claimed. These are actual Filament/Livewire transport tests, not Playwright/browser or human usability testing.

The grant remains inside the still-open PHPUnit fixture transaction until cleanup. That outer cleanup is not an operation-level rollback and does not establish that a real HTTP request owns an encompassing transaction.

## Root cause and authorization boundary

- `CreateInstitutionRepresentative` has no local transaction/service override and inherits Filament's default `handleRecordCreation` model save.
- The panel does not enable optional blanket transactions. The installed `CanUseDatabaseTransactions` trait supplies no operation transaction when disabled.
- `InstitutionRepresentative::creating` checks canonical institution scope, target-role delegation and self-grant denial, then sets actor attribution.
- `AppServiceProvider` registers `CoreIdentityObserver` for this model. Its synchronous `created` callback writes the mandatory audit **after insertion**.
- `AuditRecorder` writes the audit on the normal model connection; it does not own a surrounding representation-creation transaction.

The actual authorized mutation therefore has no owner that rolls back insertion when the observer audit fails. `AccessGovernance::grantInstitution` and account-created representations already have distinct transactional service handlers; neither handles this generic Create page. G-INT-006 protects regional creation only and was not changed or generalized.

## Confirmed impact and limits

An unsuccessful mandatory-audit operation leaves a new **unverified representation row without its matching audit**. That is a critical authority-history/data-integrity defect. Other conditions still govern effective representation and applicant access. This reproduction does **not** establish verified representation, account activation, successful applicant access, an authorization bypass, a historical incident, or corruption of existing submissions/history. Normal creation also does not itself verify or activate the SCHOOL account.

No duplicate/concurrency, after-audit insertion failure, real PostgreSQL audit rejection, response-loss browser recovery or historical completeness claim is made. Those are possible targeted-remediation gates, not completed diagnostics here.

## Earlier fixture failures reconciled

The earlier probe assigned only seeded Admin to its actor while targeting SCHOOL. That bundle lacks `accounts.create.school`; the model's existing target-role predicate correctly denied it with 403 before the intended sink fault. Earlier assertion/harness errors and `g-int-007-*` private logs remain unchanged as historical inconclusive attempts. The new authorized normal-success control and actual sink-fault reproduction supersede their **unverified-risk classification**, not their recorded outcomes.

The only test-code changes in this diagnostic are to the existing `RepresentationCreationAtomicityTest`: factor fixture setup, use the existing correctly permitted actor, assert real selector/authority prerequisites, and add the normal-success control. The original rollback regression name and no-persist comparison remain. No runtime file, observer, permission, audit contract or transaction setting was changed.

## Proposed narrow remediation - not authorized or applied

Separately authorize an operation-owned transaction for **generic institutional-representation creation**, encompassing the existing model insert and sole existing Core observer audit on the same PostgreSQL connection. Recheck current authenticated/active actor, staff admission, explicit assignment permission, existing target-role delegation, self-grant and canonical institution scope at execution time. Assess locking/revalidation for relevant concurrent authority changes. Preserve dates, attribution, unverified initial state, selector rules and existing lifecycle/duplicate semantics.

Retain both diagnostics; expand actual-action tests for successful pairing, before/after audit failures, genuine database audit rejection, current permission/scope/target authority, forged input, duplicate/concurrent behavior and safe persistent/uncertain-response recovery. No new governance, observer replacement, manual duplicate audit, blanket panel transaction, automatic privileged retry, migration, compensation delete or historical repair is proposed.

**Stop for separate remediation authorization.** No further Batch G closure work, selector styling, recovery reruns, concurrency or full-suite/Pint/build acceptance was performed after confirmation. The accepted 230-test / 2,615-assertion baseline predates these diagnostics; the retained failing regression prevents claiming a passing current tree.

## Preservation

Only isolated **`osds_test`** was used; the test explicitly asserts its database before fixture preparation. Manual-testing databases were not inspected or modified. Fixtures are synthetic and transaction-cleaned. Existing runtime protections, prior evidence, historical records and staged changes are preserved. No commit, push, deployment, schema change, historical repair, Batch H or Phase 4 work.
