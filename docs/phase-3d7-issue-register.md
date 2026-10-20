# Phase 3D.7 issue register

## G-INT-003 targeted acceptance - PASS for development

The user authorized targeted G-INT-003 remediation only. Before implementation the audit reported the existing Phase 3D manager contract as **global by explicit `applications.reviewer.assignments.manage` permission for active staff**, with existing panel admission and sandbox boundaries; no manager-region/module predicate was found or inherited from Phase 3C. `ApplicationReviewerAssignmentService` now reloads the actor, locks the exact row, rereads current identity after lock wait, authorizes the unchanged global permission, validates the one-revocation lifecycle/reason and executes update plus the sole existing observer audit in one PostgreSQL transaction. The real table action uses safe support-reference/persistent dialog feedback and existing session/network recovery.

Original regression: **one test / 13 assertions passed** (retained pre-fix failure **one / eight**). Final targeted matrix **15 / 112**; complete current-tree PostgreSQL **177 / 2,087**, passed. Includes an actual PostgreSQL audit-insert rejection, operation-level rollback, same PDO, partial audit rollback and redacted response/log checks. Actual revoke browser **eight interactions / eight screens**, two real PostgreSQL concurrent worker pairs and final Pint/build/diff pass. Preservation browsers: Phase 3C revoke **eight / eight screens**, CHEDRO **12 / 30 screens**, applicant original plus **11 cases**, builder original plus final clean **14-case** matrix and **seven condition checks**, all passed. Global Phase 3D management survives regional-grant expiry/revocation by its existing contract; inactive actor/permission loss denies it. Phase 3C retains its separately approved regional rule. No Phase 3C runtime change, new permission, schema, historical repair, manual-testing database or index operation.

Failed attempts and limits are retained in `docs/phase-3d7-g-int-003-remediation.md`: a test queried the wrong audit action; artifact paths were corrected and Phase 3C evidence regenerated; a Phase 3C modal-transition timeout led to fresh GETs between independent preservation cases; a builder held-response selection check timed out before any new edit, then passed unchanged in isolation and in the full serialized matrix. No unintended persisted builder mutation was demonstrated, and no builder runtime fix was applied. These observations remain broader reliability/manual follow-ups. Human usability, native zoom/screen-reader/full accessibility and production acceptance remain open. No independent critical defect was confirmed in this bounded task. The initial stop records below are historical. **G-INT-003 targeted gates pass; broader Batch G remains incomplete and no unrelated G/H/Phase 4/deployment work is authorized by this result.**

## G-INT-003 initial stop - historical

**20 October 2026. NOT READY.** All targeted G-INT-002 gates passed, then the resumed reliability audit reproduced a separate Phase 3D application-reviewer assignment audit-integrity defect. Work stopped immediately. No Phase 3D transaction/governance fix or historical repair was applied. The user's approved Phase 3C region-scoped rule is not extended to Phase 3D.

### G-INT-003 - Application-reviewer assignment revocation survives mandatory audit failure

**Severity:** Critical authority-history/audit-integrity gate. **Status:** Confirmed; separate targeted authorization required.

The implemented `SyntheticApplicationReviewerAssignmentResource` revoke action directly updates its Eloquent record. The model's updated observer subsequently records `application.reviewer_assignment_revoked`. No encompassing operation transaction is enabled by the action/model/panel. A downstream audit exception therefore leaves the revocation persisted without a matching audit event.

Retained real PostgreSQL/Filament regression: `EvidenceAssignmentAtomicityTest::test_application_assignment_revocation_and_audit_are_atomic_when_audit_fails` — **one test / eight assertions / one failure**. The Admin has the existing explicit Phase 3D management permission; the actual list/action, Eloquent observer and PostgreSQL persistence execute. Only the downstream recorder is faulted after fixture setup. Audit count remains unchanged; the comparison requiring the assignment's complete stored state to remain unchanged fails because revocation/attribution persisted. Private evidence: `storage/app/private/batch-g-application-assignment-atomicity.txt`. Only isolated `osds_test` fixtures were used.

This confirms an unaudited synthetic application-reviewer grant revocation, not expanded reviewer access, a historical outage, unauthorized grant creation or historical audit incompleteness. The Phase 3C service is not used by this separate operation and must not silently be generalized across the two models. Proposed separate remediation: an authoritative Phase 3D revocation/audit transaction with current explicit actor/target authorization, row locking, duplicate/concurrent tests and actual table-action/browser coverage. Its manager region-scope contract requires an explicit decision; the current Phase 3C approval does not settle that policy.

The complete **162 tests / 1,975 assertions** pass below was obtained on the final G-INT-002 runtime tree before this new failing regression was added. The current tree intentionally contains G-INT-003; no new full-current-tree pass is claimed. Broader G work, H, Phase 4 and deployment are stopped under Sections 6/10 of the continuation approval and the prior independent-critical stop condition. No manual-testing database, stored historical record or staged-index change was made.

## G-INT-002 final targeted result - passed

The original and expanded matrix passed **14 tests / 89 assertions**; combined G-INT-001/002 focused **22 / 160**; complete final runtime PostgreSQL **162 / 1,975**. Actual final revoke browser: **eight checks / eight light/dark desktop/mobile screens**, with zero recorded axe/overflow/uncaught-error/checked-response-leak failures. Two real PostgreSQL worker pairs verified lock waits: one committed revocation/audit with duplicate rejection, and one committed pair with post-lock inactive-actor denial. CHEDRO **12 interactions / 30 screens**, applicant original plus **11 cases**, builder original plus **13 cases** and **seven condition checks** all passed. Pint/build/diff passed. See `docs/phase-3d7-g-int-002-remediation.md` for implementation, actual screenshots, failed-attempt explanations and limitations. Broader G resumed only after these gates, then stopped at G-INT-003 above. The checkpoints below are historical.

## G-INT-002 approved-rule implementation checkpoint

The user explicitly approved the new Phase 3C revocation rule: active authenticated actor, sandbox, management permission and effective `ScopeAuthorizer::canAccessRegion` for the exact assignment region (including its existing effective nationwide alternative). No Phase 3D management rule was changed. `EvidenceReviewerAssignmentService` now locks and reauthorizes the exact row inside the transaction encompassing the existing observer audit. The table action uses this service and safe support-reference feedback; its list has existing session/network recovery guidance.

Targeted matrix **14 tests / 89 assertions**; combined G-INT-001/002 focused **22 / 160**; complete final runtime PostgreSQL **162 / 1,975**, all passed. Actual revoke browser **eight checks / eight light/dark desktop/mobile screens**, zero recorded axe/overflow/page-error/response-leak findings. It verifies partial audit rollback, retained remarks, duplicate click, committed response loss, authorized audit inspection, System keyboard changes and post-mount permission/scope/session loss. Two separate PostgreSQL workers verify an actual row-lock wait: one commit, one already-revoked rejection, one audit. Pint/build/diff pass. Applicant/builder/CHEDRO preservation browser reruns are still finishing; broader G has not resumed yet. The original pre-fix evidence is retained. No manual-testing database, index, historical record, creation callback or Phase 3D governance change.

The blocker/stop wording below is historical and superseded by this implementation checkpoint. Final acceptance remains subject to the remaining browser preservation gates. See `docs/phase-3d7-g-int-002-remediation.md`.

## G-INT-002 authorization checkpoint - governance decision required

20 October 2026: targeted remediation is authorized, but its pre-implementation audit found that the current assignment manager boundary is active actor plus explicit management permission, with no target-region policy. The required wrong-region/expired-scope denials would add governance semantics not defined by this resource or its approved plans. Sections 3 and 11 of the targeted approval require reporting that difference and stopping before inventing policy. No runtime fix was applied. The original actual-action regression remains unchanged: **one test / eight assertions / one failure** on `osds_test`. See `docs/phase-3d7-g-int-002-remediation.md` for the exact source findings, proposed narrow transaction and concrete permission-plus-effective-region rule awaiting decision. No additional critical creation defect is claimed confirmed; its null callback remains unresolved. Manual-testing data and staged changes remain preserved.

The earlier request for targeted authorization below is historical; authorization was received, and the current blocker is the governance clarification above.

## Current Batch G gate — G-INT-002

**20 October 2026. NOT READY.** G-INT-001 targeted gates passed, then the resumed audit confirmed an independent mandatory-audit defect in reviewer-assignment revocation. Work stopped immediately under Section 13 of the targeted authorization. No assignment remediation or historical repair has been applied.

### G-INT-002 — Reviewer-assignment revocation persists after mandatory audit failure

**Severity:** Critical authority-history/audit-integrity gate. **Status:** Confirmed; targeted authorization required.

An authorized synthetic Admin opens the actual Phase 3C reviewer-assignment list and invokes its implemented `revoke` table action with a reason. The action calls `$record->update(...)`; the assignment model's `updated` observer then records `evidence.reviewer_assignment_revoked`. There is no operation transaction spanning these writes. Installed Filament transactions default to false, and neither this table action nor the panel enables them. If the downstream recorder throws, PostgreSQL retains the revocation without its corresponding audit event.

Retained real PostgreSQL/Livewire regression: `ApplicantSandboxTest::test_reviewer_assignment_revocation_and_audit_are_atomic_when_audit_fails` — **one test / eight assertions / one failure**. Only the downstream audit boundary is faulted after fixture setup; the real list, table action, model update and PostgreSQL persistence execute. The expected unchanged assignment instead has a non-null `revoked_at`; audit and assignment-row counts remain unchanged. The fixture's outer transaction does not roll back the failed operation before inspection. Diagnostics are retained privately in `storage/app/private/batch-g-assignment-atomicity-reproduction.txt`.

Confirmed impact is an unaudited revocation of an existing synthetic reviewer grant despite an action exception. This does not establish unauthorized grant creation, access expansion, an ordinary real outage or historical audit incompleteness. The same model's creation observer also records audit after insertion, but creation-failure impact is not claimed reproduced. An initial attempt to test the actual Create page instead encountered a null relationship query callback (`modifyQueryUsing: fn ($q) => ...`); that separate functional defect remains unresolved and was not worked around in runtime code.

Affected implemented path: `SyntheticEvidenceReviewerAssignmentResource::table()` → actual revoke action → `SyntheticEvidenceReviewerAssignment` updated observer → `AuditRecorder`. The finding transaction fixed under G-INT-001 does not encompass this separate account-authority operation. Proposed separate remediation: one supported transactional boundary for this assignment mutation and its mandatory audit, with fresh existing authority checks, grant/revocation attribution, rollback/partial-audit tests and real Filament action coverage. No new policy, migration, historical repair or blanket panel transaction change is authorized by this report.

The complete **148-test / 1,886-assertion** pass below predates the new failing regression. The current suite intentionally contains this failure; no new full-current-tree pass is claimed. Only `osds_test` was used for the reproduction. Manual-testing databases and the staged index were not modified. Broader G, H, Phase 4 and deployment remain stopped.

## G-INT-001 targeted remediation — gates passed

20 October 2026: the user authorized the finding/audit transaction and directly related response handling. The finding service now uses one default PostgreSQL transaction, with a fresh actor and requirement, current authorization, finding insert and mandatory audit insert. Existing append-only records are not altered. Failure before or after the audit insert rolls back both records on the same PDO connection. Delivered success is inline server-controlled feedback; uncertainty hides stale confirmation and blocks normal replay pending authorized history inspection. No server idempotency guarantee has been added.

The original regression and expanded matrix passed **eight tests / 71 assertions**. Final G-INT-001 PostgreSQL: **148 tests / 1,886 assertions**. Focused browser: **four checks / 12 screens**, no recorded axe/overflow/page-error/response-leak failures; held-response controls, audit-side rollback, committed response loss and authorized keyboard history inspection pass. Two overlapping real PostgreSQL transactions committed two findings and two corresponding audits with unchanged review-copy digest. CHEDRO journey: **12 interactions / 30 screens**; original applicant reproduction and 11 expanded cases pass; original builder reproduction, 13 expanded cases and seven condition-journey checks pass. Pint/build/diff pass. Computed error text: **7.60:1 light / 11.16:1 dark**. See `docs/phase-3d7-g-int-001-remediation.md` for exact evidence and limitations. Broader G resumed only after these gates, then stopped at G-INT-002 above. The original discovery/stop below is historical. No historical data or manual-testing databases were inspected or repaired; staged changes remain preserved.



## Batch G initial inventory - 20 October 2026 (historical G-INT-001 discovery)

Batch F is conditionally accepted; Batch G source/runtime inventory is authorized. No Batch G runtime change has been applied. Inspected applicant and builder action catches/state, both CHEDRO pages and shared queue scope, shared Laravel exception configuration, and evidence review transaction/audit calls. Existing local safe-reference handlers preserve authorization exceptions; shared unexpected HTTP handling, measured query workloads and broader recovery inventory remain unverified for G.

### G-INT-001 - Append-only evidence finding survives failed audit recording

**Severity: Critical transaction/audit-integrity gate. Status: confirmed; Batch G stopped before runtime remediation.**

The actual Phase 3C Livewire saveFinding action invokes SyntheticEvidenceReviewService::finding. That service inserts the append-only SyntheticEvidenceReviewFinding, then calls AuditRecorder::record outside a transaction spanning both writes. The recorder independently inserts AuditEvent. The finding model has no automatic audit observer and prohibits ordinary updates/deletes.

A focused real PostgreSQL/Livewire regression creates a complete synthetic review copy, an explicitly assigned CHEDRO reviewer and matching current regional scope. Only the downstream AuditRecorder failure is injected; authorization, the component, domain service and finding insert remain real. The action reports staffAction failure, audit count remains unchanged, but finding count becomes **1 instead of 0**. Thus the action leaves partial protected history instead of rolling back. A later explicit retry could add another finding; that retry consequence is a source risk, not separately reproduced here.

Retained regression: ApplicantSandboxTest::test_evidence_finding_and_audit_are_atomic_when_audit_recording_fails. **1 test / 6 assertions / 1 failure.** Failure: expected zero new findings, observed one. The test uses RefreshDatabase's isolated osds_test transaction; it proves missing operation rollback, not a historical production outage. In a normal autocommit request the service has no enclosing transaction of its own. No manual-testing/production record or historical audit completeness was inspected.

Only the regression and documentation changed for G. No runtime, schema, permissions, grant, encryption, lifecycle or stored-history repair was applied. The failing test remains in the suite; the previous 140-test Batch F result is historical and cannot be claimed as a current-tree full pass. No Batch G browser, performance, Pint/build or complete-suite gate is claimed after this stop.

Proposed separately authorized remediation: make finding insertion and its corresponding audit record one transaction, preserving exact case/revision authorization, append-only attribution and controlled outcomes; verify rollback on audit failure, successful paired persistence and safe response/retry inspection. Do not erase or repair existing findings or introduce new policy/idempotency schema without approval. Scope any analogous defect only after authorized assessment; no other critical reproduction is claimed.

The mandatory stop is from **Section 13 of the user's Batch G authorization**, requiring separate targeted approval for a new independent critical data-integrity defect. Ordinary Batch G work remains stopped. Batch H/Phase 4/production/historical repair remain unauthorized. Manual-testing databases and staged changes remain untouched.

## Current Batch F result - 20 October 2026

**SEC-007/008 fixed; Batch F CONDITIONALLY READY for user review only.** Final PostgreSQL 140 tests / 1,815 assertions; staff journey 15 interactions / 94 screens; shared pages three checks / 24 screens; final preview one check / four screens; security reruns five credential/four screens and six assignment/four screens. Final reported axe/overflow/page errors are zero. Pint/build/diff pass. Human retesting remains pending. No manual-testing database access, migration, historical repair or staged-index change. Pause before G; H/Phase 4/deployment excluded.

The F findings below are implemented and verified within that representative scope; original failures/stop records remain historical. See the Batch F report for actual measurements, first-attempt failures, current screenshots and limitations.



## Resumed Batch F ordinary refinement inventory — 20 October 2026

SEC-007/008 targeted gates passed; ordinary Batch F work resumes. Changes below are presentation/recovery work using existing services, not new governance permissions.

| ID | Priority | Confirmed source defect | Implemented correction |
| --- | --- | --- | --- |
| ERR-001 | High | Both configuration publication catches report the raw exception and display its message. Other review/retire actions have no consistent recovery feedback. | Preserve authorization/validation denials; redact unexpected diagnostics to support reference/class only; confirm success only after the service returns. |
| UX-007 | Medium | Publication list omits audience and offers the staff-preview URL for applicant publications. | Label/filter the audience; offer preview only for its supported staff channel; clarify retirement consequences. |
| F-UX-001 | Medium | Approved review next-step text describes only staff publishing; completed rejected reviews render current editable content as if it were the reviewed candidate. | Explain separate audiences; distinguish returned drafts from recorded review identifiers without inventing historical snapshots. |
| F-UX-002 | Medium | Assignment/eligibility tables use empty revocation dates as “Active,” ignoring future/expired dates. | Show plain effective-state labels without using presentation as authorization. |
| F-UX-003 | Medium | Generic regional edit headings and binding selectors imply reassignment; date errors lack task context. | Plain labels and date-edit guidance; keep SEC-008 binding/stale protections. |
| F-UX-004 | High usability | After approval redirects, publication controls can appear before Livewire initialization; readiness probes observed a missing page root/Livewire object during that transition. Early clicks dispatched no action. The initial key-remount hypothesis did not resolve it and was removed. No wrong decision/publication was observed. | Native `wire:cloak` keeps header actions hidden until initialization. The subsequent native click and lost-response recovery passed; final browser matrix passed. |
| F-UX-006 | Medium | Shared staff preview puts raw publication/configuration references in primary content. | Keep them under a native Configuration details disclosure; final four-screen and native Enter check passed. |
| F-UX-005 | Medium | Applicant publication confirmation used the generic “Submit” label and inconsistent date labels. | Explicit audience-specific publication button and matching “Available from/until” labels. |
| F-REL-001 | High UI reliability | Real account-creation browser runs emitted passwordRevealFormComponent/isPasswordRevealed undefined errors during reactive creation. No incorrect credential persistence or response disclosure was established. | Create uses the native fixed password control; Edit retains its existing reveal control. A trial keyed Group did not resolve the error and was removed. Final native-control probe and complete staff journey passed with zero uncaught errors. No vendor patch, authorization, validation or recovery-policy change. |
| F-FLOW-001 | High usability | Regional Users lists intentionally require a target regional grant, while SCHOOL creation creates an institutional representation. A regional verifier cannot find that new SCHOOL account through Users. The representation list and canonical verification service already authorize the institution-based operation. | Expose the existing verification action on the scoped representation row; keep Users scope and creator/verifier separation unchanged. Actual separate-verifier browser workflow passed. |
| PERF-002 | Medium | Publication status and action visibility repeatedly query retirement. | Eager-load the existing retirement relation and reuse it. |
| F-A11Y-002 | High accessibility | Actual axe on Admin regional assignments and Super User institutions found link-name: nullable dates or institution identifiers became empty record links. | Plain No end date/Not revoked and missing-value placeholders on regional/representation/institution tables; final browser rerun passed. |
| F-A11Y-001 | High accessibility | Actual axe on Admin representations at 375px found scrollable-region-focusable: a read-only horizontal table had no keyboard-reachable content. | Scoped native table hook adds a named focusable region and shared-token focus ring. Focused browser probe passes; final matrix includes passing native arrow-key scrolling. |

No new critical authorization or data-integrity defect is established by these presentation findings. Batch F gates passed; user review and human acceptance remain pending. G/H, Phase 4 and deployment are excluded.

## SEC-008 targeted remediation checkpoint - before ordinary Batch F completion

The original actual Filament self-extension regression now fails closed and retains the original PostgreSQL end instant. Regional and representation date edits use current delegation authorization, transactional row locks and a locked stale-record fingerprint. Forged bindings/revocation fields and expired/revoked grant revival are rejected. Existing grant/revoke/transfer services, permissions, target predicates, UTC and audit behavior remain intact.

Expanded assignment plus SEC-007 matrix: **18 tests / 216 assertions passed**. Complete PostgreSQL suite: **135 tests / 1,734 assertions passed**. Focused assignment browser: **six interactions / four stable light-dark desktop-mobile screens passed**, including two-tab stale writes and response reordering; zero reported axe, overflow or page errors. Pint, production build and working-tree diff passed. The original and 11 expanded applicant browser cases and original and 14 expanded editor cases passed.

See `docs/phase-3d7-sec-008-remediation.md` for exact paths, restrictions, screenshot evidence and limitations. No manual-testing database was inspected or changed; staged changes remain preserved. **At this earlier checkpoint, ordinary Batch F refinement remained incomplete.** The final Batch F result above now supersedes that readiness status. G/H, Phase 4 and deployment remain excluded. The original stop record below is retained as history.

---


## SEC-008 — Original discovery record (superseded by remediation above)

**Date:** 20 October 2026. **Severity:** Critical authorization / self-delegation bypass.
**Status at discovery:** Confirmed; Batch F stopped immediately after SEC-007 verification. The stop and failing result below are historical; separately authorized SEC-008 remediation is recorded above.

### Reproduction and confirmed effect

Create an isolated active Admin with an effective regional grant ending in one hour. Confirm the actor has `region_scopes.assign` but lacks target-account authority over their own account. As that actor, mount the actual Filament EditUserRegionAssignment page for their own grant, fill its end date with a value one year ahead and Save. The form reports no errors and PostgreSQL stores the extended end date.

Retained regression: `PolicyRefinementTest::test_staff_cannot_extend_own_region_authority_through_generic_assignment_editor` — **1 test / 6 assertions / 1 failure**. Its final assertion requires the original effective end instant to remain unchanged. This is actual Filament/Livewire persistence in `osds_test`, not browser verification or an assertion about UI visibility.

### Root cause and boundary

`UserRegionAssignmentPolicy::update` delegates to `view`, checking only the assign permission and access to the grant's current region. The generic EditUserRegionAssignment page inherits direct EditRecord persistence. The form permits effective-date edits. `UserRegionAssignment` checks self/target delegation on creation, but its update hooks only handle revocation metadata/session invalidation. The ordinary edit therefore bypasses `AccessGovernance::assertDelegation`, which prohibits self-delegation and requires target-account authority.

The first unauthorized persisted effect is an extension of the actor's own regional-authority lifetime. No new region, role or credential was assigned in this reproduction. This is separate from SEC-007 credential editing and SEC-006 UTC serialization. Neither production exploitation nor any change to manual-testing grants has been investigated.

### Stop boundary and proposed targeted work

Stop ordinary Batch F refinement. A targeted fix should enforce current actor, original/destination region and target-account delegation before generic grant updates, prevent self-extension/rebinding/revival, protect forged/stale requests and ensure transactional nonmutation on denial. Audit the analogous representation edit pattern as a source lead; it is not independently reproduced here. Preserve existing explicit grant/revoke permissions, effective boundaries, nationwide exceptions, audit/session behavior and UTC contract. Do not introduce role ranks, new permissions, migrations or historical repair without approval.

Only a failing regression and documentation were added after discovery. No SEC-008 fix, wider browser audit, full-suite pass or build completion is claimed after this stop. SEC-007's preceding targeted gates remain passing evidence for that correction: 7 tests / 122 assertions; complete suite 124 / 1,640; editor/applicant browser regressions and account browser checks. The new failing test means the current tree must not be treated as an overall green regression state.

`osds_sandbox` and `osds_dev` were not inspected, reset, reseeded or modified. Existing staged changes remain preserved. Batch F is NOT READY; Batch G/H, Phase 4 and production remain unauthorized.

---

## SEC-007 remediation result — 20 October 2026

**Fixed under targeted authorization; final automated security gates passed.** Profile-only editing retains existing scope rules. Email/password changes require current explicit target-account authority and are blocked for self service. Fresh authorization, transactional service persistence and raw-state checks prevent forged or revoked-authority writes; password values are removed from response state. No historical repair, schema or role/permission expansion.

Original actual Filament regression retained and passing. Focused matrix **7 tests / 122 assertions**; full PostgreSQL **124 / 1,640**; original and 14 expanded editor isolation cases; original and 11 expanded applicant integrity cases; five account browser checks/four light-dark desktop-mobile screens passed. Zero axe/overflow/page-error/credential-response findings in those account screens. Pint/build/working-tree diff passed; existing staged changes and staged whitespace were preserved.

See `docs/phase-3d7-sec-007-remediation.md` for semantics, exact paths, real HTTP revocation/helper checks, safe failure handling and limitations. Human/native zoom/screen-reader checks remain pending. Batch F is still NOT READY but may resume its previously authorized inventory/refinement. Batch G/H and Phase 4 remain excluded. The original stop below is historical.

---

## SEC-007 — Generic account editing bypasses target-account credential authority

**Date:** 20 October 2026. **Severity:** Critical authorization / credential-takeover risk.
**Status:** Confirmed; Batch F stopped before remediation. Separate targeted authorization required.

### Reproduction and first unauthorized effect

In an isolated PostgreSQL fixture, give an Admin and a Super User effective assignments to the same region. Confirm the Admin has `users.manage` but `AccountGovernance::canManageTargetRole($admin, $super)` returns false. Mount the actual Filament `EditUser` page as the Admin, fill its password control and call Save. The form reports no errors. The new disposable credential matches the target's persisted password hash.

Retained regression: `tests/Feature/PolicyRefinementTest.php::test_delegated_admin_cannot_reset_a_super_user_password_through_the_user_editor` — **1 test / 6 assertions / 1 failure**. The assertion requires the proposed credential not to match; actual result is true. No hashes or real credentials are published in this register. This is a real Livewire/Filament save and PostgreSQL assertion, not browser verification. Actual target sign-in was not tested.

### Root cause and boundary

`UserPolicy::update` checks active identity, generic `users.manage` and shared scope, but not target-account management authority. `UserForm` exposes email/password for Edit, and `EditUser` inherits direct Filament record persistence. Other consequential account operations in `AccountGovernance` require the target-specific creation permission. The generic editor bypasses that protection. Same-region visibility is not sufficient authority to replace another account credential.

The confirmed path allows credential replacement for a target outside the configured delegation boundary. No claim of production exploitation, cross-region access, historical changes, or application/submission mutation is made. Broader generic-edit surfaces and target combinations remain unassessed under the stop condition.

### Stop and proposed targeted work

Stop ordinary Batch F work. A separately authorized fix should centralize server-side credential/profile edit authority, cover direct/forged/stale EditUser requests and table actions, preserve effective scope and explicit target permissions, and define self-edit/recovery behavior without inventing role ranks or broadening permissions. Test allowed and forbidden target combinations, changed authority after mount, email/password tampering, persisted nonmutation, session handling and safe audit/error behavior. No schema change or historical repair is proposed; any historical assessment requires separate authorization.

Only a failing test and documentation were added. No runtime remedy, full-suite rerun, Pint/build completion, screenshots or browser acceptance is claimed for Batch F. `osds_test` alone was used; `osds_sandbox` and `osds_dev` were not inspected or changed. Existing staged changes were preserved. See `docs/phase-3d7-batch-f-report.md`. Batch G/H and Phase 4 remain unauthorized.

---

## Authorized E-INT-002 update — 20 October 2026

Targeted remediation is implemented. Native listener instrumentation proves the old question binding changes Question 1 before HTTP dispatch, then the newly bound listener changes Question 2. Stable item and binding-location keys remount the affected controls; PostgreSQL/service validation is unchanged. See `docs/phase-3d7-e-int-002-remediation.md`.

The original reproduction now passes. The 14-case selection matrix, 7-check condition journey and 14-check recovery/authoring journey passed. Their 36 stable screens have zero axe/overflow/page-error findings. The supplemental Duplicate/section-description case and original plus 11 expanded C-INT-001 browser cases passed. E-INT-001 and E-INT-002 automated remediation gates are complete; human/manual acceptance remains pending. Batch E is CONDITIONALLY READY for user review. The full PostgreSQL suite passed 117 tests / 1,518 assertions, and focused FormMaker tests passed 29 / 375. The historical stop below is retained; its pending-authorization and no-remediation statements no longer describe the current code. Original before evidence is `selection-integrity-before.json`, `selection-integrity-before.png` and `selection-integrity-mechanism-before.json`; current `selection-integrity.json` records the passing reproduction.

No historical repair, migration, permission change or manual-testing database access. Batch F and Phase 4 remain unstarted.

---


## E-INT-002 — Editing the selected question also changes another question

**Severity:** Critical authoring data-integrity gate.  
**Status at discovery:** Confirmed in an isolated real browser; Batch E stopped before targeted authorization. Current remediation is described above. No historical repair was applied.
**Discovery:** 19 October 2026, during authorized E-INT-001 browser verification.

### Reproduction and persisted evidence

Run `node scripts/reproduce-builder-selection-integrity.mjs` from the development shell. It creates new synthetic fixtures only in `osds_ui_browser_test`, opens the Form Builder as OSDS and:

1. Confirms Question 1 (`source`) has title **Organization category**.
2. Selects Question 2 (`dependent`) and waits until its editor displays **Conditional information**.
3. Enters **Intended dependent title** in the selected question's title input.
4. Saves, waits for the actual database lock increment, then reloads.

**Expected:** Only Question 2 changes. **Actual:** Both Question 1 and Question 2 persist **Intended dependent title**. Both exact version locks advance from 1 to 2. The condition trees remain intact in this reproduction.

The trace records the selected key as `dependent` and the DOM input binding as `sections.0.questions.1.label` before typing. Immediately after typing, client state already contains the new title on both questions. PostgreSQL inspection and reload confirm the same result. This is not merely a displayed title or premature success assertion.

Original evidence: `docs/screenshots/phase-3d7-e/selection-integrity-before.json` and `selection-integrity-before.png`. At discovery the reproducer exited unsuccessfully and retains the assertion requiring the first question to remain unchanged. The screenshot captures confirmed data after reload; no contrast/animation verification is claimed from it.

### Investigation boundary and proposed targeted work

The focused editor reuses input DOM while its array-index model path changes after selection. Stale or duplicated reactive bindings are an investigation lead, **not an established framework-level root cause**. The trace establishes client-side cross-question mutation before persistence. Document editing, option controls, reordering, other types and other browsers have not been assessed for this defect.

Targeted remediation should establish editor/control identity by stable question/requirement key, inspect listener lifecycle and delayed model updates, and prove that edits affect only the selected item across selection, ordering, navigation, rapid input, save and reload. Preserve condition trees, input recovery, version locks, existing services and published immutability. Do not repair existing configurations without separately approved scope.

**Separate targeted authorization is required under the user's independent-critical-defect stop rule. Batch E is NOT READY; Batch F and Phase 4 remain unstarted.** Neither `osds_sandbox` nor `osds_dev` was inspected, reset, reseeded or modified.

### E-INT-001 remediation progress before this stop

Condition preservation changes are implemented, but browser acceptance is incomplete and they are not claimed accepted. The original stop record below remains historical evidence. Focused final server tests: **23 tests / 264 assertions passed**. Earlier complete PostgreSQL run: **109 tests / 1,388 assertions passed**, before the last two focused tests and final scalar-value protection adjustment. Pint and production build passed before the E-INT-002 stop. The browser completed no-op/reload and unrelated-edit/stage checks for exact condition trees, then exposed E-INT-002. Preview/applicant parity, intentional browser edit/clear and responsive/axe acceptance did not complete. See `docs/phase-3d7-e-int-001-remediation.md` and the updated Batch E report.

## E-INT-001 — Unchanged Form Builder saves discard supported condition semantics

**Current update:** Targeted condition-preservation implementation and server regressions are present; final browser acceptance is blocked by independently confirmed E-INT-002 above. The following discovery/status and proposed-remediation text describe the original stop, before targeted authorization.

**Severity:** Critical authoring data-integrity gate.  
**Status:** Confirmed; **Batch E stopped before implementation**. No remediation or historical repair applied.  
**Discovery:** 19 October 2026, initial audit following Batch D acceptance and Batch E authorization.

### Reproduction and observed impact

In isolated `osds_test` fixtures, create a valid draft documentary requirement with one of the supported `ConditionalLogic` rule shapes, open the Form Builder, and click Save without editing anything. Livewire reports `Saved` and no validation error. Reading the saved requirement from PostgreSQL shows:

| Original rule | Persisted result |
| --- | --- |
| Standalone `question` / `operator` / `value` leaf | `null` — requirement becomes unconditional |
| `all` containing two leaves | Only the first leaf remains |
| `any` containing two leaves | Replaced with `all` containing only the first leaf |

The changes affect actual applicability semantics, not just JSON formatting or key order. For example, the second permitted alternative in `any` stops matching; an `all` exclusion can disappear. This can change applicant visibility and document completeness if the changed draft is subsequently approved and published. No test mutated an existing publication, and no claim is made that any manual-testing or production configuration has been affected.

### Root cause

- `FormBuilder::conditionFields()` reads only `all[0]` or `any[0]`, ignoring standalone leaves and additional/nested conditions.
- `loadWorkingState()` keeps the original rule but derives incomplete single-condition editor fields.
- `save()` always calls `compileConditions()`.
- `compiledCondition()` treats an empty editor source as unconditional; otherwise it creates a new one-leaf `all` group.
- `QuestionnaireAuthoringService` validates and transactionally persists that now-changed state. Its locking and draft-only checks do not detect the semantic loss introduced before persistence.

The shared `ConditionalLogic` validator/evaluator already supports standalone leaves and `all`/`any` groups. This is an authoring round-trip defect, not authorization to change the supported schema or invent new condition semantics. The same loader/compiler functions handle questions and documentary requirements; persisted reproduction currently covers documentary requirements. Question and nested-rule cases require targeted expansion.

### Retained regression and verification

`tests/Feature/FormMakerTest.php::test_noop_builder_save_preserves_supported_document_conditions`, using `existingConditionShapes`:

- **3 PostgreSQL/Livewire cases / 9 assertions / 3 failures**.
- Each case confirms that the component reports `Saved` without field errors, then asserts exact preservation of the persisted supported rule.
- Fixtures and transactional test lifecycle use only `osds_test`.
- No runtime code, schema, permissions, UTC contract, ordering adapter, publication service, historical record or manual-testing data was changed.
- The new failing tests remain in the suite. No full-suite pass, browser acceptance, Pint/build completion, or Batch E completion is claimed after the stop.

### Proposed targeted remediation — not applied

1. Preserve the original complete condition tree on an unchanged save; do not regenerate it from a lossy editor projection.
2. Distinguish explicit user changes/clearing from untouched conditions using trustworthy authoring state.
3. For supported trees the simple editor cannot represent, provide a clear protected/read-only explanation or a faithful editor; never silently simplify them.
4. Expand question and requirement tests for leaf, grouped, nested, no-op save/reload, unrelated edits, explicit clearing, invalid references and preview/applicant parity. Compare behavior as well as persisted structure.
5. Preserve stable keys, optimistic locks, service validation, exact approval pairing, published immutability and SEC-001–006/C-INT-001.
6. Rerun focused PostgreSQL/Livewire tests and security/publication regressions before resuming the remaining Batch E audit and browser workflow.

No migration, condition-policy change or existing-data repair is proposed. Any assessment or repair of existing saved configurations would require a separately defined scope. **Separate targeted remediation authorization is required under Batch E's data-integrity stop condition. Batch F and Phase 4 remain unstarted.**

## SEC-006 — PHP/PostgreSQL time-zone mismatch defeats real assignment-expiry boundaries

### Authorized remediation result

**Current status: Fixed under targeted authorization; historical timestamp integrity remains unverified.** The initial stop record below is retained as historical evidence.

`config/database.php` now sets the supported PostgreSQL application-session `timezone` to `UTC`. Laravel's connector applies it at connection creation/reconnect. No host/server setting, column type, grant semantics, domain query, historical row, permission or lifecycle transition was changed. This aligns offset-free PHP UTC bindings with PostgreSQL comparisons/new writes.

- Original regressions: **2 tests / 4 assertions passed**.
- Expanded timestamp/schema/non-UTC-startup/reuse/reconnect/expiry matrix: **7 tests / 76 assertions passed**.
- Final complete PostgreSQL suite: **98 tests / 1,190 assertions passed**, retaining SEC-001–005 and existing immutable-history protections.
- Final CHEDRO browser journey: **12 interactions / 30 stable light/dark desktop/tablet/mobile screen checks passed**, no reported axe/overflow/uncaught-error findings. Explicit-offset assignments expired one minute ago are database-confirmed and denied in queues and direct routes. Correction/resubmission and response-loss recovery remain verified.
- Final browser fixture: session `UTC`, measured binding/database-clock skew **0.078041 seconds**, new finding **97 seconds after** its reviewed copy. Previous skew was approximately 28,800 seconds.
- Original C-INT-001 browser reproduction and all **11 expanded browser cases** passed. Pint, production build and diff check passed.

Information-schema checks distinguish `timestamptz` authorization/workflow fields from older timestamp-without-zone metadata. A SELECT-only isolated legacy simulation proves shifted offset-free writes can coexist with correct database-default/explicit-offset instants. **No blanket correction or historical integrity claim is justified.** Manual/production records were neither inspected nor repaired. Any historical repair remains a separate approval boundary.

Exact affected date-sensitive paths, worker/reconnect/deployment limits, column findings and assessment: `docs/phase-3d7-sec-006-remediation.md`. Current UI/browser handoff: `docs/phase-3d7-batch-d-report.md`. Batch E and Phase 4 remain unstarted; pause for Batch D review.

### Initial stop record — historical

**Severity:** Critical authorization and timestamp-integrity defect.  
**Status at discovery:** Confirmed; Batch D stopped before targeted authorization. The remediation result above supersedes this initial status; no stored-data repair was performed.  
**Discovery:** 19 October 2026, during final Batch D screenshot inspection after SEC-005 remediation.

### Root cause and evidence

The application uses UTC (`config/app.php`). The tested PostgreSQL connection inherits `Asia/Kuala_Lumpur` (UTC+08), and `config/database.php` specifies no PostgreSQL session time zone. Laravel prepares PHP datetime bindings as offset-free datetime strings. PostgreSQL interprets these strings in its own session zone when comparing or storing `timestamptz` values.

A read-only `osds_test` probe compares a normal prepared `now()` binding to PostgreSQL `CURRENT_TIMESTAMP`. The discrepancy is **28,800 seconds**. Screenshot inspection first exposed the inconsistency: a recently recorded finding displays about eight hours earlier than the test copy it reviews. This is not resolved by adding another display-time conversion.

An independent PostgreSQL authorization regression then:

1. Creates an active synthetic reviewer with permission and matching regional scope.
2. Inserts exact region/module assignments for both workflows using **explicit-offset instants**, with end times one minute in the past.
3. Confirms in PostgreSQL that each assignment satisfies `ends_at <= CURRENT_TIMESTAMP`.
4. Applies the unchanged `ReviewerQueueScope` to actual isolated submitted-application and evidence-review records.
5. Expects zero authorized rows in both queues, but obtains **one row from each queue**.

The scope guard still matches exact region/module pairs and fails closed for empty authorized pairs. The defect is that its PHP-bound effective-time cutoff can classify a genuinely expired assignment as effective. Other date-controlled paths use similar bindings; their precise affected surface has **not** been exhaustively assessed because the critical stop condition applies. This evidence does not establish production exposure or modification of manual-testing records.

### Tests and limitations of earlier green results

- `TimestampCoherenceTest::test_php_datetime_bindings_agree_with_postgresql_current_time`: **1 test / 1 assertion / 1 failure**; observed UTC+08 session and approximately eight-hour skew.
- `ApplicantSandboxTest::test_database_expired_reviewer_assignments_never_authorize_queue_rows`: **1 test / 3 assertions / 1 failure**; expected `[0, 0]`, actual `[1, 1]` after database-confirmed expiry.
- Tests used only `osds_test`. Browser observation used only `osds_ui_browser_test`.
- Earlier expiry fixtures used the same PHP timestamp serialization for setup and comparisons, often with day-wide boundaries. They did not prove agreement with the real database clock and did not catch this boundary failure.
- The earlier complete suite passed **91 tests / 1,114 assertions**, and the ordinary CHEDRO browser journey passed 11 interactions / 30 screen checks. Those results remain historical evidence of the cases exercised; **they do not override SEC-006**. The new failing regressions remain in the suite.

### Proposed targeted remediation — not implemented

1. Establish one explicit UTC instant contract for every PostgreSQL application connection, worker and test connection; verify prepared datetime bindings against database time.
2. Audit authorization, representation, eligibility, publication, assignment and other date-bound queries against real past/future boundary instants, including non-UTC server defaults.
3. Add server-clock/explicit-offset regressions and positive/negative queue plus individual-case checks for both workflows.
4. Assess existing immutable timestamps separately. Do not rewrite submissions, evidence revisions, assignments, audit history or manual-testing data as part of a configuration fix.
5. Rerun SEC-001–005, the complete PostgreSQL suite, and relevant browser expiry checks before resuming Batch D acceptance.

No configuration repair, migration, database-time-zone change, stored timestamp correction, production policy change, or manual-data inspection/repair has been performed. **Separate targeted authorization is required before remediation. Batch E and Phase 4 remain unstarted.**

## SEC-005 — Direct Livewire helper returns bypass the intake answer permission

### Authorized remediation result

The initial stop/reproduction below is retained as historical evidence. Targeted authorization was supplied in the subsequent SEC-005 approval. Current/historical intake and evidence presentation helpers are now request-local computed properties; raw query helpers are protected. Forged computed calls are rejected by the installed framework, verified through real Livewire/HTTP payload tests. Answer access is permission-gated **before** relationship queries; only applicable answers load. Evidence opening and finding history retain their separate permissions. No aggregate is placed in public component state.

Original regression: **1 test / 12 assertions passed**. Expanded matrix: **1 test / 87 assertions passed**. Complete PostgreSQL suite: **91 tests / 1,112 assertions passed**. Pint, Vite build and diff check passed. Current/historical positive access, denied helper returns, snapshots, later responses, permission/assignment revocation, hidden answers and unchanged submitted hashes/history are covered. Existing ineffective-assignment/exact-pair tests remain green. Manual-testing databases were untouched.

See `docs/phase-3d7-sec-005-remediation.md` for files/surfaces and HTTP rejection details. **SEC-005 security gates passed; ordinary Batch D work may resume under the targeted authorization. Batch D browser acceptance is still pending.**

**Severity:** Critical authorization / information disclosure.  
**Status:** Fixed under targeted authorization; original/expanded Livewire response checks and complete PostgreSQL regression passed. Batch D browser acceptance remains separate.  
**Discovery:** 19 October 2026, during the authorized Batch D audit and implementation.

### Reproduction and confirmed boundary

1. Create an active synthetic reviewer with `platform.admin.access` and `applications.intake.view`, an effective exact region/module application-review assignment, and matching regional scope.
2. Do **not** grant `applications.intake.answers.view`.
3. Open an assigned submitted application. The ordinary page correctly omits the questionnaire-answer section.
4. Invoke the component's public `selected` method as a Livewire action.
5. The action's `effects.returns[0]` serializes the loaded application, including `current_submission.answers` and their values despite the missing answer permission.

The installed Livewire action handler puts public-method return values into the JSON response. A public presentation helper is therefore an action surface, not merely a template implementation detail. `SyntheticApplicationIntake::selected()` authorizes the general intake-view permission and loads submitted answers; the answer-view permission is checked only in Blade. This behavior existed in the source inspected at the beginning of Batch D. Batch D moved general authorization before relation loading, which does not resolve this separate permission bypass.

The partially implemented `submittedCopy()` helper is also public and returns answers after general intake authorization. It must be included in targeted remediation before historical-copy presentation can be accepted. Neither helper should expose raw Eloquent aggregates through action returns. The scope of this reproduction is an assigned application with a missing answer-view permission; it does not establish cross-region access, decrypted locator disclosure, or modification of immutable submissions.

### Regression evidence

`tests/Feature/ApplicantSandboxTest.php::test_intake_livewire_helper_does_not_return_answers_without_answer_permission`:

- Confirms the reviewer lacks the answer-view permission.
- Confirms the initial rendered page excludes a synthetic answer marker.
- Calls `selected` through the actual Livewire testing transport.
- Asserts that the serialized return excludes the marker: **fails** because the response contains it.
- PostgreSQL result: **1 test, 3 assertions, 1 failure**. This is a deliberately retained failing security regression, not a passed gate.

Only `osds_test` fixtures were used for this reproduction. No manual-testing data was read or changed. Do not publish the raw diagnostic response; it contains synthetic fixture records and internal identifiers. A concise Batch D handoff is in `docs/phase-3d7-batch-d-report.md`.

### Proposed targeted remediation (not applied)

1. Remove presentation-only helper methods from the callable Livewire action surface, using the installed framework's supported computed/private presentation mechanism.
2. Enforce `applications.intake.answers.view` before loading or returning answer data for current **and** historical copies; UI visibility alone is insufficient.
3. Inspect public helpers in both CHEDRO pages for serialized aggregates and closely related disclosure paths. Preserve Phase 3C's separate assignments and no-general-answers boundary.
4. Add direct forged-action tests, permission revocation between page load and call, hidden/inapplicable-answer coverage, and authorized-answer positive tests.
5. Rerun SEC-001–004, queue pagination/filter tests and the complete PostgreSQL suite before resuming browser acceptance.

No domain-policy change, new permission, destructive migration, or manual-data repair is proposed. **Stop remains in force pending targeted remediation authorization under the Batch D critical-defect stop condition.**

**Date:** 19 October 2026 (Asia/Manila)  
**Scope of this update:** SEC-001–004 targeted security fixes and the resumed Batch A source/route inventory. Automated security verification passed; browser/manual verification remains pending. Batches B–H are not complete.

## SEC-001 — Empty reviewer assignments removed queue restrictions

**Severity:** Critical authorization boundary defect.  
**Status:** Fixed; automated PostgreSQL verification passed; manual browser retest pending. Provisionally accepted by the user on 19 October 2026.

### Reproduction and impact

1. Sign in with an active CHEDRO account holding the relevant queue-view permission.
2. Give the account no effective reviewer assignments, or allow all assignments to expire or be revoked.
3. Open Application intake or Assigned test reviews.

Previously, both list builders placed all region/module predicates inside a loop in a nested `where` closure. With no authorized pairs, the closure added no predicates. Laravel's `addNestedWhereQuery` discards an empty nested predicate, so the remaining query listed records without assignment restrictions. Organization names, modules, statuses, and case existence could be disclosed. Existing case-detail and evidence-opening services separately checked authority; this finding does not establish that those services allowed unauthorized content access.

### Fix and files changed

- `app/Services/Authorization/ReviewerQueueScope.php`: central server-side query guard. Rejects absent, inactive, nonsynthetic, or permissionless identities and disallowed sandbox environments with `WHERE 1 = 0`. Effective assignments must also pass `ScopeAuthorizer`. Empty authorized pair sets explicitly return `WHERE 1 = 0`; nonempty sets use grouped exact region AND module predicates.
- `app/Filament/Pages/SyntheticApplicationIntake.php`: application queue uses the guard with Phase 3D application assignments only.
- `app/Filament/Pages/SyntheticEvidenceReviews.php`: evidence queue uses the guard with Phase 3C evidence assignments only.
- `tests/Feature/ApplicantSandboxTest.php`: queue regression matrix, exact-pair PostgreSQL grid, rendered-page disclosure checks, counts, filtered queries, pagination totals, and direct-route checks.

Existing detail authorization, permissions, individual evidence membership, domain workflows, and database protections were preserved. No migration, permission change, or data correction was required.

### Coverage

Both queues are exercised with no assignments; expired, revoked, and future assignments; wrong region/module; missing regional scope; missing permission; inactive/nonsynthetic identity; production environment; the opposite workflow's assignment; and valid authority. A PostgreSQL-derived four-combination region/module grid proves that two assigned pairs do not authorize their cross combinations.

The focused tests verify zero counts, empty query results, filtered pagination totals, queue method results, and rendered-page non-disclosure. Unauthorized application details retain 404 behavior; evidence-review detail routes retain their existing 403 behavior. Authorized queues still display their assigned organization.

These custom queues currently have no implemented export, search/filter controls, or pagination UI. Query composition tests exercise filtering, counting, and pagination on the guarded builder; HTTP tests confirm unrelated URL parameters do not expose unauthorized rows. The existing intake badge calls the same guarded queue method. This fix adds no such UI features.

### Verification and data preservation

- Targeted authorization tests: 2 tests, 136 assertions passed.
- Full PostgreSQL suite: 75 tests, 450 assertions passed.
- Laravel Pint: passed.
- Vite production build: passed.
- Git diff check: passed, with pre-existing line-ending advisories.
- Automated fixtures and transactions used the `osds_test` database configured by PHPUnit. No command targeted `osds_sandbox` or `osds_dev`; no manual-testing records were reset, reseeded, or modified.
- Browser visual checks were not performed for this server-side change. Rendered-route checks are HTTP tests, not browser verification.

### Targeted manual retest

1. As an assigned synthetic CHEDRO reviewer, verify the existing assigned cases remain visible in both queues.
2. In disposable test fixtures, revoke the workflow-specific assignment and refresh its queue: expect the existing empty state and no organization/service disclosure.
3. Confirm the other workflow still requires its separate assignment.
4. Verify a copied unauthorized case-detail URL remains denied.

## SEC-002 — Applicant tracking bypasses representation revocation

**Severity:** Critical authorization boundary defect under the approved requirement to revoke applicant access immediately when organization representation ends.  
**Status:** Fixed under targeted authorization; PostgreSQL regression verification passed; manual browser retest pending.

### Source evidence and impact

- `ApplicantPortalController::home` excludes applications without a current verified, unrevoked, effective organization representation.
- `ApplicantPortalController::show` calls the sandbox/identity guard and checks application ownership plus `applications.track.own`, but does not check current organization representation.
- The registered `/applicant/applications/{application:public_id}` route adds authentication and active-account middleware only; it has no representation policy middleware.
- `resources/views/applicant/track.blade.php` renders organization identity, application state, status history, and applicant-visible correction instructions.

An otherwise active synthetic applicant whose representation has expired or been revoked can therefore retain tracking visibility through a previously saved application URL, even though the application no longer appears in the authorized list. Ownership alone does not satisfy the approved current-representation boundary. This finding does not establish access to another applicant's application, evidence locators, or an ability to edit or resubmit.

### Implemented targeted resolution

`ApplicationPolicy::view` now requires ownership, active synthetic identity, explicit portal/tracking permissions, strict sandbox availability, an active synthetic organization, the applicant publication channel, effective module eligibility, and current representation for the exact organization. The controller checks the policy before loading tracking history or correction details and returns 404 on resource authorization denial. Existing portal/middleware identity denials retain their safe 403 response. Publication retirement does not itself remove authorized historical read access.

`ScopeAuthorizer::hasEffectiveRepresentation` extracts the existing representation predicate without changing its meaning. `canAccessInstitution` reuses that predicate and retains its existing staff regional/nationwide alternatives. The applicant policy calls the representation predicate directly so staff scope cannot substitute for representation.

Files changed: `app/Policies/ApplicationPolicy.php`, `app/Services/Authorization/ScopeAuthorizer.php`, `app/Http/Controllers/ApplicantPortalController.php`, `tests/Feature/ApplicantSandboxTest.php`, and this register. No migrations or permission changes were made.

### Verification boundary

PostgreSQL tests cover valid access; revoked, expired, future-dated, unverified, missing, and wrong-organization representation; another owner; missing tracking permission; nonsynthetic/inactive identity; production denial; repeat HTTP and JSON requests after revocation; correction-instruction non-disclosure; and preservation of submission rows, ownership, state history, and audit counts. Existing retired-manifest tracking tests remain in the full regression gate.

Tracking is a server-rendered GET page with no separate tracking Livewire component or JSON controller. Each HTTP/JSON request traverses the policy again. The implementation does not claim to erase previously delivered content from a browser.

The focused applicant suite passed (22 tests, 299 assertions before the final added edge checks); the final tracking-edge test passed with 34 assertions. The final full PostgreSQL suite passed: **76 tests, 499 assertions**. Pint, production build, and diff checks passed. Tests target isolated `osds_test`; neither manual-testing database was reset, reseeded, or modified. Browser verification remains pending.

The related draft read audit found SEC-003 below. Batch A remains incomplete and Batch B has not started.

## SEC-003 — Draft Livewire read requests lack ongoing representation authorization

**Severity:** Critical authorization boundary defect.  
**Status:** Fixed under targeted authorization; automated PostgreSQL verification passed; manual browser retest pending.

`DraftEditor::mount` authorizes draft access on the initial request, but no `hydrate`/per-request authorization hook is present. `checkProgress` reads fresh saved answers and document completeness without calling the draft policy; `goToStep`, rendering, and evidence-list helpers also lack an ongoing read authorization check. Livewire update requests do not rerun `mount`.

Consequently, a previously authorized browser session can request further protected draft read responses after representation becomes ineffective, especially on time-based expiry, which does not invoke session invalidation. Existing write services separately authorize mutations; this finding does not establish successful unauthorized writes. Initial draft GET denial and write authorization do not protect subsequent component read requests.

### Implemented boundary

`DraftEditor` now authorizes on mount, `hydrate`, and rendering. The installed Livewire 4 `SupportLifecycleHooks::hydrate` executes before property updates and action dispatch; the component's authorization failure prevents rendering/dehydration of protected values. Each check resolves the current user from the database, then uses the draft policy. Signed/model hydration may resolve the draft header needed for authorization; answers and evidence relationships are not explicitly loaded until authorization succeeds. Previously delivered browser state cannot be retroactively erased.

`ApplicationDraftPolicy::view` now combines strict sandbox availability, active synthetic identity, explicit portal/draft-read permissions, draft ownership, the canonical `ScopeAuthorizer::hasEffectiveRepresentation` predicate, an active synthetic organization, applicant publication channel, exact application/organization/module/publication binding, and effective organization/module eligibility. It does not use staff regional or nationwide grants as a substitute for representation. Retired/consumed drafts remain readable to otherwise authorized owners; existing services continue to enforce mutation/lifecycle restrictions.

### Affected workflow and files

The applicant questionnaire, documents, review, and correction editor are stages of the same `DraftEditor` component. Tracking remains a controller-rendered page covered by SEC-002. No additional nested applicant Livewire components or separate tracking update endpoint were found. Field components are Blade components rendered within the editor.

Files changed: `app/Livewire/Applicant/DraftEditor.php`, `app/Policies/ApplicationDraftPolicy.php`, `tests/Feature/ApplicantSandboxTest.php`, and this register. No migrations, permission grants, or domain-service contracts changed.

### Regression and verification

- Targeted Livewire tests: **2 tests, 145 assertions passed**.
- Complete PostgreSQL suite: **78 tests, 644 assertions passed**.
- Coverage includes revoked, expired, future, unverified, missing, and wrong-organization representation on initial entry and subsequent hydration; missing portal/read permission; inactive/nonsynthetic identity; ineffective eligibility; and production denial.
- After initial authorized mount, revoked/expired representation blocks refresh, section changes, progress checking, evidence reads, status reads, saves, evidence mutations, review-copy creation, and submission. Denied Livewire responses expose no protected answer marker, evidence public ID, or organization name.
- Denied requests leave drafts, answers, applications, submissions, evidence references/revisions, and audit rows unchanged. Existing authorized flows, correction/resubmission, retirement read-only behavior, and immutability tests pass.
- Laravel Pint, Vite build, and git diff checks passed (existing line-ending advisories only).
- Tests used isolated `osds_test`; no commands reset, reseeded, or modified `osds_sandbox` or `osds_dev`.
- Browser/manual retest remains pending. These are actual Livewire request-lifecycle and HTTP tests, not visual browser checks.

## SEC-004 — Staff list queries ignore regional grant effective dates

**Severity:** Critical authorization/scope boundary defect.  
**Status:** Fixed under targeted authorization; automated PostgreSQL verification passed; manual/browser retest pending.

During resumed inventory, resource list queries were found to use regional grants with only `revoked_at IS NULL`, omitting `starts_at <= now` and `ends_at > now` checks required by `ScopeAuthorizer::canAccessRegion`:

- `Regions/RegionResource::getEloquentQuery`: lists regions using unrevoked user assignments regardless of dates.
- `Institutions/InstitutionResource::getEloquentQuery`: builds region IDs using unrevoked assignments regardless of dates; the representation alternative also omits verified/effective-date checks.
- `InstitutionRepresentatives/InstitutionRepresentativeResource::getEloquentQuery` and `UserRegionAssignments/UserRegionAssignmentResource::getEloquentQuery`: use unrevoked region ID sets without effective dates.
- `AuditEvents/AuditEventResource::getEloquentQuery`: uses the same undated regional set to list regional audit metadata.

Resource admission uses the applicable list permission; individual record policies and mutation services do not themselves constrain these list queries. The source therefore allows regional list metadata to remain visible to a permission-bearing staff identity after a regional grant expires, or before it starts. The finding does not establish unauthorized mutations. Nationwide-grant behavior and other related list paths need explicit verification during the targeted fix.

Proposed resolution: centralize effective staff regional list constraints using existing `ScopeAuthorizer` semantics, preserve explicit permissions and valid nationwide access, and apply a separate verified/effective representation predicate only where already authorized. Cover expired/future/revoked/absent grants, empty lists/counts, search/filter/pagination, direct records, and valid staff access with isolated PostgreSQL tests. Do not conflate these staff scope rules with the SEC-001 reviewer assignment pairs.

### Implemented authorization boundary

`StaffListScope` applies explicit resource permissions and active identity before applying the canonical effective regional scope. `ScopeAuthorizer` exposes effective region and represented-organization ID sets using the existing `starts_at <= now`, `ends_at IS NULL OR ends_at > now`, and `revoked_at IS NULL` semantics. No date precision/timezone or inclusive-end rule was changed. Nationwide access requires an effective explicit nationwide grant; neither Admin nor Super User role names bypass scope.

The six staff resource queries now use this boundary: Regions, Institutions, Institution representatives, Regional assignments, Audit history, and Users. Users already checked effective dates, but now share the guard with related selector queries. Institution access retains its existing verified/effective representation alternative. Audit history retains regional access, institution-only events allowed by existing institution scope, own events with no region/organization, and the existing national-audit permission plus nationwide-grant rule. Scoped users can still see their own user row under the existing UserPolicy exception. These exceptions are policy-backed and do not authorize unrelated regional records.

The region selector in organization forms and the user/organization/region selectors in representation and regional-grant forms use the same guard. User account creation selectors were already filtered through `ScopeAuthorizer`; reviewer queues retain their separate exact-pair `ReviewerQueueScope`. No resource relation managers, bulk actions, or exports are implemented in the affected resources; future query extensions must start from the guarded resource builder.

### Files changed

- `app/Services/Authorization/StaffListScope.php` (new) and `ScopeAuthorizer.php`.
- Resource query methods in `Regions/RegionResource.php`, `Institutions/InstitutionResource.php`, `InstitutionRepresentatives/InstitutionRepresentativeResource.php`, `UserRegionAssignments/UserRegionAssignmentResource.php`, `AuditEvents/AuditEventResource.php`, and `Users/UserResource.php`.
- Selector schemas: `Institutions/Schemas/InstitutionForm.php`, `InstitutionRepresentatives/Schemas/InstitutionRepresentativeForm.php`, and `UserRegionAssignments/Schemas/UserRegionAssignmentForm.php`.
- `tests/Feature/StaffListScopeTest.php` (new) and this register.

### Verification

- Focused PostgreSQL authorization suite: **5 tests, 300 assertions passed**.
- Complete PostgreSQL suite: **83 tests, 944 assertions passed**.
- Actual resource builders, HTTP lists/detail routes, Livewire table search and refresh after expiry, and Filament relationship field options are exercised. Counts, filtered results, and pagination totals exclude unauthorized fixture rows.
- Coverage includes no grant, just expired, future, revoked, start/end boundaries, wrong region, inactive identity, absent permission, valid union of regions, mixed valid/expired grants, revoked overlapping history, valid/invalid representation, and effective/expired nationwide access. The schema prohibits two simultaneously unrevoked grants for the same user/region; the overlap test uses a revoked historical grant plus a valid current grant rather than bypassing that constraint.
- Grant tables have no separate active flag. Ineffectiveness is expressed by dates/revocation; the tests also cover inactive identities. Region catalogue activity is not reinterpreted as grant validity.
- Existing direct-record policies and consequence-specific domain authorization remain in place. Audit history was not rewritten or deleted.
- Pint, production build, and diff checks passed; existing line-ending advisories remain.
- Tests used only isolated `osds_test`. No manual-testing database records were reset, reseeded, or modified. No migration was added.
- Browser/manual testing remains pending. HTTP, Livewire, and field-options checks are automated server-side verification.

## Remaining Batch A refinement register

The following are source findings or explicitly identified verification tasks, not claims of browser-observed failures. Security fixes SEC-001–004 are automated-green with browser/manual retest pending.

| ID | Priority | Affected screens | Evidence/root cause | Planned resolution / batch |
| --- | --- | --- | --- | --- |
| UX-001 | High | Applicant and intake primary buttons | White action text uses blue tokens that become pale in dark mode. Source-pair contrast is 2.13:1 for white/#78b7f2 and 2.77:1 for white/#5ba0e3. These calculations are not rendered-browser measurements. | Introduce separate action foreground/background token pairs; measure computed normal/hover/focus/disabled states. B |
| UX-002 | High | Applicant tracking/account/home; Phase 3C evidence reviews | Generic `.portal-card` uses flex layout for heterogeneous headings, content, and forms; the Phase 3C page uses applicant class names not defined equivalently in the Filament theme. | Shared stack/card/form patterns; responsive grouping and state feedback. B–D |
| UX-003 | High | CHEDRO intake answers | `displayAnswerValue` title-cases arbitrary free text and replaces underscores instead of resolving only configured choice keys. | Preserve text verbatim; resolve labels using the immutable question/options. D |
| ERR-001 | High | Admin publication actions | `catch (Throwable)` displays raw exception messages in notifications. Shared exception bootstrap has no application-specific safe support-reference renderer. | Safe error mapping and redacted diagnostics; no raw database/exception content in UI. F/G |
| A11Y-001 | High | Shared questionnaire controls | Input IDs exist, but text/number/date controls lack explicit label/legend association and error/help bindings. No radio group names are declared in the shared template. | Stable field/group names and accessible descriptions/errors; verify browser keyboard behavior and shared preview parity. B/C/E |
| UX-004 | Medium | Applicant navigation | Home and My applications target the same route; active location and correction-required state need clearer task language. | Clarify navigation/current page and next actions without new domain states. C |
| UX-005 | Medium | Applicant start | Module options use the union of represented organizations' grants; the UI does not narrow options when the selected organization changes. Domain start remains authorized and rejects mismatches. | Filter displayed options for the explicitly selected organization and explain unavailable options. C |
| UX-006 | Medium | Applicant account | Verified status ignores grant start/end dates in the display expression; no empty state. Account page includes historical representations without explaining their state. | Display verified/effective/expired/revoked accurately; show a safe empty state. C |
| UX-007 | Medium | Published questionnaires | Table omits publication channel; staff-preview action appears on applicant manifests and points to a controller that accepts review_sandbox only. | Label audiences and show only supported actions; preserve channel separation. F |
| UX-008 | Medium | Questionnaire overview | Overview metrics use the first draft pair even when several candidates exist; builder itself requires explicit pairing. | Display a selection prompt rather than implying one working pair. E |
| PERF-001 | Medium | CHEDRO queues | Queues use unbounded `get`; intake executes its queue method for both count and listing. Evidence template queries institutions/modules within the loop. | Measure fixed synthetic workloads, paginate guarded builders and remove repeated lookup work. D/G |
| PERF-002 | Medium | Staff selectors and publication table | User creation fetches all regions/organizations then checks per item; publication status/actions repeatedly query retirement. | Measure queries and use scoped/eager-loading improvements while preserving permissions. F/G |
| UX-009 | Medium | Public home and staff sandbox preview | Hardcoded light-only surface/text utility colors and no theme initialization shared with applicant shell. | Apply shared theme shell/tokens and consistent navigation. B/F |
| ERR-002 | Medium | Forms and consequential actions | Error handling and pending/duplicate-click feedback differ by screen; Phase 3C findings have no inline service-error presentation. | Standard error summaries, field mapping, pending states, and safe retries after confirmed domain results. C–G |
| VERIFY-001 | High release gate | All screens | No page-wide browser/theme/contrast verification has been performed during Batch A. No Playwright/axe dependencies currently configured. | Isolated browser database, browser tooling, safe screenshots, computed contrast and keyboard/zoom/reflow checks before final readiness. B/H |

### Batch A handoff

See `docs/phase-3d7-page-inventory.md` for registered screens, role coverage, shared interaction states, and source-only verification limits. This is a completed source/route inventory with security regression evidence, not completed browser/accessibility or human usability testing. No new unresolved critical finding was confirmed after SEC-004 during this source inventory. Batch B has not been implemented.

## Batch B follow-up — Shared theme foundation

The preceding Batch A handoff is historical. Batch B implements semantic action/text pairs, input borders, placeholders, disabled and feedback colors, shared typography and spacing, responsive progress, shared Blade theme initialization, and scoped Filament adapters. See `docs/phase-3d7-batch-b-report.md` for measurements and screenshot evidence.

| Issue | Batch B disposition |
| --- | --- |
| UX-001 | Corrected shared action pairs. Rendered applicant action text measures 6.57:1 light and 8.72:1 dark; hover, focus and disabled styling are also measured. |
| UX-002 | Shared stacked card/form primitives now exist in both stylesheet entry points. Representative applicant and CHEDRO screens are browser-inspected; role-specific flow improvements remain in C/D. |
| A11Y-001 | Shared question controls now associate accessible names and configured help, and radio groups have names. Representative renderer pages are checked with axe. Full screen-reader/error-recovery assessment remains pending. |
| UX-009 | Public home and staff preview use semantic surfaces and shared theme controls. Public home is browser-inspected; the staff preview changes are source-inspected only. |
| VERIFY-001 | Local Playwright/axe tooling and a dedicated synthetic browser database are established. Representative screen coverage is recorded in the Batch B report; this does not establish verification of every inventory route or human usability. |

SEC-001 through SEC-004 remain protected by the complete PostgreSQL regression suite (**83 tests, 944 assertions passed**). Targeted human/browser security retesting remains pending; representative visual page visits do not replace the security retest matrix. No security policy, lifecycle, permission, schema, encryption, or manual-testing record was changed by Batch B. Other Batch A findings remain open for their assigned batches.

## Batch C follow-up — SCHOOL journey and recovery

**Batch C status: C-INT-001 remediated; CONDITIONALLY READY for user review.** The initial critical stop and its evidence are retained below. Final post-remediation gates passed; no Batch D work is authorized by this result.

See `docs/phase-3d7-batch-c-report.md` for actual interaction results, screenshot coverage and limits. The preceding Batch B counts remain historical.

| Issue | Batch C disposition |
| --- | --- |
| UX-004 | Removed duplicate Home/My applications destinations. Mobile navigation is collapsed behind a keyboard-accessible Menu; current-step summary and All steps are separate from portal navigation. Collapsed header measures 76px at 375px width in both themes. |
| UX-005 | Start choices are filtered using the selected represented organization's effective module grants. Backend start authorization remains authoritative and unchanged. |
| UX-006 | Account labels now evaluate start/end dates, verification and revocation; an empty state explains how to obtain assistance. |
| ERR-002, applicant subset | Safe inline validation/failure/recovery messages, true confirmed-save feedback, explicit same-operation retry and session-redirect recovery. Staff/CHEDRO variants remain assigned to later batches. |
| A11Y-001, applicant subset | Keyboard menu, Escape/focus return, skip link, named application-step navigation and shared renderer labels retained. Screen-reader assessment remains pending. |

### C-REL-001 — Unconfirmed save/evidence request recovery

**Severity:** High usability/reliability defect; fixed. The previous component generated answer/evidence idempotency keys inside each action, so a response-loss retry could not reuse the original key and met a stale-version conflict after a successful first write. Existing service transactions/locks protected stored data. Locked component key state now provides stable same-payload/version retry keys until a confirmed response. No database/schema/idempotency service change was required. Browser tests drop a save response after persistence and prove one version increment; an uncertain submitted-copy retry proves one submission. PostgreSQL tests cover answer and evidence replay with one effect.

### C-REL-002 — Session redirect and missing local recovery

**Severity:** High usability/reliability defect; fixed. Actual cookie-loss testing showed that this installation redirects unauthenticated Livewire requests to sign-in. A 419-only handler missed that path. The applicant component now intercepts sign-in redirects and 419 responses, preserves the open tab, and offers explicit RAM-only answer-copy recovery. It does not reauthenticate/replay automatically, store answers in local/session storage, or return unauthorized server data. Server 403/404 responses block/hide the editing workspace. Human shared-device and screen-reader usability remain unverified.

### C-REL-003 — Save feedback before rendered state synchronization

**Severity:** Medium; fixed. Browser tests showed local feedback could remain stale when handled at the request-finish boundary. Reconciliation now occurs after Livewire applies and morphs the response. Dirty input and active/unconfirmed operations suppress the prior Saved badge. Actual aborted-network, committed-lost-response, stale-tab and session-loss probes exercise the feedback path.

Complete PostgreSQL regression after these applicant changes: **87 tests, 982 assertions passed**, including SEC-001–004. Browser tests also deny tracking routes after expired/revoked representation in disposable fixtures. This does not mark all previously planned human security retests complete. No manual-testing database records, permissions, scope rules, evidence cipher, immutable submission guard or correction lineage were changed. Batch D and Phase 4 remain unstarted.

## C-INT-001 — Rapid conditional answer changes can persist an unintended prior value

**Severity:** Critical data-integrity gate.  
**Status:** Fixed following targeted remediation authorization. Original and expanded browser regressions and the full PostgreSQL suite passed; Batch C human/manual review remains pending.

### Original reproduction and observed result (before remediation)

1. Use the isolated browser fixture's required Yes/No question controlling Activity details.
2. Select Yes, enter the conditional detail, select No, then select Yes again without extra pauses.
3. Visit Contact details and return. Save draft and reload.
4. The observed final answer and selected radio are No, despite the final intentional choice being Yes. A comparison run with pauses between changes retains Yes throughout save/reload.

The trace records first Yes, No, second Yes requested, return to step, and the persisted/reloaded value. The current failure already appears immediately after the second Yes interaction, before saving; persistence then faithfully stores the unintended No. This is not merely a screenshot color/selection defect. Conditional applicability can consequently follow that unintended value.

### Evidence and verification gap

- Reproducer: `scripts/reproduce-applicant-answer-race.mjs`.
- Trace: `docs/screenshots/phase-3d7-c/answer-race.json`.
- Safe synthetic screenshot: `docs/screenshots/phase-3d7-c/answer-race-after-reload.png`.
- Original general browser run: 18 interaction checks/67 screens, zero axe/overflow findings. Its conditional check asserted visibility/detail retention, but omitted an assertion that the final Yes survived saving/resuming. It therefore missed this defect. A stronger last-answer regression now exposes it.
- PostgreSQL: 87 tests/982 assertions passed; server tests do not reproduce this browser update race.

### Original investigation boundary and proposed remediation

The observable trigger is rapid Livewire model updates combined with conditional hide/show rendering. The precise internal ordering/root cause remains to be established; no claim is made that a particular framework function has been proven responsible. Likely investigation points are the shared questionnaire renderer, conditional morph ordering and model acknowledgement handling.

Stop ordinary Batch C work here. A targeted fix should preserve the latest intentional value through rapid updates, navigation and save/resume; add equivalent cases for choices, conditional text and editing during delayed responses. Preserve all existing server authorization, type/required validation, idempotency, locking and immutable-copy behavior. Do not patch historical submissions or introduce a migration/policy change to work around this failure.

All reproduction data is synthetic in `osds_ui_browser_test`. No command inspected, reset, reseeded or changed `osds_dev` or `osds_sandbox`. This finding establishes no cross-user disclosure or alteration of an existing immutable submission. Whether other field types are affected remains unverified.

### Authorized remediation and final root cause

The installed Livewire v4.4.7 client permits overlapping model messages. Synthetic traces proved two failure paths: a consolidated whole-answers update retained a live object reference that changed after dispatch, and an empty-delta earlier response could reconcile against canonical state advanced by a different response. In both paths `mergeNewSnapshot` was the first point where the latest client Yes became No, before DOM morphing or persistence. Radio/component identity stayed stable; the server condition evaluator and draft service acted on the values they actually received.

`resources/js/applicant.js` now copies JSON update values at dispatch and defers later applicant `model.live` actions until the active message finishes. Native input remains local immediately; queued actions capture the latest values. Existing Save/loading/action handling, locking and request keys remain in use. There is no arbitrary correctness delay, vendor modification, consequential retry, server-service rewrite, migration or policy change.

The original reproduction remains intact and is strengthened with selected-radio, conditional-child and direct database assertions. Before evidence is preserved separately; after remediation the final visible, client, save-payload, persisted and reloaded answer is **Yes**. The reverse sequence finishes **No**. Conditional child visibility/applicability follows those final values and retained child text survives hide/reshow. A copy-only first attempt failed the expanded no-pause test; it was diagnosed rather than accepted or skipped.

Expanded testing also found an unanswered multiple-choice control initially binding to boolean, which the backend safely rejected. `DraftEditor::mount` initializes that control to its defined array type. Existing saved answers and required validation remain unchanged. A new PostgreSQL regression verifies empty required selection, valid array persistence and resume; the browser matrix retains its checkbox tests.

### Final verification and handoff

- Original reproduction: passed with strengthened assertions.
- Expanded browser matrix: **11 complete cases passed**, each asserting selection/client, PostgreSQL value, reload and conditional applicability. Covers native keyboard, multi-question/array changes, immediate Save, navigation, in-flight changes, delayed replies, actual response reordering across tabs and stale-version rejection.
- Complete post-fix journey: **18 interactions and 67 screens passed**, zero axe/overflow/uncaught-error findings. Includes network/HTTP 503, uncertain responses, sessions, evidence, corrections/resubmission, representation and retirement.
- Focused PostgreSQL: **29 tests, 495 assertions passed**. Full PostgreSQL: **88 tests, 989 assertions passed**, including SEC-001–004.
- Pint, Vite production build and diff checks passed, with existing line-ending advisories. No manual-testing database was touched.
- Exact affected files, before/after images and coverage mapping: `docs/phase-3d7-c-int-001-remediation.md`. Updated handoff: `docs/phase-3d7-batch-c-report.md`.

The original stop record above is historical; its unresolved-root-cause wording is superseded by this remediation. Framework upgrades require rerunning the adapter's browser matrix. Other engines, screen readers, real Google accessibility, unrelated staff reactive state and human usability are not certified by these tests. No historical submission or manual record was repaired. **Pause for Batch C user review; Batch D and Phase 4 remain unstarted.**
