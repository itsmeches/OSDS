# Phase 2 refinement completion report

**Date:** 14 October 2026. **Scope:** incremental refinement of Phase 2 identity, authorization, regional/institutional/nationwide access, account lifecycle, and audit controls. Phase 3 has not started. The rules in this report are conditionally approved technical defaults, not final CHED policy.

## Baseline and repository control

- The existing Phase 2 implementation was preserved. No baseline migration was edited; one forward migration extends it.
- Before refinement, the original 13 PostgreSQL tests passed with 49 assertions.
- Previously reviewed Windows development helpers were isolated in baseline commit `654c21b`.
- `main` was synchronized with `origin/main` before that local commit. GitHub run `38509736083` for refinement commit `3ebe3a2` was observed through the Actions API. It failed before any step or runner started; GitHub's failure annotation says the account is locked due to a billing issue. This is an external repository-account blocker, and this report does not claim CI success.

## Delivered refinement

### Account creation and primary roles

- Account creation is target-permission driven: Super User creates Admin, Admin creates OSDS, OSDS creates CHEDRO, and CHEDRO creates SCHOOL.
- Laravel policies and `AccountGovernance` enforce account status, explicit permission, target account type, region/institution scope, and self-escalation denial.
- Filament account creation delegates to the domain service; role and scope rules are not implemented in the resource class.
- A PostgreSQL partial unique index on Spatie's existing `model_has_roles` pivot enforces one User role. This was selected over a second primary-role relationship to avoid two sources of truth. A later multi-role decision can use a forward migration to drop the index and introduce conflict rules.

### Account lifecycle and SCHOOL verification

- Supported states are `pending_verification`, `active`, `suspended`, and `inactive`.
- SCHOOL accounts start pending and have a pending institution-representative record.
- Account creation, role assignment, verification, activation, suspension, reactivation, and deactivation use semantic audit events.
- `account_status_events` preserves append-only state history with actor, reason, correlation ID, and timestamp.
- Whether the SCHOOL creator may also verify is configured by `OSDS_ALLOW_SCHOOL_CREATOR_TO_VERIFY` and defaults to false pending supervisor approval.
- Status and access revocation invalidates database sessions and clears remember tokens.

### Explicit scope and delegation

- Region and institution grants remain effective-dated and revocable.
- Nationwide access now uses its own effective and revocable grant record. No role receives it automatically.
- `AccessGovernance` performs grant, revoke, and regional transfer operations transactionally.
- An actor cannot grant themselves access, manage an account type outside their creation authority, or delegate a region/institution they cannot access.
- Partial unique indexes prevent duplicate active region, institution, and nationwide grants.
- User queries and policies ignore future, expired, and revoked region assignments.

### Audit integrity and security

- Sensitive changes and their semantic audit events commit in the same database transaction.
- PostgreSQL triggers reject direct update or deletion of `audit_events` and `account_status_events`; model-level append-only protection remains in place.
- SCHOOL receives no security-audit permission. Institution-scoped audit authorization now evaluates institution access correctly.
- National audit viewing requires both `audit.view.national` and an active nationwide grant.
- MFA role requirements are represented as a deployment policy only. MFA, recovery, emergency access, SSO, and retention remain production approval gates and are not claimed as completed controls.

## Database changes

The forward migration `2026_10_14_000001_refine_identity_authorization.php`:

1. expands the user status constraint;
2. adds verification metadata to institution representatives;
3. creates append-only account status history;
4. creates explicit nationwide access grants;
5. enforces one User role on the Spatie pivot;
6. prevents duplicate active grants; and
7. adds database-level append-only triggers.

Existing users, roles, permissions, regions, institutions, grants, and audit records are preserved. Existing institution representatives are treated as verified during migration to avoid silently removing established access.

## Configuration versus code

Permission bundles, target-role creation permission mapping, the SCHOOL creator-verifier toggle, and future production MFA role selection are configuration driven. One-role enforcement, state transitions, transaction boundaries, scope ceilings, self-escalation denial, session invalidation, and append-only guarantees remain in code and database constraints. See [permissions matrix](./permissions-matrix.md).

## Verification results

The final result table must reflect the verification commands run after the last code change:

| Check | Result |
| --- | --- |
| PostgreSQL clean migration | Passed through the full PostgreSQL test rebuild; all migrations apply from empty state |
| Development forward migration | Passed; refinement migration applied without rebuilding existing data |
| Automated tests | Passed: 26 tests, 80 assertions on PostgreSQL |
| Laravel Pint | Passed after formatting |
| Frontend production build | Passed with Vite 8.3.4 |
| IDE/static problem scan | Tool unavailable; PHP execution, tests, and formatter provide the local evidence |
| GitHub Actions | Blocked before execution: run `38509736083` created no runner or steps; GitHub reported an account billing lock |

## Formal CHED decisions and production blockers

1. Designate production Super User custodians and approve bootstrap, replacement, and recovery.
2. Decide whether one CHEDRO staff member may create and verify the same SCHOOL account, and approve verification evidence.
3. Approve final lifecycle, transfer, and delegation authorities and any second-person controls.
4. Identify offices eligible for nationwide access and its grant/revocation custodians.
5. Implement and approve privileged MFA, recovery, emergency access, and any SSO integration.
6. Approve audit reviewers, records classification, retention, legal hold, archival, and disposal.
7. Decide how athletic associations map to organization and account types.
8. Resolve the GitHub account billing lock and rerun CI successfully. The current failure contains no application test result because no runner started.

## Scope stop

No COR workflow, ACSA workflow, questionnaire engine, certificate generation, document intake, or RA 11180 reporting work is included. Phase 3 remains blocked pending separate approval.
