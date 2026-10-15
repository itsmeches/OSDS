# OSDS Form Maker 2.0 completion report

**Date:** 15 October 2026
**Scope:** Visual questionnaire authoring, shared preview rendering, guided validation, review handoff, and usability foundation. Phase 3C remains unstarted.

## Implemented by work package

### FM2.1 — Shared rendering parity

- Added shared questionnaire field and document-requirement Blade components.
- Refactored the Phase 3B applicant draft editor and staff questionnaire preview to use the same field rendering foundation.
- Added accurate controls for short answer, paragraph, multiple choice, checkboxes, yes/no, date, whole number, decimal number, telephone, email, and declaration.
- Kept preview values ephemeral and applicant values under the existing `ApplicantDraftService` boundary.

### FM2.2 — Unified visual builder

- Added the protected route `/admin/form-builder/{form}/{requirements}`.
- Added **Create questionnaire** and **Open visual Form Builder** actions to the task-based Questionnaires workspace.
- The open action requires explicit selection when multiple draft versions exist and binds the builder to the exact pair.
- Added inline section, question, choice, and document cards with add, reorder, duplicate, and confirmed delete actions.
- Stable keys are generated automatically, hidden from ordinary staff, and preserved during label edits.
- Added `QuestionnaireAuthoringService` for permission checks, row locks, full-pair transactional saves, audit recording, and optimistic concurrency.
- Existing `lock_version` columns protect both paired records, including edits occurring within the same timestamp window. No migration was required.

### FM2.3 — Live preview and test mode

- Added **Edit and Preview**, **Preview only**, and **Test form** modes.
- Desktop uses a persistent split layout; responsive breakpoints stack the preview for narrower screens.
- Test answers are Livewire-only state. They create no application drafts, answers, evidence, submissions, or review records.
- Preview displays a persistent preview-only notice and identifies unsaved content.
- Questions and document requirements respond to the same conditional evaluator used by the platform.

### FM2.4 — Guided validation and conditions

- Added natural-language condition controls using earlier question labels and safe existing operators.
- Choice-source conditions offer stored option keys through human-readable labels.
- Advanced settings use progressive disclosure.
- `ConfigurationValidator` now exposes structured, item-linked findings while retaining authoritative failure behavior for lifecycle services.
- **Ready for review** is set only after the latest saved database state passes the backend validator. Any edit clears it.
- Choice questions require at least two choices in the visual authoring path and authoritative check.

### FM2.5 — Review, accessibility, and usability

- Added a review summary with counts, lifecycle explanation, and direct submission of the builder’s exact bound pair.
- Send for review remains unavailable until the configuration is saved and authoritatively checked.
- Added accurate save states: Unsaved changes, Saving, Saved, Save failed, and Conflict.
- Dirty-page navigation warning and conflict messaging preserve local working state.
- Added responsive layouts, visible semantic labels, live status announcements, non-color status text, reduced-motion handling, and retained Light/Dark/System support.
- Prepared the actual-staff usability script; staff testing remains pending and is not claimed as completed.

## Navigation and workflow

### Before

```text
Questionnaires → separate technical questionnaire resource → save
               → separate technical document resource → save
               → simplified preview → manually pair versions in review modal
```

### After

```text
Questionnaires → select module → create/open exact draft pair
               → Build form → Required documents
               → Live preview/Test form → Save → Check form
               → Send exact pair for Admin review
```

Technical resource routes remain policy-protected for support, but ordinary authoring uses the Form Builder.

## Security and lifecycle verification

- Builder requires an active account and explicit questionnaire-draft permission.
- Admin and SCHOOL users cannot access the OSDS builder through their role labels.
- Save reauthorizes in a domain service and locks both paired version rows.
- In-review, published, and retired records cannot be changed through the authoring service; PostgreSQL immutability triggers remain in force.
- Review uses the existing `QuestionnairePublicationService`, hashes, approval process, immutable manifests, and audit guarantees.
- Preview interaction creates no applicant records.
- No evidence collection, official application submission, CHEDRO processing, registry, certificate, issuance, RA 11180, or production publication feature was added.

## Verification results

| Check | Result |
| --- | --- |
| Baseline before implementation | Passed: 49 tests, 158 assertions |
| FM2.1 focused gate | Passed: 17 tests, 50 assertions |
| FM2.2 focused Form Maker gate | Passed: 4 tests, 14 assertions |
| FM2.3/FM2.4 focused gate | Passed: 21 tests, 64 assertions |
| Complete PostgreSQL suite | Passed: 53 tests, 172 assertions |
| Blade compilation | Passed |
| Laravel Pint | Passed after formatting |
| Frontend production build | Passed with Vite 8.3.4 |
| Development migration status | All migrations ran; no Form Maker migration added |
| Git whitespace check | Passed before completion documentation |

## Accessibility and responsive findings

- Controls use native inputs, fieldsets, legends, labels, details, and buttons.
- Save status uses an `aria-live` region; errors use alert semantics.
- Destructive actions require confirmation.
- Layout changes from split columns to a single column at tablet width and simplifies stage/condition grids on mobile.
- Light, Dark, and System remain available through Filament.
- Reduced-motion rules are present.
- Automated/code-level checks and component rendering passed. Formal WCAG conformance and real assistive-technology testing have not been claimed.

## Manual acceptance checklist

- [ ] Create a synthetic questionnaire.
- [ ] Add five supported question types.
- [ ] Reorder and duplicate questions.
- [ ] Create and test a conditional question.
- [ ] Add two document requirements.
- [ ] Trigger and correct a validation finding.
- [ ] Save, leave, and reopen the draft.
- [ ] Simulate a second-tab conflict and confirm local input remains.
- [ ] Send the checked exact pair for review.
- [ ] Confirm Admin, CHEDRO, and SCHOOL access boundaries.
- [ ] Complete the actual OSDS participant script in `docs/osds-form-maker-2-usability-test.md`.

## Known limitations

- Form titles can be edited only while the shared definition has no published or retired version. Historical definition names remain immutable. A future per-version title would require a separately approved migration.
- The first release supports one simple `all` condition through the visual editor. Existing backend nested `all`/`any` evaluation remains supported, but a complete nested rule composer is deferred.
- Reordering uses accessible up/down controls instead of drag and drop.
- Explicit Save draft is used; timed autosave is deferred.
- Raw advanced validation JSON remains available only in the protected technical fallback resource. The visual builder does not expose it.
- Screenshot capture was not automated in this checkpoint; the manual checklist should capture approved representative screens after staff review.

## Stop boundary

Form Maker 2.0 is complete within its approved scope. Phase 3C has not started.
