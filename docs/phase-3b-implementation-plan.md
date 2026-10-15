# Checkpoint 3B implementation plan

**Status:** Implemented locally; see the Phase 3B completion report
**Date:** 14 October 2026

## Objective and boundary

Checkpoint 3B will add organization compatibility, a separate authenticated SCHOOL portal, and concurrency-safe questionnaire drafts. A verified synthetic SCHOOL user will be able to start, save, leave, and resume a draft bound to one exact immutable publication manifest.

This checkpoint will not accept evidence links or uploads, submit an application, issue a tracking number, expose a production questionnaire, create CHEDRO work, request corrections, validate an application, create registry records, or generate certificates. Evidence belongs to 3C; atomic submission and correction revisions belong to 3D. Official COR/ACSA processing remains Phase 4 or later. RA 11180 remains separate.

## Proposed technical defaults

These defaults need approval with this plan and do not establish CHED policy:

1. Keep the existing `institutions` table for Phase 2 compatibility and add `organization_type`, `is_synthetic`, and optional `display_name`.
2. Backfill existing rows to `hei`. Candidate types are `hei`, `athletic_association`, `national_sports_association`, `private_organization`, and `government_entity`.
3. Type permits representation only. It never proves accreditation, recognition, or program eligibility.
4. Add an authenticated `applicant_sandbox` publication channel. It is disabled by default outside local/testing and restricted to synthetic SCHOOL users and organizations. No production or anonymous channel will exist.
5. A draft belongs to one user, one represented organization, one module, and one immutable publication manifest. Those bindings cannot change.
6. Autosave uses a client supplied `lock_version` and idempotency UUID. Stale saves fail without overwriting newer data.
7. Draft answers are mutable working data. Immutable submitted revisions begin only in 3D.

## Organization safeguards

The Phase 2 audit found that policies, `ScopeAuthorizer`, account governance, Filament resources, and fixtures treat all represented records as institutions. Regional scope answers who may access a record; it must never be reused as eligibility or accreditation.

- Require active, verified, unrevoked representation on every applicant request.
- Require explicit organization selection when a user represents more than one.
- Block access immediately after account suspension or representation revocation while preserving history.
- Add explicit `isHei()` and applicant-sandbox predicates to HEI-only and applicant policies.
- Audit organization type changes and prohibit them after a draft exists until a correction policy is approved.
- Keep table/class names as a documented compatibility compromise. A later neutral `organizations` migration needs separate justification and approval.

## Publication isolation and permissions

Extend the manifest channel constraint from `review_sandbox` to also allow `applicant_sandbox`. The new channel must retain exact approved hashes, effective dates, retirement history, append-only manifests, and PostgreSQL immutability.

| Permission | Provisional assignment |
| --- | --- |
| `applicant.sandbox.publish` | Admin |
| `applicant.portal.access` | SCHOOL |
| `applications.draft.create` | SCHOOL |
| `applications.draft.view` | SCHOOL |
| `applications.draft.update` | SCHOOL |

Every operation also checks active account, synthetic status, current representation, ownership, manifest channel/effectivity, module, and organization. Role labels alone grant nothing.

## Database design

### `application_drafts`

- `id`, unique UUID `public_id`
- required FKs: `module_id`, `publication_manifest_id`, `institution_id`, `created_by`
- `status` constrained to `draft` in 3B
- `lock_version`, `last_saved_at`, timezone timestamps
- indexes on `(created_by, status, updated_at)`, `(institution_id, module_id, status)`, and manifest
- PostgreSQL trigger prevents changes to module, manifest, organization, or creator after insert

Do not impose one draft per module because repeat-application rules are unapproved.

### `application_draft_answers`

- draft FK, exact `form_question_id`, typed JSONB `value`, `updated_by`, timestamps
- unique `(application_draft_id, form_question_id)`
- service/database checks reject questions outside the manifest form version
- choice values store stable option keys
- hidden answers remain in the draft but are excluded from current validation; no silent deletion

### `application_draft_events` and idempotency

Append-only events record creation, successful saves, stale-save rejection, and access denial using IDs, versions, correlation IDs, and safe metadata only. Answer content is prohibited from audit records and logs. Store request idempotency keys with their result so retries do not duplicate work or increment versions twice.

## Domain transaction boundaries

`startDraft` locks and validates the user, representation, synthetic organization, effective sandbox manifest, hashes, and module before atomically creating the draft and audit/event records.

`saveAnswers` authorizes ownership and representation, locks the draft, compares `expected_lock_version`, validates types/options/conditions against the exact published form, upserts only supplied answers, increments the version, and writes safe history in one transaction. A stale request returns a conflict with **Reload latest draft**; it never merges automatically.

Autosave permits incomplete drafts while enforcing types and safe lengths. **Check progress** reports missing visible required questions without submitting. Documentary requirements are read-only in 3B and clearly state that evidence will be added in a later checkpoint.

## Applicant portal

Build a separate Blade/Livewire shell outside `/admin`, reusing design tokens and Light/Dark/System modes.

- **Home:** draft count and next task.
- **My applications:** owned drafts for currently represented organizations.
- **Start application:** explicit organization and module selection from effective sandbox manifests.
- **Account and organization:** read-only representation summary.
- **Help:** technical checkpoint guidance without invented policy.

The editor uses the approved applicant reference: compact sidebar, progress rail, large questionnaire surface, predictable **Back**, **Save draft**, **Next step**, and **Check progress** actions. On mobile, progress moves above the form. It must not show Submit, Upload, official case status, CHEDRO assignment, or certificate language.

## Security boundaries

- SCHOOL remains excluded from Filament; staff do not receive applicant-answer access.
- Policies and domain services enforce ownership and representation on every read/write.
- UUID route binding returns not found for another applicant's draft.
- Never hydrate or log another user's answers, permissions, internal IDs, secrets, or audit payloads.
- Protect against forged question/organization IDs, cross-manifest answers, mass assignment, expired manifests, CSRF, oversized values, and excessive autosave frequency.
- Production privacy notice, lawful basis, retention, MFA, recovery, hosting, and VAPT remain release gates.

## Forward-only migration order

1. Add/backfill organization type and synthetic marker with constraints and indexes.
2. Add type guards without changing existing Phase 2 scope grants.
3. Add `applicant_sandbox` to manifest constraints and existing immutability protections.
4. Create drafts and immutable-binding trigger.
5. Create typed draft answers with exact-version enforcement.
6. Create append-only draft events and idempotency records.
7. Seed permission definitions; add synthetic applicant fixtures only through `DevelopmentFixtureSeeder` in local/testing.

Test clean PostgreSQL migration and forward upgrade from Phase 3A. No destructive rename or data rewrite is planned.

## Work packages and gates

### 3B.1 — Organization compatibility

Implement migration, model predicates, policy/service hardening, neutral applicant labels, and regression tests. **Gate:** all Phase 2 tests pass and non-HEIs gain no HEI meaning or access from type alone.

### 3B.2 — Applicant sandbox publication

Implement channel, feature gate, permission, Admin confirmation, synthetic restrictions, and retirement. **Gate:** exact approved configuration can be published to the sandbox; production and anonymous publication remain impossible.

### 3B.3 — Draft domain

Implement schema, models, policies, transactions, typed validation, idempotency, audit, and concurrency. **Gate:** applicants can access only owned represented-organization drafts; forged and stale writes fail safely.

### 3B.4 — Livewire applicant portal

Implement the separate responsive shell, application list/start flow, questionnaire steps, autosave, progress check, and conflict recovery. **Gate:** a first-time synthetic SCHOOL user completes the manual draft scenario without staff access or submission/evidence actions.

Stop after every work package if its focused PostgreSQL, authorization, and migration checks fail.

## Required tests

- HEI backfill and all existing Phase 2/3A behavior.
- Neutral behavior for every organization type.
- Denial for unverified, revoked, suspended, expired, wrong-organization, wrong-region, and nonsynthetic access.
- Direct-route and forged Livewire request denial.
- Exact effective manifest binding; reject review sandbox, retired, expired, changed-hash, mixed-version, and cross-module records.
- Round-trip every supported question type; reject invalid options, values, IDs, and cross-version questions.
- Shared conditional semantics and applicable-only progress validation.
- Two-tab conflict: first save succeeds, stale save fails without lost data.
- Duplicate idempotency keys have one effect; transaction failure leaves no partial answer/audit state.
- Revocation between page load and save blocks the write.
- Answer values never appear in logs or audit metadata.
- Keyboard, focus, error announcement, 200% zoom, mobile reflow, reduced motion, and both themes.

## Acceptance scenario

1. Admin publishes an approved synthetic Registration configuration to applicant sandbox.
2. A verified synthetic SCHOOL user signs in outside Filament and explicitly selects a represented synthetic organization.
3. The portal creates a draft bound to the exact manifest.
4. The user completes sections, triggers conditions, saves, signs out, and resumes.
5. A second-tab stale save is rejected with clear recovery and no overwritten data.
6. **Check progress** shows applicable missing answers and previews document requirements without accepting links/files.
7. No Submit action appears; another user's draft and all staff routes remain inaccessible.

## Decisions still required

| Decision | Required before |
| --- | --- |
| Which organization types may apply to Registration/ACSA and who represents them | Real pilot/Phase 4 |
| Platform versus Google Form transition and cutover | Production publication |
| Privacy notice, lawful basis, sensitive-data classification, retention | Real applicant data |
| Requirement-level link/upload/physical custody and Google sharing | 3C |
| Anonymous/shareable application links | Any anonymous channel |
| Jurisdiction/routing rules | CHEDRO workflow |
| Duplicate applications, submission acknowledgment, withdrawal, correction/resubmission | 3D/Phase 4 |
| Official terminology, approved forms, and CMO citation | Production configuration |

## Completion gate

Run the complete PostgreSQL PHPUnit suite, clean and upgrade migrations, concurrency/idempotency tests, authorization smoke tests, Laravel Pint, production frontend build, `git diff --check`, and the synthetic applicant usability script. Produce `docs/phase-3b-completion-report.md`, then stop before 3C.
