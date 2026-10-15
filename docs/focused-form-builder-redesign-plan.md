# Focused Form Builder redesign plan

**Status:** Proposed for review; implementation is not authorized
**Date:** 15 October 2026
**Boundary:** Form Builder and Questionnaire workspace usability only. Phase 3C, evidence collection, official submission, certificates, and issuance remain excluded.

## 1. Current usability audit

### Form Builder

| Priority | Finding | Classification | Effect |
| --- | --- | --- | --- |
| P0 | Builder CSS uses short variables such as `--osds-text`, `--osds-border`, and `--osds-surface`, while the design system defines `--osds-color-*` variables. | Styling defect | Invalid computed colors produce weak text, borders, steps, and dark-theme inconsistencies. |
| P0 | The builder combines the Filament sidebar, a 55/45 editor/preview split, sticky preview, and an internal preview scrollbar. | Layout defect | Narrow editing space, clipped headings, nested scrolling, and poor 200% zoom behavior. |
| P1 | Every question is expanded with its complete editor. | Interaction design problem | Long forms require excessive scrolling and are difficult to scan or navigate. |
| P1 | Header actions, four workflow links, page navigation, and the workspace rail compete for attention. | Navigation hierarchy problem | Users cannot easily identify their location or next action. |
| P1 | Preview is always present in the default `edit_preview` mode and uses its own scroll area. | Interaction design problem | Preview consumes space even when the user is only editing; controls and badges can be clipped. |
| P1 | Question editing, document configuration, testing, checking, and review submission appear on one long page. | Information architecture problem | The user must remember state across distant page sections. |
| P1 | Save state is visible but visually competes with mode buttons. Technical version wording dominates the context line. | Visual hierarchy problem | Users can miss whether their work is saved and may confuse versions with workflow progress. |
| P2 | Question move controls use up/down buttons but do not show a clear destination or selected item. | Usability limitation | Reordering is safe and dependency-free, but inefficient in long sections. |
| P2 | Validation errors can link to a question, but there is no persistent outline marker for which questions contain errors. | Navigation limitation | Users may lose context after correcting one error. |

### Questionnaire workspace

| Priority | Finding | Effect |
| --- | --- | --- |
| P1 | A module workspace repeats Questions, Documents, Preview, and Review before the user enters the Form Builder, which repeats the same journey. | Creates redundant workspace and builder layers. |
| P1 | Technical questionnaire and document version lists are primary content. | Ordinary users must interpret implementation records before continuing work. |
| P1 | Several header actions compete: create, open builder, submit, and clone. | The next action varies by lifecycle state but is not presented as one primary path. |
| P2 | Exact version selection is correctly required when multiple drafts exist, but labels emphasize version numbers over questionnaire names and status. | Safe behavior is harder to understand than necessary. |
| P2 | Module cards are the top-level discovery unit, although a module may contain multiple form definitions. | This will become confusing as more questionnaires are created. |

### Rendering and component observations

- The shared `questionnaires.field` component correctly supports text, paragraph, choice, yes/no, declaration, date, integer, decimal, telephone, and email controls. It should remain the rendering source for builder preview and applicant drafts.
- Preview answers are held only in Livewire component state and are not persisted. This is correct and must remain unchanged.
- The recent Save Draft fix correctly preserves unsaved state and maps backend validation errors. The redesign must reuse it.
- Some visible mojibake characters originate in source strings, not from the questionnaire renderer. These should be normalized to UTF-8 during implementation.

## 2. Proposed screen hierarchy

```text
Questionnaires
  └─ Questionnaire overview
       ├─ Continue editing
       ├─ Preview and test
       ├─ Check configuration
       ├─ Send for approval
       └─ Version history
            └─ Form Builder
                 1. Build questionnaire
                 2. Required documents
                 3. Review and test
                 4. Send for approval
```

Breadcrumbs inside the builder:

```text
Questionnaires → ACSA Application → Edit draft
```

Only one workflow indicator appears in the builder. The currently selected stage receives strong emphasis; completed and upcoming stages remain readable but quieter.

## 3. Focused editor layout

### Desktop

The builder uses three functional areas, with only two required at once:

```text
┌──────────────────────────────────────────────────────────────────┐
│ Breadcrumbs                                                      │
│ ACSA Application          Draft · Saved          Preview  Save   │
│ 1 Build | 2 Documents | 3 Review and test | 4 Send              │
├──────────────────────┬───────────────────────────────────────────┤
│ Form outline         │ Focused editor                            │
│                      │                                           │
│ Applicant details    │ Edit question                 Required    │
│  1 Organization type│ Question title                           │
│  2 Organization name│ Answer type                              │
│  3 Activity type     │ Choices                                  │
│                      │ Advanced settings ▸                      │
│ + Add question       │                                           │
│ + Add section        │                                           │
└──────────────────────┴───────────────────────────────────────────┘
```

- The outline occupies about 18–22rem and lists sections and numbered questions.
- Selecting a question opens only that question in the focused editor.
- Selected, invalid, and conditionally configured questions receive distinct text/icon states in addition to color.
- The focused editor shows question title, answer type, required state, and choices first.
- Instructions, validation, and conditions remain under clearly labelled collapsible groups.
- Duplicate, delete, and move actions appear in one secondary action menu. Delete retains consequence-specific confirmation.
- Reordering uses accessible Move up/Move down controls initially. No drag library is justified for this checkpoint.
- Save state and **Save draft** remain visible in a non-obstructive page header or footer action bar.

### Tablet, mobile, and zoom

- At tablet width or 200% zoom, the outline becomes a collapsible drawer above the editor.
- At narrow mobile width, selecting an outline item transitions to a full-width editor with **Back to form outline**.
- Preview becomes full width rather than sharing the narrow screen.
- Actions retain a logical keyboard order and do not depend on hover.

### Component state proposed

Add presentation-only component state such as:

- `stage`: `build`, `documents`, `review`, or `send`;
- `selectedSectionKey`;
- `selectedQuestionKey`;
- `selectedRequirementKey`;
- `previewOpen` and `previewWidth`/mode;
- `previewTesting`.

Selections use stable keys rather than array positions so moving questions does not switch the editor to a different item. None of these fields alter published data or require a migration.

## 4. Preview interaction design

### Entry points

- **Preview** toggles a side panel on sufficiently wide screens.
- **Full preview** expands the shared renderer to the full workspace.
- **Test as applicant** is a switch inside Preview. It enables temporary controls and conditional behavior.
- **Close preview** restores the focused editor without losing draft edits.

### Behavior

- Preview is closed by default and never creates application drafts or answers.
- The selected outline question scrolls into view in preview when visible under current conditions.
- Selecting a question in the outline may highlight its preview field without changing an answer.
- Hidden conditional questions show an author-only explanation in edit preview; applicant test mode reflects actual visibility.
- Inputs use the existing shared renderer and shared light/dark tokens.
- Preview has one scroll context. It must not create a page-height nested scrollbar beside the main page scrollbar.
- Unsaved changes are labelled clearly in preview.
- Testing answers are discarded when the component closes or refreshes.

## 5. Color and contrast remediation

### Root correction

Use the existing `--osds-color-*` token vocabulary consistently. Remove undefined short-name references and the duplicate token import in the Filament theme.

### Semantic roles

Verify or introduce paired light/dark variables for:

- canvas, primary surface, raised surface, and selected surface;
- primary text, secondary text, and disabled text;
- standard, strong, input, and focus borders;
- primary, secondary, destructive, and disabled actions;
- information, success, warning, and error foreground/background/border combinations.

### Required checks

- Normal text: target at least 4.5:1.
- Large text and meaningful control boundaries: target at least 3:1.
- Focus indicators: visible against adjacent colors.
- Selected outline item, inputs, cards, errors, disabled controls, preview fields, badges, and buttons in both themes.
- Do not rely on blue alone for selected or completed states.

Actual computed foreground/background combinations will be tested. The report will describe observed results and will not claim full WCAG conformance without a broader assessment.

## 6. Questionnaire workspace simplification

### Top-level Questionnaires page

Show authorized questionnaire definitions rather than only program/module cards:

- questionnaire title and purpose where available;
- module/service name;
- lifecycle status;
- question and document counts;
- last saved/updated time;
- one state-aware primary action.

Provide search and module/status filters. Keep **Create questionnaire** prominent for authorized users.

### Questionnaire overview

The overview summarizes the current working pair:

- title and module;
- Draft, In review, Published to sandbox, or Retired;
- question, section, and document counts;
- last confirmed save;
- **Continue editing** as the primary action when editable;
- Preview/test, Check configuration, and Send for approval as stage-appropriate actions;
- version history in a secondary disclosure.

If more than one eligible draft form or document version exists, show a human-readable pair-selection step containing questionnaire name, state, creator/update time, and version as secondary information. Do not auto-select unless there is exactly one eligible item in each list.

### Technical screens

Raw form-version and requirement-version resources remain policy-protected support screens. Ordinary workflow navigation should not require them.

## 7. Existing components and services to reuse

| Existing capability | Reuse decision |
| --- | --- |
| `QuestionnaireAuthoringService` | Remains the only draft persistence path, including validation, transaction, and lock checks. |
| `ConfigurationValidator` | Remains the authoritative Review-stage checker. |
| `QuestionnairePublicationService` | Remains the exact-pair review submission and lifecycle path. |
| `ConditionalLogic` | Continues to drive author preview and applicant visibility. |
| `questionnaires.field` | Shared renderer for preview and applicant portal controls. |
| `questionnaires.requirement` | Shared documentary-requirement preview. |
| Form Builder save-error mapping | Preserve field errors, summary, dirty-state warning, and local input. |
| Filament notifications and confirmation dialogs | Use for brief success and consequential actions; blocking failures remain on-page. |
| Shared design tokens and theme modes | Correct and extend instead of introducing another styling system. |
| Existing policies and permissions | Retain route/action checks; UI visibility remains presentation only. |

No new frontend framework or drag-and-drop dependency is proposed.

## 8. Security and lifecycle risks

| Risk | Control |
| --- | --- |
| Array-index selection edits the wrong question after reordering. | Store selected stable keys and resolve the current array index server-side for every action. |
| New focused actions bypass domain services. | All saves, checks, submissions, clones, and lifecycle changes call existing services. |
| Preview becomes an applicant data path. | Keep answers in transient component state; test database counts and prohibit submit/evidence actions. |
| A stale browser silently overwrites another editor. | Preserve form and requirement lock versions and conflict handling. |
| UI stage change implies workflow-state change. | Treat stage as presentation state only; persisted lifecycle remains draft/in-review/published/retired. |
| Exact draft pairing is obscured by simplified labels. | Show technical IDs/versions in secondary details and require explicit pair selection when ambiguous. |
| Unauthorized direct calls reach hidden actions. | Test direct routes and forged Livewire actions for OSDS, Admin, CHEDRO, SCHOOL, inactive users, and revoked permissions. |
| Published content becomes editable through focused controls. | Render read-only state and preserve service plus PostgreSQL immutability enforcement. |
| Error links disclose internal identifiers. | Use stable keys only within authorized component markup; keep restricted records behind 404/403 policies. |

## 9. Implementation work packages

### FB1 — Contrast and rendering foundation

- Repair token-name mismatch and remove duplicate imports.
- Normalize hardcoded light-only form/status colors.
- Normalize mojibake strings in affected builder/workspace views.
- Verify shared input and preview boundaries in both themes.

**Gate:** computed contrast targets pass for representative components; Phase 2–3B and Form Maker tests remain green.

### FB2 — Focused builder state and outline

- Add stable-key selection state and resolver methods.
- Build accessible section/question outline.
- Render only one selected question editor.
- Add safe add, duplicate, delete, and move interactions.
- Preserve save-error anchors and mark invalid outline items.

**Gate:** all question operations persist through the existing authoring service; concurrent and immutable writes still fail safely.

### FB3 — Four-stage navigation and document editor

- Implement the single four-stage workflow.
- Build a focused document-requirement outline/editor using the same pattern.
- Move validation to Review and test and exact submission to Send for approval.
- Keep one primary action per stage.

**Gate:** the user can traverse all stages without duplicate indicators or technical resource pages, and exact pairing remains unchanged.

### FB4 — Optional synchronized preview

- Add show/hide, full preview, and temporary test modes.
- Reuse the shared renderer and synchronize selected visible questions.
- Remove nested scroll behavior and handle narrow screens/zoom.

**Gate:** preview/test writes no applicant data, conditions match the applicant renderer, and direct actions remain authorized.

### FB5 — Questionnaire overview and workspace simplification

- Replace the redundant module workspace journey with questionnaire discovery and a concise overview.
- Add counts, last saved state, lifecycle guidance, and one state-aware primary action.
- Keep explicit human-readable draft-pair selection when ambiguous.
- Move version history and support identifiers into secondary details.

**Gate:** users can open the correct draft without silently pairing versions; all existing review/publication behavior passes.

### FB6 — Usability, accessibility, and regression verification

- Run automated tests below and the prepared first-time OSDS script.
- Verify desktop, tablet, 320px mobile, 200% zoom, keyboard, reduced motion, and Light/Dark/System.
- Record actual staff usability testing as pending until conducted.
- Update the staff guide and completion report.

## 10. Acceptance tests

### Automated

- Opening a builder selects a deterministic first question without mutating data.
- Selecting, adding, duplicating, deleting, and moving questions uses stable keys and preserves conditions/choices.
- Validation errors mark the correct outline item and focus/link to its editor.
- Valid saves commit both exact versions and update lock versions only after commit.
- Invalid, stale, in-review, and published saves preserve input and do not partially persist.
- Preview and test mode create no applicant draft, answer, submission, or evidence records.
- Conditional behavior matches the shared renderer for every supported type.
- Multiple draft pairs require explicit selection; forged pairs and cross-module pairs fail.
- OSDS access succeeds only with explicit permission; Admin, CHEDRO, SCHOOL, inactive, and revoked users remain denied where required.
- Existing Phase 2, 3A, 3B, Form Maker, publication, audit, and applicant suites remain passing.
- Pint, production Vite build, and `git diff --check` pass.

### Manual first-time OSDS script

1. From Questionnaires, find and open ACSA Application.
2. Identify the page location, draft status, and next action without assistance.
3. Locate **Organization type** through the outline.
4. Edit its title and required state.
5. Add a question, choose its type, and configure choices.
6. Move and duplicate a question, then delete the duplicate.
7. Open Preview and test a conditional answer without saving test data.
8. Save, leave, and resume the same draft.
9. Add and correct a documentary requirement.
10. Check configuration and navigate to each reported problem.
11. Send the exact draft pair for Admin review.

Record completion, elapsed time, unnecessary clicks, page/inner scrolling, navigation mistakes, unclear labels, assistance required, and suggested improvements. Repeat at desktop, tablet, mobile, and 200% zoom where practical.

## 11. Approval boundary

No code, schema, permissions, or styling changes are authorized by this plan. The implementation should proceed sequentially from FB1 through FB6 only after approval, stopping at any failed authorization, migration, lifecycle, or PostgreSQL regression gate. No database migration is currently proposed. Phase 3C remains unstarted.
