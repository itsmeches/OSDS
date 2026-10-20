# Phase 3D.7 - Batch F Admin, Super User and shared refinement

**20 October 2026. Verdict: CONDITIONALLY READY for user review and development progression only.** SEC-007 and SEC-008 targeted gates passed before ordinary refinement resumed. Batch G/H, Phase 4 and production remain excluded. Historical discovery/stop evidence is retained in the issue register and the two remediation documents.

## Scope and data preservation

No migration, role hierarchy, new permission, historical repair, official decision or production access was introduced. Existing target-account predicates, effective regional/nationwide scope, representation, exact review pairs, UTC sessions, encrypted evidence and immutable application/publication history remain authoritative. Automated tests use only osds_test and osds_ui_browser_test; manual-testing databases were not inspected or changed. Existing staged changes are preserved.

## Before and after

| Area | Before | Implemented refinement |
| --- | --- | --- |
| Credentials | Generic profile Save could bypass target authority. | SEC-007 fresh target-specific credential/email authorization, transactional whitelist persistence and response redaction. |
| Delegated assignments | Generic Edit allowed self-extension and direct date persistence. | SEC-008 canonical delegation checks, fresh authority, locked date-only write and stale fingerprint. Own/unauthorized edits explain read-only state; forged Save remains server-denied. |
| Configuration review | Conditions/instructions were difficult to inspect; returned reviews displayed later mutable draft content. | Complete supported condition summaries and exact-pair guidance; recorded returned-review findings remain distinct from current draft content. No new historical snapshot is invented. |
| Publication | Audience was omitted, applicant rows offered an unsupported staff-preview link, generic Submit wording was unclear. | Separate audience labels/filter and supported actions; explicit publication confirmations; applicant date availability and retained retirement history. Approval alone does not grant applicant access. |
| Shared staff preview | Technical references occupied the primary heading area. | References remain available under Configuration details, leaving the questionnaire and nonofficial notice prominent. |
| Failure recovery | Raw unexpected exception messages could reach notifications; unknown responses gave little recovery direction. | Safe support-reference/class diagnostics, inline validation, retained modal input, local interrupted/session guidance and an authoritative saved-record link. No automatic consequential replay. |
| SCHOOL verification | A regional Users list could not find a newly represented SCHOOL account. | The existing verification operation is available on its scoped Institution representatives row. Users scope remains unchanged; creator/verifier separation and separate eligibility are preserved. |
| Grant status | No revocation date was described as Active even for future/expired assignments. | Display-only Current/Scheduled/Expired/Revoked labels across existing grant resources; policies still decide access. |
| Account creation | Reactive account-type changes could clear an already typed password; real runs also caught an undefined native reveal factory. | Account type/scope come first and credentials last. Create uses the native fixed password control; Edit retains reveal. Credential response redaction remains intact. A failed grouping experiment was removed. |
| Mobile tables | A read-only table had no keyboard-reachable horizontal scroll area; nullable fields rendered empty record links. | Scoped native table hook adds a named focusable region and shared-token focus ring; plain missing-value placeholders give links accessible text. No data/query replacement. |
| Lookup work | Publication rendering repeated retirement queries; region selectors loaded and authorized each row. | Eager retirement/module relations and canonical SQL-scoped selectors, retaining the same authorized options. |

## Source inventory and interaction boundaries

Inspected User resource/create/edit/table actions, policies and AccountGovernance; regional/representation resources, selectors, AccessGovernance and policies; exact configuration review/detail/publication services and policies; publication list/retirement; Admin dashboard and permission-gated overview; separate 3C/3D reviewer-assignment resources and eligibility; audit/region/institution resources and StaffListScope; shared panel/session/error/theme behavior; read-only module/provider catalogues. No standalone role editor, export, bulk action, API or relationship manager exists in these inspected resources, and none was added.

SEC-008 reuses current explicit delegation permissions, existing target-role predicates and effective scope. It never permits self-delegation, rebinding, attribution/verification tampering or expired/revoked revival through Edit. The returned saved row is acknowledged under its own version, so an unseen later writer is not silently acknowledged. Original real Filament regressions for SEC-007/008 are retained.

## Final verification

| Gate | Observed result |
| --- | --- |
| Complete settled-source PostgreSQL suite | **140 tests / 1,815 assertions passed**, retaining SEC-001-008, C-INT-001 and E-INT-001/002 regressions. |
| Focused assignment/credential security checkpoint | 18 tests / 216 assertions passed before ordinary Batch F; the final full suite includes these tests. |
| Complete staff browser journey | **15 actual interaction checks / 94 screen checks passed**; 34 native horizontal keyboard-scroll checks; zero reported axe findings, page overflow or uncaught page errors. |
| Shared pages | Three checks / 24 light-dark desktop-mobile screen checks; six native keyboard-scroll checks; zero reported axe/overflow/page errors. |
| Final staff-preview disclosure | One check / four current-template screens; native summary focus and Enter opening passed. |
| SEC-007 browser rerun | Five checks / four screens; zero reported credential-response leaks or page errors. |
| SEC-008 browser rerun | Six checks / four screens, including stale tabs, reordered committed responses and revoked scope; zero reported page errors. |
| Account-type probe | Three earlier real rapid/delayed/repeated SCHOOL-to-OSDS-to-SCHOOL cases preserved final visible/client/persisted SCHOOL role. No role ordering adapter was changed. |
| Applicant/builder browser preservation | Original plus 11 applicant integrity cases and original plus 14 builder isolation cases passed at the SEC-008 checkpoint. No applicant/builder ordering JavaScript was changed during F. |
| Laravel Pint / production Vite / working-tree diff | Passed. Existing line-ending advisories and pre-existing staged whitespace are preserved. |

### Pages and real interactions

The main browser journey uses Admin configuration review/list/detail, approve/request-changes modals, separate staff/applicant publication and retirement, audience filtering, returned-review history, dashboard, Users, Regions, Institutions, Institution representatives, Regional assignments, Audit history, eligibility and both separate reviewer-assignment lists. CHEDRO creates a SCHOOL account; its creator cannot verify it; a different authorized verifier does so; the new SCHOOL account signs in without automatic eligibility. Super User routes cover Users, Regions, Institutions, representations, assignments and audit, plus explicit nationwide scope without missing questionnaire/reviewer permissions. Native search, no-results state, pagination, account creation controls, System theme keyboard activation and session-expiry recovery are exercised.

Actual consequential behavior includes required review-reference validation, double-click approval with one decision, a publication response dropped after persistence, authoritative saved-state recovery without replay, exact approved pair preservation, retirement history, and a returned draft edit not misrepresented as the reviewed copy. Expired-session testing uses a valid form after all prior requests settle; credentials and request values are not written to artifacts. No automatic retry is introduced.

Shared checks cover public home, staff sign-in, approvals list, supported read-only staff preview, and authorized OSDS module/provider catalogues. Detailed new/edit permutations for every institution, region, grant and catalogue field were source-audited or server-tested, not all browser-mutated. They remain in the human retest checklist; no nonexistent export, bulk API or role editor is claimed tested.

### Contrast and keyboard results

Actual rendered stable-screen samples use computed colors and ancestor background compositing; axe also inspects applicable contrast. Representative measured text pairs:

| Sample | Light | Dark |
| --- | --- | --- |
| Native action text, across sampled actions | 5.09-19.90:1 | 4.64-15.55:1 |
| Inline notice text | 6.15:1 | 12.03:1 |
| Form labels | 18.35-19.90:1 | 17.74-18.72:1 |

These sampled normal-text pairs exceed 4.5:1. Shared primary actions separately measure 6.57:1 light / 8.72:1 dark. This is representative measured evidence, not certification of every state or full WCAG conformance. The runner skips compositing samples with gradients/group opacity, waits finite animations, and closes notifications using native controls before stable measurements. Timed animation contrast is not certified.

Desktop 1440px and mobile 375px are checked throughout; the pending review also has 768px tablet checks. Keyboard results include native menus/System switching, disclosure opening, search/pagination and arrow-key table scrolling. Page-level overflow is zero at the tested widths; wide tables intentionally scroll internally. Native 200% zoom and screen-reader acceptance remain open.

### Retained failures and harness corrections

The initial full attempts found real keyboard-scroll and empty-link defects, retained in first-keyboard-failure.json, first-empty-link-failure.json and first-institution-link-failure.json. Password reveal failures are retained in password-reveal-first-failure.json. A trial Group did not fix the latter and was removed; Create now uses the native fixed password input, while Edit retains reveal. No incorrect credential persistence or exposure was established by that UI finding.

Other unsuccessful attempts used incorrect control selectors, typed before staff initialization, dismissed notifications before the action response settled, or removed cookies with an outstanding request. These were harness synchronization errors, not accepted application results. The final run waits actual request/initialization boundaries and asserts the complete workflow. Debug runs are not counted as accessibility verification.

### Screenshots and reproduction

| Representative page | Light desktop | Dark mobile |
| --- | --- | --- |
| Exact configuration review | [Image](screenshots/phase-3d7-f/review-pending-light-1440.png) | [Image](screenshots/phase-3d7-f/review-pending-dark-375.png) |
| SCHOOL account creation | [Image](screenshots/phase-3d7-f/school-account-create-light-1440.png) | [Image](screenshots/phase-3d7-f/school-account-create-dark-375.png) |
| Regional assignments | [Image](screenshots/phase-3d7-f/admin-regional-assignments-light-1440.png) | [Image](screenshots/phase-3d7-f/admin-regional-assignments-dark-375.png) |
| Publication audiences | [Image](screenshots/phase-3d7-f/publication-audiences-light-1440.png) | [Image](screenshots/phase-3d7-f/publication-audiences-dark-375.png) |
| Staff preview | [Image](screenshots/phase-3d7-f/preview-final/staff-preview-light-1440.png) | [Image](screenshots/phase-3d7-f/preview-final/staff-preview-dark-375.png) |

Each directory also contains the opposite light/dark mobile/desktop pair. Developer screenshot inspection includes light desktop review, dark mobile creation/assignments/publications/preview. Images contain fictional data; external avatars are deliberately blocked. This is not human usability acceptance.

Use the existing isolated browser router on port 8017, then run node scripts/verify-staff-journey.mjs. It prepares new disposable fixtures through guarded scripts. Run node scripts/verify-staff-shared.mjs and node scripts/verify-staff-preview.mjs while its supported staff publication exists. Security reruns use verify-account-security.mjs and verify-assignment-security.mjs. Never point these tests at a manual-testing database. Machine-readable results are in the linked screenshot directories; performance.json contains only safe counts/timing.

## Isolated lookup measurements

The measurement script compares the previous source access pattern with the current pattern against the same disposable data, retaining only counts/timing, not SQL or record values. Both region selectors assert identical authorized ID sets before discarding those IDs from the artifact.

| Workload | Previous | Current |
| --- | --- | --- |
| Ten publication rows; three retirement checks per row | 32 queries; 14.202ms elapsed | 3 queries; 2.339ms elapsed |
| Regional account selector; one permitted region | 151 queries; 69.125ms | 3 queries; 1.555ms |
| Explicit nationwide selector; 75 permitted regions | 76 queries; 31.905ms | 3 queries; 4.681ms |

The final browser run also recorded 27 GET status/timing checks: TTFB 597.3-2,513.2ms, median 1,003.2ms, on the local PHP development server. These are local observations, not production percentiles or capacity guarantees. The current pattern reduces lookup work without broadening the authorized options. Evidence: screenshots/phase-3d7-f/performance.json.

## Limitations and review boundary

No human usability, native 200% zoom, screen-reader or complete WCAG conformance is claimed. Stable-screen contrast sampling is representative; timed animations, all cell/control permutations and every browser engine remain open. Tables retain internal horizontal scrolling on narrow screens rather than hiding columns. External avatar requests are deliberately blocked in the local-only browser matrix. No real email delivery, password recovery redesign, historical credential/timestamp impact assessment or production certification was performed.

See [manual retesting guide](phase-3d7-batch-f-manual-retesting.md). Pause for user review before Batch G. Batch H, Phase 4 and deployment remain unauthorized.
