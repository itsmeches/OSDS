# Gate A — Read-only database inventory

**Latest status — controlled continuation: PASS.** Both approved databases passed strict identity verification and completed the read-only inventory on 27 October 2026. Both transactions ended with ROLLBACK. No pending migrations were found. Browser configuration slots are occupied; Gate B remains **NOT AUTHORIZED**. Current findings appear in the continuation section below.

## Initial attempt — retained historical record

The following initial-attempt account is retained as historical evidence. Its BLOCKED outcome and statements about an unexecuted helper describe that earlier attempt, not the successful continuation below. The original report is also preserved privately, byte-for-byte.

**27 October 2026. Overall outcome: BLOCKED at the first database identity check. Gate B remains NOT AUTHORIZED. No retry or second database connection was attempted after the stop condition.**

## Execution and identity results

Created `scripts/Run-UiUxAcceptance.php` as a standalone Gate A inventory helper. It accepts only the `inventory` operation and the exact database names `osds_test` / `osds_ui_browser_test`. It reads testing credentials privately, validates PostgreSQL and `127.0.0.1:55432` before connecting, ignores ambient target/default DSNs, and does not bootstrap Laravel. No migration, preparation, test or Gate B mode exists in the helper.

Executed once:

```powershell
php scripts/Run-UiUxAcceptance.php inventory --database=osds_test
```

The connection opened and accepted `BEGIN TRANSACTION ISOLATION LEVEL REPEATABLE READ READ ONLY`. Its DSN also specified default read-only transactions, a connection timeout and statement/lock timeouts. The first SELECT compared actual database, server address, server port and read-only settings with the approved values. The strict comparison failed. The helper rolled back the transaction, recorded BLOCKED and exited nonzero.

| Check | Actual outcome |
| --- | --- |
| Preconnection operation/database/host/port allowlist | **PASS** for this invocation: explicitly configured `pgsql`, `osds_test`, `127.0.0.1:55432`; no fallback target |
| PostgreSQL connection | **PASS:** PDO connection opened |
| Explicit read-only transaction command | **PASS:** accepted; subsequently closed with ROLLBACK |
| Actual database/server/read-only identity gate | **FAIL:** strict comparison returned a mismatch; full identity could not be established |
| Schema/migration/configuration inventory for `osds_test` | **BLOCKED:** never started |
| `osds_ui_browser_test` connection and inventory | **BLOCKED / not attempted:** execution stopped after the first identity failure, as authorized |
| Existing records modified | **NONE:** no DDL, INSERT, UPDATE, DELETE, fixture, login, retirement or withdrawal operation executed |
| Source/artifact preservation | **PASS:** all 2,781 baseline files and all 71 baseline PDFs unchanged |

The failed identity comparison is an **inventory gate failure**, not an application test failure. No database-backed tests were run.

## Diagnostic limitation and helper correction

The initial helper retained the observed identity row only after a successful comparison. Consequently, the failed attempt's log does not contain the returned values, and **the specific mismatching field cannot be established from that attempt**. Do not infer that a particular database, server address, port or read-only setting was wrong.

The first version also compared `inet_server_addr()::text` directly with a bare IP string. The current helper uses `host(inet_server_addr())` to compare the canonical host address and records the non-sensitive observed identity row **before** validating it. This removes an address-format dependency and improves diagnostics; it does not establish the cause of the first failure. The approved host, port, databases and read-only requirements remain unchanged.

**The corrected helper has not been re-executed against a database.** The stop condition was respected. A successful retry is not claimed.

The initial helper source and attempt log are retained privately:

- `storage/app/private/uiux-consolidated-acceptance/gate-a/inventory-helper-first-attempt.php`
- `storage/app/private/uiux-consolidated-acceptance/gate-a/osds_test-20261027-060701-1f1521.json`

The attempt was recorded at **2026-10-27 06:07:01 UTC**. The log contains the approved configured target, connection-opened flag, ROLLBACK outcome and failure reason; it contains no credential, applicant answer or evidence locator.

## Migration and schema findings

| Requested inventory | `osds_test` | `osds_ui_browser_test` |
| --- | --- | --- |
| Current applied migration ledger | **BLOCKED:** not queried | **BLOCKED:** not connected |
| Pending / database-only migrations | **UNKNOWN** | **UNKNOWN** |
| Relevant tables and columns | **UNKNOWN** | **UNKNOWN** |
| Indexes, constraints and coherence/immutability triggers | **UNKNOWN** | **UNKNOWN** |
| B1/B2/B3 and downloadable-form schema compatibility | **NOT ESTABLISHED** | **NOT ESTABLISHED** |
| COR / ACSA reference availability | **UNKNOWN** | **UNKNOWN** |

Local migration definitions remain source evidence only. No conclusion about running-schema currency or compatibility can be drawn from this incomplete Gate A attempt.

## Configuration slots and namespace

No configuration rows were retrieved. Existing active intake publications, published issuance definitions, published templates and published personnel mapping IDs are **unknown** in both databases. No exact retirement/withdrawal approval can be prepared yet.

Availability of proposed fixture namespace `uiux_20261027_a01` is **unknown**. No namespace scan, fixture creation, account provisioning or ledger reuse occurred. No configuration was retired or withdrawn.

## Preservation and verification

Before helper creation, Git state/HEAD and 2,781 existing file hashes were recorded under `storage/app/private/uiux-consolidated-acceptance/gate-a/`. HEAD remains `f7625a9de909a991013db6ce43c68bee65402ce0` with the pre-existing dirty working tree preserved.

The comparison in private `gate-a/preservation.json` confirms:

- **2,781 baseline files byte-identical**, zero missing files and zero modified baseline files.
- **71 baseline PDFs byte-identical**.
- Existing application source, Phase 1–3 changes, pending accessibility remediation, historical reports/evidence, the ten published evidence JSON files, source artifacts and the unrelated Batch G script preserved.

New files for this task are the inventory helper, this report and ignored private baseline/log/diagnostic artifacts. The helper alone received scoped Pint formatting and passed PHP syntax validation. These are file-based checks, not regression or schema verification.

Only the approved `osds_test` DSN was opened in this attempt. No connection to `osds_dev`, `osds_ui_browser_test` or another database was attempted. No Laravel bootstrap, test suite, migration, seeder, schema reset, application activation, commit, push or deployment occurred. Batch G remains CONDITIONAL CLOSE.

## Blockers and recommended next authorization

1. **Identity verification is unresolved.** Review this blocked attempt and authorize a narrow Gate A continuation using the corrected diagnostic helper. Its first connection should again be `osds_test`; stop if that identity check fails.
2. Only after the first target verifies successfully should the helper connect to `osds_ui_browser_test`, under the same original allowlist and read-only restrictions.
3. Finish the read-only migration/schema/slot/namespace inventory. If an actual identity mismatch or unavailable database is reported, stop again; do not adjust the allowed targets or fall back to another database.
4. **Gate B cannot be concretely recommended yet.** Pending migration names, occupied configuration IDs, namespace suitability and browser fixture prerequisites have not been observed. Do not approve or run destructive schema replacement, forward migrations, preparation or retirement based on assumptions.

If Gate A later succeeds, prepare the separately reviewed Gate B operations specified by the consolidated plan: explicitly disposable `osds_test` regression preparation; only itemized browser forward migrations/additive fixtures; exact-ID configuration disposition if needed; and bounded evidence/PDF handling. None is authorized now.

**Stop after this blocked Gate A report. Await review before any further database connection.**

## Continuation results — 27 October 2026

### Preparation, scope and helper provenance

The user explicitly authorized one controlled continuation: first `osds_test`, then `osds_ui_browser_test` only after successful completion of the first inventory. Each target was attempted once. There was no automatic retry or fallback.

Before execution, a new baseline recorded Git state and **2,783 file hashes**, including 71 PDFs. The original failed-attempt JSON and original helper snapshot remained unchanged. The previous report was copied to private `gate-a/continuation/initial-report-before-continuation.md` before this report update.

| Preserved artifact / helper | SHA-256 |
| --- | --- |
| Original failed-attempt JSON | `7BF35C7146DEF8F5DB731B4529A9A22642B01DFD78500E1AA806228E9426DD0F` |
| Original first-attempt helper snapshot | `00C02028CA866A859D65D67C0BA1D50634648B92FE83B232A8A49AF33F8E247E` |
| Corrected helper before continuation diagnostics | `4817D2F8448336D6C2A65DEEFDCB76AABAA1F10CC4797C6FF4E81E87D0FF163B` |
| Helper actually executed for both targets | `3315A5526187C97A72E56E5CBCBA7A2605B06B572142EC5BD3F537F112D375CF` |

The verification helper was adjusted before execution to retain explicit field-by-field comparisons and identify mismatching fields in a failure. It retained canonical `host(inet_server_addr())` comparison. An unneeded permission-definition row scan was removed to keep this continuation within the explicitly listed metadata scope. No expected database, endpoint or read-only requirement was weakened. Scoped Pint and PHP syntax validation passed before connection. The executed helper snapshot and hash are stored privately under `gate-a/continuation/`.

The helper still accepts only `inventory --database=osds_test` or `inventory --database=osds_ui_browser_test`, with PostgreSQL `127.0.0.1:55432`. It has no migration, fixture, login, test, retirement, withdrawal or application-mutation operation and does not bootstrap Laravel. It uses testing credentials privately without exposing them or loading the local application's default connection.

### Configured and observed identities

Executed sequentially:

```powershell
php scripts/Run-UiUxAcceptance.php inventory --database=osds_test
php scripts/Run-UiUxAcceptance.php inventory --database=osds_ui_browser_test
```

| Compared field | Approved expectation | Observed `osds_test` | Observed `osds_ui_browser_test` | Result |
| --- | --- | --- | --- | --- |
| Driver | `pgsql` | `pgsql` | `pgsql` | PASS / PASS |
| Database | Exact command target | `osds_test` | `osds_ui_browser_test` | PASS / PASS |
| Server address | `127.0.0.1` | `127.0.0.1` | `127.0.0.1` | PASS / PASS |
| Server port | `55432` | `55432` | `55432` | PASS / PASS |
| Effective transaction read-only | `on` | `on` | `on` | PASS / PASS |
| Default transaction read-only | `on` | `on` | `on` | PASS / PASS |

Additional observations, not substituted identity criteria: both connections reported schema `public` and PostgreSQL **17.11**. Both reported effective read-only `on` again immediately before transaction closure, then completed with **ROLLBACK**.

Private raw inventory logs:

- `gate-a/osds_test-20261027-062908-823b54.json` — recorded 06:29:08 UTC.
- `gate-a/osds_ui_browser_test-20261027-062913-33025c.json` — recorded 06:29:13 UTC.

Paths above are relative to `storage/app/private/uiux-consolidated-acceptance/`. They contain metadata and necessary identifiers, not passwords, applicant answers, document locators, personnel names or unrelated personal records. The successful continuation does **not** retrospectively identify the exact field that failed in the original attempt; that observation was not retained then.

### Applied migrations and observed schema

| Inventory finding | `osds_test` | `osds_ui_browser_test` |
| --- | --- | --- |
| Local source migrations | 27 | 27 |
| Applied migration records | 27 | 27 |
| Pending source migrations | None | None |
| Database-only migration names | None | None |
| Missing tables from the inspected allowlist | None | None |
| Inspected column records | 369 | 369 |
| Inspected indexes | 104 | 104 |
| Inspected constraints | 200 | 200 |
| Inspected noninternal triggers | 33 | 33 |
| Unvalidated inspected constraints | 0 | 0 |
| Inspected triggers not normally enabled | 0 | 0 |

Observed objects include the B1 final-decision/stage binding and immutable-submission guards, terminal-inclusive application uniqueness, B2 request/snapshot guards and original-request uniqueness, B3 document/attempt/history/version guards, and downloadable-form tables. Relevant examples:

- `applications_state_check` includes draft, submitted, under_screening, correction_requested, resubmitted, approved and rejected.
- `one_final_decision_per_application` and `b1_final_commit` / `b1_stage_binding` are present.
- `applications_one_active_configuration` includes approved/rejected terminal cases, preserving the existing no-reapplication boundary.
- `issuance_one_current_definition`, `issuance_one_original_request`, `issuance_request_guard` and `issuance_snapshot_binding` are present.
- `b3_published_version`, `b3_document_guard`, `b3_attempt_required` and append-only attempt/history guards are present.

**PASS for the observed schema/migration inventory.** This does not certify stored-function bodies, migration-file checksums, every historical row's coherence, application authorization or runtime behavior. No mutation-based constraint probe or regression test was executed. Current findings do not justify any new forward migration in either database.

### COR/ACSA references and slots

`osds_test` currently has **no `registration` or `acsa` module rows** and no matching slots. This is a reference-fixture gap, not a missing schema migration. Future regression fixtures may populate these under Gate B; no seeder ran during Gate A.

`osds_ui_browser_test` has both active module references: **COR `registration` ID 1**, **ACSA `acsa` ID 2**.

The following browser slots are occupied:

| Module / slot | Existing ID | Relevant metadata | Impact on fresh configurations |
| --- | --- | --- | --- |
| COR applicant intake publication | **198** | Form version 591; requirement-set version 591; approval 264; currently within date window | Must not publish another active COR applicant configuration without an approved disposition of 198. |
| ACSA applicant intake publication | **199** | Form version 592; requirement-set version 592; approval 265; currently within date window | Same restriction for ACSA publication 199. |
| COR staff-preview publication | **128** | Form/requirement-set 466; approval 137; unretired | Separate review-sandbox channel. Leave untouched unless a later scenario specifically needs that slot and receives approval. |
| COR issuance definition | **54** | Published version 45; source intake form 591; approval 266 | Matches publication 198's form ID at the metadata level. Blocks a second published definition for the module. |
| ACSA issuance definition | **55** | Published version 10; source intake form 592; approval 267 | Matches publication 199's form ID at the metadata level. Blocks a second published definition for the module. |
| COR PDF template | **43** | Published version 19; module-wide template slot | Blocks another published COR template until an explicitly approved withdrawal. |
| ACSA PDF template | **45** | Published version 7; module-wide template slot | Blocks another published ACSA template until an explicitly approved withdrawal. |

Matching intake/definition form IDs make reuse a **candidate**, not proof that their content, bindings or evidence requirements fit new fixtures. No definition JSON, applicant values or template/personnel content was retrieved in this inventory.

Existing published personnel mappings are scoped by module and region:

| Module | Mapping ID → region ID / directory revision ID |
| --- | --- |
| COR | 2 → 370/10; 4 → 373/11; 6 → 374/12; 8 → 375/13; 12 → 377/15; 14 → 378/16; 16 → 379/17; 19 → 380/18; 22 → 381/19; 28 → 383/21; 40 → 387/24; 44 → 389/25 |
| ACSA | 25 → 382/20; 31 → 384/22; 42 → 387/24; 46 → 389/25 |

There are **16 published personnel mappings** and two published templates in the inspected browser module slots. A genuinely new synthetic region can have its own personnel mapping without withdrawing these old region-specific mappings. Do not bulk-withdraw them or assume that a mapping identifies an approved real signatory.

### Namespace availability

For **both** databases, the proposed prefix `uiux_20261027_a01` had zero matches in the inspected user name/email predicates, institution names, region codes/names, form-definition names and issuance-definition titles. Only matching IDs would have been returned; no personal field values were returned.

**PASS at the inventory snapshot.** This is not a namespace reservation or a guarantee against later concurrent fixture creation. Recheck before any separately authorized preparation. No fixtures or accounts were created.

### Preservation after continuation

The original failed log and first-attempt helper snapshot retain the hashes shown above. Of **2,783 baseline files**, exactly two changed: the verification helper's diagnostic/scope adjustment and this authorized report update. The other **2,781 files are byte-identical**, with zero missing files. The final comparison is stored privately as `gate-a/continuation/preservation.json`.

All **71 baseline PDFs** are preserved. Application runtime source, Phase 1–3 implementation and evidence, pending accessibility remediation, the ten published historical evidence JSON files, source artifacts and the unrelated Batch G script remain unchanged. No diagnostic test writer was executed.

No schema/row/application change, migration, seeder, fixture, login, database-backed test, retirement, withdrawal, reset, activation, commit, push or deployment occurred. Only the two approved databases were connected to, once each in this continuation; **`osds_dev` was not accessed**. Batch G remains CONDITIONAL CLOSE.

### Remaining blockers and Gate B requirements for later review

Gate A's connection and inventory blockers are resolved. **Consolidated runtime acceptance is still BLOCKED by Gate B authorization**, not by pending migrations.

For later Gate B review, the actual inventory supports these bounded choices:

1. Explicitly authorize disposable `osds_test` schema replacement/fixture DML required by RefreshDatabase and the targeted/full suite. Its absent COR/ACSA references must be supplied through the authorized test fixture process, not a new migration.
2. No browser schema migration is currently indicated. Use additive new namespace fixtures and individually granted permissions; preserve existing records and role bundles.
3. Decide whether compatible existing intake/definition/template configurations will be reused read-only, or fresh independent published configurations are required. For fresh ones, the identified potential old-slot dispositions are applicant publications **198/199**, issuance definitions **54/55**, and PDF templates **43/45**. These are candidate exact IDs for explicit approval, **not authorized changes or a recommendation to withdraw all of them automatically**. Recheck states/IDs before acting. Staff-preview publication 128 and all 16 old personnel mappings can remain untouched under the proposed fresh-region approach.
4. Existing configuration reuse needs an authorized UI/content compatibility review before fixture preparation assumes it is suitable. If neither reuse nor required exact-ID disposition is approved, mark the affected publication/generation scenarios BLOCKED rather than bypassing the slots.
5. Preserve the consolidated plan's separate approvals for synthetic account/login/workflow operations, newly generated PDF fault/recovery probes, temporary diagnostic-writer output restoration and scoped cleanup. No blanket browser reset or deletion of immutable history is justified.

These are **requirements for a later proposal**, not a request to execute Gate B now. Actual current-tree tests, authenticated pages, roles, workflows, PDF rendering/download behavior and human accessibility acceptance remain unverified by Gate A.

**Gate A continuation complete. Stop for review. Gate B remains NOT AUTHORIZED.**
