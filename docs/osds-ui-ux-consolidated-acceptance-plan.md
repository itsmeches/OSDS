# OSDS — Phase 1–3 consolidated acceptance and regression plan

**27 October 2026. Planning and file-based preflight only. Database inventory, preparation, authentication and database-backed tests are BLOCKED pending separate authorization.** No database connection was opened. No application bootstrap, test, migration, fixture operation, commit, push or deployment was performed. This plan does not authorize execution or a runtime fix.

## 1. Preflight findings

Git HEAD is `f7625a9de909a991013db6ce43c68bee65402ce0`. The working tree contains the pending B4 accessibility remediation, Phase 1–3 runtime changes, new tests/reports and evidence. Testing must cover the actual dirty working tree, not claim acceptance of HEAD alone.

Before documentation changes, Git state and **2,780 baseline file hashes**, including repository assets and private PDFs, were recorded in `storage/app/private/uiux-consolidated-planning/`. No test-related file was changed by this preflight.

### Configuration inspected without application bootstrap

| Source / purpose | Connection and target | Finding |
| --- | --- | --- |
| Local `.env` | `pgsql`, `127.0.0.1:55432`, `osds_dev` | Forbidden execution target. Session/cache defaults use database stores, so ordinary local bootstrap is not an acceptable isolation method. |
| `.env.testing` | `pgsql`, `127.0.0.1:55432`, `osds_test` | Test target declared on disk; its session/cache defaults still require explicit process overrides. |
| `phpunit.xml` | `pgsql`, `osds_test`; array session/cache | Defines Unit and Feature suites. Environment entries are not a sufficient defense against inherited configuration on their own. |
| Existing guarded browser tooling | `pgsql`, `osds_ui_browser_test`, local PostgreSQL configuration | B1–B4 scripts/reports identify this as the isolated browser target. |
| Laravel cached configuration | `bootstrap/cache/config.php` absent at inspection | Recheck before every authorized process; refuse execution if it appears. Do not clear/rewrite local configuration automatically. |

Both test database names are **configuration/history-verified only**. Their present existence, connection availability, actual server identity, schema currency and records have not been verified. Do not describe them as newly observed running databases. No credentials are included in this plan.

### Existing test and script infrastructure

- Current source contains **37 feature-test files and three unit-test files**. No test discovery or execution was run; the current expanded test total is unknown. One unit file, `WindowsServeUploadEnvironmentTest`, extends the Laravel test base, so the Unit directory must not be assumed database-free.
- The last recorded complete PostgreSQL pass predates these UI/UX changes: **431 tests / 4,627 assertions**. Phase 3 separately recorded 11 pure tests / 27 assertions. These are historical evidence, not consolidated acceptance.
- `scripts/Run-B4AccessibilityTests.php` and `Run-B3Regression.php` pin `osds_test`, refuse cached configuration, assert actual database identity and launch PHPUnit with 512 MB. The B4 runner's focused filter does not include the new dashboard tests; do not invoke it unchanged as comprehensive verification.
- Laravel's inspected `RefreshDatabase` implementation invokes **`migrate:fresh`**, then transaction-based test isolation. Therefore the suite can destroy existing `osds_test` rows/schema even when no explicit migration command is typed.
- `tests/Feature/DashboardGuidanceTest.php` adds configured/actual `osds_test` checks before RefreshDatabase. Other existing tests have varying guard coverage; a runner-wide preconnection guard is still required.
- `scripts/Prepare-B4AccessibilityFinal.php` uses a hard-coded historical namespace and ledger. `rehearse-b4-accessibility-final.mjs` points at historical evidence, uses old labels and can retire/withdraw previously published configurations. Do not run either unchanged for this acceptance task.
- The existing browser server pins port 8042 and browser database identity. New acceptance tooling must use a distinct loopback port, cookie, private ledger and output directory; historical scripts remain unchanged.
- Existing B1/B2/B3 tests cover stage separation, immutable revisions, eligibility, duplicates, audit rollback and document integrity. Existing concurrency scripts are separate processes with historical ledgers: they are not safe automatic add-ons to the new run.

### Preservation and special hazards

1. Preserve Phase 1–3 code and evidence and B4 remediation, especially `resources/css/theme/components.css` and `resources/views/filament/pages/form-builder.blade.php`. Earlier B4 incomplete accessibility findings remain unresolved; new sampled passes do not close them.
2. Preserve `scripts/.batch-g-recovery-current.mjs` byte-for-byte. It is unrelated untracked work, not a consolidation helper.
3. Five existing verification tests write published evidence files under `docs/evidence/`: K1 institution-update-after, K2 eligibility-create, K3 institution-create-after, K4 region-update-after and K5 region-create-after. Snapshot all ten published historical evidence JSON files, archive regenerated outputs separately, then restore originals byte-for-byte in a guaranteed finalization step. This **temporary overwrite/restore must itself be explicitly authorized** before running those tests.
4. B3 integrity tests delete/corrupt/restore their newly generated disposable PDFs. Authorize those operations only on files created by this run; existing stored originals are excluded.
5. A downloadable-form test temporarily sets the configuration database string to `osds_dev` while retaining its already established `osds_test` connection. Source inspection confirms the intent is configuration-only. Preserve its assertion, but install a preconnection guard that rejects any attempted new connection/reconnection to `osds_dev`; fail rather than permit such access.
6. Timestamp tests create `sec006_utc` and `sec006_legacy` connection aliases cloned from `pgsql`. They must retain their intentional timezone behavior while targeting the same approved `osds_test` database. Database isolation must not invalidate these tests by forcing all aliases to use one timezone.
7. Active intake publication and issuance-definition slots are shared by module; published PDF templates are shared by kind/module and personnel by kind/module/region. Existing occupied slots may block fresh COR/ACSA fixture publication. Do not silently retire old configurations or bypass uniqueness.

## 2. Authorization gates and operation inventory

### Gate A — read-only inventory, requested first

Request access only to:

- `pgsql` → **`osds_test`**, host **127.0.0.1**, port **55432**.
- `pgsql` → **`osds_ui_browser_test`**, host **127.0.0.1**, port **55432**.

Use a dedicated inventory helper with direct, explicitly named PostgreSQL connections and read-only transactions. Do not bootstrap local Laravel sessions/cache. Reject any other database/host/port or URL override before connection creation. If a named database does not exist, stop; do not connect to another database to create it.

Permitted inventory statements: connection/server/database identity, schema/migration ledger presence and applied migration names, relevant table/index/trigger definitions, active COR/ACSA publication/definition/template/personnel IDs and version/state metadata, required reference-catalogue availability, and synthetic namespace collisions. Do not retrieve applicant answers, evidence locators, credential values or unrelated records. End read-only transactions without writes.

**No migrations, inserts, updates, deletes, fixture creation, login/session creation, account changes or cleanup are included in Gate A.** File-based private inventory logs are allowed. Existing records may not be replaced.

Gate A output must name any schema gaps, slot conflicts and exact pre-existing IDs whose retirement/withdrawal would be necessary. Return for review before Gate B; neither this plan nor Gate A authorizes those changes.

### Gate B — separately approved writes and execution

| Target | Proposed operations | Existing records replaced? | Cleanup / rollback |
| --- | --- | --- | --- |
| `osds_test` | Serialized targeted and complete suites; RefreshDatabase `migrate:fresh`; migration-managed table/index/constraint/trigger/function creation; fixture inserts/updates/deletes, permission grants/revocations, negative SQL probes and transactional rollback checks | **Yes, only after explicit approval that all existing test data is disposable.** No DROP DATABASE or broad DROP SCHEMA authorization is requested. | Per-test transactions roll back ordinary fixture rows. Schema reset and sequence increments are not undone by that rollback; do not promise restoration of previous test records. Test schema may remain after execution. |
| `osds_ui_browser_test` schema | Only reviewed pending **forward migrations**, if Gate A identifies any | No reset/reseed/drop. Forward DDL requires approval of the exact pending migration list first. | No automatic down migration. If forward migration fails, retain evidence and stop; inspect before recovery. |
| `osds_ui_browser_test` foundational fixtures | Append new namespace identities, explicit permission memberships, synthetic institutions/regions, representations, eligibility and reviewer/regional assignments. Insert missing permission definitions only if itemized; never synchronize existing role bundles. | No replacement of old actors, records, fixtures or ledgers | Transactional preparation where supported; record committed IDs in a private ledger. A namespace collision or uncertain commit requires inspection, never blind replay. |
| Browser workflow | Real login/session activity; UI-driven questionnaires/reviews/publications, drafts/submissions/corrections, B1 stages, request definitions/requests, PDF configuration/generation, audit/history and downloads | Existing business records remain. Prefer compatible existing configurations read-only; any required old-slot retirement/withdrawal needs exact-ID approval from Gate A. | Keep append-only case/history/decision/request/document records for inspection. Withdraw/retire only this run's new active configurations through real handlers when cleanup is approved. No hard-delete cleanup of immutable rows. |
| Negative controls | Revoke/restore this namespace's permissions/representation/assignments; inactivate dedicated probes; attempt unauthorized actions and duplicates; withdrawal tests on this run's versions | Only new probe/configuration rows named in the ledger | Restore only explicitly reversible fixture authorities via existing permitted mechanisms; do not rewrite immutable grant dates or revive retired history. |
| New private artifacts | Stage uploads, render PDFs, private backups, browser downloads, temporary corruption/removal of designated new integrity-test bytes and checksum-identical recovery | **No original baseline file may be overwritten, removed or corrupted** | Ledger-scoped file cleanup only after evidence and path/hash review. Never broad-delete private storage. Retain uncertain/orphan files for explicit inspection. |
| Published diagnostic JSON | The five existing writers listed above may temporarily overwrite their established paths during suite execution | Original bytes restored; new results retained separately | Snapshot all ten published files first; archive generated versions and restore originals after each attempt in `finally`, including failures/interruption. Verify SHA-256 equality. |

Browser cleanup is **not** an all-encompassing rollback: UI operations commit, retirements/withdrawals may be irreversible, audit rows are append-only and filesystem writes are not database transactions. Retain the run's records rather than fabricate a reset. Do not change existing configuration slots before a concrete approval.

No manual activation or changes to `osds_dev` are requested. If either target is absent, unsuitable or not disposable as described, stop and obtain a revised environment decision.

## 3. Proposed fixture and authentication package

Use fresh namespace **`uiux_20261027_a01`** only if Gate A confirms it is unused. A collision is a stopping point; choose and approve a new namespace rather than reusing records. Credentials are fictional, task-specific and kept in the private ledger; never publish passwords, cookies or storage-state files.

Proposed **18 identities**:

1. OSDS configuration manager.
2. Configuration reviewer/publisher.
3. Technical evaluator.
4. Chief.
5. Regional Director.
6. Evidence reviewer.
7. Intake read-only staff.
8. Questionnaire-view-only staff for the zero-stat case.
9. Combined-permission staff.
10. Panel-admitted staff with no dashboard destinations.
11. Active account without panel admission.
12. Inactive probe account.
13. Revocation probe account.
14. Wrong-region staff.
15. COR-only staff.
16. ACSA-only staff.
17. Applicant owner/representative.
18. Unrelated applicant with a separate institution.

Provision existing permissions individually. Use two synthetic regions, a primary and auxiliary institution for the owner and a third institution for the unrelated applicant. Existing COR (`registration`) and ACSA module references are reused, not renamed or duplicated. Give effective representation, eligibility and exact region/module assignments only to the intended identities. Main evaluator, Chief and Director are three distinct accounts; combined permissions never waive case-wide separation.

Browser authentication must use the real applicant/staff login controls, fresh contexts and task-owned cookies. Inactive/no-panel probes verify denial rather than receiving fabricated authenticated sessions. Role switching uses separate contexts; no direct session-token injection. Public landing captures use a separate logged-out context and do not submit forms before Gate B authorization.

Create successful COR and ACSA applications through the UI, with R1 and corrected R2 values. Use auxiliary cases for rejection and separation probes. Preserve the exact same institution/module/publication terminal uniqueness boundary; do not create artificial renewal/reopening behavior. Unknown state coverage remains pure/component testing, not an invalid database insertion.

Existing B4 prepared ledgers are not reusable credentials/fixtures for this task. Reuse their reviewed scenario logic in new tools, not their account IDs, output paths or global withdrawal loops.

## 4. Acceptance matrix

All runtime rows below are **BLOCKED pending the appropriate gates**. “Expected” is an acceptance criterion, not an observed result.

| ID | Target and scenario | Expected evidence / outcome |
| --- | --- | --- |
| P0 | Each process and worker | Configured target and actual PostgreSQL identity match before preparation/execution; forbidden connection attempts fail before PDO connection creation. |
| N1 | OSDS, evaluator, Chief, Director, evidence reviewer | Sidebar groups/items follow Phase 1 order and exactly the existing destination access checks. Shared intake remains one route. |
| N2 | Read-only, combined, no-task, no-panel, inactive and revoked probes | Correct visible union, no duplicate destination, no unauthorized task invitation, expected 403/404/login outcome under the current contract. |
| N3 | Staff expanded/collapsed sidebar and mobile drawer; applicant menu | Active indicators and parent highlighting, long labels, group expansion, keyboard access, Escape/focus return and hidden-link tab exclusion. |
| L1 | Logged-out actual `GET /` | Real Blade component/Vite/route-helper resolution; unchanged applicant/staff destinations; no public registration or official issuance implication. |
| L2 | Landing links/themes | Skip link focuses main; section anchors work; Light/Dark/System and unavailable browser storage; responsive wrapping, contrast and target size. |
| D1 | Actual dashboard with each authorized profile | Widget discovery mounts TaskOrientation exactly once before WorkflowOverview; proper sections, descriptions, real URLs and no new workload queries. |
| D2 | Combined and stage-only-without-intake permission | One intake card with ordered responsibility descriptions, or no card when destination access is absent. Case stage/assignment remains authoritative after opening. |
| D3 | Panel-only and questionnaire-view-only accounts | Helpful no-task message; actual zero-stat description renders without inventing counts or granting questionnaire management. |
| D4 | Statistics | Real values match the **existing** three predicates at the same fixture checkpoint; scheduled/ended non-retired publications remain counted. No regional/personal reinterpretation. |
| A1 | COR and ACSA application stages | Draft, submitted, under_screening, correction_requested, resubmitted, approved and rejected messages match actual state; existing actions remain correct. Unknown-state fallback tested without storing an invalid state. |
| A2 | Questionnaire availability ended | Notice remains separate from lifecycle outcome; editing/resubmission restrictions remain; approval is not obscured or converted into a new status. |
| A3 | Correction/resubmission | Existing correction summary/items visible; R1 immutable, R2 submitted explicitly; no unauthorized edit or repeated submission. |
| A4 | Approved-case request link | Links to the same case; approval alone never bypasses current ownership, representation, eligibility, source revision, definition, identity binding, evidence or permission checks. |
| R1 | Request definitions absent/withdrawn/mismatched | Actual service reason remains; no automatic eligibility or free-text protected-field replacement. Submitted originals retain their existing read behavior. |
| R2 | Request draft/submitted | Save/reload/submission through real handlers; one submitted original; request remains submitted after document completion. |
| R3 | PDF absent/processing/failed/completed | Correct text, controls and immutable source/template/personnel bindings. Failure/processing states observed through a bounded genuine renderer delay/fault, not direct state fabrication. |
| S1 | Direct-route and mounted-action denial | Guest, unrelated owner, revoked permission, inactive identity, revoked representation, wrong region/module and expired assignment deny with no partial persistence. |
| S2 | B1 separation and revision coherence | Case-wide three-person separation including prior revisions; stale evaluations/recommendations do not survive resubmission; no missing-stage or conflicting decision accepted. |
| S3 | Immutable applications/requests/documents | Existing PostgreSQL guards and audit rollback tests still pass; no stale payload or duplicate-original bypass. |
| S4 | Private PDF integrity | Actual downloaded bytes equal the stored checksum; every page has fixed demonstration markings; missing/corrupt **new** test files deny and audit; recovery accepts checksum-identical bytes only. |
| S5 | Version withdrawal | Withdraw this run's template/personnel versions through authorized actions; previously completed originals remain unchanged and authorized retrieval remains governed by existing rules. |
| W1 | Independent COR and ACSA rehearsals | Real UI submission → correction/R2 → evaluator → Chief → Director approval → request draft/submission → explicit staff PDF → applicant exact-byte download. No service-prepared business success records. |
| F1 | Focused tests then full current-tree suite | Actual totals, failures/skips, source hashes, run settings and durations recorded. Stop on focused failures; do not launch the broad run to obscure a known failure. |

Application/request/PDF boundary checks must use actual data separately; do not label simulated component profiles as successful authentication. Existing failure/audit/concurrency tests are retained. Any additional worker contention scenario needs a fresh ledger and the same guarded target; do not run historical worker scripts blindly.

## 5. Responsive and accessibility evidence

Use actual rendered pages at **375, 768, 1024 and 1440px**, both light and dark. Capture a fixed core matrix of landing, staff dashboard, staff navigation/intake, applicant home, correction tracking, request draft, submitted request and completed-document availability: **64 representative captures** across eight checkpoints. Use COR for this shared layout matrix and add independent ACSA correction, approved-case and submitted-request/PDF checkpoints at both themes in mobile/desktop: **12 additional captures**. Record functional role/probe checks separately; do not imply all 18 identities or every transient state received every capture combination.

Add 320px reflow and 200% text-size checks for landing, combined-permission dashboard and applicant application/request layouts. Retain unrestricted default axe plus enabled target-size, raw violations/incomplete results, overflow, uncaught errors and visible-focus observations. Do not alter thresholds, omit failing nodes or dismiss notifications earlier to obtain a pass. Preserve the established stable-capture procedure and separately inspect natural notification entrance/exit where relevant.

Measure keyboard navigation, skip links, active indicators, dialogs and drawer closure/focus return. No-task pages need no artificial controls. Human screen-reader, native zoom and first-time-user/supervisor review remain separately pending.

## 6. Proposed commands and tooling — NOT executed

New verification-only files are proposed, not created by this preflight:

- `scripts/UiUxAcceptanceBootstrap.php`: process configuration, preconnection guard and identity checks.
- `scripts/Run-UiUxAcceptance.php`: read-only inventory, reviewed preparation/control/inspection modes and serialized test launching.
- `scripts/uiux-acceptance-server.php`: guarded loopback browser router on **8044**, unique session cookie and private file sessions.
- `scripts/verify-uiux-acceptance.mjs`: new role/layout/journey checks using real controls.
- `scripts/inspect-uiux-pdfs.mjs`: read-only byte/checksum/page/marking inspection for this namespace.

After the relevant authorization and tool review, proposed commands are:

```powershell
# Gate A only: direct read-only connections; no application bootstrap or fixtures.
php scripts/Run-UiUxAcceptance.php inventory --database=osds_test
php scripts/Run-UiUxAcceptance.php inventory --database=osds_ui_browser_test

# Gate B only, after reviewing the inventory and exact pending migration list.
php scripts/Run-UiUxAcceptance.php migrate-browser
php scripts/Run-UiUxAcceptance.php prepare --namespace=uiux_20261027_a01
php scripts/Run-UiUxAcceptance.php focused
php -S 127.0.0.1:8044 -t public scripts/uiux-acceptance-server.php
node scripts/verify-uiux-acceptance.mjs roles
node scripts/verify-uiux-acceptance.mjs journeys
node scripts/verify-uiux-acceptance.mjs matrix
node scripts/inspect-uiux-pdfs.mjs
php scripts/Run-UiUxAcceptance.php full
php scripts/Run-UiUxAcceptance.php inspect --namespace=uiux_20261027_a01

# Non-database final gates; no formatter rewrites.
php vendor/bin/pint --test
node --check scripts/verify-uiux-acceptance.mjs
npm.cmd run build
git diff --check
```

`migrate-browser` is skipped if no approved pending migrations exist. The server command is a task-owned background process, not a second terminal to a manual environment; check port availability first and stop only that process afterward. The commands assume the inspected portable tools are on PATH; do not source Enter-DevShell blindly because it also invokes `pg_isready`. Do not start/stop PostgreSQL automatically during preflight.

The new runner's focused mode launches PHPUnit directly with **512 MB** and the acceptance bootstrap, using this filter:

`DashboardGuidanceTest|ApplicantApplicationGuidanceTest|FormMakerTest|QuestionnaireConfigurationTest|ApplicantSandboxTest|ApplicationDecisionFoundationTest|CaseIssuanceRequestTest|DemonstrationDocumentTest|DownloadableFormsTest`

Full mode uses the same guard/bootstrap without a filter, serialized against `osds_test`. No parallel schema rebuild or browser process may point at `osds_test`. Browser activity uses only `osds_ui_browser_test` and should finish before the full suite to avoid shared-file/evidence collisions. Record current totals rather than predict them from previous passes.

Do not use bare `php artisan test`, `migrate`, `db:seed`, generic `php artisan serve` or historical preparation scripts as shortcuts. No cleanup/reset command is included for the browser database; retained records and explicit scoped cleanup are the default.

## 7. Fail-closed execution safeguards

Before any authorized application process boots, force `DB_CONNECTION=pgsql`, the single approved target database, `DB_HOST=127.0.0.1`, `DB_PORT=55432`, empty `DB_URL` and an isolated APP_ENV. Set values consistently in process environment, `$_ENV` and `$_SERVER`; never modify `.env` or `.env.testing`.

Install a verification-only PostgreSQL connection resolver before Laravel bootstrap. Laravel's inspected ConnectionFactory calls a registered driver resolver before resolving its lazy PDO closure. Validate database, host, port and any read/write target configuration before allowing that closure to connect. Cover new connections and reconnects, including permitted timestamp-test aliases. Reject `osds_dev` and every unapproved target before connection creation. Do not silently correct an attempted forbidden target or weaken an assertion.

Also check configured connection and actual `current_database()` before migrations/preparation and in every helper/worker/request process. Gate A uses a separate direct-PDO allowlist and read-only transaction. No fallback to a cluster administrative database is allowed.

Console acceptance uses array cache/session; browser acceptance uses array cache and task-private file sessions with a unique cookie. Pin mail to array and disable external notification delivery. Keep normal synchronous renderer behavior. Protect credentials and browser state in private storage; block external browser requests. Assert fresh namespace membership and synthetic identity before any probe mutation.

Only verification tooling and new evidence may be prepared after approval. If application runtime changes are needed, record the failing scenario and stop that portion for separate remediation authorization. Do not rewrite existing workflow assertions or historical browser scripts merely to obtain a pass; use new locators only for the approved visible-copy changes while retaining persistence/security expectations.

## 8. Preservation, recovery and outcomes

Refresh the complete baseline immediately before execution, including dirty source, untracked runtime/test files, all historical screenshots/JSON, the ten published evidence files, every existing PDF byte/checksum, configuration files and the unrelated Batch G script. Store credentials/private ledger separately from safe evidence.

New safe outputs belong under `docs/screenshots/uiux-consolidated-acceptance/`; private logs, fixtures, traces containing credentials and backups belong under `storage/app/private/uiux-consolidated-acceptance/`. The final report should be `docs/osds-ui-ux-consolidated-acceptance-report.md`.

Before running each suite attempt, verify the published-evidence backup exists and matches its hash manifest. Archive regenerated diagnostic JSON, restore originals in finalization and verify byte equality after failures as well as success. Keep the restoration ledger separate from original evidence. Check original PDFs after each generation/integrity group; never target them for negative probes.

On uncertain UI responses, inspect the saved record before another explicit action. Do not automatically replay consequential operations, delete an apparent orphan or rewrite immutable states. Retain first failures, network traces and screenshots; redact credentials before publishing. Stop task-owned servers and revoke only task-owned disposable sessions if approved; preserve unrelated services.

Final statuses:

- **PASS:** the exact scenario executed successfully with its persistence/access evidence.
- **FAIL:** an expected behavior was contradicted; retain the failure even after an independently authorized fix/rerun.
- **BLOCKED:** required authorization, schema, slot availability or suitable account/fixture is missing.
- **PENDING HUMAN REVIEW:** screen-reader/native zoom/usability/supervisor acceptance not established by automation.

Complete consolidated technical acceptance requires actual Laravel/Filament rendering, both module journeys, permission/security checks, targeted and full current-tree PostgreSQL results, accessibility evidence and preservation checks. It does not establish production capacity, official issuance, supervisor acceptance or Batch G final closure.

## 9. Precise authorization request

**Requested now: Gate A only.** Authorize creation of the bounded verification inventory helper and read-only inspection of `pgsql` connections to `osds_test` and `osds_ui_browser_test` at `127.0.0.1:55432`, with the stated preconnection allowlist and read-only transactions. Permit private inventory logs. **No existing records may be replaced and no database writes, migrations, fixtures, authentication submissions or cleanup may occur.**

After Gate A, present a concrete Gate B request containing the actual schema/migration findings and exact occupied publication/definition/template IDs, if any. Gate B must explicitly approve:

1. Destructive disposable-schema replacement and fixture DML in **osds_test**, including existing test-row loss from RefreshDatabase.
2. Only itemized forward migrations and additive namespace fixtures in **osds_ui_browser_test**; no reset or replacement of existing records.
3. Any individually identified older configuration retirement/withdrawal, or an explicit decision to leave the affected publication scenarios BLOCKED.
4. The 18 synthetic identities, real login submissions, controlled fixture revocation/inactivation, UI workflow mutations and new-PDF fault/recovery operations described above.
5. Temporary diagnostic JSON overwrites followed by archival and exact-byte restoration, plus ledger-scoped artifact handling.
6. The listed guarded commands, cleanup limits, fail-closed connection checks and retention of immutable browser records.

This two-gate structure is necessary because database existence, pending migrations and occupied slots cannot be truthfully identified from source alone. **Nothing in this plan authorizes accessing `osds_dev`, performing Gate A now, or proceeding from Gate A to Gate B automatically. Stop for review.**
