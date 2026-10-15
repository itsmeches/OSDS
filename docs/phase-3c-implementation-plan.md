# Phase 3C implementation plan

**Status:** Proposed for review; implementation is not authorized
**Date:** 15 October 2026
**Boundary:** Synthetic documentary evidence references and synthetic CHEDRO findings only. No official submission, decision, COR/ACSA validation, registry, certificate, signature, issuance, or RA 11180 reporting.

## 1. Architecture audit

Phase 3C can reuse the immutable publication manifest, manifest-bound application drafts, shared conditional logic, draft lock version, idempotency pattern, verified representation checks, strict sandbox guard, regional ScopeAuthorizer, provider catalogue, append-only audit history, Filament staff panel, and shared applicant UI.

The audit found these gaps:

- The applicant Documents step is currently read-only.
- There is no evidence storage, provider parser, encryption service, or authorized link resolver.
- The existing audit sanitizer is shallow and does not explicitly reject nested locator fields.
- CHEDRO has no evidence permissions, reviewer assignment, case policy, queue, or finding model.
- Mutable drafts are unsafe review targets because later applicant edits could change what a finding appears to describe.
- Staff currently have no applicant-answer interface. This boundary should remain until separately approved.
- Every CHEDRO query and direct route must explicitly apply region scope; navigation visibility is insufficient.

## 2. Proposed schema

### Evidence references

**evidence_references** provides a stable UUID identity bound immutably to one application draft and one documentary requirement. The requirement must belong to the draft manifest's exact requirement-set version. Rows are not deleted through ordinary operations.

**evidence_reference_revisions** is append-only and stores:

- reference and monotonic revision number;
- action: added, replaced, or removed;
- provider type;
- encrypted locator and encryption-key version;
- keyed locator fingerprint for duplicate detection;
- optional encrypted applicant label;
- actor, draft lock version, and timestamp.

Current state is derived from the newest revision. Replacement appends a revision; removal appends a tombstone. PostgreSQL rejects updates and deletes.

**evidence_mutation_idempotency_keys** stores draft, user, operation, UUID key, resulting lock version, and resulting reference/revision IDs so retries return the original result.

### Reviewer authority

**synthetic_evidence_reviewer_assignments** binds a synthetic CHEDRO reviewer to one region and module with effective dates, grant/revocation actors, reason, and immutable history.

### Stable review copy

**synthetic_evidence_review_cases** is an immutable nonofficial copy header binding the draft, manifest, module, institution, region, applicant, exact draft lock version, safe answer-state hash, creator/time, and optional superseded-case reference.

**synthetic_evidence_review_requirements** records exact requirement, applicability, required status, minimum/maximum counts, active count, and order at copy time.

**synthetic_evidence_review_items** binds each case requirement to the exact evidence revision reviewed.

**synthetic_evidence_review_findings** is append-only and records:

- exact case requirement and optional exact evidence revision;
- reviewer and review timestamp;
- finding type: accessibility, completeness, or document match;
- controlled outcome;
- safe comment;
- optional superseded-finding reference and correlation ID.

Corrections append a superseding finding. Findings cannot approve, reject, validate, issue, or mutate an application.

## 3. Evidence lifecycle

1. Resolve requirements exclusively from the draft's immutable manifest.
2. Evaluate applicability from current saved answers with the shared conditional engine.
3. Applicant adds one individual Google file link.
4. Server validates provider, locator, counts, ownership, representation, manifest state, draft lock version, and idempotency key.
5. One transaction locks the draft, writes the reference/revision, increments the shared lock version, and appends safe history.
6. Replace and remove append revisions.
7. If an answer makes a requirement inapplicable, evidence history remains visible to its owner as **Currently not required**, but is excluded from completeness and new review copies.
8. Manifest retirement or expiry preserves evidence while blocking mutation and new review-copy creation.

The platform will not download, copy, preview, scan, or store external document contents.

## 4. Validation and encryption

Phase 3C accepts HTTPS links only:

| Provider | Accepted form |
| --- | --- |
| Google Drive file | drive.google.com/file/d/{fileId} and controlled drive.google.com/open?id={fileId} input |
| Google Docs | docs.google.com/document/d/{fileId} |
| Google Sheets | docs.google.com/spreadsheets/d/{fileId} |
| Google Slides | docs.google.com/presentation/d/{fileId} |

Reject folders, Google Forms, unsupported products, HTTP, credentials, custom ports, host lookalikes, malformed IDs, redirect parameters, provider/path mismatches, providers not accepted by the requirement, duplicate active locators, and excessive lengths.

Syntax validation never claims accessibility, authenticity, ownership, permanence, or integrity.

Introduce an EvidenceLocatorCipher using authenticated encryption, a dedicated versioned evidence key ring, and a separate HMAC key for duplicate fingerprints. No plaintext URL or file ID column is stored. Models hide ciphertext, fingerprints, and key details. Raw Livewire input is cleared after a confirmed save.

Authorized opening uses a route containing only public UUIDs. After ownership or reviewer authorization, the service decrypts and reparses the locator and constructs a destination from a hardcoded Google base URL plus validated file ID. It returns no-store and strict referrer headers. The authorized browser will necessarily see the Google destination.

## 5. Applicant workflow

**My drafts → Resume → Documents → Add/replace/remove individual links → Check completeness → Save/resume**

- Requirements display as Required, Optional, or Currently not applicable.
- Each shows accepted Google types and current versus minimum/maximum counts.
- Errors explain the correction without echoing the locator.
- Evidence and answer mutations share the draft lock, conflict behavior, and save-state language.
- Combined progress includes missing applicable answers and incomplete applicable documents.
- Stored URLs are never hydrated back into ordinary component state.
- Applicants may use an explicitly labeled **Create synthetic review copy** action once applicable required counts are complete. It creates a nonofficial immutable copy, does not freeze the draft, and creates no submission or tracking number.
- Later changes require an explicit newer copy and never alter an earlier copy.

## 6. CHEDRO workflow

**Synthetic evidence reviews → Region/module queue → Case → Requirement → Open exact reference → Record findings → History**

The Filament workspace uses plain labels such as Needs findings, Findings recorded, and Superseded copy. It displays organization, module, region, copy time, requirements, and safe evidence labels. It includes a persistent synthetic/nonofficial banner.

There are no Approve, Reject, Validate application, Request correction, Issue, Certificate, Registry, or Submit controls.

CHEDRO will not see general applicant questionnaire answers in 3C. Any contextual answer disclosure requires separate privacy and policy approval.

## 7. Authorization and region scope

Proposed permissions:

| Permission | Provisional role |
| --- | --- |
| evidence.references.manage | SCHOOL |
| evidence.references.open.own | SCHOOL |
| evidence.review.copy.create | SCHOOL |
| evidence.reviewer.assignments.manage | Admin for synthetic setup |
| evidence.reviews.view | CHEDRO |
| evidence.reviews.open | CHEDRO |
| evidence.review.findings.create | CHEDRO |
| evidence.review.history.view | CHEDRO |

Applicant operations additionally require the strict sandbox environment/database, active synthetic identity, verified representation, ownership, eligibility, exact applicant manifest, and editable publication.

CHEDRO operations additionally require active synthetic identity, explicit permission, effective reviewer assignment for exact region/module, current regional scope, and exact case/revision membership. Nationwide access does not replace reviewer assignment. Direct out-of-scope routes return 404. Revocation blocks later access immediately.

## 8. Audit and privacy safeguards

Audit safe identifiers and outcomes for add, replace, remove, conflict, completeness check, review-copy creation/supersession, reviewer assignment, authorized open, denial, and finding creation.

Allowed metadata includes resource IDs, manifest/module/region/institution/requirement, provider code, revision number, counts, lock version, outcome code, and correlation ID.

Never include URL, file ID, ciphertext, fingerprint, applicant label, answer values, reviewer comment, query parameters, or document content. Add recursive audit sanitization and request/exception redaction tests. No 3C exports or locator-bearing notifications are added.

Retention remains configurable, but deletion remains disabled until CHED approves a records and privacy schedule.

## 9. Migration and compatibility

Forward-only order:

1. Evidence reference, revision, and idempotency tables.
2. PostgreSQL exact-requirement, accepted-provider, immutable-binding, and append-only guards.
3. Reviewer assignments and effective-date/synthetic guards.
4. Immutable review-case, requirement, and item tables.
5. Append-only findings and controlled outcome constraints.
6. Owner/draft and region/module queue indexes.
7. Permission definitions and provisional role bundles.
8. Synthetic fixtures only through DevelopmentFixtureSeeder.

Existing drafts remain valid with zero evidence. No current columns are renamed or removed. Answer persistence, routes, manifest immutability, and Phase 2–3B permission meanings remain unchanged. Both clean PostgreSQL migration and forward upgrade must pass.

## 10. Work packages

### 3C.1 — Evidence security foundation

Schema, encryption/key versioning, provider adapters, PostgreSQL guards, models, and safe serializers.

**Gate:** migration, encryption, key rotation, parser, append-only, cross-manifest, accepted-provider, and plaintext-leak tests pass.

### 3C.2 — Applicant evidence lifecycle

Policies, transactional add/replace/remove, shared lock version, idempotency, applicability, completeness, and Documents UI.

**Gate:** owner save/resume plus stale-tab, retry, count, forged-ID, retired-manifest, revoked-representation, and conditional tests pass.

### 3C.3 — Immutable synthetic review copy

Explicit nonofficial copy of exact evidence revisions, applicability/counts, region, manifest, and lock version.

**Gate:** later draft edits cannot change the copy; incomplete, stale, forged, cross-manifest, retired, and duplicate requests leave no partial state.

### 3C.4 — Region-scoped CHEDRO review

Reviewer assignments, policies, scoped Filament queue, secure link opening, append-only findings, and history.

**Gate:** only an assigned reviewer with matching permission and scope can access the exact synthetic case.

### 3C.5 — Integration and documentation

Combined progress, responsive themes, safe errors, guides, privacy review, full regression, and completion report.

**Gate:** complete suite, migrations, authorization smoke tests, Pint, production build, git diff check, and manual synthetic scenarios pass. Stop before Phase 3D.

## 11. Acceptance tests

Automated coverage will include:

- every accepted Google pattern and all rejected schemes, hosts, folders, products, redirects, IDs, and provider mismatches;
- ciphertext/plaintext separation, keyed duplicate detection, old-key reads, current-key writes, and leak detection across logs, audits, exceptions, notifications, hydration, and responses;
- applicant ownership, representation, eligibility, manifest binding, conditions, counts, retirement, concurrency, idempotency, and rollback;
- immutable review-copy membership and stability after applicant replacement/removal;
- CHEDRO permission plus reviewer assignment plus region/module scope;
- append-only exact-revision findings and superseding corrections;
- proof that findings cannot produce official decisions or mutate drafts;
- all Phase 2, 3A, 3B, Form Builder, publication, applicant, audit, and PostgreSQL regressions;
- keyboard, error announcements, light/dark/system, mobile, tablet, desktop, 200% zoom, and reduced motion.

Manual applicant testing covers add/reject/replace/remove, resume, stale tabs, completeness, review-copy creation, later draft changes, and retirement. Manual CHEDRO testing covers queue access, exact link opening, three finding types, correction history, cross-region denial, and assignment revocation.

## 12. Decisions and production blockers

| Decision | Required before |
| --- | --- |
| May applicants create review copies, and may a newer copy supersede one? | 3C implementation approval |
| Who manages synthetic reviewer assignments? | 3C implementation approval |
| May CHEDRO see selected questionnaire answers? | Any answer disclosure |
| Approved Google sharing audience and reviewer identities/groups | Production evidence access |
| Manual-only accessibility checks or Google API integration | Any automation |
| Definitions and comment guidance for the three finding types | CHEDRO pilot |
| Region changes after a review copy | CHEDRO workflow approval |
| Link ownership, custody, sharing revocation, and preservation | Real evidence |
| Privacy notice, lawful basis, classification, rights, and breach response | Real evidence |
| Evidence, findings, access-log, and key retention | Production deployment |
| Encryption key custody, rotation, backup, and compromise response | Production deployment |
| Managed-device external-link policy and content safety requirements | CHEDRO deployment |
| Official correction, submission, withdrawal, routing, decision, and issuance | Phase 3D/Phase 4 |

Production also remains blocked on approved hosting, isolated environments, privileged MFA, recovery/emergency access, VAPT, monitoring/redaction validation, accessibility assessment, records schedule, and observable CI evidence.

## 13. Approval boundary

This plan authorizes no code, schema, permission, seed, or UI change. Implementation must wait for approval, proceed sequentially through 3C.1–3C.5, and stop before Phase 3D.
