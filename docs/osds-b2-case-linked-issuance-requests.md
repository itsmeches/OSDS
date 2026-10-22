# B2 — Dynamic case-linked synthetic issuance requests

**22 October 2026. B2 implementation and targeted development verification: PASS. Stop for review.** This slice stores demonstration requests only. It generates no certificate, PDF, official number, signature, notice or release. Batch G remains CONDITIONAL CLOSE with its separate sign-offs pending. B1 remains the unchanged decision foundation.

## Dynamic definition architecture

Authorized synthetic OSDS staff use **Issuance request definitions**, `/admin/issuance-definitions`. Four new explicit permissions are defined through the existing FoundationPermission enum: `issuance.definitions.manage`, `.review`, `.publish`, and `issuance.requests.submit`. Existing role bundles are not silently expanded. Disposable fixtures grant permissions individually; manual provisioning requires separate authorization.

The page opens the existing **FormBuilder/Questionnaires** workflow to create supplementary questions and documentary requirements under COR or ACSA. Existing `QuestionnaireDraftService`, `QuestionnaireAuthoringService`, `QuestionnairePublicationService::submitForReview/review`, `ConfigurationValidator` and `ConfigurationHasher` retain exact configuration review. Existing configuration permissions still apply. Configuration approval is neither a B1 application decision nor an issuance approval.

An approved configuration pair is snapshotted into an immutable B2 definition draft with title, instructions and explicit organization/address bindings to text-question IDs in the application's exact questionnaire version. B2 locks and reloads the configuration before hash validation/snapshotting. Reserved core-field keys cannot be repurposed as supplementary answers. There is no executable configuration, SQL editor, permission override or eligibility switch.

Definition lifecycle: **draft → reviewed → published → withdrawn**. Staff explicitly review the complete B2 version, including bindings, before publication. Every step has fresh permission checks, an operation-owned transaction, append-only history and mandatory audit. One definition per module may be published; withdraw it before publishing a replacement. Version allocation is serialized on the module. To change content/bindings/title, create a new version; reviewed/published content is never edited in place. Existing builder cloning supports new questionnaire versions and new exact configuration reviews.

**Issuance publication is separate from intake publication.** B2 does not add a publication-manifest channel or replace the applicant intake questionnaire. A disabled-fields staff preview creates no applicant request. Definition/version and publication history are inspectable. The existing unlinked layout-preview feature remains available under its established contract, labelled separately and linked to the new request section; it is not a B2 eligibility path or a generated case document.

### Evidence requirement boundary

Configured requirements match **already approved immutable submission evidence by requirement key**. Applicable required/minimum/maximum counts are checked at submission. Request snapshots bind the exact existing evidence-revision IDs, never public/raw file URLs. Conditional questions reuse `ConditionalLogic`; the shared questionnaire-field component renders the existing supported field types. Required/type/choice/length checks run server-side. Empty multiple-choice arrays and unchecked optional declarations have explicit defaults; submitted choices/booleans have readable labels. No arbitrary validation code is executed.

No new supplementary-document collection policy was supplied. B2 therefore adds no upload, external-link entry or new evidence-download endpoint. A requirement absent from the approved submission blocks the request with an explanation; it cannot be satisfied by an applicant's free-text claim. New supplementary file collection, custody and access rules require separate authorization. Blank downloadable forms remain a distinct catalogue.

## Eligibility and applicant behavior

The applicant navigation adds **Issuance requests**, `/applicant/issuance-requests`. Accessible cases are grouped separately as COR and ACSA. Inspecting a case explains an unavailable approval, form, identity binding or write permission. No qualifying case produces an empty-state explanation. Another account's case and a case with revoked representation are not disclosed.

Submission requires, inside the transaction:

1. Current authenticated active synthetic identity and the unchanged sandbox guard.
2. Existing ApplicationPolicy ownership, portal/tracking authority, verified effective institution representation and existing effective institution/module sandbox eligibility; explicit request-submit permission.
3. A COR (`registration`) or ACSA case with active module and valid active captured routing region.
4. Final B1 Director approval matching this case and its exact current immutable submission, with no open/superseding working revision.
5. A published issuance definition matching that module **and exact intake questionnaire version**.
6. Resolved applicable nonblank approved organization/address answers through the configured question IDs.
7. Valid supplementary answers and required approved evidence; no conflicting original submitted request.

Only that module's approval is required. There is **no both-modules prerequisite and no nine-day waiting lock**. Neither an elapsed time nor a submitted/unapproved/rejected case grants eligibility. These are synthetic request rules, not official issuance policy or a legal interpretation of COR terminology.

The approved institution ID/name snapshot, bound organization/address answers, case/submission/hash, Director decision/actor, certificate type and region routing are read-only. Institution name comes from the immutable submission; certificate organization/address come from the selected approved answer bindings. Region ID comes from captured submission routing; its display label is captured from the region catalogue when the request/draft is created. No applicant office/director/date/serial input controls these bindings. Personnel, validity and legal-template decisions remain B3 prerequisites.

Request lifecycle: **draft → submitted**. Applicants may save/resume the current version, review protected details, submit and view immutable answers/snapshots and append-only event history. A withdrawn-version draft is preserved read-only in saved versions; a new current-version draft must be started explicitly, without automatic copying. Submitted originals remain visible under fresh case-read authorization after definition withdrawal; withdrawal does not rewrite them. Revoked submit permission prevents mutation/retry; read access still follows current case policy.

## Transactions, uniqueness and PostgreSQL safeguards

`CaseIssuanceService` owns single-attempt transactions. It locks/reloads actor and permission memberships, case, current representation/eligibility grants and definition before authorization/eligibility checks. Configuration management also locks the module/configuration rows. Normal external writes establish authority at the operation check; this is not a guarantee against every arbitrary SQL/cache/deadlock schedule.

Tables:

- `issuance_definitions`: immutable versioned definition snapshots and hashes, source configuration approval, source intake questionnaire and controlled publication state.
- `issuance_requests`: exact case, immutable submission, final approval, module, institution, routing, definition and actor; protected identity/definition/answer snapshots, request key, payload hash, lock version and timestamps.
- `issuance_history`: append-only definition/request events and actors.

Partial unique indexes enforce **one submitted original per Director decision/certificate type**, one draft per decision/definition/actor and one published definition per module. Drafts are not additional submitted originals. Actor/request-key uniqueness prevents key reuse across cases. Fresh authorization precedes retry retrieval. Same-key/same-normalized-payload retries return the existing submitted record; conflicting keys/payloads or stale draft/definition versions deny. Authorized same-key retrieval of an existing submitted snapshot remains possible after publication withdrawal; it creates nothing new.

PostgreSQL guards enforce final-approved case/submission/decision coherence, module/type, actor/institution/region bindings, matching published definition/source questionnaire and exact definition JSON. A second insert guard checks protected JSON IDs, approved organization/address values, immutable institution name, submission hash, Director actor and exact source evidence IDs against the underlying records. Submitted requests and histories cannot be altered/deleted. Draft core bindings are immutable; only answers/status/version/timestamps may advance. Downgrades refuse to remove recorded-history safeguards without separate disposition.

Each consequential operation commits its history and AuditRecorder event atomically: `issuance.definition_created`, `.definition_reviewed`, `.definition_published`, `.definition_withdrawn`, `.draft_saved`, `.request_submitted`. Audit resources are definition/request records; metadata contains linkage IDs, not applicant answers, document locators or B1 internal notes. B1 lifecycle/decision records are never modified by B2.

Unexpected failures retain entries and fixed redacted support-reference feedback. Inspect saved requests after an uncertain response before an explicit action. No consequential automatic replay, general offline recovery, official queue processing or automatic generation is added.

## Actual verification

**Final B1+B2 focused PostgreSQL: 26 tests / 302 assertions passed**, `storage/app/private/b2-b1-focused-final.txt`. This includes all ten retained B1 cases; the new B2 class has sixteen cases. Actual Filament/Livewire definition creation, preview/review/publication and applicant draft/submission are exercised. The matrix includes COR/ACSA success, unapproved/rejected/missing cases, missing/withdrawn definitions, missing identity answers, forged fields, ownership/representation/status/permission denial, wrong module/routing/institution, real correction/resubmission and rejection of its real older submission, duplicate/retry conflict, stale versions, immutable snapshots/history and explicit version replacement. Optional declaration/multiple-choice defaults, readable empty/false answers and an approved-evidence requirement are exercised; an unmet configured evidence count blocks submission.

Audit faults include before insert, actual audit insertion then exception and genuine PostgreSQL audit foreign-key rejection. Counts/raw rows, identical PDO and operation depth above the PHPUnit fixture transaction establish rollback before cleanup. Saved-draft submission failure and definition-creation failure preserve earlier state. Direct SQL tests reject unbound relationship values, immutable mutations, duplicate originals and forged protected JSON with otherwise valid foreign keys.

[Four independent PostgreSQL scenarios](screenshots/b2-case-issuance/concurrency.json) use distinct backend PIDs, real `pg_blocking_pids` waits and an unchanged third-connection view: different-key competing submissions commit one original/audit/history; representation revocation, request-permission removal and definition withdrawal deny the waiting submission. This covers named schedules, not every authority/cache/DDL/deadlock race.

Browser/current-tree regression and final gate results appear below. Earlier diagnostic test failures are retained in private `b2-focused-first.txt`, `-second.txt`, `-third.txt` and `-expanded.txt`: method-name collision with Livewire, missing returned model defaults, Blade directive adjacency, and comparing an inserted model's raw JSON/order/timestamp representation with a fresh PostgreSQL row. Fixes preserve all persistence assertions; the final immutable comparison uses fresh rows on both sides. No guard or rollback assertion was weakened.

Three earlier full-suite attempts were deliberately interrupted before completion to include the final PostgreSQL snapshot guard, simplify the applicant display identified during visual review and correct supplementary-field defaults/validation. They provide no complete-suite pass. Private `b2-full-before-snapshot-guard-attempt.txt`, `b2-full-before-applicant-display-attempt.txt` and `b2-full-before-field-defaults-attempt.txt` retain their available output; regenerated diagnostic evidence was separately archived/restored before restarting. The final focused run includes all final behavior. The full-run CLI uses 512 MB explicitly, retaining the B1-established runner setting rather than changing application memory configuration.

The first browser run confirmed a mobile overflow in the new identity panel. [Original result/captures](screenshots/b2-case-issuance/first-mobile-attempt/results.json) remain. Wrapping was scoped to the issuance workspace and assets rebuilt. The second attempt completed both module journeys/eight captures before its permission-loss probe timed out: a pending live field update refreshed the form after revocation before the intended click. [Second result](screenshots/b2-case-issuance/second-attempt-results.json) remains. The final probe synchronizes that field update before revocation and checks actual POST denial. No application handler or persistence assertion is substituted.

### Final browser and non-database gates

[Final browser results](screenshots/b2-case-issuance/browser-results.json): **three grouped checks / eight captures passed** on guarded local port 8038 and `osds_ui_browser_test`. Each module independently exercises actual OSDS definition creation/preview/review/publication, applicant read-only identity, draft save/reload, real post-audit insertion failure with rollback/retained entries, explicit successful submission and immutable reload. Mounted permission loss is verified through an actual HTTP 403 and zero requests. Source applications/configuration reviews are prepared through real services; these are not new full questionnaire-authoring/intake browser journeys.

All eight light desktop (1440px) / dark mobile (375px) staff/applicant captures have zero unrestricted default axe plus enabled target-size violations, page overflow, uncaught page errors or checked response leaks. Notifications/finite animations settle before measurement. External requests are blocked. Representative final staff and mobile applicant images were visually inspected; the final applicant view uses readable approved details and a support-reference disclosure, while technical binding/hash data stays in persistence. Capture preparation clears an axe-focused skip link and returns to the top before screenshots. This is not human accessibility or timed-animation acceptance.

Full Pint `--test`, PHP/JavaScript syntax checks, Vite asset build and tracked/new-file whitespace checks passed. Initial formatting findings in new files/imports were normalized before the final pass; existing CRLF and bundler timing advice remain. Private records include `b2-pint-final.txt`, `b2-build-final.txt`, `b2-diff.txt`, `b2-browser-final.txt` and `b2-concurrency-final.txt`. IDE diagnostic tooling was unavailable; no IDE-clean claim. Task-owned browser servers were stopped. Unrelated servers remain unchanged.

### Complete current-tree regression and preservation

**411 tests / 4,479 assertions passed** on PostgreSQL `osds_test`, duration **1,699,754 ms**. Private output: `storage/app/private/b2-full-final.txt`. This is an executed dirty working tree based on Git HEAD **b3af4cf**, including B1, B2 and the pre-existing pending downloadable-form upload fixes. The B1 395/4,332 checkpoint and older checkpoints remain historical. No later application/test behavior change was made; no committed-tree certificate or production performance claim is implied.

The complete suite includes all retained B1 cases, new B2 cases, questionnaire/applicant/screening/correction/downloadable-form behavior and earlier K1–K5/integrity/security regressions. Existing diagnostic writers ran unchanged. Their new outputs are [archived separately with restoration hashes](screenshots/b2-case-issuance/preservation/unit-evidence/final/results.json). **All ten original published evidence JSON files were restored byte-for-byte**, with matching pre-run SHA-256 hashes; no historical outcome was rewritten. Earlier interrupted-run archives remain alongside the final archive. No new broad preservation browser suite was run: the executed gates are this full PostgreSQL suite and the scoped B2 staff/applicant journeys.

## Files and boundaries

- `app/Services/Documents/CaseIssuanceService.php`.
- `app/Models/IssuanceDefinition.php`, `app/Models/IssuanceRequest.php`.
- `app/Filament/Pages/IssuanceDefinitions.php`, `resources/views/filament/pages/issuance-definitions.blade.php`.
- `app/Livewire/Applicant/IssuanceRequests.php`, `resources/views/livewire/applicant/issuance-requests.blade.php`.
- `database/migrations/2026_10_22_200000_create_case_issuance_requests.php`, `database/migrations/2026_10_22_200001_enforce_issuance_snapshot_bindings.php`.
- `app/Support/FoundationPermission.php`, `routes/web.php`, `resources/views/layouts/applicant.blade.php`, `resources/views/applicant/issuance-demo.blade.php`, `resources/css/app.css`: permissions, route/navigation, preview distinction and scoped wrapping.
- `tests/Feature/CaseIssuanceRequestTest.php`, `tests/Support/B2Scenario.php`; `tests/Support/B1Scenario.php` adds opt-in source-address fixture questions while preserving default B1 arrangements/assertions.
- `scripts/Prepare-B2.php`, `scripts/B2RequestWorker.php`, `scripts/verify-b2-concurrency.mjs`, `scripts/b2-browser-server.php`, `scripts/verify-b2-browser.mjs`.
- This report and bounded safe artifacts under `docs/screenshots/b2-case-issuance/`.

Default local configuration was verified as **osds_dev**. Only approved **osds_test** and **osds_ui_browser_test** were used for automated preparation/migrations/tests, with database-identity assertions. No manual/demo database was migrated/reset/reseeded or otherwise changed; no synchronizing authorization seeder ran there. B2 is not activated in osds_dev. Its migrations and individual permission provisioning require separate authorization before manual use.

B1 source contracts, pending downloadable-form fixes, source artifacts, historical evidence and Batch G disposition remain preserved. No commit, push, deployment, production onboarding, registry/numbering/signature, email delivery, Batch H, Phase 4 or unrelated security inventory.

## Remaining limitations and B3 prerequisites

- Only case owners with current representation and case-read authority may use B2; institution colleagues do not automatically share ownership. A withdrawn/current definition mismatch blocks new submission; older submitted originals are retained.
- Exact source-questionnaire binding requires an explicit matching issuance version when the intake questionnaire changes. This does not infer that equal question names across versions have identical approved meaning.
- New supplementary document collection and its custody/retention rules remain unapproved and unimplemented. There is no new private upload or file-distribution surface.
- No universal mutation-retry guarantee, durable offline draft, complete human accessibility/WCAG acceptance or nationwide production capacity certification. Existing separate Institution-selector/human-acceptance residuals remain open.
- B3 must separately confirm output wording/terminology, template versions, watermark, validity/date treatment, personnel snapshot authority, private artifact storage/retrieval, renderer failures and recovery. B2's immutable source/definition/identity/evidence snapshots are dependencies, not an issuance or generation authorization.

**Stop after B2 for review. B3 is not started automatically.**
