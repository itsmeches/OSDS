# Batch E OSDS manual retesting

**Date:** 20 October 2026. Human execution pending. Automated results are in the Batch E report; this checklist is not human acceptance evidence.

Use authorized synthetic accounts and disposable configurations. Do not use existing manual records to reproduce historical corruption. Do not change permissions or repair historical conditions without separate authorization.

## Priority 1 — Item and condition integrity

1. Create a questionnaire under an existing approved service. Confirm no new official service/module is created.
2. Add three questions with distinct titles/help. Edit Q2, return to Q1/Q3, save and reload: only Q2 must change. Repeat Q1 → Q2 → Q1 → Q2 rapidly and with keyboard selection.
3. Change one question's type, required state, options and simple condition. Other questions and complete condition trees must remain unchanged.
4. Insert, duplicate, delete and move questions/options. Move sections. The selected item must remain the intended item, and a subsequent edit must follow its new location.
5. Edit two documentary requirements independently, including instructions, providers, required state and counts. Move one and edit again. Save/reload both.
6. Open disposable drafts containing standalone, all, any and nested rules. Save unchanged and after unrelated edits. Confirm identical applicability. Complex protected rules must explain their restriction, never simplify silently.
7. Intentionally edit a supported simple condition; explicitly remove another with confirmation. Emptying the source alone must not silently clear an existing rule.
8. Move/delete a condition source: invalid references must produce clear errors and leave persisted data unchanged.

## Priority 2 — Complete authoring and recovery

9. Traverse Build → Documents → Review and test → Send for approval. Return to edited fields; unsaved input must remain.
10. Trigger required-field/choice/count validation, correct it, and save. Verify confirmed save state after persistence; no premature success.
11. Open Preview, test conditions and return. Preview must not create applicant answers, drafts, submissions or evidence. Compare the approved synthetic applicant manifest using authorized fixtures.
12. Attempt navigation with unsaved changes; cancel the warning and verify input remains.
13. In two tabs, save the newer tab then attempt a stale save. Verify conflict and local input recovery without overwriting the newer version.
14. Using isolated failure fixtures, interrupt a save or lose its response. Do not blindly repeat a consequential action. Inspect the saved pair in a new tab, explicitly copy local work if needed and reconcile with the authorized saved version.
15. Expire a session: keep the editing tab open, copy local work before signing in separately, and reopen the saved pair. There must be no automatic replay or browser-storage recovery claim.
16. Send the exact pair for Admin review; verify one review request and a read-only in-review builder. OSDS cannot perform Admin approval.
17. Verify published and retired configuration remains immutable/read-only under existing lifecycle rules. Do not repair old configurations or timestamps.

## Priority 3 — Accessibility and authorization

18. Test Light, Dark and System at desktop/tablet/mobile and native 200% zoom. Current step, save status and next action must remain understandable; the mobile outline must collapse and expand with keyboard controls.
19. Use Tab/Shift+Tab/Enter/Space/Escape, visible focus, field errors and screen-reader announcements. Record assistance needed, overflow, unclear labels and unnecessary scrolling.
20. With disposable security fixtures, revoke authoring permission: direct routes and subsequent Livewire calls must be denied. Admin/CHEDRO/SCHOOL roles must not implicitly gain OSDS authoring.

Record account role, fixture, theme/width, task, expected/observed result, elapsed time, assistance and safe screenshots. Keep configuration recovery text, locators and secrets out of screenshots and tickets.

Pause for user review before Batch F. Phase 4 is excluded.
