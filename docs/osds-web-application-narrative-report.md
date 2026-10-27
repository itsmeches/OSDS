# NARRATIVE REPORT ON THE DEVELOPMENT OF THE OFFICE OF STUDENT DEVELOPMENT AND SERVICES (OSDS) WEB-BASED APPLICATION SYSTEM

**Organization:** Commission on Higher Education Regional Office IV-A (CALABARZON)  
**Prepared by:** Project Technical Staff II – Programmer  
**Reporting period:** 13–27 October 2026, covering documented development and verification activities; confirmation of the official administrative reporting period is requested.  
**Status as of:** 27 October 2026

## I. Executive Summary

The Office of Student Development and Services (OSDS) Web-Based Application System is being developed to support centralized digital management of application information, requirements, review activities and related records. The work currently concentrates on the COR and ACSA application journeys. COR is titled **Certificate of Recognition** in the current demonstration document, while existing intake interfaces retain **Certificate of Registration** terminology. ACSA refers to **Authority to Conduct Sports Activity**. The terminology difference remains subject to OSDS confirmation.

During the documented period, development progressed from the application foundation and access controls to configurable questionnaires, applicant submissions, correction and resubmission, three-stage decisions, case-linked document requests and controlled demonstration PDF generation. Independent COR and ACSA journeys were subsequently completed through the actual user interfaces using fictional records. Accessibility defects identified during that rehearsal were investigated and corrected within an approved, limited scope.

The latest verification report, dated 27 October 2026, records 431 automated PostgreSQL tests with 4,627 assertions passed, 49 focused tests with 553 assertions passed, and 32 representative browser captures with zero automated axe accessibility violations. Incomplete accessibility checks remain unresolved, and human accessibility and supervisor/business acceptance have not been established.

The application is therefore a technically verified synthetic demonstration suitable for management review. It is not an officially deployed operational service and is not authorized to issue legally valid CHED certificates. Management confirmation of business content, responsibilities, terminology and operating arrangements remains necessary before operational use.

## II. Background and Rationale

Project discovery records describe an existing arrangement involving Google Forms for collecting application information, Google Sheets for maintaining related information, Google Apps Script for certificate preparation, and separately maintained forms and output templates. Supplied form exports show application branches and a separate CHEDRO issuance form. Recorded discussions also identify questions concerning requirements, applicant representation, regional and national handling, evidence links, physical documents, reference information and certificate preparation. These observations were based on supplied artifacts and recorded discussions rather than a complete audit of the live Google-based implementation.

The proposed web application responds to the need for a common structure linking these activities. A centralized case record can connect the applicant's submitted information with the requirements used, the corrections requested, the officials who acted, and the document subsequently prepared. Maintaining these relationships within one application also provides a basis for consistent access control and review history.

The rationale is not based on a quantified finding that the previous arrangement was ineffective. No measured reduction in processing time, operating cost or staff effort has been established. The documented objective is to provide more structured and traceable processing for review by the responsible office.

The requirements analysis also distinguishes application processing from other OSDS activities. RA 11180 sports reporting, official recognition lookup, historical data migration and nationwide operational reporting remain separate matters requiring prioritization and approval. Their identification during discovery does not establish that they have been implemented in the current certificate demonstration.

## III. Project Objectives

The overall objective is to develop a web-based platform that can organize relevant OSDS application workflows and records while providing controlled access to authorized personnel and organizational representatives.

Specific objectives include centralizing drafts and submissions, supporting configurable questionnaires and requirements, making review status and correction instructions visible, and preserving the information used at each stage. Technical evaluation, recommendation and final decision responsibilities are distinguished, with actions attributable to the account that performed them.

The demonstration objectives extend to case-linked requests, reviewed template and personnel references, and clearly marked sample PDFs. Technical evidence supports management assessment and identification of further requirements. These objectives follow the documented baseline; they do not replace approved operational requirements or authorize official issuance.

## IV. System Overview and Architecture

The modules share a web application and relational database. Its main components have distinct responsibilities. Laravel provides the server-side application framework, including authentication, validation, routing and coordinated processing of records. Filament supplies the administrative interface through which authorized personnel manage configurations and perform staff functions. Livewire supports interactive forms and processing screens, while Blade generates the pages presented to users.

PostgreSQL stores structured records and their relationships. Database constraints supplement application checks so that incompatible states, altered historical records and certain duplicate actions can be rejected. A transaction groups related database changes so that they either succeed together or are rolled back. This is particularly relevant when a processing action must also create history and audit entries.

Tailwind CSS provides shared styling and responsive layouts, and Vite prepares the frontend assets. Controlled browser-based rendering and supporting PDF libraries produce demonstration documents and inspect their contents. Private file storage holds downloadable blank forms and generated demonstration PDFs. Applicant evidence references are handled separately; storing a reference does not mean the application has retained the external document itself.

```text
Authorized staff and applicant web browsers
                    |
       Laravel application and shared services
          Filament / Livewire / Blade
             |                 |
   PostgreSQL records     Private file storage
   and audit history      and controlled PDF rendering
```

This arrangement allows modules to reuse account, scope, configuration and history controls. Application services maintain processing rules beyond screen controls. The architecture supports further development; nationwide production capacity has not been certified.

## V. System Users and Responsibilities

Platform governance functions support account administration, institutional and regional reference records, representation and access assignments. The implementation includes administrative account categories and explicit permissions. Some governance operations retain limitations identified in the Batch G assessment; the existence of a service function does not mean every related operation is fully available or independently verified through an administrative screen.

Authorized OSDS configuration personnel can maintain questionnaires, documentary requirements, blank downloadable forms, issuance definitions and demonstration document references within their granted permissions. Configuration review and publication are separately controlled activities. Approving a questionnaire configuration does not approve an applicant's case.

Applicants are authorized organizational representatives associated with institutions or organizations. Their access depends on account status, verified and effective representation, ownership and applicable module eligibility. Current synthetic applicant functionality should not be described as an unrestricted public student-registration service.

Technical evaluators screen submitted information, request corrections and record assessments against an exact submission. Recommending officials can recommend approval or return a case for reevaluation. Authorized Regional Director accounts can record the final synthetic approval or rejection after the required preceding stage. Three distinct staff account identities are required across these stage roles for a case, including previous revisions.

Role titles alone do not grant access. The application also checks explicit permissions, regional or institutional scope, module assignments and the current effectiveness of authority. The displayed personnel name in a demonstration certificate is a separately reviewed reference and does not itself establish signing authority. Account custody and the designation of actual responsible personnel remain organizational responsibilities.

## VI. System Development and Implemented Features

### A. System Foundation

The foundation established the application structure, database relationships, authentication and administrative interfaces. Shared controls were developed for account governance, permissions and regional or institutional access. Reference records provide a common basis for institutions, regions and service modules, supporting consistent identification across subsequent processing activities.

Audit and history functions record significant actions and retain their attribution. Historical records are protected through application logic and database safeguards. These controls provide a basis for reviewing who performed a processing action and the record involved; they do not constitute a blanket certification of every security or audit requirement.

Administrative dashboards provide task entry points and configuration-oriented summaries. They are useful for navigating available work but are not a completed national operational reporting system. A dynamic blank-form catalogue also allows authorized OSDS personnel to upload, replace or withdraw forms without changing application code. Applicants and appropriately authorized CHEDRO personnel can view and download available forms, while management permission remains separate. Versions and file checksums support traceability of replacements.

### B. Application Intake and Processing

The questionnaire builder allows authorized personnel to configure supported question types, requirements and conditions. Configurations can be saved, reviewed and published as exact versions. Separate COR and ACSA configurations can therefore be maintained without requiring a separate application engine for each. The demonstrated content remains synthetic; final official checklist wording and applicability still require OSDS confirmation.

Applicants can create drafts, save answers, supply supported evidence references and submit a copy for review. The current applicant-evidence mechanism validates and protects individual Google file references. It does not provide retained private copies of external evidence or resolve physical-original and custody requirements. This distinction is separate from the private storage used for blank forms and generated PDFs.

Screening and correction functions allow authorized staff to identify required changes and give applicant-facing instructions. The applicant works on a new revision and resubmits it, while earlier submitted answers and evidence references remain preserved. The resulting history shows what was submitted, what was requested and what changed.

### C. Three-Stage Application Decision Workflow — B1

The B1 implementation, documented on 22 October 2026, added technical evaluation, Chief recommendation or return, and Regional Director approval or rejection for synthetic applications. Each stage is tied to the exact current immutable submission. A revised submission must undergo fresh evaluation and recommendation; earlier readiness cannot simply carry forward.

Case-wide separation prevents an account that acted in one stage role from later taking another stage role on the same case, including after resubmission. Applicant-facing summaries are kept separate from internal staff notes. Consequential changes, history and required audit entries are coordinated within transactions, while database checks protect final-decision linkage and the existing terminal-case boundary. Reopening, renewal and reapplication were not introduced through B1.

### D. Dynamic Certificate Issuance Requests — B2

The B2 implementation, also documented on 22 October 2026, introduced versioned issuance-request definitions and supplementary questions. Definitions are reviewed and published independently of the original intake publication. Organization and address fields are bound to approved answers in the application's exact questionnaire version, preserving their connection to the case.

Eligible applicants can save a draft request and submit it against their approved case. Submission checks current authority, ownership or representation, the relevant module, the exact final approval and the applicable published definition. Submitted request information becomes immutable, and duplicate-original controls are applied.

Under the approved synthetic contract, eligibility relates to the corresponding module's final approval. The system does not enforce a fixed nine-day waiting period or require both modules to be approved. Official timing, cross-module prerequisites and requirement applicability remain matters for OSDS confirmation. Submission of a request alone does not automatically generate a document.

### E. Demonstration Certificate Generation — B3

The B3 work, documented on 26 October 2026, connected submitted B2 requests to authorized, explicit staff PDF generation. It supports the Certificate of Recognition demonstration and the Authority to Conduct Sports Activity demonstration independently. Reviewed template versions and regional personnel mappings determine the permitted reference content, while approved case information supplies protected recipient details.

The generated original retains snapshots of its source request, template and personnel references. Later reference changes do not rewrite it. The system verifies the privately stored file, page count, checksum and required markings before recording completion. A checksum is a digital fingerprint used to detect changed bytes. Download checks confirm that the stored original matches that fingerprint and that the requester remains authorized.

Every page is marked **DRAFT — DEMONSTRATION ONLY** and carries a statement that it is not valid as an official CHED certificate or authorization. The outputs provide no official serial number, signature, seal or legal validity. Missing or corrupt originals cannot be silently regenerated; recovery is controlled and limited to authorized restoration of identical bytes. File processing has explicit attempt states because filesystem operations are not part of a database transaction.

### F. End-to-End System Verification — B4

The B4 technical rehearsal, reported on 26 October 2026, completed independent COR and ACSA journeys through actual user interfaces. Each journey included questionnaire creation, review and publication; applicant submission; correction and resubmission; the three-stage decision; issuance-definition publication; applicant request submission; document configuration; explicit generation; and applicant download of the exact stored PDF.

Negative scenarios covered regional and module access, same-person stage combinations, stale submissions, revoked authority, duplicate generation, withdrawn reference versions and an uncertain browser response. An auxiliary UI case verified that a previously mounted action could not act on a superseded revision. The rehearsal established named technical outcomes using fictional data; it did not establish supervisor acceptance or a universal guarantee against every concurrent processing scenario.

### G. Accessibility and Interface Improvements

The initial B4 review recorded five failed accessibility captures. Approved remediation, reported on 27 October 2026, corrected nested main-page landmarks and move-button accessible names. A separate real-action investigation identified loss of notification text contrast during opacity transitions. A narrow application-owned CSS change keeps success notifications opaque during entrance and exit while preserving their existing duration, movement and dismissal behavior.

All 32 final representative captures recorded zero automated axe violations, and additional notification diagnostics showed no low-contrast frames after the correction. However, incomplete automated checks involving non-text symbols, dropdown references and the applicant theme selector remain unresolved. Keyboard, zoom, screen-reader and other required human reviews have not been accepted. The changes are therefore bounded improvements with supporting evidence, not complete accessibility certification.

## VII. Development Methodology and Activities

The records show incremental development involving requirements analysis, artifact inventory, architecture and data design, implementation, automated testing, browser rehearsal, defect investigation and documentation. Policy and process materials were reconciled with proposed behavior, with unresolved decisions retained. No formal sprint calendar or undocumented management approval is asserted.

The earliest documented foundation completion is dated 13 October 2026. Account-governance refinement and questionnaire configuration are recorded on 14 October. Applicant drafting and evidence work followed, and the 19 October application-lifecycle report documented submission, screening, correction and resubmission. The 21 October Batch G assessment recommended conditional closure of its defined verification work. B1 and B2 reports followed on 22 October, B3 and B4 reports on 26 October, and accessibility remediation on 27 October.

Verification combined PostgreSQL tests, browser actions, controlled failure injection, independent-worker concurrency exercises and PDF inspection. Specified situations included competing decisions, duplicate generation and authority withdrawal. Evidence was retained separately from earlier results, with conclusions limited to the tested situations.

## VIII. Testing, Verification and Results

The following summary uses the latest corresponding reports. No new tests or database operations were performed to prepare this narrative report.

| Verification activity | Recorded result and reporting date |
| --- | --- |
| Complete PostgreSQL regression after accessibility changes | **431 tests and 4,627 assertions passed**, reported 27 October 2026 |
| Focused questionnaire, configuration and evaluator-related tests after remediation | **49 tests and 553 assertions passed**, reported 27 October 2026 |
| COR and ACSA complete UI journeys | Both passed in the 26 October B4 rehearsal and were repeated successfully during the 27 October remediation verification |
| Final representative accessibility matrix | **32 of 32 captures with zero automated axe violations**, no measured horizontal overflow or uncaught page errors, reported 27 October 2026 |
| Additional notification lifecycle diagnostics | Eight module/theme/viewport combinations recorded zero low-contrast frames after correction, reported 27 October 2026 |
| Existing PDF and historical evidence preservation | **45 pre-existing private PDF files** remained checksum-identical; **ten historical evidence JSON files** were restored byte-for-byte after test writers ran, reported 27 October 2026 |

Assertions are individual checks within automated scenarios; these figures do not represent real applications processed or persons served. Recorded formatting, syntax and frontend build checks also passed during remediation.

The complete accessibility data retained incomplete checks in all 32 samples. They were not suppressed or treated as resolved by the zero-violation result. Further review is required to determine their disposition. Likewise, successful automated tests do not prove production capacity, legal compliance, operational security acceptance or management approval. The results establish a technical basis for demonstration and further review within the documented synthetic environment.

## IX. Challenges Encountered and Solutions Implemented

A central challenge was distinguishing activities that can appear similar on a form but have different administrative meaning. Questionnaire approval, technical evaluation, recommendation, final decision, request submission and document preparation were separated in the design. Explicit permissions and stage prerequisites help keep those responsibilities identifiable.

Another challenge was allowing corrections without losing the basis of earlier review. Working revisions and immutable submitted copies address this by preserving the previous record while allowing a new submission. Exact-revision linkage also prevents prior evaluation or recommendation from being applied automatically to changed information.

Concurrent and duplicate actions required transactions, record locking, version checks and database constraints, supported by conflicting-action and authority-loss tests. Coordinating database records with separately stored PDFs required explicit attempt states, actual-file inspection, checksums and controlled recovery.

Accessibility investigation distinguished stable colors from text rendered during transitions. Real UI behavior was measured before applying a limited correction. Failed attempts and qualified conclusions were retained alongside successful results.

## X. Current Project Status

The implemented and technically verified scope includes configurable intake, controlled publication, synthetic applicant submissions, screening and revision history, B1 decisions, B2 case-linked requests and B3 demonstration PDFs. B4 established complete UI journeys for both modules, and approved accessibility remediation addressed the five originally failed automated captures. These accomplishments are present in the reviewed repository and working copy.

Pending matters include the disposition of incomplete accessibility checks, human keyboard/zoom/screen-reader review, supervisor and OSDS business acceptance, and confirmation of official terminology, questionnaires, requirements, certificate wording and processing policies. Regional and national jurisdiction, signatory authority, validity and release requirements also require authoritative business decisions. Existing intake Registration terminology and demonstration Recognition terminology must remain distinguished until resolved.

B1–B3 verification in isolated environments did not activate the features in the existing manual-development environment. Activation and individual permissions require separate authorization. Official registry/numbering, signatures, seals, legal issuance, notification delivery, production integrations, historical migration and nationwide capacity acceptance remain outside the verified demonstration.

Batch G remains **CONDITIONAL CLOSE**, pending its separate sign-offs and disposition of documented deferred operations and governance/accessibility matters. Later module progress does not automatically close those earlier acceptance conditions. The requirements baseline is not a signed operational requirements approval, and technical verification must remain distinct from formal authorization for use.

## XI. Recommendations and Next Steps

A structured supervisor and OSDS demonstration should examine both journeys, correction instructions, separate staff responsibilities, protected issuance details and marked PDFs. Feedback should identify the decision, responsible person, required revision and approval status.

OSDS should validate the separate questionnaires and checklists, resolve Registration/Recognition wording, and confirm applicant categories, routing, recommendation and decision responsibilities. The infographic's summarized timeline and the more detailed source process should be reconciled before any official waiting period, service target or deadline is implemented. Demonstration behavior should not be adopted as policy merely because it can be executed in the application.

Incomplete checks and human accessibility reviews require documented disposition. A supervisor demonstration environment should be separately authorized, identifying required setup, individual permissions and responsible personnel while preserving existing records.

A later, separately approved readiness plan should address operational identity verification, evidence custody, retention, recovery, hosting, security assessment, integrations, capacity and official issuance controls. Requirements for reports, exports, national recognition lookup and RA 11180 should be prioritized explicitly. No production deployment is assumed or recommended as already approved by this report.

## XII. Conclusion

The documented work produced a functioning synthetic demonstration connecting configurable intake, traceable review, distinct decisions, protected issuance requests and controlled PDF preparation. Its reusable foundation and completed UI journeys provide concrete material for OSDS, MIS and supervisor review.

Operational consideration requires business validation, outstanding accessibility review and separately authorized environment preparation. The accomplishments do not establish authority to issue legally valid certificates. Further work should maintain the distinction between tested capability, demonstration scope and formal organizational acceptance.

## Appendix A. Source References and Basis of Preparation

The report was prepared through read-only inspection of source code, database migration definitions, dependency manifests, interfaces and existing evidence. No running database was inspected. Source review covered account and scope controls, questionnaire and publication services, application lifecycle and decision services, issuance requests, downloadable forms, document generation and private retrieval. Current implementation and later dated evidence were used to reconcile historical scope descriptions in earlier documents.

| Reference group | Project records consulted |
| --- | --- |
| Requirements, architecture and source artifacts | [Requirements baseline](requirements.md); [requirements and architecture report](../OSDS_REQUIREMENTS_ARCHITECTURE_REPORT.md); [technical specification](../OSDS_TECHNICAL_SPECIFICATION.md); [form artifact inventory](current-form-artifact-inventory.md); [CMO/artifact alignment record](osds-cmo-artifacts-and-project-alignment.md) |
| Foundation and intake history | [Phase 1 completion](phase-1-completion-report.md), 13 October; [Phase 2 refinement](phase-2-completion-report.md), 14 October; [Phase 3A completion](phase-3a-completion-report.md); [Phase 3D completion](phase-3d-completion-report.md), 19 October 2026 |
| Integrity assessment and dynamic downloads | [Batch G final assessment](phase-3d7-batch-g-final-closure-assessment.md), 21 October; [dynamic downloadable forms implementation and updates](osds-dynamic-downloadable-forms.md) |
| B1 and B2 | [Three-stage decision foundation](osds-b1-three-stage-decision-foundation.md); [case-linked issuance requests](osds-b2-case-linked-issuance-requests.md), both reported 22 October 2026 |
| B3 and B4 | [Demonstration certificate generation](osds-b3-demonstration-certificate-generation.md); [end-to-end acceptance report](osds-b4-end-to-end-acceptance-report.md), both reported 26 October 2026 |
| Latest remediation and verification | [Accessibility remediation report](osds-b4-accessibility-remediation-report.md) and [verification summary](screenshots/b4-accessibility-remediation/results.json), reported 27 October 2026 |

The README was consulted for project structure and stack context. Its early Phase 3A scope introduction predates later implementations and was not treated as the current feature inventory. Supplied policy/process findings were considered through the source-alignment record; this narrative does not independently establish legal effectivity or approve disputed policy interpretations.
