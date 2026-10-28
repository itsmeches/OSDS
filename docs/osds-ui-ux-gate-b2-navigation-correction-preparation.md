# Gate B2 — Navigation verification correction preparation

**28 October 2026. Source patch PREPARED; applicability and syntax checks PASS. No test or browser execution.** Root cause remains unresolved. Attempt 06's driver, pins and evidence remain unchanged; no Attempt 07 was reserved or prepared. Protected acceptance remains BLOCKED pending validation and separate execution authorization.

## Exact artifact and scope

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reviewed the Attempt 06 [execution report](osds-ui-ux-gate-b2-attempt-06-execution-report.md), [navigation investigation](osds-ui-ux-gate-b2-attempt-06-navigation-investigation.md), prepared driver and installed Playwright poll implementation.

Created **`scripts/patches/uiux-b2-navigation-verification.patch`**, an inert, reviewable patch targeting the five-line navigation block beginning at **line 80** of `scripts/verify-uiux-b2-attempt-06.mjs`. The replacement is 27 lines: destination polling, the original UI assertion, destination rechecks and closed failure classification. No other driver block is changed by the patch.

The patch is **not applied** to the historical Attempt 06 driver. A complete text-only candidate is private at:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-correction/candidate-driver.mjs`

Its replacement starts at line 80; the original authentication-confirmed flag follows the replacement, at candidate line 107. The candidate was parsed only, not imported or run. It still carries the occupied Attempt 06 path and is not an execution entry point for a new attempt. Do not use it to reuse Attempt 06 or bypass its pins. The patch header identifies its source baseline, not permission to alter/reexecute historical evidence.

New files are the patch, this report and private filesystem baseline, patch metadata, candidate and preservation helpers/results. No application, driver, guard, policy, framework, credential or historical evidence file was edited.

## Before/after contract

| Condition | Existing driver | Prepared correction |
| --- | --- | --- |
| Destination | URL predicate plus `domcontentloaded` dependency | Bounded **`expect.poll`** over current URL origin/path; no lifecycle-event prerequisite |
| Origin/path | Exactly `base` and `/admin` | Exactly the same; query/fragment remain ignored |
| Destination bound | Driver's 30,000 ms default | Explicit **30,000 ms**, no increase |
| Authorized UI | Exact **Case-linked demonstration PDFs** link must be visible | Same locator/assertion and existing assertion timeout retained |
| Redirect-away check | No explicit URL recheck beside the UI assertion | Instant destination checks immediately before and after UI visibility assertion |
| Fatal failure | Existing shared failure promise/listeners | Preserved; explicitly raced at each new awaited condition, with abort checks |
| Authentication flag | Set after navigation and link assertions | Still after all assertions, now including destination revalidation |

The URL function constructs a local URL from `page.url()`, returns a boolean and never exports its string. Parsing failure returns false. Polling observes state; it performs no navigation, credential entry, reload or request. No custom polling interval, sleep, larger timeout or retry of a business action is introduced.

This removes a document-lifecycle dependency without removing authentication acceptance criteria. HTTP 200 or reaching `/admin` still cannot pass acceptance without the exact authorized link. Both password-readiness checkpoints, one Sign in click, consequential-request rejection, endpoint allowlist, personnel-page checks, postflight and cleanup remain outside the patch and unchanged.

The existing outer failure race stays intact. The poll callback checks the abort flag before reading URL state; installed `invokePollMatcher` evaluates that callback outside its assertion-retry catch. A thrown abort therefore terminates that poll rather than becoming an ordinary false sample. No framework function is wrapped or replaced.

## Closed failure evidence

The proposed result field `verificationConditionFailure` contains only one of:

- `DESTINATION_CONDITION_FAILED`: the bounded initial destination condition failed without an already observed fatal event.
- `AUTHORIZED_UI_CONDITION_FAILED`: the existing authorized-link assertion failed.
- `DESTINATION_CHANGED`: an immediate URL recheck before/after the UI assertion failed.
- `EXISTING_FATAL_EVENT`: an existing page/request/HTTP fatal signal took precedence.

These describe **which acceptance condition failed**, not the historical root cause or necessarily a timeout. Catch paths replace local exception details with fixed messages and rethrow; they do not suppress errors or turn failure into success. Existing first-error sanitization remains authoritative, including when the outer failure race wins. No new raw URL, error text, request body, DOM, credential, cookie or stack capture is added.

## Static checks actually performed

| Check | Result |
| --- | --- |
| Exact baseline block / text replacement | **PASS**, one matching block; five lines replaced with 27 |
| `git apply --check --unidiff-zero scripts/patches/uiux-b2-navigation-verification.patch` | **PASS**, check only; patch not applied |
| Node `--check` on private complete candidate | **PASS**, parse only; imports/driver not executed |
| Source review of installed poll behavior | **PASS**, supports callback and explicit bound; callback rejection is not swallowed as a matcher retry |
| Scoped whitespace checks | **PASS**, new patch/report |
| Executable unit/fixture tests | **NOT RUN**, outside this non-executing-check authorization |
| Disposable/protected browser, application/database | **NOT RUN** |

No current or historical test total is presented as validation of this new patch. Filesystem scripts only prepared text and hashes; no candidate, Playwright or application code was executed.

## Proposed validation matrix — not executed

Use the exact candidate logic in future tests, not a second manually implemented destination rule. Polling observations may repeat; sign-in and navigation actions must not.

| Case | Offline design | Disposable design / acceptance |
| --- | --- | --- |
| Destination already reached | Current URL initially matches; UI ready | Begin at synthetic destination; no navigation event required; pass only with authorized UI |
| Delayed full-document navigation | URL sequence changes once; bounded poll sees final state | Controlled synthetic document navigation; authorized UI appears; both conditions pass |
| Same-document URL change | Current URL changes without lifecycle event | Synthetic History API route change; UI appears; no DOMContentLoaded dependency |
| Correct destination, absent authorized UI | Destination passes, UI assertion fails | Keep link absent/hidden; flag stays false; UI-condition code retained |
| Wrong origin or pathname | Include alternate origin, `/admin/`, nested paths; all fail | Controlled synthetic equivalents remain rejected without accessing OSDS |
| Query/fragment semantics | Same origin and `/admin` with query/fragment remain matching | Confirm no accidental exact-string restriction; do not record query contents |
| Redirect away | Match destination, then change before or during UI check | Persistent redirect away must yield `DESTINATION_CHANGED`; no authentication flag |
| Page/request error precedence | Reject existing failure signal while either condition is pending or just resolved | Induced errors must abort; no later confirmation/action; no allowance expansion |
| Timeout and data limits | Simulate destination/UI failures with closed codes only | Keep original bounds; no waits extended, raw dumps or retries |

Offline tests would also verify that only the navigation block changed, both readiness checks remain, Sign in is called once, authorization/navigation assertions remain and no lifecycle operation is added. A disposable fixture needs only synthetic routes/UI; no credentials or OSDS database are required. No disposable runner, new attempt ID or reservation was prepared here.

## Risks, preservation and authorization

The candidate is a contract-alignment correction, **not a confirmed fix** for Attempt 06. Destination can match before useful UI; that is why visibility remains required. Before/after rechecks detect a destination that stays changed at those boundaries, but cannot prove that no transient redirect-away-and-back occurred between samples. The patch is not an atomic guarantee against changes after the final check. Existing fail-fast handling and subsequent exact personnel-page checks remain necessary.

The lifecycle condition is intentionally removed; if a separate delivery requirement truly needs completed document parsing, it must be identified and tested explicitly rather than inferred from URL navigation. Do not replace this with predicate-form `toHaveURL`, which the inspected version routes through navigation waiting.

Fresh focused preservation: **4,432/4,432 baseline files unchanged**, zero changed/missing. This includes the historical driver/pins, Attempt 06 evidence/session bytes, prior reports and protected baseline, all 81 inventoried PDF-named artifacts (original 73 included), ten historical diagnostic JSON files, credentials/ledger and framework/application sources. Existing identity-log and missing temporary-metadata exceptions remain unchanged. The inventory is not a complete vendor/node_modules or database-state verification.

Private evidence is under `storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-correction/`. No evidence was restored, truncated or reconstructed. Only the proposed patch artifact, private preparation records and this report are new.

**Execution boundary:** this authorization permits preparation and non-executing checks only. Executable tests need separate authorization; disposable browser execution needs explicit scope and a fresh isolated evidence path. Protected authentication or any new attempt reservation additionally requires an explicitly approved package with reviewed pins and fresh guarded preflight. Attempt 06 remains permanently occupied; the patch must not be applied as a means of rerunning it. No Attempt 07 is authorized or prepared, and no reviewer lifecycle/B3/B4 action follows from this source preparation.

**STOP. Patch prepared, not applied or executed.** No browser/server, authentication, Laravel/PostgreSQL, production change, record mutation, migration/seeding, PDF, lifecycle action, commit, push or deployment occurred.
