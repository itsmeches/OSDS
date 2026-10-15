# Form Builder UX overhaul and accessibility remediation plan

**Status:** Proposed for review; implementation is not authorized
**Date:** 15 October 2026
**Scope:** Form Builder usability, platform contrast remediation, questionnaire discovery and creation, and future module governance

This plan responds to the current Form Builder implementation and the supplied reference layout. It proposes no Phase 3C work, evidence intake, official submission, certificate workflow, or production publication capability.

## 1. Executive assessment

The current builder contains the required editing and preview capabilities, but presents too many controls and too much content at once. The default split view competes with the staff navigation for horizontal space, the preview adds a nested scrollbar, and the four-step workflow separates testing from review even though testing is a review activity.

The most urgent defect is a design-token mismatch. The builder stylesheet requests variables such as `--osds-text`, `--osds-muted`, `--osds-border`, and `--osds-primary`, while the design system defines `--osds-color-text`, `--osds-color-muted`, `--osds-color-border`, and related `--osds-color-*` names. Invalid computed colors explain the faint stage numbers, weak controls, and unreliable light/dark rendering visible in the screenshot.

The recommended builder has three stages:

1. **Build form**
2. **Required documents**
3. **Review and send**

Editing occupies the full working area by default. **Preview** opens a collapsible side panel on sufficiently wide screens and a full-width view on smaller screens. **Test form** becomes an option inside Preview and continues to keep all test answers temporary.

## 2. Current-state audit

| Priority | Finding and root cause | User impact | Proposed correction |
| --- | --- | --- | --- |
| P0 | Builder CSS uses undefined short token names while the shared token file defines `--osds-color-*` variables. | Text, borders, steps, cards, and states can become faint or inherit unsuitable colors, particularly in dark mode. | Use one documented semantic token vocabulary throughout; remove the duplicate token import; add safe component-level fallbacks only where necessary. |
| P0 | Shared form and alert styles contain hardcoded light surfaces and status colors. | Inputs, warnings, errors, disabled controls, and placeholders can fail contrast or appear inconsistent in dark mode. | Replace hardcoded presentation colors with light/dark semantic tokens for canvas, surface, text, muted text, border, focus, disabled, success, warning, and danger. |
| P0 | The current default combines a 17-rem staff sidebar, a 55/45 editor split, and a separately scrolling sticky preview. | Titles truncate, editing cards become narrow, the preview badge clips, and users encounter nested scrolling. | Default to full-width editing. Collapse the staff sidebar when entering the builder, while retaining a visible control to reopen it. Open preview only on request and avoid an independently scrolling page-height panel. |
| P1 | Save, Edit and Preview, Preview only, Test form, Check form, and Save draft compete equally in the header. | The primary next action is unclear and first-time users must understand several modes. | Retain one prominent context action, place Preview beside it, and move Test form into Preview. Place validation in the Review stage. |
| P1 | Four workflow stages expose implementation steps: Build, documents, test/validate, and send. | Testing appears to be a separate deliverable and increases navigation choices. | Use Build form, Required documents, and Review and send. Review contains validation, applicant preview, test mode, and exact-pair submission. |
| P1 | Question cards expose editing detail and technical settings together. | Long forms are difficult to scan and common edits require navigating dense cards. | Show compact question summaries by default. Open one question editor at a time and progressively disclose choices, validation, and conditions. Keep stable keys hidden. |
| P1 | Status and readiness are spread across badges and actions. | A user can have difficulty knowing whether changes are saved and why review submission is unavailable. | Add a single persistent save-state indicator and a review checklist that distinguishes unsaved, invalid, ready, in-review, and immutable states. |
| P2 | The Questionnaires landing page is module-oriented and exposes **Create questionnaire** only after selecting a module. | Users cannot immediately find or create a questionnaire and may confuse modules with questionnaires. | Make questionnaires the primary list. Add a prominent create action, search, module/status filters, updated time, owner, and clear next action. |
| P2 | Existing permissions allow draft management but do not distinguish editing from creating a new questionnaire definition. | A staff member able to edit drafts can also create a new questionnaire structure. | Add an explicit `questionnaires.create` permission and enforce it in the domain service and UI. Keep editing under `questionnaires.draft.manage`. |
| P3 | The database permits multiple form definitions per module, but requirement-set versions belong only to a module. | As questionnaires grow, documentary-requirement drafts can be paired with the wrong questionnaire unless users inspect exact versions. | Keep exact explicit pairing in the near term. Before production expansion, consider binding requirement-set versions to a form definition through a separately approved migration. |
| P3 | Modules have no formal proposal/approval lifecycle for introducing a new service. | Allowing ordinary questionnaire creation to create modules would bypass governance. | Treat new-module creation as a distinct Admin governance workflow with separate permissions, review, and audit. Do not include it in this UX implementation without separate approval. |

## 3. Proposed Form Builder experience

### Page structure

```text
Back to Questionnaires
Questionnaire title                         Saved   Preview   Save draft
Module · Questionnaire v2 · Documents v2

[1 Build form] [2 Required documents] [3 Review and send]

Full-width stage workspace
```

- Preserve the full questionnaire title and version context instead of truncating them to fit header actions.
- Keep one emphasized action for the current stage. Secondary actions remain visible but quieter.
- Show the staff sidebar collapsed on builder routes at desktop widths; its reopen control remains keyboard accessible.
- Use the existing design foundation and Light, Dark, and System modes.

### Stage 1: Build form

- Show sections as clear containers with a concise description and reorder controls.
- Show questions as compact summary cards containing label, type, required state, and condition summary.
- Open one card for editing question text, help text, type, required state, and choices.
- Reveal validation and display conditions under **Advanced settings**.
- Preserve stable machine keys automatically when labels change.
- Provide **Add question** within a section and **Add section** after the section list.
- Show explicit Unsaved, Saving, Saved, Save failed, and Conflict states based on server-confirmed persistence.

### Stage 2: Required documents

- Use the same compact-card pattern for each documentary requirement.
- Prioritize title, required/optional, accepted evidence types, and evidence count.
- Place applicability conditions in progressively disclosed settings.
- Explain that this configuration defines future evidence requirements and does not collect documents in this checkpoint.

### Stage 3: Review and send

- Display the exact questionnaire and document-requirement versions being reviewed.
- Run the authoritative backend validator against the last confirmed saved state.
- Present errors by section/question/requirement with direct links back to the affected editor.
- Show a representative applicant preview and optional temporary test mode.
- Enable **Send for review** only when the exact pair is saved, unchanged, and valid.
- Preserve the current review, hash, immutability, authorization, and audit services.

### Preview behavior

- **Wide screens:** Preview toggles a resizable or collapsible side panel. It does not open by default.
- **Narrow screens and 200% zoom:** Preview becomes a full-width mode with a clear return-to-edit action.
- **Preview only:** Available as a full-page inspection view.
- **Test form:** A control inside preview enables temporary answers and conditional-behavior checks. Closing or refreshing discards them and creates no applicant or draft records.
- The preview and applicant portal continue to use the shared questionnaire renderer so question types and conditional logic remain consistent.

## 4. Platform contrast remediation

### Token strategy

Retain the existing `--osds-color-*` vocabulary as the source of truth and extend it only where a semantic role is missing:

- page canvas, primary surface, elevated surface;
- primary and secondary text;
- standard and strong borders;
- input background, placeholder, disabled background/text;
- focus ring;
- success, warning, danger, and information foreground/background/border pairs.

Builder and applicant styles must consume these shared variables. Component styles must not depend on an undefined alias. Light and dark values will be reviewed as pairs rather than assuming that browser inversion or inheritance is sufficient.

### Components included in the P0 audit

- Navigation, page headings, breadcrumbs, cards, dividers, and status badges.
- Text inputs, textareas, selects, checkboxes, radios, placeholders, help text, validation messages, disabled and read-only controls.
- Buttons, icon buttons, focus rings, links, modals, notifications, tables, and empty states.
- Builder stages, save-state labels, question cards, preview controls, document cards, and review checklist.
- Applicant portal shell, progress rail, draft editor, warning/error messages, and both themes.

Acceptance targets are WCAG 2.2 AA contrast practices: at least 4.5:1 for normal text, 3:1 for large text and meaningful graphical/control boundaries, plus visible keyboard focus. Results will be documented as an accessibility review rather than a claim of full conformance.

## 5. Questionnaire landing page and creation flow

### Landing page

The page will list questionnaire definitions that the current user is authorized to view, rather than requiring the user to start from a module card.

Each row or card shows:

- questionnaire name;
- module/service;
- latest lifecycle state;
- questionnaire and documents version labels;
- owner and last update;
- one clear next action such as **Continue editing**, **View review**, or **Open published version**.

Provide:

- a prominent **Create questionnaire** action;
- search by questionnaire name or stable identifier;
- filters for module and lifecycle state;
- plain empty states and permission-aware actions;
- responsive cards at narrow widths and a concise table on larger screens.

### Create questionnaire under an existing module

The creation dialog asks for:

1. an existing authorized module;
2. questionnaire title;
3. an optional description only if storage for it is separately approved.

The backend transaction creates a form definition, first form draft, initial section, and matching documentary-requirements draft. It records safe audit metadata and returns the new builder route. Generated stable identifiers remain hidden from ordinary users.

The current schema already supports multiple questionnaire definitions under one module. Creation must remain distinct from creating a new module and must call a domain service protected by the proposed `questionnaires.create` permission.

### Future module/service creation

Creating a new OSDS service has broader policy consequences than adding a questionnaire to Registration or ACSA. It should eventually use a separate workflow:

1. authorized Admin creates a module proposal;
2. name, purpose, owner, applicant audience, and governance reference are recorded;
3. another authorized action approves and activates it;
4. questionnaire authors may then create drafts under it.

This requires a distinct permission and likely lifecycle data not present in the current `modules` table. It remains a P3 design proposal and will not be implemented as part of this UX change without separate approval.

## 6. Schema and authorization implications

### Changes that do not require a schema migration

- Fixing tokens, contrast, layout, preview behavior, labels, navigation, and progressive disclosure.
- Listing existing questionnaire definitions with search and filters.
- Creating a questionnaire with its current stored fields.
- Adding permission definitions and provisional role assignments through the existing permission infrastructure.
- Retaining explicit questionnaire/document version pairing at review submission.

### Changes requiring separate migration approval

1. **Questionnaire description:** `form_definitions` currently has no description field. The initial creation flow can omit it, or a nullable description column can be proposed separately.
2. **Requirement ownership:** `requirement_set_versions` is module-scoped. Adding `form_definition_id` would make documentary requirements unambiguously belong to one questionnaire. A safe migration needs a reviewed backfill for existing records and must preserve publication manifests.
3. **Module governance:** formal proposed/active/retired states, approval references, and ownership metadata would require new columns or governance records.

No migration is proposed for P0 or P1. P2 can proceed without a migration by retaining title-only creation and exact pairing. The requirement-ownership migration should be decided before many questionnaires are created under one module.

### Authorization boundaries

- `questionnaires.view`: discover authorized questionnaires.
- Proposed `questionnaires.create`: create a definition and its first paired drafts under an existing active module.
- `questionnaires.draft.manage`: edit and save mutable drafts.
- Existing review, approval, publication, preview, and retirement permissions remain unchanged.
- A future module-governance permission must remain separate from questionnaire creation.
- Every route and Livewire action rechecks active account and explicit permission; navigation visibility is presentation only.
- SCHOOL and CHEDRO users remain unable to access the builder or technical questionnaire routes in this work.

## 7. Prioritized implementation plan

### P0 — Restore readable, reliable presentation

**Primary files:**

- `resources/css/theme/tokens.css`
- `resources/css/filament/admin/theme.css`
- `resources/css/app.css`
- relevant shared Blade/Livewire components

**Work:**

1. Replace undefined builder token references with shared semantic tokens and remove the duplicate import.
2. Inventory hardcoded light-only input, alert, placeholder, disabled, and state colors.
3. Define missing light/dark semantic pairs and update staff and applicant components.
4. Verify focus visibility and no color-only status communication.

**Security/domain impact:** None; presentation only.

**Gate:** Both themes remain readable across staff and applicant screens, browser console/CSS checks show no missing project tokens, and existing authorization/domain regression tests pass.

### P1 — Simplify the Form Builder

**Primary files:**

- `resources/views/filament/pages/form-builder.blade.php`
- `app/Filament/Pages/FormBuilder.php`
- builder presentation components and shared renderer views
- `resources/css/filament/admin/theme.css`

**Work:**

1. Replace four stages with three and move testing/validation into Review and send.
2. Make editing full width by default; implement an on-demand responsive preview.
3. Collapse the staff sidebar on builder entry without removing user control.
4. Convert questions and requirements to compact summaries with one-at-a-time editing and progressive advanced settings.
5. Keep one prominent primary action and accurate save/readiness states.
6. Preserve exact review pairing and all domain service calls.

**Security/domain impact:** No lifecycle changes. Add forged Livewire/direct-action tests to ensure the rearranged UI cannot bypass permissions, save validation, review hashing, or immutability.

**Gate:** A first-time OSDS tester can build, preview/test, validate, and send the exact saved pair without using technical resource pages or encountering nested horizontal/page-height scroll at supported widths.

### P2 — Questionnaire discovery and safe creation

**Primary files:**

- `app/Filament/Pages/Questionnaires.php`
- its Blade view and UI tests
- `app/Domain/Questionnaires/QuestionnaireDraftService.php`
- permission seeder/configuration and policies

**Work:**

1. List questionnaire definitions with search, module/status filters, owner, update time, and next action.
2. Move **Create questionnaire** to the page header.
3. Add explicit existing-module selection and title entry; never silently select a module or draft pair.
4. Introduce and enforce `questionnaires.create` separately from draft editing.
5. Create the initial questionnaire/document drafts transactionally and audit the action.
6. Keep description out of the first release unless its migration is approved.

**Security/domain impact:** Enforce active account, create permission, authorized module access, generated stable keys, transaction rollback, and rate/duplicate protection. Do not grant new-module authority.

**Gate:** Authorized OSDS staff can find all permitted questionnaires and create a draft under an existing module; users without create permission can still perform only their authorized existing-draft actions.

### P3 — Production scalability and module governance proposals

**Work requiring separate approval:**

1. Decide whether to bind requirement-set versions to a questionnaire definition and approve a forward-only migration/backfill.
2. Decide whether questionnaire descriptions are required and approve storage.
3. Design module proposal, approval, activation, and retirement governance with independent permissions and audit.
4. Confirm ownership and authorized roles before exposing any new-module action.

**Gate:** Stakeholders approve the data ownership model, migration/backfill, permissions, and governance process before implementation.

## 8. Verification strategy

### Automated checks

- Existing Phase 2, 3A, 3B, and Form Maker regression suites on PostgreSQL.
- Form Builder authorization, direct route, forged Livewire, stale save, conflict, immutable publication, and exact-pair tests.
- Preview/test verification proving no applicant draft, answer, submission, or audit payload containing answers is created.
- Questionnaire search/filter/create permission and transaction rollback tests.
- A token-contrast test for approved foreground/background pairs, supplemented by browser inspection.
- Laravel Pint, production frontend build, and `git diff --check`.

### Responsive and accessibility review

- Light, Dark, and System themes.
- Keyboard-only operation, focus order, modal focus, accessible names, error announcements, and unsaved-state announcements.
- 320-pixel viewport, tablet, standard desktop, wide desktop, and 200% zoom.
- Reduced motion and browser high-contrast/forced-color review where available.
- Long questionnaire titles, many sections, long choices, and validation errors.

### First-time OSDS usability scenario

1. Find an existing questionnaire.
2. Create a new questionnaire under an existing authorized module.
3. Add a section and two question types.
4. Add a documentary requirement.
5. Preview and test a conditional question.
6. Correct a validation error.
7. Save and send the exact pair for review.

Record task completion, navigation errors, unclear terms, assistance required, and suggested improvements. Actual staff testing remains pending until conducted.

## 9. Decisions requested before implementation

1. Approve P0 and P1 as migration-free remediation.
2. Approve P2 with a separate `questionnaires.create` permission and title-only creation under existing modules.
3. Decide whether the requirement-set ownership migration should occur before multiple questionnaires are used within one module.
4. Confirm that new module/service creation remains deferred to a separately approved governance design.
5. Confirm automatic sidebar collapse on the builder route, with an accessible reopen control.

## 10. Approval boundary

No implementation is authorized by this document. After approval, work should proceed P0, P1, then P2 with focused PostgreSQL, authorization, accessibility, and frontend-build gates after each package. P3 remains a separate decision. Phase 3C remains unstarted.
