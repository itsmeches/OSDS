# Focused Form Builder redesign completion report

**Date:** 15 October 2026
**Scope:** FB1–FB6 questionnaire authoring usability only. Phase 3C remains unstarted.

## Result

The Form Builder now uses a focused outline and editor, one four-stage workflow, an optional shared-renderer preview, and a concise questionnaire overview. All state changes continue through the existing authoring, validation, review, publication, authorization, audit, and PostgreSQL protection paths.

## Work packages completed

### FB1 — Contrast and rendering foundation

- Replaced undefined short CSS variables with the shared `--osds-color-*` vocabulary.
- Removed the duplicate token import and added semantic input, selected, border, status, and disabled tokens for light and dark themes.
- Normalized affected builder characters to UTF-8-safe HTML entities.
- Kept the shared `questionnaires.field` and `questionnaires.requirement` components as the preview rendering source.

### FB2 — Focused builder and outline

- Added stable-key question and requirement selection.
- The outline shows the selected item plus required, conditional, and validation-error states.
- Only the selected question or requirement editor is expanded.
- Add, duplicate, delete, Move up, and Move down actions preserve the selected stable key.
- Basic fields appear first; instructions, conditions, and advanced settings are progressively disclosed.
- Existing save error mapping, optimistic concurrency, local input preservation, and domain persistence remain in use.

### FB3 — One four-stage workflow

The builder owns one sequence:

1. Build questionnaire
2. Required documents
3. Review and test
4. Send for approval

The Review stage runs the authoritative backend validator. Send remains unavailable until the current saved configuration has passed validation. Review submission binds the exact questionnaire and requirement versions.

### FB4 — Optional synchronized preview

- Preview is closed by default.
- Side preview is available on wide screens; Full preview uses the whole workspace.
- Test as applicant keeps answers only in transient Livewire state.
- Conditional visibility uses the existing shared conditional logic.
- Preview testing creates no applicant drafts or answers.
- Narrow layouts use a full-width preview and avoid nested fixed-height scrolling.

### FB5 — Questionnaire overview and creation

- The overview shows lifecycle state, section/question/document counts, last update, and one clear next action.
- Editing, preview, checks, and review preparation were removed from the overview and remain in the builder.
- Version history and technical labels are in a secondary disclosure.
- Multiple draft pairs still require explicit selection.
- **Create questionnaire** is prominent for authorized OSDS users, including at the top level.
- Existing support was confirmed: `QuestionnaireDraftService::createPair` already creates a definition and paired initial drafts under an existing module. The redesigned action now requires selection of an active approved module and does not create or authorize modules. No migration was needed.

### FB6 — Usability, accessibility, and regression work

- Added tests for stable selection after reordering.
- Added tests that unsaved question and requirement changes survive selection, stage, and preview changes.
- Kept the unsaved-navigation warning and save-failure recovery message.
- Updated task-workspace tests for the simplified journey and added questionnaire-creation coverage.
- Added desktop, mobile, light, dark, and tablet screenshot evidence.
- Actual first-time staff usability testing remains pending.

## Navigation comparison

| Before | After |
| --- | --- |
| Module workspace repeated Questions, Documents, Preview, and Review | Overview explains status and offers one next action |
| Builder displayed every editor and preview simultaneously | Outline plus one focused editor; preview opens only when requested |
| Four page links competed with header mode controls | One four-stage workflow indicator |
| Technical versions dominated primary content | Versions remain available under technical details |
| Creation required first entering a module | Authorized OSDS users can select an approved module from the Create questionnaire action |

## Unsaved state and concurrency

Verified automated scenarios:

- Edit Question 1, select Question 2, and return without losing the local edit.
- Open and close preview without losing local edits.
- Edit a requirement, change stages, and return without losing it.
- Invalid save retains local values and maps the server validation error.
- The page registers an unsaved-navigation warning for Unsaved changes, Save failed, and Conflict.
- Stable keys keep the selected question selected after reordering.
- Existing stale-writer tests continue to reject conflicting lock versions.
- No success state is shown until the save transaction returns confirmed lock versions.

## Visual and responsive verification

Headless Microsoft Edge rendered the actual authenticated builder after the production CSS build.

| Check | Observed result |
| --- | --- |
| Desktop 1440 px, light | Rendered without horizontal overflow; focused outline/editor visible |
| Desktop 1440 px, dark | Rendered with dark surfaces and visible text/control boundaries |
| Mobile 375 px, light/dark | Document width equaled viewport width; workflow and actions stacked |
| Tablet 820 px | Document width 805 px within an 820 px viewport |
| 200% scale simulation | Document width 705 px within a 720 px viewport |
| Main editor text contrast | Light: 12.34:1; dark: 15.18:1 from computed foreground/background colors |
| Reduced motion | Existing reduced-motion rule remains applied to builder descendants |
| Keyboard semantics | Native buttons, inputs, selects, details/summary, landmarks, labels, and explicit action names retained |

These checks are implementation verification and do not constitute a formal WCAG conformance audit.

## Screenshot evidence

- [Desktop, light](screenshots/focused-builder-desktop-light.png)
- [Desktop, dark](screenshots/focused-builder-desktop-dark.png)
- [Mobile, light](screenshots/focused-builder-mobile-light.png)
- [Mobile, dark](screenshots/focused-builder-mobile-dark.png)
- [Tablet, light](screenshots/focused-builder-tablet-light.png)

## Verification results

| Check | Result |
| --- | --- |
| Focused Form Maker tests | Passed: 10 tests, 47 assertions |
| UI/workspace and questionnaire configuration tests | Passed: 18 tests, 65 assertions |
| Complete PostgreSQL PHPUnit suite | Passed: 61 tests, 219 assertions |
| Existing migration status | All migrations through Phase 3B are applied |
| New database migration | None |
| Laravel Pint | Passed |
| Vite production build | Passed without CSS optimization warnings |
| `git diff --check` | Passed |

## Security and lifecycle preservation

- OSDS authoring still requires active account and explicit permission.
- Admin, CHEDRO, and SCHOOL remain denied from OSDS authoring routes.
- All saves use `QuestionnaireAuthoringService`.
- Checks use `ConfigurationValidator`.
- Exact-pair submission uses `QuestionnairePublicationService`.
- Published and in-review configurations remain protected by services, policies, and PostgreSQL immutability guards.
- Preview/test state does not persist applicant records.
- No Phase 3C evidence, submission, certificate, or issuance capability was added.

## Known limitations

- Reordering uses accessible Move up/Move down actions; drag and drop remains intentionally excluded.
- Actual first-time OSDS staff usability testing has not yet been conducted.
- The top-level discovery still groups entries by approved service because documentary requirement versions remain module-scoped in the approved schema. The selected overview presents the working questionnaire by name.
- This checkpoint did not change questionnaire governance or authorize new official modules.

## Manual OSDS acceptance checklist

Use [focused-form-builder-usability-test.md](focused-form-builder-usability-test.md). Record task completion, elapsed time, unnecessary clicks, scrolling, navigation mistakes, unclear labels, and help required. Mark staff testing complete only after a real participant performs it.
