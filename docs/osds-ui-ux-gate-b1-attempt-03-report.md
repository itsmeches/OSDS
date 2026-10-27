# Gate B1 — Attempt 03

**27 October 2026. `b1-attempt-03`: PASS — Gate B1 targeted regression passed.** All nine approved files completed in one execution. This is not full-application, browser, production or deployment approval. Gate B2, B3 and B4 remain NOT AUTHORIZED. Batch G remains CONDITIONAL CLOSE.

## Execution and actual results

Executed once using the project's portable PHP binary:

```powershell
php -d memory_limit=512M scripts/Run-UiUxRegression.php targeted
```

The runner invoked PHPUnit directly with the private attempt bootstrap, `memory_limit=512M`, `--do-not-cache-result`, `--stop-on-failure`, `--stop-on-error` and exactly:

1. `tests/Feature/DashboardGuidanceTest.php`
2. `tests/Unit/ApplicantApplicationGuidanceTest.php`
3. `tests/Feature/FormMakerTest.php`
4. `tests/Feature/QuestionnaireConfigurationTest.php`
5. `tests/Feature/ApplicantSandboxTest.php`
6. `tests/Feature/ApplicationDecisionFoundationTest.php`
7. `tests/Feature/CaseIssuanceRequestTest.php`
8. `tests/Feature/DemonstrationDocumentTest.php`
9. `tests/Feature/DownloadableFormsTest.php`

| Result | Actual observation |
| --- | --- |
| Executed / passed | **168 / 168 tests** |
| Failures / errors | **0 / 0** |
| Assertions | **1,869** |
| PHPUnit duration | **268,190 ms** |
| Process exit | **0** |
| First identity / finish | **08:58:16 / 09:02:44 UTC**, 27 October 2026 |
| Additional retries / test groups | **None** |

The raw result is retained in the new attempt's `targeted.txt`, with the exact absolute child command and exit status in `execution.json`. No historical totals are substituted for these results.

## Preflight and fixed attempt handling

Recorded HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and a **26,753-file** baseline before the runner change. Compared the previous remediation's 26,744-file baseline: the only difference was the already approved `CaseIssuanceRequestTest.php` assertion correction. A before/after string comparison confirmed that its complete absent-demonstration-PDF expectation and multiline formatting were the only edits to that test. The previously approved dashboard responsibility assertion and runtime sources were unchanged.

Both assertions match the independently documented Phase 3 contracts: permission-based recommendation responsibility, and separate request/PDF availability with explicit staff generation. All nine files existed. The runner retained its historical-evidence-writer exclusion and fixed test selection.

Changed only four fixed attempt-identifier/path occurrences in `scripts/Run-UiUxRegression.php`, from attempt 02 to attempt 03: evidence directory, collision message, start-record ID and snapshot identity-log path. Atomic directory reservation, refusal to reuse a path, full-suite refusal, memory limit, guards and stop behavior were preserved. No fallback number or automatic retry exists.

The original `UiUxAcceptanceBootstrap.php` was not modified. The private snapshot preserves its guard logic, with the established absolute-root substitution and attempt-local identity output. Its source/snapshot hashes and relocation provenance remain in the attempt directory. Neither previous attempt was reopened for logging.

PHP syntax passed for both verification scripts and all nine test files. The generated private bootstrap passed syntax before PHPUnit. Scoped Pint `--test` and tracked whitespace checks passed; existing CRLF advisories remain.

## Database identity and boundaries

| Field | Required | Observed |
| --- | --- | --- |
| Driver | PostgreSQL (`pgsql`) | PostgreSQL only |
| Database | `osds_test` | `osds_test` in every identity record |
| Server host | `127.0.0.1` | `127.0.0.1` in every record |
| Server port | `55432` | `55432` in every record |
| Identity checks | All must pass | **159/159 PASS**, 0 mismatches; 154 distinct backend PIDs |

The first identity check, PID **24560**, completed at **08:58:16 UTC** before Laravel bootstrap or test migrations. The final recorded check, PID **10700**, was at **09:02:44 UTC**. New connections and reconnect-created connections remained subject to configured-target validation and actual server identity checks before Laravel received their PDOs. Cached configuration, unexpected URLs/targets and unsupported aliases remained rejected.

The disposable `osds_test` schema and fixture data were changed by the authorized Laravel test lifecycle. Its prior contents are not claimed restored. No connection to `osds_dev`, `osds_ui_browser_test` or another database was attempted by this execution. No `.env`, `.env.testing`, migration definition, application runtime source or browser configuration was modified.

## Preservation, including fake-disk cleanup

| Protected material | Outcome |
| --- | --- |
| Initial B1 evidence, report, identity log and preservation manifest | **PASS: unchanged** |
| Attempt 02 evidence and rerun report | **PASS: unchanged** |
| Both remediation reports and their evidence | **PASS: unchanged** |
| Phase 1–3 implementation/reports, Gate A/B0 evidence and Batch G artifacts | **PASS: unchanged** |
| Ten historical evidence JSON files | **PASS: 10/10 byte-identical**, no restoration needed |
| Original PDFs | **PASS at final verification: 73/73 byte-identical; two fake-disk fixtures restored as detailed below** |

The first post-test comparison identified the authorized runner change, generated Laravel log/cache changes and removal of three pre-existing fake-upload artifacts by the test filesystem lifecycle. `DownloadableFormsTest` uses `Storage::fake('local')`, and Livewire upload tests use a fake temporary disk. This cleanup removed two PDF fixtures and one upload-metadata JSON. These were under `storage/framework/testing/disks/`, not private completed demonstration originals or historical evidence.

The two removed PDFs had the same pre-run SHA-256:

`7A6F7637BE17A89E0D1A6EBB8EA0AE701B4DA00727303FFE16E024A33A73B85C`

An existing newly produced fake-upload fixture had exactly that checksum. Copied its identical bytes to the two missing baseline paths, then verified both against their pre-run hashes. This was preservation recovery of disposable fixture files, not document regeneration, test replay or restoration of a previous attempt log. No completed demonstration original was missing, corrupt or overwritten. The pre-restoration differences and source/destination/hash records are retained privately; the temporary loss is not concealed.

The removed fake-upload metadata JSON could not be restored from a verified original byte copy and **remains missing**. It is not one of the ten historical diagnostic JSON files. No metadata was invented to obtain a clean preservation comparison.

Final baseline comparison:

- **26,749 of 26,753 files unchanged**, including all 73 baseline PDFs.
- **3 modified files:** the authorized verification runner, `storage/logs/laravel.log`, and one existing compiled Blade cache.
- **1 missing generated test artifact:** the old fake-upload metadata `.pdf.json`.

The exact generated cache/metadata paths are private in `preservation.json`; generated log/cache bytes were archived separately. Logs and caches were not cleared, rolled back or rewritten to hide test effects. Earlier attempts' logs and manifests were not restored or modified.

Outside the new preflight directory, the post-run new-file inventory contained **16 files**: seven attempt evidence files, eight new `.pdf`-named test artifacts and one new fake-upload metadata file. These are automated-test outputs, including fixture/failure-path artifacts; their extension alone is not a validity claim. No browser-test PDF was generated. This report is an additional new public deliverable.

## Evidence locations

New append-only attempt evidence:

`storage/app/private/uiux-consolidated-acceptance/gate-b1/attempt-03/`

Contains `started.json`, `bootstrap.php`, `bootstrap-provenance.json`, `bootstrap-syntax.txt`, `identities.jsonl`, `targeted.txt` and `execution.json`.

Preflight and preservation:

`storage/app/private/uiux-consolidated-acceptance/gate-b1-attempt-03-preflight/`

Contains HEAD/status, runner-before snapshot, source-recheck results, baseline, raw pre-restoration comparison, final comparison, checksum-identical PDF restoration record, individual historical JSON comparison, new-file inventory and generated-runtime archive. Credentials, raw evidence locators and private PDF paths are not exposed in this report.

## Comparison and stopping point

| Attempt | Actual outcome |
| --- | --- |
| Initial B1 | FAIL: 1 test / 5 assertions; obsolete `Chief` expectation |
| Attempt 02 | FAIL: 117 tests / 1,444 assertions; obsolete absent-document wording |
| **Attempt 03** | **PASS: 168 tests / 1,869 assertions**, following the two separately approved test-only corrections |

Gate B1 targeted regression is complete. Full current-tree regression remains unexecuted under Gate B4. Browser navigation, authenticated account journeys, responsive/accessibility acceptance and supervisor review remain separate; this test run does not establish them. The fake-disk metadata preservation limitation remains explicitly recorded.

The result supports review of the already planned B2 additive-preparation authorization package. It does not authorize B2, B3 or B4. Future preservation preparation should privately back up disposable fake-disk artifacts if their continued byte-for-byte retention is required; do not change application behavior or weaken tests to avoid normal fake-disk cleanup.

**STOP. No additional retry, browser account/fixture, website login, browser configuration mutation, browser PDF generation, runtime edit, commit, push or deployment occurred. Await separate authorization for any subsequent stage.**
