# Phase 3A completion report

**Date:** 14 October 2026. **Scope:** configurable Registration and ACSA questionnaire definitions, documentary requirements, controlled review, immutable synthetic publication, and compatibility analysis. This checkpoint creates no applicant submission, evidence reference, CHEDRO review, official validation, registry, certificate, issuance, or RA 11180 record.

## Implemented features

- Relational modules, definitions, numbered form versions, ordered sections/questions/options, separately versioned documentary requirements, and a Google evidence-provider catalogue.
- Shared declarative `all`/`any` condition semantics with `equals`, `not_equals`, `in`, `not_in`, `is_empty`, and `is_not_empty`. Publication rejects missing/later question references, invalid option keys, unsupported operators and module/version mismatches.
- OSDS draft authoring, nested ordering, guided condition entry, preview and deep cloning through Filament.
- Draft → review → approval/rejection → sandbox publication. Approval and the immutable manifest bind exact form/requirement versions and canonical hashes.
- Admin-only sandbox publication; OSDS/Admin staff-only preview with an explicit synthetic banner and no response persistence. No production publication channel exists.
- Application and PostgreSQL immutability for published configuration, approvals, manifests and retirement records. Publication and audit records commit transactionally.

## Database migration

Forward migration `2026_10_14_100000_create_questionnaire_configuration_tables.php` creates 14 configuration/publication tables plus a manifest-retirement record, constraints, indexes and database triggers. Follow-up migration `2026_10_14_100001_harden_questionnaire_publication_guards.php` adds the definition-level and direct-state publication guards. Neither alters `institutions` or any Phase 2 table. Both passed from an empty PostgreSQL database and as forward migrations over the existing development schema.

Reference seeding creates only Registration/ACSA module records and Google Drive/Workspace provider codes. Synthetic draft questions and requirements are loaded only by `DevelopmentFixtureSeeder` in `local` or `testing`; no production manifest or user is seeded.

## Permissions and security

- OSDS: view, manage drafts, submit review, preview sandbox.
- Admin: view, review, publish/retire sandbox, preview and view manifests.
- Super User, CHEDRO and SCHOOL receive no questionnaire permission through their role bundles.
- Domain services recheck active account and explicit permissions. Filament visibility is only presentation.
- Sandbox routes require authentication, active account and explicit preview permission.
- Audit metadata contains IDs, hashes, channel, reference and reasons; it contains no full questionnaire payload or future evidence locator.
- PostgreSQL rejects mutations to published/retired versions and their children and rejects update/deletion of approval, manifest and retirement records.

## Screens and manual workflow

Under **Questionnaire configuration** in `/admin`:

1. **Modules** and **Evidence provider types** show the controlled catalogues.
2. **Form definitions** defines the module-owned questionnaire.
3. **Form versions** edits ordered sections, questions, choices, validation JSON and guided conditions; OSDS pairs a draft with a draft requirement set and submits it for review.
4. **Requirement set versions** edits, reorders and clones documentary requirements, accepted provider types and conditional applicability.
5. **Configuration review requests** lets an authorized Admin approve/reject the exact hash and publish an approved pair to the sandbox.
6. **Publication manifests** shows the bound versions/hashes, opens the staff preview, and records retirement separately.

## Phase 2 compatibility audit

- `Institution`, `InstitutionPolicy`, `ScopeAuthorizer`, representation grants and Filament resources consistently assume that every represented organization is an institution. No type or accreditation marker exists.
- A verified `institution_representatives` record grants access to that record; regional staff access follows `primary_region_id`. Future association support must add an organization type and require operation-specific type guards rather than treating regional access as HEI accreditation.
- `AccountGovernance` creates and verifies SCHOOL users against an institution. Phase 3B must decide which organization types a SCHOOL-role applicant may represent and must not infer eligibility from the role name.
- `official_id`, directory labels, routes, fixtures, policies and tests use institution/HEI terminology. A backward-compatible type migration and neutral applicant-facing labels are required before associations are enabled.
- Phase 3A makes no institution schema or authorization-semantic change, so existing Phase 2 behavior remains intact.

## Verification results

| Check | Result |
| --- | --- |
| Clean PostgreSQL migration | Passed from empty `osds_test` |
| Development forward migration | Passed as batch 4 without rebuilding Phase 2 data |
| Focused questionnaire tests | Passed: 10 tests, 23 assertions |
| Complete PHPUnit suite | Passed: 36 tests, 103 assertions |
| Laravel Pint | Passed after formatting |
| Frontend production build | Passed with Vite 8.3.4 |
| Git diff check | Passed |

## Known limitations and production blockers

- Provisional form content is synthetic review data; official terminology, questions, requirements, privacy wording and cited CMO still require approval.
- Conditional authoring supports one guided `all` or `any` group; the domain evaluator accepts nested groups, but the UI intentionally avoids a general expression builder.
- No applicant response, evidence locator, Google sharing verification, workflow or official record exists.
- Retention, production identity/MFA, hosting and deployment clearance remain unresolved.
- GitHub CI remains subject to the previously documented repository-account billing lock until a successful remote run is observable.

## Phase 3B prerequisites

Approve the organization compatibility migration, applicant organization types and eligibility boundaries, neutral applicant terminology, verified representation behavior, and exact applicant-portal scope. Checkpoint 3B must preserve manifest binding, regional isolation and Phase 2 account controls. It remains unstarted.
