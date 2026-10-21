# Demonstration certificate rendering — isolated preview

The later [applicant issuance demonstration form](osds-applicant-issuance-demonstration-form.md) adds OSDS-published form definitions and applicant-answerable, watermarked `demo-v3` output. Earlier fixed/staff-only preview results below remain historical; application-linked issuance is still unimplemented.

## Dynamic issuance-form addition — 21 October 2026

The user confirmed **OSDS demonstration preview first**, with applicant linkage deferred. OSDS now uses **Demonstration documents → Issuance form — demonstration preview** to enter certificate type, fictional HEI/association name, address and demonstration date, and select a regional office. Director/designation fill automatically from the selected saved office. The server independently resolves them; forged director/designation fields are ignored. Outputs use new template version **demo-v2**; the original demo-v1 samples remain unchanged for comparison.

OSDS can add/edit office labels, director names and designations in the same page without code edits. Each save appends a full directory revision and one `demonstration_document.directory_updated` audit in a single-attempt PostgreSQL transaction. Fresh actor/permission checks, permission-membership locks, a narrowly scoped directory table lock and expected-revision check protect saves. Duplicate labels are rejected; up to 100 office entries are supported. Directory revisions have database append-only protection. No update/delete of earlier revisions or official personnel verification is performed. This directory is separate from canonical Region catalogue scope and confers no authority.

The preview posts values rather than placing organization/address in URLs. Names are escaped; the user must confirm fictional applicant data. The server rejects an unknown office or outdated directory revision. The generated HTML embeds template/directory revision identifiers and the resolved name/designation; a later directory change cannot alter bytes already downloaded. There is **no stored case-linked artifact, application eligibility decision, official validity, signature, email delivery or certificate-number allocation**. Preview generation itself remains read-only and does not add a generation audit. Browser Print / Save as PDF remains the export option. Template wording/layout management is still source-versioned, not a staff-editable HTML engine.

The new forward migration was applied **only** through `scripts/Activate-LocalDemonstrationDirectory.php --apply`, guarded to local `osds_dev`. It creates the append-only directory revision table; no reset, full seeder, account change, permission synchronization or personnel import occurred. The existing OSDS management permission is reused. In the actual local system, add an office first; no real director names are automatically loaded. Use fictional personnel unless OSDS confirms the supplied mapping for demonstration use. Previous claims below about no migration describe the earlier fixed-preview slice, not this addition.

**Executed verification:** focused PostgreSQL **27 tests / 258 assertions passed**, 16,863 ms, across DemonstrationDocumentsTest and DownloadableFormsTest (`osds_test`). Includes actual office add/update actions, both module merges, escaped input, ignored forged director fields, unchanged earlier revision/output bytes, stale edit/preview rejection, absent fictional confirmation, CHEDRO denial, and real audit insertion followed by failure with directory/audit rollback observed before fixture cleanup. An initial additional test selected a JSONB object's “last” key as insertion order; it was corrected to select the explicit office label, preserving the director assertion. JSON object order is not an office identity.

Actual Chrome verification on isolated port 8033 / `osds_ui_browser_test` saved two office entries through Livewire, changed selections and verified automatic resolution, then posted both COR/ACSA previews. **Eight captures** (four light/dark desktop/mobile form states and four document states) passed unrestricted default axe plus enabled target-size, with no overflow or recorded page errors. [Results](screenshots/dynamic-demonstration-documents/results.json). The dark mobile form was visually inspected. First browser attempt found an existing preview-link accessible-name mismatch, now corrected without suppressing axe. The next attempt timed out dismissing arriving notifications; existing bounded notification-close polling was reused before measurement without mutation retries. Logs remain in `storage/app/private/dynamic-demonstration-documents-browser*.txt`. Human accessibility/printing and every concurrency/response-loss schedule are not certified.

Touched PHP Pint and tracked whitespace checks passed. No full-suite, production build or independent-worker concurrency run is claimed for this addition. The task-owned browser server is stopped. No commit, push, deployment, official issuance, Batch H or Phase 4. Batch G's conditional-close status is unchanged.

**21 October 2026. Two fictional layout previews implemented.** This is a preview slice, without application decisions, case linkage, official issuance or publication of source forms. Batch G remains CONDITIONAL CLOSE pending its separate sign-offs.

## Where to review

Sign in to the existing local system as an authorized OSDS manager and open **Demonstration documents** in the staff navigation (`/admin/demonstration-documents`). Each layout opens in a new tab and offers an HTML download. Use the browser's Print / Save as PDF for paper-layout review. No PDF rendering dependency or server-side PDF download endpoint is introduced.

Access reuses existing `platform.admin.access` and `forms.downloads.manage` permissions and requires fresh active identity, in local/testing/sandbox only. This grants layout-preview access, not application approval or issuance authority. CHEDRO/applicant access is not added for this isolated manager preview. No permission seed or manual database update is required.

## Template contract

| Element | Implemented behavior |
| --- | --- |
| Layouts | Supplied Certificate of Registration and Authority to Conduct Sports Activity layout structures, adapted for demonstration. Original attached image files are not repository assets; these are HTML reconstructions. |
| Version | `demo-v1`, with separate COR/ACSA entry templates and a shared layout under `resources/views/documents/demonstration/demo-v1`. Unknown kinds/versions return 404 after authorization. Future approved wording should receive a new version; this is source versioning, not a staff-editable template management system or database-enforced historical record. |
| Merge data | Fixed fictional organization/address, sample reference/date, director/designation/office. Query parameters cannot inject live names or change fixture values. No real personnel table is consumed. |
| Marking | Repeated embedded **DRAFT / DEMONSTRATION ONLY** watermark plus visible footer. Both remain in downloaded HTML and print layout. No signature image or official number. Downloaded editable HTML cannot be made tamper-proof. |
| Wording | Source title retained with an explicit Registration/Recognition qualification. Compliance assertion replaced by a sample source-reference paragraph and explicit no-compliance finding. This is not an approved transcription of the certificate wording. |
| Validity | Source image's fixed 15 June 2027 expiry is not adopted as policy. Preview says no legal validity; final validity treatment remains unapproved. |
| Persistence | Read-only rendering; no application, submission, decision, document registry, audit event or stored-case artifact is created. No automatic generation/replay or generated-case-document recovery contract is claimed. |
| Response | Private/no-store HTML, attachment option, nosniff and restrictive content security policy. No external fonts, signatures, scripts or evidence links. |

The source register and [issuance reference data](osds-chedro-issuance-reference-data.md) remain references, not approved signatory or transition rules. Before case-linked generation, confirm template wording/version approval, validity treatment, exact revision linkage, stage/authority mapping and generation/audit/recovery requirements in the [first-slice contract](osds-first-slice-implementation-contract-draft.md).

## Executed verification

- Focused PostgreSQL: **23 tests / 213 assertions passed**, 12,867 ms, covering DemonstrationDocumentsTest and retained DownloadableFormsTest. Uses `osds_test` only. Both templates, fixed-data behavior, HTML downloads, version/kind rejection, current permission removal, inactive identity, CHEDRO/guest denial and production restriction are covered. No full-suite rerun is claimed.
- An initial new test incorrectly changed `is_active` instead of User's established `status`; it failed as expected for that ineffective fixture change. The fixture now uses `status=inactive`; runtime status semantics were not changed.
- Chrome layout checks: two templates at 1440px and 375px, four captures, unrestricted default axe plus enabled target-size, zero final violations/overflow. Browser-generated PDFs each contain one page. These checks render fixed HTML, not authenticated end-to-end UI/browser workflow. [Results](screenshots/demonstration-documents/results.json), [COR sample](screenshots/demonstration-documents/cor-demo-v1-DEMONSTRATION.pdf), [ACSA sample](screenshots/demonstration-documents/acsa-demo-v1-DEMONSTRATION.pdf).
- First browser harness attempt required an explicit browser context for axe. Subsequent checks found watermark contrast and mobile rotated-content overflow; watermark contrast and sheet clipping were corrected, without rule suppression. COR desktop screenshot was visually inspected. Native printing, assistive-technology and human wording/layout acceptance remain pending.
- Touched PHP Pint and diff whitespace checks passed; existing CRLF advisories remain. No shared asset change requires a new Vite build, and no new build/full-suite pass is claimed.

No migrations, default/manual database mutation, real signatory mapping, application workflow change, official issuance, deployment, commit/push, Batch H or Phase 4. **Pause for layout/wording review.**
