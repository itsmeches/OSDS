# Phase 3B completion report

**Date:** 14 October 2026
**Result:** Implemented and verified locally; Checkpoint 3C remains unstarted

## Implemented functionality

- Added controlled organization types plus synthetic organization and identity markers while preserving the Phase 2 `institutions` model.
- Kept organization type, verified representation, explicit module eligibility, and any future accreditation concept separate.
- Added effective-dated synthetic organization/module eligibility grants managed by an authorized Admin.
- Added a distinct immutable `applicant_sandbox` publication action and channel. Staff review publication does not expose a form to SCHOOL users.
- Added manifest-bound drafts, typed JSON answers, optimistic locking, idempotency records, and append-only draft events.
- Existing retired or expired drafts remain viewable, while editing and new draft creation are blocked.
- Added a separate authenticated Livewire applicant portal with Home, Start application, My drafts, Account and organization, questionnaire progress, Save draft, and Check progress.
- Reused the shared design system. No evidence, submission, tracking number, CHEDRO processing, official validation, registry, certificate, issuance, or RA 11180 feature was added.

## Database and migrations

`2026_10_14_200000_create_phase_3b_applicant_foundation.php` adds organization compatibility, applicant eligibility, draft/answer/event/idempotency tables, channel separation, constraints, indexes, append-only history, and immutable draft bindings.

`2026_10_14_200001_add_synthetic_identity_guard.php` adds the explicit synthetic-user marker as a separate forward migration.

`2026_10_14_200002_harden_applicant_sandbox_guards.php` adds PostgreSQL enforcement for manifest/module matching, exact form-version answers, synthetic eligibility, and organization-type immutability after a draft exists.

Clean migration against `osds_test` passed. Both migrations also applied forward over the existing development database without rebuilding earlier data.

## Security and isolation

- Applicant access is rejected in `production` and limited to `local`, `testing`, or `sandbox` environments.
- Outside tests, the connected database name must contain `sandbox` or `test`; an application flag alone cannot enable applicant access.
- Synthetic identity and organization, active account, explicit permission, verified representation, effective eligibility, effective applicant manifest, and ownership are checked server side.
- UUID draft routes return not found for another applicant.
- SCHOOL remains excluded from Filament; staff have no applicant-answer interface.
- Answer values are excluded from audit and draft-event metadata.

## Autosave and retirement behavior

- Saves compare the expected version while holding a PostgreSQL row lock.
- Stale saves do not overwrite newer values.
- Repeated idempotency keys return their original confirmed version without another write.
- UI feedback distinguishes Unsaved changes, Saving, Saved, Save failed, and Updated in another tab. Saved appears only after server confirmation.
- Retired or expired drafts remain readable with preserved answers and cannot be edited or rebound.

## Verification results

| Check | Result |
| --- | --- |
| UI/UX prerequisite | Passed: 6 tests, 28 assertions |
| Focused 3B/authorization/regression gate | Passed: 28 tests, 103 assertions |
| Clean PostgreSQL migration | Passed against `osds_test` |
| Complete PHPUnit suite | Passed: 49 tests, 158 assertions |
| Laravel Pint | Passed after formatting |
| Frontend production build | Passed with Vite 8.3.4 |

Automated acceptance covers manifest-bound creation, save/resume, idempotency, stale conflict protection, progress checking, retirement read-only behavior, cross-user denial, and publication-channel separation.

## Manual verification

Use a dedicated database such as `osds_sandbox`. Run migrations and `DevelopmentFixtureSeeder` with `OSDS_FIXTURE_PASSWORD`, then:

1. As OSDS, submit a synthetic configuration for review.
2. As Admin, approve it, publish separately to **Applicant sandbox**, and confirm the synthetic eligibility grant.
3. Sign in at `/applicant/login` with the synthetic SCHOOL account.
4. Start, complete, save, leave, resume, and check a draft.
5. Use two tabs and verify the stale tab reports **Updated in another tab** without discarding its input.
6. Retire the publication and verify that the draft remains visible but read-only.

Testing with actual CHED staff remains pending and is not claimed as completed.

## Known limitations

- Supplied-form questionnaire fixtures remain provisional and synthetic.
- Applicant sandbox is unavailable on a general development database unless it is deliberately isolated and named for sandbox/test use.
- Changes immediately show **Unsaved changes** and persist through **Save draft**. Timed background autosave can reuse the same service contract later.
- Real eligibility, accreditation, privacy, retention, evidence custody, Google sharing, jurisdiction, duplicate application, submission, withdrawal, and correction rules remain unresolved.

## Stop boundary

Checkpoint 3C has not started. No evidence reference, Google-link review, or CHEDRO finding was implemented.
