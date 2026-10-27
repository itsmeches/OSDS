# Gate B1 — Controlled targeted regression rerun

**27 October 2026. Attempt `b1-attempt-02`: FAIL.** Executed once, stopped at the next failing assertion, and made no test/runtime correction or retry. Gate B2, B3 and B4 remain NOT AUTHORIZED. Batch G remains CONDITIONAL CLOSE.

## Attempt provenance and tooling

Before execution, recorded HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and a new **26,716-file** pre-attempt hash baseline. Verified the 89 protected files from remediation, including the component, Blade view, historical evidence and 73 PDFs, against their earlier hashes. Verified that the corrected test differs from its original snapshot only by the separately approved recommendation assertion and multiline formatting.

Extended only `scripts/Run-UiUxRegression.php` for the fixed second attempt. It still accepts only `targeted`, retains the exact nine files, memory limit and stop-on-failure/error options, and refuses full-suite selection. It atomically reserves `gate-b1/attempt-02/`; an existing directory or reservation failure stops execution. There is no automatic attempt-number increment, retry loop, arbitrary output path, database, SQL or test-selection argument.

The original `scripts/UiUxAcceptanceBootstrap.php` remains unchanged. To avoid appending to the original identity log, the runner writes a private bootstrap snapshot with exactly two path adaptations: literal absolute project root and attempt-local identity log. Connection checks, allowed targets, aliases and guard logic are unchanged. The snapshot is syntax-checked before PHPUnit; source/snapshot hashes and path adaptations are recorded in `bootstrap-provenance.json`. This is verification-only evidence routing, not an application change.

The new directory has separate start, identity, syntax, execution and diagnostic records. Nothing in the original failed attempt was deleted, renamed or overwritten.

## Executed command and actual results

Using the project's portable PHP binary:

```powershell
php -d memory_limit=512M scripts/Run-UiUxRegression.php targeted
```

The runner invoked PHPUnit directly with `-d memory_limit=512M`, `--bootstrap` pointing to the private attempt snapshot, `--do-not-cache-result`, `--stop-on-failure`, `--stop-on-error`, and exactly:

1. `tests/Feature/DashboardGuidanceTest.php`
2. `tests/Unit/ApplicantApplicationGuidanceTest.php`
3. `tests/Feature/FormMakerTest.php`
4. `tests/Feature/QuestionnaireConfigurationTest.php`
5. `tests/Feature/ApplicantSandboxTest.php`
6. `tests/Feature/ApplicationDecisionFoundationTest.php`
7. `tests/Feature/CaseIssuanceRequestTest.php`
8. `tests/Feature/DemonstrationDocumentTest.php`
9. `tests/Feature/DownloadableFormsTest.php`

All files existed. Historical diagnostic JSON writer patterns were excluded by the fixed-source check; selected-file/support-fixture review from B1 remained applicable because those sources were unchanged apart from the reviewed assertion. No extra test group or full suite ran.

| Check | Actual result |
| --- | --- |
| Attempt start / finish | **08:37:28 / 08:39:28 UTC**, 27 October 2026 |
| PHPUnit reported duration | **119,994 ms** |
| Executed tests | **117** |
| Passed / failed | **116 / 1** |
| Assertions | **1,444** |
| Process exit | **1** |
| Corrected original dashboard test | Proceeded successfully; it is no longer the stopping failure |
| Complete nine-file acceptance | **FAIL / incomplete**: remaining tests not reached after the failure |
| Document and blank-download test files | **BLOCKED / not reached** in this attempt |

No historical test totals were used as current results. The 116 passing tests are not proof of a complete B1 regression pass.

## New failure and classification

Failed test:

`CaseIssuanceRequestTest::test_draft_recovery_uses_explicit_new_version_and_submitted_snapshot_survives_withdrawal`

Failure location: `tests/Feature/CaseIssuanceRequestTest.php:219`.

Expected text:

> No certificate has been generated.

Actual rendered text begins:

> No demonstration PDF has been generated. A saved draft is not a submitted request. Authorized staff must explicitly prepare the PDF after a qualifying request is submitted.

The test's earlier assertions checked preservation of the old draft, two request rows and same-key retrieval of the submitted request after definition withdrawal. The Livewire page rendered the retained submitted request, its fictional answer, saved versions and the separate PDF section. The final text assertion then failed on the changed wording; its subsequent `Fictional contact` assertion in that chain was not reached and is not claimed passed.

**Classification: apparent test/content expectation mismatch.** The Phase 3 plan explicitly separates submitted requests from demonstration PDF availability, and the current view uses demonstration-PDF terminology. This evidence does not establish a database or environment failure, nor does it prove every draft/withdrawal/document behavior correct. No production defect is inferred solely from this mismatch.

Recommended next review: compare this exact message with the approved Phase 3 contract and, if justified, authorize a test-only expectation update that preserves all draft/version/withdrawal/idempotency/content assertions. Do not change the runtime view simply to restore old text. **No correction has been made here.** No third attempt is authorized or implemented.

## Database isolation

| Identity field | Configured / allowed | Observed |
| --- | --- | --- |
| Driver | PostgreSQL (`pgsql`) | PostgreSQL connections only |
| Database | `osds_test` | `osds_test` in every identity record |
| Server host | `127.0.0.1` | `127.0.0.1` in every record |
| Server port | `55432` | `55432` in every record |
| Identity checks | Must all pass | **108/108 PASS**, zero mismatches; 106 distinct backend PIDs |

The first actual identity check occurred at **08:37:28 UTC**, before Laravel bootstrap/destructive testing, on backend PID **27352**. The last was **08:39:27 UTC**, PID **3520**. Actual server identity was checked on newly created guarded connections before Laravel received them. The existing strict configuration/environment/cache/URL and alias checks were reused without weakening.

The authorized RefreshDatabase lifecycle and synthetic fixture writes ran only in the disposable `osds_test` database. Existing test data/schema may have been replaced; restoration of prior database contents is not claimed. Neither `osds_dev` nor `osds_ui_browser_test` was accessed by this attempt. No browser server, website authentication, browser fixture/account, published browser configuration or browser-test PDF was involved.

## Preservation results and generated files

| Comparison | Actual outcome |
| --- | --- |
| All 73 original PDFs | **PASS: byte-identical** against original and new pre-attempt baselines |
| Ten historical evidence JSON files | **PASS: unchanged**; no restoration necessary |
| Phase 1–3 runtime source, component and Blade view | **PASS: unchanged** |
| Gate A/B0 reports/logs, original B1 report/log/identity/failure/preservation records | **PASS: unchanged** |
| Pending remediation and unrelated Batch G artifacts | **PASS: unchanged** |
| New pre-attempt baseline, 26,716 files | **26,709 unchanged; 7 generated-runtime files changed; zero missing** |
| Original B1 project baseline, 26,683 files | **26,675 unchanged; approved test remediation plus the same 7 generated-runtime changes; zero missing** |

The seven runtime-generated changes are `storage/logs/laravel.log` and six existing compiled Blade cache files under `storage/framework/views/`. They are not source templates, historical evidence or stored PDFs. Their exact paths and hashes are retained privately. Copies of their post-test bytes are archived in `gate-b1-rerun/generated-runtime/`. They were not silently restored, deleted or represented as byte-identical. No application source was changed by the attempt.

The runner extension predates the new pre-attempt hash snapshot and is separately preserved against `runner-before.php`. The original B1 baseline predates creation of that runner, so it does not measure this authorized tooling delta. The original bootstrap is unchanged against its saved copy and the new baseline.

The seven new attempt files are `started.json`, `bootstrap.php`, `bootstrap-provenance.json`, `bootstrap-syntax.txt`, `identities.jsonl`, `targeted.txt` and `execution.json`. All reside under:

`storage/app/private/uiux-consolidated-acceptance/gate-b1/attempt-02/`

Preflight, hash comparisons, runner/bootstrap snapshots, new-file inventory and generated-runtime archive reside under:

`storage/app/private/uiux-consolidated-acceptance/gate-b1-rerun/`

This report is a new public deliverable. No original report or result was rewritten. The failure trace and rendered synthetic output remain private; credentials, evidence locators and unrelated personal data are not published.

## Static checks and remaining acceptance

PHP syntax passed for the runner, unchanged guard, corrected dashboard test and generated attempt bootstrap. Scoped Pint passed for the changed runner. Tracked whitespace checks passed with existing CRLF advisories. Guard/source and preservation checks were file-based; no additional database-backed verification was performed after the failure.

Compared with the original **1 failed test / 5 assertions** attempt, this rerun demonstrates successful progress past the reviewed dashboard mismatch, but stops at a second text expectation mismatch. Both results remain independently auditable. Browser/authenticated Phase 1–3 acceptance, full PostgreSQL regression, complete PDF regression and human accessibility/supervisor acceptance remain incomplete.

**B2 readiness recommendation: hold execution authorization pending review of this new mismatch and completion of B1.** Option A remains the planned strategy; this test-database failure does not establish that existing browser configurations are incompatible. Do not infer permission to prepare browser fixtures from either finding.

**STOP. No further retry, test edit, runtime change, B2/B3/B4, browser work, commit, push or deployment was performed.**
