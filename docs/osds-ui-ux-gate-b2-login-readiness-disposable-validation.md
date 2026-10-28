# Gate B2 — Login readiness disposable-browser validation

**28 October 2026. PASS: one disposable validation of the implemented guard completed.** All required scenarios passed in **nine fresh contexts**, including the disconnect/replacement and missing-state subcases. Neither readiness checkpoint was bypassed. This is not protected Gate B2 acceptance or authentication verification. No Attempt 06 was prepared; B3/B4 remain closed.

## Exact implementation and execution

Applied OSDS Verification Gates and Livewire Browser Diagnostics. Used the unchanged real functions from `scripts/uiux-b2-login-readiness.mjs`: `loginReadiness`, `inspectLoginReadiness` and `requireLoginReadiness`. No alternate predicate or collector was implemented.

New private adapter files, all within the existing reproduction directory:

- `prepare-guard-validation.mjs`: copies full installed assets and prepares synthetic markup/provenance.
- `guard-fixture.html`, `guard-livewire.js`, `guard-support.js`: new fixture and byte-identical framework copies. Existing password module copy is reused unchanged.
- `guard-validation-manifest.json`: copied-file, installed-source and exact guard hashes.
- `validate-login-readiness.mjs`: one-shot isolated browser validation runner.
- `check-guard-validation.mjs`: focused offline preparation checks.
- `guard-validation-preflight.mjs` and its JSON: dependency, hash and unused-output record.
- `guard-validation-preserve.cjs` and its baseline/comparison: preservation evidence.

Existing guard, tests, reviewer harness, production files, earlier private framework copies, Experiment 01 and its result were **not edited or rerun**. The synthetic fixture adds only the fixed expected Login memo name as test metadata; it has no `wire:id`, real login route, credential entry, server hydration or authentication action. The copied framework starts its normal Alpine/AsyncAlpine initialization and loads the real password module natively.

Executed once with portable Node:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/validate-login-readiness.mjs run
```

Fixed, newly reserved evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/login-readiness-validation-01/`

It contains the exclusively created `result.json`; the directory was absent before invocation and must not be reused.

| Execution fact | Observed |
| --- | --- |
| Result / process exit | **PASS / 0** |
| Start | **2026-10-28 04:05:50.935 UTC / 12:05:50.935 Asia/Manila** |
| Finish | **04:05:54.112 UTC / 12:05:54.112 Asia/Manila** |
| Recorded duration | **3.177 seconds**, start-to-finish timestamps |
| Node / validation PID | **v24.20.0 / 4080** |
| Local Playwright package | **1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Contexts / retries | **9 / 0** |
| Cleanup | **9/9 contexts closed; browser closed** |

No browser package was installed or downloaded, and no fallback browser executable was selected.

## Pre-execution checks and isolation

**PASS: 70 existing guard checks and 28 new offline preparation checks.** These verified guard/framework hashes, synthetic metadata, resource denial controls, runner safety contracts and unused output. Syntax checks passed for the private runner/preparation/check files and copied bundles. Local Chromium availability was checked before launch. A fresh protected-file baseline was established before preparation.

The browser used only **`https://password-repro.invalid`** and the existing exact resource policy. Admitted GET resources were fulfilled in memory from fixed private files. Unlisted paths, OSDS/external origins, ports, queries and methods were denied; no POST was required or sent by the validation. The old `/adapter.js` observer is absent from this fixture and cannot be served by the new resource map.

Each case had a new nonpersistent context with service workers and downloads disabled, plus a rule closing unexpected WebSockets without connecting them. No existing browser profile, cookies, sessions, credentials or application endpoint was loaded. No screenshot, raw DOM, console log, request body or unrestricted trace was captured. No HTTP server, Laravel bootstrap, PostgreSQL connection or browser MCP was used.

## Actual scenario results

Credential-entry and sign-in **stand-ins are controlled Node action counters**, not actual input values, network requests or authentication. The same guard called by the reviewer harness controls when those actions may occur.

| Scenario | Observed guard behavior | Entry stand-ins | Sign-in stand-ins | Result |
| --- | --- | ---: | ---: | --- |
| **A — native import pending, then released** | Initial `PENDING`; both counters stayed zero while the real guard awaited the loader. Native module release completed initialization; first checkpoint allowed entry and second checkpoint allowed one sign-in. Final `READY`. No page errors. | **1** | **1** | **PASS** |
| **B — native import rejected** | Initial `PENDING`; controlled module 404 produced `IMPORT_REJECTION`. The shared failure signal aborted readiness. Both counters stayed zero. | 0 | 0 | **PASS** |
| **C — wrapper disconnected** | Initialized fixture, then original DOM node removed. Collector/guard rejected the captured identity. | 0 | 0 | **PASS** |
| **C — wrapper replaced after entry** | First checkpoint passed and allowed the entry stand-in. Replacing that node did not permit reacquisition: second checkpoint rejected it. | **1** | **0** | **PASS** |
| **D — missing Alpine state** | Fixture state stack removed after initialization; actual collector and predicate rejected it. | 0 | 0 | **PASS** |
| **D — absent reveal-state property** | Fixture stack replaced with an empty scope; no reveal-state property was injected. Guard rejected it. | 0 | 0 | **PASS** |
| **D — invalid metadata** | Fixture loader state changed to an unknown value; guard rejected it. | 0 | 0 | **PASS** |
| **E — duplicate wrapper** | An additional real DOM wrapper was added; exact-count check rejected ambiguity. | 0 | 0 | **PASS** |
| **F — page-error precedence** | While pending, an intentionally thrown synthetic page error aborted the guard. No stand-in ran. | 0 | 0 | **PASS** |

Across the whole validation there were **two entry stand-ins and one sign-in stand-in**. The second entry belongs to the replacement-between-checkpoints control; that case correctly permitted no sign-in. All cases began with a held native module request and a `PENDING` sample.

The negative state mutations were confined to disposable fixture nodes. They test the guard's interpretation of actual DOM metadata; they do not simulate a production repair. No provider was injected to make readiness succeed. The successful case used registration and state supplied by the copied installed implementation.

## Error handling and checkpoint integrity

**Unexpected framework errors: zero. Isolation violations: zero.** In particular, neither `FACTORY_UNDEFINED` nor `REVEAL_STATE_UNDEFINED` appeared in this validation.

Two deliberately induced negative inputs were retained as closed categories: `IMPORT_REJECTION` in B and `INTENTIONAL_ERROR` in F. Every page error still aborted the real readiness guard and prevented actions. Those cases passed because the negative input failed closed; errors were not suppressed, and the protected reviewer driver's error policy was not changed. Any other page error would have stopped the entire validation.

Both actual checkpoints ran successfully in A using the same captured node references. The replacement control demonstrated that passing the first did not bypass the second. Disconnect, invalid state and duplicates also prevented sign-in. No retries, fixed sleeps, repeated submissions or extra POSTs were introduced. Bounded semantic waits used the implemented 30-second RAF-based condition; none timed out.

Only closed phases, status, action counts, readiness classifications, gate state, error categories and cleanup outcomes were retained. Raw scope objects, reveal-state values and DOM contents were not exported. F records the gate as held during its failure; cleanup closed the context before releasing its cancelled pending route. It does not claim a successful import in F.

## Preservation

Fresh focused baseline: **4,227 files**. Final result: **4,227/4,227 unchanged**, zero modified or missing files, including:

- Experiment 01, Gate B2 attempts 01–05, historical reports and diagnostic/reproduction evidence.
- Historical identity logs, foundation credentials/ledger, the implemented guard/tests and reviewer harness.
- Protected application/configuration and selected installed/public framework files, Batch G artifacts.
- **81/81 PDF-named artifacts**, including the original **73**, and **10/10 historical diagnostic JSON files**.

Private comparison records use `guard-validation-baseline.json` and `guard-validation-preservation.json` in the reproduction directory. New artifacts are the private adapter/copies/check/preflight files, this validation's new result directory and this report. No prior evidence directory was reused. The known historical identity-log append and missing temporary fake-upload metadata remain prior exceptions; no logs/caches were cleared, restored or reconstructed.

This focused scope is not an exhaustive vendor/node_modules inventory or database-row verification. No database row was accessed. Attempt 06 remains absent.

## Decision and remaining limits

**PASS: the real guard worked against actual disposable browser DOM state and native password-module initialization.** This supports considering separately authorized protected Gate B2 preparation next. It does not authorize that preparation or execution.

Remaining limitations: the fixture supplies only the fixed Login memo identity and password markup; it does not render the real PHP Login component or test credential acceptance, middleware, session handling, request processing or reviewer navigation. Stand-ins prove gating, not authentication. No guarantee is made against DOM changes after a sample/during actionability, all fast-user timing paths, or future framework versions. Attempt 05's historical cause remains unconfirmed, and no production fix is claimed.

The next protected-stage proposal should preserve the passing guard, exact request/permission boundaries and one-shot actions, then obtain separate authorization for a new fixed evidence path and fresh guarded preflight of the existing roots, directory 26 and versions 47/48. No record should be recreated or advanced merely because this disposable validation passed.

**STOP.** No OSDS navigation/authentication, Laravel/PostgreSQL, Attempt 06 preparation/execution, COR/ACSA lifecycle transition, directory recreation, migration, PDF, B3/B4, commit, push or deployment occurred.
