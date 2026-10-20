# Phase 3D implementation plan

**Status:** Implemented and verified  
**Date:** 19 October 2026  
**Boundary:** Synthetic application submission, intake, correction, resubmission, and tracking only. No official CHED submission, approval, rejection, validation, issuance, certificate, registry entry, or RA 11180 reporting.

## 1. Objective and architectural position

Phase 3D will connect the existing immutable questionnaire manifest, mutable applicant draft, encrypted evidence history, and region-scoped staff controls into a synthetic application lifecycle.

The central design rule is to keep three concepts separate:

1. **Application** â€” the stable synthetic case and its current lifecycle state.
2. **Working revision** â€” mutable questionnaire answers and evidence held in an `application_draft` while the applicant is allowed to edit.
3. **Submitted revision** â€” an immutable copy created atomically from one exact working revision.

Phase 3C `synthetic_evidence_review_cases` remain nonofficial evidence-review exercises. They will not be converted into submissions, reused as application intake records, or linked to lifecycle decisions.

## 2. Current architecture audit

### Reusable controls

| Existing capability | Phase 3D use |
| --- | --- |
| Immutable `publication_manifests` | Every application and submission stays bound to the exact applicant-sandbox manifest, form version, requirement version, and configuration hashes. |
| `ApplicationDraft` immutable binding | Remains the mutable working container for one user, organization, module, and manifest. |
| Shared draft `lock_version` | Serializes answer, evidence, submission, and correction-working-copy operations. |
| `ApplicantDraftService` | Supplies ownership, representation, type/option validation, conditional-answer completeness, transactions, and safe draft events. |
| `EvidenceReferenceService` | Supplies encrypted locator revisions, provider/count validation, conditional applicability, ownership checks, idempotency, and shared-lock mutation. |
| `ConditionalLogic` | Recomputes question visibility and requirement applicability during server-side submission validation. |
| `ApplicantSandboxGuard` | Continues to deny production and non-isolated database access regardless of configuration flags. |
| Eligibility grants and verified representation | Rechecked at submission and resubmission, not inferred from SCHOOL role or organization type. |
| Evidence encryption and secure open routes | Submitted evidence points to exact encrypted evidence revisions; plaintext locators are not copied into submission tables. |
| Phase 3C reviewer-assignment pattern | Informs Phase 3D application assignments, but the evidence-review assignments themselves are not reused. |
| `ScopeAuthorizer` | Provides the regional-scope half of CHEDRO intake authorization. |
| Append-only audit recorder and PostgreSQL trigger function | Records safe lifecycle events and enforces immutable submissions, history, correction requests, and screening records. |
| Applicant and Filament design foundation | Supplies responsive navigation, Light/Dark/System themes, accessible controls, status presentation, and save-state feedback. |

### Gaps and risks

- `application_drafts.status` is constrained to `draft`; no stable application aggregate or submitted state exists.
- Draft answers are mutable rows, and evidence current state is derived from mutable append-only reference history. Neither is a submission snapshot.
- There is no atomic completeness-and-submit transaction, submission idempotency record, revision sequence, or state-transition service.
- Phase 3C review copies omit the full questionnaire and intentionally cannot represent an application submission.
- No database rule currently blocks answer or evidence mutation after a draft has been consumed by a submission.
- No correction request, correction target, replacement working revision, resubmission link, or applicant-safe instruction model exists.
- CHEDRO has no application-intake permission, application reviewer assignment, submitted-answer policy, screening queue, or screening history.
- Region changes, representation revocation after submission, manifest retirement during correction, duplicate applications, withdrawal, deadlines, and routing policy remain unapproved.
- Existing synthetic drafts require a safe forward backfill into application aggregates without changing their public draft URLs or manifest bindings.

## 3. Provisional state machine

The technical lifecycle is deliberately narrow:

```text
Draft
  â””â”€ Submit â†’ Submitted
                 â””â”€ Begin screening â†’ Under screening
                                            â””â”€ Request correction â†’ Correction requested
                                                                          â””â”€ Resubmit â†’ Resubmitted
                                                                                           â””â”€ Begin screening â†’ Under screening
```

This is a synthetic test workflow and does not establish CHED policy.

### Transition rules

| Transition | Actor | Required guards | Result |
| --- | --- | --- | --- |
| Start application â†’ Draft | Synthetic SCHOOL | Active account, portal permission, verified representation, explicit organization/module eligibility, active applicant manifest | Stable application plus initial open working revision. |
| Draft â†’ Submitted | Owning synthetic SCHOOL user | Submission permission, ownership, current representation and eligibility, editable manifest, exact open working revision, current lock, complete answers/evidence | Immutable submission revision 1; working revision consumed. |
| Submitted â†’ Under screening | Assigned synthetic CHEDRO reviewer | Intake permission, active exact region/module assignment, regional scope, exact current submission | Append-only screening event and status history. |
| Under screening â†’ Correction requested | Assigned synthetic CHEDRO reviewer | Correction permission, at least one applicant-visible correction item, active manifest under the provisional rule | Immutable correction request plus new open correction working revision cloned from the last submission source. |
| Correction requested â†’ Resubmitted | Owning synthetic SCHOOL user | Same applicant guards, exact correction working revision, current lock, all current completeness rules, unresolved current request | New immutable submission with the next sequence; correction request linked as answered; working revision consumed. |
| Resubmitted â†’ Under screening | Assigned synthetic CHEDRO reviewer | Same intake guards and exact latest submission | New append-only screening cycle. |

No transition to Approved, Rejected, Validated, Issued, Registered, Certified, Withdrawn, or Closed will exist in Phase 3D.

### State enforcement

- One domain lifecycle service is the only application state mutation path.
- A PostgreSQL transition trigger permits only the transitions above.
- Every transition locks the application row and relevant working draft.
- `application_state_history` is append-only and must contain the exact prior/new state, actor, event time, submitted revision where applicable, and correlation ID.
- UI stage labels never mutate state by themselves.

## 4. Proposed database schema

### `applications`

Stable synthetic case:

- `id`, UUID `public_id`;
- immutable `module_id`, `publication_manifest_id`, `institution_id`, and `applicant_id`;
- captured `routing_region_id` from the institution at initial submission;
- constrained `state`: `draft`, `submitted`, `under_screening`, `correction_requested`, or `resubmitted`;
- nullable `current_submission_id` and `current_working_revision_id` pointers validated against the same application;
- `lock_version`, created/updated timestamps.

The UUID is an authorization-safe route identifier, not an official tracking number. The applicant UI will call it **Test application reference** only in secondary details.

### `application_working_revisions`

Maps each mutable `application_draft` to its stable application:

- application and exact draft;
- monotonically increasing working revision number;
- purpose: `initial` or `correction`;
- state: `open` or `consumed`;
- optional source submitted revision and correction request;
- creator, created time, consumed time, and consuming submitted revision.

Exactly one open working revision is allowed per application using a PostgreSQL partial unique index. Existing drafts are backfilled as initial open working revisions.

### `application_submissions`

Immutable submission header:

- UUID `public_id`, application, submission sequence;
- exact source working revision and source draft;
- exact manifest, module, organization, routing region, and applicant;
- synthetic applicant and organization display snapshots needed to understand the record at submission time; no unapproved identity fields;
- exact source draft lock version;
- canonical answer, requirement, evidence, and combined submission hashes;
- submitted actor and timestamp;
- optional prior submission and answered correction request;
- scoped UUID idempotency key and correlation ID.

Unique constraints cover `(application_id, submission_sequence)`, source working revision, and `(application_id, submitted_by, idempotency_key)`.

### Snapshot children

**`application_submission_answers`** stores the exact question, stable key, typed JSONB value, applicability, and display order. All saved answers are copied, including currently hidden values, with an explicit applicability flag. Applicant and CHEDRO views show only policy-permitted applicable values by default.

**`application_submission_requirements`** stores the exact documentary requirement, applicability, required flag, configured minimum/maximum, active evidence count, and display order.

**`application_submission_evidence_items`** binds each applicable copied requirement to the exact `evidence_reference_revision`. It stores no plaintext or duplicate encrypted locator.

PostgreSQL guards verify that questions and requirements belong to the submission manifest, evidence revisions belong to the source draft and copied requirement, copied counts agree with items, and all snapshot rows reject update/delete.

### Lifecycle and correction tables

**`application_state_history`** is append-only and contains state transitions and safe applicant-visible summaries where permitted.

**`application_screening_events`** is append-only and records `screening_started` and other nondecisional Phase 3D intake events. It cannot encode approval, rejection, validation, issuance, or final disposition.

**`application_correction_requests`** is append-only and binds the exact application and submitted revision, reviewer, request time, applicant-visible summary, and correlation ID. It has no mutable status column; response is established by the later submission link.

**`application_correction_items`** is append-only and contains:

- target kind: `question`, `document_requirement`, or `general`;
- exact question or requirement when targeted;
- controlled issue category;
- applicant-visible instruction;
- safe display order.

Private staff notes are not mixed into applicant-visible correction instructions. Phase 3D will omit private notes unless a separate need and retention policy are approved.

### CHEDRO authority

**`synthetic_application_reviewer_assignments`** separately grants one synthetic CHEDRO reviewer authority for an exact region and module with effective dates, grantor, reason, and append-only revocation history.

Phase 3C evidence-review assignments do not grant application-intake access.

## 5. Immutable revision and correction strategy

### Initial submission

The current draft becomes the source of submission revision 1. The service copies answers and exact evidence revision memberships, marks the working-revision mapping consumed, and leaves the draft rows preserved and database-protected.

### Correction working revision

When CHEDRO requests correction, the service creates a new `application_draft` bound to the same application, applicant, organization, module, and exact manifest. It clones the prior submission's source working data:

- answer values, including hidden values and their exact question IDs;
- active evidence into new evidence references/revisions by copying ciphertext, key version, and fingerprint without decrypting locators;
- no submitted row is edited or repointed.

The correction draft receives its own lock version and idempotency scope. Applicant edits continue through the existing answer/evidence services after they are hardened to require an open working revision.

### Manifest retirement policy

The current Phase 3B/3C rule remains the default: an expired or retired manifest is viewable but not editable. Under Phase 3D, CHEDRO cannot create a new correction request for such a manifest, and an existing correction working revision becomes read-only if the manifest ends before resubmission. Whether an already-submitted case may continue corrections under a retired form requires explicit OSDS approval.

### Region and identity changes

The routing region is captured at initial submission and does not silently follow a later institution-region change. Representation and eligibility are rechecked for submission and resubmission. Revocation blocks further applicant mutation while preserving tracking and immutable history. Reassignment or region-transfer behavior is deferred.

## 6. Atomic submission and idempotency

`submitWorkingRevision` will use one PostgreSQL transaction:

1. Enforce the isolated synthetic sandbox.
2. Lock application and open working-revision/draft rows.
3. Return the prior committed result when the same scoped idempotency key already completed.
4. Reauthorize active synthetic identity, explicit permission, ownership, verified representation, organization/module eligibility, exact manifest, and allowed application state.
5. Compare the expected application and draft lock versions.
6. Recompute question visibility and documentary applicability from saved server-side answers.
7. Validate all value types/options and every applicable required answer.
8. Resolve active evidence revisions and validate provider membership and applicable minimum/maximum counts.
9. Compute canonical snapshot hashes.
10. Insert the immutable submission header and children.
11. Mark the working revision consumed, advance application state/pointers/version, append state/audit events, and commit.

No **Submitted** confirmation is shown before commit. A stale version, authorization change, validation failure, or database failure leaves no submission, state transition, consumed draft, or partial snapshot.

The idempotency response contains safe application/submission UUIDs, sequence, committed state, and timestamp. Duplicate retries create one submitted revision and one transition.

## 7. Authorization and routing

### Proposed permissions

| Permission | Synthetic role assignment |
| --- | --- |
| `applications.submit` | SCHOOL |
| `applications.track.own` | SCHOOL |
| `applications.corrections.view.own` | SCHOOL |
| `applications.resubmit` | SCHOOL |
| `applications.intake.view` | CHEDRO |
| `applications.intake.answers.view` | CHEDRO, explicitly separate |
| `applications.intake.evidence.open` | CHEDRO |
| `applications.screening.start` | CHEDRO |
| `applications.corrections.request` | CHEDRO |
| `applications.screening.history.view` | CHEDRO |
| `applications.reviewer.assignments.manage` | Admin for synthetic setup |

Every applicant operation also requires the sandbox guard, active synthetic identity, ownership, current representation, explicit eligibility, exact manifest/application binding, and appropriate working/application state.

Every CHEDRO operation requires the sandbox guard, active synthetic identity, explicit action permission, effective exact region/module application-reviewer assignment, and current `ScopeAuthorizer` access to the captured routing region. Nationwide scope alone does not replace assignment.

Direct routes use UUID binding and return 404 for another applicant or an out-of-scope reviewer so they do not reveal application existence.

### Submitted answer access

CHEDRO never receives access to mutable applicant drafts. Submitted answers appear only when the reviewer has `applications.intake.answers.view` plus exact case authorization. Hidden/inapplicable answers remain unavailable in the ordinary intake view. Any broader disclosure requires separate privacy approval.

Evidence opening reuses the controlled decrypt-and-reconstruct approach but verifies membership in the exact submitted revision rather than a Phase 3C review copy.

## 8. Applicant experience

### Navigation

- Home
- Start application
- My applications
- Account and organization
- Help

### Workflow

```text
Complete form â†’ Add documents â†’ Review â†’ Submit test application â†’ Track
```

The Review step shows:

- organization and service;
- saved questionnaire sections;
- applicable document completeness;
- exact synthetic-only explanation;
- unresolved errors linked to the relevant field;
- one consequential **Submit test application** action with confirmation.

After submission, **My applications** opens a tracking page rather than the consumed draft. It shows plain-language current status, submission/resubmission date, latest activity, pending applicant action, applicant-visible correction instructions, and chronological history.

It does not show staff identities, private notes, internal IDs, hashes, evidence locators, or an invented official tracking number.

For correction requests, the page presents each affected question/document and instruction, then offers **Make corrections**. The new working revision retains normal Unsaved/Saving/Saved/Save failed/Updated in another tab behavior. Resubmission uses a separate confirmation and server-side validation.

Session expiration, network failure, validation failure, and stale-tab conflicts preserve local input and never display a false success state.

## 9. CHEDRO intake experience

```text
Assigned test applications â†’ Open submission â†’ Begin screening â†’ Review submitted answers/documents â†’ Request correction when necessary â†’ Continue screening after resubmission
```

The Filament workspace will provide:

- a persistent **Synthetic intake only â€” not an official CHED decision** notice;
- assigned region/module queue with status, organization, service, and submitted/resubmitted time;
- exact submitted revision, applicable answers, requirements, and evidence membership;
- controlled secure evidence opening;
- correction builder with question/document targets and applicant-visible instructions;
- append-only state and screening history.

There will be no Approve, Reject, Validate, Issue, Certificate, Registry, Final decision, or official case-number control.

## 10. Security, privacy, and audit safeguards

- Snapshot rows, correction requests/items, screening events, and state history are append-only at model and PostgreSQL levels.
- Consumed working drafts reject answer/evidence insert, update, and delete through services and database triggers.
- Evidence ciphertext is referenced, never decrypted or duplicated into submission records.
- Audit metadata may contain safe UUIDs/IDs, sequence, state, manifest/module/region/organization, counts, hashes, lock versions, transition codes, and correlation IDs.
- Audit metadata, logs, exceptions, notifications, and unauthorized Livewire state must exclude answer values, correction text, reviewer remarks, URLs, file IDs, ciphertext, fingerprints, keys, and decrypted locators.
- Applicant-visible correction text is application content, not audit metadata. Length limits, plain-text rendering, and output escaping prevent executable content.
- No public or anonymous tracking endpoint is added.
- Rate limits cover submission, resubmission, correction creation, secure evidence opening, and status polling.
- CSRF, mass-assignment, forged target IDs, cross-manifest membership, cross-organization access, and stale state are tested explicitly.
- Retention and deletion remain disabled pending an approved records schedule.

## 11. Forward-only migration and compatibility plan

1. Create `applications`; backfill one stable application for every existing draft without changing draft public IDs.
2. Create and backfill `application_working_revisions`; add validated application linkage and one-open-working-revision constraint.
3. Create immutable submission headers, answer/requirement/evidence snapshots, hashes, and idempotency constraints.
4. Add exact-manifest membership, sequence, snapshot consistency, and append-only PostgreSQL guards.
5. Create state history and nondecisional screening events.
6. Create correction requests/items and exact-target guards.
7. Create synthetic application-reviewer assignments and append-only revocation controls.
8. Add consumed-working-revision answer/evidence mutation rejection triggers.
9. Seed permission definitions and provisional role bundles; add only synthetic local/testing fixtures.

Backfill defaults every existing draft to `applications.state = draft` with one initial open working revision. Existing draft routes, answers, evidence, lock versions, and Phase 3C review copies remain unchanged. No existing table or column is renamed or removed.

Clean PostgreSQL migration and forward upgrade from the committed Phase 3C schema are mandatory gates. A rollback strategy will be documented for deployment, but application migrations remain forward-only.

## 12. Sequential implementation work packages

### 3D.1 â€” Application aggregate and working-revision foundation

Create/backfill applications and working revisions, add permissions/policies, lifecycle enum/service skeleton, open/consumed database guards, and synthetic application-reviewer assignments.

**Gate:** clean and upgrade migrations pass; every Phase 2â€“3C test remains green; existing drafts open unchanged; cross-user/organization and implicit reviewer access fail.

### 3D.2 â€” Atomic immutable submission

Implement snapshot schema, canonical hashing, authoritative completeness validation, transaction, idempotency, consumed-draft protection, submission confirmation, and rollback tests.

**Gate:** one complete draft produces one immutable revision; duplicate/stale/forged/incomplete/retired/unauthorized requests produce no partial state; later database mutation is rejected.

### 3D.3 â€” Applicant review, submission, and tracking

Add Review stage, consequential confirmation, own-application list/tracking page, status history, safe references, responsive themes, and failure/session recovery.

**Gate:** a first-time synthetic applicant can review, submit once, leave, sign in, and track without seeing private staff data or mutable controls.

### 3D.4 â€” Region-scoped CHEDRO intake

Add reviewer-assignment administration, assigned queue, exact submitted-revision view, separately permitted applicable-answer access, secure submitted-evidence opening, begin-screening transition, and append-only history.

**Gate:** permission, exact assignment, and region scope are all required; mutable drafts and out-of-scope application existence remain undisclosed.

### 3D.5 â€” Correction and resubmission

Add targeted correction requests, immutable applicant instructions, atomic correction-draft cloning, open/consumed enforcement, resubmission snapshots, and repeated screening cycles.

**Gate:** prior submissions never change; the applicant edits only the new working revision; resubmission is complete, idempotent, concurrency-safe, and linked to the exact correction request.

### 3D.6 â€” Integration, usability, and documentation

Run full regression/security/accessibility checks, manual synthetic applicant and CHEDRO scripts, migration verification, logging/redaction review, Pint, production build, and diff checks. Produce `docs/phase-3d-completion-report.md` and staff guides.

**Gate:** all completion checks pass. Stop before any Phase 4 or official workflow.

## 13. Automated acceptance tests

### Submission and snapshots

- Complete applicable answers and evidence submit successfully from the exact open working revision.
- Missing/invalid applicable answers, options, evidence providers, evidence counts, or forged IDs fail server-side.
- Hidden answers are copied with applicability metadata and are excluded from required validation and ordinary CHEDRO display.
- Exact evidence revisions remain stable after later correction-draft replacement/removal.
- Snapshot hashes are deterministic; material answer/evidence changes alter the appropriate hash.
- Submitted rows, children, source bindings, state history, screening events, and correction records reject update/delete.
- Consumed drafts reject answer and evidence mutation at service and direct SQL levels.

### Transactions and concurrency

- Two-tab submission: one succeeds; the stale request cannot create another revision or consume another draft.
- Same idempotency key returns the first committed submission without another sequence or event.
- Different concurrent keys still result in one allowed transition.
- Failure during any snapshot child, history, or audit write rolls back the header, state, and consumed marker.
- Representation, eligibility, account, manifest, assignment, or scope revocation between page load and action blocks the transaction.

### Corrections

- Only an assigned in-scope reviewer may request correction from `under_screening`.
- Each request has at least one valid applicant-visible item belonging to the exact submitted manifest.
- Correction cloning preserves exact question bindings and encrypted evidence without plaintext exposure.
- Applicant cannot edit submitted rows or another working revision.
- Resubmission links exact prior submission and correction request and increments sequence once.
- Multiple correction cycles preserve every earlier submission and request.
- Retired/expired-manifest correction behavior follows the provisional read-only rule.

### Authorization and privacy

- Applicant ownership, verified representation, explicit eligibility, and sandbox isolation are required.
- CHEDRO permission, exact application-reviewer assignment, and routing-region scope are independently required.
- Phase 3C reviewer assignment grants no Phase 3D intake authority.
- Direct UUID routes return 404 for cross-user, cross-organization, cross-region, unassigned, suspended, revoked, and nonsynthetic access.
- Raw answers, correction instructions, staff remarks, evidence URLs/file IDs/ciphertext/fingerprints/keys do not enter logs, audit metadata, exceptions, notifications, or unauthorized Livewire payloads.
- No transition or route can create approval, rejection, issuance, certificate, registry, or official tracking records.

### Regression and interface

- All Phase 2, 3A, 3B, 3C, Form Builder, publication, audit, and PostgreSQL tests remain passing.
- Keyboard operation, focus, confirmation dialogs, error announcements, Light/Dark/System, reduced motion, mobile/tablet/desktop, and 200% zoom are checked.
- Laravel Pint, production Vite build, route authorization smoke tests, and `git diff --check` pass.

## 14. Manual acceptance scenarios

### Synthetic applicant

1. Open an existing draft and confirm saved answers/documents.
2. Open Review and correct linked completeness errors.
3. Submit the test application and confirm the warning distinguishes it from an official filing.
4. Refresh and retry the action; confirm there is still one submitted revision.
5. Track the Submitted and Under screening history.
6. Receive a correction request and understand every affected item without assistance.
7. Open the new correction working revision, edit, save, leave, and resume.
8. Trigger a stale-tab conflict and confirm local input remains recoverable.
9. Resubmit and confirm the prior submission remains visible in history.

### Synthetic CHEDRO

1. Admin grants an exact synthetic region/module application-reviewer assignment.
2. Reviewer opens Assigned test applications and begins screening.
3. Reviewer sees only the immutable current submission and specifically authorized applicable answers/evidence.
4. Reviewer creates question-, document-, and general-correction items with applicant-safe instructions.
5. Reviewer observes resubmission as a new revision and restarts screening.
6. Assignment revocation and cross-region direct URLs deny later access.
7. Confirm no decision, approval, rejection, issuance, registry, or certificate action exists.

Record task completion, elapsed time, navigation errors, unclear statuses/instructions, assistance required, stale/network recovery, responsive behavior, both themes, and suggested improvements. Actual staff testing must be marked pending unless conducted.

## 15. Outstanding OSDS policy decisions and production blockers

| Decision | Required before |
| --- | --- |
| Official application lifecycle, service-level statuses, and responsible offices | Production workflow |
| Whether correction is allowed after manifest retirement/expiry | 3D implementation approval or correction pilot |
| Duplicate/concurrent application rules per organization and module | Real pilot |
| Whether representation/eligibility revocation blocks tracking as well as mutation | Real applicant policy |
| Region transfer and reviewer reassignment after initial submission | CHEDRO routing pilot |
| Exact questionnaire answers CHEDRO may view and why | Any real answer disclosure |
| Correction categories, instruction guidance, deadlines, escalation, and abandonment | CHEDRO pilot |
| Applicant withdrawal and CHEDRO administrative closure | Later workflow approval |
| Official acknowledgment and tracking-number format | Official submission approval |
| Privacy notice, lawful basis, classification, access rights, and breach response | Real applicant data |
| Submission, correction, evidence, screening, and audit retention | Production deployment |
| Evidence sharing/custody and external-link security | Real evidence |
| Notifications and approved communication channels | Any external notification |
| Approval, rejection, validation, registry, certificate, and issuance policy | Phase 4 or later |

Production also remains blocked on approved hosting and isolated environments, privileged MFA, recovery and emergency access, key custody, VAPT, monitoring/redaction validation, accessibility assessment, records schedule, backup/restore testing, and observable CI evidence.

## 16. Approval boundary

This plan authorizes no code, migration, permission, seed, route, or UI change. Implementation must wait for explicit approval, proceed sequentially through 3D.1â€“3D.6, and stop at any failed migration, authorization, immutability, concurrency, privacy, or PostgreSQL gate.

Phase 3D remains synthetic-only. Phase 4 and all official decisions, certificates, registry, issuance, and RA 11180 work remain unstarted.

