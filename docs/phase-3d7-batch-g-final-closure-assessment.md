# Batch G — Final closure assessment

**21 October 2026. Recommendation: CONDITIONAL CLOSE. Decision and sign-offs pending.** Read-only evidence reconciliation, with this assessment as the sole documentation addition. No tests, application bootstrap, database access or runtime changes were performed. This recommendation does not close Batch G on management's behalf or authorize COR/ACSA implementation, Batch H, Phase 4 or deployment.

## Executive disposition

The finite technical assessment supports conditional closure: all five proposed exposed-path checks have outcomes, the confirmed K1/K3/K4/K5 integrity defects have targeted remediation evidence, and the latest complete PostgreSQL checkpoint passed **345 tests / 3,779 assertions**. No unresolved confirmed critical defect is identified in the reviewed final reports. Earlier diagnostic failures remain valid historical evidence; their corresponding final remediation reports supersede the operation-specific stop instructions.

**CLOSE is not yet justified:** acceptance of the nine service-only deferrals and nine implementation-covered operations remains a decision; eligibility audit governance, the Institution selector target-size finding and human accessibility sign-offs remain open. These are named decisions and limitations, rather than a newly reproduced critical defect requiring HOLD. If the responsible decision-maker requires any outstanding item as a mandatory closure gate, that item must be completed or explicitly disposed before closure is recorded.

This assessment uses the existing [33-operation inventory](phase-3d7-batch-g-authority-inventory.md), [disposition proposal](phase-3d7-batch-g-inventory-disposition.md), [acceptance matrix](phase-3d7-batch-g-acceptance-matrix.md), [issue register](phase-3d7-issue-register.md) and final reports linked below. It adds no operation, audit event, authorization rule or test requirement.

## Reconciled 33-operation inventory

The following is the final **assessment classification**, not management risk acceptance. **Accept** recommends bounded acceptance of recorded evidence; implementation-covered C rows remain C, without independent proof of every audit-fault/concurrency boundary. **Defer** requires explicit agreement. **K-verified** is limited to the stated targeted matrix. **N/A** concerns implemented authorized workflows, not arbitrary PHP or SQL.

| ID | Existing operation | Final classification | Supporting evidence and retained limitation |
| --- | --- | --- | --- |
| A01 | Account Create, initial role/grant | Accept — C | AccountGovernance aggregate transaction, PolicyRef creation/delegation and staff browser evidence. Late multi-audit failure and real concurrent account creation not independently proved; sequential duplicates are not concurrency. |
| A02 | Profile/email/password Update | Accept — C | SEC-007 actual editor, credential authorization, hashing/redaction and mounted revocation; transactional service. Independent post-persistence audit outage unverified. |
| A03 | Account status transitions | Defer — C | Service transaction, lifecycle/audit/session and duplicate evidence. No located status UI; actor authority after waits and late-failure side effects unverified. |
| A04 | Replace primary role | Defer — C | Transaction, explicit permissions and one-role constraint. No located standalone UI or successful direct-service regression; pivot/audit rollback and contention authority unverified. |
| A05 | Enable synthetic applicant | Accept — C | Actual table action, transactional Core/domain audits and PolicyRef enable/event evidence. Specific late-audit and mounted-authority-loss permutations unverified. |
| A06 | Verify representation/activate SCHOOL | Accept — C | Transactional multi-record workflow, separate-verifier controls, actual staff action/browser. Partial multi-audit failure and concurrent authority matrix unverified. |
| A07 | Regional assignment Create | Accept — V | G-INT-006 actual action, audit faults, concurrency and recovery. Existing duplicate/lifecycle semantics retained. |
| A08 | Regional dates Update | Accept — C | SEC-008 actual editor, raw-state/stale-version/authority checks and transactional amendAssignment. Independent update audit-sink fault unverified. |
| A09 | Older regional grant service | Defer — C | AccessGovernance transaction/Core plus domain audit. No located UI/direct successful fault matrix; A07 evidence is not interchangeable. |
| A10 | Regional revoke service | Defer — C | Transaction and success/audit/session regression. No located revoke UI; lock-wait authority and late-failure/session boundaries unverified. |
| A11 | Regional transfer service | Defer — C | Outer transaction spans revoke/grant. No located UI or direct success/second-half-failure/concurrency proof. |
| A12 | Generic representation Create | Accept — V | G-INT-007 actual action, fault matrix, six worker scenarios and browser recovery. Distinct from account-created/service grants. |
| A13 | Representation dates Update | Accept — C | SEC-008 actual representation editor/stale boundary and shared transactional service. Separate observer audit-fault case unverified. |
| A14 | Representation grant service | Defer — C | Transaction/Core plus domain audit visible. No located UI/direct success evidence; A12 does not certify this method. |
| A15 | Representation revoke service | Defer — C | Transaction/audits visible; no located UI/direct mutation regression. Revoked fixtures prove consumption behavior, not this operation's rollback. |
| A16 | Nationwide grant service | Defer — C | Explicit authority, transaction and functional grant/access evidence. No located UI; audit failure/concurrent authority unverified. |
| A17 | Nationwide revoke service | Defer — C | Transaction/domain audit and functional revoke evidence. No located UI; late-failure/session/contention boundary unverified. No Core event inferred. |
| A18 | Phase 3C reviewer Create | Accept — V | G-INT-005 real search/selection/Create, faults/concurrency/recovery. Global explicit management creation contract; revocation's region predicate is not imported. |
| A19 | Phase 3C reviewer Revoke | Accept — V | G-INT-002 actual action/audit rollback/lock waits; separately approved effective target-region requirement preserved. |
| A20 | Phase 3D reviewer Create | Accept — V | G-INT-004 actual Create/faults/concurrency/recovery; global explicit management contract and separate intentional grants retained. |
| A21 | Phase 3D reviewer Revoke | Accept — V | G-INT-003 actual action/faults/locks/browser under its distinct global management contract. |
| A22 | Applicant eligibility Create | K-verified — K2; audit CONTRACT-N/A | Actual creation/denial/forgery/duplicate matrix passes. No specific mandatory grant event/writer established. Audit governance remains undecided; browser duplicate recovery/concurrency unverified. |
| A23 | Exact-pair approve/return | Accept — C | Transaction/locks/exact hashes/domain audit, configuration and actual staff evidence. Independent late audit fault unverified. |
| A24 | Staff/applicant sandbox publication | Accept — C | Transaction/locks, exact-pair/audience/immutability and committed-response-loss evidence. Independent late audit fault unverified; no production channel approval. |
| A25 | Retirement | Accept — C | Transaction/manifest lock, append history/audit and actual staff action/duplicate evidence. Late audit outage unverified. |
| A26 | Institution Create | K-verified — K3 | Owned creation transaction, actual-action faults/authority, concurrency and browser recovery. Optional null region/official ID and hei/false defaults preserved; selector residual remains. |
| A27 | Institution scope-bearing Update | K-verified — K1 | Owned update transaction, actual-action faults/denials, six concurrency scenarios and browser/snapshot evidence. Destination restriction remains this editor's scoped form boundary, not a universal model policy. |
| A28 | Region Create | K-verified — K5 | Owned creation transaction, actual-action faults/authority, four concurrency scenarios and final browser pass. Global explicit permission; no scope-grant prerequisite. Initial popup timeout remains unexplained. |
| A29 | Region Update | K-verified — K4 | Owned update transaction, actual-action faults/current-region authority, four concurrency scenarios and browser recovery. Existing activity/null audit bindings/last-writer behavior retained. |
| A30 | Hard Delete/restore/force delete of identity/catalogue/grants | N/A | Policies deny destructive workflows, including generated DeleteAction declarations. No authorized successful operation to fault; not a claim about direct SQL. |
| A31 | Reviewer Edit/Delete; eligibility Update/Revoke/Delete UI | N/A | No registered implemented UI operation located. Columns and fixture mutations are not UI actions. |
| A32 | Permission/role/scoped-grant management UI | N/A | No runtime management UI located; seeders/Spatie APIs do not establish one. A04 service remains separately deferred. |
| A33 | Mutable audit UI | N/A | List-only registered resource, policy denials and append-only protections. Generated unregistered page stubs do not establish mutable routes. |

**All 33 accounted for once:** 15 recommended accepts (six V and nine C), nine proposed service-only deferrals, five K-verified outcomes including K2's audit qualification, and four N/A. The former five R rows now have finite outcomes; this does not convert the remaining C rows into independently verified operations. No further inventory is proposed.

## Accepted protection and K-check evidence

G-INT-001's finding operation is an existing related workflow, not a new inventory row. Recorded focused results are:

| Final report | Protected operation | Focused PostgreSQL result |
| --- | --- | --- |
| [G-INT-001](phase-3d7-g-int-001-remediation.md) | Phase 3C finding and mandatory audit | 8 tests / 71 assertions |
| [G-INT-002](phase-3d7-g-int-002-remediation.md) | Phase 3C reviewer revoke | 14 / 89 |
| [G-INT-003](phase-3d7-g-int-003-remediation.md) | Phase 3D reviewer revoke | 15 / 112 |
| [G-INT-004](phase-3d7-g-int-004-remediation.md) | Phase 3D reviewer Create | 15 / 145 |
| [G-INT-005](phase-3d7-g-int-005-remediation.md) | Phase 3C reviewer Create | 17 / 157 |
| [G-INT-006](phase-3d7-g-int-006-remediation.md) | Regional assignment Create | 21 / 226 |
| [G-INT-007](phase-3d7-g-int-007-remediation.md) | Institutional representation Create | 22 / 216 |

These accepted reports retain their original regressions and actual-action/audit/concurrency/recovery evidence. They are not reopened or interpreted as certification of unrelated service paths.

| K check / final report | Final focused result | Independent concurrency / actual browser | Final disposition |
| --- | --- | --- | --- |
| [K1 Institution Update](phase-3d7-k1-institution-update-remediation.md) | 22 / 189 | Six scenarios; seven interactions/eight captures | PASS targeted remediation. Original audit fault now rolls back; submission header hash/count/routing array preserved, not an independent rehash of every child relation. |
| [K2 Eligibility Create](phase-3d7-k2-eligibility-create-verification.md) | 17 / 134 | No browser or independent concurrency claim | PASS tested creation/denials; audit failure CONTRACT-N/A. No mandatory event was established or fabricated. |
| [K3 Institution Create](phase-3d7-k3-institution-create-remediation.md) | 16 / 211 | Four scenarios; eight interactions/eight captures | PASS targeted remediation. Original fault changes from institution 0→1/audits 3→3 to 0→0/3→3. |
| [K4 Region Update](phase-3d7-k4-region-update-remediation.md) | 20 / 210 | Four scenarios; nine interactions/eight captures | PASS targeted remediation. Original code/name/activity changes now roll back with audit failure. |
| [K5 Region Create](phase-3d7-k5-region-create-remediation.md) | 18 / 204 | Four scenarios; final eight interactions/eight captures | PASS targeted remediation. Original fault changes from Regions 0→1/audits 1→1 to 0→0/1→1. |

Fault evidence distinguishes operation rollback from later PHPUnit fixture cleanup. Before/after audit faults and genuine PostgreSQL rejection are covered by the remediation matrices; K2 has no invented fault boundary. Counts above are separate recorded focused runs, not additive proof of a new combined execution. Historical fixture/locator/assertion errors remain reconciled in the respective reports; their outcomes are not rewritten.

## Latest verified regression checkpoint and currency

The existing private result `storage/app/private/k5-full-final.txt` records **passed: 345 tests, 3,779 assertions, 247,300 ms**. Its recorded file modification time is **21 October 2026, 11:51:04 local**. The K5 report identifies the executed dirty working tree based on Git HEAD **37084d1**. This is the latest reviewed complete PostgreSQL run, not a newly executed run or committed-tree certificate.

`phpunit.xml` discovers `tests/Unit` and `tests/Feature`, with PostgreSQL `osds_test`. The retained Institution Update/Create and Region Update/Create verification/remediation files and EligibilityCreateVerificationTest are present under that Feature directory. Together with the final run report, this supports inclusion of K1–K5 regressions, alongside the earlier G-INT/security/applicant/builder protections. The compact private result contains aggregate counts, not a per-test transcript.

Read-only inspection found **no files modified after that timestamp** within the inspected app, tests, scripts, resources, config, database and routes directories. The working tree contains substantial uncommitted prior work; that fact alone does not invalidate a run explicitly performed on that dirty tree. Subsequent documented work concerns checkpoint/report completion and evidence archival/restoration, rather than runtime/test changes. No later source change invalidating the checkpoint was identified. File timestamps and reports are not a cryptographic whole-tree attestation; no sealed source manifest was established at run time. This new Markdown assessment does not alter executable coverage.

K5 records seven serial browser preservation journeys passing: original applicant reproduction plus 11 expanded cases; original builder reproduction plus 14 selection cases; seven condition/preview/publication checks/six screens; CHEDRO 12 interactions/30 screens; staff 15/94. [Preservation index](screenshots/phase-3d7-k5-remediation/preservation/results.json). Its full Pint `--test`, production Vite build and diff/whitespace gates passed, with existing CRLF advisories. These are historical executed gates, not rerun here. Earlier diagnostic JSON outputs were archived and prior published files restored byte-for-byte with recorded hashes; [archive record](screenshots/phase-3d7-k5-remediation/preservation/unit-evidence/results.json).

## Recovery, accessibility and performance reconciliation

- [Dedicated builder recovery](phase-3d7-builder-recovery-verification.md): all eight scenarios/24 states passed, including navigation protection, validation, aborted Save, committed response loss, HTTP 503, cookie loss and authority denial. Persisted condition/document comparisons remain exact within that matrix. Cookie loss is not natural idle expiry; local recovery is not durable offline storage or proof of every unsaved condition-tree permutation. The historical interrupted builder branch is closed within this coverage.
- [Earlier selector correction](phase-3d7-selector-accessibility.md): four regional-assignment/representation selectors changed from 16×16 to 24×24 CSS px; eight states had zero axe findings, sixteen keyboard cycles passed, and separate recovery preservation passed 14 interactions/16 screens. These findings are resolved. The **Institution Create/Edit primary-region clear control is separate**: K1/K3 each record one target-size node per sampled capture, with no other recorded axe violations. K4/K5 sampled states have zero axe violations. Human accessibility remains open.
- [Recorded timing/workload evidence](phase-3d7-g-int-006-discovery.md): twelve modal trials and six held-response builder cases passed within the existing controlled scenarios. Prior timeout causes remain unestablished. Workloads used 12/36/60 cases, page size ten, one warmup/seven samples: populated intake/evidence query counts 12/10; empty search six and denied identity three. Intake medians 6.972/7.626/9.257 ms, evidence 7.185/8.674/10.878 ms; maximum 18.989 ms. These are warm local operation measurements, not HTTP latency, production capacity or an SLA. No new measurement is claimed.

## Residual-risk register and required decisions

Owners below are **proposed roles**, not assignments or approvals. Decisions must be recorded by the responsible OSDS/CHED decision-maker.

| Item / classification | Residual and proposed owner | Required decision / December alignment |
| --- | --- | --- |
| Eligibility audit — governance question | OSDS business owner with CHED governance/security representative. No specific mandatory grant event exists in the reviewed contract; attribution/reason/timestamps are present. | Explicitly decide whether current sandbox coverage is acceptable for closure/demo or a separately authorized audit requirement is necessary. Resolve the disposition before demonstrating eligibility administration; do not describe it as audited. No missing-event critical defect is inferred. |
| Institution clear target — confirmed noncritical accessibility finding | Development lead and accessibility/QA reviewer. Existing 16px control, distinct from corrected selectors. | Authorize a scoped correction separately, or explicitly accept a documented demonstration limitation/accommodation. Record disposition before demo; a correction would require affected checks, not a global redesign. |
| Human acceptance — unverified | OSDS representative users with QA/accessibility reviewer. | Schedule native 200% zoom/reflow, keyboard/touch/focus, screen-reader labels/errors/recovery announcements, first-time tasks and timed-state contrast. Complete demonstration-critical tasks or explicitly document limitations before demo. Broader accessibility acceptance remains separately scheduled and cannot be claimed from axe. |
| K5 popup timeout — inconclusive reliability observation | QA automation/development lead. First saved-list search timed out after committed records were confirmed; final run passed after hydration/network wait. | Rehearse saved-list recovery in the demonstration browser. If it recurs, retain traces and classify actual persistence/access impact. Neither a production defect nor a conclusively harness-only cause is established. No automatic mutation retry. |
| Nine service-only deferrals — unverified risk | Technical lead and security/governance reviewer. A03/A04/A09–A11/A14–A17. | Explicitly accept bounded deferral or request a particular check. Reassess before exposing new callers or relying on these paths in production. Absence of a located UI is not proof of unreachability/safety; these can be scheduled separately from a demo that does not rely on them. |
| Nine accepted C rows — evidence limitation | Technical lead and OSDS acceptance owner. A01/A02/A05/A06/A08/A13/A23–A25. | Approve bounded functional/authorization evidence despite untested late-audit/concurrency permutations, or identify a specific mandatory gap. Do not relabel them independently fault-verified. |
| Concurrency/retry semantics — implementation limit | Technical lead and QA. Targeted independent workers cover named schedules only. | Accept that operations establish authority at their check, use one transaction attempt and do not certify all SQL/cache/deadlock schedules. Region/Institution edits retain last-writer semantics; separate valid Creates can remain intentional. No universal idempotency or identical retry response. |
| Recovery/duplicates — coverage limit | QA and OSDS demonstration lead. K2 browser duplicate recovery and some idle-expiry/in-flight-option scenarios remain unverified. | Demonstrate saved-record inspection before an explicit repeat action. Do not replay consequential actions automatically or promise durable unsaved work. Decide if any unverified scenario is essential to the planned demonstration before adding a targeted check. |
| Performance/environment — bounded evidence | Technical lead/delivery owner. Warm local timings, blocked external avatars and harness warnings do not establish production behavior. | Accept development-stage measurements for this assessment. Schedule representative deployment capacity/SLA evaluation separately if required; make no production performance claim in December. |
| Historical completeness — outside assessment | CHED data/governance owner. Fixes do not certify or repair historical records. | Acknowledge exclusion. Any historical investigation/repair requires separate scope and authorization; no implied cleanup or certification. |

## Closure checklist and transition recommendation

| Closure condition | Status |
| --- | --- |
| Finite 33 rows reconciled, without expansion | Complete in this assessment. |
| G-INT-001–007 retained; K1–K5 have final outcomes | Complete within linked coverage; K2 audit qualification retained. |
| No unresolved confirmed critical defect in the reviewed bounded results | Supported by final reports; not a universal safety assertion. |
| Latest full PostgreSQL, relevant preservation, Pint/build/diff evidence | Recorded PASS at K5 checkpoint; no new execution. No subsequent executable change identified. |
| Completed selector and dedicated builder recovery evidence reconciled | Complete; separate Institution selector residual remains. |
| Nine C accepts and nine service-only deferrals expressly accepted | **Pending decision-maker sign-off.** |
| Eligibility audit governance and Institution selector disposition recorded | **Pending.** |
| Human accessibility/demo task acceptance or explicit limitations recorded | **Pending.** No full accessibility certification. |
| December recovery rehearsal and residual-risk ownership agreed | **Pending scheduling/approval.** |
| Final closure decision and separate functional-development authorization | **Pending user review.** |

For December delivery, prioritize explicit risk decisions, the Institution selector disposition, representative user task/accessibility checks and a rehearsal of uncertain-response saved-record inspection. Keep the demonstration in its approved nonproduction/synthetic setting and describe eligibility audit and accessibility coverage accurately. There is no evidence here authorizing production use or deployment.

After the decision-maker records conditional acceptance and any required preconditions, recommend returning to the existing **COR/ACSA functional development priorities under separate authorization**, instead of creating another security inventory. Deferred service-only fault schedules, broader capacity evaluation and full human accessibility work can have separate owners/dates where explicitly accepted and not prerequisites of the demonstration. A residual that management makes mandatory must be completed before its corresponding sign-off.

**Stop for review.** CONDITIONAL CLOSE is the recommendation, not an executed closure or approval to start the next phase. No runtime changes, tests, database operations, staging, commit, push, deployment, migration, historical repair, new inventory, Batch H or Phase 4 occurred in this assessment.
