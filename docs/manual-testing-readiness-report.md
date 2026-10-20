# Phase 3D manual testing readiness report

**Date:** 19 October 2026  
**Verdict:** **CONDITIONALLY READY** — the critical synthetic environment, runtime SCHOOL entry path, application domain lifecycle, and Livewire submission flow are operational. A human browser run through correction and resubmission remains pending.

## Environment verification

- Laravel 13.35.0, PHP 8.4.25, Livewire 4, Filament 5, PostgreSQL on `127.0.0.1:55432`.
- Normal development runs on port 8000 with `osds_dev`.
- Applicant testing runs on port 8001 with `APP_ENV=sandbox` and the isolated `osds_sandbox` database.
- `ApplicantSandboxGuard` continues to reject production and databases whose names do not contain `sandbox` or `test`.
- The sandbox launcher provisions protected credentials, migrates, and idempotently seeds synthetic records without printing the password.

## Defects found and fixed

| Defect | Root cause | Resolution |
| --- | --- | --- |
| Applicant pages displayed unstyled HTML | The outer PHP router included Laravel's router, preventing its static-file `return false` from reaching the built-in server. | The outer sandbox router now serves existing static assets directly. |
| Applicant login rejected apparently valid SCHOOL accounts | Port 8000 accounts were nonsynthetic records in `osds_dev`; the portal correctly uses isolated `osds_sandbox`. | Added explicit sandbox provisioning guidance and protected synthetic account creation/enablement paths. |
| Start Application contained an empty questionnaire selector | Development fixtures created configuration drafts and grants but no applicant-sandbox manifest. | Added a sandbox-only lifecycle fixture that submits, approves, and publishes exact configurations through `QuestionnairePublicationService`. |
| Eligibility-grant create page returned HTTP 500 | A Filament relationship query modifier received `null` and called `where()` on it. | Replaced it with explicit synthetic-organization and active-module option queries; added a route regression test. |
| Repeated fixture setup could invalidate a reviewed hash | New fixture fields were added to a configuration already under review. | Fixture authoring now creates a forward version when no draft exists. Publication validates and publishes the new exact pair. |
| Applicant start options could include an unusable module | The controller listed every applicant manifest before checking organization/module eligibility. | Start options are now restricted to effective grants for currently represented synthetic organizations. |
| Duplicate SCHOOL email produced a database exception | Account creation relied on the unique constraint without a user-facing precheck. | Added normalized duplicate validation and safe unique-constraint handling. |

## Synthetic readiness fixture

`DevelopmentFixtureSeeder` now calls `SyntheticManualTestingSeeder` only in `sandbox` or `testing` and only when the strict database guard passes. It creates no production publication.

The fixture provides:

- active synthetic Super User, Admin, OSDS, CHEDRO, and SCHOOL identities;
- Harborview Learning Institute in Synthetic North Region;
- an active verified SCHOOL representation;
- effective Registration and ACSA eligibility grants;
- effective CHEDRO application and evidence reviewer assignments for the matching region/modules;
- required, optional, and conditional questionnaire fields;
- required and optional document requirements;
- approved immutable applicant-sandbox manifests created through the domain lifecycle.

Rerunning setup does not create another active manifest or assignment. It does not delete applications or developer data.

## Runtime and automated verification

| Check | Result |
| --- | --- |
| Sandbox setup, first run | Passed |
| Sandbox setup, immediate repeat | Passed; idempotent |
| Runtime SCHOOL login over HTTP | Passed |
| Runtime Start Application over HTTP | Passed; Registration and ACSA visible |
| Runtime draft creation and authorized editor route | Passed; editor returned HTTP 200 |
| Livewire answer → save → evidence → completeness → submission | Passed through regression test |
| CHEDRO screening, correction, cloned working revision, resubmission lineage | Passed through application lifecycle regression test |
| Cross-user, unassigned reviewer, regional and manifest controls | Passed in focused/full suite |
| Clean PostgreSQL migration | Passed through all migrations ending `400002` |
| Forward sandbox migration | Passed; nothing pending |
| Full PHPUnit suite | **72 tests, 288 assertions passed** |
| Applicant focused suite | **18 tests, 94 assertions passed** |
| Vite production build | Passed with Vite 8.3.4 |
| Laravel Pint | Passed after formatting |
| `git diff --check` | Passed; existing CRLF advisory only |

## Security verification

- Applicant publication still requires explicit permission, synthetic identities, synthetic organizations, verified representation, effective eligibility, an effective immutable manifest, nonproduction environment, and isolated database.
- Review and applicant publication channels remain separate.
- Evidence locators remain encrypted; raw locators and keys were not printed during this work.
- Draft lock versions, idempotency keys, immutable submissions, append-only findings, and PostgreSQL guards remain enabled.
- No official decision, certificate, issuance, registry, production publication, or Phase 4 feature was added.

## Role access matrix

| Role | Landing/navigation expected | Authorized areas | Direct routes expected to fail |
| --- | --- | --- | --- |
| Super User | Staff dashboard; only explicitly granted links | Existing oversight, account, and audit permissions in its bundle | Applicant ownership routes and questionnaire publication without explicit permission |
| Admin | Staff dashboard, approvals, publications, access management | Review decisions, sandbox publication, eligibility and reviewer assignments | Applicant-owned drafts/evidence |
| OSDS | Staff dashboard, Questionnaires, review submissions | Draft authoring, preview, configuration check, review submission | Admin decisions, applicant answers, CHEDRO intake |
| CHEDRO | Staff dashboard, assigned test reviews, Application intake | Cases matching explicit reviewer assignment and regional scope | Form authoring, publication, unassigned/cross-region cases |
| SCHOOL | Applicant home, Start application, My applications, account | Owned drafts, evidence, test-review copies, submission/tracking/corrections | Filament staff panel, another applicant's or organization's records |

Navigation is presentation only; middleware, policies, services, assignments, scopes, ownership, and database guards remain authoritative.

## Workflow evidence classification

| Workflow | Automated | Runtime HTTP | Browser/UI | Human manual |
| --- | --- | --- | --- | --- |
| SCHOOL login and questionnaire discovery | Passed | Passed | Not tested | Pending |
| SCHOOL save/evidence/completeness/submission | Passed through Livewire | Editor route passed | Not tested | Pending |
| CHEDRO screening and correction | Passed | Not tested | Not tested | Pending |
| SCHOOL correction and resubmission | Passed | Not tested | Not tested | Pending |
| Phase 3C evidence review/supersession | Passed | Not tested | Not tested | Pending |
| OSDS authoring and Admin publication | Passed by service/resource tests | Sandbox fixture lifecycle passed | Not tested | Pending |

## Browser and accessibility verification status

The integrated browser tool returned an unsupported-tool error in this session. Browser screenshots, viewport checks, theme visual checks, 200% zoom, and assistive-technology checks were therefore **not performed here**. Runtime HTTP responses and Livewire component behavior were exercised, but those checks do not replace visual browser testing. Use the accompanying checklist for the human run.

## Known limitations

- Actual human completion of the full OSDS → SCHOOL → CHEDRO → SCHOOL journey is pending.
- Synthetic questionnaire wording and documentary requirements remain provisional.
- Google URL validation does not prove file accessibility, authenticity, ownership, or continued sharing.
- Session-expiration appearance, mobile overflow, visual contrast, and confirmation-dialog behavior need the human browser pass.
- Historical errors remain in `storage/logs/laravel.log`; current focused operations generated no new unhandled application error after the fixes.

## Files central to stabilization

- `scripts/applicant-sandbox-server.php`
- `scripts/Start-ApplicantSandbox.ps1`
- `database/seeders/DevelopmentFixtureSeeder.php`
- `database/seeders/SyntheticQuestionnaireSeeder.php`
- `database/seeders/SyntheticManualTestingSeeder.php`
- `app/Http/Controllers/ApplicantPortalController.php`
- `app/Filament/Resources/ApplicantSandboxEligibilityGrants/ApplicantSandboxEligibilityGrantResource.php`
- `resources/views/applicant/login.blade.php`
- `resources/views/layouts/applicant.blade.php`
- `resources/views/applicant/start.blade.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `tests/Feature/ApplicantSandboxTest.php`

## Final assessment

The original SCHOOL blocker is resolved and the isolated environment now provides a repeatable path to start an application. Automated domain, authorization, database, and Livewire gates are green. The platform is **CONDITIONALLY READY** for complete human manual testing; it should move to **READY** only after the attached browser checklist is completed without a critical failure.
