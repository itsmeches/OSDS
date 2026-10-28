# Gate B2 — Dashboard diagnostics disposable-browser validation

**28 October 2026. FAIL for the requested diagnostic discrimination; integration is not justified.** One disposable validation executed. Eight cases passed; the ninth, hidden heading, stopped the run on an invariant failure. Source review confirms a diagnostic design defect: the candidate counts a role locator that excludes hidden headings, so hidden and absent headings are indistinguishable. No candidate change, retry, protected operation or Attempt 09 preparation occurred.

The runner's preserved aggregate status is **BLOCKED** with `DISPOSABLE_VALIDATION_INVARIANT_FAILED`. This report retains that execution result and classifies the candidate's source-level discrimination defect as **FAIL**. The failed case's exact runtime code was not retained; it must not be reconstructed as an observed value.

## Execution, versions and isolation

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reused the unchanged candidate and its real `inspectDashboardContract` collector, installed Playwright assertions and the shared failure/destination contracts. No protected driver or application/framework module was executed.

Executed exactly once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-dashboard-diagnostics-validation/run.mjs run
```

| Fact | Actual result |
| --- | --- |
| Process exit | **1** |
| Start / finish | **2026-10-28 08:48:59.559 / 08:49:36.089 UTC** (16:48:59.559 / 16:49:36.089 Asia/Manila) |
| Recorded interval | **36.530 seconds** |
| Node / installed Playwright | **v24.20.0 / 1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Contexts | **9 entered, 9 closed**; browser closure recorded |
| Unexpected browser errors / isolation violations | **0 / 0** |
| Unhandled rejections | **0 recorded** |
| Retries / candidate changes after execution | **0 / 0** |

The runner verified the unchanged candidate/collector hashes, local Chromium executable presence and unused evidence directory before launching. No browser download/install or fallback executable was used.

Candidate SHA-256: `e72262278feb30bd46fc3864a463f901132fbc34d9978c9113977566bf694707`.

Collector's containing sidebar helper SHA-256: `336a5d26c4afc4ce7738fda28eb9d68160e49be962edfba84a7db1093cca5692`.

Each scenario had a fresh nonpersistent context, service workers blocked and downloads disabled. Synthetic origin **`http://127.0.0.1:18108`** was fulfilled entirely through in-memory Playwright routes; no HTTP server started. Only exact same-origin GET `/admin`, `/away` and the deliberately failing `/failure` control were admitted. Other origins, ports, paths, queries and methods were rejected; WebSockets were closed and treated as isolation failures. No OSDS application, real credentials, cookies, existing sessions, database or Laravel bootstrap was used.

New private files are `preserve.cjs`, `baseline.json`, `pins.json`, `run.mjs`, `preservation.json` and the exclusively created `browser-validation-01/result.json`, under `gate-b2-dashboard-diagnostics-validation/`. This report is new. No historical evidence directory was reused.

## Exact scenario outcomes

All assertions used the real candidate with actual Playwright DOM collection and role/count/visibility behavior. Valid test-only confirmation is not real authentication. No protected navigation/business callback exists in this fixture.

| Required scenario | Actual outcome / retained code | Confirmation |
| --- | --- | --- |
| 1. Valid Dashboard and visible level-one heading | **PASS**, `NONE` | true, test-only |
| 2. Dashboard plus child widgets | **PASS**, `NONE` | true, test-only |
| 3. Missing Dashboard | **PASS**, `DASHBOARD_COMPONENT_MISSING` | false |
| 4. Duplicate Dashboard | **PASS**, `DASHBOARD_COMPONENT_DUPLICATED` | false |
| 5. Unexpected Login component | **PASS**, `UNEXPECTED_LOGIN_COMPONENT` | false |
| 6. Login-form signature | **PASS**, `LOGIN_FORM_PRESENT` | false |
| 7. Malformed component metadata | **PASS**, `MALFORMED_COMPONENT_METADATA` | false |
| 8. Missing heading | **PASS**, `HEADING_MISSING` | false |
| 9. Hidden heading | **FAILED validation invariant**, expected `HEADING_INVISIBLE`; exact observed code not retained | not retained for failed case |
| 10. Duplicate heading | **NOT RUN** | unavailable |
| 11. Changed destination | **NOT RUN** | unavailable |
| 12. Page error | **NOT RUN** | unavailable |
| 13. Request failure | **NOT RUN** | unavailable |
| 14. Invalid diagnostic metadata | **NOT RUN** | unavailable |

Six additional precedence controls were prepared for malformed metadata over Login/form, Login over form, form over missing Dashboard, component failure over heading failure, fatal over metadata and destination over metadata. **None ran**, because they followed the required cases. Browser precedence validation therefore remains incomplete. The passing offline precedence/fatal tests do not substitute for these unexecuted cases.

The invalid-output control was designed to inject an extra field at the adapter boundary **after executing the real collector**; DOM markup alone cannot change the collector's fixed output shape. It was not reached. No candidate or browser global function was patched for it.

The six completed negative cases retained their exact expected codes, false confirmation and zero protected actions. After context closure and a Node microtask drain, completed-case confirmation/code checks remained stable. This does not establish fatal-event behavior or failed-case state that was not retained.

## Failure analysis and evidence limitation

The candidate creates:

```javascript
page.getByRole('heading', {name: 'Your workspace', exact: true, level: 1})
```

It then calls `count()` and calls `isVisible()` only when that count equals one. No `includeHidden` option is specified. Installed Playwright's role selector implementation filters hidden elements when `options.includeHidden` is false/absent, returning before name matching. Thus a `display:none` level-one heading is excluded from this locator. The candidate cannot reach its `HEADING_INVISIBLE` branch for this hidden fixture; its logic maps a zero role-match count to `HEADING_MISSING`.

This is a **diagnostic distinction defect**, not an authentication bypass: a hidden heading still cannot satisfy acceptance. The offline node double previously supplied count 1 and visibility false independently, which did not model Playwright's default hidden-role filtering. Those offline tests passed but did not prove this browser distinction.

The run stopped while validating the ninth case. The fixture source gives that case valid Dashboard metadata, a hidden `<h1>` and a visible `<h2>` with the same name; there is no broad-level fallback. The result has eight completed entries and nine closed contexts, locating the stop. However, the runner appends a case record **after** checking its expected code. Its generic catch therefore omitted the failing case's diagnostic sample and confirmation state. We cannot report an exact observed ninth-case code, claim that every negative scenario was verified, or infer the original assertion detail. The source-level locator defect is established; the omitted runtime values are not recovered.

No timeout was increased, sleep/reload/retry introduced, assertion weakened or failure repaired. Expected negative assertion rejections were handled as test outcomes; there was no application error suppression or protected allowlist expansion.

## Regression and preservation

Before browser execution:

| Suite / check | Actual result |
| --- | --- |
| Dashboard diagnostic offline suite | **PASS: 52 checks / 14 cases** |
| Sidebar navigation suite | **PASS: 20 checks** |
| Login readiness suite | **PASS: 70 checks** |
| Existing diagnostics/policy/source suite | **PASS: 98 checks** |
| Disposable runner syntax | **PASS** |

The offline results remain PASS within their documented model limits; they are not relabeled as browser validation. No protected entry point was imported or existing historical test output overwritten.

Fresh focused baseline: **4,914/4,914 files unchanged**, zero modified/missing. Scope preserves attempts 01–08, historical evidence/identity records, original credentials/ledger, application/runtime and selected installed/public framework assets, logs/caches in the inherited scope, guarded tools/helpers/policy, Batch G, all 81 previously inventoried PDF-named artifacts and ten historical diagnostic JSON files. Credentials/session bytes were hashed only. No artifact was cleared, restored, truncated or reconstructed. Prior historical append and missing temporary-metadata exceptions remain unchanged. This is a focused inventory, not a whole vendor/node_modules or database-state assertion.

Persisted results contain bounded counts, booleans, closed case/outcome/code fields, timings, hashes and versions only. No raw DOM, snapshot data, errors, credentials, cookies, session contents, screenshots, console dumps or unrestricted traces were retained. Partial-run evidence is permanently occupied and must not be overwritten.

## Decision and next authorization boundary

**FAIL: do not integrate this candidate into a new protected attempt yet.** It does not meet the required hidden-versus-missing heading distinction, and browser fatal/precedence/invalid-output coverage remains unexecuted.

Recommend separate authorization for one source-only revision: count the exact heading with hidden matches included, then require its actual visibility for acceptance; preserve exact name/level, uniqueness, destination and fatal checks. Also retain each bounded case outcome before validation assertions so a failed validation cannot discard its diagnostic evidence. Neither revision was implemented here. Any later disposable execution requires separate authorization and a fresh evidence directory; no automatic rerun is authorized.

**STOP.** No protected browser/authentication, PostgreSQL/Laravel bootstrap, Attempt 08 reuse, Attempt 09 preparation/execution, application/framework change, personnel lifecycle, migration/directory recreation/PDF, B3/B4, commit, push or deployment occurred.
