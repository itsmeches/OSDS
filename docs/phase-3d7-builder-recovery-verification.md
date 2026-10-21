# Batch G — Dedicated Form Builder recovery verification

**21 October 2026. Scoped automated assessment: PASS — eight recovery scenarios.** No runtime correction was needed. Batch G remains incomplete; pause for review. No Batch H, Phase 4, commit/push or deployment.

## Existing requirement and precise gap

Sources: the Batch G acceptance matrix's dedicated builder-recovery row, `scripts/assess-batch-g-recovery.mjs`, the recovery branch of `scripts/verify-builder-recovery.mjs`, Batch E's recovery/authoring report, and G-INT-006/007 preservation reports. The broader interrupted recovery index records only applicant completion. It is not a completed builder run. Batch E had an earlier **14-check / 30-screen** recovery/authoring pass; the later selection/condition suites do not replace current-tree recovery confirmation.

This run executes only the existing **eight recovery checks**. The six subsequent native authoring/overview/preview/review-send checks are excluded: their earlier evidence is retained, not newly repeated. Nothing is sent for approval or published. Existing condition/document assertions are strengthened at each checkpoint, and the HTTP/session cases additionally retain the selected second document and RAM-only instructions as required by this assessment.

## Environment, method and evidence

- Chrome/Playwright/axe; guarded existing `scripts/ui-browser-server.php`, port **8017**; fixture control pins **`osds_ui_browser_test`**, rejects cached configuration and creates disposable synthetic draft pairs. No manual-testing database access.
- `scripts/verify-batch-g-builder-recovery.mjs` extracts the existing recovery branch, preserves its assertions and writes a separate evidence directory. It records persistence checkpoints, console errors and failed requests. Its temporary wrapper is removed; the old interrupted `.batch-g-recovery-current.mjs` is preserved untouched.
- Each checkpoint compares the complete persisted question-condition map, document-condition map and configured documents against its prepared fixture. Original native form/Livewire interactions and direct database inspection remain in use. Injected network faults are browser interceptions, not runtime service substitutions.
- [Machine-readable results](screenshots/phase-3d7-builder-recovery-final/recovery-results.json): `checks[0..7]` and matching `persistence[0..7]` correspond to rows R1–R8 below; `screens`, `consoleErrors`, `failedRequests`, `errors` record observations. Private execution log: `storage/app/private/builder-recovery-dedicated.txt`.

## Scenario acceptance matrix

| ID / outstanding scenario | Result | Actual interaction and persistence result |
| --- | --- | --- |
| R1 Dirty feedback / navigation recovery | **PASS** | Local title immediately shows Unsaved changes. A real before-unload dialog is dismissed; editor URL and local title remain. Persisted pair stays at locks **1/1**, unchanged condition/document configuration. |
| R2 Validation / correction / duplicate click | **PASS** | Empty question title produces not-saved guidance and retains empty input, without a version change. Corrected title and double-click Save produce locks **2/2**, confirmed Saved feedback and the same title after reload. Other condition/document data remains exact. |
| R3 Interrupted save before persistence | **PASS** | Exactly one Save request is aborted. Local second-question edit remains; locks stay **1/1**. Recovery copy contains the edit; its marker is absent from local/session storage. Open saved version in a new tab shows original saved content without replacing local work. No automatic retry. |
| R4 Committed save / lost response | **PASS** | Browser forwards one Save to completion, then drops its response. Database contains the intended title at locks **2/2**. UI reports Action not confirmed and disables another Save. No second request or false confirmed-success claim; conditions/documents unchanged. |
| R5 HTTP 503 / selected-document recovery | **PASS** | A real request receives injected 503. Local questionnaire title, selected **Second document** and unsaved instructions remain. Persisted locks **1/1** and document configuration remain unchanged; no success claim. |
| R6 Session loss / selected-document recovery | **PASS** | Actual session cookies are cleared before Save. Session-ended guidance appears without leaving the editor; local title, selected document and unsaved instructions remain. Locks **1/1** and saved data unchanged. This exercises cookie loss, not natural idle expiry or a separately forced 419. |
| R7 Authority loss after mount | **PASS** | Disposable fixture author role is removed; a real helper request returns **403** without the protected question marker. Workspace and copy action hide, fresh direct URL is denied, locks stay **1/1**. Fixture authority is restored. No production permission/governance change. |
| R8 Unauthorized role / direct URL | **PASS** | Existing Admin fixture receives **403** on the OSDS builder URL; role name does not grant authoring authority. No new version or review request. |

All eight checkpoints retain **draft/draft** states and **zero review requests**. The run compares persisted condition trees and configured documents exactly; it does not claim to test every possible unsaved condition-tree edit during each failure. Existing E-INT condition/selection matrices remain the evidence for those authoring permutations. No confirmed unintended persistence, automatic privileged replay or independent critical defect was found.

## Screenshots, accessibility and console observations

**24 page-state checks:** interrupted Save, uncertain committed Save, HTTP 503 with selected document, and session loss with selected document; each in Light/Dark at **1440 / 768 / 375px**. Axe runs all rules in the existing WCAG 2/2.1/2.2 A/AA tag set without finding exceptions. All 24 record **zero axe violations and zero page-level overflow**; there are **zero uncaught page errors**. Computed contrast samples are recorded, not asserted as a complete contrast/WCAG audit. Normal notification dismissal and animation completion precede screenshots as in the existing harness; timed-animation accessibility is not certified.

Representative developer-inspected images:

- [Interrupted save, light desktop](screenshots/phase-3d7-builder-recovery-final/interrupted-save-light-1440.png)
- [Session recovery, dark mobile](screenshots/phase-3d7-builder-recovery-final/expired-session-selected-document-dark-375.png)
- [Uncertain committed save, dark tablet](screenshots/phase-3d7-builder-recovery-final/uncertain-committed-save-dark-768.png)
- [HTTP 503, light mobile](screenshots/phase-3d7-builder-recovery-final/http-503-selected-document-light-375.png)

The browser records **16 console errors**: 12 failed-resource messages, one injected HTTP 503 and three expected HTTP 403 denial responses. The **12 failed requests** are ten blocked external avatar requests (`/api/`) and the two deliberately aborted Livewire saves. These are recorded rather than reported as a zero-console-error run. They do not establish an unexpected runtime exception. External avatar access is intentionally blocked by fixtures. No assertion failed or harness correction was needed in this run.

## Reconciliation with preserved evidence

| Existing evidence | Disposition after this assessment |
| --- | --- |
| Dedicated builder recovery gap | **Closed for R1–R8** by this current-tree eight-check / 24-screen run. |
| Batch E native authoring/review-send branch | Earlier six remaining checks retained; **not rerun here**. No new publication or review-send recovery guarantee. |
| Applicant recovery and C-INT-001 | Prior applicant journey and original/11-case preservation retained; not repeated. |
| Builder condition/selection integrity | Prior original/14-case selection and seven condition checks retained; not repeated. New recovery checks additionally prove unchanged saved rules/documents at each checkpoint. |
| CHEDRO and staff recovery | G-INT-007 preservation **12 interactions / 30 screens** and **15 / 94** retained; not repeated. |
| Modal, held-response timing, workload and concurrency/security | Previously accepted bounded evidence unchanged; no unnecessary rerun or broader safety claim. |
| Full PostgreSQL/Pint/build | Previous results remain historical, including **252 / 2,831**. No runtime code changed or concrete failure required targeted PHP regression; no new full-suite/build pass claimed in this assessment. Diff check passed with existing line-ending advisories. |

## Remaining acceptance gaps / NOT VERIFIED

- **NOT VERIFIED — human acceptance:** native 200% zoom, screen readers and recovery announcements, human keyboard task completion, first-time usability and timed-animation contrast.
- **NOT VERIFIED — expanded recovery permutations:** other browser engines, real idle timeout/forced 419, browser crash/closed-tab restoration, every condition/provider/field permutation. These are limits, not newly added blocking requirements. RAM-only local work is lost if the tab is destroyed; no browser-storage restoration is introduced.
- **NOT VERIFIED by this task — consequential review/publication uncertainty:** no Send/Publish action executed. Existing staff publication-response-loss evidence remains separate. No automatic replay is authorized or introduced.
- Other finite authority-inventory C/R dispositions and broader Batch G final acceptance remain unchanged. This assessment closes the dedicated builder recovery gap only and does **not** declare Batch G complete or authorize Batch H.

Only the new verification runner, safe synthetic evidence and documentation changed in this task. Existing selector work, staged state, manual databases, historical records and prior evidence are preserved. No runtime, schema, policy, permission, transaction or audit correction; no migration, repair, commit/push or deployment. **Pause for review.**
