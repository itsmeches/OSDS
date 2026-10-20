# Reproducible synthetic end-to-end scenario

## Main Phase 3D scenario

1. Start the isolated sandbox on port 8001.
2. OSDS opens or clones the Registration questionnaire, saves it, checks it, and sends the exact questionnaire/document pair for approval.
3. Admin inspects, approves, and explicitly publishes it to **applicant sandbox**.
4. SCHOOL signs in, selects Harborview Learning Institute and Registration, and creates a draft.
5. SCHOOL supplies required answers, exercises the optional and conditional fields, and saves.
6. SCHOOL adds valid synthetic Google file links for applicable required documents and checks completeness.
7. SCHOOL submits the test application. The application becomes read-only and shows immutable submission sequence 1.
8. Assigned Synthetic North CHEDRO opens Application intake, begins screening, and requests a targeted correction.
9. SCHOOL opens Make corrections. The platform creates a new working revision copied from sequence 1 without mutating it.
10. SCHOOL changes the requested answer or evidence, saves/resumes, and resubmits.
11. CHEDRO sees resubmission sequence 2. Sequence 1, its evidence membership, and the complete state history remain unchanged.

Expected state path:

```text
draft → submitted → under screening → correction requested → resubmitted
```

No step creates an official application decision, COR/ACSA validation, certificate, registry record, or issuance.

## Separate Phase 3C evidence-review scenario

1. While a working draft remains editable, SCHOOL completes the applicable document requirements.
2. SCHOOL selects **Send documents for test review**. This creates immutable evidence review copy 1 and does not submit the application.
3. Assigned CHEDRO opens Assigned test reviews and records accessibility, completeness, and document-match findings.
4. CHEDRO corrects a finding by creating a superseding finding; the earlier finding remains unchanged.
5. SCHOOL replaces one evidence reference and explicitly sends a newer test-review copy.
6. CHEDRO sees copy 2 while copy 1 and all its findings remain immutable.
7. Admin revokes the CHEDRO evidence reviewer assignment; future access is denied without exposing case existence.

This evidence-review history remains separate from the Phase 3D submitted application and screening state.
