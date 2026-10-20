# G-INT-001 - Finding/audit atomicity remediation

**20 October 2026. G-INT-001 fixed; targeted gates passed.** Broader Batch G resumed, then stopped at independent G-INT-002. No assignment-operation fix or historical repair was applied.

## Root cause and boundary

The original service inserted an append-only finding before separately inserting its audit event, outside an operation transaction. The retained actual Livewire/PostgreSQL regression injected only the downstream audit failure: the action reported an unconfirmed result, finding count increased from zero to one, and audit count did not change. Original evidence is retained in the issue register and batch-g-atomicity-reproduction.txt; no historical outage or record-impact assessment was performed.

SyntheticEvidenceReviewService::finding now uses Laravel DB::transaction on the default PostgreSQL connection. It reloads the actor and immutable requirement, authorizes the exact case/module/region and revision membership, validates controlled outcomes, inserts the finding and records its mandatory audit before returning. Both models use the default connection; tests compare actual PDO identity and assert a nested operation transaction level above PHPUnit's fixture transaction. A partial audit insert followed by a thrown failure rolls back both rows. No asynchronous or best-effort audit is used, and the operation has no automatic transaction retry.

Previously committed findings/audits are not updated or deleted. Rollback concerns only the new attempt. Existing attribution, case/revision binding, UTC interpretation, permissions and append-only guards remain unchanged. No migration, new permission or historical repair.

## Feedback and response loss

Finding success uses a locked, fixed-text inline component confirmation after the paired service returns. It does not queue a session success toast that can appear outside the delivered action response. Finding controls are disabled during the pending save so later remarks cannot be confused with the acknowledged payload. On uncertainty, existing recovery handling hides old confirmation, retains local remarks, disables normal consequential submits and provides native refresh/history inspection. Reviewers without history permission must ask an authorized reviewer; no extra history authority is granted.

A lost successful response means both rows may exist. The browser gate drops a response after server persistence, checks one paired effect, verifies blocked replay, reloads through the authorized route, opens actual history with the keyboard and proves a blank retry is rejected by existing validation. There is no schema or service idempotency token: explicit direct repeated service calls still append distinct legitimate findings. Stronger domain deduplication would require separate approval.

## Files

- app/Services/Evidence/SyntheticEvidenceReviewService.php: atomic authoritative boundary.
- app/Filament/Pages/SyntheticEvidenceReviews.php: locked delivered confirmation; existing authorization/errors retained.
- resources/views/filament/pages/synthetic-evidence-reviews.blade.php and resources/views/components/reviewer-feedback.blade.php: inline confirmation and uncertainty/history guidance.
- tests/Feature/ApplicantSandboxTest.php: original regression retained; rollback/partial audit/database failure/authorization/serialization/confirmation/pairing tests.
- scripts/g-int-001-browser-server.php: local port-8021-only fault server with uncached isolated-database guards; no application provider/route exposes the injector.
- scripts/Bootstrap-FindingAtomicity.php, Inspect-FindingAtomicity.php, FindingAtomicityWorker.php, verify-finding-atomicity.mjs: guarded test observations, overlapping transactions and browser evidence.

The fault server injects only the selected finding-audit failure after the real audit insert. Workers use a test-only barrier after actual paired inserts to prove overlapping PostgreSQL transactions. Synchronization is not a runtime correctness delay. All helpers refuse non-browser databases/cached configuration; no manual-testing database is queried.

## Completed verification

The original regression and expanded matrix pass **eight tests / 71 assertions**. The complete PostgreSQL suite on the final G-INT-001 runtime tree passes **148 tests / 1,886 assertions**, including SEC-001–008, C-INT-001 and E-INT-001/002 coverage. The later independent failing G-INT-002 regression was added after this pass; no complete-current-tree pass is claimed after that stop.

- Focused actual browser journey: **four checks / 12 light/dark screens** at 1440, 768 and 375 CSS pixels; zero recorded axe violations, page overflow, uncaught errors or response-leak detections. A held committed response proves finding controls remain disabled until delivery, then unlock. Audit-side partial work rolls back; delivered success produces 1/1 paired rows; a second committed response deliberately lost produces 2/2, retained remarks, no success claim and no normal replay. Authorized keyboard history inspection confirms the result; an empty repeat is rejected without a third finding.
- Computed representative error text: **7.60:1 light / 11.16:1 dark**; information text: **10.82:1 light / 10.50:1 dark**. These samples exceed the 4.5:1 normal-text target; they are not a complete WCAG assessment. System preference changes work through native keyboard theme controls. Screenshots and machine-readable records: [target evidence](screenshots/phase-3d7-g-int001/results.json).
- Two real overlapping PostgreSQL workers append two paired records (finding/audit counts 2/2 to 4/4) and retain the exact review-copy digest: [concurrency record](screenshots/phase-3d7-g-int001/concurrency.json).
- CHEDRO evidence/intake browser journey: **12 interactions / 30 screen checks passed**, including scope, expiry, screening/corrections and applicant resubmission integration.
- Original applicant answer-race reproduction and **11 expanded cases passed**. Original builder selection reproduction and **13 expanded cases passed**. Builder condition journey: **seven checks passed**, including exact complex-tree persistence, preview/applicant parity and published read-only behavior.
- Laravel Pint, production Vite build and working-tree diff checks passed. Existing line-ending advisories remain. A PowerShell stderr wrapper initially interrupted the build command; the explicit exit-code runner then completed successfully. No build failure is concealed as a successful first attempt.

A fixture attempt to rewrite immutable assignment dates was rejected by PostgreSQL; fixtures were corrected to create separate ineffective assignments rather than weakening guards. A later test used the wrong evidence service result key and was corrected to read the saved latest revision. These were fixture errors, not accepted runtime results.

## Limits and approval boundary

No historical audit completeness claim, backfill, production deployment, full WCAG claim or human usability acceptance. Native zoom/screen-reader gates remain open. The transaction guarantee applies to the actual shared PostgreSQL architecture; a future external audit connection/queue would require architectural review. Assignment and other domain audit paths are not rewritten by this targeted authorization. Broader G resumed only after the gates above passed and immediately stopped when G-INT-002 was independently reproduced. H, Phase 4 and production remain excluded.
