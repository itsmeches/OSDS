# Stakeholder questions and decision priorities

**Purpose:** Questions that must be answered before the next implementation phases. They are derived from preliminary notes, supplied artifacts, two partial machine transcripts, and the Phase 0–2 design. None of the suggested choices below is an approved CHED policy.

## How to use this register

| Priority | Meaning | Implementation rule |
| --- | --- | --- |
| P0 — stop gate | Answer before Phase 3 questionnaire/evidence implementation | Do not build the affected capability from assumptions. |
| P1 — workflow gate | Answer before Phase 4 COR/ACSA intake and review | Prototypes may use synthetic cases only; do not encode transitions. |
| P2 — official-record gate | Answer before Phase 5 registry, certificates, signature, and issuance | Do not assign official numbers, generate final documents, or issue anything. |
| P3 — release gate | Answer before pilot, migration, or public production launch | Do not expose the system to real applicants or import live records. |

Impact is scored from **5 (system-wide/legal/security rework)** to **1 (localized usability change)**. For every decision, record the decision maker, decision date, authoritative source, effective date, affected modules, and approval evidence.

## P0 — questions that block Phase 3

| # | Question requiring an authoritative answer | Impact | Why the answer changes the system | Proposed decision owner | Expected answer or artifact |
| --- | --- | :---: | --- | --- | --- |
| P0.1 | Which program is the next authorized implementation target: COR, ACSA, both in parallel, or RA 11180 as a separate workstream; and is the sports priority formally authorized against the originally discussed Lakbay/SAS outputs? | 5 | Determines the next schema, screens, backlog, acceptance tests, funded deliverables, and delivery schedule. | OSDS Director, program owners, and funding/engagement owner | Signed release priority, module scope, and authority for reprioritization. |
| P0.2 | Is there a signed User Requirements Analysis, and who has authority to approve changes to it? | 5 | Without an authoritative baseline, fields and rules can change without controlled acceptance. | OSDS Director and designated process owners | Signed URA, change-control owner, and approval route. |
| P0.3 | Are the supplied Registration-branch, ACSA-branch, and CHEDRO Issuance exports the current approved forms? What is each owner, version, effective date, and module applicability? | 5 | Prevents configuring a scraped prototype as policy and resolves mismatches with earlier templates. | COR/Registration, ACSA, issuance, and document owners | Approved artifact register and controlled source copies. |
| P0.4 | Should applicants answer questionnaires directly in the OSDS platform after cutover, with Google Forms retained only for transition/emergency use? | 5 | Establishes the authoritative submission channel and avoids duplicate records. | OSDS operations and program owners | Approved channel and cutover statement. |
| P0.5 | Who may create, review, publish, retire, and clone a questionnaire version? Is a second-person review required before publication? | 4 | Defines publication permissions, audit events, and form-version workflow. | OSDS program owner and security owner | Publication responsibility matrix. |
| P0.6 | Which question types and conditional rules are genuinely needed in the first release? The exports demonstrate text, contact number, choices, consent/declaration, PDF/spreadsheet upload, and individual-file-link fields; confirm whether repeating member/activity rows are needed. | 3 | Controls Phase 3 engine scope and prevents an unsafe general-purpose rules engine. | Form owners and intended users | Approved examples and expected validation for every enabled type. |
| P0.7 | Must questionnaire access require a SCHOOL account, or are anonymous/shareable links allowed? If links are allowed, who creates them, for whom, for how long, and how many submissions? | 5 | Affects identity assurance, abuse controls, response ownership, and privacy exposure. | OSDS, privacy, and identity/security owners | Audience/authentication/link policy. |
| P0.8 | For each documentary requirement, is the authoritative evidence a private upload, an individual external-file link, a physical original, or a controlled hybrid? The complete transcript contains both uploads and a technical preference for individual file links, while explicitly rejecting folder links. | 5 | Changes the schema, storage, security testing, retention, operating cost, and legal custody. | Records, privacy, IT/security, and process owners | Requirement-by-requirement evidence custody matrix. |
| P0.9 | If uploads are approved, where will private files be stored, what file types and size limits apply, and what are the per-application/account quotas? | 5 | Determines object storage, capacity, validation, cost controls, and upload design. | IT infrastructure, records, security, and program owners | Approved storage service, limits, capacity estimate, and cost owner. |
| P0.10 | If external links are approved, must CHED retain a snapshot or checksum, and what happens when access is revoked, content changes, or the link breaks? | 5 | Determines whether submitted evidence remains verifiable and usable as an official record. | Records, legal/privacy, IT, and process owners | Link custody, verification, change-detection, and failure procedure. |
| P0.11 | Which documents still require notarization, wet signatures, or physical originals, and how will receipt/custody be recorded? | 5 | Prevents the platform from presenting an electronic upload as legally sufficient when it is not. | Authorized process and records owners | Document-level legal-form and custody requirements. |
| P0.12 | What personal or sensitive data will questionnaires and documents contain, especially athlete, insurance, contact, and signature data? | 5 | Drives data minimization, field-level access, notices, retention, encryption, and breach impact. | Data Protection Officer, records owner, and program owners | Data inventory and classification. |
| P0.13 | How long must drafts, submitted answers, rejected cases, documents, audit events, and physical-receipt records be retained, and who authorizes disposal or legal hold? | 5 | Retention cannot safely be added after large volumes of records accumulate. | Records officer, DPO, legal, and program owners | Approved retention/disposal schedule. |
| P0.14 | Who may represent a school or athletic association, and what evidence verifies that authority? | 5 | Required before real SCHOOL enrollment or binding submissions. | OSDS/CHEDRO operations and identity owner | Enrollment and representative-verification procedure. |
| P0.15 | Do athletic associations use the SCHOOL account category, or is another organization/account category required? | 4 | Changes organization modeling, onboarding language, permissions, and applicant screens. | Supervisor and OSDS program owners | Approved account-to-organization mapping. |
| P0.16 | Which Phase 2 role permissions are approved, who provisions each role, and can one person hold more than one top-level role? | 5 | Prevents privilege escalation and controls whether Phase 2 defaults can be used beyond technical testing. | Supervisor, OSDS, CHEDRO, and IT/security | Signed action/scope/provisioning matrix. |
| P0.17 | Who creates the first Super User, approves account recovery, reviews privileged access, and handles emergency access? | 5 | Avoids an unaudited permanent administrator or unrecoverable platform. | IT/security and accountable system owner | Privileged-account operating procedure. |
| P0.18 | Must staff authenticate through an existing CHED identity provider, and what MFA method is required? | 5 | Changes login, account lifecycle, recovery, and production security architecture. | CHED IT/security and identity owner | Identity-provider/MFA decision and integration contact. |

## P1 — questions that block COR/ACSA workflow implementation

| # | Question requiring an authoritative answer | Impact | Why the answer changes the system | Proposed decision owner | Expected answer or artifact |
| --- | --- | :---: | --- | --- | --- |
| P1.1 | Who is eligible to apply for COR, and who is eligible to apply for ACSA? | 5 | Determines application creation, validation, and applicant organization types. | COR and ACSA process owners | Separate eligibility rules with examples and exclusions. |
| P1.2 | Does ACSA require an existing valid COR in every case, only for athletic associations, in defined circumstances, or never? The 4:10–5:37 discussion suggests recognition followed by a separate activity-authority application but does not state a complete rule. | 5 | A prerequisite would change validation, user guidance, and case linking. | COR/ACSA policy owner | Explicit rule, affected applicant types, and exceptions. |
| P1.3 | Which office owns a case: institution region, association coverage, event venue, national scope, or another rule? | 5 | Controls routing, confidentiality, queues, and reporting. | OSDS and CHEDRO operations | Jurisdiction matrix for schools, regional associations, national associations, and cross-region activities. |
| P1.4 | When and how may a case be transferred between CHEDROs or Central Office, and who approves the transfer? | 4 | Requires explicit history, access changes, notifications, and pending-task handling. | OSDS/CHEDRO operations | Transfer procedure and exceptional cases. |
| P1.5 | What are the exact COR states and allowed transitions, actors, prerequisites, and required reasons? | 5 | This is the executable COR workflow contract. | COR process owner and CHEDRO reviewers | Signed COR transition matrix. |
| P1.6 | What are the exact ACSA states and allowed transitions, actors, prerequisites, and required reasons? | 5 | Avoids incorrectly reusing the COR process for activity authority. | ACSA process owner and CHEDRO reviewers | Signed ACSA transition matrix. |
| P1.7 | What is the difference between receipt, initial screening, completeness checking, substantive validation, approval, and registry entry? | 5 | Determines permissions, audit evidence, queues, and separation of duties. | Process owners and authorized approvers | Definitions and actor/outcome matrix. |
| P1.8 | When requirements are incomplete, may CHEDRO request correction? How many cycles, what deadline, what remains immutable, and when is a case closed? | 4 | Defines revisions, deadlines, notifications, and historical preservation. | COR/ACSA process owners | Correction/resubmission rules and examples. |
| P1.9 | Who may withdraw, reject, reopen, cancel, reassign, or archive a case, and what reasons/evidence are mandatory? | 4 | These exceptions otherwise cause manual database changes and audit gaps. | Process owners and records owner | Exception matrix. |
| P1.10 | Is a second reviewer or separation between screening and validation required? | 5 | Changes permission assignment, work queues, and acceptance tests. | OSDS/CHEDRO and internal-control owner | Separation-of-duties rule. |
| P1.11 | Does the displayed nine-day duration apply to COR, ACSA, or both; are these working days; and which events pause or restart the clock? | 4 | Prevents misleading deadlines, dashboards, and notices. | Process owner | Approved service-clock definition and holiday calendar. |
| P1.12 | Which notifications are official, who approves their wording, and which channels are allowed (in-app, email, SMS, printed notice)? | 3 | Defines templates, delivery evidence, retries, and privacy controls. | Process and communications owners | Notification matrix and approved templates. |
| P1.13 | What constitutes successful submission and acknowledgment, especially during outages or failed file uploads? | 4 | Determines transaction boundaries, case references, retry behavior, and dispute evidence. | Operations, IT, and records owners | Submission/acknowledgment rule and outage procedure. |

## P2 — questions that block official records and issuance

| # | Question requiring an authoritative answer | Impact | Why the answer changes the system | Proposed decision owner | Expected answer or artifact |
| --- | --- | :---: | --- | --- | --- |
| P2.1 | At what exact event is a Central Registry entry created: receipt, validation, approval, or issuance? | 5 | Prevents premature or missing official records. | Registry and process owners | Approved registry trigger. |
| P2.2 | What are the COR and ACSA registry/certificate numbering formats, uniqueness scopes, sequence owners, reset rules, and reservation/void rules? | 5 | Required for concurrency-safe official identifiers. | Registry/records owner | Numbering specification with examples. |
| P2.3 | Who may approve, sign, and issue each document, and how are delegation, acting appointments, and expiration recorded? | 5 | A role label does not establish legal authority. | Authorized signatory and legal/records owners | Authority/delegation matrix and evidence requirements. |
| P2.4 | Are digital or electronic signatures permitted? If yes, what technology and validation evidence are approved? | 5 | Determines rendering, signing integration, custody, and verification. | Legal, records, IT/security, and signatory | Signature policy and approved service. |
| P2.5 | Which certificate, letter, undertaking, and notice template version is legally approved for COR and for ACSA? | 5 | Prevents producing invalid official documents. | Document owners and authorized signatory | Signed template register and approved merge fields. |
| P2.6 | Is the Certificate of Undertaking required for COR, ACSA, or both, and when/how is the signed undertaking received and validated? | 5 | Changes issuance prerequisites and evidence custody. | Process and records owners | Module-specific undertaking rule. |
| P2.7 | How are correction, reprint, replacement, expiration, suspension, revocation, and cancellation of registry entries/certificates handled? | 5 | Official history must be preserved without silently overwriting issued records. | Registry, legal, and process owners | Lifecycle and replacement/revocation procedure. |
| P2.8 | How is issuance delivered and acknowledged—in person, electronic download, email, or another channel? | 4 | Determines release authorization, receipt evidence, and privacy safeguards. | Records and process owners | Delivery and receipt procedure. |
| P2.9 | What fields may appear in a nationwide recognition lookup, who may search it, and how quickly must revocation/status changes appear? | 5 | Prevents leaking case details or presenting stale recognition status. | Registry owner and DPO | Approved public/authorized projection and freshness rule. |

## P3 — questions that block pilot or production launch

| # | Question requiring an authoritative answer | Impact | Why the answer changes the system | Proposed decision owner | Expected answer or artifact |
| --- | --- | :---: | --- | --- | --- |
| P3.1 | Is OPRKM's DigitalOcean environment the approved host, or was it only an example? Does the approved environment support PHP, PostgreSQL, private storage, queues, scheduler, email, backups, and monitoring? | 5 | Determines whether the implemented architecture can run safely and reliably. | CHED IT/infrastructure, OPRKM, and system owner | Hosting architecture and responsibility matrix. |
| P3.2 | The complete transcript says ICT VAPT clearance is required and also mentions DICT security assurance. Which office performs the test, which office grants launch clearance, and what scope/evidence is required? | 5 | Public deployment cannot rely on conversational or inconsistent authority references. | CHED IT/security | Written launch/security gate. |
| P3.3 | What are the verified numbers of institutions, users per institution, applications/reports per year, files per case, average/maximum file size, and peak simultaneous submissions? | 4 | Replaces the spoken “approximately 3,000 schools” estimate with capacity targets. | OSDS operations and IT | Volume forecast and peak-load scenarios. |
| P3.4 | What backup frequency, recovery point, recovery time, retention, encryption, and restore-test schedule are required? | 5 | Protects official records and determines infrastructure cost. | IT, records, and system owner | Approved backup/disaster-recovery plan. |
| P3.5 | Who owns daily operations, failed jobs, storage monitoring, account support, security incidents, and maintenance after turnover? | 5 | Prevents an unsupported public system and unresolved failed submissions. | OSDS and IT management | Support and incident-responsibility matrix. |
| P3.6 | Which reports, dashboards, and exports are required, and who may see row-level data versus aggregates? | 4 | Reports can bypass otherwise correct scope controls if not explicitly governed. | OSDS/CHEDRO, DPO, and reporting owners | Metric catalog and export permission matrix. |
| P3.7 | Will existing Google Form/Sheet/COR/ACSA records be imported, retained externally, or archived as read-only references? | 4 | Determines migration mapping, duplicate detection, reconciliation, and cutover. | Data owner, records owner, and OSDS operations | Signed migration/archive/reference decision. |
| P3.8 | Confirm or replace the discussed August–December 2026 development window and January submission pressure point. What is the exact cutover date, and how will Google Form/manual submissions during transition be reconciled? | 5 | Avoids an assumed deadline, lost submissions, and duplicate applications. | OSDS operations, engagement owner, and records owner | Approved schedule, cutover, and reconciliation runbook. |
| P3.9 | Is RA 11180 part of the first release, a parallel project, or a later release, and who owns it? | 5 | It is a separate program with separate states and legacy data; unclear priority can derail COR/ACSA delivery. | OSDS Director, RA 11180 owner, and OPRKM | Signed release priority and accountable owner. |
| P3.10 | For RA 11180, what is the approved academic-year cycle, final questionnaire dataset, correction/validation process, regional coordinator role, consolidation rules, and duplicate definition? | 5 | Required before any RA 11180 schema or workflow is implemented. | RA 11180 program/data owners | Separate signed URA and transition/data dictionary. |
| P3.11 | What happens to the existing OPRKM RA 11180 system, seven per-region output files, and historical eight-sheet questionnaire data: migrate, archive, reference, or retire? A meeting participant suggested starting new, but no records disposition was approved. | 5 | Protects continuity and avoids data loss or two conflicting authoritative systems. | OPRKM, RA 11180 data owner, and records owner | Inventory, data-quality report, and signed disposition decision. |
| P3.12 | Has a reviewed Git baseline been committed and has remote CI passed in the authorized repository? | 4 | Establishes reproducible provenance before more schema and workflow work accumulates. | Technical lead/repository owner | Reviewed baseline commit and successful CI run. |

## Source discrepancies to resolve explicitly

1. The complete transcript discusses direct uploads, possible notarized/physical follow-up, and individual-file links; it explicitly distinguishes file links from folder links. None is an approved evidence-custody decision.
2. Google Forms/manual submission is described as a transition option; the authoritative cutover channel is not approved.
3. The combined COR/ACSA image does not prove identical module rules or a COR prerequisite for ACSA.
4. The undertaking template references a 2026 CHED memorandum without a number.
5. The coaches/technical-officials sheet requests contact numbers in its instruction but has no contact-number column.
6. The complete transcript repeatedly describes ICT VAPT clearance as a deployment prerequisite and also invokes DICT in describing security assurance. Obtain a written responsibility and approval path.
7. RA 11180 is separate from COR/ACSA, but its release priority remains undecided.
8. The complete machine transcript and linked artifacts are evidence for clarification, not approval records or approved minutes.
9. Sports was verbally prioritized ahead of originally discussed Lakbay/SAS work; the underlying memo and attachments were not readable during the meeting, so funded scope and deliverable priority require written confirmation.
10. The supplied export is named `COE.docx`, its visible branch says Certificate of Registration, and earlier project materials say Certificate of Recognition (COR). Confirm the legally correct name and acronym.
11. The current form reminder says files must not exceed 10 MB, but the coaches spreadsheet upload permits 1 GB. Approve consistent limits and capacity assumptions.
12. The CHEDRO issuance form has no visible source application/reference ID, registry/serial number, signatory, validity, or delivery evidence. Confirm whether automation supplies these and approve the authoritative issuance linkage.

## Minimum decision package to authorize Phase 3

Phase 3 should be authorized only after answers to P0.1–P0.18 are recorded, or after the accountable owners formally narrow Phase 3 to a technical prototype using synthetic data. At minimum, the approval package should contain:

- signed next-module scope and accountable owners;
- approved artifact inventory and questionnaire versions;
- questionnaire publication and access policy;
- requirement-level evidence custody matrix;
- data classification and retention rules;
- approved role/provisioning matrix and representative enrollment process;
- private storage/link security requirements and capacity limits;
- reviewed Git baseline and an observed successful CI run.

Any unanswered item should remain an explicit backlog blocker with an owner and target decision date; it should not be converted into a hidden coding assumption.
