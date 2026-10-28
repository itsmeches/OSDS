# Gate B2 — Disposable navigation validation 02

**28 October 2026. PASS: candidate suitable for separately authorized Attempt 07 preparation.** This newly authorized independent run completed **10/10 cases** covering the nine-category matrix. There were zero failed cases, unexpected errors or unhandled rejections. The earlier successful validation remains unchanged. No protected preparation or execution followed.

## Exact source and execution

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Read the navigation investigation, correction-preparation and offline-validation reports.

The private `run02.mjs` is identical to the previous disposable runner except its fixed output directory changes from `browser-validation-01/` to **`browser-validation-02/`**. A text comparison verified that sole difference. The candidate source hash matched its original preparation metadata. The exact extracted candidate block was executed with real Playwright polling and visibility assertions; no manually implemented predicate or mock assertion replaced it.

Executed once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-disposable-validation/run02.mjs run
```

| Observation | Actual result |
| --- | --- |
| Result / process exit | **PASS / 0** |
| Start | **2026-10-28 07:19:05.488 UTC / 15:19:05.488 Asia/Manila** |
| Finish | **07:20:11.312 UTC / 15:20:11.312 Asia/Manila** |
| Recorded interval | **65.824 seconds** |
| Browser, queried at runtime | **Chromium 156.0.8078.4** |
| Playwright / Node | **1.64.1 / v24.20.0** |
| Contexts / retries | **10 fresh contexts / 0 retries** |
| Cleanup | **All contexts and browser closed** |

The complete protected driver was not imported, and its credential/session paths were not used. Neither the patch nor historical Attempt 06 driver/pins were modified. No installation, download or browser fallback occurred.

## Isolation and retained evidence

The dedicated synthetic origin was **`http://127.0.0.1:18046`**. Only the wrong-origin control additionally admitted the exact synthetic `127.0.0.1:18047/admin` target. A fixed GET resource map fulfilled HTML in memory; no HTTP listener, application server, OSDS port-8044 route, Laravel or PostgreSQL was used.

Contexts were nonpersistent, with service workers/downloads blocked. Unexpected resources, methods, external origins, popups and WebSockets would fail the run. No OSDS credentials, cookies or private sessions were loaded into the fixture. There was no actual authentication, Sign in submission or reviewer action.

Only closed case names, failure codes, booleans, sample/marker sequences, timings, DOMContentLoaded counts and cleanup outcomes were retained. No screenshot, unrestricted trace, raw DOM, URL/query string, cookie, request body or exception dump was captured.

## Actual scenario results

The nine matrix categories used ten contexts because page and request fatal events were separate controls. A negative case passes only when it fails closed as expected.

| Category / case | Actual behavior | Confirmation / subsequent-action counter |
| --- | --- | --- |
| **1. Destination already reached** | One real poll sample; authorized UI visible | **true / 1 — PASS** |
| **2. Delayed full-document navigation** | Held synthetic response released after polling began; two samples; DOMContentLoaded count **1 → 2** | **true / 1 — PASS** |
| **3. Same-document navigation** | History API URL change; two samples; DOMContentLoaded count unchanged **1 → 1** | **true / 1 — PASS** |
| **4. Correct destination, absent UI** | Actual visibility assertion rejected; `AUTHORIZED_UI_CONDITION_FAILED`; **5,124.263 ms** case duration | **false / 0 — PASS** |
| **5. Wrong origin/path** | Wrong-path and wrong-origin samples both observed; 32 samples, `DESTINATION_CONDITION_FAILED` | **false / 0 — PASS** |
| **6. Query/fragment semantics** | Combined query/fragment at exact origin and `/admin` accepted; link still required | **true / 1 — PASS** |
| **7. Redirect away during UI verification** | Link became visible after URL changed away; final destination recheck rejected with `DESTINATION_CHANGED` | **false / 0 — PASS** |
| **8a. Page fatal precedence** | Intentionally thrown page error rejected shared failure signal; `EXISTING_FATAL_EVENT` | **false / 0 — PASS** |
| **8b. Request fatal precedence** | Intentionally aborted synthetic request caused real `requestfailed`; `EXISTING_FATAL_EVENT` | **false / 0 — PASS** |
| **9. Timeout/classification/evidence** | Real poll never matched; 32 samples, `DESTINATION_CONDITION_FAILED`; **29,152.456 ms** case duration | **false / 0 — PASS** |

Destination assertions retained the exact **30,000 ms** setting. The UI assertion retained its existing default timeout without override. Observed case timings are not promises of millisecond-exact deadline expiry; no timeout was shortened, increased or bypassed. No fixed sleep, reload, resubmission or failed-case retry was added.

Query and fragment were tested together in the browser; separate variants remain offline coverage. Redirect during UI verification was exercised here; the before-UI variant remains offline coverage. Full-document and same-document behavior used actual browser navigation/history, not simulated lifecycle events.

The subsequent-action counter is a Node-only stand-in immediately after the confirmation statement, not a network request or protected business action. All six negative cases retained false confirmation and zero subsequent actions; the four positive cases progressed only after both required conditions and rechecks.

## Promise/fatal findings and limitations

In both fatal cases, the losing **real** UI assertion was made to settle successfully after candidate rejection. Its late settlement did not resume the failed workflow: confirmation remained false and subsequent actions stayed zero. The run recorded **zero unhandled rejections** through final event-loop draining.

`Promise.race` does not cancel losing operations; context cleanup remains necessary and completed in all ten cases. The induced page/request failures were explicit negative inputs, not suppressed errors or expanded protected error allowances. No unexpected candidate/fixture/browser failure required repair or retry.

**No candidate defect was demonstrated.** These results establish behavior under the synthetic conditions, not actual Filament authorization/session acceptance or the discarded Attempt 06 cause. Transient redirect-away-and-back between samples and changes after the final check remain possible. No universal claim about all scheduling, crashes or application loading paths is made.

## Preservation and decision

Fresh focused baseline comparison: **4,452/4,452 unchanged**, zero modified/missing. This preserves the previous browser-validation result and runner, candidate/patch, historical drivers/pins, Attempt 06 and other gate evidence, credentials/ledger, protected application/framework sources, **81 inventoried PDF-named artifacts** including the original **73**, ten historical diagnostic JSON files and Batch G artifacts.

New evidence is exclusively under:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-disposable-validation/browser-validation-02/result.json`

The new private runner and `baseline02.json` / `preservation02.json` records are in its parent directory; this report is the new public deliverable. No previous output was overwritten. Historical identity-log append and missing temporary fake-upload metadata remain prior exceptions; nothing was restored, truncated or reconstructed. This is not a full vendor/node_modules inventory or database-state assertion.

**Decision: PASS — candidate is suitable for separately authorized Attempt 07 preparation.** That future work requires an explicitly approved new protected package, reviewed pins and fresh guarded read-only preflight before any separately authorized authentication execution. This disposable authorization grants none of those operations. Do not reuse Attempt 06.

**STOP.** No OSDS application/database access, credentials/session use in the fixture, protected source change, Attempt 07 preparation/reservation/execution, reviewer lifecycle, directory recreation, migration/seeding, B3/B4, commit, push or deployment occurred.
