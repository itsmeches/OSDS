# Gate B2 — Reviewer inspector table-mapping correction

**28 October 2026. PASS for the source-only correction and offline audit. Attempt 02 is prepared but NOT RUN; live acceptance remains BLOCKED pending authorization.** No database, Laravel bootstrap, server, browser, authentication or lifecycle action occurred. B3/B4 remain closed.

## Root cause and exact changes

The inspector inferred `osds_modules` from the `OsdsModule` class name. The model explicitly declares `$table = 'modules'`, and the demonstration-document migration's module foreign key targets `modules`. This was a verification-helper defect, not evidence of an application or migration failure.

Changed only:

| File / line | Correction |
| --- | --- |
| `scripts/Inspect-UiUxB2Reviewer.php:98` | Replace `table('osds_modules')` with `table('modules')` in the shared preflight/postflight module-label lookup |
| `scripts/Run-UiUxB2Reviewer.mjs:7` | Fixed evidence directory changes from attempt-01 to attempt-02 |
| Same launcher, line 14 | Fixed reservation identifier changes to `b2-reviewer-only-attempt-02` |
| `scripts/UiUxB2PersonnelBootstrap.php:10` | Match the fixed attempt-02 evidence and identity-log directory |
| `scripts/verify-uiux-b2-reviewer-only.mjs:4` | Match the fixed attempt-02 evidence directory |

Offline before/after comparisons prove that the inspector differs only by that table literal, and the other three files differ only by these attempt identifiers/paths. The foundation credential path still points to its original creation attempt; it was not mechanically advanced. No application model, migration, guard, permission, request policy or acceptance assertion changed.

## Complete inspector reference audit

Reviewed both branches of the complete inspector, including SQL expressions and dereferenced row fields. Used application models, migration definitions and the retained Gate A browser schema inventory `osds_ui_browser_test-20261027-062913-33025c.json`. This is historical schema evidence, not a fresh connection.

| Reference / use | Columns checked / finding |
| --- | --- |
| `modules`, module-label lookup | `id`, `name`; confirmed by `OsdsModule::$table`, core module migration and historical columns. **Confirmed defect corrected.** |
| `regions`, primary-region label | `id`, `name`; confirmed schema/model. No correction. |
| `demo_office_directory_revisions`, fixed directory and latest ID | `id`, `offices`; model and directory migration agree. Existing map comparison and max-ID assertion retained. |
| `demonstration_document_versions`, fixed versions / row digests | `id`, `kind`, `module_id`, `region_id`, `directory_revision_id`, `version`, `state`, `content`, `content_hash`, `created_by`, `lock_version`, `created_at`; all present. `to_jsonb(v)`, `md5(...)`, ID exclusion and ordering remain unchanged. |
| `demonstration_document_history`, ordered lifecycle | `id`, `version_id`, `event`, `actor_id`; migration explicitly binds version/actor to document versions/users. No inferred join. |
| `audit_events`, version lifecycle audit | `id`, `resource_type`, `resource_id`, `action`, `actor_id`; resource ID is a string column, matching the explicit string cast. The type discriminator matches the model class and audit recorder contract. |
| Dynamic count-only queries | Fixed historical keys: `applications`, `application_state_history`, `application_decision_stages`, `issuance_requests`, `demonstration_documents`, `permissions`, `model_has_roles`, `nationwide_access_grants`. No inferred column or assumed `id` on pivot tables. Definitions exist in retained schema/migrations; counts passed before attempt-01 stopped. |
| Delegated `b2Protected()` | Explicit IDs and digest predicates on publications/retirements, issuance definitions, document versions, directory revisions, form/requirement versions, sections, questions/options, requirements and configuration approvals. Reviewed predicate fields against their migration/source definitions; unchanged helper and protected comparisons passed in attempt-01. |
| Delegated `uiuxCompatibility()` / projection catalogue | Fixed publication/form/requirement/approval, definition, template, module and personnel projections retain their existing explicit linkage checks. No renamed table or changed projection. Complete equality passed before attempt-01's label failure. |
| Delegated `b2VerifyFoundation()` | Existing user/institution/region models, permission/role pivots and ledger-owned representation, eligibility, regional and reviewer assignment tables remain unchanged. Actual model table mapping and migration definitions support these references; foundation verification passed in attempt-01. |

The static schema test checked **27 distinct direct column references across six tables**, plus all **eight count-only table names** against retained metadata/migrations. No direct inspector query contains a join. Module/region/directory links are checked by exact foreign-key values; history uses `version_id`; audit resources are polymorphic string bindings, not a guessed foreign key.

No other incorrect table name, invalid direct column, confirmed foreign-key assumption or preflight/postflight query inconsistency was found. Both stages use the corrected label lookup. Postflight alone reads the new attempt's `preflight.json` to compare unrelated version digests; it does not read or overwrite attempt-01's preflight. Missing files/rows, changed configuration, invalid JSON or genuine authorization/lock conditions may still fail closed at runtime; offline metadata cannot certify a new live run.

## Lifecycle and integrity expectations retained

- Latest directory **26**, unchanged office map and recorded office/content binding.
- Exactly COR **47/module 1** and ACSA **48/module 2**, with ledger primary region and directory 26; original author, creation timestamp, content and hash retained.
- Preflight requires both **draft / lock 1**, only `version_created`, and original author/history/audit. Review/publication must not already exist.
- Future review/publication history and audit require the ledger's exact `config-reviewer` actor; no permission or identity substitution.
- Valid states remain draft/1, reviewed/2 and published/3. Complete postflight success requires both published after the expected two transitions; coherent partial progress remains BLOCKED.
- Original 16 published mappings and fixed COR/ACSA roots remain protected. Unrelated version digests, business counts and foundation checks are unchanged.

## Static and offline results

| Check | Actual result |
| --- | --- |
| PHP syntax, inspector and bootstrap | **PASS** |
| Scoped Pint `--test`, inspector and bootstrap | **PASS** |
| JavaScript syntax, launcher and reviewer driver | **PASS** |
| Static table/column audit | **PASS**, 27 columns / six tables and eight count-only table names |
| Exact permitted source delta | **PASS**, one lookup literal and attempt-path-only adaptations |
| Extracted pure lifecycle checks | **PASS: 13 checks**, valid states, wrong locks, wrong actor and partial/full acceptance; no Composer, PDO or Laravel |
| Attempt-02 directory absent | **PASS**, not reserved |
| Full `git diff --check` | **FAIL on an unrelated pre-existing edit:** `docs/phase-3b-implementation-plan.md:3` has trailing whitespace. This file was already modified at task entry and was not edited here. |

The extracted lifecycle checks execute only the inspector's pure assertion blocks with fictional in-memory arrays. They do not run the inspector entry point or connect to a database. No live test result is inferred from them.

## Preservation and next-attempt preparation

A fresh focused baseline captured **2,959 files**, including failed attempt-01, prior evidence and all current PDF-named files. Final comparison found exactly the **four authorized tool files changed**, with all remaining baseline files unchanged. All **81 current PDF-named files**, including the original **73**, and the ten historical diagnostic JSON files remain unchanged. Original attempt-01 files and prior reports/logs were not renamed, truncated, restored or overwritten.

The historical identity log's pinned whole-file hash and the documented original 61-record / appended 101-record exception remain preserved. Unrelated Batch G artifacts, application runtime and the user's existing documentation edit are unchanged from the new baseline. The known missing temporary fake-upload metadata remains a prior exception. Private source snapshots, metadata audit, lifecycle checks and preservation results are under `storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-inspector-correction/`.

The next fixed directory is `gate-b2-reviewer-only/attempt-02/`. Atomic reservation, collision refusal, historical hash protection, fixed database guards, actor, IDs, request policy, no-retry behavior and reviewer-only UI workflow are unchanged. No directory was reserved, and the launcher was not invoked.

**Readiness: source correction PASS; live execution BLOCKED pending separate authorization and a successful fresh preflight.** Actual table availability, current draft states, credential acceptance, framework batching and HTTP log isolation remain unverified by this source-only task. No automatic repair or widened request policy is proposed.

**STOP. No database connection, server, authentication, review/publication, migration, PDF, B3/B4, commit, push or deployment occurred.**
