# Phase 3 — Role-based dashboards and application guidance plan

**27 October 2026. Read-only source assessment and documentation only. Proposed implementation requires separate authorization.** No runtime file, route, permission, database, workflow, PDF or historical evidence was modified. No test, fixture preparation or application command was run. Phase 1 and Phase 2 authenticated acceptance remain pending. Batch G remains CONDITIONAL CLOSE.

## 1. Assessment and evidence limits

“Role-based” means presentation derived from existing effective permissions and destination access checks, never branching on an account's role name. A dashboard explains available destinations; it does not establish that a particular case is assigned, ready or actionable.

This assessment inspected the current working-tree source. It did not observe signed-in pages, query a running database, evaluate real accounts or measure live responsiveness. Existing screenshots and reports supply historical context, not a new authenticated pass.

| Priority | Source-verified finding | Effect and proposed response |
| --- | --- | --- |
| High | [Dashboard](../app/Filament/Pages/Dashboard.php) provides only a generic subheading. [WorkflowOverview](../app/Filament/Widgets/WorkflowOverview.php) is the only application widget found. | Review-only users lack an explicit dashboard task entry point. Add a separate permission-filtered task-orientation widget, retaining the shared dashboard. |
| High | WorkflowOverview contains questionnaire draft, pending configuration review and non-retired publication counts. | “Your work” / “Pending approvals” can suggest assigned applications. Relabel these as configuration records; preserve the existing queries and visibility checks. |
| Medium | Widget visibility includes `questionnaires.view`, but a draft statistic requires manage/submit permission. | A view-only combination can reach the widget's visibility gate without receiving a statistic. Render a meaningful zero-stat explanation rather than inventing counts or granting a new destination. Actual layout requires browser confirmation. |
| High | Applicant home has special guidance only for draft/correction; submitted, screening, resubmitted, approved and rejected use the same default message. | Final outcomes and next steps are unclear. Present explicit guidance for each real lifecycle state. |
| High | Applicant home applies questionnaire-ended text before state-specific guidance. | A publication notice can obscure an approved/rejected outcome. Show the lifecycle meaning and publication notice separately; retain the existing editing restrictions. |
| Medium | Applicant status labels explicitly cover five nonterminal states; final labels fall through to title-cased raw values. | Give approved/rejected explicit demonstration wording. Preserve stored states and immutable history. |
| Medium | Tracking already has useful correction instructions and separate final-outcome panels. It does not guide approved applicants toward the existing request workspace. | Add a clearly qualified “Check issuance-request availability” destination, not an eligibility or generation promise. |
| Medium | Application `under_screening` persists across evaluation, Chief return and recommendation. The applicant controller does not load decision-stage records for display. | Explain “Under staff review” generally. Do not infer a Chief/Director stage from this lifecycle state or add queries for stage-specific applicant guidance. |
| Medium | Issuance request views show some raw application states and repeat technical scope wording. | Reuse readable application labels and explain request status separately from application status. |
| High | Issuance requests have draft/submitted states; demonstration documents separately have processing/failed/completed states. | “Completed request” must not become a new state or imply official issuance. Explain a submitted request and its separate PDF availability. |
| Medium | Applicant home loads all matching owned cases, while request views contain existing case/definition/document/history queries. | Do not add dashboard queries, per-card relationship lookups or aggregation. Pagination/query optimization would be a separate proposal. |

Evidence: [applicant controller](../app/Http/Controllers/ApplicantPortalController.php), [home](../resources/views/applicant/home.blade.php), [tracking](../resources/views/applicant/track.blade.php), [status component](../resources/views/components/applicant-status.blade.php), [request component](../app/Livewire/Applicant/IssuanceRequests.php), [request view](../resources/views/livewire/applicant/issuance-requests.blade.php), [case policy](../app/Policies/ApplicationPolicy.php), [reviewer scope](../app/Services/Authorization/ReviewerQueueScope.php), [B1 service](../app/Services/Applications/ApplicationDecisionService.php), [B2 eligibility](../app/Services/Documents/CaseIssuanceService.php) and [document migration](../database/migrations/2026_10_26_100000_create_demonstration_documents.php).

## 2. Proposed layouts and task orientation

### Shared staff dashboard

Use the existing Filament dashboard and widget discovery. Add one task-orientation widget before WorkflowOverview; do not add a new dashboard route or replace the navigation shell.

Order:

1. Heading: **Your workspace**. Description: “Open the work available to your account. Case actions depend on your current assignments and the application's stage.”
2. **Review applications**: one card linking to Applications and decisions, with permission-specific responsibility descriptions combined in a fixed evaluator → Chief → Director order. Add the separate evidence test-review card only when authorized.
3. **Forms and demonstration documents**: authorized questionnaire, configuration-review, publication, blank-form, issuance-definition and case-PDF destinations in Phase 1 order.
4. **Administration**: compact existing Users, Institutions, Institution representatives and Regional assignments links, only when their existing resource access permits them. Other administrative resources remain available in the unchanged sidebar.
5. **Questionnaire configuration overview**: existing three statistics with corrected explanations, visually subordinate to task links.

Omit empty task groups. Deduplicate destinations for combined permissions. A user permitted to view intake but without a stage permission receives a general “View assigned applications” description; no mutation invitation. A user with no available task destinations sees: “No dashboard shortcuts are available for your current access. Use an available sidebar item or contact the administrator who manages your account.” Do not imply there are no pending cases.

Do not title a card “Cases awaiting your decision,” display workload badges or assert “Nothing requires attention”: those claims require case-level data not loaded on this dashboard.

### Permission-specific responsibility text

| Audience / existing permission | Proposed explanation | Existing destination and limits |
| --- | --- | --- |
| Evaluator — `applications.evaluation.record` | “Review the current submitted copy and record a technical evaluation. Request corrections when your account is authorized.” | Applications and decisions; correction additionally requires its existing permission. |
| Chief — `applications.recommendations.record` | “Review the evaluation, recommend approval or return the case for reevaluation when the current stage permits it.” | Same intake destination; not a new recommendation queue. |
| Director — `applications.decisions.record` | “Review the recommendation and record a final demonstration approval or rejection when permitted.” | Same intake destination; no decision button on the dashboard. |
| Evidence reviewer — existing page access | “Open assigned evidence test reviews. Findings do not constitute a final application decision.” | Assigned evidence test reviews, retaining exact assignment/scope filtering. |
| OSDS / configuration permissions | “Manage application questionnaires, configuration reviews, publications and demonstration settings available to your account.” | Only destinations passing their existing access checks. |
| Administration permissions | “Manage the institutions and account records you are authorized to access.” | Existing resource pages and policies; no new account lifecycle operations. |

Combined permissions produce combined explanations, not a precedence rule that hides a user's other responsibilities. Case-wide evaluator/Chief/Director separation still applies even if an account holds several stage permissions.

### Existing configuration statistics

Keep all three existing count queries unchanged. Do not reinterpret their results as regional, personally assigned or live intake data.

| Existing statistic | Proposed label / explanation |
| --- | --- |
| Questionnaire drafts | **Questionnaire drafts** — “Draft questionnaire versions; not applicant drafts.” |
| Pending approvals | **Pending questionnaire reviews** — “Configuration review requests awaiting review; not application decisions.” |
| Sandbox publications | **Questionnaire publications (not retired)** — “Publication records without retirement. May include scheduled or ended availability; not a count of currently open services.” |

The widget description must state that these are existing configuration-record counts, not personal application workload. A zero count is a valid record count, not proof that a user has no work. Preserve the widget's existing visibility contract; broader task orientation comes from the separate widget.

### Applicant workspace

Retain **My applications**, the existing collection and its ordering. Layout:

1. Existing page heading and Start application action.
2. Short explanation: “An application and a demonstration-document request are separate. Start with your application; an approved case may have a request available.”
3. Existing application cards with module, institution, readable status, concise state guidance, existing primary action and saved/updated timestamp.
4. Small resource links to the existing issuance-request workspace and blank downloads; neither claims case eligibility.

Keep the empty state helpful: “No applications are available in this workspace. Start an application to view the organizations and questionnaires currently available to your account.” Do not promise that a questionnaire is published or representation is valid before the start screen checks it. Do not add counts, new lists or document lookups to home.

Correction cards retain “Make corrections” and tracking retains the actual requested items. Submitted copies remain immutable. The plan does not add inline editing, automatic resubmission or copying between cases.

## 3. Application-status guidance

Use a small pure presentation helper for application labels and explanatory copy, shared by the status component and application home/tracking. It takes existing scalar state/context only: no model queries, eligibility checks, mutations or generated workflow stages. Existing action conditions remain in the views.

| Actual state/context | Proposed label and guidance | Available destination; no new action |
| --- | --- | --- |
| `draft` | **Draft — not submitted.** “Complete and save your questionnaire and required document links, then review and submit.” | Resume draft under the existing working-copy/publication conditions. |
| `submitted` | **Submitted — awaiting screening.** “Your submitted copy is saved for review. You do not need to submit another copy unless corrections are requested.” | View status and history. |
| `under_screening` | **Under staff review.** “Authorized staff are reviewing your current submission. Any requested corrections and applicant-facing updates will appear in your history.” | View status and history; do not infer the current B1 stage. |
| `correction_requested` | **Corrections requested.** “Read the requested changes, update your correction copy, save and resubmit.” | Make corrections only through the existing editable working copy; otherwise retain read-only status/history and existing availability restrictions. |
| `resubmitted` | **Corrections submitted — awaiting screening.** “Your revised copy has been sent. Earlier submissions remain preserved.” | View status and history. |
| `approved` | **Approved — demonstration only.** “The Director approved this submitted revision for the demonstration. This does not issue a certificate. Check the issuance-request workspace for availability.” | Existing status/history plus a link to `applicant.issuance-requests` with the existing `application` parameter, where current authority and eligibility are checked. |
| `rejected` | **Rejected — demonstration only.** “Read the final decision summary in your history. No demonstration-document request is available from this rejected decision.” | View status/history. No appeal, reopening, renewal or new-application invitation. |
| Publication retired/ended | Separate notice: **Questionnaire availability ended.** “Saved information is preserved; editing and resubmission are unavailable under the current publication.” | Preserve current draft/view/history link conditions. Do not replace final-outcome copy or imply that publication expiry automatically blocks a previously submitted issuance request. |
| Unknown/unmapped state | **Status unavailable.** “Open the application history for the available details.” | Conservative read-only guidance; never infer a transition. |

“Awaiting review” is explanatory copy for existing states, not a new stored state. Do not add B1 substage loading to applicant pages. History summaries remain unchanged and internal notes remain excluded.

The homepage's current list authorization is not identical to the stronger tracking policy. Do not silently rewrite that query or claim that a visible case guarantees successful access to every destination. Existing destination authorization remains authoritative. Any proposed reconciliation of those access contracts requires a separate review.

## 4. Request and PDF guidance

Change guidance only within the existing request workspace, using its already available `$case`, `$definition`, `$saved`, `$identity`, `$reason` and `$document` values. Do not fetch request/document statuses on application home or invoke eligibility repeatedly to populate cards.

| Existing context | Proposed explanation / action |
| --- | --- |
| No accessible case | “No accessible case is shown. Start with an available application; a qualifying Director approval is required before submitting a demonstration-document request.” Existing application/start navigation only. |
| Approval absent or mismatched | Explain that the current exact submitted revision needs the existing final approval. Show the current service-provided reason; no nine-day lock or both-modules prerequisite. |
| Definition missing, withdrawn or mismatched | “The matching request form is not currently available. OSDS must publish a version for this application questionnaire.” Preserve submitted originals and existing availability checks. |
| Other `$reason` | Preserve the actual eligibility/permission/binding failure; never replace all failures with “awaiting form publication.” No free-text identity override. |
| Request draft | “Request draft saved — not submitted.” Retain existing Save issuance draft and Submit demonstration request handlers/conditions. |
| Request submitted, no document | “Your demonstration request is submitted and read-only. Authorized staff must explicitly prepare the PDF.” No applicant generation/repeated-request action. |
| Document `processing` | “Demonstration PDF preparation is in progress.” No completion promise, automatic retry or new timeout UI. |
| Document `failed` | “The PDF is unavailable. Authorized staff must inspect the attempt before retrying.” Preserve recovery rules and support information. |
| Document `completed` | “A stored demonstration PDF is available. No official certificate has been issued.” Preserve the existing download link and controller checks. Missing/corrupt bytes may still deny download and create an integrity incident. |
| Unknown document state | “Document availability could not be confirmed. Check the saved request or contact the administrator.” No claim that nothing was generated; no automatic replay. |

Do not change request state from submitted to completed. The document and request have different lifecycles. Do not equate presence of a saved draft with submitted-request readiness. Preserve notices for withdrawn saved versions and retained immutable originals.

## 5. Data and permission dependencies

- Staff entry still requires existing panel admission and active-account checks. A task card uses the destination's existing page `canAccess()` or resource access/policy method; never a role name or an invented permission.
- Additional responsibility text requires the relevant existing stage permission **and** accessible intake destination. A stage permission alone does not expose an intake shortcut.
- Reviewer assignment and region/module authority remain enforced by ReviewerQueueScope and stage services at the destination. Do not query assignments to claim dashboard readiness.
- Questionnaire management, review/publication, blank downloads, issuance definitions and case-PDF destinations have different access contracts. Reuse each one; do not replace them with a broad OSDS permission check.
- Resource shortcuts use their existing resource access checks and policies. Do not call list queries just to determine whether a shortcut should appear.
- Applicant home keeps its existing owner/representation query and loaded institution, manifest/module/retirement and working-copy relationships. Tracking keeps its existing case policy and history loads.
- Issuance availability and final mutation checks remain in CaseIssuanceService. Approved state alone does not establish representation, module/routing validity, matching definition, protected-answer bindings, evidence completeness or request-submit permission.
- Existing submitted request read access and private document retrieval remain under current authorization. Dashboard links never grant permanent access or bypass fresh download integrity checks.

**No new domain-data query, aggregate, eager load, polling loop, route, permission, role or lifecycle state is proposed.** Existing permission checks can use their normal permission infrastructure; this is not a claim of zero framework queries. Existing configuration count queries and request-view lookups remain unchanged. Any required additional data access is a scope-change stopping point.

## 6. Expected implementation files

This is the proposed runtime allowlist, not edits performed by this assessment:

| File | Planned change |
| --- | --- |
| `app/Filament/Pages/Dashboard.php` | Heading/subheading orientation only; retain shared dashboard route and widget discovery. |
| `app/Filament/Widgets/TaskOrientation.php` — new | Static destination catalogue, existing access checks, permission-based responsibility text and deterministic task ordering; no business-data queries. |
| `resources/views/filament/widgets/task-orientation.blade.php` — new | Accessible task cards, secondary administration links and no-shortcut empty state; render once through existing widget discovery. |
| `app/Filament/Widgets/WorkflowOverview.php` | Existing statistic labels/descriptions and meaningful zero-stat explanation; preserve count expressions and visibility contract. |
| `app/Support/ApplicantApplicationGuidance.php` — new | Pure shared state labels/copy; no service or database dependency. |
| `resources/views/components/applicant-status.blade.php` | Use explicit presentation labels, including demonstration final outcomes. |
| `resources/views/applicant/home.blade.php` | Per-state guidance, separate publication notice, existing actions and qualified request-workspace link. |
| `resources/views/applicant/track.blade.php` | Consistent guidance and qualified approved-case request link; preserve corrections/history/internal-note boundaries. |
| `resources/views/livewire/applicant/issuance-requests.blade.php` | Readable labels and separate request/document messages using already loaded values; existing handlers/queries unchanged. |
| `resources/css/theme/dashboard.css` — new | Scope layout styles to new dashboard/guidance wrappers; retain all shared tokens. |
| `resources/css/app.css` | New stylesheet import only. |
| `resources/css/filament/admin/theme.css` | Import the same scoped stylesheet only; preserve Phase 1 rules. |

Proposed verification additions after implementation approval: `tests/Unit/ApplicantApplicationGuidanceTest.php`, `tests/Feature/DashboardGuidanceTest.php`, `scripts/verify-phase3-dashboards.mjs`, `docs/osds-ui-ux-phase-3-dashboard-report.md` and separate evidence under `docs/screenshots/phase3-dashboards/`.

Controllers, Livewire request logic, policies, services, models, routes, seeders, migrations, stage-action partials, shared navigation, landing-page files, theme JavaScript and PDF templates are excluded. If implementation cannot stay within these boundaries, stop and propose a change rather than expanding scope silently.

## 7. Responsive and accessibility specification

- Staff task sections use one column on phones, two where text fits on tablets, and up to three at desktop widths; do not stretch a single card to resemble a full operational dashboard.
- Applicant cards place their action beneath the description on narrow screens; maintain institution/module/status context and existing timestamps. Keep primary actions clear without making each card contain multiple competing primary buttons.
- Test 375, 768, 1024 and 1440px in light/dark, plus 320px-equivalent reflow and 200% text scaling. Use flexible widths and wrapping for long institution names, translated-style long labels and combined-permission descriptions.
- Reuse Filament and existing portal components, semantic headings, native anchors/buttons and current focus tokens. Do not make whole nested cards into overlapping links.
- Aim for 44px control height, visible keyboard focus, complete accessible names, sufficient text contrast and readable non-color status labels.
- Static explanatory text is not a live region. Preserve appropriately scoped save/error feedback; do not announce the entire dashboard or auto-focus it on every Livewire update.
- No new charts, automatic refresh, animation, drawer, theme control or browser-storage behavior. Preserve existing notices and recovery feedback.

## 8. Complexity, risks and delivery order

| Slice | Complexity / risk | Mitigation |
| --- | --- | --- |
| Staff task widget and configuration copy | Medium / medium | Reuse destination access contracts; one shared intake link; verify combined and absent permissions; leave query semantics intact. |
| Pure state guidance and applicant cards/tracking | Medium / medium | Explicit state matrix and publication notice precedence; no new mutation buttons or inferred B1 substage. |
| Request/PDF explanatory copy | Low–medium / medium | Preserve request/document separation, immutable snapshots, actual service reasons and download/recovery checks. |
| Responsive styles | Medium / medium | Scoped wrappers/imports; recheck Phase 1 navigation and Phase 2 landing appearance; no token edits. |
| Authenticated verification | Medium–high / authorization-dependent | Isolated explicit fixture authorization, identity assertions and real UI checks; retain failed attempts and existing evidence. |

Implement in that order after approval, stopping for any conflict with existing access/workflow contracts. Avoid changing every screen at once. Plan review does not authorize coding or execution.

## 9. Verification and acceptance criteria

### Before implementation

Record the actual Git state and working-tree hashes, Phase 1/2 outputs, pending remediation, the ten published historical evidence JSON files, source artifacts, original PDF bytes and the unrelated Batch G script. Do not replace earlier reports or results.

### Authorized database-free verification

- Unit-check the pure state presenter for every listed state, publication-ended combinations and unknown-state fallback without bootstrapping the application/database.
- Check task catalogue metadata, destination reuse and static code boundaries. This is not proof of actual account permissions.
- Create explicitly labelled component previews for no-task, review-only, configuration-only, combined-permission and applicant guidance layouts. Preview data must not be represented as real authorization or workflow evidence.
- Run default axe plus target-size checks without exclusions, preserve incomplete findings, verify keyboard order/focus, contrast, wrapping, touch targets and overflow at all required sizes/themes.
- Run applicable syntax, Pint, frontend build and whitespace checks. Compare hashes after verification. No database-capable test runner may execute merely because a test is called “unit”.

### Separately authorized authenticated verification

Before any database-backed test, fixture operation or migration-capable preparation, obtain explicit approval identifying operations and databases. If unavailable, mark these checks **BLOCKED**. Do not access or activate `osds_dev`. If authorized, use only `osds_test` for PostgreSQL regression and `osds_ui_browser_test` for browser fixtures with configured/actual identity assertions.

Required scenarios:

1. Configuration staff, evaluator, Chief, Director, evidence reviewer, read-only, combined, no-task and inactive/revoked-permission combinations. Verify destination visibility and unchanged direct-route denial. Stage permission without intake permission must not expose a shortcut.
2. Every application lifecycle state, retired/ended publication combinations, missing working copy, empty list, long institution names and preserved correction instructions. Approval must not automatically produce an eligible request action.
3. Existing B1 same-person separation and wrong-region/module denial; dashboard copy must not bypass case authorization or advertise an assigned-case count.
4. Missing/withdrawn/mismatched issuance definition, revoked submit permission, protected identity binding failure, saved withdrawn-version draft and retained submitted original. Preserve actual service reasons.
5. Request draft/submitted and document absent/processing/failed/completed; duplicate prevention and missing/corrupt-file download denial remain unchanged. No automatic retry or regeneration.
6. Both COR and ACSA real UI journeys through affected entry points, preserving existing application/request handlers and PDF checksums.
7. Appropriate targeted tests, followed by the complete current-tree PostgreSQL regression suite for runtime changes, only after the required database-writing authorization. Report actual totals, not inherited B1–B4 passes.

Complete technical acceptance requires truthful labels, no new business-data queries, correct permission-filtered destinations, no workflow/route changes, successful authorized regressions and preserved evidence/PDFs. Database-free success alone is partial acceptance. Human screen-reader, native zoom and first-time-user review are separate acceptance activities, not inferred from axe.

## 10. Supervisor/business confirmation and stopping points

Confirm before final business acceptance:

- Whether the proposed terminology and explanations are understandable to HEI/association representatives and CHED staff. COR's official name remains unresolved; this plan does not settle it.
- Whether the evaluator, Chief and Director descriptions accurately communicate the approved synthetic responsibilities. This does not reopen or change the B1 rules.
- Whether the present configuration-wide counts should remain visible as currently authorized. Requests for regional/personal workload counts require separate data/authorization design and approval.
- Which contact or office should be named for access problems; use generic administrator guidance until approved contact details exist.
- Who will perform supervisor review, screen-reader/zoom checks and first-time-user acceptance, and when database-backed verification will be authorized.

No new nine-day issuance restriction, two-module prerequisite, appeal/reopening behavior, official certificate validity or production readiness is proposed. Phase 1/2 pending authenticated acceptance is not silently closed by this plan.

**Stop after this assessment and plan. No Phase 3 runtime implementation or database execution is authorized by this document.**
