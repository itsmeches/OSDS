# OSDS platform requirements baseline

**Review draft, updated 14 October 2026.** Primary sources: [requirements report](../OSDS_REQUIREMENTS_ARCHITECTURE_REPORT.md), [technical specification](../OSDS_TECHNICAL_SPECIFICATION.md), supplied artifacts, and the complete timestamped meeting transcript provided on 14 October 2026. This document distinguishes requested capabilities from meeting discussion and design proposals. Checkpoint 3A implements synthetic questionnaire configuration only; this document is not an approved user requirements analysis (URA).

## Evidence grades

| Grade | Meaning | Use in implementation |
| --- | --- | --- |
| Recorded request | Explicit in the user's initial notes or Phase 0 request | Can guide platform foundation; business rules still need owner approval. |
| Supervisor-confirmed, provisional | Five account labels and order reported by the user | Seed only after matrix review; order grants no inheritance. |
| Observed artifact | Field/wording in blank Office template or supplied process image | Inventory and prototype only; verify version and applicability. |
| Discussed in complete transcript | Statement in the timestamped machine transcript of the full meeting | Stronger meeting evidence and source for follow-up, but not policy or approval. |
| Proposed design | Architectural choice made for review | Implement after technical approval and relevant process gate. |

## Program boundaries

**COR and ACSA** are separate application modules sharing identity, organization, region, evidence, audit, and registry services. The provided image shows a combined five-step, nine-displayed-day path, but does not prove identical eligibility, checklists, signatory rules, or a COR prerequisite for ACSA. Schools and athletic associations were both mentioned as applicants in a meeting; exact eligibility requires approval.

**RA 11180 sports reporting** is a different program. It concerns recurring HEI reporting, validation/correction, and national consolidation, not certificate issuance. A meeting participant called it urgent; release priority is unapproved. The existing OPRKM collection system and its data must be inventoried before a rebuild or migration decision. The supplied RA 11180 enhancement image is labeled proposed.

## Complete-transcript findings

The following statements are now traceable to the complete transcript. They remain meeting statements requiring written approval:

| Time | Observed statement | Requirement effect / unresolved decision |
| --- | --- | --- |
| 4:10–5:37 | The discussion combines the sports reportorial requirement with COR for sports associations and a separate ACSA application before conducting activities. | This is stronger evidence of a possible COR-to-ACSA relationship, but it does not define eligibility or prove that every ACSA applicant must first hold COR. Obtain an explicit rule and exceptions. |
| 6:18–8:44 | Google Forms were proposed as a temporary intake; CHEDRO would screen/validate, register certificate details, obtain the Regional Director's signature/stamp, and inform the HEI for collection. | Confirms the presented current proposal and interim channel, while template status, signatory authority, and cutover remain unapproved. |
| 13:07–15:51 | Participants requested a centralized nationwide recognition lookup. Regional associations were described as registering regionally and national associations with Central Office. | Candidate jurisdiction and lookup requirements; obtain a signed national/regional association matrix and approved visible fields. |
| 16:25–21:39 | Participants wanted less physical-paper handling. Uploads, notarized documents, an undertaking, and some later physical submission were discussed. A technical participant preferred individual file links rather than uploads for capacity reasons and explicitly rejected folder links. | Evidence custody is unresolved. Decide per requirement whether the official evidence is upload, individual file link, physical original, or hybrid. |
| 22:06–23:03 | Applicant, regional, and Central Office users were expected to query status and see whether work remained at a regional office or moved elsewhere. | Supports status visibility and routing history, but forwarding destinations and access rights require a workflow/jurisdiction matrix. |
| 23:08–23:52 | A director-signed User Requirements Analysis was requested as the implementation baseline and change-control reference. | Signed URA is an explicit implementation gate rather than an optional documentation task. |
| 27:19–33:20 | OPRKM DigitalOcean was mentioned as a possible host. The speaker twice described ICT vulnerability and penetration testing clearance as necessary before public deployment; DICT was also mentioned in describing security assurance. | Candidate infrastructure and launch gate only. Confirm the actual host, accountable office, whether ICT or DICT performs/accepts VAPT, and required evidence in writing. |
| 28:20–29:50 | School enrollment through an identified focal person was discussed, commonly an athletic/sports director; an association secretariat was suggested for associations. | Candidate representative types, not verification rules. Obtain authority evidence and association account mapping. |
| 31:22–33:50 | The communication was described as August–December 2026 for coding/testing/deployment/maintenance, while January was described as a submission pressure point. Google Forms or manual intake was proposed during development. | Dates and transition approach need written confirmation. Do not represent January as a delivered production commitment until scope, VAPT, cutover, and acceptance are approved. |
| 34:46–35:34 | Secure central/admin and external user accounts plus repeated status queries for almost 3,000 schools and possible simultaneous use were raised. | Use approximately 3,000 schools as a planning scenario only; obtain verified users, files, submissions, and concurrency targets. |
| 36:16–45:35 | RA 11180 was expressly distinguished from the first sports registration work. Its existing OPRKM system has regional access, repeated per-region downloads, seven output files per region, errors, no usable consolidated view, and no available original developer. Eight questionnaire sheets were under recalibration. | Confirms a separate legacy-system discovery and data-quality workstream. The meeting suggestion to start new does not authorize discarding or omitting historical records. |
| 49:17–52:22 | Participants said the PTS2 engagement had originally been associated with Lakbay and SAS work, while sports was verbally requested first because submissions were nearer; the source memo/attachments were not fully readable in the meeting. | Written authority and priority are required to establish that OSDS sports work is within the funded engagement and to define remaining Lakbay/SAS obligations. |

## Capability register

| Capability | Status | Acceptance at requirements level |
| --- | --- | --- |
| Login and five account types | Recorded request; five labels provisional | Named role/permission owner approves actions, provisioner, and deactivation path. |
| Region and institution/association scoping | Recorded request plus meeting discussion | Jurisdiction matrix covers national, regional, cross-region, and school representation cases. |
| Configurable forms and requirements | Recorded request | Module owner signs off forms, required evidence, conditional rules, and published versions. |
| Shareable questionnaire links | Recorded request | Audience, authentication, expiry, reuse, and data ownership approved. |
| Screening, validation, correction, status | Recorded request and supplied process image | Separate COR/ACSA transition maps specify actor, evidence, outcome, and exceptions. |
| Central registry and certificates | Recorded request and supplied process image | Registry trigger, number rules, signatory, validity, replacement, and issuance evidence approved. |
| Searchable association recognition | Discussed in meeting | Registry owner approves user audience and minimal fields visible nationwide. |
| Evidence custody | Conflict in meeting: upload versus individual file links; some physical originals | Records, privacy, and IT owners select hosted, linked, or hybrid mode and retention/custody controls. |
| Dashboards and exports | Recorded request | Metric definitions, scope, and export approvals signed off. |
| RA 11180 reporting | Proposed image; described as urgent in meeting | Separate owner approves release priority, reporting cycle, dataset, validation, and legacy-data disposition. |
| Audit, notifications, security | Recorded request | Security/records owners approve classification, logging, recovery, and launch gate. |

## Candidate source templates

The five downloaded Office files are inventoried in the [technical specification](../OSDS_TECHNICAL_SPECIFICATION.md). They suggest an undertaking, insurance coverage, personnel, member HEIs, and activity calendar. None is assigned to COR or ACSA. The undertaking's 2026 memorandum reference lacks a number; the coaches sheet's instruction requests a contact number that the header lacks. Document owners must resolve both before online validation or generation is based on them.

## Current Google Form exports

Three DOCX exports supplied on 14 October 2026 provide a more complete snapshot of the applicant form's Registration and ACSA branches and the separate CHEDRO issuance form. Their observed fields, requirements, capture modes, and discrepancies are recorded in the [current form artifact inventory](./current-form-artifact-inventory.md).

They confirm that the prototype uses a hybrid of PDF/spreadsheet uploads and individual Google Drive file links. They also expose unresolved design and policy issues: Certificate of Registration versus COR/COE terminology; validation of CMO No. 13, s. 2026; a 10 MB reminder versus a 1 GB coaches spreadsheet control; sensitive background-clearance records; missing visible event details in ACSA; and a standalone issuance form without a visible application/reference identifier. These observations improve Phase 3 test fixtures but do not establish approved questions or evidence rules.

## Process clarification package

Obtain signed URA and approved forms/templates for each module; separate COR/ACSA flowcharts; an action-by-role and jurisdiction matrix; registry and certificate rules; approved evidence custody; RA 11180 release and migration decision; hosting/security gate; retention and recovery rules. Reconcile the complete machine transcript with approved minutes before treating any statement as final.
