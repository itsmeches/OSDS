# C-INT-001 — Applicant answer-integrity remediation

**Date:** 19 October 2026 (Asia/Manila)  
**Boundary:** Targeted applicant binding/request coordination. Batch D and Phase 4 remain unstarted. No migration, authorization, questionnaire meaning, persistence-service, encryption, submission or correction-lineage change.

## Confirmed cause

The installed dependency is Livewire **v4.4.7**, as recorded in `composer.lock`. Synthetic browser instrumentation traced trusted native radio input through the component proxy, request dispatch, snapshot merge, conditional rendering, save and reload. The last Yes initially reached the native selection and client state correctly. The first incorrect value appeared in `mergeNewSnapshot`, before the DOM morph or save.

Two related reconciliation mechanisms were confirmed in the installed client:

1. `diffAndConsolidate` can consolidate newly added conditional-answer keys into an `answers` object update. Its object value aliases the live reactive answers object. A request was dispatched with No; after a later intentional Yes, its retained update object contained Yes while its server response still contained No. Reconciliation therefore interpreted the old No as a server correction and patched it over the latest client value.
2. Freezing that object fixed the original reproduction but did not resolve every rapid sequence. An overlapping request with an empty answer delta could return No after another response advanced the component's canonical answer to Yes. The merge uses the current canonical state, so that old response could again patch No into the client. A later Yes response could then see no server difference and leave the client incorrectly at No.

These are evidenced in synthetic dispatch/merge traces, rather than inferred from latency alone. Stable question keys and the same component remain in use. The server's condition evaluator and draft service correctly acted on the values they received; they did not independently turn Yes into No. Once the client had been overwritten, Save sent/persisted the unintended value and reload reproduced it.

Before evidence is preserved in `answer-race-before.json`, `answer-race-lifecycle-before.json` and `answer-race-before-reload.png` under `docs/screenshots/phase-3d7-c/`. The trace pinpoints a whole-object dispatch at No whose retained `rootUpdate` became Yes, followed by a response merge changing the current answer from Yes to No. The expanded matrix also caught the empty-delta overlap after the first attempted fix; that failure was not accepted as green.

## Correction and rationale

`resources/js/applicant.js` uses the installed Livewire component interception API to:

- Copy each message's JSON update values at dispatch, breaking the live-object alias used during later reconciliation. This does not modify the already serialized network request or its values.
- Keep one applicant reactive model message in flight. Later native input still updates local state immediately, but its model action is deferred to the active message's lifecycle finish. Queued actions dispatch the latest values after the prior response completes. No sleep, correctness debounce, vendor patch, or automatic consequential retry is introduced.
- Retain the existing framework handling of already deferred actions, Save, request keys, optimistic locks and confirmed feedback. A change made during an in-flight Save stays unsaved locally until a subsequent explicit Save persists it.

This removes the overlapping reconciliation that caused both observed failures. Same-component response overtaking is prevented by construction, rather than patched by accepting an older value or weakening conditional logic. Independent tabs still make requests concurrently and remain protected by the existing server lock version.

The expanded multi-question test also found a new multiple-choice control starting as a boolean, causing the existing backend array validator to reject saving. `DraftEditor::mount` now initializes only previously unanswered `multiple_choice` controls to `[]`, their existing defined value type. Saved answers are retained, required validation still rejects an empty selection at progress/submission, and provider/type/domain validators are unchanged. The original validation failure is preserved in `answer-matrix-initial-binding-failure.json`; the test was retained and now exercises checkbox arrays.

## Files affected

| File | Change |
| --- | --- |
| `resources/js/applicant.js` | Scoped message update copy and lifecycle-based reactive action ordering; existing recovery and feedback retained. |
| `app/Livewire/Applicant/DraftEditor.php` | Correct initial array type for unanswered multiple-choice controls. |
| `scripts/reproduce-applicant-answer-race.mjs` | Original last-Yes assertion retained; stronger database, selected-radio and conditional-child checks; optional synthetic-only lifecycle trace. |
| `scripts/verify-applicant-answer-matrix.mjs` | Real browser rapid-answer matrix, direct isolated database checks, delayed responses, concurrent tabs and false-save checks. |
| `scripts/Prepare-ApplicantJourney.php` | Guarded synthetic matrix fixture with two conditional parents, single/multiple choices, and read-only answer/applicability inspection. |
| `tests/Feature/ApplicantSandboxTest.php` | New array-binding round trip and required-selection regression. |
| Batch C report, manual guide and issue register | Root cause, evidence, verification and limitations. |

No framework/vendor source, database migration, policy, permission, representation, eligibility, evidence cipher, snapshot model or PostgreSQL guard was modified.

## Browser coverage

Every complete matrix case asserts final selection/client state, direct saved PostgreSQL values, reloaded answers and the applicable conditional children. Multiple-choice arrays and single-choice option keys are also checked. Cases cover:

1. Yes → No → Yes with rendered conditional transitions.
2. No → Yes → No.
3. Yes → No → Yes with no deliberate pauses.
4. Rapid changes immediately before Save.
5. Rapid changes followed by section navigation.
6. Conditional child text hidden and reshown without loss.
7. Repeated changes across two parents, choice controls and checkbox arrays.
8. Native keyboard arrow navigation through radio groups.
9. An earlier response held while more input is made: the latest input remains local; later same-component dispatch waits for release. This deliberately attempts overtaking and verifies serialization, not a claim that same-component replies remain concurrent.
10. A Save reply held after persistence while another answer is chosen: the newer input is not falsely marked saved; a later explicit Save persists it.
11. Two tabs with actual reordered response release: the saved tab's reply is held after commit; the stale tab's conflict arrives first. The stale tab retains its local input and cannot overwrite the saved answer.

The original targeted reproduction is also run independently. Its assertion is strengthened, never removed or relaxed. The full applicant browser journey separately covers response-loss idempotency, HTTP 503, sessions, evidence, correction/resubmission, representation and retirement. This is Playwright browser testing, not a component-only simulation or human usability claim.

## Verification results

| Gate | Final result |
| --- | --- |
| Original reproduction, with stronger database/selection/child assertions | Passed; final visible/client/saved/reloaded value is Yes |
| Expanded real-browser matrix | **11 cases passed**, zero failures; every case checks selection/client, database, reload and applicability |
| Complete post-fix applicant journey | **18 interaction checks passed** |
| Applicant light/dark desktop/tablet/mobile screen matrix | **67 checks**, zero axe/overflow/uncaught-error findings |
| Focused PostgreSQL applicant tests | **29 tests, 495 assertions passed** |
| Complete PostgreSQL suite, including SEC-001–004 | **88 tests, 989 assertions passed** |
| Laravel Pint / Vite production build / `git diff --check` | Passed; existing line-ending advisories remain |

Coverage of the 15 requested browser requirements:

| Requested coverage | Matrix evidence |
| --- | --- |
| 1–3: both directions and no deliberate pauses | Cases 1, 2 and 3 |
| 4: input during an earlier in-flight request | Case 9 holds a reply while native inputs continue |
| 5: rapid input immediately before Save | Case 4; the real Save button respects pending-update loading/queuing |
| 6: section navigation | Case 5 |
| 7–8: conditional children, hide/reshow | Cases 1 and 6; child text is retained |
| 9: save/reload/resume | Direct database and reload assertions in all 11 cases |
| 10: delay and response reordering | Case 9 proves same-component overtaking is prevented; case 11 releases the stale tab's conflict before the saved tab's held reply |
| 11: repeated input across questions | Case 7, including two parents, a single choice and checkbox arrays |
| 12: keyboard radios | Case 8 uses native arrow-key interactions |
| 13: actual database matches intent | Direct guarded PostgreSQL inspection in all 11 cases |
| 14: no false acknowledgement of newer unsaved input | Case 10 changes the answer while a committed Save reply is held, verifies the newer input remains unsaved, then explicitly saves it |
| 15: stale-tab/optimistic locking | Case 11 plus the complete applicant journey's independent two-tab conflict |

Browser evidence and failures are retained as point-in-time records; the failing pre-fix reproduction is not overwritten by claiming it had passed. The final result is **CONDITIONALLY READY for Batch C user review**, not human acceptance or platform production readiness. Batch D remains unstarted.

Browser fixtures and direct database checks are guarded to **`osds_ui_browser_test`** on port 8017. PostgreSQL automated tests use **`osds_test`**. No command resets, reseeds, inspects or modifies `osds_sandbox` or `osds_dev`.

## Remaining limits

- The adapter depends on the installed Livewire interception lifecycle. Re-run the original reproduction, matrix and journey when upgrading Livewire; no upstream framework fix is claimed.
- No historical submitted copy or manual record is repaired or rewritten. The finding and remediation are established with synthetic fixtures; existing manual data was not inspected for affected answers.
- Real browser tests use Chrome. Other browser engines, native zoom, screen readers and real-user testing remain outside the reported verification.
- Normal network latency remains observable; correctness no longer depends on deliberate pauses. Conditional views update once the serialized server render confirms the latest inputs.
- This resolves applicant coordination only; it does not certify unrelated staff-builder reactive state or authorize Batch D.
