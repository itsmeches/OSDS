# G-INT-006 - Regional authority creation survives mandatory audit failure

**Follow-up:** Targeted remediation is complete under separate authorization: original regression and expanded matrix pass (21 tests / 226 assertions); full PostgreSQL 230 / 2,615 passed. See [remediation, browser/concurrency evidence and limitations](phase-3d7-g-int-006-remediation.md). The discovery/stop record below is retained historically; no historical repair was performed. Batch G remains incomplete and paused for review.

**20 October 2026. Confirmed critical integrity defect; Batch G stopped. NO-GO for Batch H.** No runtime remediation applied. Accepted G-INT-001 through G-INT-005 protections remain unchanged.

## Actual-action reproduction

`RegionalGrantCreationAtomicityTest::test_actual_regional_grant_creation_rolls_back_when_mandatory_audit_fails` uses the actual `CreateUserRegionAssignment` Filament page, real form selection/validation, Livewire `create`, Eloquent model and registered observer. An authorized Admin has effective scope in two disposable regions; the delegated OSDS target already has scope in the first. The action creates a new target grant in the second region. Fixture setup alone suppresses events; the tested creation does not.

Only the downstream AuditRecorder sink is mocked to throw once for the actual model's `.created` event. The exception reaches the action; audit count remains unchanged, but the new target/region grant exists. Retained regression: **1 test / 7 assertions / 1 failure**. Private output: `storage/app/private/g-int-006-confirmation.txt`. Tests use only `osds_test`; fixture cleanup is not an operation-level rollback.

This proves regional authority survives an unsuccessful mandatory-audit operation. It does not demonstrate an authorization bypass, historical incident, access without other permissions, or a browser exploitation chain. Existing actor scope, target-role delegation and self-grant checks executed normally.

## Root cause and bounded scope

`CreateUserRegionAssignment` inherits Filament's default persistence handler without an owned operation transaction. `UserRegionAssignment` validates target authority before insertion. `AppServiceProvider` registers `CoreIdentityObserver`, whose synchronous `created` callback writes the mandatory audit after insertion. With optional page/panel transactions disabled, an audit exception does not undo the grant.

The global observer registration is material: absence of an explicit audit call in the resource/model does not imply absence of auditing. Earlier source leads must be read with that registration included. `AccessGovernance` has transactional service operations, but this generic Create path does not invoke them. No new management policy is proposed.

Separate targeted authorization is requested to establish the narrow authoritative creation transaction and execution-time checks under existing regional/delegation rules, preserving the original regression and verifying actual action success/failure, partial audit rollback, stale authority, concurrency and safe recovery. No runtime fix, migration or historical repair is applied here. Other generic creation paths remain source leads, not independently reproduced critical findings.

## Assessment evidence completed before stop

- Modal transitions: **12 same-page trials passed** (8 rapid cancel/open, 4 held-cancel responses), with unchanged persistence. Four representative light/dark desktop/mobile screen captures. First harness attempt used `context.record`; installed Filament uses `context.recordKey`. Corrected instrumentation only; first attempt retained. Evidence: `screenshots/phase-3d7-g-final/modal`.
- Builder timing: **6 unchanged held-response cases passed**, repeated with additional response holds of 0, 250 and 1,000 ms. Selection, client state, database/reload and stale-writer assertions retained. Prior timeout not reproduced; its original cause is not established. Evidence: `screenshots/phase-3d7-g-final/builder-timing`.
- Queue workloads: 12, 36 and 60 cases per queue; one warmup and seven measured samples per scenario, page size 10. Populated intake/evidence query counts remained **12 / 10**; empty search 6, denied identity 3. Page-one medians: intake **6.972 / 7.626 / 9.257 ms**, evidence **7.185 / 8.674 / 10.878 ms**. Maximum sampled operation 18.989 ms. These are warm local PHP/query-builder measurements including authorization/count/eager loading, not HTTP latency, production capacity or an SLA. Empty-search growth is a bounded scalability observation, not a proven release blocker. Evidence: `screenshots/phase-3d7-g-final/workloads.json`.
- Existing applicant recovery journey completed; subsequent builder recovery was interrupted after discovery. Partial recovery evidence remains in `screenshots/phase-3d7-g-final/recovery`; the pipeline was stopped before final CHEDRO/staff journeys. Partial output must not be treated as complete acceptance.
- An already-running baseline suite passed **209 tests / 2,389 assertions**. It enumerated tests before the new regression; it is **not a passing final current-tree suite**. The first focused attempt overlapped that suite and deadlocked disposable test-schema setup; serial rerun produced the confirmed rollback failure above. This scheduling error is not a runtime defect.

## Readiness and preservation

**Batch G NOT READY; NO-GO for Batch H.** Final current-tree PostgreSQL acceptance fails the retained new regression. Final browser preservation, accessibility, Pint/build/diff acceptance are incomplete after the stop; earlier targeted results remain historical. Human usability, native zoom, screen readers and full accessibility assessment remain pending.

No runtime code, existing staged changes, historical records or manual-testing databases were modified. No commit, push, deployment, schema change, historical repair, Batch H or Phase 4 work. Only disposable `osds_test` and `osds_ui_browser_test` fixtures were used. Pause for separate G-INT-006 authorization.
