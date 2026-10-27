# Gate B1 — Targeted PostgreSQL regression

**27 October 2026. Overall result: FAIL; remaining targeted checks BLOCKED after the first failure.** One authorized attempt executed against `osds_test` only. No retry, assertion change or application fix was performed. Gate B2, B3 and B4 remain NOT AUTHORIZED. Batch G remains CONDITIONAL CLOSE.

## Actual execution

Implemented only the two approved verification scripts:

- `scripts/UiUxAcceptanceBootstrap.php` — pre-Laravel configuration validation, fixed testing environment, PostgreSQL connection resolver and actual identity logging.
- `scripts/Run-UiUxRegression.php` — fixed nine-file targeted selection, direct PHPUnit process with 512 MB, stop on first failure/error, no result-cache write and refusal to overwrite an earlier attempt.

The runner accepts exactly `targeted`. A file-only negative check of `full` returned exit **2**, before database bootstrap. No full suite ran. The existing read-only Gate A/B0 helper was unchanged.

Executed with the project's portable PHP binary:

```powershell
php -d memory_limit=512M scripts/Run-UiUxRegression.php targeted
```

Its child command was equivalent to:

```powershell
php -d memory_limit=512M vendor/phpunit/phpunit/phpunit --bootstrap scripts/UiUxAcceptanceBootstrap.php --do-not-cache-result --stop-on-failure --stop-on-error tests/Feature/DashboardGuidanceTest.php tests/Unit/ApplicantApplicationGuidanceTest.php tests/Feature/FormMakerTest.php tests/Feature/QuestionnaireConfigurationTest.php tests/Feature/ApplicantSandboxTest.php tests/Feature/ApplicationDecisionFoundationTest.php tests/Feature/CaseIssuanceRequestTest.php tests/Feature/DemonstrationDocumentTest.php tests/Feature/DownloadableFormsTest.php
```

The private execution record contains the actual absolute paths and exit code. No credentials were supplied on the command line.

| Result | Actual observation |
| --- | --- |
| Selected files | All **9** approved files exist; none silently omitted |
| Historical diagnostic writers | None found in selected test sources; reviewed support fixtures and existing file writes separately |
| PHPUnit exit | **1** |
| Executed tests | **1** |
| Passed / failed | **0 / 1** |
| Assertions | **5** |
| PHPUnit duration | **4,691 ms** |
| First test | `DashboardGuidanceTest::test_combined_stage_permissions_share_one_destination` |
| Remaining selection | **BLOCKED / not reached** by stop-on-failure |

The remaining three dashboard tests and the other eight files were not executed. No historical PostgreSQL checkpoint is substituted for their missing results. There is no current-tree targeted or full-regression PASS.

## Failure diagnosis and proposed correction

The failure is at `tests/Feature/DashboardGuidanceTest.php:58`:

```php
Livewire::test(TaskOrientation::class)->assertSee('Chief')
    ->assertSee('technical evaluation')->assertSee('final demonstration approval');
```

Actual authenticated Livewire rendering succeeded and included one intake destination with all three responsibility descriptions. The preceding assertions checked one group, one destination, three responsibility entries and the existing intake URL. The first text assertion then failed because **“Chief” is absent from the rendered wording**.

The implemented recommendation description in `TaskOrientation.php` is:

> Review the evaluation, recommend approval or return the case for reevaluation when the current stage permits it.

The rendered output also includes technical-evaluation and final-demonstration-decision descriptions, plus the case-wide different-identities notice. This points to a **test/content expectation mismatch**, rather than a database, migration or widget-rendering failure. It does not establish broader authorization, browser accessibility or workflow correctness.

**Proposed for separate review, not implemented:** align this test with the approved responsibility wording by asserting the complete recommendation description, while retaining the existing destination/group/count assertions and evaluation/Director checks. Review whether the intended product requirement instead calls for explicit role labels before choosing a test-only correction. Do not remove combined-permission coverage or add a runtime label merely to obtain a pass. A corrected test and another destructive targeted attempt require explicit authorization; this runner deliberately refuses automatic reuse of its attempt log.

## Database identity and isolation evidence

The bootstrap parsed testing credentials privately without Laravel bootstrap and rejected unexpected configured driver, endpoint, database, URL, read/write configuration, direct configuration, PDO options and unknown connection aliases. It refused cached configuration and relevant ambient PostgreSQL/connection-cache overrides. It did not change `.env` or `.env.testing`.

Fixed process values included `APP_ENV=testing`, `DB_CONNECTION=pgsql`, host/port/database below, array cache/sessions/mail and synchronous queue. A direct preflight connection established actual identity before Laravel bootstrap. Each subsequent Laravel PostgreSQL PDO was created through the verification resolver, checked before connection and identity-verified before being returned to Laravel. The same resolver applies to reconnect-created connections. Non-PostgreSQL connections are rejected. Known timestamp aliases retain their configuration/timezone while remaining subject to the same endpoint/database checks.

| Connection | Time UTC | Actual database | Actual host | Actual port | Backend PID | Identity |
| --- | --- | --- | --- | --- | --- | --- |
| Pre-Laravel preflight | 08:16:14 | `osds_test` | `127.0.0.1` | 55432 | 15156 | PASS |
| Laravel connection | 08:16:15 | `osds_test` | `127.0.0.1` | 55432 | 24520 | PASS |
| Laravel connection | 08:16:17 | `osds_test` | `127.0.0.1` | 55432 | 34308 | PASS |

All **3/3 observed connections passed**. Only the approved test database was opened. No `osds_dev`, browser-test database or other database connection was attempted by this execution. No browser server, fixture preparer or login ran.

The existing RefreshDatabase lifecycle was allowed to recreate the disposable test schema and perform test fixture DML. No database rollback-to-prior-records is claimed; `osds_test` remains disposable after this stage. Browser publications, definitions, templates and personnel mappings were not accessed or changed.

Alias compatibility, reconnect denial and the intentional blank-download configuration-name probe are implemented guard boundaries but **were not exercised by the stopped test run**. The guard is verification-process protection, not a security sandbox for arbitrary malicious PHP opening raw PDO connections. Source inspection found no raw PDO connection or database-worker launch in the selected test paths. No universal external-process/database-access certification is claimed.

## Preservation and file checks

Pre-execution Git HEAD was **`f7625a9de909a991013db6ce43c68bee65402ce0`**, with the existing dirty tree recorded. The baseline includes **26,683 existing files**, covering ignored dependency/private files as well as source, and all **73 PDFs**. `.git` internals were excluded from file hashing; HEAD/status were recorded separately. The newly created B1 tooling and task-private output directory were excluded from this existing-file baseline and inventoried separately.

The first PowerShell hash scan was too slow and was stopped before any database execution. A private PHP hash utility completed the replacement baseline before testing. Its initial working-directory error was corrected during file-only preparation; it opened no database. No test attempt or destructive operation was retried as a result of that file-tool correction.

| Preservation / check | Actual result |
| --- | --- |
| All baseline files | **PASS: 26,683 unchanged; zero changed or missing** |
| Original PDFs | **PASS: 73/73 byte-identical** |
| Historical evidence JSON | **PASS: 10/10 byte-identical**, individually compared with private pre-test backups |
| Phase 1–3, pending remediation, Gate A/B0 source/reports/logs | **PASS**, included in baseline comparison |
| Unrelated Batch G script/artifacts | **PASS**, unchanged |
| `.env`, `.env.testing`, application/runtime/tests | **PASS**, unchanged |
| New PHP scripts | **PASS**, syntax checks and scoped Pint `--test` |
| Whitespace | **PASS**, tracked diff; existing CRLF advisories remain |

No original PDF or historical JSON needed restoration: none changed. No new test PDF was generated because the document tests were never reached. Before this report was added, the new-file inventory outside the private B1 directory contained only the two approved scripts. This report is the third new nonignored deliverable. Existing project files were not rewritten.

Private records under `storage/app/private/uiux-consolidated-acceptance/gate-b1/` include:

- `head.txt`, `git-status.txt`, `git-status-after.txt`.
- `paths.txt`, `baseline-fast.json`, `preservation.json`.
- `historical-json/` and `historical-preservation.json`.
- `targeted.txt`, `execution.json`, `identities.jsonl`.
- `helper-hashes.json`, `new-files.json`, and private file-hashing utility.

The failure log retains the actual synthetic rendered widget and trace privately. No credentials or unrelated applicant records are included in this report. No safe browser screenshot package was produced because browser execution was not authorized.

## Stopping point

Gate B1 has produced a real failure requiring review. No correction or retry was made. The rest of the targeted regression is BLOCKED, and consolidated Phase 1–3 technical acceptance remains incomplete.

No runtime code, permissions, workflow, migration definition or test assertion was changed. No browser fixture/account, website login, existing published-configuration mutation, browser PDF generation, full suite, B2/B3/B4 execution, commit, push or deployment occurred.

**Stop for review of the failed test and proposed expectation correction. Further execution requires explicit authorization.**
