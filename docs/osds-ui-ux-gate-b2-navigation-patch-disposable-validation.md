# Gate B2 — Navigation candidate disposable-browser validation

**28 October 2026. PASS for separately authorized protected Attempt 07 preparation.** One isolated run completed **10/10 case contexts** covering the nine-category matrix, with zero unhandled rejections or unexpected failures. The candidate remained unchanged and unapplied. This does not prove Attempt 06's root cause or authorize protected execution, authentication or lifecycle actions.

## Execution, source and isolation

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reviewed the navigation investigation, correction-preparation and offline-validation reports.

Executed once using portable Node:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-disposable-validation/run.mjs run
```

| Fact | Actual observation |
| --- | --- |
| Result / exit | **PASS / 0** |
| Start | **2026-10-28 07:12:32.945 UTC / 15:12:32.945 Asia/Manila** |
| Finish | **07:13:41.367 UTC / 15:13:41.367 Asia/Manila** |
| Total recorded interval | **68.422 seconds** |
| Runtime browser version | **Chromium 156.0.8078.4**, queried with `browser.version()` |
| Playwright / Node | **1.64.1 / v24.20.0** |
| Contexts / retries | **10 fresh contexts / zero retries** |
| Cleanup | **10/10 contexts and browser CLOSED** |

The runner verified the complete candidate hash against its existing preparation metadata and extracted the exact navigation block. The actual `authenticationConfirmed=true` assignment and outer workflow/failure race were included. Only the surrounding test environment supplied a synthetic `base` and closed fixture dependencies. The full protected driver was never imported or executed.

All assertions were **real Playwright assertions**, including its actual `expect.poll` loop and role-based `toBeVisible`. A thin synchronous observer delegates to those APIs to record bounded samples and retain the UI promise for late-settlement checks. It does not replace the predicate, comparison, timeout or matcher result. This is materially different from the prior mock-assertion offline suite.

Primary synthetic origin: **`http://127.0.0.1:18046`**. The wrong-origin control alone admits **`http://127.0.0.1:18047/admin`**. These are fixed loopback test origins, not the OSDS application on port 8044. Requests were fulfilled from synthetic HTML through a closed in-memory GET route map; no HTTP listener/application server was started. Query admission was limited to the known fixture query. Unlisted methods/resources, OSDS/external origins, WebSockets, downloads and popups abort validation.

Fresh nonpersistent contexts blocked service workers and downloads. No OSDS credentials, cookies, sessions, Laravel component, framework runtime bundle or database connection was used. The only UI was synthetic markup containing the exact authorized-link label; it represents an acceptance condition, not a real authenticated panel. No screenshots, traces, DOM dumps, raw URLs or exceptions were retained.

## Actual cases

PASS below means the case produced its required positive or fail-closed outcome. An intentionally failed assertion is not mislabeled as a successful authentication.

| Case / matrix category | Observed result | Confirmation / later-action counter | PASS |
| --- | --- | --- | --- |
| **1 — destination already reached** | Real poll matched on first sample; authorized link visible | true / 1 | **Yes** |
| **2 — delayed full-document navigation** | First sample saw wrong path; held destination response was released; real document navigation completed. DOMContentLoaded count **1 → 2**, two poll samples | true / 1 | **Yes** |
| **3 — same-document change** | History API changed URL and fixture displayed link. DOMContentLoaded count stayed **1 → 1**, two samples | true / 1 | **Yes** |
| **4 — destination without authorized UI** | URL matched, real visibility assertion failed after **5,082.076 ms** case time; `AUTHORIZED_UI_CONDITION_FAILED` | false / 0 | **Yes** |
| **5 — wrong origin/path** | Initial wrong path and subsequent controlled wrong origin were both sampled as nonmatches; **32 samples**, `DESTINATION_CONDITION_FAILED` | false / 0 | **Yes** |
| **6 — query/fragment** | Exact origin/path with combined fixture query and fragment matched; authorized link visible | true / 1 | **Yes** |
| **7 — redirect during UI verification** | URL changed away while real UI assertion was pending; link then appeared; post-UI recheck produced `DESTINATION_CHANGED` | false / 0 | **Yes** |
| **8a — page-error precedence** | Deliberate synthetic page error rejected shared fatal signal during UI verification; `EXISTING_FATAL_EVENT` | false / 0 | **Yes** |
| **8b — request-error precedence** | Known synthetic request deliberately aborted; real `requestfailed` event rejected shared fatal signal; `EXISTING_FATAL_EVENT` | false / 0 | **Yes** |
| **9 — destination deadline / evidence** | Real poll never matched, **33 samples**, `DESTINATION_CONDITION_FAILED`; case duration **30,021.750 ms** | false / 0 | **Yes** |

Wrong-origin/path case duration was **29,069.050 ms** with the same explicit 30,000 ms poll setting; durations are observations, not a claim of millisecond-exact matcher scheduling. No timeout value was reduced or enlarged. The authorized-UI assertion had no timeout override and retained its original default behavior, observed at about five seconds for the missing-UI case.

The browser query/fragment case tests both together. Individual query-only/fragment-only variants and redirect-before-UI were covered offline; this browser run does not claim separate contexts for those subcases.

“Later-action counter” is a Node-only stand-in immediately after confirmation, not a click, request or personnel action. It proves that a rejected candidate did not progress to the next workflow statement. There was no sign-in submission or application navigation in this run.

## Polling, failure and Promise.race findings

- Actual polling handled an already reached destination and both real full-document and same-document changes. No new DOMContentLoaded event was required by the same-document case.
- Correct URL alone did not confirm acceptance. The real authorized-link visibility assertion still had to succeed.
- Redirecting away during UI verification prevented confirmation even when the link subsequently became visible.
- All six negative contexts retained false confirmation and zero later actions. Four positive contexts confirmed only after the required URL/UI sequence.
- In **both fatal-event cases**, the losing real UI assertion was deliberately allowed to resolve after candidate rejection by making the synthetic link visible. It actually settled, but confirmation remained false and later actions remained zero. This tests late settlement with a real matcher, not a mocked promise.
- `Promise.race` does not cancel losers. Context cleanup remains necessary. **Zero unhandled rejections** were observed through completion and the final Node event-loop drain.

The two induced errors were expected negative-test inputs. Every such error rejected the candidate's shared failure signal; none was suppressed to permit acceptance. No production error allowance or request policy changed. Unexpected errors/resources would have ended the run without retry; none occurred.

Closed result fields contain case/status, boolean confirmation, literal failure code, bounded sample/marker sequences, relative timings, DOMContentLoaded counts and cleanup status. No raw matcher errors or query strings are exported. Source hash and evidence remain private alongside the result.

## Defects and limitations

**No candidate defect was exposed; no revision was made.** Applicability/syntax checks passed before the single invocation. The original patch, historical driver and pins remain unchanged.

The result establishes the candidate's behavior for the tested synthetic conditions. It does not prove real Filament credentials, middleware, sessions or reviewer authorization. It does not reproduce Attempt 06's discarded exception, compare the old and new wait under that historical environment, or establish a production fix.

The known sampling limits remain: transient redirect-away-and-back between checks may escape detection, and state can change after the final synchronous check. Failure codes describe acceptance boundaries, not root causes. Ordinary queued fatal events were tested; arbitrary reentrant instrumentation and all browser/process-crash schedules were not. Losing operations are not instantaneously cancelled, though tested late settlement was safe and all contexts were closed.

## Evidence and preservation

Fresh, exclusively created evidence:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-disposable-validation/browser-validation-01/result.json`

The runner, filesystem baseline and comparison are in its parent private directory. No earlier output was reused, truncated or overwritten. The candidate is still only a patch/private source artifact; it was not applied to Attempt 06.

Fresh focused preservation comparison: **4,446/4,446 files unchanged**, zero changed or missing. Scope includes candidate/patch, historical driver/pins, Attempt 06 and prior evidence/session bytes, credentials/ledger, protected application/framework sources, all **81 PDF-named artifacts** including the original **73**, ten historical diagnostic JSON files and Batch G artifacts. No log/cache/PDF was restored or cleared. Known identity-log append and missing temporary-metadata exceptions remain preserved. This is not a complete vendor/node_modules inventory or database-row verification.

New artifacts are the isolated runner, baseline/preservation records, this run's result and this report. No protected source or evidence file changed; no Attempt 07 was prepared or reserved.

## Recommendation and stop

**PASS for separately authorized protected Attempt 07 preparation.** That preparation should integrate the reviewed candidate into a new verification package, retain the validated login-readiness guard and one-shot/request boundaries, pin its exact sources, and require fresh guarded read-only checks of existing roots, directory 26 and versions 47/48. Do not repurpose occupied Attempt 06 or infer any review/publication permission.

Current authorization ends here. Any protected package preparation/preflight, new attempt reservation, authentication execution or lifecycle action needs its own explicit scope. B3/B4 remain closed. This report authorizes none of them.

**STOP.** No OSDS authentication/application request, Laravel/PostgreSQL, protected server/runtime, Attempt 07, reviewer lifecycle, directory recreation, migration, PDF, B3/B4, commit, push or deployment occurred.
