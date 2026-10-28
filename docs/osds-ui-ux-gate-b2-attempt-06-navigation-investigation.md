# Gate B2 — Attempt 06 navigation-verification investigation

**28 October 2026. Source-only investigation complete. Root cause UNRESOLVED.** The evidence does not support calling this a confirmed missed-navigation race, DOMContentLoaded timeout, SPA defect or authentication failure. A narrowly scoped destination-state assertion is a defensible correction candidate, not a proven fix. No correction or execution occurred; Attempt 06 remains preserved and occupied. No Attempt 07 was reserved.

## Verified timeline

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reviewed the [execution report](osds-ui-ux-gate-b2-attempt-06-execution-report.md), retained browser/preflight/postflight/launcher results and current prepared driver. Examined installed Playwright **1.64.1**, Filament Login/response and Livewire redirect implementation without importing or executing framework code.

The recorder contains 36 events and zero dropped events. Times below are **Node observer timestamps**, milliseconds since recorder origin; they are not browser DOM-event, expression-execution or server-commit times.

| Sequence | Elapsed ms | Retained observation |
| --- | ---: | --- |
| 19–22 | 26,274.683–26,320.945 | Credential fill boundaries completed after first readiness checkpoint |
| 23 | 26,324.386 | Sign-in click started, after second readiness checkpoint |
| 24 | 26,402.751 | Single permitted login POST forwarded |
| 25 | 26,406.170 | Click completed; current safe path `/admin/login` |
| 26 | 26,407.622 | Navigation verification started; safe path still `/admin/login` |
| 27 | 30,648.915 | Livewire POST response 200 |
| 28 | 33,767.372 | `/admin` response 200 |
| 29–34 | 34,716.144–41,072.029 | Six asset responses, all 200; asset families retained as unknown |
| 35 | 42,031.353 | Workflow failure, phase `login-verifying`, safe path `/admin`; restricted Error, authentication flag false |
| 36 | 42,037.920 | Request-failed marker after workflow failure, during shutdown |

Key intervals: verification began **1.452 ms after click completion**, **4,241.293 ms before the login response**, and **7,359.750 ms before the `/admin` response**. Failure occurred **15,623.731 ms after verification-start**, not 30 seconds. The later failed-request marker is **6.567 ms after failure** and does not establish an initiating network error.

No navigation-check-complete, auth-link-check-start, authentication-confirmed or personnel-page step exists. The failure message/subtype, frame navigation events, document identity, DOMContentLoaded events, response body and redirect effects were not retained. Do not reconstruct them from the sanitized category.

## Exact driver and Playwright analysis

In `scripts/verify-uiux-b2-attempt-06.mjs`, the driver awaits the click, disposes its readiness references, marks navigation start, then awaits:

```javascript
await page.waitForURL(u => u.origin === base && u.pathname === '/admin', {
    waitUntil: 'domcontentloaded',
});
```

Only afterward does it assert the authorized **Case-linked demonstration PDFs** link and set `authenticationConfirmed=true`. The stop is inside the navigation-wait boundary; the required link assertion was not attempted. Both readiness completions are supported by pinned control flow, but they are not authentication assertions.

The URL predicate requires the exact origin `http://127.0.0.1:8044` and pathname `/admin`; it ignores query and fragment. Installed `urlMatches` passes a parsed URL object to the predicate in Node. There is no source evidence of a lost browser closure or wrong predicate argument type. The failure's sanitized `/admin` path is consistent with the intended origin/path at that later observation; it does not reveal the URL at every intermediate event.

Installed Playwright source is bundled in `node_modules/playwright-core/lib/coreBundle.js`:

- **61916**, `Frame.waitForURL`: first matches the **current URL**. If it matches, waits for the requested load state; otherwise delegates to `waitForNavigation` with that predicate.
- **61868**, `waitForNavigation`: registers navigation/error handling, matches the URL, then requires the requested state if `_loadStates` does not already contain it. A navigation error can reject independently of matching the destination.
- **61896**, `waitForLoadState`: returns without waiting for a new event when the requested state is already remembered.
- **61804–61830**, frame event handlers: maintain remembered lifecycle states and update the cached URL before emitting navigation events.
- **61858** and timeout settings around **58963**: page closure/crash/frame detach and timeout can reject. The driver's explicit default timeout is **30,000 ms**; no separate navigation timeout is configured in this source.

Therefore, **a navigation that fully completed before registration is not inherently lost**: the current-URL branch and remembered load states account for it. The installed type documentation even shows clicking before `waitForURL`. Pre-arming every navigation wait is not established as the missing fix here.

A very short interval exists between click completion and waiter entry, but the marker still showed `/admin/login`, followed by the login and destination responses seconds later. This strongly weakens the claim that the complete successful navigation simply finished before the waiter began. Exact frame event delivery was not captured, so an unusual transient route/frame sequence cannot be categorically excluded.

Matching the destination alone does **not** satisfy this call: `domcontentloaded` is an additional contract. A response-headers event does not establish completed body parsing, script execution or DOMContentLoaded. Conversely, SPA/same-document navigation does not automatically break the API: an already remembered lifecycle state can satisfy it. Both simplistic conclusions would overstate the source.

**Important timing contradiction:** a straightforward 30-second timeout beginning at sequence 26 would not fail after 15.624 seconds. The retained Error category does not identify a timeout. There may have been another navigation/frame rejection, an unrecorded condition or different runtime timing behavior; the current evidence cannot choose between them. Do not label this a confirmed timeout or increase its timeout as a repair.

## Filament / Livewire redirect path

Installed `Filament\Auth\Pages\Login::authenticate()` returns its LoginResponse after credential/panel checks and session regeneration. `vendor/filament/filament/src/Auth/Http/Responses/LoginResponse.php` uses `redirect()->intended(Filament::getUrl())`.

Livewire's `SupportRedirects` temporarily binds its component-aware redirector and emits the redirect effect. Its `Redirector::to()` calls the component's redirect without an explicit navigate argument. The component method defaults that argument to false. In the client bundle around **16451–16480**, a true `redirectUsingNavigate` effect selects `Alpine.navigate(url)`; otherwise it assigns `window.location.href`.

The application panel uses standard `->login()` / `->path('admin')`; no `->spa()` call was found there. Thus a normal document redirect is better supported by the inspected default Login path than an assertion that SPA navigation certainly occurred. Other runtime customization/effects were not captured, so actual navigation mode is still unknown. No response payload was recovered or inspected for credentials/session data.

The guarded router also bootstraps before its static-file check. With the loopback PHP server, this can add work to asset requests. The post-destination asset response sequence supports ongoing loading, but does not prove which resources blocked parsing or that load latency caused rejection. The framework assets are already preserved; speculative server changes or asset republishing are unjustified.

## Ranked hypotheses

Ranking reflects supporting evidence, not a measured probability or confirmed diagnosis.

| Rank / explanation | Supporting evidence | Contradictions / unknowns |
| --- | --- | --- |
| **1 — navigation/lifecycle wait rejected despite a destination change** | Source couples URL matching with lifecycle/error handling. Browser later reports `/admin`; asset responses continued; no completion marker. | No frame/load-state/error subtype telemetry. Fifteen-second observed interval contradicts a simple configured 30-second timeout. Exact rejection cause unresolved. |
| **2 — navigation abort, frame replacement, page lifecycle interruption or related runtime failure** | Installed waiter rejects navigation errors/closure/crash/detach independently. Restricted Error can represent such a rejection without a JavaScript pageerror. | No such event was retained; user-driven context closure occurs after workflow failure. The later request-failed marker proves no initiating cause. |
| **3 — SPA/redirect/load-state integration mismatch** | Livewire supports both modes and UI readiness differs from document lifecycle. | Standard inspected Login redirect favors normal location navigation; no captured navigate effect or panel SPA setting. Remembered lifecycle states mean SPA alone is insufficient explanation. |
| **4 — waiter registered after successful navigation and missed it** | Wait is sequenced after the click. | Current-URL/load-state handling exists; marker remains on Login; relevant responses occur substantially later. Weakly supported and not an adequate root-cause conclusion. |
| **5 — persistent wrong origin/path or predicate defect** | Exact matching can reject trailing slash or alternate origin. | Recorded failure path is the expected `/admin`; installed callback receives URL correctly. A transient mismatch remains possible, not demonstrated. |
| **6 — failed authentication or wrong destination content** | HTTP 200 does not establish authenticated content/identity; authorized link was never inspected. | Standard redirect path and observed destination suggest progress. No credential/authorization error was retained. Authentication success and failure both remain unproven. |

**Root cause cannot be established from the retained evidence.** The confirmed finding is failure at a composite navigation assertion with incomplete causal diagnostics, not proof of a production defect. Authentication remains unconfirmed; personnel lifecycle acceptance must stay blocked.

## Smallest defensible proposal — not implemented

For a separately reviewed verification-only correction, separate **destination state** from document lifecycle and retain the existing authorized UI assertion. A bounded `expect.poll` over the current URL's origin/path can preserve the exact predicate semantics without requiring a new navigation/load event. Keep the current bound, one-shot click, both readiness guards, and all page/request/HTTP fail-fast listeners.

Conceptually: observe `origin === base && pathname === '/admin'` as a bounded state assertion, then require the already specified authorized link to be visible. The URL observation is not authentication; the link assertion and subsequent expected page checks must still pass before acceptance is marked confirmed.

This is a **contract-alignment candidate**, not a proven fix for Attempt 06. It is justified because the required acceptance state is destination plus usable authorized UI; no independent requirement for DOMContentLoaded was established. If the underlying problem is an aborted/invalid page or failed authentication, the UI assertion or existing fatal path must still fail.

**Do not blindly substitute predicate-form `toHaveURL`.** Installed `node_modules/playwright/lib/matchers/expect.js:12225` delegates that form to `mainFrame().waitForURL`, omitting `waitUntil` and thereby potentially waiting for the default **load** state. String/regex URL matchers use a different web-assertion path around line 12943, but changing exact-string/query semantics also needs review. `expect.poll` is present around line 13267 and can preserve the current origin/path contract directly.

No longer timeout, sleep, reload, second POST, authentication bypass, error suppression, script reordering, runtime patch or broad framework project is proposed. Do not merely move the existing wait before the click and claim the cause fixed.

## Risks and required validation

- Destination state can arrive before useful content; retain authorized-link visibility and exact personnel-page component/draft assertions. Never turn `/admin` or HTTP 200 into authentication PASS.
- A later redirect could invalidate a briefly matched URL; validate destination again alongside the UI checks, retaining failure listeners throughout.
- URL polling must remain read-only and bounded, preserving origin/path restrictions and the existing query/fragment treatment. Do not log full URLs or restricted exceptions.
- Before implementing, source-review the proposed matcher semantics and define closed failure categories such as URL-condition failure versus UI-condition failure; lifecycle snapshots may be booleans/enums only. Do not recover discarded diagnostics or expand raw tracing.
- Separately authorized disposable checks should cover destination already reached, delayed full-document navigation, same-document URL change, correct URL with absent authorized UI, wrong origin/path, redirect away and page/request error precedence. No OSDS credentials/database are needed for those checks.
- Only after source preparation and disposable validation should a separately authorized new protected attempt be considered, with fresh guarded preflight, unchanged drafts/roots/permissions and a new non-overwriting evidence path. Attempt 06 must never be reused.

## Preservation and authorization boundary

Only source/evidence reads and filesystem hashing were performed; no installed Playwright, Filament or Livewire code, test fixture, browser, server, application request, Laravel bootstrap or database query was executed. No driver, guard, policy, credential or framework file was modified.

Fresh focused preservation comparison: **4,431/4,431 files unchanged**, zero modified/missing. Scope includes the previous protected baseline, Attempt 06 evidence/session bytes, its execution report and installed Playwright source/package metadata. All 81 inventoried PDF-named artifacts (original 73 included), ten historical diagnostic JSON files, prior attempts, credentials/ledger and protected sources remain unchanged. This is not a whole vendor/node_modules inventory or new database-state assertion. Prior identity-log and temporary-metadata exceptions remain preserved.

Private hash records are under `storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-06-navigation-investigation/`. New artifacts are those filesystem-preservation records/helper and this report. Current installed Playwright source was inspected; Attempt 06 did not retain a dedicated historical hash for its core bundle, so exact historical byte identity of that unpinned package file cannot be independently established beyond the available version/source evidence.

**Next authorization:** source-only preparation of the minimal destination/UI assertion correction and focused offline/disposable-test design. Actual disposable browser execution, protected authentication, a new attempt reservation, and any lifecycle operation each remain outside the current authorization. No Attempt 07 was prepared or reserved.

**STOP. Root cause unresolved; correction proposed only.** No retry, browser/server, authentication, application/database operation, migration, lifecycle action, PDF, B3/B4, commit, push or deployment occurred.
