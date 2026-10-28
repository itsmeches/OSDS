# Gate B2 — Isolated framework browser experiment 01

**28 October 2026. BLOCKED.** The prepared runner was invoked exactly once. Scenario A's comparison context recorded the expected `FACTORY_UNDEFINED` error, then an additional `REVEAL_STATE_UNDEFINED` ReferenceError triggered the unchanged fail-fast rule. No instrumented context or later scenario ran. No correction, retry or OSDS interaction occurred. B3/B4 remain closed.

## Execution identity and preflight

Executed using the portable Node binary, version **v24.20.0**:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/run-isolated-browser.mjs run
```

Experiment identity: **`isolated-browser-execution-01`**. The prepared runner atomically created its previously absent fixed evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-isolated-framework-reproduction/isolated-browser-execution-01/`

Actual process exit: **1**. Command tool elapsed time: **4.626 seconds**. Result-file write timestamp: **2026-10-28 03:25:19.744 UTC / 11:25:19.744 Asia/Manila**. The runner did not record an exact invocation-start timestamp, browser PID or runtime `browser.version()`; none is invented here.

Preflight was recorded at **03:25:01.657 UTC / 11:25:01.657 Asia/Manila**, before invoking the command.

| Safeguard | Actual result / evidence |
| --- | --- |
| Fixed output path unused | **PASS**, absent before invocation; atomic `mkdir` succeeded |
| Entry-point pins and fixture hashes | **PASS**, all pinned adapter/copy/input hashes matched |
| Installed source provenance | **PASS**, source hashes matched the prepared manifest |
| Synthetic origin / resource scope | **PASS**, fixed `https://password-repro.invalid`; exact GET allowlist and denial controls verified |
| OSDS/external targets, ports, paths and methods | Denied by the existing pure policy; no resource-policy violation was reported during this execution |
| WebSocket isolation | Prepared route closes sockets without connecting a server; no unsupported-API or socket failure was recorded |
| Context isolation | Fresh nonpersistent context, service workers blocked, downloads disabled; no existing browser profile or storage state imported |
| Credentials, application routes and sessions | None loaded by the fixture or runner; no OSDS request/authentication path exists in the admitted resource set |
| Local Playwright | **PRESENT: 1.64.1** |
| Expected local Chromium executable | **PRESENT**, before invocation; no download/install or alternate executable used |

The local Playwright package metadata specifies Chromium / headless-shell revision **1248**, browser version **156.0.8078.4**. This identifies the configured package build, not an independently queried runtime version. A browser context executed the synthetic page and produced the retained errors; this was not merely a launch/dependency failure.

All resources were served through the prepared in-memory Playwright routes. No HTTP server, Laravel bootstrap, PostgreSQL connection or browser MCP was used. No script, resource policy, CSP, error allowance or browser option was changed during execution.

## Actual scenario outcomes

| Scenario | Comparison context | Instrumented context |
| --- | --- | --- |
| **A — pending native import followed by morph** | **BLOCKED / incomplete:** expected factory error followed by unexpected reveal-state error | **NOT RUN** |
| B — initialized before morph | **NOT RUN** | **NOT RUN** |
| C — native import rejection | **NOT RUN** | **NOT RUN** |
| D — disconnection during import | **NOT RUN** | **NOT RUN** |
| N — initialized without morph | **NOT RUN** | **NOT RUN** |

One of ten planned contexts was entered; none completed the full scenario observation/capture path. The remaining nine contexts were not executed. No assertion count or PASS total is inferred from earlier offline tests.

The retained result contains:

```text
status: BLOCKED
scenario: A
mode: control
errors: [FACTORY_UNDEFINED]
gateRequests: 1
unexpectedError.name: ReferenceError
unexpectedError.category: REVEAL_STATE_UNDEFINED
```

The additional closed category corresponds to the already known public reveal-state identifier. It was **not** admitted as an expected Scenario A error by the prepared runner, so execution stopped rather than extending its allowance. The result's generic failure field is `Isolated invariant or browser dependency failed`; its retained unexpected-error field supplies the more precise observed blocker. No raw exception, arbitrary identifier, stack or console dump was captured.

**Classification: blocked isolated-experiment error contract.** The comparison fixture exhibited the factory failure plus an additional reveal-state failure outside its approved expected set. This does not establish that the framework should be changed or that this companion error should automatically be accepted. The source's password-type binding uses reveal state, making a companion error plausible; its exact causal chain was not captured here.

## What the experiment establishes—and does not

The factory-undefined symptom occurred in a real isolated browser/DOM context using the copied framework path and native module request. This adds evidence beyond the earlier mock-DOM reproduction. It is **not complete confirmation of the clone-before-registration mechanism**.

| Required observation | Actual evidence |
| --- | --- |
| Native module request | Gate records **one** request |
| Native import success/failure settlement | **Not established**; the comparison context has no internal import markers |
| Provider assignment / initialization completion | **Not captured** |
| Morph start / clone source awaiting/state/transfer | **Not captured**, because the instrumented run was not reached |
| Expression entry relative to assignment | **Not captured** |
| Expected error | **Observed:** `FACTORY_UNDEFINED` |
| Additional error | **Observed and fail-fast:** `REVEAL_STATE_UNDEFINED` |
| Comparison versus instrumented difference | **NOT RUN / unavailable**, not “no difference” |
| Complete conditional-mechanism result | **BLOCKED / unresolved** |

There is no retained final DOM-state summary or completed-step marker. The runner's `finally` releases a pending module gate during cleanup as well as closing the context. Consequently, `gateRequests: 1` must not be interpreted as proof of planned import release, registration or completion. The retained comparison result alone also cannot locate the precise error phase or prove that the explicit morph trigger was reached. Delivery order is not original expression-evaluation time; no server commit exists in this fixture.

No instrumented/uninstrumented scheduling comparison is possible from this stopped run. The conditional mechanism remains supported by the earlier offline tests, but this browser execution did not satisfy its complete causal evidence requirements. Attempt 05's historical cause remains unconfirmed.

## Cleanup, preservation and evidence

The context cleanup `finally` and outer `await browser.close()` ran before the runner wrote its result. The process then exited. No second invocation, reload, alternate browser launch or repair occurred. Runtime PID-based process verification was not recorded; the result file's placement after awaited browser closure supports normal completion of the prepared cleanup path.

Before execution, recorded a fresh focused **4,188-file** SHA-256 baseline. Post-execution comparison: **4,188/4,188 unchanged**, zero modified or missing files. This includes:

- Previous Gate B2 attempts 01–05, historical identity logs and previous diagnostic/reproduction evidence.
- All **81 inventoried PDF-named artifacts**, including the original 73, and all ten historical diagnostic JSON files.
- Foundation credentials and ownership ledger, protected application/runtime and selected installed/public framework files, pinned adapter files and Batch G artifacts.

The focused baseline is not an exhaustive vendor/node_modules inventory. The historical identity-log append exception and known missing temporary fake-upload metadata remain prior exceptions; no log, cache or evidence file was cleared, restored, truncated or reconstructed. No database row was queried or written, and no fresh database-state preservation assertion is claimed.

The execution directory contains exactly **`result.json`**. Preflight/baseline/preservation helpers and records are additional new files in the existing private reproduction directory: `execution-01-preflight.json`, `execution-01-baseline.json`, `execution-01-preservation.json` and their helper sources. The preservation record inventories new files and hashes separately. This report is the new public deliverable. No screenshot, raw DOM dump, unrestricted trace, PDF or application log was generated by the prepared capture path.

The now-occupied output directory must not be reused. Gate B2 `attempt-06/` remains absent.

## Recommendation and mandatory stop

Recommend **one source-only review of the companion reveal-state error and the isolated runner's phase-evidence coverage**, using this preserved result and installed binding/evaluator source. Determine whether it is an expected consequence of the factory failure and specify any narrowly justified future observation contract before seeking another execution authorization. Do not widen error allowances or rerun this directory automatically.

**STOP.** No repeat execution, OSDS navigation, authentication, Laravel/PostgreSQL access, Attempt 06 preparation, versions 47/48 transition, directory 26 recreation, PDF, migration, B3/B4, commit, push or deployment occurred.
