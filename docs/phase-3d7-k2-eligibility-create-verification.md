# K2 / A22 — Applicant eligibility Create diagnostic

**21 October 2026. Disposition: PASS for the tested actual creation/denial boundary; audit failure CONTRACT-N/A.** No independent critical defect confirmed. No runtime remediation or audit event added. Pause for review; K3–K5, Batch H and Phase 4 are not started. This does not declare Batch G complete or settle production eligibility/audit policy.

## Established implemented contract

Inspected the registered `ApplicantSandboxEligibilityGrantResource`, Create/List pages, inline form, model, ApplicantSandboxGuard, actor staff admission, observer registration, AuditRecorder callers, eligibility migrations and Phase 3B/3C/3D approved implementation/completion material. No assignment-specific eligibility policy or creation service is invoked by this page; resource methods and model checks supply this operation's implemented boundary.

| Aspect | Existing rule and diagnostic boundary |
| --- | --- |
| Actor | Resource `canViewAny`/`canCreate` requires explicit **`applicant.sandbox.publish`**. Normal staff HTTP admission requires active account/panel access; model creation rereads `granted_by` and requires active identity and that permission. Role labels alone are not a grant. Phase 3B names Admin as provisional permission holder, not an implemented role-name bypass. |
| Sandbox | `ApplicantSandboxGuard` permits local/testing/sandbox, denies production; outside testing the database name must contain sandbox/test. Fixtures explicitly assert `osds_test` before seeding. No new environment rule. |
| Scope | Grant is **institution + module**, not applicant + institution. There is no applicant field, creator representation prerequisite or manager-region constraint in this path. Normal actor has zero regional assignments and representations yet creates successfully for the synthetic fixture institution. Do not import reviewer-management or applicant-consumption scope rules. |
| Organization | Form lists **all synthetic institutions**, without a regional, organization-type or institution-status filter. Model and PostgreSQL trigger require a synthetic institution. Nonsynthetic input is rejected by the actual form in this assessment. No new active-institution/type eligibility rule is inferred. |
| Module | Form lists **active modules** and validates selected options. It rejects inactive/nonexistent modules. This is the form boundary; model creation does not separately define a module-activity policy beyond its normal FK. No arbitrary direct-model caller is certified. |
| Accepted fields | `institution_id`, `module_id`, required `starts_at`, optional `ends_at` after start, required reason up to 500 characters. The normal form defaults start to now; fixture control explicitly supplies dates. |
| Attribution / state | `mutateFormDataBeforeCreate` sets `granted_by` from `auth()->id()`. Initial `revoked_at`/`revoked_by` are null, timestamps are ORM-generated. Effectivity derives from dates/revocation; there is no separate approval/verification state or applicant activation action here. |
| Duplicate | Existing partial unique index **`active_applicant_sandbox_eligibility_unique`** prohibits a second unrevoked institution/module pair. The index predicate is revocation-based, not end-date-based. No new duplicate-grant/idempotency semantics. |

Creating this record does not itself confer applicant access: existing consumer policies/services separately require active synthetic identity/organization, representation, ownership, explicit permissions, effective grant and exact manifest/application bindings. Those previously verified consumer paths are preserved, not rerun or broadened by K2.

## Audit contract — CONTRACT-N/A for fault injection

No **specific mandatory eligibility-grant creation audit event** was established in the reviewed requirements. The plans discuss applicant draft/submission/evidence and audit safeguards, but do not identify an event/writer for this administrative Create operation. General audit intent is not treated as authorization to invent a grant event.

The eligibility model has no audit call or observer attribute, and it is absent from the global CoreIdentityObserver registration. Reviewed migration triggers enforce synthetic institutions/dates/uniqueness; they do not insert an eligibility audit. The page invokes inherited `CreateRecord::handleRecordCreation`, without a domain service/audit call or an explicit operation transaction. Actual normal creation leaves AuditEvent count unchanged with the **real recorder/observers/database** in the final run.

Therefore no audit fault is injected, no nonexistent writer is mocked and no transaction/audit remediation is proposed. **Residual governance question:** should eligibility grants require a dedicated append-only event beyond the row's creator, reason and timestamps? That decision remains for explicit review; this diagnostic neither declares a missing writer a defect nor approves unaudited production authority changes.

## Actual-action method and evidence

`tests/Feature/EligibilityCreateVerificationTest.php` uses the real registered Create page/schema, relationship-option rules, Livewire action, Eloquent model and PostgreSQL constraints. It asserts **`osds_test`** before fixture preparation; actors/institutions are disposable test fixtures. The nonsynthetic negative institution is deliberately artificial test data, not a real organization's record. No replacement form/persistence handler, reflection bypass, service mocking or runtime code change.

An authorized control completed before the broader matrix. Final evidence: [safe persisted rows and case outcomes](evidence/phase-3d7-k2-eligibility-create.json). Each JSON key below names a separately transaction-cleaned fixture; IDs are not shared manual records. Successful control records, for example, institution/module/creator **1/1/1**, start `2026-10-21T00:39:25Z`, end `2026-10-22T01:39:25Z`, reason `Disposable K2 eligibility control`, revocation fields null, and audit count **5→5**. These are recorded synthetic values, not live database state after cleanup.

## Scenario outcomes

**Final scoped PostgreSQL matrix: 17 tests / 134 assertions passed.** Private final output: `storage/app/private/k2-matrix-final-corrected.txt`.

| Check | Result | Persistence / evidence key |
| --- | --- | --- |
| Normal authorized actual Create | **PASS** | Correct institution/module, current creator, exact dates/reason, null revocation and generated timestamps; no new audit row. `authorized`. Actor requires neither regional grants nor representation. |
| Initial actor missing permission / guest | **PASS** | Actual Create route returns **403** / redirects **302** to `/admin/login`; zero grants. `initial-denials`. |
| Management permission removed after mount | **PASS** | Actual Create denied **403**; zero grants/audit delta. `denied-permission`. |
| Inactive / deactivated actor after mount | **PASS** | Model's fresh actor check denies **403**; zero grants/audit delta. `denied-inactive`, `denied-deactivated`. |
| Production environment | **PASS** | Actual creation denied **403**, zero grant/audit delta. `denied-production`. No production database was contacted. |
| Missing panel permission | **PASS for HTTP admission** | Actual route denied **403**; zero grants. `denied-panel`. This is not a claim that isolated component testing alone enforces every route middleware. |
| Nonsynthetic / nonexistent organization | **PASS** | Real form `institution_id` validation fails before persistence. `invalid-real-institution`, `invalid-missing-institution`. |
| Inactive / nonexistent module | **PASS** | Real form `module_id` validation fails before persistence. `invalid-inactive-module`, `invalid-missing-module`. |
| Missing start / end before start | **PASS** | Field errors; zero grants/audit delta. `invalid-missing-start`, `invalid-end-before-start`. |
| Missing / excessive reason | **PASS** | Required/max-500 errors; zero grants/audit delta. `invalid-missing-reason`, `invalid-long-reason`. |
| Forged creator/applicant/revocation/created timestamp | **PASS** | Raw Livewire state cannot replace actual creator or populate absent applicant/protected state. Revocation stays null; timestamp is not forged 2000 date; audit count unchanged. `forged`. |
| Duplicate unrevoked pair | **PASS for existing persistence constraint** | Genuine unique-index rejection; one original grant remains exactly unchanged, no audit added. `duplicate`. |
| Regional/applicant combination denial | **CONTRACT-N/A** | No applicant-specific binding or manager-region restriction exists in this operation. Positive global-permission fixture proves the actual behavior. Nonsynthetic organizations/unavailable modules are the relevant input exclusions tested above. |
| Mandatory grant-audit failure | **CONTRACT-N/A** | No existing mandatory event/writer established. No fabricated event or fault probe. |

For the duplicate, the test uses a savepoint around the actual second Create so PostgreSQL's rejected statement does not poison PHPUnit's outer fixture transaction. No runtime transaction is enabled or substituted, and no rejected insert survives. The unhandled database exception observed by the test is not claimed as good browser error recovery or evidence of an HTTP information disclosure. Exact user-facing duplicate recovery remains unverified.

## Chronology and harness reconciliation

- Initial authorized control: **1 test / 17 assertions passed**; first bounded matrix **16 / 126 passed**. An early negative-call audit spy was removed so the final run uses the real audit implementation throughout; no audit fault was manufactured.
- Expanded route probe: **17 tests / 131 assertions, 16 passed / one failed**. Route denial itself worked. Its total-audit assertion incorrectly included `auth()->logout()`, which rotates the fixture actor's remember token and produces existing **`App\Models\User.updated`** through Core.
- The corrected probe separately asserts no audit change on each denied/guest route, verifies the logout event's changed-field name is `remember_token`, and retains that legitimate audit. It logs no token value. Final **17 / 134 passed**, with zero unintended eligibility records in rejected cases. The earlier output remains `k2-matrix-final.txt`; earlier partial evidence is retained as [first route-probe record](evidence/phase-3d7-k2-first-route-probe.json).

All DB-backed invocations were serialized. No critical integrity/authorization defect was confirmed, and no runtime behavior was altered to satisfy test expectations.

## Remaining limits and review boundary

- These are actual Filament/Livewire and HTTP transport tests, **not Playwright/browser, screenshot, axe or human usability verification**. No such claim is made.
- Real concurrent creates, committed-response loss, every date/duplicate variant, changing organization/module options in flight and direct-model callers are not independently certified. The known unique-index predicate is documented; no new policy is inferred from missing permutations.
- Institution inactivity/type are not form exclusions in this implemented grant-administration path. Normal applicant-consumption restrictions remain separate. Production governance and dedicated grant-audit coverage need explicit disposition.
- No full suite/build/preservation rerun was required by a concrete failure here or claimed. The accepted K1 **274 / 3,020** remains the latest recorded full-suite checkpoint, not a new pass after adding this diagnostic file. K1/G-INT-001–007, selector and builder-recovery runtime/evidence are untouched.

Only diagnostic test/evidence and documentation changed. No manual database inspection/reset/reseed/mutation, historical repair, new permission/audit event, runtime fix, schema change, staging, commit/push, deployment, K3–K5, Batch H or Phase 4. **Pause for review. K2's operational checks pass within their stated scope; audit policy remains an explicit CONTRACT-N/A review question.**
