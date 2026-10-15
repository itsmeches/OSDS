# OSDS Form Maker 2.0: usability audit and implementation plan

**Status:** Approved and implemented; see [completion report](./osds-form-maker-2-completion-report.md)
**Date:** 15 October 2026
**Boundary:** Form Maker usability redesign only. This plan does not authorize Phase 3C, evidence collection, applicant submission, official processing, production publication, or database migrations.

## 1. Executive recommendation

Replace the current launcher-plus-resource workflow with one custom Filament/Livewire **Form Builder** page. The page should edit a working copy of the paired questionnaire and document-requirements drafts, show a live preview produced by the same renderer used by the applicant portal, and send all persistence and lifecycle actions through domain services.

Keep the existing relational schema, lifecycle, hashes, approvals, manifests, permissions, audit events, and PostgreSQL immutability controls. Retain the current technical resources as policy-protected support screens, removed from the normal OSDS journey.

The simplest maintainable first release uses explicit **Save draft**, an unsaved-change warning, and an `updated_at` concurrency token. Timed autosave is deferred. This avoids introducing a migration and reduces the risk of overwriting work while the new editor is stabilized.

## 2. Current Form Maker usability audit

### Current journey

```text
Questionnaires
  → choose Registration or ACSA
  → open Edit questions technical resource
  → edit nested repeaters and save
  → return to workspace
  → open Edit document requirements technical resource
  → edit nested repeaters and save
  → return to workspace
  → inspect a simplified read-only preview
  → open Submit for review modal
  → manually pair exact draft versions
```

### Findings

| Priority | Observed implementation | Effect on a first-time OSDS employee | Required response |
| --- | --- | --- | --- |
| Critical | The workspace sends users into separate `FormVersionResource` and `RequirementSetVersionResource` nested repeater forms. | The task feels like editing database records and requires repeated navigation. | Make the module-scoped builder the only ordinary authoring surface. |
| Critical | Staff preview uses simplified text placeholders while the Phase 3B applicant portal has a separate renderer. | Staff cannot reliably see the applicant result; behavior can drift. | Introduce shared field and requirement rendering components. |
| Critical | The applicant renderer handles `single_choice`, `yes_no`, `long_text`, email and date specially, but other declared types fall back to a text input. `multiple_choice`, integer, decimal, telephone and declaration need accurate shared controls. | A “live preview” built on the current renderer would be misleading. | Normalize and test every currently supported backend type before claiming preview parity. |
| High | Stable section, question, option and requirement keys are visible and manually entered. | Users must understand machine identifiers and can create invalid or unstable references. | Generate keys automatically, keep them stable, and show them only in authorized technical details. |
| High | Advanced response validation is edited as JSON. | Nontechnical users can produce syntax errors and cannot predict the result. | Replace common rules with guided controls; keep unsupported advanced JSON out of the normal builder. |
| High | Conditions request an “earlier question key” through a datalist and values through free text/tags. | The rule is technical, weakly constrained, and errors may appear only at review. | Use question labels, type-aware operators, and selectable option labels. |
| High | Question and document editing has no persistent live preview or test answers. | Consequences of changes remain hidden until later. | Add Edit/Preview split view and ephemeral Test as applicant mode. |
| High | Nested repeaters expose many controls at once and rely on modal/resource saves. | Long forms are hard to scan; users can lose context. | Use focused cards, progressive disclosure, and inline editing. |
| High | Existing resource persistence is presentation-driven; lifecycle actions use services, but ordinary nested edits do not have a dedicated authoring transaction service. | Reliable concurrency, audit boundaries, and consistent error recovery are harder to guarantee. | Add a draft-authoring service for explicit transactional saves. |
| Medium | Preview and review pairing select separate versions. | Pairing is easy to misunderstand even though exact pairing is required internally. | Bind the builder session to one explicit pair and present it as one form draft. |
| Medium | Validation returns a single configuration-level message and does not identify a UI card. | Users must search manually for the error. | Return structured findings with stable item paths and focus links. |
| Medium | Duplicate support exists only for cloning entire published/reviewed configurations. | Repetitive questions or sections require re-entry. | Add safe local duplication for questions and sections with new generated keys. |
| Medium | Save state is not continuously visible in the authoring resource. | Users cannot confidently tell whether changes persisted. | Add Unsaved, Saving, Saved, Save failed, and Conflict indicators. |
| Medium | The workspace submission action previously opened with no eligible drafts. | Lifecycle state was unclear. | Preserve the new disabled-state guidance and incorporate it into the builder review stage. |

### Strengths to preserve

- Exact form and requirement versions remain separate and immutable after publication.
- Review requests, approval hashes, publication manifests, and audit events already protect lifecycle integrity.
- Declarative conditional operators are constrained and evaluated server-side.
- Phase 2 permissions and account status checks restrict authoring and publication.
- PostgreSQL triggers protect published configuration.
- The Phase 3B applicant portal already supplies an authenticated, manifest-bound rendering context.

## 3. Proposed unified screen and interaction design

### Desktop layout

```text
┌──────────────────────────────────────────────────────────────────────────┐
│ Registration form   Draft   Unsaved changes     Preview  Check  Review  │
├─────────────────────────────────┬────────────────────────────────────────┤
│ 1 Build form                    │ LIVE APPLICANT PREVIEW                 │
│ 2 Required documents            │ Preview / Test as applicant / Expand  │
│ 3 Test and validate             │                                        │
│ 4 Send for review               │ Applicant-style section and controls  │
│                                 │ Preview data is temporary              │
│ [Section card]                  │                                        │
│   [Question card]               │                                        │
│   [+ Add question]              │                                        │
│                                 │                                        │
│ Save draft                      │                                        │
└─────────────────────────────────┴────────────────────────────────────────┘
```

The editing pane should use about 55% of the available width and the preview 45%, with a draggable divider omitted from the first release. At narrower widths, show accessible **Edit** and **Preview** tabs. Full Preview opens an applicant-style page or full-width panel without persisting answers.

### Header

- Editable plain-language form title for a draft.
- Human-readable lifecycle badge.
- Save-state indicator with `aria-live="polite"`.
- Secondary **Preview** and **Check form** actions.
- One primary context action: **Save draft** while editing or **Send for review** after a successful check.
- Technical version IDs and hashes only in an expandable support-details panel.

### Builder stages

1. **Build form:** sections and question cards.
2. **Required documents:** visual requirement cards.
3. **Test and validate:** interactive preview plus structured findings.
4. **Send for review:** paired-version summary and lifecycle explanation.

The stages organize one bound draft pair. Users do not manually select version records during routine submission. If multiple draft pairs exist, the workspace first asks which pair to open and never silently chooses one.

### Question cards

Collapsed cards show question label, response type, required/optional state, condition summary, and drag handle. Expanding a card shows:

- Question text and optional instructions.
- Response type using only supported types.
- Required toggle.
- Choices for choice types, with Add choice, reorder, duplicate where useful, and delete.
- **Display rules** and **Answer validation** under Advanced settings.
- Duplicate and Delete actions; deletion requires confirmation when referenced by a condition.

Internal keys are generated from a slug plus a collision-safe suffix when the item is created. Editing labels never changes an existing key. Duplicating generates a new key and retains safe content; condition references are not copied when they would be invalid.

### Document requirement cards

Each card exposes document name, instructions, required state, accepted provider labels, link count, condition summary, duplicate, delete, and reorder. Phase 3B preview displays the requirement but does not accept links or files.

## 4. Live preview architecture and synchronization

### One rendering contract

Create a presentation DTO/view model for sections, questions, options, conditions, and requirements. Both persisted models and unsaved builder state are normalized into this contract.

Create shared Blade/Livewire components such as:

- `questionnaires.renderer`
- `questionnaires.fields.short-text`
- `questionnaires.fields.long-text`
- `questionnaires.fields.single-choice`
- `questionnaires.fields.multiple-choice`
- `questionnaires.fields.yes-no`
- `questionnaires.fields.date`
- `questionnaires.fields.number`
- `questionnaires.fields.telephone`
- `questionnaires.fields.declaration`
- `questionnaires.requirement-card`

The Phase 3B draft editor and the staff builder use these components with different modes:

| Mode | Answers | Persistence | Banner |
| --- | --- | --- | --- |
| Applicant draft | Manifest-bound draft values | `ApplicantDraftService` | Synthetic applicant context |
| Builder live preview | Ephemeral component state | None | Preview: changes may be unsaved |
| Test as applicant | Ephemeral interactive state | None | Test only: responses are discarded |
| Full preview | Ephemeral/read-only state | None | Preview only |

### Synchronization

- The builder owns a single in-memory working state.
- Editing fields updates the right pane through Livewire state without writing applicant records.
- Structural edits update after a short debounce or explicit card completion; text may update live.
- Test answers live in a separate `previewAnswers` property and are reset when response type or options make them incompatible.
- Conditional visibility calls the existing `ConditionalLogic` evaluator against preview answers.
- Requirement applicability uses the same evaluator and answer map.
- A clear indicator distinguishes persisted draft state from unsaved preview state.

No application draft, applicant answer, evidence reference, review request, or audit event is created by preview interactions.

## 5. Shared validation strategy

### Authoritative server validation

Refactor `ConfigurationValidator` to return structured findings before throwing at lifecycle boundaries. Each finding should include:

- severity (`error` initially; warning only where it cannot imply readiness);
- plain-language message;
- item type;
- stable section/question/requirement key;
- UI anchor/path;
- technical code for tests and support.

`submitForReview` continues to invoke authoritative validation and refuses invalid configuration. The builder’s **Check form** calls the same validator against the saved paired drafts.

### Continuous advisory checks

The builder can immediately identify missing labels, insufficient choices, invalid min/max counts, and visibly broken rules. These checks are advisory until the saved configuration passes backend validation.

Statuses:

- **Incomplete:** obvious required authoring data is absent.
- **Needs attention:** backend check returned findings.
- **Ready for review:** the latest saved configuration passed the backend check and has not changed since.

Any edit clears **Ready for review**. It must never imply approval or publication.

### Validation gaps to close

- Require at least two choices for choice questions if that is accepted as a technical authoring rule; the current validator only requires a nonempty set.
- Validate duplicate keys, blank labels, option labels and option-key uniqueness with item-specific findings.
- Validate number/date/length settings through guided fields.
- Ensure all supported types render and round-trip consistently.
- Continue rejecting later references, invalid options, malformed groups, cross-version references, and unsupported operators.

The “at least two choices” rule should be confirmed as a product rule before implementation because the current backend accepts one.

## 6. Natural-language condition builder

Display a sentence-shaped editor:

> Show this item if **[Type of organization] [is] [Athletic Association]**.

Design rules:

- Source dropdown contains only earlier questions in the same working form, showing the label first and internal key only in support details.
- Operator labels map to existing operators: `is`, `is not`, `is one of`, `is not one of`, `is empty`, `is not empty`.
- Choice sources use option-label checkboxes/selects while storing stable option keys.
- Non-choice values use type-appropriate controls.
- Group labels read **All rules must match** or **Any rule may match**.
- A read-only natural-language summary remains visible when Advanced settings is collapsed.
- Invalid or deleted references remain visible as an error rather than being silently removed.
- Preview answers allow immediate condition testing.

This is a UI adapter over the existing `all`/`any` JSON structure and evaluator. It adds no executable expressions or eligibility decisions.

## 7. Draft saving and error recovery

### First-release save model

- Use explicit **Save draft** rather than timed autosave.
- Track dirty state in Livewire and warn on navigation, close, refresh, and module change.
- Show Unsaved changes → Saving → Saved only after the database transaction commits.
- On validation or network failure, retain the working state and show Save failed with a retry action.
- Capture the parent form and requirement `updated_at` values when loading. The authoring service locks both rows, compares expected timestamps, persists the full pair transactionally, touches both parents, and returns new timestamps.
- On conflict, preserve local state and offer **Reload latest**, **Copy my changes**, and **Cancel**. Do not merge automatically.

### Domain service

Add a `QuestionnaireAuthoringService` responsible for:

- active-account and explicit permission checks;
- confirming both records are drafts, same module, and the selected pair;
- rejecting published/in-review/retired mutation;
- stable-key generation and uniqueness;
- transactional section/question/option/requirement synchronization;
- optimistic conflict detection;
- safe authoring audit metadata without full questionnaire content.

Question duplication and section duplication occur in local working state and persist only on Save. Delete confirmations identify affected conditions. Undo can be limited to restoring the most recently removed unsaved card in the first release.

No migration is required for this plan if timestamp concurrency proves reliable in a prototype. If a dedicated `lock_version` is later required, it must be proposed as a separate migration before implementation.

## 8. Guided review experience

The final stage displays:

```text
Registration
15 questions · 4 required documents · 3 display rules
Configuration check: Passed against the latest saved draft

Next: Send this exact form and document configuration to an Admin.
```

It explains that:

- Admin reviews the exact saved pair.
- Review does not expose the form to applicants.
- Approval and each sandbox publication are separate actions.
- Submitted versions are frozen.
- Later changes require rejection back to draft or cloning a new version according to the existing lifecycle.

The builder passes its already bound form and requirement IDs to `QuestionnairePublicationService::submitForReview`; ordinary users do not choose version IDs in a modal.

## 9. Proposed components and files

### New

- `app/Filament/Pages/FormBuilder.php`
- `app/Livewire/Questionnaires/FormBuilder.php` or focused child components if the Filament page becomes too large
- `app/Services/Questionnaires/QuestionnaireAuthoringService.php`
- `app/Services/Questionnaires/QuestionnaireRenderer.php` or a presentation mapper
- `app/Data/Questionnaires/*` for small typed presentation/finding objects
- shared Blade components under `resources/views/components/questionnaires/`
- builder views under `resources/views/filament/pages/form-builder/`
- focused CSS under the existing Filament theme entry point
- feature tests for builder access, save, preview, validation, lifecycle, and conflicts

### Refactor

- `ConfigurationValidator`: structured findings plus existing authoritative failure behavior.
- `ConditionRuleField`: replace in ordinary flow with the natural-language builder; retain for technical fallback if needed.
- `DraftEditor` and its view: consume shared render components without changing its persistence service.
- `Questionnaires` page: become module/pair selection and lifecycle overview, opening the unified builder.
- `FormVersionResource` and `RequirementSetVersionResource`: support-only routes; no ordinary navigation.
- Sandbox preview: consume shared renderer and fix text encoding artifacts.

### Preserve unchanged

- `QuestionnairePublicationService` lifecycle boundaries.
- Hashing and immutable manifests.
- Phase 2 authorization model.
- Phase 3B draft persistence and ownership checks.
- PostgreSQL publication guards.

## 10. Security and lifecycle risks

| Risk | Control |
| --- | --- |
| Unsaved builder state bypasses authoritative checks | Treat live checks as advisory; require saved-state backend validation immediately before review submission. |
| Custom Livewire calls mutate published content | Reauthorize in the authoring service and recheck state under row locks; keep database immutability triggers. |
| Preview accidentally creates applicant data | Use isolated ephemeral component properties and shared presentation components only; never call `ApplicantDraftService`. |
| Stable keys change with labels | Generate once, preserve on rename, and hide from ordinary editing. |
| Deleting a referenced question breaks conditions | Block or explicitly confirm deletion, list dependents, and surface unresolved references until corrected. |
| Two editors overwrite each other | Compare saved timestamps under row locks and reject stale saves while retaining local input. |
| UI and applicant behavior drift | Share render components, type registry, and conditional evaluator; add parity tests. |
| The preview exposes staff-only drafts | Require active OSDS permissions on every builder/preview request; no public route. |
| Technical resources become an authorization back door | Preserve policies and direct-route tests even after hiding navigation. |
| WYSIWYG claim exceeds actual parity | Call it “Live applicant preview” only after supported-type parity tests pass; keep a visible preview-only banner. |

## 11. Implementation work packages

### FM2.1 — Rendering parity foundation

- Define the supported field registry and presentation contract.
- Build shared field/requirement components.
- Refactor Phase 3B editor and staff sandbox to use them.
- Verify every existing type, help text, required semantics, choices, and conditions.

**Gate:** Applicant behavior remains unchanged except corrected rendering of already-supported types; Phase 2, 3A and 3B tests pass.

### FM2.2 — Unified builder shell and working state

- Add the module/draft-pair selection flow and builder header/stages.
- Add question, section and requirement cards with reorder, duplicate and delete.
- Hide internal keys and generate them safely.
- Add explicit transactional save, dirty-state warning, save feedback and stale-save rejection.

**Gate:** A draft pair can be edited, saved, reopened and conflict-tested without affecting lifecycle or applicant records.

### FM2.3 — Live preview and test mode

- Add split view, responsive tabs and full preview.
- Feed unsaved working state into shared components.
- Add ephemeral test answers and conditional behavior.
- Add preview synchronization and unsaved-state labeling.

**Gate:** Preview parity and non-persistence tests pass for every supported type and condition operator.

### FM2.4 — Guided validation and conditions

- Add natural-language condition UI.
- Return structured validator findings and focus anchors.
- Add guided answer-validation controls for rules the backend actually supports.
- Add Check form status and correction workflow.

**Gate:** Invalid configurations identify exact cards and cannot reach review; evaluator semantics remain unchanged.

### FM2.5 — Review handoff, accessibility and usability

- Add review summary and direct bound-pair submission.
- Simplify navigation and retain support details.
- Complete keyboard, screen reader, zoom, mobile and both-theme verification.
- Run the prepared OSDS staff usability script when participants are available.

**Gate:** All automated acceptance scenarios pass and real-user testing is recorded as completed or pending accurately.

No work package may begin Phase 3C.

## 12. Automated acceptance criteria

- Authorized OSDS can create/open a draft pair; Admin, CHEDRO and SCHOOL cannot access builder or preview routes without explicit permission.
- Five representative supported question types can be added, reordered, duplicated, saved and reopened.
- Generated keys are unique and remain stable after label edits.
- Published, in-review and retired versions reject direct and forged mutations.
- All supported question types render consistently in builder preview and applicant draft mode.
- Preview answers and test conditions never create application drafts, answers, events, evidence, review requests, or submissions.
- Choice and condition edits update preview and invalid references produce item-specific findings.
- Requirement conditions use the same evaluator as question conditions.
- Stale saves fail without overwriting database or local working state.
- Save failures never display Saved.
- Navigation with dirty state warns the user.
- Check form uses the authoritative backend validator; edits invalidate the prior Ready result.
- Review submission uses the bound exact pair and existing publication service.
- Existing hashes, approvals, manifests, audit guarantees and PostgreSQL immutability tests continue to pass.
- Light, dark and system themes; keyboard flow; visible focus; accessible names; error announcements; 200% zoom; 320 px reflow; and reduced motion are verified.

## 13. Manual usability script

Give a first-time OSDS participant a synthetic module and ask them to:

1. Open the Form Builder and rename the draft.
2. Add short answer, paragraph, multiple choice, checkbox and date questions.
3. Reorder and duplicate a question.
4. Add a conditional question and test it in preview.
5. Add two document requirements.
6. Run Check form, locate one planted error and correct it.
7. Save, leave, reopen and confirm the content.
8. Send the exact configuration for Admin review.
9. Explain what happens next using only information shown by the interface.

Record completion, time, navigation errors, misunderstood labels, help requested, lost-work concerns and participant suggestions. Do not claim usability success until actual OSDS personnel perform the script.

## 14. Existing versus proposed workflow

| Task | Existing | Proposed |
| --- | --- | --- |
| Start editing | Module page, then separate technical resource | Open one module-bound Form Builder |
| Questions and documents | Separate screens and saves | One staged workspace and paired save boundary |
| Keys and versions | Visible/manual | Generated and hidden; technical details remain available |
| Validation | JSON plus lifecycle-time error | Guided settings, continuous advice, authoritative Check form |
| Conditions | Machine key/operator/free value | Natural-language, type-aware rule builder |
| Preview | Simplified placeholders separate from applicant UI | Shared applicant renderer with ephemeral test answers |
| Save confidence | Resource save with no persistent workspace state | Explicit save state, dirty warning, retained input and conflict handling |
| Review submission | Modal manually pairs versions | Summary submits the builder’s exact bound pair |
| Published changes | Clone exists but is separate | Clear read-only state with Create new draft |

## 15. Approval boundary

Approval of this document would authorize implementation planning for FM2.1 through FM2.5 in sequence. Before coding, confirm whether the first implementation checkpoint should include all five packages or stop after rendering parity and the builder shell. Any schema migration discovered during prototyping must be documented and separately approved. Phase 3C remains unstarted.
