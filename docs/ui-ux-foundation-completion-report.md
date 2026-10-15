# UI/UX foundation completion report

**Date:** 14 October 2026
**Scope:** Staff UI/UX standardization between Phase 3A and Phase 3B
**Result:** Implemented; Phase 3B remains unstarted

## Implemented screens and changes

### Shared design foundation

- Added centralized provisional color, surface, text, border, focus, radius, and reduced-motion tokens.
- Added a Filament theme consuming the same token foundation as future Blade/Livewire interfaces.
- Configured a consistent CHED OSDS staff-workspace brand name, constrained responsive content width, a collapsible 17rem sidebar, and unsaved-change alerts.
- Added complete light and dark token sets, retained Filament's Light/Dark/System switcher, and defaulted to the operating-system preference. Canvas, surfaces, text, borders, and controls now switch together.
- Added a persistent navy sidebar in both modes following the approved illustrative workspace direction.
- Removed the redundant dashboard account card; account access remains in the user menu.
- Added centralized questionnaire status labels, colors, icons, and version presentation.
- Retained Filament components and introduced no additional frontend framework.

### Dashboard and navigation

- Added permission-aware task cards for questionnaire drafts, pending approvals, and active sandbox publications.
- Added the OSDS **Questionnaires** entry point.
- Renamed the Admin queue to **Questionnaire approvals** and the publication list to **Published questionnaires**.
- For OSDS, the same secured review resource is labeled **My review submissions** and is scoped to submissions made by the signed-in user.
- Grouped existing user, region, institution, representative, and regional-assignment screens under **User and access management**.
- Grouped audit records under **Security and oversight**.
- Removed modules, form definitions, raw version lists, and evidence-provider catalogues from ordinary navigation. Their routes remain policy-protected for the task workspace and authorized technical support.

### OSDS questionnaire workspace

- Registration and ACSA are presented as program cards.
- Each module workspace shows Questions, Document requirements, Combined preview, and Submit for review as one workflow.
- Editable drafts link directly to plain-language editing screens with **Save draft** actions.
- Exact questionnaire and document-requirements versions must be selected when multiple drafts exist.
- Submit for review uses `QuestionnairePublicationService` and explains that the selected versions become frozen.
- **Create new draft** uses the existing clone service and preserves published/reviewed history.
- Stable keys remain visible with descriptions because conditional logic depends on them.
- Conditional key suggestions are narrowed to earlier questions for persisted questionnaire questions and to the same program for document requirements. Publication validation remains authoritative for the exact pair.
- Added a responsive workflow rail inspired by the supplied applicant-portal reference. Questions, documents, preview, and review remain anchors into the existing secured workspace; no applicant behavior was introduced.

### Staff visual refinement

- Refined staff tables and toolbars with clearer bordered surfaces, quieter headers, compact row treatment, and subtle hover feedback based on the supplied staff-workspace reference.
- Documented a future applicant-portal layout contract so Phase 3B can reuse the design tokens and progress pattern without presenting unfinished applicant routes now.
- Preserved the Light, Dark, and System theme choices and the persistent navy identity sidebar.

### Admin approval and publication workspace

- The queue defaults to Pending review and keeps **Open review** visible in a concise table.
- The review page shows the complete questionnaire and document requirements before any decision.
- Approve, Request changes, and Publish to review sandbox are separate confirmed actions.
- State-changing actions continue to use `QuestionnairePublicationService`.
- Hashes and public IDs moved to expandable **Technical details for authorized support**.
- Publication history uses readable statuses and keeps technical hashes optional.
- Retirement explains its consequence, requires a reason, and retains immutable history.

## Before and after navigation

| Before | After |
| --- | --- |
| Modules, Form definitions, Form versions, Requirement set versions, Review requests, Publication manifests, Evidence provider types | OSDS: Questionnaires and My review submissions; Admin: Questionnaire approvals and Published questionnaires |
| Default dashboard with account widget | Permission-aware counts and direct links to existing tasks |
| Review decisions at the far edge of a wide table | Concise queue plus dedicated inspection page and visible header actions |
| Hashes in primary operational tables | Technical details on demand |

## Security and compatibility

- No database migration was added.
- Phase 2 account status, authorization, regional controls, and audit behavior remain unchanged.
- Phase 3A hashes, review approval, publication transactions, manifests, PostgreSQL immutability triggers, and audit events remain unchanged.
- SCHOOL and CHEDRO have no access to questionnaire authoring, approval details, technical configuration routes, or sandbox previews.
- Admin cannot enter the OSDS authoring workspace solely because it can review configurations.
- OSDS review history is limited to its own submitted review requests unless the account has the explicit reviewer permission.
- Hidden navigation is not used as an authorization control.

## Automated verification

| Check | Result |
| --- | --- |
| Complete PostgreSQL PHPUnit suite | Passed: 42 tests, 131 assertions |
| Phase 3A questionnaire regression suite | Passed: 10 tests, 23 assertions |
| New UI route, scoping, authorization, and theme-regression suite | Passed: 6 tests, 28 assertions |
| Development migration status | All existing migrations applied; no migration added |
| Laravel Pint | Passed |
| Frontend production build | Passed with Vite 8.3.4 |
| Blade/route rendering exercised by feature tests | Passed |
| `git diff --check` | Passed |

## Accessibility and responsive findings

Implemented and verified through component/code inspection and route rendering:

- Semantic Filament controls and page structure remain in use.
- Primary workflows have descriptive action names rather than icon-only controls.
- Statuses combine readable text, icon, and color.
- Shared visible-focus and reduced-motion rules are present.
- Workflow cards use responsive grids; operational tables contain fewer primary columns and do not place approval decisions at the far edge.
- Long hashes wrap only inside secondary technical details.
- Confirmation dialogs explain consequential lifecycle transitions.

Formal assistive-technology testing, measured contrast auditing, 200% zoom observation, and testing with actual CHED/OSDS staff remain pending. No claim of full WCAG 2.2 AA conformance is made.

## Manual verification

Use [UI usability acceptance script](./ui-usability-test-script.md). It records task completion, confusing labels, navigation mistakes, assistance, and suggested improvements for first-time OSDS and Admin participants. Actual staff testing is explicitly pending.

Use [staff guide](./staff-guide-phase-3a-ui.md) for the implemented workflows. Design conventions are recorded in [UI design system](./ui-design-system.md).

## Known limitations

- The combined draft preview is intentionally read-only and concise. The sandbox remains the most representative staff preview after approval and publication.
- Common response validation is described more clearly, but advanced validation remains JSON-backed pending an approved catalogue of supported business-neutral rules.
- Documentary-requirement condition suggestions can be narrowed to the program, but the exact questionnaire pairing is chosen only at review submission. Backend validation rejects invalid exact-version references.
- Approved CHED visual identity standards and official logo assets have not been supplied; current tokens are provisional.
- Automated screen-reader testing and authenticated screenshot capture are not part of the current local toolchain. No screenshots containing account or synthetic security data were produced.
- Actual staff usability testing remains pending and must not be reported as completed.

## Stop boundary

No applicant submission, evidence reference, CHEDRO application review, autosave, correction, certificate, issuance, RA 11180, or other Phase 3B functionality was implemented.
