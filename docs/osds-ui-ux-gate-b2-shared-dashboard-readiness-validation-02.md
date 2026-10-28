# Gate B2 — Shared-deadline readiness validation 02

**28 October 2026. PASS.** One fresh disposable execution completed the unchanged **22/22-case matrix**. The corrected fixture measured exact-root absence beyond five seconds from the candidate's actual monotonic start, then verified readiness before the shared deadline. All negative cases retained false test-only confirmation and zero candidate navigation/business actions. The candidate is suitable for separately authorized Attempt 11 integration review; no protected package or execution was prepared.

## Fixture-only correction and candidate identity

Applied OSDS Verification Gates and Evidence Preservation. The candidate remained unchanged at the required SHA-256:

`49f91c5697e392a85198c8c3ba423e5927765bccb6e131772ac3cf57c4b6861a`

No pre-existing file was modified. New private files under `gate-b2-shared-dashboard-validation/` are `prepare02.cjs`, `run02.mjs`, `preserve02.cjs`, `baseline02.json`, `preservation02.json` and fresh `browser-validation-02/` evidence. This report is new. Original `run.mjs`, failed `browser-validation-01/` and its report remain unchanged.

The new runner preserves the original 22 specifications and acceptance expectations. Only fixture timing/clock wiring and evidence destination changed:

1. A test-only clock wrapper returns the actual `performance.now()` value and records its **first invocation**. The candidate uses that exact same value to establish its existing deadline; fixture-case setup time is excluded.
2. The CSS gate stays closed while a bounded fixture poll checks elapsed monotonic time. It samples the actual exact Dashboard collector only after elapsed time is strictly greater than 5,000 ms.
3. Release additionally requires Dashboard count zero, the candidate still in metadata readiness, elapsed time below 10,000 ms and more than 20,000 ms of shared allowance remaining.
4. A bounded gate checkpoint is written before timing invariants are asserted. Gate release occurs only after those conditions pass. Root completion must then be strictly after five seconds and before 25,000 ms, preserving at least five seconds of safety margin.

No assertion rejection is treated as a clock. There is no fixed sleep, candidate timeout extension, root injection for the positive late-root case, retry or additional protected action. The bounded fixture poll synchronizes the expressly required time/absence condition; it does not add polling to the candidate.

## Measured monotonic timing

| Late-root measurement | Actual value from candidate start |
| --- | ---: |
| Before exact-root absence sample | **5,909.9668 ms** |
| Completed gate-condition sample | **5,912.7133 ms** |
| Exact Dashboard count before release | **0** |
| Remaining shared budget at sample | **24,087.2867 ms** |
| CSS gate release | **5,914.0778 ms** |
| Successful root-stage completion | **6,020 ms** |
| Remaining budget at root completion | **23,980 ms** |

The stylesheet remained held through collection/checkpointing; the following classic inline head script prevented parsing the fixture body/root until release. Thus the exact root was not made available early to obtain PASS. This establishes the required beyond-five-second case while using the candidate's original shared 30,000 ms allowance.

The shared deadline remains a **maximum**, not a minimum wait. Missing/wrong-root cases stopped at 29,423/29,391 ms with the expected deadline code: Playwright can finish a poll before the hard cap when its next sample interval would exceed the deadline. These results are not presented as exactly 30,000 ms of wall-clock waiting. No fresh metadata budget followed that failure.

## One fresh execution and isolation

Executed exactly once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-shared-dashboard-validation/run02.mjs run
```

| Fact | Observed |
| --- | --- |
| Exit / aggregate | **0 / PASS** |
| Start / finish | **2026-10-28 10:40:23.766 / 10:41:47.500 UTC** |
| Asia/Manila | **18:40:23.766 / 18:41:47.500** |
| Recorded interval | **83.734 seconds** |
| Node / installed Playwright | **v24.20.0 / 1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Contexts / cleanup | **22 fresh nonpersistent contexts; 22 closed; browser closed** |
| Unexpected browser errors / isolation violations | **0 / 0** |
| Recorded unhandled rejections | **0** |
| Retries / candidate modifications | **0 / 0** |

The same closed synthetic-origin/resource policy was retained at `http://127.0.0.1:18120`, with in-memory route fulfillment and no HTTP server. Only exact approved same-origin GET document/resource paths and fixed fixture queries were admitted. Unexpected origins/methods/paths/queries/WebSockets were rejected. Service workers/downloads were blocked. No OSDS application, real credentials, existing profiles/sessions, PostgreSQL or Laravel bootstrap was used. Local Chromium was present; no install/download occurred.

Fresh exclusive evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-shared-dashboard-validation/browser-validation-02/`

It retains bounded phase checkpoints, pre-invariant case outcomes, the measured late-root gate checkpoint and final aggregate. Final aggregate statuses are PASS; observation checkpoints retain their original OBSERVED status. No raw DOM, snapshot contents, arbitrary component identities, credentials/cookies/sessions, exception text, screenshots, console dumps or unrestricted traces were persisted.

## Complete browser matrix

Every actual code matched its expected code. Root elapsed values below cover only the shared destination/root stage, not subsequent heading/sidebar assertion time.

| Case | Actual code | Root elapsed ms | Test-only confirmation |
| --- | --- | ---: | --- |
| URL before body parsing, held head script | `NONE` | 74 | true |
| CSS plus classic inline script, late exact root | `NONE` | 6,020 | true |
| Root already available | `NONE` | 48 | true |
| Footer/deferred resource still pending | `NONE` | 13 | true |
| Missing root through poll budget; later insertion | `SHARED_DEADLINE_EXHAUSTED` | 29,423 | false |
| Login instead of Dashboard | `UNEXPECTED_LOGIN_COMPONENT` | 67 | false |
| Malformed snapshot | `MALFORMED_COMPONENT_METADATA` | 44 | false |
| Duplicate Dashboard roots | `DASHBOARD_COMPONENT_DUPLICATED` | 43 | false |
| Wrong component identity | `SHARED_DEADLINE_EXHAUSTED` | 29,391 | false |
| Destination away during readiness | `DESTINATION_CHANGED` | 44 | false |
| Same-document entry into `/admin` | `NONE` | 131 | true |
| Full-document replacement at matching pathname | `DOCUMENT_CHANGED` | 54 | false |
| Root ready briefly, then redirect | `DESTINATION_CHANGED` | 59 | false |
| Away-and-back history navigation | `DESTINATION_CHANGED` | 54 | false |
| Deliberate page fatal event | `EXISTING_FATAL_EVENT` | 63 | false |
| Deliberate request failure | `EXISTING_FATAL_EVENT` | 68 | false |
| Missing exact h1 | `HEADING_MISSING` | 44 | false |
| Hidden exact h1 | `HEADING_INVISIBLE` | 52 | false |
| Sidebar acceptance failure | `SIDEBAR_CONDITION_FAILED` | 44 | false |
| Persistence failure after root readiness | `EVIDENCE_PERSISTENCE_FAILED` | 41 | false |
| Failure-checkpoint persistence failure | `UNEXPECTED_LOGIN_COMPONENT` | 39 | false |
| Query/fragment semantics at exact origin/path | `NONE` | 42 | true |

All 16 negative cases retained false confirmation. All 22 cases recorded zero candidate navigation/business actions: the runner validates the returned navigation contract but does not invoke its real click. Fixture-owned history/document changes are explicit synthetic controls, not candidate or protected navigation actions. Positive confirmation flags are synthetic caller state after required root/heading/sidebar success, not authentication or reviewer identity proof.

## Precedence and late-result findings

- Pinning an opaque document prevented borrowing metadata across full replacement, even with the expected pathname. The transient root and away-and-back cases did not confirm acceptance.
- Same-document entry and ignored query/fragment changes passed when the current exact destination, document and required UI were valid. No alternate identity was accepted.
- Page/request fatal controls retained `EXISTING_FATAL_EVENT`. They were induced negative inputs, not expanded protected error allowances.
- A persistence fault blocked acceptance. When the original failure was unexpected Login, a subsequent checkpoint fault preserved that original code and reported the bounded persistence flag. Faults were injected at the test persistence callback after safe checkpoint capture; these are not claims about a real permission/disk failure.
- In the no-root deadline case, the fixture inserted a root **after the candidate had already failed**, without invoking it again. Confirmation stayed false. Context cleanup and a microtask drain recorded no late confirmation or unhandled rejection. This tests post-failure suppression, not a guarantee about every possible future schedule.
- The footer/deferred gate remained pending while root/heading/sidebar acceptance completed. Exact-root readiness did not require unrelated full-document `load` or network idle.

The candidate's document/fatal rechecks protect the tested observation boundaries; they cannot predict navigation occurring after the final check/return. Future integration must retain caller fatal handling and `dispose()` in cleanup. Exact root readiness never independently proves reviewer identity.

## Regression and preservation

Required unchanged regression command:

```powershell
node scripts/check-uiux-b2-shared-dashboard-readiness.mjs
```

**PASS: all 65 existing checks / 11 controlled orchestration cases.** Candidate hash matched the required value before and after the run; new fixture-runner syntax and scoped whitespace passed. No broader suite or protected entry point ran.

Fresh focused preservation: **5,103/5,103 baseline files unchanged**, zero modified/missing. The candidate, offline tests, original failed runner/result/report, attempts 01–10 and session/evidence bytes, original credentials/ledger, identity history, existing protected drivers/helpers/manifests, application/framework/router/public sources, protected logs/caches/artifacts, all 81 previously inventoried PDF-named files, ten historical diagnostic JSON files and Batch G remain unchanged.

Only new authorized fixture/preparation/evidence/report files were added. No log/cache/evidence was cleared, restored or truncated. Prior append/missing temporary-metadata exceptions remain unchanged. This is a focused inventory, not a whole vendor/node_modules or fresh database-state assertion.

## Decision and one next action

**PASS: suitable for separately authorized Attempt 11 integration review.** The fixed candidate completed the full matrix, including a genuinely late root, shared-budget failures, wrong-document safeguards and all remaining acceptance conditions. This validates verification behavior in synthetic documents, not the cause of Attempt 10's loading delay or real reviewer authentication.

**One smallest recommended next action:** separately authorize source-only Attempt 11 package integration and its guarded read-only preparation, preserving the shared deadline, root-observation boundaries, every acceptance predicate, one-shot submission and cleanup. No such package or protected execution was prepared here.

**STOP.** No protected browser/authentication, PostgreSQL/Laravel bootstrap, Attempt 10 reuse, Attempt 11 preparation/execution, application/framework change, COR/ACSA lifecycle, migration/seeding/repair, B3/B4, commit, push or deployment occurred.
