# Phase 3D.7 - Batch F manual retesting

**20 October 2026. Human acceptance remains pending.** Use disposable synthetic accounts and configurations. Do not reset or overwrite existing manual-testing records. Record role, scope, theme, viewport, result, assistance needed and any unclear wording. Automated evidence and limitations are in the Batch F report.

## Priority 1 - Account and authority boundaries

1. As a regional account manager, create a SCHOOL account for an authorized institution. Enter the password last. Expect pending verification, not a claim that applicant access is ready.
2. Open Institution representatives. The creator must not see Verify SCHOOL account for their own new account. A different authorized regional verifier should see and complete that action. Confirm applicant sign-in works after verification; eligibility remains separately required.
3. Edit a permitted delegated account. Save a name-only change; verify email, password, role, status and scope are unchanged. Test invalid email/password input without exposing secrets.
4. In isolated fixtures, attempt a forbidden target credential change and direct edit URL. Expect denial with no partial mutation. Do not test this against another person's real credentials.
5. Open your own regional assignment: expect read-only guidance and no Save. On an authorized delegated assignment, change effective dates, save, reopen and verify the dates. Account/location bindings remain fixed.
6. Open that assignment in two tabs. Save in the first; saving the old second tab must retain its local dates and explain that the record changed. Revoked/expired grants must not be revived through Edit.
7. With disposable fixtures, revoke permission or effective scope after mount. The next read/write must fail closed. Check regional/nationwide access and cross-region denial without treating a role name as universal authority.

## Priority 2 - Exact configuration review and audiences

8. Open a pending questionnaire review. Read the exact questionnaire/document pair, full conditions and instructions. Try an empty review reference; expect inline correction guidance and no decision.
9. Approve that exact pair once. A double click must not create two decisions. Check the next-step explanation.
10. Publish the staff-only preview. Confirm that it is not an applicant publication. Separately publish to the applicant sandbox using the explicitly labelled confirmation button and availability dates.
11. On Published questionnaires, filter Audience. Applicant rows must not offer Open staff preview. Read availability and retirement states. Retire a disposable publication with a reason; its history must remain visible. A new applicant publication after retirement must use the approved exact pair.
12. Request changes on a different review. After OSDS edits the returned draft, reopen the review: see the recorded decision/reference/comments and the explanation that current content is not an unchanged reviewed copy.
13. If a consequential response is uncertain, keep the tab open and use Open saved record in a new tab before repeating it. Verify the actual saved state. No operation should replay automatically.

## Priority 3 - Scoped staff resources and recovery

14. Inspect Users, Regions, Institutions, Institution representatives, Regional assignments, Audit history, sandbox eligibility and the two separate reviewer-assignment resources available to your permissions. Search, filter and paginate; no unauthorized metadata or counts should appear. Where explicit permissions allow it, exercise disposable region/institution forms and reviewer-assignment/eligibility grant forms; these detailed create/edit permutations were not all browser-mutated in Batch F.
15. Confirm Current, Scheduled, Expired and Revoked labels match fixture dates. Phase 3C evidence-review assignments must remain distinct from Phase 3D application-intake assignments.
16. As an explicitly scoped Super User, inspect permitted account/support pages. Nationwide scope alone must not reveal questionnaire approval or reviewer-management pages without their permissions.
17. In disposable testing, interrupt a save or expire the session. Expect safe guidance and no premature success. Keep local work in the tab; signing in must not replay an old operation. Credentials must not appear in messages, screenshots or logs.

## Accessibility and usability still requiring people

18. Repeat key tasks in Light, Dark and System at desktop, tablet and mobile widths. Use keyboard only, visible focus, modal open/close, field errors, search and pagination. Record horizontal scrolling and unclear labels.
19. Test native 200% browser zoom and screen readers, including validation announcements, modal focus and recovery notices. Automated axe checks do not complete these tasks.
20. Record first-time task completion, time, unnecessary clicks, assistance and suggested wording changes. No human usability success is claimed until these steps are performed.

Pause for user review before Batch G. Batch H, Phase 4 and production deployment remain excluded.
