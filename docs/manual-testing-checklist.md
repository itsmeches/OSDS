# Phase 3D step-by-step manual testing checklist

Record pass/fail, browser, viewport, theme, elapsed time, errors, and screenshots for each section. Use only port 8001.

## Environment

- [ ] Run `Start-ApplicantSandbox.ps1`; expect migrations and seeders to finish without error.
- [ ] Open `/applicant/login`; expect styled Applicant sign in and sandbox notice.
- [ ] Open `/admin`; expect styled staff sign in.
- [ ] Repeat representative pages at 1440 px, 820 px, 375 px, 320 px, 200% zoom, Light, Dark, and System.

## OSDS and Admin publication

- [ ] Sign in as OSDS; expect Questionnaires and My review submissions.
- [ ] Open or clone a draft; edit a required, optional, and conditional question.
- [ ] Edit a documentary requirement; save, leave, and reopen; expect values preserved.
- [ ] Preview and test conditional behavior; expect no applicant record to be created.
- [ ] Check configuration; correct linked validation errors.
- [ ] Send the exact form/document pair for approval.
- [ ] Sign in as Admin and inspect the complete proposal.
- [ ] Approve it; expect publication to remain a separate action.
- [ ] Publish specifically to applicant sandbox; expect an immutable manifest.
- [ ] Confirm later draft edits do not alter that manifest or an earlier submission.

## SCHOOL applicant

- [ ] Sign in as `school@example.invalid`; expect My applications.
- [ ] Start application; expect Registration and ACSA options.
- [ ] Select Harborview and one questionnaire; expect a new draft/editor.
- [ ] Answer required fields, optional field, and trigger the conditional field.
- [ ] Save; expect Unsaved changes → Saving → Saved only after confirmation.
- [ ] Leave and resume; expect saved answers.
- [ ] In Documents, test an invalid URL; expect a safe message without echoing sensitive locator details.
- [ ] Add `https://drive.google.com/file/d/AbCdEfGhIjKlMnOp/view` to each required test requirement.
- [ ] Replace one link, then remove and add it again; expect history-preserving mutations.
- [ ] Check progress; expect no missing applicable answers/documents.
- [ ] Send documents for test review; expect a nonofficial immutable copy.
- [ ] Open Review and submit test application; expect confirmation/tracking page.
- [ ] Confirm the submitted working revision is read-only and history says synthetic/nonofficial.

## CHEDRO screening and correction

- [ ] Sign in as CHEDRO; expect Application intake.
- [ ] Find the submitted Harborview application and open it.
- [ ] Inspect permitted applicable answers and document requirements.
- [ ] Open a document; expect authorization then a Google destination with no locator in audit output.
- [ ] Start screening; expect Under screening.
- [ ] Request a targeted question or document correction with clear summary/instruction.
- [ ] Confirm status history includes the correction request.
- [ ] As SCHOOL, open tracking and Make corrections.
- [ ] Change the requested value, save, leave, resume, and resubmit.
- [ ] As CHEDRO, confirm sequence 2 appears and sequence 1 is unchanged.

## Phase 3C evidence review

- [ ] Before application submission, create a test-review copy from complete evidence.
- [ ] As CHEDRO, open Assigned test reviews.
- [ ] Record accessibility, completeness, and document-match findings.
- [ ] Correct a finding; expect append-only supersession.
- [ ] As SCHOOL, alter evidence and explicitly send a newer copy.
- [ ] Confirm the prior copy/findings remain unchanged.
- [ ] Revoke the reviewer assignment as Admin; expect later CHEDRO access to return not found.

## Negative access checks

- [ ] SCHOOL direct `/admin` access is denied.
- [ ] Staff applicant login is denied.
- [ ] Another SCHOOL cannot open the draft/application UUID.
- [ ] Unassigned and other-region CHEDRO cannot discover the application.
- [ ] Revoked/expired representation or eligibility blocks mutation.
- [ ] Retired/expired manifest leaves history readable and blocks editing/resubmission.
- [ ] Stale second-tab save shows Updated in another tab and preserves local input.
- [ ] Session expiry returns to sign-in without a false save-success state.

## Result classification

- **Automated test passed:** covered by PHPUnit/Livewire.
- **Browser/UI test passed:** performed through a browser by the tester.
- **Manual human test passed:** tester completed and recorded the action.
- **Not tested:** no evidence yet.
- **Failed:** capture safe error, URL, role, timestamp, and reproduction steps.
