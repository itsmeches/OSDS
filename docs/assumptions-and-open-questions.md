# Assumptions and open decisions

**Phase 0 review draft.** No item below is an approved CHED policy. Record the decision maker, decision date, source document, and affected tests before moving an item to confirmed.

The detailed, meeting-ready question register is maintained in [Stakeholder questions and decision priorities](./pre-implementation-stakeholder-questions.md).

## Working assumptions for architecture only

| Assumption | Reason | Stop point |
| --- | --- | --- |
| One Laravel modular monolith with PostgreSQL | Simplifies initial operations and transactions | Revisit if approved hosting/team constraints differ. |
| Five account roles with no inheritance | Supervisor gave labels/order but no permission semantics | Do not seed effective approval, export, or signatory rights without matrix approval. |
| COR and ACSA have separate rules | Their relationship and checklists are unconfirmed | Do not force COR before ACSA or share policy-dependent transitions. |
| RA 11180 is a separate module | Reporting cycle differs from certificate cases | Its release timing is undecided; do not mark it “later” by default. |
| Private hosted evidence is the baseline technical option | User's preferred stack names private storage | Do not finalize intake until hosted/link/hybrid custody is approved. |
| Approximately 3,000 schools is a load-test scenario | Number was spoken in meeting | Replace with verified volume/concurrency before setting targets. |

## Decisions requiring stakeholder sign-off

| Priority | Decision and options | Proposed owner | Why it matters |
| --- | --- | --- | --- |
| Blocker | Signed URA and approved COR/ACSA process maps; precise eligibility, checklists, corrections, exceptions | OSDS and CHEDRO process owners | Prevents coding an invalid government process. |
| Blocker | Who applies: school, association, or both; national/regional association jurisdiction; representative proof | OSDS/CHEDRO and identity owner | Drives organization model, routing, and access. |
| Blocker | Five-account action/region matrix; account provisioning and separation of duties | Supervisor, OSDS, IT/security | Prevents excess access and operational dead ends. |
| Blocker | Approval/signatory/delegation, registry trigger and number, validity, undertaking and issuance evidence | Authorized process/records owners | Required before official outputs. |
| Blocker | Evidence custody: hosted upload, individual file links, hybrid; physical/notarized originals | Records, privacy, IT, process owners | Affects storage, security, and legal record integrity. |
| Blocker | RA 11180 first-release/parallel/later priority; existing OPRKM data import/archive/reference | OSDS, OPRKM, data owner | Affects scope, schema, schedule, and continuity. |
| Blocker | Approved host and public launch security review authority; transcript says “DICT” or “ICT” | IT/security owner | Avoids unapproved deployment. |
| High | Actual January deadline and interim Google Form/manual intake/cutover | OSDS operations | Controls rollout and reconciliation. |
| High | Current forms, certificate slides/script, eight RA 11180 sheets, and five Office templates: approved versions/applicability | Artifact owners | Prevents wrong fields, wording, and migration. |
| High | Nationwide association recognition lookup fields, audience, and status semantics | Registry/privacy owners | Avoids cross-region data exposure and stale verification. |
| High | Nine-day clock: calendar/working days, pauses, and module applicability | Process owner | Prevents false SLA notices/reports. |
| High | Hosting resources, support owner, backup retention/recovery targets, school counts and peak concurrency | IT/operations | Determines capacity and maintenance. |
| High | Questionnaire link audience, anonymous use, expiry/reuse, and response ownership | OSDS/privacy owner | Limits unauthorized submissions and leakage. |

## Known source-quality concerns

Two pasted meeting transcripts overlap and end mid-discussion; names and acronyms are machine-transcribed. Their statements are not formal approvals. The first excerpt's RA number is uncertain in speech, although CHED's [published RA 11180 IRR](https://legacy.ched.gov.ph/rules-and-regulations-to-implement-the-provisions-of-republic-act-no-11180-athletic-programs-report-act/) confirms the Athletic Programs Report Act. The reference to “MO 13” is not sufficient to identify an applicable order. The undertaking template lacks a 2026 memorandum number; the coaches sheet lacks the contact-number column requested by its own instruction. Google links and the OPRKM system have not been directly inventoried.
