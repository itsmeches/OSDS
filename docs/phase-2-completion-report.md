# Phase 2 completion report

**Date:** 13 October 2026. **Scope:** identity, authorization, regional/institutional scope, core directories, development fixtures, and audit infrastructure only. Phase 3 has not started.

## Handoff findings

The six Phase 1 tests passed before implementation and both local PostgreSQL databases were reachable. The Phase 1 baseline was not committed: `main` has no commits and all workspace files remain untracked. GitHub CI could not be observed because no GitHub CLI or connected run was available. These gaps limit change provenance and remote reproducibility; this report does not claim CI success.

## Delivered data model

| Tables | Purpose and controls |
| --- | --- |
| `users` | Added immutable public UUID, active/inactive status, deactivation time/actor; deactivation invalidates database sessions. |
| `roles`, `permissions`, Spatie pivots | Five exact role names on the `web` guard and ten explicit foundational permission keys; no hierarchy or wildcard permission. |
| `regions`, `institutions` | UUID directory records, unique region codes, partial unique official institution ID, active state, and optional primary region. |
| `user_region_assignments` | Effective, expiring, and revocable regional scope with grant/revocation actors; indexed active lookup and valid-range constraint. |
| `institution_representatives` | Effective and revocable user-to-institution representation with actor history and scope indexes. |
| `scoped_authorization_grants` | Permission plus exactly one region or institution scope, effective range, grantor, and revocation fields. No policy-dependent grant is seeded. |
| `audit_events` | Append-only actor/action/resource/context/outcome/correlation/timestamp and sanitized JSON metadata; indexed timelines. |

Foreign keys restrict deletion of referenced directory and scope records. PostgreSQL check constraints reject invalid statuses, outcomes, date ranges, and unscoped or ambiguously scoped permission grants. Public UUIDs are assigned by models and backfilled during migration.

## Provisional roles and permissions

The roles are `Super User`, `Admin`, `OSDS`, `CHEDRO`, and `SCHOOL`. The implemented permission keys are `platform.admin.access`, `users.view`, `users.manage`, `regions.view`, `regions.manage`, `region_scopes.assign`, `institutions.view`, `institutions.manage`, `institution_representatives.assign`, and `audit.view`.

Super User and Admin receive all ten foundational permissions for validation. OSDS receives panel and read permissions including audit. CHEDRO receives panel and directory access plus institution/representative management. SCHOOL receives institution view permission but no staff-panel permission. Every record operation still requires active region or represented-institution scope. No role inherits from another, and no role has an unconditional authorization bypass.

These assignments are technical defaults pending supervisor approval. They confer no COR/ACSA approval, signature, registry, issuance, certificate, export, or RA 11180 authority because those permissions do not exist.

## Security controls

- Filament requires authentication, active account state, and `platform.admin.access`. SCHOOL accounts are separated from the staff panel.
- Resource policies deny by default; queries are restricted before rendering so guessed out-of-scope identifiers return no record. Model hooks also reject forged cross-region creation and assignment writes.
- Account deactivation timestamps the change, records its actor when available, removes database sessions, and blocks the next request.
- Scope and representation grants support start/end time and explicit revocation. A higher role name does not create national visibility.
- Core identity changes create audit events containing changed field names rather than before/after personal values. The recorder removes password, token, secret, authorization, and cookie keys. Audit rows reject model update/delete operations.
- Local fixtures require an environment-supplied password, use only `example.invalid` identities and fictional organizations, are not loaded automatically, and refuse non-local/non-test environments.

## Minimal administrative interfaces

Filament provides staff login, scoped user listing/editing and deactivation, region and institution directories, region assignment and institution representative records, and a read-only audit list. User creation and initial SCHOOL enrollment are intentionally absent from the panel until provisioning, identity verification, and privilege-assignment rules are approved. Records are deactivated or revoked rather than deleted.

## Verification

| Check | Result |
| --- | --- |
| Clean PostgreSQL test migration | Passed: baseline, user extension, Spatie permission, and core identity/audit migrations |
| Development migration | Passed; all Phase 2 migrations applied |
| Authorization seed | Passed on development and test databases |
| Synthetic development fixtures | Passed with a generated temporary local password; five account types, two regions, three institutions |
| Automated tests | 13 passed, 49 assertions, PostgreSQL; includes authentication, inactive account/session, roles, panel access, regional and institutional isolation, direct-route concealment, direct-write denial, revocation, auditing, and constraints |
| Formatter | Laravel Pint passed after formatting |
| Frontend | Vite/Tailwind production build passed |

The local fixture password was generated outside the repository in the Phase 1 temporary tools directory and was not printed or committed. Local URLs remain `http://127.0.0.1:8000/`, `/up`, and `/admin` when `php artisan serve` is running.

## Outstanding decisions and limits

1. Approve the provisional permission assignments and decide who provisions, deactivates, recovers, and assigns each account type. Initial user creation is kept out of the panel until this is settled.
2. Define SCHOOL representative verification and association handling. The current data model supports representation but does not establish legal authority.
3. Decide whether Admin or OSDS receives any national detailed-data or audit access. Current access requires explicit region/institution scope.
4. Approve staff MFA/identity-provider integration, session lifetime, recovery, and emergency-access procedure before production use.
5. Define retention and archive rules. Audit records are application-level append-only; database-owner tamper resistance and archival controls still require infrastructure design.
6. Confirm whether multiple top-level roles are allowed. Fixtures assign one role, but the database package can technically hold more than one until an approved application rule is added.
7. Remote CI remains unverified, and the repository still has no baseline commit. Establish a reviewed commit and observe a successful CI run before treating this as a controlled shared baseline.
8. Filament scope selectors intentionally cannot discover unscoped SCHOOL users. A controlled enrollment workflow is needed after stakeholder approval.

## Readiness for Phase 3

The Phase 2 implementation passes all local acceptance checks and supplies the database and policy boundaries needed for Phase 3. Phase 3 should remain blocked from production-oriented work until the permission matrix, enrollment procedure, MFA approach, baseline commit, and remote CI run are reviewed. No questionnaire or evidence implementation is authorized by this report.
