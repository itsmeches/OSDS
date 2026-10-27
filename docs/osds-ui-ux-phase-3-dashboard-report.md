# Phase 3 — Dashboards and application guidance

**27 October 2026. Approved presentation implementation and database-free verification PASS. Authenticated authorization, workflow acceptance and PostgreSQL regression remain BLOCKED pending separate authorization. Stop for review.**

## Implemented behavior

The shared staff dashboard now provides a **Your workspace** task widget before the existing configuration statistics. Filament's current dashboard route, discovery and navigation remain intact. Shortcuts appear only after the existing destination `canAccess()` check; resource destinations retain their policy-based access. The widget itself requires active staff-panel access. It uses no role-name branch or application/workload query.

Tasks are ordered as Applications and Reviews, Forms and Demonstration Documents, then Administration. Empty groups are omitted and destination classes are deduplicated. Evaluation, Chief and Director descriptions are included only for the corresponding permission and an accessible intake destination. Combined permissions yield one intake link with all applicable responsibilities. The text reiterates case-wide three-person separation and does not claim a particular case is assigned or ready.

Configuration statistics remain separate. Their labels are now **Questionnaire drafts**, **Pending questionnaire reviews**, and **Questionnaire publications (not retired)**. Descriptions distinguish configuration records from applicant drafts/decisions/workload and explain that non-retired publications can include scheduled or ended availability. All three count expressions and the original widget visibility method are unchanged. A permission combination with no visible statistics receives explanatory description text; its real Filament rendering remains part of blocked authenticated verification.

Applicant home and tracking use a pure shared guidance helper. Lifecycle meaning is no longer replaced by a questionnaire-availability warning. Existing draft/correction actions, publication restrictions, controller queries, history and immutable submission behavior remain unchanged. Approved cards and tracking provide a qualified link to check request availability; approval does not establish eligibility or generate a document. Resource links identify requests and blank downloads as separate tasks.

The request view uses readable application labels, explicitly identifies a saved request draft, and distinguishes a submitted immutable request from its separate PDF state. Existing service reasons, handlers, bindings, queries, download destination and recovery rules are retained. Unknown document states give uncertainty guidance rather than asserting that no document exists. No new stored status, automatic retry, generation or eligibility rule was added.

## Before and after

| Area / responsibility | Before | After |
| --- | --- | --- |
| Staff entry | Generic dashboard description; questionnaire statistics were the only widget | Existing dashboard with authorized task shortcuts first, followed by clearly named configuration statistics |
| Evaluator / Chief / Director | Shared intake destination available through navigation, little dashboard orientation | Same destination, with descriptions based on existing stage permissions; no new queue or decision action |
| Combined permissions | No combined task explanation | One destination per class with ordered responsibility descriptions |
| Restricted / no-task account | Potentially sparse dashboard | Explicit no-shortcut explanation; does not assert that no cases require attention |
| Configuration staff | “Your work” and “Pending approvals” could imply applicant workload | Explicit configuration terminology; unchanged counts and visibility |
| Applicant application list | Generic message for submitted, screening, resubmitted and final states | Explicit lifecycle meaning, existing action and separate publication notice |
| Approved applicant | Final outcome without request guidance | Qualified availability link to existing case-linked request workspace |
| Request/PDF view | Some raw application statuses and ambiguous request/document wording | Separate readable application, request and document guidance |

These are implemented source changes. The permission profiles in browser evidence are **simulated layouts**, not authenticated authorization results.

## Application guidance matrix

| Stored state/context | Display and next-step meaning |
| --- | --- |
| `draft` | Draft — not submitted. Complete/save the questionnaire and required links, then review and submit. Existing Resume draft conditions remain. |
| `submitted` | Submitted — awaiting screening. Do not send another copy unless corrections are requested. |
| `under_screening` | Under staff review. Updates and corrections appear in applicant-facing history; no Chief/Director substage is inferred. |
| `correction_requested` | Corrections requested. Read requested changes, update the correction copy, save and resubmit through existing permitted actions. |
| `resubmitted` | Corrections submitted — awaiting screening. Earlier submissions remain preserved. |
| `approved` | Approved — demonstration only. This does not issue a certificate; check request availability under existing eligibility checks. |
| `rejected` | Rejected — demonstration only. Read the final history summary; no request from that rejected decision. No reopening or reapplication invitation. |
| Unknown / empty / null | Status unavailable. Open history; do not echo an unrecognized value or infer a transition. |
| Questionnaire retired/ended | Separate notice that information is preserved and editing/resubmission is unavailable under the publication. Does not replace the lifecycle outcome. |

Issuance requests still have **draft/submitted** states. Documents separately have **processing/failed/completed** states. Completed PDF messaging remains nonofficial and its download still depends on fresh authorization and actual byte integrity. Missing definitions, binding failures and other unavailable conditions retain their service-provided reasons. No nine-day lock or both-module prerequisite was introduced.

## Exact runtime files

Modified existing files:

1. `app/Filament/Pages/Dashboard.php` — heading/subheading.
2. `app/Filament/Widgets/WorkflowOverview.php` — labels/descriptions and no-visible-statistics explanation.
3. `resources/views/components/applicant-status.blade.php` — shared explicit labels.
4. `resources/views/applicant/home.blade.php` — lifecycle guidance, publication notice, qualified request link and resources.
5. `resources/views/applicant/track.blade.php` — shared guidance and qualified approved-case request link.
6. `resources/views/livewire/applicant/issuance-requests.blade.php` — presentation wording/status labels and existing download styling; one newline separates adjacent Blade directives.
7. `resources/css/app.css` — dashboard stylesheet import only relative to the Phase 3 baseline.
8. `resources/css/filament/admin/theme.css` — dashboard stylesheet import only relative to the Phase 3 baseline.

New approved runtime files:

- `app/Filament/Widgets/TaskOrientation.php`.
- `resources/views/filament/widgets/task-orientation.blade.php`.
- `app/Support/ApplicantApplicationGuidance.php`.
- `resources/css/theme/dashboard.css`.

New verification/deliverables:

- `tests/Unit/ApplicantApplicationGuidanceTest.php` — pure PHPUnit tests.
- `tests/Feature/DashboardGuidanceTest.php` — four future database-backed feature tests, **not executed**. They guard configured and actual `osds_test` identity before RefreshDatabase preparation.
- `scripts/verify-phase3-dashboards.mjs`.
- This report and separate evidence under `docs/screenshots/phase3-dashboards/`.

Private baseline, standalone renderer, source-check tooling and logs are under `storage/app/private/phase3-dashboards/`. No file outside the approved runtime allowlist was changed as application code.

## Actual verification

| Check | Actual result / limit |
| --- | --- |
| Pure guidance tests | **PASS: 11 tests / 27 assertions**, final duration 15 ms. PHPUnit ran with `--no-configuration`, Composer autoload and the single pure test file; no Laravel application or database bootstrap. |
| Previous pure run | 11 / 25 passed before adding null-value coverage; final run includes that coverage. |
| Task catalogue | **PASS, source/metadata only:** all 12 destinations load and expose their existing access method; destination access calls, panel admission and permission-description checks are present; no new ORM/DB query or role branch in the widget. |
| Configuration count and visibility contracts | **PASS, source comparison:** all three original count expressions and original `canView()` method remain unchanged. |
| Request and application bindings | **PASS, source comparison:** all four existing request-view query expressions, request wire bindings and application-list draft action guards remain unchanged. |
| Standalone Blade rendering | **PASS:** changed widget/application/request view branches render with in-memory objects, collections and substituted component wrappers/helpers. Normal Blade loops/conditions/escaping are used. No application bootstrap or real authorization. |
| PHP syntax | **PASS:** dashboard, both widgets, helper and both new test files; rendered standalone Blade views also compiled/evaluated during preview preparation. |
| Standard component matrix | **PASS: 56/56 samples** — seven profiles × four widths × two themes. |
| Supplemental component matrix | **PASS: 12/12 samples** — combined-staff and applicant-state profiles at 320px, 375px/200% root text size and 1440px/200% root text size, both themes. |
| Automated accessibility | **PASS:** all 68 final samples have zero default axe violations and zero incomplete findings; target-size explicitly enabled. No rule exclusions or threshold changes. |
| Overflow / errors | **PASS:** zero measured horizontal page overflow or uncaught page errors in all final samples. |
| Keyboard focus | **PASS in sampled component entry:** Tab reaches a visible-focus link in every interactive profile. No-task preview has no controls; full keyboard journeys and screen-reader acceptance remain pending. |
| Visual review | Final combined-staff light 1440px and applicant-empty dark 375px images inspected. |
| Formatting | **PASS:** scoped Pint and full Pint `--test`. |
| Frontend/syntax/whitespace | **PASS:** Vite asset build, Node driver syntax, PHP syntax, tracked diff and new-file whitespace checks. Existing CRLF and bundler timing advice remain advisory. |
| IDE diagnostics | **UNAVAILABLE:** diagnostic tool returned an unsupported-namespace error; no IDE-clean claim. |
| Authenticated menu/task permissions and direct-route denial | **BLOCKED:** feature tests were created but not run. Simulated previews do not prove account access, revocation or combined permissions. |
| Real widget discovery / zero-stat rendering / authenticated Blade integration | **BLOCKED:** source compatibility and standalone rendering do not establish full Filament/Livewire integration. |
| Both-module UI journeys / PostgreSQL regression | **BLOCKED:** no fixture preparation or database-backed execution authorized. No historical pass is substituted. |
| Human accessibility / native browser zoom | **PENDING:** root text sizing and automated checks do not certify these. |

Final raw results: [results.json](screenshots/phase3-dashboards/results.json). Source checks: [source-contracts.json](screenshots/phase3-dashboards/source-contracts.json). Catalogue/guidance metadata: [catalogue-and-guidance.json](screenshots/phase3-dashboards/catalogue-and-guidance.json). Private logs include `unit-final.txt`, `browser-final.txt`, `build-final.txt` and `pint-final.txt`.

### Browser scope and retained failures

The driver builds standalone Blade previews from the changed source using Composer classes and a minimal isolated view container. Task catalogue visibility is deliberately simulated; destination authorization and service eligibility are **not called**. Existing request-view queries are replaced with inert preview collections, with a guard rejecting retained query calls. Route helpers produce inert preview fragments. Actual built CSS and Heroicon SVGs are used. Filament wrappers and some shared components are substituted, so this is not an authenticated Filament/Livewire page or a demonstration of functional form submission.

Profiles are no-task, review-only, configuration-only, combined staff, applicant states/tracking, applicant empty state, and request/PDF states. The application gallery includes all seven lifecycle states, unknown state, ended-publication combinations, correction tracking, approval tracking and unknown tracking. The request gallery includes draft/submitted requests, processing/failed/completed/unknown PDFs and an unavailable definition. Supplementary question rendering, protected-identity population, real saved-version data and case access remain outside this preview's coverage.

The first standalone renderer encountered adjacent `@endif@endforeach` directives in the request view. A newline was added within the approved view; the condition, loop, bindings and queries remain unchanged. This establishes standalone compilation, not the cause or prior behavior of full application component compilation.

The first complete preview matrix had **14 failing samples out of 60**: it concatenated populated and empty home pages, duplicating the “Application resources” navigation landmark. This was a preview-composition problem, not an established live-page defect. The empty page now has its own isolated profile. No landmark names, accessibility rules or application semantics were removed to resolve it. Original results and 60 captures remain under `first-gallery-attempt/`; the final matrix has 68 samples. Existing historical reports/captures were not overwritten.

Existing B4 unresolved accessibility findings remain open; these sampled component passes do not close them or certify the full application.

## Preservation and boundaries

The pre-edit baseline recorded Git HEAD `f7625a9de909a991013db6ce43c68bee65402ce0`, the dirty working tree and **2,638 file hashes**. [Preservation comparison](screenshots/phase3-dashboards/preservation.json) establishes:

- Exactly **eight existing files changed**, all on the approved runtime allowlist.
- **2,630 baseline files remain byte-identical**, with zero missing baseline files.
- All **71 baseline PDFs remain byte-identical**, including private stored documents and source artifacts.
- Phase 1 navigation, Phase 2 landing source/evidence, pending accessibility remediation, historical reports/evidence including the ten published JSON files, source artifacts and the unrelated Batch G script are preserved. The only changes to the two existing stylesheet entry files are their approved imports.

The four new runtime files and new tests/report/evidence are additions, not part of the baseline-identical count. No controller, service, policy, model, route, seeder, migration, workflow stage or PDF renderer changed. No database was accessed, inspected, prepared, reset, seeded or migrated. No official issuance, manual activation, commit, push, deployment or further phase occurred. Batch G remains CONDITIONAL CLOSE.

## Remaining acceptance and next recommendation

Before complete technical acceptance, separately authorize the exact disposable database operations for authenticated widget rendering, representative and combined permissions, revoked/inactive users, same-person stage denial, regional/module denials, each real application state, missing/withdrawn request definitions, request/PDF integrity behavior and the independent COR/ACSA UI journeys. Run the new feature tests, appropriate targeted regressions and complete PostgreSQL suite only under that authorization, with identity checks. Never use or activate `osds_dev` for those checks.

User-facing wording changes may require reviewed locator/text-assertion updates once real testing is authorized. Do not weaken handlers, persistence assertions or historical evidence to accommodate copy. Zero-stat rendering and actual resource-policy integration require particular attention because this phase did not mount authenticated widgets.

Supervisor review should confirm the wording with nontechnical HEI/association representatives and CHED personnel. Official COR terminology, approved support contacts, native zoom, screen-reader testing and first-time-user acceptance remain pending.

The recommended next work is **authenticated acceptance of the accumulated Phase 1–3 presentation changes**, rather than another feature phase or new operational metrics. No further UI/UX phase has started. **Stop for review.**
