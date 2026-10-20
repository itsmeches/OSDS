# Phase 3D.7 — Batch D CHEDRO refinement

**Date:** 19 October 2026 (Asia/Manila)  
**Status: CONDITIONALLY READY for development review.** SEC-005 and SEC-006 are remediated; manual usability, native zoom/screen-reader checks and historical timestamp assessment remain open. This is not production approval. Batch E and Phase 4 have not started.

## Security remediation

**SEC-005:** Current/historical intake and related CHEDRO presentation helpers now use request-local computed properties. Protected builders cannot be called as public actions. Explicit answer permission is checked before answer queries; only applicable answers load. Evidence-opening and finding-history relationships retain their separate permissions. No loaded aggregate is stored in public component state.

The original reproduction passes (1 test / 12 assertions). Expanded tests inspect actual forged HTTP responses, initial snapshots and subsequent Livewire effects, current/historical authorized views, permission/assignment revocation, hidden answers and unchanged submitted hashes/history. Full details, affected methods, response rejection behavior and rationale: [SEC-005 remediation](phase-3d7-sec-005-remediation.md).

**SEC-006:** Screenshot inspection exposed an eight-hour PHP/PostgreSQL mismatch; explicit-instant tests confirmed real expired assignments could authorize both queues. Targeted remediation now sets Laravel's PostgreSQL session `timezone` to UTC through the supported connector configuration. Both original regressions and the expanded real-clock/boundary matrix pass. No comparison policy, column type, host/server time zone or stored timestamp was changed. See the [UTC contract and read-only historical assessment](phase-3d7-sec-006-remediation.md) and [issue register](phase-3d7-issue-register.md). Historical record correctness is **not** certified by this fix.

## Implemented CHEDRO improvements

| Area | Before | Implemented |
| --- | --- | --- |
| Separate queues | Unbounded collections and repeated list/count calls | Workflow-specific guarded queries; literal organization search, service/region/status filters, ten-record server pagination and totals |
| Queue navigation | Uneven cards and weak empty guidance | Named Previous/Next controls, reset/refresh actions, scoped options and meaningful no-match states |
| Mobile queue | Filter controls occupy most of first viewport | Search stays visible; optional filters collapse into a disclosure; table scroll is contained, labelled and explained |
| Application answers | Machine-key labels and title-cased free text | Exact published labels/options and shared read-only renderer; free-text case preserved |
| Submitted history | Latest copy only | Exact submitted-copy selector; earlier answers/document summary read-only; historical link opening remains unavailable under the existing latest-copy-only route |
| Screening/corrections | Sparse guidance and uneven errors | Plain status labels, exact question/document targets, error summary and inline feedback, confirmed correction state and sent-instruction history |
| Evidence reviews | Repeated organization/module queries and implicit defaults | Eager-loaded authorized metadata, clear test-copy context and persistent nonofficial notice |
| Findings | Mismatched UI/server defaults and little history context | Explicit check/result selection; requirement or exact-document target; reviewer attribution, remarks and append-only history |
| Failure recovery | Inconsistent pending/unconfirmed behavior | Safe support references; retained local remarks/instructions; no automatic consequential retry; explicit refresh to inspect saved status/history |
| Accessibility | Default pager invalid ARIA; observed notification contrast failure; mobile overflow | Named native pagination buttons; semantic text token for notification body; contained screen-reader text prevents table content expanding page width |

Phase 3C document test reviews remain separate from Phase 3D application screening. No official decision, new domain transition, permission definition, eligibility policy, encryption change, migration or immutable-data repair was introduced.

## Files changed

- `config/database.php`: central UTC PostgreSQL application-session contract (SEC-006)
- `app/Filament/Pages/SyntheticApplicationIntake.php`, `SyntheticEvidenceReviews.php`, and shared `Concerns/ScopesReviewerQueue.php`
- Relationships in `Application`, `ApplicationSubmissionAnswer`, `SyntheticEvidenceReviewCase`, and `SyntheticEvidenceReviewFinding`
- Both CHEDRO page views; `components/reviewer-queue.blade.php`, `reviewer-feedback.blade.php`; shared `theme/components.css`
- PostgreSQL/Livewire tests in `ApplicantSandboxTest.php`; new failing `TimestampCoherenceTest.php`
- Isolated `Prepare-ChedroJourney.php`, `verify-chedro-journey.mjs`, and read-only layout diagnostic script

## Actual final browser results after SEC-006 remediation

Local Chrome/Playwright on **port 8017 / osds_ui_browser_test** completed **12 interaction checks**:

1. Intake search, service filter, result counts, pagination, reset, empty result and keyboard navigation.
2. The equivalent separate evidence queue checks.
3. Authorized application document-opening route; external Google content blocked.
4. Screening, required correction validation and a response deliberately lost **after** the correction commits; recovery finds exactly one correction request without automatic replay.
5. SCHOOL correction editing, save/resubmission and unchanged earlier submitted answer; staff can select the earlier copy.
6. Separate evidence document-opening route.
7. Explicit finding validation and three finding types with reviewer attribution/history.
8. Aborted finding request: no false success, local remarks remain, no automatic retry.
9. Actual signed-in reviewer with explicit-offset assignments expired **one minute ago**, confirmed expired by PostgreSQL, receives no queue records and is denied both direct case URLs.
10. Actual signed-in reviewer lacking queue permission receives 403 and no case disclosure.
11. Revocation removes rows/counts/filter choices; copied case URLs remain denied.
12. New review/finding timestamps agree with the real database clock: UTC session, observed binding skew **0.078041 seconds**, and finding time **97 seconds after** its reviewed copy in the final fixture.

The earlier pre-remediation browser run used day-wide relative expiry dates and missed SEC-006. It is superseded by the explicit-instant near-boundary run above. Controlled PostgreSQL tests additionally cover exact-end, future/current/open-ended/revoked assignments, expiry after Livewire mount, staff regional grants, SCHOOL tracking/drafts and eligibility. Existing exact-pair and wrong-region/module tests remain green.

The final browser run checked **30 stable screens** across light/dark at 1440, 768 and 375 pixels: zero reported axe violations, page overflow or uncaught page errors. Machine-readable final evidence is `screenshots/phase-3d7-d/results.json`; `queue-layout-results.json` records an earlier separate 12-check layout retest. A first SEC-006 rerun caught a contrast threshold during a toast's disappearing animation; its screenshot showed the toast already gone. That attempt is retained in `sec006-first-browser-run.json`. Final measurements use the normal close controls to dismiss transient toasts before the multi-viewport scan; inline validation errors remain tested. Contrast throughout a timed fade is not certified.

Developer screenshot inspection found SEC-006 despite earlier green automated checks; new screenshots show coherent copy/finding times. Browser screenshots are not human usability testing, native zoom or screen-reader verification. External avatar requests are blocked in the synthetic browser harness, so some screenshots show the unavailable avatar image.

## Contrast and screenshot evidence

Measured rendered text/background minima from the full browser matrix:

| Sample | Light | Dark |
| --- | ---: | ---: |
| Primary action | 6.57:1 | 8.72:1 |
| Input/select/textarea text | 12.34:1 | 15.28:1 |
| Table cells | 12.34:1 | 15.18:1 |
| Test notice | 6.15:1 | 12.03:1 |

These measured samples exceed the normal-text 4.5:1 target. They do not constitute full WCAG conformance or verification of every state/control.

| Screen | Light | Dark |
| --- | --- | --- |
| Intake queue, desktop | [Image](screenshots/phase-3d7-d/intake-queue-light-1440.png) | [Image](screenshots/phase-3d7-d/intake-queue-dark-1440.png) |
| Intake queue, mobile | [Image](screenshots/phase-3d7-d/intake-queue-light-375.png) | [Image](screenshots/phase-3d7-d/intake-queue-dark-375.png) |
| Evidence queue, mobile | [Image](screenshots/phase-3d7-d/evidence-queue-light-375.png) | [Image](screenshots/phase-3d7-d/evidence-queue-dark-375.png) |
| Correction instructions, desktop | [Image](screenshots/phase-3d7-d/intake-correction-light-1440.png) | [Image](screenshots/phase-3d7-d/intake-correction-dark-1440.png) |
| Finding history, desktop | [Image](screenshots/phase-3d7-d/evidence-findings-light-1440.png) | [Image](screenshots/phase-3d7-d/evidence-findings-dark-1440.png) |

## Regression gates and limits

| Gate | Result |
| --- | --- |
| SEC-005 original regression | 1 test / 12 assertions passed |
| SEC-005 expanded response matrix | Initially 1 / 87 passed; two later evidence/history query assertions included in full-suite result |
| Original SEC-006 clock and real-expiry regressions | **2 tests / 4 assertions passed**; before remediation both failed |
| Expanded SEC-006/type/connection/expiry matrix | **7 tests / 76 assertions passed**, including originals |
| Final complete PostgreSQL suite | **98 tests / 1,190 assertions passed**, including SEC-001–005 and existing lifecycle/immutability regressions |
| Pint / production build | Passed |
| Git diff check | Passed, with existing line-ending advisories |
| C-INT-001 original browser regression | Passed: final Yes preserved in selection, database and reload; adapter unchanged |
| Expanded applicant answer-integrity matrix | **11 complete browser cases / zero failures**, including rapid/no-pause changes, keyboard, delayed replies, in-flight saves and stale-tab protection |

Only `osds_test` and `osds_ui_browser_test` were targeted. No manual-testing database reset, destructive reseed, migration, timezone modification or record correction targeted `osds_sandbox` or `osds_dev`. Isolated browser reviewer assignments were revoked/restored through new disposable fixture records for testing; existing manual records were untouched.

## Remaining limitations and next action

- UTC session contract is fixed. Existing persisted timestamps may mix correct database-default/explicit-offset instants and previously shifted PHP writes. Only isolated read-only simulations were assessed; manual/production records remain uninspected and unmodified. Any historical repair requires separate authorization and a provenance/migration/rollback plan.
- Findings have no existing service-level idempotency key. Uncertain-result recovery checks history before a manual repeat; exactly-once finding creation is not claimed.
- Dedicated superseding-finding editing controls remain absent. This refinement records separate append-only checks and does not modify existing findings.
- Native zoom, screen-reader assessment, human first-time CHEDRO usability, every browser engine, and real Google accessibility remain pending.
- New or reconnected application/worker sessions inherit UTC. Any future approved deployment must refresh cached configuration and restart long-lived workers/connections. No production deployment or server configuration change was made.

Manual instructions: [CHEDRO retesting checklist](phase-3d7-batch-d-manual-retesting.md). **Pause for Batch D review; do not begin Batch E or Phase 4.** Historical timestamp integrity and remaining human/accessibility checks remain release gates.
