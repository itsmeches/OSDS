# Gate B0 — Existing configuration compatibility assessment

**Latest status — authorized continuation: PASS for bounded structural reuse assessment.** The exact COR and ACSA configuration roots were revalidated in one read-only connection to `osds_ui_browser_test`. Option A is supported for the inspected synthetic journey structures, subject to later account/evidence/personnel preparation and authenticated verification. Full payload rehash and runtime success remain UNKNOWN/BLOCKED. Gate B remains **NOT AUTHORIZED**.

## Initial B0 preflight — historical assessment

The following preflight is retained as the record before the helper extension was approved. Its UNKNOWN/BLOCKED results and statements that no helper/database operation occurred describe that earlier stage. Current executed findings appear in the continuation section below. A byte-preserved copy of this earlier report is held privately.

**27 October 2026. Overall status: BLOCKED at the inventory-helper capability gate. Source assessment and bounded extension proposal completed. No new database connection, helper change, fixture, authentication, test or document generation was performed. Gate B remains NOT AUTHORIZED.**

## 1. Scope reached and evidence limits

The user authorized read-only compatibility assessment but explicitly required a stop for review if the existing Gate A helper cannot safely support the necessary inspection.

The inspected `scripts/Run-UiUxAcceptance.php` accepts only `inventory --database=osds_test` or `inventory --database=osds_ui_browser_test`. It reports schema objects, migration names, publication IDs/dates, definition source-form IDs and document-version slot metadata. It **does not retrieve the configuration structures or bindings needed by B0**: intake question validation/conditions/options, evidence provider/count restrictions, definition identity question IDs/supplementary structure, PDF merge fields or personnel structural completeness.

Therefore, the requested compatibility verdict cannot be established with the unchanged helper. No generic SQL client, temporary query script, Laravel bootstrap or alternate database access path was used to bypass that boundary. The helper remains byte-identical at SHA-256 **`3315A5526187C97A72E56E5CBCBA7A2605B06B572142EC5BD3F537F112D375CF`**.

Source reviewed includes:

- `app/Services/Applicants/ApplicantDraftService.php` and the existing intake/publication contracts.
- `app/Services/Documents/CaseIssuanceService.php`.
- `app/Services/Documents/DemonstrationDocumentService.php`.
- `app/Services/Questionnaires/ConfigurationHasher.php`.
- `app/Models/FormQuestion.php` and `app/Models/DocumentaryRequirement.php`.
- The existing Gate A helper and [completed Gate A report](osds-ui-ux-gate-a-inventory-report.md).

### Read-only identity verification

| Evidence | Result / limit |
| --- | --- |
| Previous Gate A continuation | **PASS, historical:** both approved databases verified as PostgreSQL at `127.0.0.1:55432`, effective/default read-only `on`, then ROLLBACK |
| New B0 database connection | **NOT ATTEMPTED:** helper cannot collect the required structures within its existing operation |
| Current IDs, states and dates revalidated for B0 | **BLOCKED:** requires the reviewed extension below |
| Existing configurations proven incompatible | **NO:** no configuration failure is established; missing evidence is UNKNOWN, not FAIL |

Gate A migration/schema findings remain historical observations. They do not constitute current B0 validation or authorization to mutate records.

## 2. Exact requested configurations and last known metadata

These are the records requested for B0. They were **not newly read in this assessment**. Metadata below comes from the completed Gate A inventory; live states must be rechecked before relying on them.

| Module | Intake publication | Issuance definition | PDF template |
| --- | --- | --- | --- |
| COR / `registration` | **198**, form 591, requirement set 591, approval 264; applicant_sandbox; unretired and within date window at Gate A | **54**, published v45, source form 591, approval 266 | **43**, published template v19 |
| ACSA / `acsa` | **199**, form 592, requirement set 592, approval 265; applicant_sandbox; unretired and within date window at Gate A | **55**, published v10, source form 592, approval 267 | **45**, published template v7 |

The intake approval and supplementary-definition approval are different records and must not be substituted for one another. Matching source-form IDs alone do not prove that protected answer bindings, evidence keys or template fields are satisfiable.

Gate A also recorded these published personnel mappings:

| Module | Mapping ID → region ID / directory revision ID |
| --- | --- |
| COR | 2 → 370/10; 4 → 373/11; 6 → 374/12; 8 → 375/13; 12 → 377/15; 14 → 378/16; 16 → 379/17; 19 → 380/18; 22 → 381/19; 28 → 383/21; 40 → 387/24; 44 → 389/25 |
| ACSA | 25 → 382/20; 31 → 384/22; 42 → 387/24; 46 → 389/25 |

These are historical slot identifiers only. Their current state, configuration completeness and suitability for fictional personnel have not been rechecked. No director name, designation, applicant answer or evidence locator was retrieved.

## 3. Compatibility matrix

| Requirement | COR | ACSA | Evidence needed for a verdict |
| --- | --- | --- | --- |
| Requested publication still exists, is unretired, correct channel/module and within its dates | UNKNOWN | UNKNOWN | Fresh fixed-ID metadata and retirement/date checks |
| Published intake can start a new synthetic case | UNKNOWN | UNKNOWN | Current form/requirement states, module status, configuration coherence and proposed effective synthetic representation/eligibility; actual start remains a later UI test |
| Required questions can accept controlled fictional values | UNKNOWN | UNKNOWN | Exact question types, required flags, options, validation and conditional dependencies; no existing applicant answers |
| Evidence requirements can be satisfied without external personal material | UNKNOWN | UNKNOWN | Applicable requirement keys, min/max, required/conditional rules and permitted provider codes; separate syntax/reference validation from actual custody/content verification |
| Issuance definition points to the corresponding intake form | Historical metadata match: 54 → 591 | Historical metadata match: 55 → 592 | Recheck IDs/state/module, source references and definition structure; do not treat historical match as complete compatibility |
| Protected organization/address bindings are valid and satisfiable | UNKNOWN | UNKNOWN | `name_question_id` / `address_question_id` select applicable text questions in the exact intake form; planned fictional R2 values can remain nonblank |
| Supplementary request rules and evidence keys fit the approved submission | UNKNOWN | UNKNOWN | Definition question/requirement structure; compatible requirement keys/counts; reserved core keys not repurposed |
| Template fields fit the submitted definition | UNKNOWN | UNKNOWN | Template layout and `content.fields` subset of definition question keys, plus bounded plain-text structure |
| Existing personnel mapping usable for the planned routing region | UNKNOWN | UNKNOWN | Same module, exact region, current published state and structurally complete mapping/directory linkage; no borrowing another region's identity |
| New synthetic region can have a separate mapping | PASS, source capability only | PASS, source capability only | Existing uniqueness is module/kind/region; new mapping creation/review/publication and its fictional directory dependency need separate Gate B approval |
| Existing publication dates prevent the journey | UNKNOWN currently; Gate A saw no date-window blocker | UNKNOWN currently; Gate A saw no date-window blocker | Recheck against current server time, without rewriting dates |
| End-to-end runtime success demonstrated by B0 | BLOCKED | BLOCKED | No fixtures, approvals, requests, generation or authentication authorized in B0 |

No row above establishes a failed configuration. **Reuse remains unproven for both modules.**

### Source-level constraints that a reuse strategy must satisfy

- Intake drafting/saving retains synthetic identity, representation, eligibility, publication and immutable-working-copy restrictions. The assessment cannot make an existing account or case eligible by inspecting a configuration.
- B2 requires a matching final B1 Director approval of the current immutable submission, no superseding working revision, the matching published definition and nonblank applicable approved organization/address answers. Existing definition source IDs must bind to the intended text questions, not merely similar labels.
- Request evidence requirements use already approved submission evidence by requirement key. A supplementary requirement cannot be satisfied by an arbitrary applicant claim or by collecting a new file through B2.
- The PDF service requires exact published template/personnel versions in the same module and the **personnel region equal to captured request routing**. Every configured template supplementary field must exist among the submitted definition's question keys.
- A new region does not inherit the old personnel mappings. Reusing templates and intake/issuance definitions can still require **new personnel configuration writes**, even under Option A. Never change the application's captured region just to match an existing mapping.
- Dates, processing requirements and immutable publication/version contracts cannot be bypassed. No nine-day waiting rule or both-modules prerequisite is introduced.

## 4. Bounded helper extension proposed for review

**Not implemented or executed.** Extend only the dedicated verification helper, not application runtime code. Preserve the existing inventory operation and its snapshots.

Proposed additional operation:

```powershell
# Proposed only; the unchanged helper currently rejects this operation.
php scripts/Run-UiUxAcceptance.php compatibility --database=osds_ui_browser_test
```

The new mode must reject every other database target, arbitrary record ID, SQL fragment, connection URL or output directory supplied through the CLI. It retains the existing explicit PostgreSQL endpoint, canonical server-address comparison, recorded field-by-field identity results, default read-only session, explicit repeatable-read read-only transaction, timeouts and final rollback. Stop on an unavailable database, identity mismatch or missing/changed required configuration; no automatic retry or substitution of newer IDs.

### Exact read scope

| Root / dependency | Necessary projection and checks |
| --- | --- |
| Publications **198/199** | Revalidate exact ID, module/channel, linked form/requirement/approval IDs, dates, retirement existence and stored configuration hashes. Reject unexpected graph changes before fetching dependent structures. |
| Intake forms **591/592** and requirement sets **591/592**, only while confirmed by those publications | Named version/module/state fields; section/question IDs, keys/order, types, required flags, options, validation and condition structures; requirement IDs/keys, required/min/max/conditional rules and accepted provider codes. Follow only their relevant `form_sections`, `form_questions`, `form_question_options`, `documentary_requirements`, `documentary_requirement_provider` and `evidence_provider_types` dependencies. |
| Referenced configuration review/approval records | Only exact version/module/outcome/state/hash linkage needed to check publication/definition coherence; exclude reviewer personal data and free-text comments. |
| Definitions **54/55** | Revalidate published/module/source-form state; read only protected question IDs, source form ID, supplementary question/requirement structures and stored hash/configuration reference fields needed for validation. Compare binding IDs/types/applicability with exact intake questions. |
| Templates **43/45** | Revalidate published kind/module; project layout, merge-field keys, content hash and bounded structure checks. Check plain-text wording length/markup/link flags without exporting raw wording. Compare fields with each definition's supplementary keys. |
| Personnel mapping IDs listed above | Only exact ID/module/region/state/version/directory linkage and SQL-derived completeness/type booleans for required office fields. Do not select director names, designations, full office objects or provenance text. Follow referenced directory/region rows only for linkage/active-state/completeness checks, not personal values. |
| COR/ACSA modules | Revalidate the expected codes and active state using only the module references attached to the fixed roots. |

Do not fetch bulk JSON rows with `SELECT *`. Project only the approved configuration keys or derive safe booleans/lengths in SQL, especially for personnel/wording. Keep configuration structure results private; public reporting should contain compatibility outcomes, necessary IDs and proposed synthetic fixture requirements rather than raw content. If exact configuration-hash recomputation needs additional text fields not approved by this projection, report that verification UNKNOWN rather than quietly retrieving them or claiming a full hash check.

**Excluded:** application drafts/answers, submissions/answers/evidence items, issuance requests/identity snapshots/answers, generated documents/render snapshots, user credentials/contact values, evidence URLs/locators, audit payloads and unrelated configuration graphs. The extension must have no write command, login/session creation, rendering, validator path with side effects or fixture preparation. Do not call application service operations to “test” compatibility.

If another table/field or an unlisted configuration is needed, stop and itemize the additional requirement. Do not widen the allowlist or select a replacement version automatically.

### Proposed analysis output

For each module, record fixed-root revalidation, structural validity, conditional dependencies, satisfiable fictional-value requirements, evidence count/provider requirements, protected binding checks, template-field subset checks and personnel-region requirements. Distinguish PASS, FAIL and UNKNOWN per check. Matching stored hashes/references alone must not be described as full payload validation. Synthetic values are newly planned examples, never copied from existing applicants.

Raw diagnostics should go to `storage/app/private/uiux-consolidated-acceptance/gate-b0/`, with a timestamped helper hash, explicit read-only identity log, query/projection scope and rollback outcome. Preserve the existing Gate A logs/report. Updating this B0 report would then be authorized documentation; Gate B would still remain closed.

## 5. Strategy comparison and recommendation

| Strategy | Disruption | Current verdict |
| --- | --- | --- |
| **A — Reuse existing published intake/definitions/templates** | Lowest if compatible. Old versions remain untouched; additive synthetic identities/cases and potentially new fictional directory/personnel versions still need Gate B authorization. | **Preferred target, conditional.** Cannot approve reuse before the extended read-only checks. Reuse does not test creation/publication of replacement configurations. |
| **B — Fresh configurations** | Requires explicit exact-ID retirement/withdrawal where slots conflict; immutable history remains but old versions may not be reactivated. | **Not justified yet.** No incompatibility has been established. Do not retire publications 198/199, definitions 54/55 or templates 43/45 merely to simplify a harness. |
| **C — Partial acceptance** | Preserve existing configurations and mark incompatible/unsupported journeys BLOCKED. Read-only page acceptance may be possible later while publication/generation coverage remains limited. | **Fallback if reuse cannot be established or additive personnel configuration is declined.** Even authenticated partial acceptance needs separate authorization; none runs now. |

Recommend approving the bounded read-only extension first, then choosing **Option A only for configurations that pass**. Use Option C for genuine remaining gaps. Consider Option B only after a specific incompatibility and exact affected IDs are documented. This avoids withdrawing working configurations before learning whether withdrawal is necessary.

## 6. Exact operations requiring later Gate B authorization

If Option A is proven compatible, later approval must separately cover:

1. Additive synthetic identities with individually granted existing permissions; synthetic institutions/regions, representation, eligibility and reviewer/regional assignments. Recheck namespace and current configuration state before inserts.
2. New fictional directory/office configuration if necessary, and separate personnel mapping creation → review → publication for **each module** in a new routing region. These are configuration writes, not implied by permission to add accounts.
3. Real login submissions and UI-based application drafts/submissions, correction/R2, three-person stages, requests, explicit PDF generation and authorized downloads. Reuse must not mutate the six existing published roots.
4. Controlled negative tests and new-artifact integrity/recovery probes with exact fixture/file ownership. No baseline PDF may be altered.
5. Disposable `osds_test` schema replacement/reference fixtures and targeted/full regression, temporary diagnostic evidence output/restoration and scoped artifact handling under the consolidated plan.

If Option B becomes necessary, additionally identify and approve the exact old publication/definition/template dispositions. Existing personnel mappings should not be bulk-withdrawn. No browser reset, immutable-record deletion or manual activation is proposed.

None of these operations is authorized by B0. No new migration is indicated by the historical Gate A ledger, but that does not authorize a schema operation.

## 7. Preservation and stopping point

A file-based preflight baseline recorded Git state and **2,783 existing file hashes**, including **71 PDFs**, under `storage/app/private/uiux-consolidated-acceptance/gate-b0-preflight/`. The helper, completed Gate A report/logs, application runtime, Phase 1–3 changes/evidence, pending remediation, historical evidence and unrelated Batch G script were not edited. The only new requested deliverable is this report; private preservation metadata is ignored verification output.

No database was connected to during B0, including `osds_ui_browser_test`, `osds_test` and `osds_dev`. No configuration rows, applicant answers, credentials, personal evidence or personnel values were retrieved. No fixture, login, test, migration, document generation, retirement, withdrawal, commit, push or deployment occurred. Batch G remains CONDITIONAL CLOSE.

**Stop for review of the bounded helper extension. B0 configuration compatibility remains UNKNOWN/BLOCKED; Gate B remains NOT AUTHORIZED.**

## Authorized B0 continuation — executed findings

### Helper, operation restrictions and private evidence

The user authorized the proposed fixed-scope helper extension and one controlled compatibility inspection. Only `scripts/Run-UiUxAcceptance.php` was extended; no application runtime file was changed.

| Item | Recorded value |
| --- | --- |
| Previous helper hash / preserved snapshot | `3315A5526187C97A72E56E5CBCBA7A2605B06B572142EC5BD3F537F112D375CF` |
| Executed extended helper SHA-256 | `8BF4EAF66BE2BC60F81B6BC1FC4B6C4AF2D006F87BB31D3D292FED22F4E53D1E` |
| Executed operation | `php scripts/Run-UiUxAcceptance.php compatibility --database=osds_ui_browser_test` |
| Database connection attempts for the inspection | One, to the approved browser database only |
| Raw result | `gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json` |
| Inspection recorded at | 2026-10-27 07:17:58 UTC |
| Exact preconnection SQL projection manifest | `gate-b0/approved-projections-20261027-071758.json` |
| Offline structural comparison | `gate-b0/structural-analysis.json` |

Private paths are relative to `storage/app/private/uiux-consolidated-acceptance/`. The executed helper snapshot/hash, pre-extension helper snapshot, baseline and prior report copy are also retained under `gate-b0/`.

PHP syntax and scoped Pint passed before execution. Database-free CLI rejection checks confirmed that compatibility targeting `osds_test`, an extra record-ID argument and a migration operation all fail with `connection_opened=false`. These were argument-validation checks, not database-backed tests or additional connection attempts. No arbitrary ID, SQL, URL or output path is accepted.

The original Gate A inventory operation remains available with its original target restrictions. A static comparison of its inventory body, ignoring indentation introduced by the new branch, matched the pre-extension body. Gate A logs/helper snapshots were not overwritten. The B0 compatibility mode is limited to `osds_ui_browser_test`; the preserved inventory operation was not invoked during this continuation.

The projection manifest was written before connection. After identity verification, **16 fixed configuration SELECTs** ran; no additional table or field was requested. Queries projected approved structures only. Personnel queries returned completeness/type/equality booleans, not names or designations. Template wording was checked using length/type/markup flags without returning its text. No applicant/request/generated-document rows, answers, evidence locators, credentials, personal contact values or audit payloads were queried.

### Identity and transaction verification

| Field | Expected | Observed | Result |
| --- | --- | --- | --- |
| Driver | `pgsql` | `pgsql` | PASS |
| Database | `osds_ui_browser_test` | `osds_ui_browser_test` | PASS |
| Server address | `127.0.0.1` | `127.0.0.1` | PASS |
| Server port | `55432` | `55432` | PASS |
| Effective transaction read-only | `on` | `on` | PASS |
| Default transaction read-only | `on` | `on` | PASS |

The connection used the existing timeouts and an explicit **REPEATABLE READ, READ ONLY** transaction. Additional diagnostics reported PostgreSQL 17.11 and schema `public`. Effective read-only was still `on` immediately before **ROLLBACK**. No fallback or automatic retry occurred. Neither `osds_test` nor `osds_dev` was accessed.

### Exact records inspected and dependency bounds

The fixed publications, definitions and templates were checked before following their dependencies:

| Module | Intake | Issuance definition | PDF template | Current result |
| --- | --- | --- | --- | --- |
| COR / module 1 `registration` | Publication **198**, form **591**, requirement set **591**, approval **264** | **54**, published v45, source form 591, supplementary approval **266** | **43**, published template v19 | PASS: exact requested graph, active module, unretired applicant_sandbox publication within its dates |
| ACSA / module 2 `acsa` | Publication **199**, form **592**, requirement set **592**, approval **265** | **55**, published v10, source form 592, supplementary approval **267** | **45**, published template v7 | PASS: exact requested graph, active module, unretired applicant_sandbox publication within its dates |

Both publication end dates are null; start dates were within the server-time window. This is a snapshot, not a reservation or a future availability guarantee.

Direct structural dependencies inspected: intake form definitions **555/556**, sections **1963/1965**, questions **4550–4556**, requirements **1495/1496**, their option/provider relations, and configuration reviews **295–298** through approvals **264–267**. Approval metadata identifies supplementary source form/requirement IDs **593/594**, but their form/question/requirement content was not followed; B2's approved supplementary structures were read from definitions 54/55 as authorized.

Intake form rows and requirement-set rows retain state **`in_review`**; the corresponding review requests and approvals are **approved**, with published manifests present. Source inspection shows that the current approval/publication flow does not require changing these child rows to a literal `published` state for intake. Do not mutate them to force a different label. Their stored hashes agree with their review/publication references; complete source-payload rehash was not performed.

### COR and ACSA compatibility matrix

“PASS” below means the inspected structure supports the stated fixture plan. It does not mean a fixture was created, a runtime validator was executed, or an end-to-end journey passed.

| Check | COR | ACSA | Interpretation |
| --- | --- | --- | --- |
| Fixed publication/state/channel/module/date gate | PASS | PASS | Exact root IDs retained; module active; applicant_sandbox, unretired and within dates |
| Intake form/requirement module and audience | PASS | PASS | Linked versions belong to the expected module; intake audience is applicant |
| Required questions | PASS: **2** short-text fields | PASS: **5** short-text fields | All required, unconditional, with empty validation structures; no choice options. Controlled nonblank fictional text fits the inspected software constraints. |
| Documentary evidence | PASS structurally: **one** required reference, min/max **1/1** | PASS structurally: **one** required reference, min/max **1/1** | Each is unconditional and accepts enabled `google_drive_file` or `google_docs` providers. Real external content/custody was not inspected. |
| Intake approved-reference/hash linkage | PASS | PASS | Stored form/requirement hashes match the form/set and approved review/publication metadata; combined hash relationship matches |
| Issuance source form | PASS: definition 54 → form 591 | PASS: definition 55 → form 592 | Both the definition column and approved source-form binding agree |
| Organization/address bindings | PASS: question IDs **4550/4551** | PASS: question IDs **4552/4553** | Distinct required short-text questions in the exact intake version, unconditionally applicable |
| Supplementary request configuration | PASS: one required long-text field | PASS: one required long-text field | No conditions, options or extra configured validation; a short fictional note fits the existing request text limit |
| Supplementary approval linkage | PASS: approval 266 | PASS: approval 267 | Correct module, approved review/outcome and matching stored approved-configuration hash |
| Supplementary evidence | PASS, no extra collection needed | PASS, no extra collection needed | Each definition has an optional, unconditional requirement with min/max 0/1 whose key differs from the intake requirement. The inspected service permits its absence when optional. Do not pretend it is already supplied or add an upload. |
| Template merge-field subset | PASS | PASS | Each template references exactly its definition's one supplementary question key |
| Template plain-text structure | PASS | PASS | `b3-letter-v1`, array of field keys, string wording length 61, no markup/link flag; raw wording not retrieved |
| Existing personnel structural availability | PASS: **12** mappings | PASS: **4** mappings | All inspected fixed mappings remain published, exact module/region/directory links retained, regions active and required office fields structurally complete |
| Personnel/directory snapshot linkage | PASS | PASS | Referenced directory/office entry exists; stored office snapshot equals its referenced directory entry, evaluated as SQL booleans only |
| New fictional region ready now | BLOCKED | BLOCKED | No new region/mapping exists from this task; new region needs separately approved mapping configuration for each module |
| Full configuration/definition/template/personnel payload rehash | UNKNOWN | UNKNOWN | Approved projections intentionally omit complete text/personnel payloads. Stored-reference agreement is not a full rehash. |
| Actual evidence content availability | UNKNOWN | UNKNOWN | No locator or external file retrieved; controlled evidence fixture policy and retrieval expectations need later approval |
| Exact wording/business interpretation | UNKNOWN | UNKNOWN | Labels, free-text instructions and template/personnel text were intentionally not exported; no official terminology/policy acceptance implied |
| Actual request eligibility, generation, download and per-page markings | BLOCKED | BLOCKED | No accounts, submissions, decisions, request, renderer or authenticated handler was executed |

No structural incompatibility requiring replacement of the six root configurations was found. The two optional supplementary evidence keys do not match the intake keys, but **this is not a failure**: `CaseIssuanceService::requirements()` permits absent optional evidence. Required intake evidence must still be supplied through the existing reference workflow.

### Controlled synthetic fixture requirements

For later separately authorized preparation, both modules can use a new fictional organization name and address in the bound required fields. R1 and corrected R2 can use different fictional addresses; the final request must use the approved R2 answer through its binding, never an applicant override. ACSA also needs three additional nonblank short-text answers. Their business labels were not read, so meaning-specific fixture wording should be confirmed through the authorized UI before submission; do not infer a date or event rule not present in the inspected type metadata.

Each application needs exactly one controlled reference in its required intake requirement, using an enabled accepted provider. Existing syntax/reference-count rules must be honored. No real applicant file or old locator may be copied. Whether an approved synthetic link is a format-only fixture or points to a controlled fictional document must be specified in Gate B; a valid-looking reference is not proof of accessible external content. Keep external retrieval/content verification UNKNOWN unless separately exercised.

Each request needs one fictional long-text supplementary answer and no new supplementary evidence collection. Values such as a fictional institution, a fictional R1/R2 address and a short demonstration note are **new proposed examples**, not retrieved applicant data or created records. Protected institution, routing and decision fields must continue to come from immutable approved records.

### Personnel and a new routing region

All **16** previously listed personnel mapping IDs were revalidated, including the same module/region/directory IDs. Only structural booleans were returned for office label, director and designation, directory membership and snapshot agreement. These checks establish completeness, **not that the names are fictional, current officials or legally authorized signatories**.

Reuse of templates 43/45 does not make the old mappings valid for a new routing region. For the proposed fresh-region rehearsal, Gate B would need to authorize:

1. Creation of the new synthetic region and effective institution/reviewer/regional/eligibility fixtures.
2. A clearly fictional directory/office revision if necessary, without changing an old revision.
3. One new personnel mapping for COR and one for ACSA, each created, reviewed and published through its existing workflow for that exact region.

Those new region-specific mappings do not require withdrawing the 16 old mappings. Never change captured application routing to borrow an old region or copy existing personnel names into synthetic fixtures. All old mappings remain untouched.

### Recommended strategy and later Gate B boundaries

**Recommend Option A — reuse the six existing published root configurations** for the planned synthetic application → correction/R2 → B1 decision → case-linked request → explicit demonstration PDF journeys. The inspected structures support that strategy for both modules. Keep runtime outcomes blocked until authorized execution; a source/metadata assessment is not a release acceptance pass.

No evidence-based reason was found to retire publications **198/199**, withdraw definitions **54/55** or withdraw templates **43/45**. **Option B is not recommended.** Staff-preview publication 128 was not followed or changed by this compatibility inspection. No old personnel mapping needs withdrawal for the proposed new-region approach.

Option C remains appropriate for scenarios not covered by reuse: positive creation/publication of replacement configurations into occupied global slots, actual external evidence retrieval, or generation if additive fictional personnel configuration is not approved. Do not claim those scenarios passed or free slots merely to simplify testing.

For a later explicit Gate B approval, the justified operations are:

- Additive synthetic identities/permissions, institutions/regions, representations, eligibility and exact reviewer/regional assignments, with namespace/configuration state rechecks before writes. No namespace reservation was made or rechecked in this fixed-scope B0 query.
- The two new region-specific personnel mappings and any necessary new fictional directory configuration, through existing review/publication handlers.
- Real login submissions and UI-driven application, correction, stage, request, generation and download operations using the existing root versions. No automatic generation or eligibility shortcut.
- Approved controlled evidence fixtures and negative authority/duplicate/integrity scenarios, targeting only new task-owned records/files.
- Separately disposable `osds_test` targeted/full regression preparation and the previously specified diagnostic-output preservation/restoration process. B0 did not connect to that database or reassess its current state.

Existing root retirements/withdrawals, browser reset, immutable-row deletion, unrelated data changes and manual activation should remain excluded. If state changes or later runtime failures contradict compatibility, stop the affected scenario and report before proposing a fix or version replacement.

### Preservation and stop

Before editing, Git state and **2,784 baseline file hashes**, including **71 PDFs**, were recorded under private `gate-b0/`. The final comparison finds exactly two changed files—the verification helper and this authorized report update—with **2,782 baseline files byte-identical**, zero missing files and all 71 PDFs unchanged. Known original Gate A helper/log hashes also match. The comparison is recorded in private `gate-b0/preservation.json`. Existing application code, Gate A report/logs/snapshots, Phase 1–3 work/evidence, pending remediation, ten published historical evidence JSON files, source artifacts and the unrelated Batch G script remain preserved.

Detailed structures are private. No database record, schema, configuration, role bundle, existing artifact or PDF was changed. No fixture, account, login, database-writing test, migration, seeder, document generation, retirement, withdrawal, commit, push or deployment occurred. The only actual database connection in this continuation was the approved read-only browser connection. Gate B remains **NOT AUTHORIZED** and Batch G remains CONDITIONAL CLOSE.

**Read-only B0 continuation complete. Stop for review; no Gate B execution begins automatically.**
