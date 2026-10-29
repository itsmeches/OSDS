# Gate B2 — WorkflowOverview fixture correction and validation

**29 October 2026. PASS.** One fresh disposable Chromium execution completed **22/22 cases** with the unchanged policy candidate. The destination-departure case retained its deliberate fixture navigation, rejected the request and recorded zero unexpected navigation. Page-error precedence and external-origin rejection also passed. The candidate is suitable for separately authorized Attempt 14 integration review; no protected package or execution was prepared.

## Fixture-only correction and hashes

Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation. Original `run.mjs`, failed `browser-validation-01/` and its report remain unchanged. A new `run02.mjs` preserves all 22 scenario specifications, expected reasons, policy inputs and assertions except the incorrect navigation accounting; its evidence destination is exclusively `browser-validation-02/`.

The original listener counted every observed main-frame navigation after fixture setup. Case 20 deliberately called `history.replaceState(..., '/admin/login')`, then required that combined count to be zero. The corrected listener still records **every event**. Exactly one event may be classified `FIXTURE_HISTORY`, only while that explicit operation is active and only for its exact synthetic destination. The operation's attribution window closes in `finally`. All other events remain `UNEXPECTED`; duplicate events cannot receive the exemption. No listener is disabled or event discarded during this operation.

Assertions require zero unexpected events, exactly one fixture-owned event in case 20, zero fixture-owned events elsewhere, and an observation-array length equal to the total count. Case 20 actually recorded **total 1 / fixture 1 / unexpected 0**. This is attribution of an explicit controlled operation, not a general exemption for pathname changes. It does not establish universal navigation attribution under arbitrary concurrent scripts outside this fixture.

| Source | SHA-256 before/after identity |
| --- | --- |
| Original fixture `run.mjs`, preserved | `b32635cefc8fa6b44452bf06522b4259d4d3961816d644cacb3202099869e315` |
| Corrected fixture `run02.mjs` | `91945f570f6831b2e3861a98c6ca3b523bae5d1a18d6b8e7577c1fa9bf20384a` |
| Policy candidate, unchanged | `8eecd269b80509afa02485eda84d0808f76b1e7d3d9ad98ce12175f632561c6e` |

The original validator, first-fatal candidate, application/avatar sources and historical manifests were not modified. New artifacts are this report and private preparation, corrected runner, baseline/comparison and fresh evidence files under `gate-b2-workflow-overview-validation/`.

## One execution and isolation

Executed exactly once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-workflow-overview-validation/run02.mjs run
```

| Fact | Actual result |
| --- | --- |
| Exit / aggregate | **0 / PASS** |
| Start / finish UTC | **2026-10-29 10:18:33.512 / 10:18:49.061** |
| Asia/Manila | **18:18:33.512 / 18:18:49.061** |
| Recorded interval | **15.549 seconds** |
| Node / installed Playwright | **v24.20.0 / 1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Cleanup | **22 fresh nonpersistent contexts closed; browser closed** |
| Isolation violations / unexpected page errors / unhandled rejections | **0 / 0 / 0** |
| Browser retries / candidate changes | **0 / 0** |

Fresh exclusive evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-workflow-overview-validation/browser-validation-02/`

All resources were fulfilled in memory at `http://127.0.0.1:18160`. No HTTP server, OSDS application, real credentials/profile/session, PostgreSQL or Laravel application bootstrap was used. External probes were aborted before network continuation. Service workers/downloads were disabled and WebSockets blocked. Actual installed Livewire client bytes and existing synthetic signed fixtures were retained. No timeout, sleep or new policy allowance was introduced.

Closed per-case observations were saved before expected-outcome assertions. Those checkpoints remain `OBSERVED`; the final aggregate marks all cases PASS. No raw DOM/body/snapshot, arbitrary component identity, exception text, screenshot or unrestricted trace was added to browser observations. The existing synthetic snapshots remain private fixture inputs.

## Complete 22-case results

Every actual first reason matched its expected reason. `NONE` means no fatal signal in the positive case.

| Case | Actual first reason / bounded outcome | Result |
| --- | --- | --- |
| Installed client lazy mount and poll | `NONE`; 1 lazy, 1 refresh | PASS |
| Login followed by installed-client widget traffic | `NONE`; **1 Login**, 1 lazy, 1 refresh | PASS |
| Repeated legitimate polling | `NONE`; 3 refreshes | PASS |
| Wrong component | `COMPONENT_ACTION_BLOCKED` | PASS |
| Wrong method | `COMPONENT_ACTION_BLOCKED` | PASS |
| Wrong arguments | `COMPONENT_ACTION_BLOCKED` | PASS |
| Unexpected updates | `COMPONENT_ACTION_BLOCKED` | PASS |
| Multiple components | `COMPONENT_COUNT_INVALID` | PASS |
| Malformed snapshot | `POST_BODY_INVALID` | PASS |
| Tampered lazy token | `COMPONENT_ACTION_BLOCKED` | PASS |
| Tampered request snapshot | `COMPONENT_ACTION_BLOCKED` | PASS |
| Lifecycle method | `COMPONENT_ACTION_BLOCKED` | PASS |
| Duplicate Login | `CONSEQUENTIAL_REPEAT_BLOCKED`; only 1 Login forwarded | PASS |
| Unexpected notification/event | `COMPONENT_ACTION_BLOCKED` | PASS |
| Duplicate lazy initialization | `COMPONENT_ACTION_BLOCKED`; only 1 lazy forwarded | PASS |
| Refresh cap | `COMPONENT_ACTION_BLOCKED`; 12 forwarded, 13th blocked | PASS |
| Missing Dashboard | `COMPONENT_ACTION_BLOCKED` | PASS |
| Hidden heading | `COMPONENT_ACTION_BLOCKED` | PASS |
| Login form present | `COMPONENT_ACTION_BLOCKED` | PASS |
| Destination departure | `COMPONENT_ACTION_BLOCKED`; fixture navigation retained; no POST forwarded | PASS |
| Page-error first-fatal precedence | `PAGE_ERROR` retained ahead of subsequent policy/request failure | PASS |
| External origin | `EXTERNAL_ORIGIN_BLOCKED`; no external forwarding | PASS |

All 22 cases retained false test-only authentication confirmation, **zero unexpected navigation and zero business actions**. Exactly one synthetic Login was forwarded in the Login-plus-widget case; the duplicate-Login negative likewise forwarded only its initial Login. These are independent disposable cases, not protected login attempts.

The deliberate page error is an expected negative input, not an unhandled test failure. Its first record is sequence 1 `PAGE_ERROR`; subsequent sequence 2 `COMPONENT_ACTION_BLOCKED` and sequence 3 `REQUEST_FAILED` did not replace it. External-origin rejection and lifecycle denial remained fail-fast. Cleanup preserved first-reason identity.

## Offline/checksum results and limits

Fresh regression:

```powershell
node scripts/check-uiux-b2-workflow-overview-policy.mjs
```

**PASS: unchanged 62 checks**, including ten negative contracts and eight route cases. Corrected runner syntax passed. The prior **4 installed Checksum checks** were not rerun: `fixtures.php`, its generated synthetic fixture with `checks=4` / `tamperedRejected=true`, the original report and installed source pins remain byte-unchanged in the preservation baseline. This is verified retained provenance, not four newly executed checks.

Initial preparation inventory creation exceeded the operating system's open-file limit before writing a baseline or launching any browser. The new preparation helper was changed to hash in batches of 32 and then completed. This was a filesystem-inventory correction, not a browser retry or policy change.

The unchanged candidate validates exact WorkflowOverview identity, document-bound lazy/refresh shapes and separate bounded widget accounting. Server snapshot signatures and server authorization remain authoritative; policy-side DOM/token binding is not a replacement for cryptographic verification. Synthetic installed-client success does not prove protected reviewer identity, actual statistics correctness or Attempt 13's exact historical rejected request. No new business operation was permitted.

## Preservation and decision

Fresh focused comparison: **8,203/8,203 baseline entries unchanged**, zero changed/missing/unavailable. This includes the original failed runner/evidence/report, candidate/offline tests, synthetic checksum fixtures, attempts 01–13 and their preparation/session/evidence bytes, credentials/identities, avatar correction, protected sources, historical logs/caches and artifacts. Credentials/session bytes were hashed only. All **29 historical pins** match; Attempt 13 manifest remains `f5426fa2a5f8c01468de383b98ff612c275880c40580816b45c4013ff27e1e09`.

Laravel log remains **17,218,391 bytes**, full SHA-256 `a71c2bd7f7d4fb8180decea1d7bbc13cf6b943b4c9a8a5fc428d8a0b3182e44b`. Original prefix and known 370-byte suffix hashes also match. **New log bytes: 0.** The exception remains **APPLICATION ERROR INDICATED — PHP_EXECUTION_TIME_LIMIT — ATTRIBUTION NARROWED**. Historical exceptions were not cleared or reclassified. This is a focused inventory with inherited path aliases, not a whole-cluster or whole vendor/node_modules assertion.

**PASS: suitable for separately authorized Attempt 14 integration review.** The complete disposable matrix now validates the unchanged candidate, including the corrected destination fixture and the two previously unexecuted controls. No historical failure was overwritten.

**One recommended next action:** separately authorize source-only integration and guarded preparation of an Attempt 14 package that explicitly pins this candidate and retains all authentication, Dashboard/sidebar/personnel, request-policy, first-fatal and cleanup requirements. This report authorizes neither that package nor protected execution.

**STOP.** No protected browser/login, database operation, policy/application modification, Attempt 14 preparation/execution, service operation, lifecycle action, migration/repair, B3/B4, commit or push occurred.
