# Gate B2 — Candidate navigation patch offline validation

**28 October 2026. PASS for proceeding to separately authorized disposable validation.** Exact candidate-block tests: **26 passed / 0 failed, 150 assertions**, zero unhandled rejections. No candidate revision was required by these results. This is not browser validation, protected acceptance or confirmation of Attempt 06's root cause. The patch remains unapplied; no Attempt 07 was prepared or reserved.

## Tested source and isolation

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Reviewed the [prepared patch/report](osds-ui-ux-gate-b2-navigation-correction-preparation.md), historical driver and installed poll semantics described by the preceding investigation.

Executed once with portable Node:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-offline-validation/tests.mjs
```

The harness reads the complete prepared candidate, extracts the **exact navigation block**, and executes it with controlled `page`, `expect`, marker and failure-promise dependencies. It includes the actual subsequent `authenticationConfirmed=true` assignment and the existing outer workflow/failure race. It does not import the complete driver, Playwright, credentials or any application module.

The `expect` dependency is an explicit deterministic stand-in: it invokes the candidate's real poll callback, checks the configured 30,000 ms bound and models assertion success/rejection. URL changes, visible-UI settlement and fatal signals are controlled in memory. The candidate's predicate, guards, catches, failure classifications and URL rechecks are not reimplemented. No real wall-clock timeout, DOM, navigation lifecycle or installed Playwright polling loop runs. `setImmediate` only drains Node promise-rejection delivery; no fixed sleep or network request is used.

Private `results.json` contains closed test names/status and counts. No raw URL, exception, DOM, credential or cookie is exported. Failure-detail redaction is checked with synthetic detail, not a recovered restricted message.

## Coverage of the nine proposed scenarios

| Proposed scenario | Actual offline coverage | Result / boundary |
| --- | --- | --- |
| **1. Destination already reached** | Initial matching URL, visible authorized UI; one sample and one UI assertion | **PASS**, confirmation only after both conditions |
| **2. Delayed full-document navigation** | Controlled URL changes from Login to destination between samples | **PASS as a URL-transition model**; no real document or DOMContentLoaded validation |
| **3. Same-document URL change** | Same controlled URL transition without any lifecycle-event dependency | **PASS as a same-document model**; intentionally the same consumer behavior as case 2, not a separate browser implementation |
| **4. Correct destination, absent authorized UI** | UI assertion rejects; original exact role/name locator is verified | **PASS**, `AUTHORIZED_UI_CONDITION_FAILED`, flag false; synthetic error detail absent from retained result |
| **5. Wrong origin/path** | Alternate host, port, HTTPS, `/admin/`, nested path, Login path and unparsable URL | **PASS**, destination failure, zero UI assertions, flag false |
| **6. Query/fragment semantics** | Query only, fragment only and both at the exact origin/path | **PASS**, original ignored-query/fragment semantics retained |
| **7. Redirect away** | URL changes before UI assertion or while its controlled promise is pending | **PASS**, `DESTINATION_CHANGED`, no authentication confirmation |
| **8. Page/request fatal precedence** | Already-failed signal, pending destination/UI, simultaneous UI resolution and fatal signal, fatal at destination-completion boundary | **PASS**, `EXISTING_FATAL_EVENT`, flag false; no later confirmation after losing promises settle |
| **9. Timeout/data limits** | Deterministic destination deadline, UI rejection, indefinitely pending operation until explicit settlement, late losing rejections and private-detail exclusion | **PASS**, fixed codes and bounds. Actual elapsed-time timeout scheduling remains untested. |

The 26 test cases include subcases for the wrong destinations, query/fragment forms and promise races. All original nine matrix categories are represented; that does not imply nine live navigation scenarios were run.

## Promise and authentication findings

**Confirmed in these offline tests:**

- A pending shared failure promise does not prevent a successful condition from winning `Promise.race`.
- A fatal signal during pending destination/UI verification rejects the workflow. Once observed, the abort flag prevents subsequent authentication confirmation, including when the successful condition resolves in the same Node turn.
- `Promise.race` does **not cancel** its losing operation. Late UI resolution did not resume the rejected candidate path. Late poll/UI rejections were handled: the process-level observation recorded **zero unhandled rejections**.
- The installed-semantics review remains relevant: the real poll generator throws on the abort flag before reading URL state. The harness models that behavior but does not certify actual Playwright cancellation or cadence.
- Destination and UI assertion failures retain false authentication state. Success requires destination, the exact authorized UI assertion and the destination recheck. HTTP response codes are not inputs to this harness and cannot independently satisfy it.
- The outer workflow race can settle before an inner catch has populated its condition code. Tests inspect settled state after draining microtasks; this is not proof that every possible live cleanup schedule will persist every optional code. Existing first-error evidence remains necessary.

There is no await between the final synchronous URL recheck, final marker and flag assignment in this candidate. Ordinary queued Node fatal callbacks cannot interleave inside that synchronous segment. The tests do not establish safety against arbitrary reentrant instrumentation, nor continuous authentication validity after confirmation. A fatal event **after** a successful confirmation is a later workflow failure; it does not mean the flag became true after an already observed fatal event.

The offline suite checks the requested no-confirmation-after-failure property in the tested orderings. It is not a universal proof over browser internals, process crashes or all scheduler behavior.

## Preservation of existing acceptance controls

The static integration test compares the complete candidate prefix and suffix against the historical driver, allowing only the intended navigation-block replacement. It confirms:

- Both original readiness calls remain.
- Exactly one Sign in click remains.
- Request/endpoint validation, original fatal listeners and no-retry submission handling are unchanged.
- The authentication flag and subsequent authorized personnel-page navigation/headings remain after the corrected block.
- No lifecycle invocation was added; the historical driver and source pins remain untouched.

Patch applicability check (`git apply --check --unidiff-zero`) and Node syntax parsing passed. The patch was **not applied**. No protected driver, guard, policy or framework file was edited.

## Edge cases and revision decision

**No candidate defect was exposed by this suite; no revision was made.** Remaining limitations are material:

1. The poll stand-in uses a controlled sample/deadline model, not the real Playwright poll loop or its timing. Full- versus same-document cases verify the candidate ignores lifecycle events, not actual browser handling.
2. Before/after URL checks detect redirects that remain away at those checks. A transient away-and-back between observations can still escape; this is the previously documented sampling limit.
3. Losing promises may remain active until they finish, observe the abort flag or the browser context closes. The tests establish handled late rejection, not instantaneous cancellation or full browser cleanup.
4. The authorized UI stand-in verifies the exact locator/condition invocation, not whether actual Filament page content provides that link or whether the reviewer authenticated.
5. Existing fatal races can affect which diagnostic field is persisted first. An omitted optional condition code must not be read as absence of an error.

These limits require disposable browser validation, not speculative changes to production, timeout increases, error allowances or a second authentication attempt. Attempt 06's historical failure remains unresolved.

## Baseline preservation

Fresh focused baseline: **4,440 files**. Final comparison: **4,440/4,440 unchanged**, zero modified or missing, including the candidate and patch, historical driver/pins, Attempt 06 evidence/session bytes, previous attempts/reports, credentials/ledger, protected application/framework files, all **81 inventoried PDF-named artifacts** (original 73 included), ten historical diagnostic JSON files and Batch G artifacts.

New files are this report and the isolated test/preservation helper, baseline/results/comparison under:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-navigation-offline-validation/`

No previous output was reused or overwritten. The historical identity-log append and missing temporary fake-upload metadata remain prior exceptions. No source, log, cache, PDF or record was restored, truncated or reconstructed. This is a focused inventory, not a whole vendor/node_modules or database-state claim.

## Recommendation and authorization boundary

**PASS for a separately authorized disposable-browser validation of the unchanged candidate logic.** That validation must use a synthetic isolated origin/resources, no OSDS credentials or application/database access, a new non-overwriting evidence directory and the nine-case matrix. It must retain fail-fast handling and distinguish assertion failures from induced negative-test inputs. Any observed defect must be reported rather than silently patched/retried.

No disposable runner or protected attempt was prepared here. Browser execution, protected authentication, Attempt 07 reservation and lifecycle acceptance remain unauthorized. Do not apply the patch to reuse Attempt 06.

**STOP. Offline tests only.** No Playwright/browser launch, server, Laravel/PostgreSQL, authentication, protected runtime, personnel action, directory recreation, migration, PDF, B3/B4, commit, push or deployment occurred.
