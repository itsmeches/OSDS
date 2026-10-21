# OSDS first slice — Supervisor decision package

**21 October 2026. Draft for discussion; no implementation authorized by this document.** Read with the [technical contract draft](osds-first-slice-implementation-contract-draft.md) and [December readiness assessment](osds-december-functional-readiness-assessment.md). Batch G remains CONDITIONAL CLOSE recommended, with decisions pending.

## Confirmed demonstration target

**CONFIRMED:** December 2026 requires a working end-to-end demonstration using synthetic data only, repeated for COR and ACSA with separate acceptance. Show authorized questionnaire configuration/publication, HEI submission, CHEDRO screening/correction, immutable resubmission, exact-revision decision, linked versioned **DRAFT / DEMONSTRATION ONLY** document, HEI viewing and CHEDRO history. Production deployment and official issuance are excluded. The first slice covers one selected module; the second follows under the [updated roadmap](osds-december-demonstration-implementation-roadmap.md).

Today the application already supports synthetic submission, screening, corrections and resubmission. It has no final application decision or generated-document workflow. Questionnaire configuration reviewers have no established application approval authority. Completing this slice requires explicit workflow/authority decisions and a separate implementation authorization.

## Decisions to record in the meeting

**CMO reconciliation:** the [consolidated source record](osds-cmo-artifacts-and-project-alignment.md) records Annex A's technical evaluation, Chief recommendation and Regional Director approval/denial (physical pp. 15–16). The infographic does not replace these distinctions. D3–D5 must settle their synthetic demonstration representation before implementation. No role names, new permissions or direct-reviewer decision shortcut are approved here.

Candidate business rules below are **PROPOSED — REQUIRES OSDS CONFIRMATION**; the demonstration-only delivery target above is already confirmed. Record selected answer/amendment, accountable approver, date and source. A blank answer is not approval. Production policy is not a prerequisite for this synthetic demonstration package.

| ID | Supervisor decision | Candidate smallest scope |
| --- | --- | --- |
| D1 | First module and official terminology | Choose `registration` or `acsa`. Resolve Recognition versus Registration/COE before output wording. Do not infer an ACSA COR prerequisite. |
| D2 | Exact demonstration date and acceptance owner | Both modules, synthetic data and watermarked nonofficial drafts are confirmed. Record date, reviewer availability and who accepts the two module journeys; do not reopen production/official issuance as a December requirement. |
| D3 | Decision entry point and recommendation | Confirm exact-revision evaluation/recommendation prerequisites before final decision. Earlier direct decision from `under_screening` is provisional; no final action while corrections are open. Determine whether a distinct recommendation state/record is needed. |
| D4 | Evaluation/validation meaning | Specify technical-staff checklist/attestation and Chief evaluation/recommendation evidence tied to the exact submission. Do not silently combine these with the Director's decision; any simplified demonstration representation requires explicit confirmation. |
| D5 | Stage/decision authority | Map technical staff, Chief and Regional Director responsibilities from Annex A to explicit synthetic action authority and separation rules. Existing exact-case scope remains a foundation, not a new grant of decision authority. Confirm OSDS handling or exclusion of national-association cases under Section 21(A). |
| D6 | Outcomes and finality | `approved` / `rejected` are demonstration outcomes, with a reason/summary for both. No reopen, withdrawal, appeal or replacement decision in this slice; confirm wording and exceptions rather than silently assuming real-world finality. |
| D7 | Applicant and staff visibility | Applicant sees own outcome and approved applicant-facing summary/document while existing tracking access remains valid. Authorized scoped staff see the case; internal notes remain separate. Decide behavior if representation/eligibility expires after a decision. |
| D8 | Demonstration document | One versioned outcome-summary template with approved variants for approval/rejection. Applicant may view the draft after explicit generation. Confirm exact wording, fields, output format and whether rejection needs a document. |
| D9 | Repeat applications after final outcome | Retain current one-active-case boundary; decide explicitly whether final outcomes allow a new application for the same institution/module/manifest. Do not let an index predicate silently decide policy. |
| D10 | Audit, recovery and retention | Accept proposed atomic decision/history/audit and separately recoverable document generation/audit. Approve new event meanings, document audience, retention and controlled regeneration. No automatic decision replay. |

## Proposed authority summary

All future cells are **PROPOSED — REQUIRES OSDS CONFIRMATION**. Exact permission names and assignments are not yet approved.

| Action | Candidate authority | Required confirmation |
| --- | --- | --- |
| Evaluate/validate | Synthetic technical-staff representation with explicit authority and exact-case scope | Checklist, evidence and actor mapping |
| Recommend | Synthetic Chief representation with separately confirmed action authority | Exact-revision recommendation evidence and separation; not a final decision |
| Approve/reject | Synthetic Regional Director representation with confirmed decision authority | Annex A mapping, reasons, delegation and separation; no automatic role/configuration approval |
| View outcome | Existing scoped case reader or owning applicant under current tracking policy | Internal/applicant projections and post-expiry behavior |
| Generate/read draft | Explicit generation authority; read only through authorized case access and approved audience | Who may generate, applicant visibility, regeneration |
| Configure template | Separately delegated template owner | Who approves exact template version; not implicit questionnaire permission |

## Approval checklist

- [x] Synthetic-only demonstration, both modules, draft output and no production deployment confirmed by supervisor requirement relayed by the user.
- [ ] D1–D2: first module/name, exact demonstration date and accountable acceptance owner recorded.
- [ ] D3–D6: transitions, validation prerequisites, actors, reason rules and separation/finality decisions signed or amended.
- [ ] D7–D9: visibility, template example/fields, output audience and repeat-application disposition recorded.
- [ ] D10: audit expectations, retention and uncertain-response/generation recovery accepted.
- [ ] Batch G residual decisions remain separately recorded; no implied closure or risk acceptance.
- [ ] Technical contract reviewed, scope bounded, and separate implementation/test authorization issued.

**Recommended first authorized implementation unit, after those decisions:** decision persistence/authorization/history/audit on one module, followed by its linked watermarked document. Acceptance must cover approval and rejection, forbidden scope, stale revision/authority, audit rollback and uncertain response. Implementation stages and database constraints are in the companion contract.

Until confirmation, conduct the decision meeting rather than implementing candidate policy. No code, migrations, tests, database access, commit/push, deployment, Batch H, Phase 4 or official issuance occurred. **Stop for supervisor review.**
