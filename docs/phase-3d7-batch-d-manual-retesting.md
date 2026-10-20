# Batch D CHEDRO manual retesting — pending

**SEC-005 and SEC-006 are remediated; human retesting remains pending.** Automated evidence is recorded in the Batch D report. Use isolated synthetic fixtures for expiry/revocation/failure probes. Do not edit existing manual-testing records merely to reproduce a test.

1. Confirm the two queues clearly distinguish document test reviews from application intake.
2. Search by organization; filter service, region and status; move between result pages and clear filters. Counts and choices must remain scoped.
3. Try no assignment, expired/revoked/future assignment, wrong region/module, missing permission and mismatched regional scope. Expect no unauthorized row, option, count or direct-case access.
   Verify expiry against explicit real instants and the database clock, including minute-wide boundaries; relative PHP fixtures alone missed SEC-006. Check that displayed test-copy/finding times are consistent without modifying historical data.
4. Open an assigned application; identify the next step and start screening.
   There is no Pass, Approve or Issue action in this checkpoint. Screening and document findings remain synthetic checks; corrections are optional when information needs changing.
5. Read exact submitted answers and open authorized latest-copy documents. A reviewer without answer-view permission must receive no answers through UI **or direct Livewire calls**.
6. Submit empty correction instructions; expect helpful errors and preserved input. Then request a specific correction to an exact submitted question or document.
7. Sign in as SCHOOL, see the instruction, edit/save and resubmit. Return to CHEDRO; inspect the latest and earlier submitted copies without altering history.
8. Open a separate assigned document test review. Verify general questionnaire answers remain absent.
9. Record accessibility, completeness and document-match checks against the requirement or exact document. Confirm reviewer attribution and saved history.
10. Interrupt a request and lose a response after persistence in disposable fixtures. Expect no premature success or automatic consequential retry. Check history before repeating an uncertain action.
11. Revoke authority with a case open. Refresh or attempt an action; expect denial without new data disclosure or mutation.
12. Repeat at desktop, tablet and mobile in light/dark/system. Check keyboard focus, table scrolling, labels, errors and loading states. Record actual contrast, native zoom and screen-reader checks separately.

Record actual outcomes, assistance needed, defects and evidence. Human testing has not been performed for this batch.
