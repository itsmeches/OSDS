# Phase 3D.7 — Batch C applicant experience

**Date:** 19 October 2026 (Asia/Manila)  
**Scope:** SCHOOL applicant presentation, feedback and recovery using existing services. Batch D and Phase 4 are not started. No migration, permission, eligibility, representation, encryption, routing or lifecycle-policy change.

**Status: C-INT-001 remediated — CONDITIONALLY READY for Batch C review.** The original critical stop remains documented below as historical evidence. The original reproduction, expanded matrix, full post-fix applicant journey and regression gates pass. This returns Batch C for the user's review; it does not claim human usability acceptance, production readiness or permission to begin Batch D. Batch D and Phase 4 remain unstarted.

## Pages and changes

| Screen | Before | After |
| --- | --- | --- |
| Shared navigation | Home and My applications duplicated the same destination; full mobile sidebar occupied most of the first screen. | Three task destinations, a compact mobile Menu, keyboard/Escape behavior, a skip link, and preserved Light/Dark/System controls. |
| My applications | Technical state names and little next-step guidance. | Plain-language state labels, next-action guidance, confirmed answer-save time, correction/resume actions, and a route to view closed drafts. |
| Start application | Questionnaire choices came from the union of all represented organizations' grants. | Explicit organization selection narrows choices to that organization's effective grants. Empty availability is explained. Domain services still reject forged organization/manifest combinations. |
| Questionnaire | Tall mobile steps; unclear local/persisted distinction; no leave warning. | Compact current-step summary with All steps, persistent in-memory answers across steps/conditions, explicit Save draft, navigation warning and confirmed-save feedback. |
| Documents | Generic instructions; error recovery varied. | Per-document accepted providers, allowed counts, add/replace/remove guidance, preserved invalid input, confirmed per-link feedback, and saved-answer prerequisite for applicability checks. |
| Review and send | Counts alone; no saved-answer summary; unsaved-data guard depended on button presentation. | Read-only saved-answer disclosure, clear test-copy consequences, server-side unsaved-answer feedback and explicit correction-send wording. No official submission is added. |
| Tracking | Mostly a status/history list. | Explains who acts next, confirms that a sent copy need not be sent again, and separates support reference from official tracking. |
| Corrections | Instructions were on tracking but not beside the correction editor. | Requested changes appear in both locations; saved corrections follow the existing new-working-copy/resubmission lineage. Earlier submissions stay immutable. |
| Account | Verification label omitted effective dates; no empty state. | Distinguishes awaiting verification, future, expired, revoked and current representation, with a helpful empty state. No grants are modified. |

The collapsed mobile navigation measures **76 CSS pixels high** in the browser at 375px, in both themes. The Batch B mobile screenshot had the full navigation above the content. Current step and next action remain available without expanding the main menu. All steps remain keyboard-accessible and can be revisited.

## Confirmed defects and fixes

1. **Premature/stale local save feedback:** local input is marked unsaved immediately; confirmed state is reconciled after Livewire finishes rendering the server result. A request in progress or an unconfirmed response does not display a successful action.
2. **Unconfirmed retries:** answer/evidence request keys previously originated inside the action. Keys now originate in locked component state and remain stable for the same payload/version when the response is lost. Keys rotate only in a confirmed response. Existing transactional idempotency tables and locks are unchanged. The UI blocks editing during uncertainty and offers an explicit retry of the same action; there is no automatic consequential retry.
3. **Lost input on expired-session redirects:** this installation redirects unauthenticated Livewire requests to sign-in, in addition to possible 419 responses. Both paths now show safe recovery guidance without automatically leaving the editor. Users can reveal answers to copy before signing in/reopening. Successful authentication does not automatically replay an old operation.
4. **Unclear failures:** validation messages preserve input; unexpected component-action failures show a support reference. Diagnostics contain only that reference and exception class, excluding exception messages, SQL bindings, answers and locators. HTTP authorization/not-found exceptions retain their status behavior.
5. **Progress against unsaved data:** checking progress or sending while questionnaire answers are unsaved now explains that they must first be saved. Successful saves clear stale completeness results; a new check uses persisted answers.
6. **Provider ambiguity:** document cards name their configured accepted providers; syntax validation is explicitly distinct from access/authenticity.

**C-INT-001 was a critical data-integrity blocker discovered during final inspection.** The pre-fix reproduction showed the last intended Yes becoming No before save and remaining No after reload. Targeted synthetic tracing then confirmed overlapping Livewire snapshot reconciliation against moving canonical/update objects as the cause. Applicant model messages are now ordered through the request-finish lifecycle and use detached JSON update values for reconciliation. There are no correctness sleeps, new retries, weakened conditions, or server persistence changes. See [the remediation report](phase-3d7-c-int-001-remediation.md) for exact mechanics and evidence. SEC-001–004 and submission immutability remain intact.

The expanded matrix also found that a new multiple-choice checkbox group initially bound to a boolean. The backend safely rejected it. Initializing unanswered multiple-choice controls to their existing empty-array type fixes that binding without changing required validation. A new PostgreSQL test checks both empty required selections and saved option arrays.

## Browser verification

Local Chrome, Playwright and axe-core use **only `osds_ui_browser_test` on port 8017**. The fixture control refuses any other database and refuses cached configuration. It creates new synthetic configurations/organizations through existing services. Active publications in this disposable browser database are retired through the publication service to make room for the next test publication; earlier data is not deleted. This does not affect `osds_sandbox` or `osds_dev`.

The browser journey exercises real forms and Livewire requests:

- Organization-specific questionnaire choices and draft creation.
- Required-answer/document validation, conditional hide/show, and unsaved answers surviving step changes.
- A real before-unload prompt that cancels leaving without losing input.
- Save, reload and resume.
- Two actual tabs: first save succeeds; stale save fails while preserving local input for copying.
- Aborted network requests and an explicitly injected HTTP 503: no false success; input remains; explicit retry succeeds.
- A request sent to the server whose response is deliberately dropped **after persistence**: retry has no second draft-version increment.
- Invalid evidence, valid Google sharing links, replacement, removal and re-addition.
- Submission persisted with its response deliberately dropped: retry produces one submission and redirects to tracking.
- Existing CHEDRO correction services create a request; the SCHOOL browser edits and resubmits; database checks prove two submission copies and the unchanged original answer.
- Actual session-cookie loss: expired-session recovery, no false save and RAM-only answer-copy recovery. Browser local/session storage contains no test answer.
- Expired and revoked representation fixtures: real tracking URLs return 404; restoring the disposable grant restores authorized access.
- Retired publication with an open working draft: saved answers remain visible, inputs are disabled and Save is unavailable.
- Keyboard menu opening, Escape/focus return, and System preference changes in both directions.

Network failures are deliberately injected by Playwright; they are real failed requests, not merely CSS loading-state samples. Correction requests, representation date/revocation probes and publication retirement are arranged by the isolated CLI fixture control; this is not a claim of human CHEDRO testing or Batch D interface verification.

The pre-remediation general journey recorded **18 passing interaction checks and 67 screen checks**, with zero axe findings, zero page-level overflow and zero uncaught page errors. Those checks were insufficient for answer-integrity acceptance: they omitted the final Yes/No value after save/resume. The original reproduction retains its last-Yes assertion and now additionally checks the direct database value, selected radio and conditional child after reload. The full journey now asserts the resumed Yes and the correction copy's Yes as well. The expanded matrix checks selection, component state, database values, reload and applicability in every complete case. Before-fix failure artifacts remain preserved alongside after-fix results.

**Final post-fix results:** original targeted reproduction passed; **11 expanded answer-integrity cases passed**; the complete applicant journey passed **18 interaction checks and 67 screen checks**, with **zero axe violations, zero page-level overflow findings and zero uncaught browser errors**. Actual session loss, HTTP 503, committed-response loss, corrections/resubmission, expired/revoked representation and retired drafts were exercised again after the fix.

| Last-answer evidence | Before fix | After fix |
| --- | --- | --- |
| Yes → No → Yes, final selection/client | No (incorrect) | Yes |
| Save payload / persisted / reload | No / No / No | Yes / Yes / Yes |
| Conditional activity details | Hidden incorrectly | Visible; earlier child text retained |
| Reverse No → Yes → No | Not part of original stop reproduction | No selected, persisted and reloaded; child excluded from applicability |

Before/after screenshots: [incorrect No after reload](screenshots/phase-3d7-c/answer-race-before-reload.png) → [correct Yes after reload](screenshots/phase-3d7-c/answer-race-after-reload.png). Detailed values and network-release order are recorded in [the answer matrix](screenshots/phase-3d7-c/answer-matrix.json); [the remediation report](phase-3d7-c-int-001-remediation.md) maps the required coverage and lists exact files.

## Screenshot review

| Screen | Light | Dark |
| --- | --- | --- |
| Questionnaire, mobile | [Image](screenshots/phase-3d7-c/questionnaire-light-375.png) | [Image](screenshots/phase-3d7-c/questionnaire-dark-375.png) |
| Start, desktop | [Image](screenshots/phase-3d7-c/start-application-light-1440.png) | [Image](screenshots/phase-3d7-c/start-application-dark-1440.png) |
| Documents, mobile | [Image](screenshots/phase-3d7-c/document-links-light-375.png) | [Image](screenshots/phase-3d7-c/document-links-dark-375.png) |
| Review, desktop | [Image](screenshots/phase-3d7-c/review-and-send-light-1440.png) | [Image](screenshots/phase-3d7-c/review-and-send-dark-1440.png) |
| Correction editor, desktop | [Image](screenshots/phase-3d7-c/correction-editor-light-1440.png) | [Image](screenshots/phase-3d7-c/correction-editor-dark-1440.png) |
| Corrections sent, mobile | [Image](screenshots/phase-3d7-c/corrections-sent-light-375.png) | [Image](screenshots/phase-3d7-c/corrections-sent-dark-375.png) |

The screenshot directory also includes tablet (768px), account, tracking, conflict and retired-draft images. Screenshots use fictional applicant data and do not show evidence URLs, encryption keys, passwords or manual-testing records. External requests are blocked in the browser matrix.

## Regression gates and reproduction

- Focused PostgreSQL applicant suite after the first implementation: **28 tests, 488 assertions passed**.
- Complete PostgreSQL suite after remediation: **88 tests, 989 assertions passed**, including SEC-001–004. Focused applicant tests: **29 tests, 495 assertions passed**. The additional array-binding test checks required validation and persistence/resume of option arrays.
- Production build, final Laravel Pint check and complete PostgreSQL suite passed after the UI changes. `git diff --check` passed with existing line-ending advisories.
- The original reproduction and **11 complete expanded browser matrix cases pass**. The original failure and the insufficient first fix were not skipped: the empty-delta overlap was separately diagnosed and corrected. Full post-fix journey: **18 interaction checks / 67 screen checks passed**, with zero axe/overflow/uncaught-error findings.
- No migration was introduced; existing PostgreSQL constraints/triggers remain exercised by the full suite. No clean/reset migration was run against a manual-testing database.

Use the development shell and existing isolated browser-server router from Batch B. Prepare with `php scripts/Prepare-ApplicantJourney.php prepare`, then run `node scripts/verify-applicant-journey.mjs`. Preparation and the browser test change **only disposable synthetic browser fixtures**. The browser server must stay on port 8017; do not point the test script at the manual sandbox.

Run `node scripts/reproduce-applicant-answer-race.mjs` for the targeted regression. It provisions a new isolated fixture, records synthetic Yes/No transitions and the reloaded result, and exits unsuccessfully while last-answer preservation fails. It does not repair submissions or touch manual data.

Run `node scripts/verify-applicant-answer-matrix.mjs` for the full rapid-answer matrix. `--trace` on either script instruments only synthetic browser fixtures. The tests explicitly hold responses: same-component overtaking is prevented by serialization; independent tabs still receive deliberately reordered replies and retain optimistic-lock conflicts.

## Limitations and review boundary

- This is automated browser/axe verification and developer screenshot inspection, not human usability testing or a complete WCAG assessment.
- Native browser zoom, screen readers, real Google document accessibility, and every supported field/type/error permutation are not claimed browser-tested. Existing server suites cover additional authorization/lifecycle combinations.
- Unsaved data is kept only in the open tab. Closing/reloading/crashing the browser loses unconfirmed data; no sensitive browser-storage recovery is introduced. The explicit copy action includes questionnaire answers only, not evidence URLs. Applicants should avoid shared clipboards/devices and keep the tab open until recovery is complete.
- Session recovery cannot restore a destroyed session automatically. Copy local changes before signing in and reopening; then apply them to the latest authorized saved copy.
- Start-draft duplicate policy and create-request handling are unchanged. A duplicate existing application is rejected by the domain service; a lost create response can be resolved through My applications.
- Third-party availability, real sharing permissions and human first-time SCHOOL usability remain pending. Other Batch A issues retain their assigned batches.

**Batch C is returned for user review; pause before Batch D. Phase 4 remains unstarted.** No migration or policy change was required. The adapter is specific to the installed Livewire v4.4.7 lifecycle and must be reverified when upgrading that dependency. Manual retest steps are in `docs/phase-3d7-batch-c-manual-retesting.md`. Editor diagnostic integration was unavailable; verification used the actual browser, PHP/PostgreSQL suite, Pint, build and diff checks rather than claiming an IDE diagnostics pass.
