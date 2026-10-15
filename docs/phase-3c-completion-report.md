# Phase 3C completion report

**Date:** 15 October 2026
**Scope:** Synthetic documentary evidence references and nonofficial CHEDRO test findings only. Phase 3D and all official submission, decision, validation, registry, certificate, issuance, and RA 11180 functions remain unimplemented.

## Implemented by work package

### 3C.1 — Evidence security foundation

- Added stable evidence references, append-only revisions, mutation idempotency records, and PostgreSQL binding/immutability triggers.
- Added a strict parser for individual Google Drive files, Docs, Sheets, and Slides. It rejects HTTP, credentials, ports, fragments, folders, Forms, lookalike hosts, malformed identifiers, extra query parameters, and provider mismatches.
- Evidence locator payloads use authenticated AES-256-GCM encryption with a recorded key version. Duplicate comparison uses a separate HMAC fingerprint key. No plaintext URL or Google file identifier column exists.
- Audit sanitization is recursive and rejects nested or prefixed URL, locator, file ID, ciphertext, fingerprint, key, credential, and token fields.

### 3C.2 — Applicant evidence lifecycle

- The applicant **Required documents** step now resolves requirements from the draft's exact manifest and evaluates their shared conditional rules.
- Synthetic SCHOOL applicants can add, replace, remove, open, save, and resume individual evidence links while the manifest remains editable.
- Evidence mutations reauthorize ownership and verified representation, lock the draft, compare the shared lock version, use scoped idempotency keys, append history, and update the lock only after commit.
- Validation and conflicts retain the typed URL in local Livewire state. A successful transaction clears it. Stored decrypted locators are never hydrated into the component.
- **Check progress** combines applicable required questionnaire answers and document counts. Inapplicable evidence history remains preserved and is excluded from current completeness.

### 3C.3 — Immutable synthetic review copy

- Added the plain-language **Send documents for test review** action with an explicit nonofficial/no-submission explanation.
- Each copy binds the exact draft, manifest, module, organization, region, applicant, draft lock version, answer-state hash, applicable requirements, counts, and exact evidence revisions.
- A newer copy requires a separate explicit action and points to the earlier copy. PostgreSQL and model guards prohibit modifying or deleting earlier copies.

### 3C.4 — Region-scoped CHEDRO review

- Admin can create effective-dated synthetic reviewer assignments for an exact reviewer, region, and module, including grantor and reason. Revocation requires an actor, timestamp, and reason and cannot rewrite the original grant.
- Added **Assigned test reviews** in Filament. The ordinary workflow is queue → case → required documents → open document → record finding → history.
- The page persistently states **Test review only — not an official application decision** and hides hashes, internal IDs, revision terminology, and questionnaire answers.
- Accessibility, completeness, and document-match findings have controlled outcomes and append-only correction history. Findings cannot mutate a draft or official status.
- CHEDRO access requires all of: synthetic sandbox guard, active synthetic identity, explicit permission, effective exact region/module reviewer assignment, and current regional scope. Nationwide scope alone is insufficient.
- Evidence opening uses public UUID routes, reauthorizes every request, decrypts only after authorization, reconstructs a destination from a hardcoded Google host/path, and emits no-store/private cache and no-referrer headers.

### 3C.5 — Integration and documentation

- Added eight permissions with provisional SCHOOL, Admin, and CHEDRO role bundles.
- Development fixtures create synthetic CHEDRO assignments only in the isolated local/testing sandbox.
- Added [key rotation, backup, and recovery procedures](/C:/Users/LocalUser.LAPTOP-MGEP8F46/OneDrive%20-%20COMMISSION%20ON%20HIGHER%20EDUCATION/Desktop/OSDS/docs/phase-3c-key-management.md).
- Reused the applicant and Filament design foundation, semantic controls, theme modes, responsive CSS, and existing save-state feedback.

## Database changes

Forward-only migrations create:

- `evidence_references`
- `evidence_reference_revisions`
- `evidence_mutation_idempotency_keys`
- `synthetic_evidence_reviewer_assignments`
- `synthetic_evidence_review_cases`
- `synthetic_evidence_review_requirements`
- `synthetic_evidence_review_items`
- `synthetic_evidence_review_findings`

The follow-up guards add public revision UUIDs and review-copy idempotency and enforce exact draft/manifest/requirement/revision membership, synthetic reviewer identity, append-only records, and immutable bindings in PostgreSQL.

The migrations passed as a forward upgrade over the Phase 3B development database. Clean PostgreSQL migrations run through every `RefreshDatabase` feature test and passed. Existing drafts require no data conversion and remain valid with zero evidence.

## Verification results

| Check | Result |
| --- | --- |
| Focused Phase 3B/3C security and lifecycle suite | Passed: 10 tests, 50 assertions |
| Complete PostgreSQL PHPUnit suite | Passed: 64 tests, 242 assertions |
| Forward migration over development database | Passed: migrations `300000` through `300004` |
| Clean PostgreSQL migrations | Passed through the complete `RefreshDatabase` suite |
| Parser rejection and provider matching | Passed |
| Encryption round trip and old-key read/new-key write | Passed |
| Nested audit locator/file-ID redaction | Passed |
| Idempotent retry and stale-lock rejection | Passed |
| Immutable copy after applicant replacement | Passed |
| Unassigned and out-of-scope CHEDRO denial | Passed |
| Laravel Pint | Passed |
| Vite production build | Passed with Vite 8.3.4 |
| `git diff --check` | Passed |

Automated acceptance demonstrates that a synthetic SCHOOL user can add evidence, retry safely, receive a stale-tab conflict without overwrite, create an immutable test-review copy, and replace later evidence without changing the copy. It also demonstrates that an unassigned CHEDRO reviewer is denied and an explicitly assigned, region-scoped reviewer can append a controlled finding.

## Manual synthetic workflow

### Applicant

1. Sign in at `/applicant/login` as the synthetic SCHOOL fixture.
2. Resume an applicant-sandbox draft and open **Documents**.
3. Add an accepted individual Google file link; confirm **Saved** appears only after the server response.
4. Try a folder or Google Form URL and confirm a safe validation message appears without echoing the URL.
5. Replace or remove a link, leave the page, and resume.
6. Select **Check progress** and resolve any required document counts.
7. Select **Send documents for test review** and verify the nonofficial explanation.
8. Change evidence and use **Send a newer test-review copy**; confirm the earlier copy remains unchanged.

### Admin and CHEDRO

1. Admin opens **Test evidence reviewers** and grants the synthetic CHEDRO fixture an exact region/module assignment.
2. CHEDRO opens **Assigned test reviews**, selects the case, and sees the persistent test-only notice.
3. Open an exact document in a new tab, then record separate accessibility, completeness, and document-match findings.
4. Confirm finding history remains visible and earlier findings are not editable.
5. Revoke the assignment and confirm subsequent queue, case, and link access is denied.

The scripted manual scenarios are ready. Actual first-time CHEDRO staff usability testing, visual checks at 320 px/200% zoom, and assistive-technology review remain pending; this report does not claim formal WCAG conformance.

## Privacy and security limits

- URL syntax validation does not establish file accessibility, authenticity, ownership, sharing audience, permanence, malware safety, or document integrity.
- The browser necessarily receives the authorized Google destination after access is approved.
- No Google content is downloaded, copied, scanned, indexed, or stored.
- No locator-bearing notifications or exports exist.
- Deletion is disabled pending an approved records and privacy schedule.
- Key custody, managed-device external-link rules, production identity/MFA, privacy notice, lawful basis, retention, VAPT, monitoring, hosting, and disaster recovery remain production blockers.

## Outstanding stakeholder decisions

Production use still requires approved Google sharing audiences, evidence custody and revocation rules, finding definitions and comment guidance, region-change handling, privacy classification and rights, records retention, encryption-key custodians, incident response, and official correction/submission/routing/decision policy.

Phase 3C stops here. Phase 3D has not started.
