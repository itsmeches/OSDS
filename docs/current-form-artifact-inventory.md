# Current COR/ACSA Google Form artifact inventory

**Observed 14 October 2026.** This inventory was extracted from three user-supplied DOCX exports of the current Google Forms. It records visible form content only. It does not establish that the questions, cited issuance, validation wording, evidence rules, or document limits are approved CHED policy.

## Artifact relationship

| Export | Observed role | Important qualification |
| --- | --- | --- |
| `COE.docx` | Applicant form introduction plus the Certificate of Registration branch | The filename and user description say “COE,” but the visible form option and headings say **Certificate of Registration**. Confirm whether COE is a filename mistake, another certificate, or an intended term. |
| `ACSA.docx` | Authority to Conduct Sports Activities branch of the same applicant form | The visible title says the overall form covers Certificate of Registration and ACSA pursuant to CMO No. 13, s. 2026. |
| `CHEDRO ISSUANCE.docx` | Separate staff-facing issuance/register form | It collects a small set of certificate-output fields and does not visibly link to the applicant response by application/reference ID. |

The applicant form states that submission does not itself constitute approval, registration, or grant of authority. It describes CHEDRO evaluation/action after completeness and compliance validation and says deficient applications are returned for compliance. Treat those statements as prototype wording pending process-owner approval.

## Shared applicant-form content

- Google-account email and identity metadata are collected by Google when files are uploaded/submitted.
- A Data Privacy Act notice and required consent appear before application-type selection.
- The application-type choices shown are `CERTIFICATE OF REGISTRATION` and `AUTHORITY TO CONDUCT SPORTS ACTIVITIES`.
- Both branches ask for organization and designated-representative information, a checklist, requirement-specific evidence, a pre-submission review confirmation, and a certification of truth/completeness/authenticity.
- The form says a response copy is emailed to the submitting account.

Before reproducing these elements, approve the privacy notice, lawful basis/consent design, account identity rules, certification wording, notification channel, and whether a response copy may contain or link to sensitive information.

## Certificate of Registration branch

### Applicant fields

| Field | Observed choices / behavior |
| --- | --- |
| Association type | Athletic Association with HEI Membership; Individual or Private Organization |
| Association name | Full name; “do not abbreviate” instruction |
| Complete address | “Do not abbreviate” instruction |
| Participating HEI category | State Universities and Colleges; Local Universities and Colleges; Private Organization |
| Head | Name of Head/President/Executive Director |
| Designated representative | Full name, designation/position, contact number |

Clarify whether participating-HEI category is single or multiple choice and whether the form also requires the actual member-HEI list as structured records.

### Observed requirements

| Requirement | Capture mode shown | Displayed limit |
| --- | --- | --- |
| Notarized Certificate of Undertaking | PDF upload | 10 MB |
| Constitution and By-Laws | PDF upload | 10 MB |
| SEC Registration Certificate or equivalent | PDF upload | 10 MB |
| Operations manual or technical guidelines per sport/activity | PDF upload | 10 MB |
| Athlete eligibility/accreditation requirements | PDF upload | 10 MB |
| Annual coaches/officials package, including profile, NBI/PNP clearances, licenses/employment certificates, and GMRC | PDF upload | 10 MB |
| List of member HEIs | Google Drive file link | No limit shown |
| MOA from all participating HEIs for student-athletes | PDF upload | 10 MB |
| Grievance/complaints mechanism | PDF upload | 10 MB |
| List of coaches | Spreadsheet upload | 1 GB |

The annual coaches/officials package and separate coaches spreadsheet appear potentially duplicative. “GMRC” is not expanded. Confirm the intended evidence, whether background clearances are necessary and lawful to collect, access restrictions, validity/expiry, and retention.

## ACSA branch

### Applicant and activity fields

| Field | Observed choices / behavior |
| --- | --- |
| Institution type | HEI; Athletic Association; National Sports Association; Individual/Private Organization; Government |
| Sports activity type | Competition; Seminar/Workshop; Training; Sports Conference/Summit; Other |
| Institution/organization name | Full name; “do not abbreviate” instruction |
| Complete address | Full address; “do not abbreviate” instruction |
| Head | Name of Head/President/Executive Director |
| Designated representative | Full name, designation/position, contact number |

The visible export does not show event dates, venues, participating institutions, expected participants, or a direct COR/registration identifier. Confirm whether these exist elsewhere or are missing from the prototype.

### Observed requirements

| Requirement | Capture mode shown | Displayed limit |
| --- | --- | --- |
| Notarized Certificate of Undertaking | PDF upload | 10 MB |
| Calendar of Athletic Activities | Google Drive file link | No limit shown |
| Safety Protocols | PDF upload | 10 MB |
| Insurance Coverage | PDF upload | 10 MB |
| Coordination with HEI for compliance with the CHED memorandum on local off-campus activities | PDF upload | 10 MB |
| List of Coaches | Spreadsheet upload | 1 GB |

Confirm what document proves “coordination,” which memorandum/version applies, whether activity-specific dates and venues are required as structured data, and whether the calendar link must point to one file rather than a folder.

## CHEDRO issuance form

The separate form visibly collects:

- staff email;
- certificate type: Certificate of Registration or Authority to Conduct Sports Activity;
- HEI/association name;
- address;
- date issued; and
- CHED Regional Office from CHEDRO I–XII, NCR, CAR, CARAGA, MIMAROPA, and NIR.

The export does not visibly collect an application/reference ID, registry number, certificate serial number, issuing officer, signatory/delegation, template version, validity period, approval decision, issuance status, or delivery evidence. These may be added by the linked automation or certificate template, but that has not been established from this form export. The platform should not reproduce free re-entry as the authoritative issuance process without mapping the source application and approved registry rules.

## Cross-artifact issues requiring resolution

1. **COR/COE terminology:** visible form content uses Certificate of Registration; the export is named `COE.docx`, while earlier planning materials use COR for Certificate of Recognition. Confirm the legally correct document name and acronym.
2. **CMO authority:** the forms cite CMO No. 13, s. 2026, “Policies and Guidelines on Student-Athletes Protection.” Verify the official issued order, exact title, effective date, and applicable provisions before displaying or enforcing it.
3. **Evidence limits:** the checklist reminder says compressed files must not exceed 10 MB per file, while the coaches spreadsheet control permits 1 GB. Set approved limits based on operational need and infrastructure capacity.
4. **Hybrid custody:** most evidence is uploaded, but two requirements use Google Drive links. Approve whether the platform preserves uploads, file links, snapshots/checksums, physical originals, or a controlled hybrid.
5. **Sensitive records:** coaches/officials packages may contain background clearances, profiles, licenses, employment documents, and contact information. Complete privacy classification, data minimization, role access, retention, and breach-impact review.
6. **Duplicate/manual entry:** CHEDRO re-enters organization and issuance information in a standalone form. Define how a validated application becomes an issuance record without transcription errors or bypassing approval.
7. **Branch applicability:** the applicant introduction says HEIs, athletic associations, and other organizations can apply, but the Registration branch shows narrower association types. Approve eligibility separately for Certificate of Registration and ACSA.

## Phase 3 impact

These artifacts provide realistic synthetic configuration examples for text, telephone, single/multiple choice, consent/declaration, PDF upload, spreadsheet upload, and individual-file-link fields. They do not authorize production configuration. Before Phase 3 uses them, obtain an owner-approved artifact/version register, correct terminology, approved evidence custody and limits, privacy classification, and a publication authority matrix.
