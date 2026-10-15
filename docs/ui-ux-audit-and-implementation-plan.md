# UI/UX audit and proposed implementation plan

**Status:** Approved and implemented as the pre-Phase 3B UI/UX checkpoint
**Date:** 14 October 2026
**Scope:** UI/UX standardization after Phase 3A and before Phase 3B

This document records the approved presentation and navigation direction. The implementation did not change the Phase 2 authorization model, change the Phase 3A publication lifecycle, expose questionnaires to applicants, or begin Phase 3B.

## 1. Executive assessment

The application has a sound security and domain foundation, but the current interface closely mirrors its database structure. Staff must understand modules, form definitions, form versions, requirement-set versions, review requests, and publication manifests before they can complete a single questionnaire task. The review and publication tables also place important actions at the far edge of wide tables, which caused the observed horizontal-scrolling problem.

The recommended change is to retain the existing resources as secured technical screens while introducing two task-based workspaces:

- **Questionnaires** for OSDS drafting, document requirements, preview, and review submission.
- **Questionnaire approvals** for Admin inspection, decision, sandbox publication, and publication history.

This provides a simpler path without changing schema, permissions, hashes, publication manifests, or domain services.

## 2. Current UI/UX audit

### High priority

| Finding | Evidence | User impact | Proposed response |
| --- | --- | --- | --- |
| Navigation exposes implementation concepts | Seven questionnaire resources appear under **Questionnaire configuration**, including Form definitions, Form versions, Requirement set versions, and Publication manifests. | Staff must learn the data model before completing routine work. | Add task-based questionnaire and approval workspaces. Hide low-level resources from ordinary navigation while keeping their routes policy-protected. |
| Review actions are difficult to discover | Approve, Reject, and Publish are row actions after six columns, including a hash. The table can overflow horizontally. | Admin users may miss the next action or believe that approval is the end of the workflow. | Make pending work the primary view, move decisions to a review page or slide-over, keep the primary action visible, and place technical metadata in a secondary details section. |
| Admin cannot inspect the complete proposal from the review request | The review table shows version numbers and a truncated hash but no questionnaire/document-requirement inspection action before approval. | The reviewer cannot make an informed decision within one coherent workflow. | Add a read-only review view containing sections, questions, choices, conditions, requirements, version identifiers, and approval hash. |
| Drafting is split across unrelated screens | Form content and documentary requirements are edited through separate resources, then manually paired during review submission. | Pairing errors and training burden are likely. | Present one module workspace with **Questions**, **Document requirements**, **Preview**, and **Review** steps while retaining separate version records internally. |
| Important lifecycle transitions lack sufficient confirmation/context | Submit, Approve, Reject, Publish, Clone, and Retire are terse row actions. Retirement has no explicit confirmation text. | Users can act without understanding consequences, especially immutability after publication. | Use confirmation dialogs with outcome-specific descriptions and clear success messages. |
| The default dashboard provides little direction | The standard Filament dashboard contains only the account widget. | Users do not know what needs attention or where to start. | Add permission-aware task cards and counts for existing features only: drafts, pending reviews, and sandbox publications. |

### Medium priority

| Finding | Evidence | User impact | Proposed response |
| --- | --- | --- | --- |
| Labels are technical or inconsistent | Examples include Requirement set versions, Publication manifests, configuration hash, `review_sandbox`, keys, and evidence-provider codes. | Ordinary users may misinterpret fields and statuses. | Display **Document requirements**, **Published questionnaires**, **Configuration reference**, and **Review sandbox**. Preserve internal names and identifiers in code/database. |
| Page purpose and next steps are not explained | Resource list and edit pages use default headings and have few descriptions or empty-state instructions. | First-time users rely on verbal training. | Add concise page descriptions, empty-state headings, and next-step text. |
| Status presentation uses raw values | Badges show values such as `in_review` and `review_sandbox`. | Labels are less readable, and color alone can carry too much meaning. | Centralize status labels, colors, and icons; show **In review** and **Review sandbox** with text plus icon. |
| Technical hashes occupy primary table space | Hashes are truncated and copyable in several list tables. | Tables become wider while the value has little daily operational meaning. | Move hashes to expandable technical details and retain copy access for authorized troubleshooting. |
| Forms are dense | Nested repeaters place keys, types, labels, validation JSON, conditions, and choices in a two-column layout. | Long forms are tiring to scan and hard to use on narrow screens. | Group basic content, response settings, choices, and display conditions into collapsible sections; use full width where needed. |
| Validation JSON is exposed directly | Questionnaire authors edit raw JSON for validation rules. | Syntax errors are likely and nontechnical users cannot confidently configure rules. | Replace common validation settings with guided fields. Keep an advanced representation only if genuinely required. |
| Conditional references are selected through a global key datalist | The field queries distinct question keys across configurations. | Authors can select an invalid or irrelevant key and discover the error only at review submission. | Limit choices to eligible earlier questions from the current form version and show question labels with stable keys as secondary text. |
| Questionnaire preview uses placeholders | Sandbox renders “Short text preview” and similar blocks rather than representative disabled controls. | Reviewers cannot fully judge the applicant experience. | Render accessible disabled/read-only controls and choices without persisting responses. |
| Dates and versions lack contextual wording | Tables show raw version numbers and timestamps without consistent relative/absolute treatment. | Users may confuse question and requirement versions. | Use labels such as **Questionnaire v1**, **Documents v1**, plus a consistent Manila-time date format and exact timestamp tooltip. |

### Lower priority and foundation observations

| Finding | Evidence | Proposed response |
| --- | --- | --- |
| Icons for several core resources all use the same rectangle-stack icon | Users cannot distinguish destinations quickly. | Assign icons by task and use a small consistent icon vocabulary. |
| The landing page and sandbox contain mojibake characters in the inspected source/output | Separators and dashes display as `Â·`, `â€“`, or `â€”` in some environments. | Normalize source files to UTF-8 and add rendering checks. |
| Styling exists separately in Blade while Filament mainly uses defaults | The public foundation and staff workspace do not yet share tokens. | Introduce shared CSS custom properties and a Filament theme that consumes them. |
| No explicit reduced-motion or enhanced focus treatment is configured | Filament defaults help, but project-specific verification is absent. | Add conservative focus and reduced-motion rules and test them. |
| Tables have minimal search/filter support and no tailored empty states | Finding records takes more effort as data grows. | Add only task-relevant search, status/module filters, default sorting, and plain-language empty states. |

### Existing strengths to preserve

- Filament supplies responsive navigation, accessible component semantics, validation, notifications, and consistent controls.
- Navigation is already constrained by policies and permissions.
- UI actions call domain services for review, publication, cloning, and retirement.
- Phase 3A separates draft, review, approval, sandbox publication, and immutable history.
- The sandbox route requires authentication, an active account, and explicit permission.
- The public landing page correctly states that applicant submission is unavailable.

## 3. Proposed navigation by role

Navigation remains permission-driven. A role label alone will not grant visibility or access.

### OSDS

1. **Dashboard**
2. **Questionnaires**
   - Registration and ACSA questionnaire workspaces
   - Draft and review status
   - Submissions for review made by authorized users

### Admin

1. **Dashboard**
2. **Questionnaire approvals**
   - Pending reviews first
   - Decided review history
3. **Published questionnaires**
   - Active and retired sandbox publications
4. **User and access management**
   - Users
   - Regions
   - Institutions
   - Institution representatives
   - Regional assignments
5. **Audit history**, when explicitly permitted

### Super User

Show only screens allowed by explicit permissions. Do not infer questionnaire publication access from the account type.

### CHEDRO

Show the dashboard and currently authorized Phase 2 directory/account functions only. Do not display future application or evidence-review links.

### SCHOOL

No staff questionnaire administration or sandbox navigation. Phase 3B applicant navigation is excluded from this checkpoint.

### Technical administration

Modules, form definitions, evidence provider types, raw version resources, and manifests remain protected routes for support or troubleshooting when authorized. They are removed from ordinary navigation once their functions are available through the task workspaces.

## 4. Design system foundation

### Provisional design tokens

Tokens must remain centrally replaceable when approved CHED brand specifications are supplied.

| Category | Proposed convention |
| --- | --- |
| Primary | Navy/blue scale; primary actions use a high-contrast blue with white text |
| Background | Neutral light or dark canvas with clearly separated content surfaces; follow the selected Light, Dark, or System preference |
| Text | High-contrast primary and secondary text for each theme; never low-contrast gray for required instructions |
| Status | Blue information, green success, amber warning/pending, red danger; always pair color with text/icon |
| Typography | System sans-serif; 16 px base where layout permits; clear heading scale and comfortable line height |
| Spacing | 4 px base scale with consistent 8/12/16/24/32 px composition |
| Radius | Moderate, consistent radius; avoid mixing sharp and highly rounded components |
| Elevation | Borders for normal grouping; subtle shadow only for overlays and prominent cards |
| Motion | Short functional transitions; honor `prefers-reduced-motion` |
| Focus | Visible two-pixel focus indicator with adequate contrast and offset |

### Component conventions

- Each page has one plain-language heading, a one-sentence purpose, and at most one emphasized primary action.
- Primary actions describe progress: **Save draft**, **Preview**, **Submit for review**, **Approve**, **Publish to review sandbox**.
- Secondary and technical actions use lower emphasis. Destructive actions require a reason when the domain requires one and a consequence-specific confirmation.
- Status badges use centralized human-readable labels, icon, and color.
- Tables prioritize name, module, status, updated/submitted time, and the next available action. IDs and hashes appear in technical details.
- Empty states explain why the list is empty and the next permitted action.
- Validation appears beside the relevant field and is summarized at the action boundary when publication validation spans multiple records.
- Published content is visibly read-only and explains that changes require cloning a new draft.
- Required fields use text and semantics in addition to an asterisk.
- Timestamps show Asia/Manila display time consistently, with full exact values available.

### Accessibility baseline

- Preserve semantic Filament controls instead of replacing them with custom div-based widgets.
- Verify keyboard reachability, logical tab order, visible focus, modal focus trapping, headings, labels, errors, and table action names.
- Maintain WCAG 2.2 AA contrast targets for text and interactive states.
- Provide labels for icon-only actions and do not encode status by color alone.
- Test 200% zoom, narrow viewport reflow, reduced motion, and screen-reader names for primary workflows.
- Record results as an accessibility review; do not claim formal WCAG conformance without a suitable assessment.

## 5. Revised Phase 3A workflows

### OSDS questionnaire workspace

```text
Questionnaires
  → Select Registration or ACSA
  → Overview: current draft, review state, and sandbox publication
  → Questions: sections, questions, choices, conditions
  → Document requirements: evidence counts, providers, conditions
  → Preview: combined read-only experience
  → Review: validation summary and Submit for review
```

Design details:

- Module cards show one clear status and next action.
- Tabs or a step indicator keep the user within one workspace.
- Draft form and requirement versions are automatically scoped to the selected module.
- Submission still pairs exact existing records through `QuestionnairePublicationService`.
- If multiple eligible drafts exist, the interface asks the user to select them explicitly and explains the choice.
- Published versions remain immutable; **Create new draft** invokes the existing clone services.

### Admin approval workspace

```text
Questionnaire approvals
  → Pending request
  → Inspect exact questions and document requirements
  → Review configuration reference and validation result
  → Approve or reject with reference/feedback
  → Publish approved version to review sandbox
  → Open preview or publication history
```

Design details:

- Pending items are the default view; history remains available as a filter/tab.
- Approve and Reject stay visible without horizontal scrolling.
- Approval and publication remain distinct actions and retain current permissions.
- The approval screen displays exactly the versions and hash bound to the request.
- Publishing explains that it creates a staff-only synthetic preview and does not make an applicant form live.

## 6. Proposed implementation plan

### Work package A: Shared UI foundation

1. Add shared CSS tokens and an approved Filament theme entry point.
2. Configure provisional brand name, primary palette, content width, typography, focus, and reduced-motion behavior through Filament/Tailwind conventions.
3. Add centralized helpers/enums for lifecycle labels, badge colors/icons, version labels, and Manila-time presentation.
4. Correct text encoding issues in current Blade views.

Likely files:

- `app/Providers/Filament/AdminPanelProvider.php`
- `resources/css/app.css`
- new `resources/css/theme/tokens.css`
- new Filament theme stylesheet under `resources/css/filament/admin/`
- a small presentation support class under `app/Support/`
- `resources/views/welcome.blade.php`
- `resources/views/questionnaires/sandbox.blade.php`
- `vite.config.js`

### Work package B: Navigation and dashboard

1. Add explicit resource labels, groups, icons, sort order, and permission-aware visibility.
2. Introduce a task-based dashboard with counts and links for implemented functionality only.
3. Remove technical questionnaire resources from ordinary navigation after the new workspaces cover their tasks.
4. Keep every route protected by existing policies and permissions.

Likely files:

- `app/Providers/Filament/AdminPanelProvider.php`
- new dashboard page/widgets in `app/Filament/Pages/` and `app/Filament/Widgets/`
- existing resource classes for labels/navigation visibility

### Work package C: OSDS questionnaire workspace

1. Add a **Questionnaires** page listing Registration and ACSA with lifecycle summaries.
2. Add a module-scoped workspace using tabs or steps for overview, questions, document requirements, preview, and review.
3. Reuse existing Filament schemas where practical; extract repeated editor schemas into reusable schema builders.
4. Add guided validation fields, context-scoped condition selectors, clear empty states, unsaved-change protection, and consequence-specific confirmations.
5. Invoke only `QuestionnaireDraftService` and `QuestionnairePublicationService` for lifecycle actions.

Likely files:

- new pages under `app/Filament/Pages/Questionnaires/`
- reusable schemas/components under `app/Filament/Forms/` or `app/Filament/Schemas/`
- existing FormVersion and RequirementSetVersion resources for extracted schema reuse and fallback technical access
- no migration expected

### Work package D: Admin approval and publication workspace

1. Replace the wide review list as the primary workflow with a concise pending-review queue.
2. Add a read-only inspection page or slide-over showing the exact paired configuration.
3. Keep Approve, Reject, and Publish visible and clearly separated.
4. Add publication history with plain-language states and place hashes in technical details.
5. Add confirmations and descriptions for publication and retirement.

Likely files:

- `app/Filament/Resources/ConfigurationReviewRequests/`
- `app/Filament/Resources/PublicationManifests/`
- possibly new review display components/views
- existing publication domain service remains unchanged

### Work package E: Guidance, responsiveness, and verification

1. Add page descriptions, tooltips only when needed, validation summaries, and task-specific empty states.
2. Test desktop, tablet, narrow mobile, keyboard-only operation, 200% zoom, focus order, modal focus, contrast, reduced motion, and screen-reader names.
3. Run the listed OSDS/Admin/unauthorized acceptance scenarios.
4. Update automated policy/authorization tests where new UI routes are introduced.
5. Run the full PostgreSQL suite, Pint, production build, and `git diff --check`.

## 7. Risks and compatibility considerations

| Risk | Control |
| --- | --- |
| A unified UI could accidentally bypass domain lifecycle controls | All state-changing actions continue to call existing domain services; add direct-route and forged Livewire action tests. |
| Hiding technical navigation could be mistaken for authorization | Preserve policies, middleware, and service authorization; test direct URLs for CHEDRO and SCHOOL. |
| Reusing nested Filament repeaters may remain difficult on mobile | Use collapsible cards, single-column narrow layouts, sticky/contextual page actions where supported, and test representative long content. |
| Custom styling could reduce Filament accessibility | Prefer theme tokens and framework components; keep overrides small and test focus, contrast, zoom, and reduced motion. |
| Task pages may duplicate resource logic | Extract shared schema and query presentation components; retain one domain action path. |
| Draft pairing could become ambiguous | Display and validate exact form/document versions; do not choose silently when multiple candidates exist. |
| Plain-language labels could hide audit-critical detail | Keep versions, public IDs, hashes, and timestamps in an expandable technical details section. |
| Provisional branding may be mistaken for approved CHED branding | Mark tokens as provisional in documentation and centralize them for replacement. |
| UI work could drift into Phase 3B | Do not add SCHOOL access, response persistence, evidence references, applicant routes, autosave, or submissions. |

## 8. Acceptance criteria

### OSDS usability

- An authorized OSDS user can find Registration or ACSA from **Questionnaires** without visiting a technical resource list.
- The user can edit an existing draft, add/reorder a question, configure a document requirement, preview the combined configuration, and submit the exact pair for review.
- The interface states why published content is read-only and offers a permitted clone action.
- Validation failures identify the affected section/question/requirement in plain language.

### Admin usability

- An authorized Admin sees pending questionnaire reviews without horizontal scrolling to discover the decision actions.
- The Admin can inspect the exact questions and document requirements before deciding.
- Approve, Reject, Publish to review sandbox, Preview, and Publication history are distinct and understandable.
- The interface clearly states that sandbox publication is staff-only and saves no applicant responses.

### Security and regression

- CHEDRO and SCHOOL cannot see or directly access questionnaire workspaces, review pages, technical configuration routes, or sandbox previews.
- Super User receives no questionnaire publication capability unless explicitly granted.
- New UI actions cannot bypass active-account, policy, permission, hash, transaction, or immutability checks.
- Existing Phase 2 and Phase 3A PostgreSQL tests remain passing.

### General usability and accessibility

- Navigation and primary workflows work at desktop, tablet, and 320 px mobile viewport widths without hiding the next action behind horizontal table overflow.
- All primary actions are keyboard reachable with visible focus.
- Form controls have accessible names; errors identify both the problem and corrective action.
- Statuses use text plus icon/shape and maintain acceptable contrast.
- Pages remain usable at 200% zoom and with reduced-motion preferences.
- There are no dead links, unfinished feature links, or applicant-facing publication routes.

## 9. Staff training guide outline

1. **Access and account safety**
   - Signing in, account status, and signing out
   - Why available menu items differ by permission
2. **OSDS: manage a questionnaire**
   - Select Registration or ACSA
   - Edit questions and choices
   - Configure document requirements
   - Use conditions
   - Preview and submit for review
3. **Admin: review a questionnaire**
   - Find pending work
   - Inspect exact proposed content
   - Approve or reject with a reference and feedback
4. **Admin: publish to the review sandbox**
   - Publish the approved configuration
   - Preview and find publication history
   - Retire a sandbox publication
5. **Lifecycle and terminology**
   - Draft, In review, Approved, Published to review sandbox, Rejected, Retired
   - Difference between questionnaire and document-requirement versions
6. **Troubleshooting**
   - Validation messages, missing actions, permissions, immutable versions, and requesting support

## 10. Approval boundary

Implementation should begin only after this audit and plan are approved. The implementation checkpoint will make no database-schema or business-workflow change unless a concrete UI requirement proves one necessary and it is separately reviewed. It will stop before Phase 3B.
