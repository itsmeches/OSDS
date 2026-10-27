# Gate B2 — Additive foundation creation

**27 October 2026. PASS: one controlled foundational creation attempt committed and was verified.** Namespace `uiux_20261027_a01` now has the approved additive synthetic foundation. No office-directory/personnel configuration or browser workflow was created. B3/B4 remain NOT AUTHORIZED; Batch G remains CONDITIONAL CLOSE.

## Execution and pre-write checks

Executed once using the project's portable PHP binary:

```powershell
php scripts/Prepare-UiUxBrowser.php prepare
```

Fixed attempt: **`b2-foundation-attempt-01`**. The helper reserved a new non-overwriting evidence directory and a fixed write-attempt marker. It did not reuse earlier inspection outputs or select a fallback attempt number.

| Pre-write gate | Actual result |
| --- | --- |
| Configured target | **PASS:** `pgsql`, `127.0.0.1:55432`, `osds_ui_browser_test` |
| Actual identity | **PASS:** all four observations matched; two distinct backend PIDs |
| COR roots | **PASS:** publication **198**, definition **54**, template **43**, exact source bindings |
| ACSA roots | **PASS:** publication **199**, definition **55**, template **45**, exact source bindings |
| Original complete B0 projections | **PASS:** original equality **true**, PHP strict equality **true**, zero field differences across 16 groups |
| Staff preview | **PASS:** publication **128**, expected form/requirements **466**, approval **137**, unretired `review_sandbox` |
| Published personnel | **PASS:** original 16 mapping IDs and projected linkage/completeness; no substitute mappings |
| Namespace | **PASS before writes:** unused, then rechecked inside the write transaction |
| Existing permissions and amended manifest | **PASS:** existing enum-backed definitions, 18 exact profiles, only `osds` receives `applicant.sandbox.publish` |
| Schema/FKs/uniqueness | **PASS:** required catalogue checks and constraints validated |
| Migration ledger | **PASS:** 27 source / 27 applied; no migration executed |
| Protected snapshots | **PASS:** matched again immediately before writes |

The repeated preflight used an explicit repeatable-read read-only transaction, associative rows and transaction-local Asia/Manila representation for the historical comparison. That transaction ended with **ROLLBACK**, restoring UTC. The subsequent creation transaction was separate and ended with **COMMIT**. The result JSON's `transaction_closed=ROLLBACK` describes preflight; `transaction=COMMIT` describes fixture creation.

| Identity observation | UTC | Database / endpoint | Backend PID |
| --- | --- | --- | --- |
| Before Laravel bootstrap | 09:59:48 | `osds_ui_browser_test`, `127.0.0.1:55432` | **35296** |
| Guarded Laravel connection creation | 09:59:48 | Same approved target | **20704** |
| Before write transaction | 09:59:48 | Same approved target | **20704** |
| After inserts/verification, before commit | 09:59:53 | Same approved target | **20704** |

No `osds_dev`, `osds_test`, other database or unexpected alias was accessed. Environment files and cached configuration were not changed. No migration, reset, truncation, destructive test lifecycle or broad seeder ran.

## Transaction and observed records

The write path used ordinary Eloquent model creation and explicit existing-permission assignment, with normal model events and audit observers enabled. No event suppression, SQL fixture insertion, role-bundle synchronization, existing real grantor or authenticated website session was used. Audit actors retain the normal unauthenticated fixture-context behavior; no staff identity was impersonated.

The bounded transaction used statement/lock timeouts and a task advisory lock. Namespace and protected snapshots were checked before the first insert. Fresh database reads verified identities, exact permissions and stored relationships before commit; the same verification ran again after commit. The commit returned successfully. No uncertain-commit recovery or retry was needed.

| Record category | Approved | Verified before commit | Verified after commit |
| --- | --- | --- | --- |
| Fictional accounts | 18 | **18** | **18** |
| Regions | 2 | **2** | **2** |
| Institutions | 3 | **3** | **3** |
| Representation bindings | 3 | **3** | **3** |
| Module eligibility grants | 6 | **6** | **6** |
| Regional authority assignments | 12 | **12** | **12** |
| Exact application reviewer assignments | 20 | **20** | **20** |
| Direct permission pivots | 154 | **154** | **154** |
| Role assignments / nationwide grants | 0 / 0 | **0 / 0** | **0 / 0** |

Normal model events produced **58 audit records**. Their IDs, action and resource linkage are recorded privately. The transaction's row-version inventory checked all public tables against the expected table/count set: **64 foundational records + 154 permission pivots + 58 audits = 276 rows**. No other table had a row version from this transaction. This is an operation-scoped observation, not a guarantee about unrelated concurrent transactions or sequence counters.

## Verified ownership, scope and permissions

All account names and region/institution labels use the approved namespace; emails use the fixed `.invalid` namespace domain. Every account is synthetic. Seventeen are active; the dedicated inactive profile has the existing `inactive` state. Passwords are freshly random and remain private.

The actual direct permissions of each fresh account matched its complete amended manifest exactly, using **48 existing permissions**. No permission definition or role was created. `applicant.sandbox.publish` was assigned **only to the new active `osds` account**; all six eligibility rows reference that account as grantor. No `questionnaires.retire` or nationwide authority was assigned.

Source-level navigation expectation: the additional `osds` permission also allows its existing **Applicant sandbox eligibility** resource. This expected visibility is recorded for later browser acceptance; no authenticated navigation was tested here. The other 17 permission unions remain unchanged.

Relationships verified from actual stored rows:

- Two new regions: primary for successful routing and probe for wrong-region checks.
- Owner, auxiliary and unrelated institutions are synthetic, with the primary region captured consistently.
- The main applicant represents owner and auxiliary; the unrelated applicant represents only the unrelated institution. All three representations are verified and effective.
- Each institution has one COR and one ACSA eligibility grant, with the same synthetic `osds` grantor.
- Twelve staff profiles have their planned primary/probe regional authority. Wrong-region receives only probe authority. COR-only and ACSA-only reviewer grants remain restricted to their respective modules.
- The evidence reviewer has regional authority but no application-review assignment. No evidence-copy assignment was fabricated before a real review copy exists.
- Grant dates, grantors, target IDs and relationship fields matched the creation ledger. Dates are fixed from preparation time, starting one day earlier and ending 30 days later; exact timestamps are private.

## Ownership ledger and evidence

New append-only attempt directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation-creation/attempt-01/`

Actual IDs and relationships:

`attempt-01/write-attempt/ownership-ledger.json`

The ledger contains account/region/institution IDs, module IDs, exact direct permissions and permission IDs, representation/eligibility/regional/reviewer rows, effective dates, audit IDs and confirmed commit status. A pre-commit pending ledger is retained separately. Credentials are in **`attempt-01/write-attempt/credentials.json`**, not in public documentation, shell arguments or the ownership report.

The attempt also retains fixed start markers, identity observations, observed projections, equality/difference results, complete preflight metadata and `result-prepare.json`. No previous B0/B1/B2 log or manifest was appended to or replaced.

## Protected configurations and files

Protected row digests matched before the transaction, immediately before writes, after inserts and after commit. The snapshot includes all three protected publications, the two issuance definitions, templates and every existing personnel version, all existing office-directory revisions and the listed immutable source configurations.

Specifically: publications **198/199/128**, definitions **54/55**, templates **43/45**, all **16 existing published personnel mappings**, and the wider protected snapshot remain unchanged. No office-directory revision or personnel version was created. Directory preservation covered all **25** existing revisions; version preservation covered **22** selected existing template/personnel rows, including nonpublished personnel versions.

### Verification-only helper adjustments

Only `scripts/Prepare-UiUxBrowser.php` changed as verification tooling. The preparation mode now routes all evidence into this new fixed attempt directory, records a write marker, and performs actual pre/post-commit checks plus transaction-row inventory. The exact target guard, permission manifest and namespace functions were compared with their saved source and remained unchanged.

Full-row digest snapshots are explicitly taken in **UTC**, matching the later write connections. This avoids a second timestamp-representation mismatch when comparing full-row JSON digests. The separate complete B0 projection comparison still uses its reviewed transaction-local Asia/Manila representation; its equality requirement was not changed. Application/write timezone remains UTC.

PHP syntax, scoped Pint `--test` and whitespace checks passed; existing CRLF advisories remain. These helper adjustments did not modify application models, services, workflow rules, policies, database schema or test expectations.

| Filesystem preservation | Actual result |
| --- | --- |
| Pre-stage baseline | **26,828 files**, HEAD `f7625a9de909a991013db6ce43c68bee65402ce0`, dirty tree recorded |
| Existing files after creation | **26,827 unchanged; one authorized helper changed; zero missing** |
| Original PDFs | **73/73 unchanged**; all **81 current PDF-named files** unchanged |
| Historical diagnostic JSON | **10/10 unchanged** |
| All previous reports, attempt logs, identity/inspection/preservation evidence | **Unchanged** |
| Phase 1–3 implementation/evidence and Batch G artifacts | **Unchanged** |
| Existing application logs / compiled Blade caches | **Unchanged** |
| Known missing temporary fake-upload metadata | Prior exception retained; not reconstructed |
| Unexpected generated files outside this stage directory | **0**, before adding this report |

The new files are task-private baseline/attempt/ledger/credential outputs and this report. No PDF, browser session or application cache artifact was generated. Private preservation records and the helper-before snapshot are in the parent `gate-b2-foundation-creation/` directory.

## Readiness and mandatory stop

**Gate B2 foundational fixture creation is complete and verified.** There are no observed creation discrepancies requiring repair. Fixtures are retained for review; no cleanup or grant revocation was performed.

The foundation is available for the next **separately authorized** fictional office/personnel setup and later authenticated acceptance. New matching personnel versions still do not exist. Actual login, role-specific navigation, COR/ACSA application journeys, accessibility captures, PDF generation/download and full-suite B4 regression remain unexecuted by this stage.

**STOP. No office-directory/personnel creation or publication, website login, application/correction/decision/request, PDF generation, B3/B4, commit, push or deployment occurred. Await explicit authorization for the next stage.**
