# K4 / A29 — Atomic Region Edit remediation

**21 October 2026. Targeted development verification: PASS for integrity/security, recovery and regression gates.** Region Update only. Batch G remains incomplete; pause for review. No K5, Batch H, Phase 4, deployment or historical repair. Representative automated accessibility checks do not constitute full human/WCAG acceptance.

## Root cause and preserved contract

The [original diagnostic](phase-3d7-k4-region-update-verification.md) established authorized actual Edit **1 / 17**, corrected denial controls **4 / 19**, input controls **1 / 40**, then a mandatory-audit fault **1 / 6 / one failure**. Filament's inherited update saved code/name/activity before its synchronous registered Core audit. Disabled optional transactions did not roll back those changes when audit failed; total audit rows remained **3→3**.

Reviewed EditRegion, RegionResource/Form/Policy/Model, StaffListScope/ScopeAuthorizer, Core observer/global registration, AuditRecorder, installed Filament persistence/transaction support and accepted K1/K3 patterns. Preserved contract:

- Active authenticated actor, existing staff-panel admission, explicit `regions.manage`, and existing RegionPolicy update/canonical exact-region scope. Resource hydration still separately uses `regions.view` with scoped IDs. Role names alone grant nothing.
- Effective regional/nationwide authority alternatives remain. Institution representation/destination-selection rules are not imported.
- Exactly **code, name, is_active** are editable. Code remains required/max-32/unique ignoring this Region; name required/max-255; activity uses the existing Toggle boolean contract. Public UUID is unchanged. No transfer, version-token or new activity-based grant-revocation semantics.
- Sole audit remains **`App\Models\Region.updated`**, success, current actor/Region resource and existing changed-field-name metadata. **region_id and institution_id remain null** for Region resources. No new audit binding or raw-value metadata.

## Exact correction

`RegionUpdateService::update` owns one default PostgreSQL transaction with **one attempt**. It locks/reloads the actor, existing regional/nationwide authority, exact Region and relevant existing role/direct-permission/role-permission memberships in the established ordering. After waits it checks current authenticated identity, active status, panel permission and existing update policy, validates the three-field whitelist/uniqueness, then updates through unchanged Eloquent and the sole registered observer. Both business update and mandatory audit commit together or roll back together.

The operation does not trust mounted/cached actor or Region objects. No blanket panel transaction, automatic retry, second audit writer, compensation mutation, migration, new permission or policy. Locking establishes authority at the operation check; no lifetime dependency or certification of arbitrary external SQL/deadlock schedules is implied.

`EditRegion::handleRecordUpdate` delegates only persistence, synchronizes the component's record with the saved model, and uses existing StaffActionFeedback. Unexpected failures yield fixed persistent **data.name** guidance with retained entries and a support reference; logs contain only fixed message, reference and exception class. Authorization/validation retain their normal denials. Existing staff recovery hook is additionally scoped to EditRegion, providing saved-record inspection after uncertain/network/session responses without privileged replay.

RegionResource/Form/Policy/Model, Core observer/registration, AuditRecorder, StaffListScope/ScopeAuthorizer, K1/K3 and G-INT services are unchanged. There is no new optimistic-lock or idempotency rule: explicit authorized competing updates serialize with existing last-writer semantics; the verified unchanged repeated Save adds no update/audit.

## Retained diagnostics and focused PostgreSQL evidence

All **seven original diagnostic cases** remain on actual registered Filament/Livewire Save, real form, Eloquent and observer. The retained fault method still requires the sink exactly once and exact pre-operation Region values. Only its acknowledgement changes from an escaping raw synthetic exception to safe persistent field/notification feedback. Original [before evidence](evidence/phase-3d7-k4-region-update.json) remains unchanged; [after evidence](evidence/phase-3d7-k4-region-update-after.json) is separate.

| Original fault probe | Before remediation | After remediation |
| --- | --- | --- |
| code | K4-A → K4-FAULT | K4-A → K4-A |
| name | Disposable K4 before → Disposable K4 audit fault | Disposable K4 before → unchanged |
| is_active | true → false | true → true |
| audit rows | 3 → 3 | 3 → 3 |
| public UUID | unchanged | unchanged |

**Final focused matrix: 20 tests / 210 assertions passed**, across RegionUpdateVerificationTest and RegionUpdateRemediationTest. Covers normal exact one-audit success; original pre-audit fault; real audit insertion followed by exception; genuine PostgreSQL audit foreign-key rejection; identical PDO/operation transaction depth and recovery of the outer fixture transaction; permission/panel/status/deactivation/regional revocation/expiry/future changes after mount; direct service denial with stale mounted identity; out-of-scope page denial; required/invalid/duplicate code/name; forged ID/UUID/scope/actor fields; exact current-actor attribution; unchanged Save; explicit later update; existing activity behavior and null audit bindings; nationwide alternative and detached actor denial.

Fault tests query fresh Region/audit state **before fixture cleanup**, verify all fields and audit count unchanged, retain input, and reject injected exception, SQLSTATE or credential hash in Livewire transport. Only the actual audit sink supplies fault injection; the Region update, observer and genuine PostgreSQL insert execute. Transaction depth and outer-query assertions distinguish operation rollback from PHPUnit's later wrapping rollback.

The expanded stale-identity tests intentionally retain the mounted authenticated object while changing persisted status: the new service denies using fresh identity. The original diagnostic's corrected request-identity fixture remains as previously recorded; its historical harness outcomes are not rewritten.

First focused **19 / 199 passed**; an additional valid-edit/forged-attribution case brought final coverage to **20 / 210**, with no focused test failure. Private logs: storage/app/private/k4-remediation-focused-first.txt and k4-remediation-focused-final.txt. Pint's first pass requested an imported ValidationException in this new test instead of a fully qualified catch; only that style issue was corrected, then full Pint passed.

## Independent PostgreSQL concurrency

[Four scenarios passed](screenshots/phase-3d7-k4-remediation/concurrency.json), with independent PHP workers, distinct backend PIDs and actual `pg_blocking_pids` waits. A third connection sees original Region fields and zero uncommitted update audits while barriers are held.

| Scenario | Observed result |
| --- | --- |
| Two different authorized actors update the same Region | First commits; second waits on exact Region row then commits; final second-writer name, two correctly attributed audits. |
| Earlier regional-grant revocation | Waiting editor denies, Region and editor audits unchanged. |
| Earlier management permission deletion | Waiting editor denies, Region and editor audits unchanged. |
| Earlier actor inactivation | Waiting editor denies, Region and editor audits unchanged. |

Barriers and authority changes are isolated fixture synchronization, not runtime delays or management-UI verification. Existing last-writer behavior is preserved; no identical-success retry or universal concurrency guarantee. All possible role-permission, nationwide, expiry and external SQL schedules are not independently certified. Log: storage/app/private/k4-concurrency.txt.

## Actual browser, recovery and accessibility

Guarded local **port 8030**, Chrome/Playwright/axe, **osds_ui_browser_test** only: **nine interaction checks / eight light/dark 1440px/375px captures passed**. Actual form covers required validation with retained input; post-audit failure/exact rollback; delivered Save/double click; duplicate-code validation without partial name change; committed response loss and saved-record popup inspection; out-of-scope direct Edit URL 404; precommit aborted request; permission and cookie loss. Each uncertain mutation is sent once; no automatic consequential replay.

[Target results](screenshots/phase-3d7-k4-remediation/results.json), [light desktop rollback](screenshots/phase-3d7-k4-remediation/edit-rollback-light-1440.png), [dark mobile uncertainty](screenshots/phase-3d7-k4-remediation/edit-uncertain-dark-375.png). Developer inspected both representative images. All eight states passed unrestricted default axe plus explicitly enabled target-size, with zero violations, page overflow, uncaught page errors or checked response leaks. No target-browser harness correction/failure occurred. Existing harness notification-close stabilization is reused without mutation retries; no transient-notification axe resampling was needed. Eight console warnings concern the harness canvas readback performance, not uncaught application errors. External avatar requests are blocked. Private log: storage/app/private/k4-browser-first.txt.

These sampled states do not certify full WCAG, screen readers, native zoom, timed-animation contrast or human usability. Separate known Institution selector target-size findings and previously completed selector corrections remain unchanged; Region Edit has no such selector. No global styling fix is included.

Human retest remains: use disposable scoped staff authority to change all three fields and inspect one attributed Core event where permitted; verify duplicate/required-field guidance; after session loss or uncertainty, retain the tab and inspect the saved record before any explicit new Save. Check keyboard/touch operation, focus, native zoom and screen-reader recovery announcements. Fault injection belongs only in isolated automation, not manual records.

## Preservation and final gates

All seven serial preservation journeys passed with unchanged assertions and separate artifacts: original applicant reproduction plus **11 expanded cases**; original builder reproduction plus **14 selection cases**; **seven condition/preview/publication checks / six screens**; CHEDRO **12 interactions / 30 screens**; staff **15 interactions / 94 screens**. [Preservation index](screenshots/phase-3d7-k4-remediation/preservation/results.json). No preservation case failed. Existing staff notification-close polling is reused before measurement, without mutation retries. Previous dedicated builder recovery, modal/timing, workload and earlier concurrency evidence is preserved rather than redundantly rerun.

**Complete current-tree PostgreSQL: 327 tests / 3,575 assertions passed**, duration **255,893 ms**. Includes retained K4 diagnostics/expanded matrix, K1–K3, G-INT-001–007, SEC-001–008, applicant/builder, UTC/exact-scope and immutability regressions. This is the executed dirty working tree based on Git HEAD **37084d1**, not a committed tree or reused historical result. Private output: storage/app/private/k4-full-final.txt. Earlier 307 / 3,365 remains historical.

The existing K1/K2/K3 diagnostic JSON writers ran unchanged in the full suite. Their fresh outputs are archived in [this run's unit-evidence directory](screenshots/phase-3d7-k4-remediation/preservation/unit-evidence/results.json); their prior published files were restored byte-for-byte from pre-suite snapshots, verified by matching SHA-256 hashes. K4 after evidence is separate from its original failure evidence. No earlier test outcome was rewritten.

Full Pint --test, production Vite build and final tracked-diff/new-file whitespace checks passed. Existing CRLF advisories remain. Private logs: k4-preservation.txt, k4-pint-first.txt, k4-pint-final.txt, k4-build.txt and k4-diff-final.txt under storage/app/private. Database-backed preparation/tests were serialized. Both task-owned browser servers are stopped and temporary K4 wrappers removed. IDE diagnostics tooling was unavailable; no IDE-clean claim. No independent critical defect outside K4 was confirmed in this bounded remediation.

## Affected files and boundaries

- `app/Services/Authorization/RegionUpdateService.php`: new narrowly owned update operation.
- `app/Filament/Resources/Regions/Pages/EditRegion.php`: persistence delegation, record synchronization and safe feedback.
- `app/Providers/Filament/AdminPanelProvider.php`: existing recovery hook additionally scoped to Region Edit.
- Original RegionUpdateVerificationTest and new RegionUpdateRemediationTest: retained rollback comparison and expanded real-action matrix.
- `scripts/Prepare-RegionUpdate.php`, RegionUpdateWorker.php, verify-region-update-concurrency.mjs, k4-update-browser-server.php, verify-region-update.mjs, verify-k4-preservation.mjs: guarded fixtures, workers/local fault server and browser/preservation harnesses.
- Remediation/checkpoint documentation and safe evidence. Prior diagnostic results remain historical.

Only osds_test and osds_ui_browser_test are used. No manual database inspection/reset/reseed/mutation, historical repair, index/staging operation, commit, push, deployment, migration, new audit event/permission/governance rule, K5, Batch H or Phase 4. Existing staged changes and accepted runtime/evidence remain preserved. After targeted gates, pause for user review; do not declare Batch G complete or begin K5 automatically.

Remaining Batch G gates include K5, broader inventory/residual-risk disposition, the separate Institution selector finding and human acceptance. This K4 pass neither resolves those items nor authorizes their execution. **Stop for review after K4.**
