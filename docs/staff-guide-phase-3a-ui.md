# Staff guide: questionnaire review sandbox

This guide covers the staff-only questionnaire configuration available after Phase 3A. It does not cover applicant submissions, evidence links, CHEDRO application review, certificates, or RA 11180.

## OSDS: prepare a questionnaire

1. Sign in and open **Questionnaires**.
2. Choose **Registration** or **Authority to Conduct Sports Activities**.
3. Under **Questions**, open the editable draft.
4. Add or reorder sections, questions, choices, and display conditions. Select **Save draft**.
5. Under **Document requirements**, open the editable draft. Configure each requirement, instructions, accepted link types, and allowed link counts. Select **Save draft**.
6. Review the **Combined preview**.
7. Select **Submit for review** at the top of the workspace.
8. Choose the exact questionnaire and document-requirements drafts. The system does not choose silently when multiple drafts exist.

After submission, both versions become read-only while an Admin reviews them. A rejected review returns them to draft with preserved review history.

## Admin: review and publish

1. Open **Questionnaire approvals**. Pending reviews appear first.
2. Select **Open review**.
3. Inspect all questions, choices, conditions, document requirements, accepted links, and counts.
4. Expand **Technical details for authorized support** only when the review reference or hashes are needed.
5. Select **Approve** or **Request changes** and provide the required reference and feedback.
6. After approval, select **Publish to review sandbox**.
7. Open **Published questionnaires** and select **Preview**.

Approval and publication are separate. The review sandbox is available only to permitted staff and saves no applicant response.

## Status guide

| Status | Meaning |
| --- | --- |
| Draft | Editable configuration |
| In review | Frozen while an Admin reviews exact content |
| Pending review | Awaiting an Admin decision |
| Approved | Accepted for possible sandbox publication |
| Changes requested | Returned to OSDS with preserved history |
| Published to review sandbox | Immutable staff-only preview exists |
| Retired | Preview is no longer active; history remains |

## Common issues

- **An action is missing:** Check the account type, explicit permission, record status, and whether the account is active.
- **A review does not appear:** Confirm OSDS selected **Submit for review** and saw the success notification.
- **A version cannot be edited:** Reviewed and published versions are read-only. Use **Create new draft** when available.
- **Publishing fails:** Another active sandbox publication for the program may need to be retired first.
- **A condition fails validation:** Confirm that it references an earlier question from the exact questionnaire and uses a valid choice key.
