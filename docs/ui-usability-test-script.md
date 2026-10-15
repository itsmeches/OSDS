# UI usability acceptance script

**Status:** Script prepared; testing with actual CHED/OSDS staff is pending.
**Data:** Use synthetic local or test data only.

## Recording sheet

For each task, record:

| Item | Notes |
| --- | --- |
| Completed without help? | Yes / No |
| Completion time |  |
| Confusing labels or actions |  |
| Navigation mistakes |  |
| Assistance required |  |
| Suggested improvement |  |

Ask the participant to think aloud. Do not guide them unless they become blocked; record any guidance supplied.

## Scenario A: first-time OSDS user

**Starting point:** Sign in using the synthetic OSDS account and begin on Dashboard.

1. Find the Registration questionnaire.
2. Open its questionnaire workspace.
3. Find the current editable questionnaire draft.
4. Add one synthetic question with a plain label and stable key.
5. Save the draft and return to the questionnaire workspace.
6. Open document requirements.
7. Add or inspect a synthetic required document and its accepted link types.
8. Save the draft and return to the workspace.
9. Find and inspect the combined preview.
10. Submit the exact questionnaire and document-requirements versions for review.
11. Explain, using the screen text, what happens next.

**Expected result:** The participant completes the workflow without visiting a technical list, understands that the versions are frozen for review, and sees a success notification.

## Scenario B: first-time Admin user

**Starting point:** Sign in using the synthetic Admin account and begin on Dashboard.

1. Find pending questionnaire approvals.
2. Open the pending request created in Scenario A.
3. Identify the program, questionnaire version, and document-requirements version.
4. Inspect all questions and documentary requirements.
5. Locate technical configuration details without those details obstructing the main task.
6. Approve the configuration using a synthetic reference.
7. Explain why approval did not automatically publish it.
8. Publish the approved configuration to the review sandbox.
9. Find Published questionnaires.
10. Open the sandbox preview and confirm that it states no responses are saved.

**Expected result:** The participant can inspect before deciding, distinguishes approval from publication, and understands that the sandbox is staff-only.

## Scenario C: navigation and access

1. At a 320 px browser width, repeat the main navigation and locate each primary action.
2. At 200% zoom, inspect the questionnaire and approval pages for clipped content.
3. Complete the workflows using only the keyboard.
4. Verify visible focus and meaningful button/link names.
5. Enable reduced motion in the operating system and verify that the interface remains understandable.
6. Sign in as synthetic CHEDRO and SCHOOL accounts and confirm questionnaire administration and sandbox preview are unavailable.

## Follow-up

Classify findings as blocking, high, medium, or low impact. Fix blocking access/task failures before Phase 3B. Review recurring terminology or navigation problems with at least one process owner before treating labels as stable.
