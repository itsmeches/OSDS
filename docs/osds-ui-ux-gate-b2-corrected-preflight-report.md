# Gate B2 — Corrected read-only preflight

**27 October 2026. PASS: one corrected read-only B2 preflight completed. Fixture creation NOT RUN and not authorized by this result.** No accounts, permissions, grants or configurations were written. B3/B4 remain closed; Batch G remains CONDITIONAL CLOSE.

## Authorized helper correction

Changed only `scripts/Prepare-UiUxBrowser.php` as verification tooling:

- Explicitly set projection PDO rows to `PDO::FETCH_ASSOC`.
- Used `SET LOCAL TIME ZONE 'Asia/Manila'` inside the read-only preflight transaction to match historical B0 timestamp strings.
- Retained the complete original `$compatibility['data'] != $old['compatibility']['data']` failure assertion. No canonical-equality replacement or omitted projection field.
- Saved approved observed projections before comparison and before propagating an inspection failure. Added recursive field differences, original/strict equality results and source hashes before the equality assertion.
- Added a fixed, atomically reserved inspection directory that refuses reuse. Previous failed inspection and diagnostic outputs were not touched.
- Recorded and checked the remaining preflight gates, including schema/constraint checks and the closed amended manifest. No creation-path mutation, new permission, schema operation or runtime change was introduced.

The target guard, exact permission-manifest function and namespace function were compared with the saved helper source and remained unchanged. The only approved `applicant.sandbox.publish` recipient remains the future synthetic `osds` identity. The amendment was validated as planned configuration, **not assigned to any account**.

PHP syntax, scoped Pint `--test` and whitespace checks passed before connection. Scoped formatting normalized the verification changes only. The original B0 helper, application timezone configuration, environment files, models, policies and migrations remain unchanged.

Executed helper SHA-256:

`43FE2784C997FD7066BDCB7E08BF4564906D678B75CE3F2876CCB22FAF7E00AB`

## Single execution and identity

```powershell
php scripts/Prepare-UiUxBrowser.php inspect
```

Used the project's portable PHP binary. One connection opened; no retry, fallback or `prepare` invocation occurred.

| Identity / transaction field | Observed | Result |
| --- | --- | --- |
| Configured driver | `pgsql` | PASS |
| Actual database | `osds_ui_browser_test` | PASS |
| Actual host / port | `127.0.0.1:55432` | PASS |
| Backend PID | **20716** | Recorded |
| Identity timestamp | **2026-10-27 09:52:06 UTC** | Recorded |
| Transaction | Explicit `REPEATABLE READ READ ONLY` | PASS |
| Effective read-only before configuration queries | **on** | PASS |
| Projection timezone | **Asia/Manila**, transaction-local | PASS |
| Effective read-only immediately before closure | **on** | PASS |
| Closure | **ROLLBACK** | PASS |
| Timezone after rollback | **UTC** | PASS |

No `osds_dev`, `osds_test` or other database was accessed. Testing credentials were read privately without using the credential file's database name as a connection target. No Laravel bootstrap, authentication or migration was performed. The local timezone adjustment ended with rollback; the connection configuration and future application/write timezone behavior remain UTC.

## Preflight results

| Gate | Status | Actual finding / scope |
| --- | --- | --- |
| Database identity and effective read-only | **PASS** | Fixed browser target, actual endpoint and read-only transaction verified |
| COR publication / definition / template | **PASS** | Fixed roots **198 / 54 / 43**, source form/requirement set **591**, expected module, published states and publication availability |
| ACSA publication / definition / template | **PASS** | Fixed roots **199 / 55 / 45**, source form/requirement set **592**, expected module, published states and publication availability |
| Complete historical B0 projection comparison | **PASS** | All **16 groups / 67 projected rows** compare equal; original equality **true**, PHP strict equality **true**, **0 field differences** |
| Staff preview | **PASS** | Publication **128**, `review_sandbox`, module 1, form/requirement set **466**, approval **137**, no retirement |
| Fixed personnel mappings and completeness | **PASS** | Original B0 projection values unchanged; exact 16 published mapping IDs below |
| Published personnel slot list | **PASS** | **2, 4, 6, 8, 12, 14, 16, 19, 22, 25, 28, 31, 40, 42, 44, 46**; no substitute mapping |
| Namespace | **PASS at snapshot** | `uiux_20261027_a01` absent from inspected user/region/institution/form-definition/issuance-definition names and user emails; fixed email domain also unused |
| Existing permissions | **PASS** | All manifest names already defined under `web`; no permission insertion |
| Amended manifest | **PASS, source/metadata validation** | 18 profiles; enum-backed permission names; only `osds` has `applicant.sandbox.publish`; no `questionnaires.retire` |
| Foundational tables / foreign keys / uniqueness | **PASS, catalogue inspection** | Required tables and primary keys, foreign-key presence for linked tables, all retrieved constraints validated, required active assignment/representation/eligibility indexes present |
| Migration ledger | **PASS** | **27 applied / 27 source**; no pending or database-only names |
| Data creation / model event execution | **NOT RUN** | No fixtures, pivots or audits created; live write success not established |
| Personnel setup / authenticated browser acceptance | **NOT RUN / separately gated** | Outside this authorization |

The schema catalogue inspection recorded **371 constraints** and **201 indexes** across `public`, including 199 foreign-key constraints. These totals cover a broader catalogue than Gate A's selected-table inventory; the different totals are not evidence of schema drift. Constraint definitions and index SQL are retained privately. No mutation probe, insertion trial, full stored-function certification or model/service test was run.

Full projection equality covers the approved metadata, structural fields, stored hashes and personnel completeness flags. It does not expand B0's scope into unselected text/personnel payloads or constitute full payload hash recomputation. Existing source-form/state semantics remain unchanged.

## Intended operations validated, not executed

| Foundation | Planned count |
| --- | --- |
| Fictional accounts | **18** |
| Regions: primary / probe | **2** |
| Institutions: owner / auxiliary / unrelated | **3** |
| Verified effective representation bindings | **3** |
| Eligibility: each institution × COR/ACSA | **6** |
| Regional authority assignments | **12** |
| Exact application reviewer assignments | **20** |
| Explicit account-permission pivots | **154**, using **48 distinct existing permissions** |
| Role assignments / nationwide grants | **0 / 0** |
| Office-directory revisions / personnel versions | **0 / 0** |

The manifest's active `osds` grantor carries the approved additional permission. All six eligibility rows are intended to reference that new identity. The other 17 permission unions are unchanged. Primary/probe and COR-only/ACSA-only scopes remain as approved; the evidence-reviewer profile receives regional authority but no application-review assignment. No evidence-review copy assignment is created before the later real workflow supplies a copy.

The source path uses ordinary model creation, events and explicit permission assignment, without role bundles or borrowing old identities. Existing source dependencies and schema metadata support the intended operation set; actual policies/events, generated IDs and transaction outcomes cannot be certified without the separately authorized creation stage. Planned counts are not created records. No ownership ledger or credentials file exists yet.

## Evidence and preservation

New fixed inspection directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-corrected-preflight/inspection-01/`

Contains `identities.jsonl`, `observed-projections.json`, `projection-comparison.json`, `preflight-inspect.json` and `result-inspect.json`. The final result records every gate, planned operations, constraints/indexes, migration names, source hashes, projection equality and rollback/UTC restoration. Raw metadata stays private.

Parent directory contains HEAD/dirty status, helper-before snapshot, discovered paths, hash baseline and preservation comparison. Baseline HEAD: **`f7625a9de909a991013db6ce43c68bee65402ce0`**.

| Preservation | Actual result |
| --- | --- |
| Existing files | **26,814 of 26,815 unchanged; exactly one authorized changed file**, `scripts/Prepare-UiUxBrowser.php`; zero missing |
| Original baseline PDFs | **73/73 unchanged**; all **81 current PDF-named files** unchanged |
| Historical diagnostic JSON | **10/10 unchanged** |
| Previous B0/B1/B2 reports, attempts, failed inspection and diagnostic records | **Unchanged** |
| Phase 1–3 runtime/evidence and unrelated Batch G artifacts | **Unchanged** |
| Existing logs / compiled Blade caches | **Unchanged**, not cleared or restored |
| Known missing temporary fake-upload metadata | Prior exception retained; not fabricated or reconstructed |

New files are this report and the new task-private baseline/inspection artifacts. No application source, published configuration, historical evidence, PDF, model, permission definition or database row was changed. No new application log/cache output was generated by this standalone inspection.

## Readiness and mandatory stop

**Read-only prerequisites passed at this snapshot. The foundation is ready for review and separately authorized additive fixture creation.** Namespace availability is not reserved, and configuration/identity/dependency checks must be repeated immediately before any future write transaction. Existing immutable roots and old personnel mappings must remain preserved.

No B2 foundation has been created yet. A successful preflight is not write authorization. Office-directory/personnel setup, website login, COR/ACSA journeys, PDF generation, B3 and B4 remain excluded.

**STOP. No prepare mode, fixtures, permission pivots, migrations, seeders, browser execution, PDFs, commit, push or deployment occurred. Await explicit authorization for database writes.**
