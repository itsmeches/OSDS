# B3 — Case-linked demonstration certificate generation

**26 October 2026. B3 implementation and development verification: PASS. Stop for review.** Synthetic demonstration only. No official issuance, registry, serial number, signature, seal, legal validity, notice or deployment. Batch G remains **CONDITIONAL CLOSE**, with separate management sign-offs pending. B4 is not started.

## Approved contract and architecture

An applicant's immutable **submitted B2 request** is the input, not a new free-text issuance form. Authorized staff explicitly generate an original through **Case demonstration PDFs**, `/admin/case-demonstration-documents`. Submission alone generates nothing. Both COR and ACSA are supported independently.

The new COR title is **Certificate of Recognition**. Existing Registration/legacy unlinked previews remain preserved. The Letter-size layout adapts the supplied examples with sample reference wording, a fixed no-compliance statement, no legal validity and no signature. It does not reproduce the supplied sample's fixed June 2027 validity or official serial/signature fields.

`DemonstrationDocumentService` reuses B1's exact current Director approval and B2's protected identity, answer, definition and source references. `PrivateDemonstrationPdf` invokes controlled Node/Playwright/Chrome, then PDF-lib adds non-editable markings. PDF.js parses the actual completed PDF before storage completion. Dependencies are recorded in the npm manifest/lockfile; no Composer PDF engine or competing questionnaire editor was introduced.

### Template and personnel versions

`demonstration_document_versions` contains typed **template** and **personnel** versions. Both use **draft → reviewed → published → withdrawn**, with immutable content and append-only history. One template per module and one personnel mapping per module/region may be published. New content requires a new version; withdrawal does not alter saved PDFs.

Templates contain the fixed `b3-letter-v1` layout identifier, bounded plain-text reference wording and explicitly selected supplementary question keys. HTML, CSS, scripts, SQL, remote assets, editable watermarks and arbitrary merge expressions are not supported. A selected key must exist in the exact submitted B2 definition. Supplementary values are labelled submitted request details, not a new substantive approval.

Personnel mappings bind an explicit **routing region ID**, immutable existing office-directory revision and office UUID, with a review/provenance note. The complete office label, name and designation are snapshotted and checked against the source directory. No label matching, current-director constant or substitution from the B1 actor's display name occurs. The directory remains managed through its existing feature/permissions; B3 adds mapping review/publication. Fictional personnel are used for verification. Real personnel verification/effective authority is not inferred from a saved directory entry.

Fixed merge data comprises approved B2 organization/address, certificate type, captured regional routing, reviewed office/personnel metadata, server demonstration generation date and case/submission/decision references. The B1 approving account and the displayed directory reference name remain separate concepts. The latter grants no signatory authority.

Every PDF page contains **DRAFT — DEMONSTRATION ONLY** and **Synthetic test document. Not valid as an official CHED certificate or authorization.** They are stamped after HTML rendering, outside editable content, including a fixed opaque footer layer. Actual per-page PDF text is validated. Protected recipient/personnel/title text is also checked; repeated page markings are removed only from that separate identity comparison so names spanning pages can be verified.

## Permissions, interfaces and private access

New explicit permissions, without automatic role-bundle expansion:

| Area | Permissions |
| --- | --- |
| Templates | `documents.templates.manage`, `.review`, `.publish` |
| Personnel mappings | `documents.personnel.manage`, `.review`, `.publish` |
| Case documents | `documents.view`, `.generate`, `.download`, `.recover` |

Generation/recovery/download require current authenticated active synthetic staff, staff-panel admission, explicit permissions, effective canonical regional or nationwide scope **and** the existing effective exact region/module application-reviewer assignment. Role names alone grant nothing. Staff queues apply region/module constraints in SQL and paginate cases; configuration history is separately paginated. Configuration management is distinct from case access.

Staff can inspect submitted request identity/linkage, select exact published versions, preview a template with fictional data, generate explicitly, inspect history/status and download the stored original. Staff without configuration permissions see the case queue rather than configuration administration.

The applicant's existing issuance-request view shows not-generated/processing/available/failed status. Downloads recheck the unchanged B2 case-read ownership, account status, representation and eligibility policy. Institution colleagues do not automatically acquire ownership. No internal notes, storage paths or raw renderer/database faults are shown to applicants.

Downloads use an authenticated UUID controller, safe attachment filenames, `application/pdf`, private/no-store and nosniff headers. They read and hash the actual bytes, then reauthorize and audit before returning those same bytes. A download authorization event is not proof of complete delivery. No public storage URL or historical reissue endpoint is added.

## Migration, integrity and transaction boundaries

New migration: `2026_10_26_100000_create_demonstration_documents.php`.

| Table | Purpose and guards |
| --- | --- |
| `demonstration_document_versions` | Typed version content; controlled lifecycle, source-directory snapshot, module and published-version uniqueness. |
| `demonstration_documents` | One original slot per submitted request/certificate type; exact B1/B2 linkage, immutable source snapshot, selected version/render snapshots and immutable completed metadata. |
| `demonstration_document_attempts` | Append-only attempt UUID, actor, selected versions and render snapshot; insert/deferred guards require coherence with the original slot. |
| `demonstration_document_history` | Append-only attributed configuration/generation/download/recovery/integrity events. |

PostgreSQL guards reject draft/unapproved/incoherent requests, incorrect relationship or protected JSON bindings, invalid state transitions, forged snapshots and mutation/deletion of completed originals. A completed artifact requires checksum/size/page count/date and its controlled private identifier. Downgrade refuses to discard recorded B3 history. PostgreSQL cannot inspect filesystem bytes; the application verifies those before completing metadata.

Generation uses three bounded phases:

1. **Reserve:** operation-owned single-attempt transaction; fresh account/permission/scope checks; exact source/version validation; reserve processing attempt; atomically write attempt/history/start audit.
2. **Render:** release transaction/row locks; render privately with fixed local assets, no page JavaScript, blocked network/service workers and a 60-second subprocess limit. Validate PDF, promote exclusively to an attempt-specific private filename and inspect the stored file. Limits are 20 pages / 20 MiB. Validation subprocesses also have finite 60-second limits.
3. **Finalize:** new transaction; freshly reauthorize generating staff, exact approved case/request and selected still-published versions. Check active attempt and actual stored checksum/size again, then atomically commit completed metadata/history/audit. No newer version is silently substituted.

The state lifecycle is **processing → completed** or **processing → failed**; an explicit retry may advance failed → processing with a new immutable attempt. Source bindings remain fixed. A completed original never returns to processing. Competing staff see the reservation or intact completed original; PostgreSQL uniqueness prevents a second original.

Audit names use `demonstration_pdf.` with `version_created`, `version_reviewed`, `version_published`, `version_withdrawn`, `generation_started`, `generation_completed`, `generation_failed`, `download_authorized`, `integrity_incident`, `interrupted_attempt_recovered` and `identical_backup_restored`. Events and corresponding history commit in the same operation transaction. Metadata contains linkage IDs, not raw answers, PDF text, personnel notes, locators or private paths.

## Failure inspection and recovery

- Renderer timeout/crash, invalid output, missing resources, storage failure or finalization failure never produce a new completed record. Where available, a separate transaction records failed state/history/audit.
- On an uncertain commit, inspect persisted state before cleanup. Bytes belonging to a confirmed completed original are retained. Uncertain private bytes remain for explicit inspection; no automatic generation replay occurs.
- An interrupted reservation remains processing if failure recording itself cannot commit. Authorized staff may recover an attempt older than 120 seconds into failed state. A still-running worker cannot complete after its reservation is recovered or superseded. Inspection and fresh authorization remain necessary before an explicit retry.
- Temporary directories and confidently uncommitted attempt files are cleaned up narrowly. A terminated process can leave private orphan bytes; no broad automatic deletion job is introduced.
- Missing/checksum-mismatched completed files deny download and atomically record an integrity incident. The completed original metadata stays immutable; the UI's completed status is not proof that externally changed storage remains healthy.
- A recovery-authorized operator may upload an authorized **checksum-identical** backup to restore a missing original. Its PDF markings/page count/checksum/size are inspected before restoration and the copied bytes are checked. Existing files are never overwritten. Corrupt bytes require controlled forensic quarantine before missing-file restoration; B3 supplies no automatic quarantine/deletion feature.
- If restoration/audit outcome is uncertain, inspect bytes and history before another action. Do not regenerate a completed original, alter its checksum or claim a replacement is the original.

## Verification

**Final focused B1+B2+B3 PostgreSQL: 46 tests / 450 assertions passed**, 282,818 ms. Private output: `storage/app/private/b3-focused-serialized-final.txt`. This includes all retained B1/B2 cases and 20 new B3 cases. The matrix exercises actual Livewire generation/configuration, real COR/ACSA PDFs, protected snapshots, missing/withdrawn/wrong-module versions, wrong-region mappings, inactive/expired/revoked authority, draft requests, download denials, duplicate originals, immutable source/history, unknown supplementary keys, interrupted reservations and identical-backup restoration.

Before-completion audit faults include before insert, real insert then exception and a genuine PostgreSQL actor foreign-key rejection. Exact saved state/audit/file assertions establish failed completion rollback. Invalid PDF, timeout exception, renderer crash and blocked private-storage-directory faults leave no completion metadata. Timeout behavior is injected through the actual process-timeout exception contract; the finite process limits are configured in the renderer, not established by a new 60-second wall-clock hang test. A separate actual-PDF stress test renders long fictional organization/address text across at least three pages and checks all page markings and complete protected text.

[Final browser results](screenshots/b3-demonstration-pdfs/browser-results.json): **two module journeys / eight captures passed** on guarded port 8039 and `osds_ui_browser_test`. Each independently creates/reviews/publishes a new template through the real UI, injects a real post-audit completion failure, inspects failed state, explicitly retries generation and downloads identical stored bytes as staff and applicant. Sources and personnel prerequisites are prepared through real B1/B2/directory services; these are scoped B3 journeys, not new complete authoring/intake browser journeys.

All eight light desktop (1440px) / dark mobile (375px) staff/applicant samples have zero unrestricted default axe plus enabled target-size violations, overflow, uncaught page errors or checked response leaks. Notifications and finite animations settle before measurement; external browser requests are blocked. [Separate actual-session access checks](screenshots/b3-demonstration-pdfs/access-results.json) pass staff download-permission loss, applicant representation loss, unrelated staff and forged UUID denials without creating another generation attempt.

[Actual PDF inspection](screenshots/b3-demonstration-pdfs/pdf-inspection.json) parses both saved two-page module PDFs, confirms their distinct titles and every-page watermark/disclaimer, and records checksum/size and 612 × 792 point Letter dimensions. Each page is rasterized for retained inspection. Representative certificate, appendix and final mobile staff images were visually reviewed. [COR sample](screenshots/b3-demonstration-pdfs/registration-DEMONSTRATION.pdf), [ACSA sample](screenshots/b3-demonstration-pdfs/acsa-DEMONSTRATION.pdf).

Full Pint `--test`, new PHP/JavaScript syntax, Vite asset build and tracked whitespace gates pass. Existing bundler timing advice remains. Private logs include `b3-pint-final.txt`, `b3-build-final.txt` and browser/concurrency/PDF-inspection outputs. IDE diagnostic tooling is unavailable; no IDE-clean claim. Task-owned browser servers are stopped; unrelated servers are preserved.

**Complete current-tree PostgreSQL: 431 tests / 4,627 assertions passed**, 959,302 ms, on the working tree based on Git HEAD **3448f26**. Private output: `storage/app/private/b3-full-final.txt`. `Run-B3Regression.php` asserts the actual `osds_test` connection before launching PHPUnit with its own explicit 512 MiB CLI limit. This executed run retains the questionnaire, applicant, screening/correction, B1/B2, downloadable-form and earlier integrity/security regressions. Existing 411/4,479 and older checkpoints remain historical; none substitutes for this result. No later application/test behavior change was made.

Existing diagnostic writers ran unchanged. Their regenerated outputs are [archived separately with restoration hashes](screenshots/b3-demonstration-pdfs/preservation/unit-evidence/final/results.json). **All ten original published evidence JSON files were restored byte-for-byte**, with matching pre-run SHA-256 hashes and no remaining tracked diff in `docs/evidence`. No historical outcome was rewritten. No new broad preservation browser suite was executed beyond the scoped B3 journeys/access checks and full PostgreSQL regressions.

Executed intermediate checkpoints: **12 / 99** and expanded **19 / 143** passed. Six independent-worker schedules passed: duplicate generation, permission loss, template withdrawal, personnel withdrawal, exact scope revocation and actor inactivation. Workers observe transaction depth zero during rendering, an inspectable processing reservation without completion metadata, and at most one completed original/audit. These establish named schedules, not universal SQL/cache/deadlock coverage.

Earlier diagnostic logs remain private. Findings corrected included the migration's module table name, PDF.js loading-task cleanup, JSON key-order comparison, complete assignment-revocation fixture metadata and unique disposable directory labels. Browser harness fixes synchronized creation before reading a new template ID and targeted the actual feedback heading instead of a hidden “results” element. One overlapping focused run and schema rebuild caused a disposable-test missing-table error; the newer run was stopped and verification serialized. No manual database or runtime guard was changed to address those failures.

## Files and boundaries

New runtime files: `DemonstrationDocumentService`, `PrivateDemonstrationPdf`, document/version models, `CaseDemonstrationDocuments` Filament page/view, `CaseDemonstrationDocumentController`, fixed B3 layout, renderer script, configuration and the single migration above. Existing permissions/routes and applicant issuance view receive bounded additions. npm manifests record rendering/validation dependencies.

New verification: `DemonstrationDocumentTest`, `B3Scenario`, guarded preparation/browser server/worker tooling, browser/concurrency/PDF-inspection scripts and safe synthetic evidence under `docs/screenshots/b3-demonstration-pdfs/`.

Default manual configuration remains `osds_dev`. Automated preparation/migrations/tests use only **osds_test** and **osds_ui_browser_test**, with identity assertions; the B3 test checks identity before its schema preparation. No manual database activation, reset, seed, migration or synchronized authorization seeding is included. Activating the migration or provisioning manual permissions requires separate authorization.

## Limitations and B4 prerequisites

- Synthetic-only development verification is not official CHED process acceptance, legal issuance, nationwide capacity certification or full accessibility certification.
- No external integrations, notifications, official numbering, signatures/seals, renewal, reissue, amendment or revocation workflow.
- Local execution requires the configured trusted Node/Chrome installations and local fonts. Missing renderer prerequisites block generation; they do not trigger a fallback document or official-looking output.
- Human screen-reader/zoom/keyboard/touch/printing acceptance and existing Batch G human/Institution-selector residuals remain separate. Automated checks cover settled sampled states, not all timed animations.
- B4 requires separately authorized complete authoring → submission → correction/resubmission → three-stage decision → B2 request → saved PDF journeys for each module, deterministic rehearsal fixtures and supervisor acceptance. Scoped B3 journeys begin from real service-prepared B1/B2 prerequisites and do not claim those complete browser journeys.

**Stop after B3 implementation and verification for review. No B4, official issuance, manual activation, commit, push or deployment.**
