# Gate B — Execution readiness and authorization package

**27 October 2026. Planning complete; execution NOT AUTHORIZED.** Option A means reuse of existing published configurations, not permission to run tests or create records. No database connection, application bootstrap, login, fixture, migration, test or PDF generation was performed for this package. Batch G remains CONDITIONAL CLOSE.

## 1. Evidence, fixed roots and readiness limits

The [Gate A inventory](osds-ui-ux-gate-a-inventory-report.md), [B0 assessment](osds-ui-ux-gate-b0-compatibility-report.md) and [consolidated plan](osds-ui-ux-consolidated-acceptance-plan.md) supply historical observations, not fresh database assertions. B0 structural compatibility passed for both modules. Full configuration-payload hash recomputation, actual field wording, runtime behavior and external evidence content remain unverified.

| Module | Applicant publication | Intake form / requirement set | Issuance definition | PDF template |
| --- | --- | --- | --- | --- |
| COR (`registration`, module 1) | 198 | 591 / 591 | 54, source form 591 | 43 |
| ACSA (`acsa`, module 2) | 199 | 592 / 592 | 55, source form 592 | 45 |

Preserve these six roots, staff-preview publication **128**, their linked immutable configurations and **every existing personnel mapping**. No retirement, withdrawal, replacement, republishing, date adjustment or content edit is proposed. Before later execution, revalidate exact IDs, states, dates, references and hashes; stop on changes instead of substituting newer versions. No migration is currently indicated by Gate A's 27/27 ledger, and browser migrations are excluded from this package.

Current intake child versions being `in_review` is compatible with the existing approved-review/published-manifest contract. Do not change those states merely to make them read “published.” New routing requires its own published personnel mapping for each module; old mappings cannot be borrowed.

## 2. Stages and independent authorization dependencies

| Stage | Proposed work | Required approval / stopping point |
| --- | --- | --- |
| 0 | Review this package and private baseline | Documentation only, completed now |
| 1 | Implement verification-only guards; run bounded targeted PostgreSQL checks | **B1**; no full-suite historical evidence writers yet |
| 2 | Recheck browser identity, roots and namespace; add foundational synthetic records transactionally | **B2**; no login or business-case preparation |
| 3 | Login through real forms; create fictional office and review/publish two personnel versions through UI | **B2 and B3 together**; B2 alone does not authorize authentication |
| 4 | Independent COR and ACSA UI journeys, permission/state checks, bounded negatives and PDF inspections | **B3**, after stage 2 and personnel setup |
| 5 | Full current-tree suite, diagnostic-output archival/restoration, final preservation and report | **B1 and B4**; serialized after browser work |

Approval of one gate does not approve the others. Each stage records commands, exit status, timestamps, source hashes and actual counts. An incomplete stage cannot be reported as PASS. No runtime fix is authorized: stop the affected scenario and propose a separately reviewed correction if application behavior fails.

## 3. Proposed verification-only tooling

These files are **design proposals, not created or executed by this task**:

| Proposed file | Responsibility |
| --- | --- |
| `scripts/UiUxAcceptanceBootstrap.php` | Prebootstrap endpoint/driver/database checks, PostgreSQL connection resolver guard, actual identity assertion and private run ledger |
| `scripts/Run-UiUxRegression.php` | Fixed targeted/full command modes; assert `osds_test`; launch PHPUnit with explicit memory and environment; reject full mode without B4 approval |
| `scripts/Prepare-UiUxBrowser.php` | Fixed namespace manifest, dry inspection and single-attempt additive foundational preparation only; no case/stage/request/document creation |
| `scripts/uiux-acceptance-server.php` | Loopback-only browser process, fixed browser database, isolated session/cache/files; no production route or persistent feature switch |
| `scripts/verify-uiux-acceptance.mjs` | Real login and UI controls, account contexts, route denials, responsive/accessibility samples, private traces |
| `scripts/inspect-uiux-pdfs.mjs` | Verify downloaded/stored bytes, checksums, page count, per-page markings and R2/module/linkage content |
| `scripts/Finalize-UiUxEvidence.php` | Archive new diagnostic results, restore historical JSON bytes, compare baseline hashes and package safe evidence |

Keep the existing read-only `Run-UiUxAcceptance.php` inventory/compatibility helper read-only. No arbitrary SQL, database name, record ID, output path or mutation command may be accepted by the new preparation runner. Named negative controls must resolve IDs from the task ledger and reject non-owned records.

### Connection and process safeguards

- PostgreSQL only, host exactly `127.0.0.1`, port exactly `55432`. Regression target exactly `osds_test`; browser target exactly `osds_ui_browser_test`. Reject `osds_dev` and every other database before opening any PDO, including aliases and reconnects. No fallback.
- Install a verification-only PostgreSQL resolver before Laravel bootstrap. Validate resolved read/write connection configuration before invoking a lazy PDO closure; do not rely solely on an after-connect event. Check actual `current_database()`, `host(inet_server_addr())`, `inet_server_port()` before each preparation operation and in each server/worker process. Refuse mismatches; log safe observed fields.
- Override process environment in PHP's environment, `$_ENV` and `$_SERVER`; clear URL-based DSN overrides. Refuse cached application configuration rather than clearing or changing it. Do not edit `.env` or `.env.testing`.
- Regression: `APP_ENV=testing`, `DB_CONNECTION=pgsql`, fixed host/port/database, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `MAIL_MAILER=array`; direct PHPUnit PHP process with `memory_limit=512M`. Credentials come privately from testing configuration and never appear in commands/logs.
- Browser: `APP_ENV=sandbox`, fixed browser database and loopback port **8044**; task-specific file sessions/private session directory, `SESSION_COOKIE=uiux_acceptance_session`, array cache/mail. Distinct Playwright contexts per identity; no shared login cookies, injected authenticated sessions or external requests. Fail if port is occupied; do not stop another server.
- Preserve intentional test connection aliases/timezones while validating their database/endpoint. An existing blank-download regression temporarily names `osds_dev` in configuration while retaining an already-connected test PDO; preserve that assertion. Any attempt to open/reconnect to that name must fail before PDO. A guard conflict is BLOCKED, not permission to weaken the test.

## 4. Proposed commands

**Not executable instructions for this task.** The wrappers below do not yet exist. Their implementation must enforce stage approvals and fixed argument sets before these commands can run. Use the project's verified PHP/Node binaries; do not source a shell helper that performs unsolicited database probes.

```powershell
# B1 only: fixed focused subset, without the historical JSON writers.
php -d memory_limit=512M scripts/Run-UiUxRegression.php targeted

# B2: inspection first, then single-attempt additive foundational preparation.
php scripts/Prepare-UiUxBrowser.php inspect
php scripts/Prepare-UiUxBrowser.php prepare

# B2 + B3: guarded server and actual login/UI workflow driver.
php -S 127.0.0.1:8044 scripts/uiux-acceptance-server.php
node scripts/verify-uiux-acceptance.mjs
node scripts/inspect-uiux-pdfs.mjs

# B1 + B4: archive-before-run wrapper, full suite and restoration in finally.
php -d memory_limit=512M scripts/Run-UiUxRegression.php full
php scripts/Finalize-UiUxEvidence.php verify

# B4 final file-based checks; no formatter rewrite of runtime files.
php vendor/bin/pint --test
npm run build
git diff --check
```

Each PHP child receives the same fixed environment and bootstrap guard; parent environment alone is insufficient proof of isolation. The regression wrapper uses PHPUnit directly, avoiding an Artisan child that loses the CLI memory setting. Syntax-check every newly created helper before database execution. Build artifacts must remain local and be inventoried. No `migrate:fresh` or seeder command is proposed against the browser database.

## 5. Fixture namespace, ownership and exact permission manifest

Namespace: **`uiux_20261027_a01`**. Its prior availability is not a reservation. Reject collisions at preparation; do not merge into old accounts, auto-select another namespace or reset existing fixtures. Emails use `<suffix>@uiux-20261027-a01.invalid`; labels begin with the namespace. All accounts are synthetic with fresh private random passwords and explicit existing permissions; no role-bundle synchronization. Accounts are active except `inactive`, whose status is the existing `inactive` value.

The following baskets are closed lists. A profile's permissions are exactly the union stated below; expand and persist that union in the private ledger before writes.

| Basket | Exact existing permissions |
| --- | --- |
| P | `platform.admin.access` |
| I | P + `applications.intake.view`, `applications.intake.answers.view`, `applications.screening.history.view` |
| E | I + `applications.screening.start`, `applications.corrections.request`, `applications.evaluation.record`, `applications.intake.evidence.open` |
| C | I + `applications.recommendations.record` |
| D | I + `applications.decisions.record` |
| A | `applicant.portal.access`, `applications.draft.create`, `applications.draft.view`, `applications.draft.update`, `evidence.references.manage`, `evidence.references.open.own`, `evidence.review.copy.create`, `applications.submit`, `applications.track.own`, `applications.corrections.view.own`, `applications.resubmit`, `issuance.requests.submit` |
| Q | `questionnaires.view`, `questionnaires.draft.manage`, `questionnaires.review.submit`, `questionnaires.publication.view`, `questionnaires.preview.sandbox` |
| G | `documents.view`, `documents.generate`, `documents.download`, `documents.recover` |

| # / suffix | Purpose | Exact permission union | Effective scope |
| --- | --- | --- | --- |
| 1 `osds` | Configuration navigation, fictional office/personnel author, PDF generator | I + Q + G + `forms.downloads.manage`, `forms.downloads.view`, `issuance.definitions.manage`, `documents.personnel.manage`, `users.view`, `institutions.view`, `regions.view` | Primary region, both modules |
| 2 `config-reviewer` | Configuration review navigation and new personnel reviewer/publisher | P + `questionnaires.view`, `questionnaires.review`, `questionnaires.publication.view`, `issuance.definitions.review`, `issuance.definitions.publish`, `documents.view`, `documents.personnel.review`, `documents.personnel.publish` | No case-review assignment |
| 3 `evaluator` | Main evaluation and correction actor | E | Primary, both |
| 4 `chief` | Main recommendation actor | C | Primary, both |
| 5 `director` | Main final-decision actor | D | Primary, both |
| 6 `evidence-reviewer` | Assigned evidence test-review navigation | P + `evidence.reviews.view`, `evidence.reviews.open`, `evidence.review.findings.create`, `evidence.review.history.view` | Primary, both; separate exact evidence assignments when a real UI-created review copy exists |
| 7 `read-only` | Intake view without stage actions | I | Primary, both |
| 8 `questionnaire-view` | Zero-stat configuration visibility | P + `questionnaires.view` | No case assignment |
| 9 `combined` | Combined descriptions and lifetime role-separation probes | E + C + D | Primary, both; not a substitute for the three main actors |
| 10 `panel-only` | No-shortcut dashboard | P | No assignments |
| 11 `no-panel` | Direct-route/panel denial | `applications.intake.view` only | No assignments |
| 12 `inactive` | Inactive-account denial | E, account inactive | Primary, both |
| 13 `revocation` | Mounted permission removal | E | Primary, both; remove only its evaluation permission for named probe |
| 14 `wrong-region` | Region denial despite action permissions | E + C + D + G | Probe region only, both |
| 15 `cor-only` | ACSA module denial | E + C + D + G | Primary, COR only |
| 16 `acsa-only` | COR module denial | E + C + D + G | Primary, ACSA only |
| 17 `applicant` | Main owner and representative | A + `forms.downloads.view` | Owner institutions below, both |
| 18 `unrelated` | Cross-owner/cross-institution denial | A | Unrelated institution only, both |

No omitted permission may be added to make a failure pass. If a destination requires another existing permission, report the exact dependency and request an amended manifest. These are test profiles, not new application roles.

### Foundational object ledger

| Object | Planned count / ownership and relationships |
| --- | --- |
| Regions | **2 new synthetic regions**: `..._primary` is the sole successful routing region; `..._probe` exists only for wrong-region denial. The second region is explicitly included in the B2 request, not an existing region borrowed silently. |
| Institutions | **3 new synthetic institutions**, all primary region: `..._owner`, `..._auxiliary`, `..._unrelated`. Owner represents the first two; unrelated applicant represents only the third. Auxiliary supports bounded terminal/separation probes without reopening a terminal case. |
| Representation | 3 verified effective institution/account bindings as above, created through approved foundational fixture paths, not broad production authorization seeders |
| Eligibility | Both modules for each of the 3 institutions, 6 effective synthetic grants |
| Regional authority | Exact region assignments for each scoped staff profile; no nationwide grant. Probe account receives only probe-region authority. |
| Application reviewer assignments | One exact region/module assignment for each listed case-review scope: both modules for osds/evaluator/chief/director/read-only/combined/inactive/revocation/wrong-region; COR only for cor-only, ACSA only for acsa-only. No implicit evidence-review assignment. |
| Office directory | One new immutable directory revision appending **one fictional office entry** with a new key; preserve existing entries unchanged. This advances the current directory revision and requires explicit B2 approval. |
| Personnel versions | Exactly **2 new** versions, COR and ACSA for primary region, referencing the newly created fictional office/directory revision; reviewed then published through existing UI |

Fix effective dates once from recorded preparation time: start one day before and end 30 days after, if the existing contract accepts these bounds. Do not edit immutable grant dates later. Preserve the actual timestamps in the ledger. Namespace absence, uniqueness and scope compatibility are checked immediately before inserts, within the preparation transaction where applicable.

The private ledger records database/endpoint, run ID, source hashes, symbolic key → actual ID, exact permission union, grants/assignments, timestamps, creation outcome and ownership for every record/file. Record every UI-created case, working revision, submission, correction, stage, request, version, document, attempt and audit linkage. Never retrieve existing applicant values to populate it. Passwords/cookies remain in separate private files, absent from public evidence and command lines.

## 6. Synthetic answers and evidence policy

COR protected organization/address question IDs are **4550/4551**; ACSA **4552/4553**, with additional required text questions **4554–4556**. B0 observed unconditional required short-text fields. Read their actual labels only through the authorized UI before supplying clearly fictional values; unexpected sensitive or unsupported requirements stop the affected case.

Use namespace-prefixed fictional organization names and “Fictional demonstration address R1”; correction changes both organization and address to explicit **R2** values. Each final immutable submission and PDF must contain R2 values and exclude the superseded R1 identity/address. Supplementary required long-text keys are COR `question_9urovq`, ACSA `question_3hteiz`; supply bounded fictional notes through the request UI.

Required intake evidence is exactly one reference per module: COR requirement **1495** / `document_0skty1`, ACSA **1496** / `document_ja9odh`. Both accept enabled `google_drive_file` / `google_docs` providers. Propose **format-only fictional Google Drive references**, using distinct deterministic synthetic IDs accepted by the existing parser, never real personal file IDs. Keep raw references private. No remote fetch, upload or Google account operation is authorized by this package.

Approval of this policy authorizes testing reference syntax, required counts and preserved linkage only. It does **not** prove evidence content, availability or custody. Mark external-content acceptance UNKNOWN/BLOCKED. If a required UI step depends on actual external content, stop and request a separately approved controlled fictional artifact; do not fabricate a successful inspection or use an unrelated real file. Never follow outbound evidence redirects in the browser.

The optional B2 evidence keys `document_wcsadm` / `document_ijtncs` may remain absent under existing minimum-zero rules. Do not pretend they were fulfilled or add uploads. B2 cannot collect new supplementary evidence.

## 7. Personnel workflow and main UI journeys

After B2 and B3 approval, `osds` uses the existing unlinked-preview settings page's **new office** controls to append a wholly fictional office, director and designation with explicit demonstration provenance. Do not modify the legacy preview form or any old office. The new directory revision necessarily carries unchanged old entries; evidence exports must omit their personnel values.

On Case-linked demonstration PDFs, `osds` creates a primary-region personnel draft for each module referencing the new directory revision/key. `config-reviewer` reviews and publishes each exact new version through the existing controls. Record optimistic versions, IDs and audit/history. Concurrent directory/publication changes stop the operation; do not silently refresh and resubmit. No template creation or withdrawal is included.

For **COR and ACSA independently**, complete these real controls/handlers:

1. Applicant logs in, starts the fixed published intake, fills fictional answers and the approved synthetic reference, saves/reviews and submits R1.
2. Evaluator opens assigned intake, starts screening as required and requests corrections through the existing correction workflow.
3. Applicant reads the actual correction, edits the working copy to R2, saves and resubmits. Check immutable R1 and distinct current R2 using authorized observations.
4. Evaluator screens/evaluates R2, Chief recommends and Director approves, using three distinct accounts. Verify exact current submission/decision linkage and applicant-visible history.
5. Applicant opens the qualified request link, checks protected R2 details, saves/reloads a request draft and explicitly submits. Existing definition 54/55 remains unchanged.
6. Authorized `osds` selects exact template 43/45 and newly published matching personnel version, explicitly generates once, then applicant downloads the completed original.
7. Privately compare downloaded bytes with the actual stored PDF, SHA-256, page count, immutable case/request/document linkage and expected module title. Inspect **every page** for fixed `DRAFT — DEMONSTRATION ONLY` and the nonofficial disclaimer. Confirm R2 identity/address and absence of superseded R1 values. Do not infer completion from a database status alone.

No successful business-case mutation may be prepared directly in SQL or by a service fixture. Foundation fixture writes are distinct from application, correction, stage, request, personnel review/publication and document-success paths.

## 8. Acceptance and regression matrix

| Area | Browser/UI acceptance under B3 | PostgreSQL verification under B1/B4 |
| --- | --- | --- |
| Navigation | All 18 profiles; group order, authorized union, active states, mobile drawer/collapse, long labels, keyboard focus | Destination access and direct-route denial, revoked/inactive cases |
| Landing | Actual Laravel Blade/helpers, original sign-in destinations, anchors/skip link, demonstration wording, Light/Dark/System | Existing public route/render coverage where available |
| Dashboard | Real widget discovery/order, combined descriptions, no-task, questionnaire-view zero-stat behavior; counts remain configuration counts | New DashboardGuidanceTest and unchanged count/access contracts |
| Application guidance | Actual draft/submitted/screening/correction/resubmitted/approved states in both journeys; rejection on auxiliary case; publication notice and missing-working-copy branches only where safely reachable | Existing lifecycle/retirement/working-copy matrices; do not retire preserved roots to manufacture browser states |
| B1 authority | Wrong region/module, unrelated account, stale mounted revision, combined-account cross-stage denial on auxiliary cases | Exact assignments, lifetime three-person separation, immutable revisions, competing decisions |
| Requests | Draft/submitted wording and actual service reasons; approved state must not imply eligibility | Missing/withdrawn/mismatched definition, binding, duplicate and immutable snapshot guards |
| PDFs | Explicit original generation, same-request duplicate denial, authorized exact download, cross-owner/region denial | Renderer/finalization authorization, timeout/failure/recovery, private access and integrity incident safeguards |
| State separation | Observe submitted request independent of real document state; do not call request “completed” | Absent/processing/failed/completed and integrity branches from existing tests |

Never withdraw preserved roots to obtain a negative state. Browser withdrawal scenarios are **BLOCKED under Option A** unless a later additive, isolated scenario is separately approved; existing PostgreSQL tests provide the applicable behavioral regression. Likewise unknown-state text may remain component-only coverage; do not insert invalid runtime states.

Bounded browser negatives may change only ledger-owned identity permissions/status/assignments, once explicitly covered by B3. A permission-loss probe removes the dedicated revocation account's evaluation permission after mounting, checks actual denial and unchanged writes, then inspects before any separately explicit restoration. Main three actors remain untouched. Auxiliary cases support same-person denial and rejection without renewal/reopening. Cross-owner probes use only IDs already created by this task.

For uncertain responses, intercept/drop only the task browser's response after an actual action; inspect the saved server outcome before any explicit subsequent action. No automatic click replay. PDF missing/corrupt-file probes are restricted to newly generated, ledger-owned auxiliary artifacts with a checksum-identical private backup and explicit B3 approval. Never alter a baseline original. Restore only verified identical bytes through the existing authorized recovery workflow; if that workflow cannot safely restore it, retain the incident and stop.

Do not fabricate failed/processing document records. Observe genuine processing when possible; otherwise retain browser coverage as BLOCKED and use existing targeted failure/timeout tests. Any additional test-only renderer/storage fault adapter requires a separately reviewed exact design before execution; this package does not grant arbitrary handler substitution.

### Responsive sampling

Plan **64 core captures**: landing, staff dashboard, staff navigation/intake, applicant home, correction tracking, request draft, submitted request and completed PDF workspace × 375/768/1024/1440px × light/dark. Add **12 ACSA captures**: correction, approved-case guidance and submitted-request/PDF workspace × 375/1440px × both themes. Label module/profile/state in every filename; do not count a download viewer as a PDF workspace capture.

Add 320px reflow and 200% root-text-size samples for representative dashboard and applicant/request pages in both themes, recorded separately with their actual totals. Use unrestricted default axe plus target-size, overflow, uncaught-error, complete accessible-name and visible-focus checks. Retain violations and incomplete results. Do not change thresholds, dismiss notifications earlier or tune capture timing to manufacture passes. Transient notification diagnostics stay separate from standard captures. Human keyboard/screen-reader/native zoom and supervisor acceptance remain separate and pending.

### Regression commands and scope

B1 targeted mode selects `DashboardGuidanceTest`, `ApplicantApplicationGuidanceTest`, `FormMakerTest`, `QuestionnaireConfigurationTest`, `ApplicantSandboxTest`, `ApplicationDecisionFoundationTest`, `CaseIssuanceRequestTest`, `DemonstrationDocumentTest` and `DownloadableFormsTest`, after confirming current filenames and evidence-writer exclusions. Missing/renamed tests stop command construction; never silently skip them. These names are proposed filters, not asserted executed totals.

B4 full mode runs the complete current-tree PHPUnit configuration, not just the focused list. Existing historical totals are not predicted current results. Protect timestamp alias behavior and genuine audit/storage failure assertions. If a label locator needs maintenance, change only verification selectors after review, retaining actual handlers and persistence assertions. Runtime changes require separate authorization and renewed regression planning.

## 9. Database operation inventory and cleanup limits

| Gate / database | Proposed write effects | Preservation / cleanup |
| --- | --- | --- |
| B1 `osds_test` | `RefreshDatabase` may drop/recreate schema objects and run existing migrations; test inserts/updates/deletes, grants, audit/history and sequence changes | **Existing test records may be destroyed.** No promise of data rollback for schema replacement; disposable database approval is required. Serialize runs. |
| B2 browser | Add manifest accounts, explicit permission pivots, regions/institutions, representation/eligibility and exact scope assignments; associated normal history/audit. Add new directory revision and 2 personnel versions through later authorized UI. | No schema reset, old record deletion or configuration disposition. New global directory revision appends office while preserving old entries. |
| B3 browser | Real sessions, drafts/submissions/evidence links/corrections/stages/history/audits, request drafts/submissions, document attempts/originals/incidents; named negative changes to owned grants/actors only | All successful case mutations through real UI; no deletion of immutable histories. New PDFs/private logs explicitly ledger-owned. |
| B4 `osds_test` and files | Full-suite disposable database DDL/DML; temporary diagnostic JSON rewrites and new test artifacts | Archive new outputs, restore originals byte-for-byte, report hashes and actual failures even if restoration succeeds. |

Preparation owns a single transaction per foundational manifest operation. Before first commit, a failure rolls back its writes. After a transport/commit uncertainty, stop and inspect namespace/ledger with a new guarded operation; do not rerun automatically. Existing immutable cases or completed originals are never overwritten as an idempotency mechanism.

Browser cleanup defaults to **retention**, not deletion: preserve ledger-owned immutable history and documents for review. Stop only task-owned server/browser processes. Remove no baseline files or old records. Deactivation/revocation of temporary identities or deletion of disposable artifacts requires an explicit itemized cleanup decision; it is not implicit permission to erase audits. `osds_test` may remain in its final disposable test state. No cross-database cleanup.

## 10. Evidence preservation, failure recovery and final deliverables

Private run storage: `storage/app/private/uiux-consolidated-acceptance/gate-b/`. Safe new evidence: `docs/screenshots/uiux-consolidated-acceptance/`; final report: `docs/osds-ui-ux-consolidated-acceptance-report.md`. Keep cookies, passwords, evidence references, raw database metadata, PDF storage keys and diagnostic traces private; redact before public packaging. Preserve failed attempts separately.

Before every execution stage, record HEAD/dirty tree/source hashes and verify preservation against this task's baseline. Snapshot protected root payloads/hashes privately only under approved configuration projections; no applicant data export. Compare roots and all old personnel mappings after browser work. This is future verification, not a new database observation in this package.

Before full suite, copy **all ten historical evidence JSON files** and SHA-256 values to a private immutable snapshot. Existing K1–K5 writers may temporarily rewrite five files: `phase-3d7-k1-institution-update-after.json`, `phase-3d7-k2-eligibility-create.json`, `phase-3d7-k3-institution-create-after.json`, `phase-3d7-k4-region-update-after.json`, `phase-3d7-k5-region-create-after.json` under `docs/evidence/`. Archive generated results separately, then restore all originals in a `finally` path on success/failure/interruption. Discover and record the exact ten-file baseline list before execution; a changed set stops the run. Never rewrite historical outcomes to match new tests.

Stop immediately for identity mismatch/unavailable target; cached config/guard failure; namespace collision; changed fixed root; missing authority; unexpectedly personal data; unsupported evidence dependency; immutable/duplicate violation; uncertain commit/render response; missing/corrupt stored PDF; failed preservation; or a runtime fix requirement. Record exact FAIL/BLOCKED scope. No fallback database, automatic mutation retry, authorization expansion or successor phase.

Final execution report must distinguish browser checks, PostgreSQL tests, static checks, human-pending acceptance and blocked branches. Include commands, actual test/assertion/capture counts, genuine PDF inspections and limitations. A passing suite cannot replace missing authenticated coverage; an automated accessibility pass cannot establish complete WCAG or supervisor acceptance.

## 11. Separate authorization requests — none granted by this document

**Gate B1 — first execution stage.** Request permission to implement the verification-only connection/runner guards and execute the listed targeted tests on PostgreSQL **`osds_test` at `127.0.0.1:55432`**, including destructive RefreshDatabase schema replacement, existing migration execution inside the test lifecycle and fixture DML. Existing records in that database may be destroyed. No browser database access, fixture preparation, login or full-suite evidence writers under B1 alone. Full execution waits for B4 too. Stop after targeted results for review.

**Gate B2 — additive browser preparation.** Separately request guarded access to **`osds_ui_browser_test` at the same endpoint**, fixed-root/namespace rechecks and the exact 18-account/2-region/3-institution foundational manifest, explicit permission and assignment DML. Request one append-only fictional office directory revision and two primary-region personnel versions, with their actual UI creation/review/publication deferred until B3 is also authorized. Preserve every existing published root and mapping; no browser schema change/reset/retirement/withdrawal. Review private ledger before business-case execution.

**Gate B3 — authenticated acceptance.** Separately request real login submissions and UI mutations for the owned namespace in the browser database; new office/personnel UI setup; both fixed-root COR/ACSA journeys; format-only fictional evidence policy; PDF generation/download; stated auxiliary/revocation/region/module/duplicate/uncertain-response checks; and only owned auxiliary PDF integrity/recovery probes. Explicitly acknowledge external evidence content, old-root withdrawal and fabricated failed states are excluded. No global fault adapter or runtime edit is included. Retain new immutable records and safe evidence for review.

**Gate B4 — full regression and final evidence.** Separately request complete current-tree tests on disposable **`osds_test`**, including repeated test-lifecycle schema replacement/DML; temporary writes by the identified diagnostic evidence writers; private archive plus checksum-identical restoration of all ten originals; test-owned artifact creation; file-based formatting/syntax/build/whitespace checks; and final preservation/report packaging. Requires B1 guard approval and review of actual earlier outcomes. Does not authorize browser resets, old PDFs changes, commits, pushes or deployment.

## 12. Preservation verified for this planning task

Before creating this document, Git HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and SHA-256 values for **2,786 existing files**, including **73 PDFs**, were recorded privately under `gate-b-readiness/`. The 73-PDF count is this task's current discovered baseline, not a restatement of the earlier reports' 71-file baseline. No claim about the provenance of the two additional PDFs is inferred from that difference.

Post-write preservation results in `gate-b-readiness/preservation.json` show **2,786/2,786 baseline files unchanged**, including **73/73 PDFs**, with zero changed or missing files. The requested document is the only new nonignored deliverable. Baseline source/evidence, pending remediation, Phase 1–3 changes and unrelated `scripts/.batch-g-recovery-current.mjs` remain byte-identical. Tracked whitespace checks and the new document's trailing-whitespace check passed; existing CRLF advisories remain. No database-backed verification has occurred.

**Stop for review. Await explicit Gate B1 authorization before the first execution stage. Gate B2–B4 remain independently closed.**
