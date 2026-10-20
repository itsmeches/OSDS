# G-INT-002 - Region-scoped atomic revocation remediation

**20 October 2026. G-INT-002 targeted gates passed.** The user explicitly approved permission-plus-effective-region revocation authority. Broader G resumed and stopped at independent G-INT-003 in Phase 3D. No schema change, historical repair, Phase 3D fix or staged-index operation was performed. The earlier blocker analysis below is retained as historical context.

## Authorized implementation checkpoint

`EvidenceReviewerAssignmentService::revoke` owns a narrow default-PostgreSQL transaction. It reloads the actor, locks the exact assignment, rereads the actor after any lock wait, checks current authenticated identity, active status, sandbox, explicit management permission and `ScopeAuthorizer::canAccessRegion`, rejects already-revoked state, validates the reason and updates using current actor/time. The existing synchronous observer is the sole mandatory audit writer. Both writes commit or roll back together. No transaction retry or blanket Filament transaction is enabled.

The Phase 3C revoke table action calls that service through existing safe support-reference feedback. A halted unexpected failure also leaves fixed, persistent feedback in the dialog description while preserving the reason. Its list receives the existing session/network recovery notice. No Phase 3D manager policy, list-discovery policy or creation callback was changed. This is the user's newly approved revocation rule, not a claim that region-scoped assignment management predated this decision.

The original regression retains real action/observer/PostgreSQL rollback assertions, with the explicitly authorized effective regional fixture grant. Its failure acknowledgement now checks the safe notification instead of exposing the injected exception. Expanded tests check actual same-PDO operation transaction depth, partial-audit rollback, exact attribution, effective/ineffective authority and post-mount permission/scope loss. Targeted matrix: **14 tests / 89 assertions passed**; combined G-INT-001/002 focused matrix: **22 / 160**. The complete final runtime suite after persistent dialog feedback passed **162 / 1,975**. This pass predates the separate G-INT-003 failing regression subsequently added during resumed G; it is not a full-current-tree pass after that stop.

## Browser and concurrency evidence

The actual Admin list/revoke journey passed **eight interaction checks and eight representative light/dark screenshot checks** at 1440px and 375px on the final runtime tree. It exercises actual audit insertion then failure, full rollback with retained input, duplicate native clicks, committed response loss without automatic replay, authoritative revoked-state reload, authorized Audit history, native keyboard System theme switching, post-mount regional/permission loss and session-cookie loss. Recorded axe violations, page overflow, uncaught errors and checked response leaks are zero. Machine-readable evidence: [browser results](screenshots/phase-3d7-g-int002/results.json), [concurrent workers](screenshots/phase-3d7-g-int002/concurrency.json). Representative rollback screenshots: [light desktop](screenshots/phase-3d7-g-int002/rollback-light-1440.png), [dark desktop](screenshots/phase-3d7-g-int002/rollback-dark-1440.png), [light mobile](screenshots/phase-3d7-g-int002/rollback-light-375.png), [dark mobile](screenshots/phase-3d7-g-int002/rollback-dark-375.png).

Two pairs of real PostgreSQL workers confirm an actual `pg_stat_activity` row-lock wait. The duplicate case commits one revocation and one audit, rejecting the second request. In the second case the fixture actor becomes inactive while the second connection waits: the waiting operation is denied after acquiring the lock. Both cases retain exactly one attributed revocation/audit. The test-only filesystem barrier and status manipulation are guarded disposable fixture synchronization, not runtime delays or changes to real users. See `concurrency.json`.

No new request-idempotency schema was added. The assignment's existing one-revocation lifecycle, row lock and fresh-state rejection prevent a second mutation; a repeated request is rejected rather than being promised an identical success response. Lost responses remain uncertain until the authorized saved record is inspected.

First attempts are not claimed successful: fixture comparisons initially used pre-refresh serialization rather than PostgreSQL-returned timestamp strings; a denied-action test helper needed direct action transport after authorization failure; logout itself added a legitimate audit and the fixture baseline was adjusted afterward. Browser harness corrections covered searchbox role, actual Confirm label, modal closure and avoiding keyboard scrolling behind an active focus-trapped modal. One rerun did not expose the transient failure notification reliably, so persistent dialog feedback was added and the browser rerun was required. An early isolated-schema bootstrap failed; subsequent unit runs were serialized. A copied helper's Admin/CHEDRO metadata label was corrected; the fixture always explicitly assigned Admin. None of these corrections bypassed service authorization, database guards or failure assertions.

Pint, production Vite build and working-tree diff checks passed; existing line-ending advisories remain. CHEDRO preservation browser: **12 interactions / 30 screens**; original applicant reproduction and **11 expanded cases** passed; original builder reproduction, **13 selection cases** and **seven condition-journey checks** passed. No full accessibility conformance, native zoom/screen-reader, human usability or production readiness claim is made. External avatar requests are blocked in browser fixtures, so screenshot avatars may be absent. The creation-page null callback and possible creation-audit risk remain unresolved source findings; no creation-failure defect was independently reproduced or repaired.

After all targeted gates passed, the resumed audit reproduced a distinct unaudited revocation through the actual Phase 3D application-assignment table action (**one test / eight assertions / one failure**). Work stopped immediately at G-INT-003. This does not undo the Phase 3C fix or authorize extending its new regional rule. Separate authorization is required for Phase 3D remediation and any management-policy decision. No historical impact assessment or repair was attempted.

Files: `app/Services/Evidence/EvidenceReviewerAssignmentService.php`; Phase 3C assignment resource; scoped recovery registration in `AdminPanelProvider`; original `ApplicantSandboxTest`; `EvidenceAssignmentAtomicityTest`; `Prepare-ReviewerRevocation.php`, `ReviewerRevocationWorker.php`, `g-int-002-browser-server.php` and `verify-reviewer-revocation.mjs`. Earlier discovery and governance-blocker text follows; its not-implemented statements describe the prior checkpoint.

## Historical discovery and governance decision

## Original transaction defect - historical

The actual Phase 3C reviewer-assignment revoke table action calls Eloquent update. Its model's updated observer then inserts the mandatory audit. Neither operation owns an encompassing transaction; optional Filament transactions are disabled by default. The original PostgreSQL/Livewire regression remains unchanged and still fails: **one test / eight assertions / one failure**. Audit count remains unchanged but revocation persists. The rerun record is private: `storage/app/private/g-int-002-governance-check.txt`. Only `osds_test` was used.

## Pre-decision governance audit - historical

Inspected the resource, list/create pages, assignment model, policies, ScopeAuthorizer, original regression, and Phase 3C/3D plans.

- `SyntheticEvidenceReviewerAssignmentResource::canViewAny()` requires an active actor and `evidence.reviewer.assignments.manage`. The resource has no target-region query restriction or assignment-specific policy.
- The revoke action is visible for unrevoked rows and directly updates the selected record. It does not invoke `ScopeAuthorizer::canAccessRegion()`.
- The assignment model's update observer supplies revocation attribution/reason validation and audit; it does not establish a target-region management boundary.
- No assignment-management service or assignment-specific policy was found defining the missing boundary. The separate CHEDRO review service and queue scope enforce exact reviewer region/module pairs for reviewing cases; those are not an Admin assignment-management policy.
- The original authorized Admin fixture has no regional/nationwide grant. Adding a target-region gate would deny it before the injected audit failure unless fixture authority is explicitly adjusted. The original failure must continue reaching the real mutation and audit observer.
- The approved plans name Admin as provisional synthetic assignment manager, but do not specify whether that management permission is global or additionally region-scoped. General staff regional-grant policies cannot silently substitute for this distinct assignment operation.

The approval requires incorrect-region denial and scope-expiry/revocation checks while prohibiting new delegation/governance semantics. Its Section 3 requires reporting a differing current contract; Section 11 requires stopping if governance semantics must change. Therefore no transaction-only partial fix is presented as meeting the complete authorization matrix.

## Proposed decision - subsequently approved

Approve this target rule for Phase 3C reviewer-assignment revocation: an active authenticated actor must hold `evidence.reviewer.assignments.manage`, pass the sandbox guard, and satisfy the existing `ScopeAuthorizer::canAccessRegion(actor, assignment.region_id)` predicate. Effective nationwide grants satisfy that existing predicate; role names alone do not. No additional module-management permission or reviewer assignment is inferred for the manager.

If approved, implement a narrow domain-service transaction: reload actor, lock the exact assignment row, reauthorize against that rule, reject an already-revoked target, validate the reason, update with explicit actor/time, and allow the existing observer audit to execute before commit on the same PostgreSQL connection. Row locking serializes duplicate/concurrent revocations. No blanket panel transaction, new permission, role hierarchy or schema is proposed. The original regression would retain its real action and rollback assertion, with an effective regional grant added solely to keep its actor authorized under the approved rule.

Alternatively, preserving global permission-only assignment management requires explicit acknowledgement that wrong-region/expired-scope cases cannot be expected to fail under that contract. No choice was made on the user's behalf.

## Bounded creation inspection

The creation observer also records audit after insertion and the Create page has no explicit transaction. This is a source risk, not a separately reproduced creation/audit failure. The existing null relationship callback remains recorded at `modifyQueryUsing: fn ($q) => ...`; no functional fix or bypass was applied. It does not prevent a revoke-only investigation. No creation-impact or historical audit completeness claim is made.

## Earlier verification checkpoint - superseded

Only the retained failing regression was rerun in this authorization turn. No expanded matrix, concurrency/browser acceptance, Pint/build gate or new complete-suite pass is claimed. The earlier G-INT-001 148-test pass remains historical. Existing G-INT-001 and SEC/integrity runtime protections were not changed. Manual-testing databases were not inspected or modified.

After the governance decision, complete the authorized actual-action rollback/success/duplicate/concurrency/security/browser matrix and full final-tree regressions. Broader G work remains paused; H, Phase 4, production and historical repair remain excluded.
