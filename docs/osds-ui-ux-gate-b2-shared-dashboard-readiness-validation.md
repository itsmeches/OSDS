# Gate B2 — Shared-deadline Dashboard readiness validation

**28 October 2026. BLOCKED — disposable fixture timing invariant failed.** The isolated candidate passed 65 offline checks and the first browser case. The second case completed its acceptance path, but released its resource before five seconds, invalidating the required late-root test. Execution stopped without changing source or retrying. No candidate defect was demonstrated by this stopped run; the required browser matrix is incomplete, so Attempt 11 integration is not justified yet.

## New candidate and exact budget

Applied OSDS Verification Gates and Evidence Preservation. New files are:

- `scripts/uiux-b2-shared-dashboard-readiness.mjs`: isolated shared-deadline verification candidate.
- `scripts/check-uiux-b2-shared-dashboard-readiness.mjs`: pure budget and controlled orchestration checks.
- Private runner/baseline/preservation evidence under `gate-b2-shared-dashboard-validation/`, and this report.

Candidate SHA-256 tested:

`49f91c5697e392a85198c8c3ba423e5927765bccb6e131772ac3cf57c4b6861a`

No protected driver, helper, manifest, framework, router or application source was changed. No protected package was prepared.

The candidate anchors **one monotonic 30,000 ms deadline at function entry**, intended to replace the existing destination-verification start under any future reviewed integration. URL polling and exact Dashboard metadata polling each receive only the remaining allowance; a shared deadline timer also races both stages and the existing failure promise. Budget checks before/after asynchronous observations reject success at or beyond expiry. A failed/expired path marks its continuations stopped, preventing a losing operation from advancing later.

There is **no fresh five-second metadata assertion after that shared budget**. Heading and sidebar assertions retain their existing separate bounds after root readiness succeeds. This reallocates the former URL allowance: a fast URL can leave more than five seconds for metadata. It does not create another independent 30-second wait, and does not claim the former driver already had an overall deadline.

The candidate retains exact origin and `/admin` pathname, existing query/fragment semantics, exact Dashboard identity/count, Login/form/malformed rejection and visible exact level-one heading with hidden-inclusive classification. Sidebar role/name/container/count/href/actionability and returned click checks retain the reviewed contract; document checks additionally surround verification. No credentials, Sign in click, POST, direct personnel navigation or authentication assignment exists in the candidate.

## Document and failure safeguards

After the exact destination first matches, an **opaque document handle** pins that document before root observation. It is compared with the current document before/after metadata collection and at later acceptance boundaries. A same-URL full replacement cannot reuse the old document's result: a mismatching/destroyed context fails with `DOCUMENT_CHANGED`.

An additive main-frame navigation listener remembers an observed departure from `/admin`; returning later does not erase it. Query/fragment changes alone preserve the existing destination semantics. Fatal status and destination are revalidated after awaits, evidence callbacks and required UI checks. Final acceptance readiness follows exact root, heading and sidebar success; callers still own confirmation and must retain fatal handling. Exact root readiness is not reviewer identity proof.

The candidate returns a `dispose()` cleanup function for its handle/listener; test callers invoke it in cleanup. Failure also disposes them. These safeguards are checked offline but **not yet validated across the planned real replacement/redirect cases**, which were not reached. They are not a universal guarantee against future navigation after the last sample or after return.

Closed failures distinguish shared deadline, Login/malformed/duplicate metadata, destination/document change, fatal events, heading/sidebar failure and persistence failure. Fatal and observed destination departures take precedence; failure-record persistence cannot erase an existing verification failure. Persistence and cleanup faults have bounded flags. No raw exception text, DOM, snapshots, URL, credential/session data or opaque handle is persisted.

## Offline checks actually run

```powershell
node scripts/check-uiux-b2-shared-dashboard-readiness.mjs
```

**PASS: 65 checks / 11 controlled orchestration cases.** These cover the fixed monotonic budget and exhaustion, valid flow, Login/malformed/duplicate roots, hidden heading, missing sidebar, fatal event, destination/document change, late result after injected clock expiry and persistence failure. Negative cases keep synthetic confirmation false and perform zero navigation actions. Source checks retain exact selectors, identity requirements and absence of credential/login/authentication assignments.

The dependencies are explicit page/assertion/clock doubles; they do not establish actual Playwright navigation or timeout scheduling. Candidate/runner syntax and scoped whitespace passed. No protected or application code was imported.

## One disposable execution

Executed once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-shared-dashboard-validation/run.mjs run
```

| Fact | Actual result |
| --- | --- |
| Process / aggregate | **exit 1 / BLOCKED** |
| Start / finish | **2026-10-28 10:25:50.978 / 10:25:57.013 UTC** |
| Asia/Manila | **18:25:50.978 / 18:25:57.013** |
| Recorded interval | **6.035 seconds** |
| Node / Playwright | **v24.20.0 / 1.64.1** |
| Actual `browser.version()` | **156.0.8078.4** |
| Contexts / cleanup | **2 entered, 2 closed; browser closed** |
| Unexpected errors / isolation violations / recorded unhandled rejections | **0 / 0 / 0** |
| Retry/source changes after execution | **0 / 0** |

The synthetic same-origin fixture used `http://127.0.0.1:18120` with in-memory GET routes for exact approved documents/resources. Service workers/downloads were blocked; unexpected origins, methods, paths, queries and WebSockets were rejected. No HTTP server, OSDS application, credentials, existing profile/session, Laravel bootstrap or PostgreSQL was used. Local Chromium was present; no dependency was installed/downloaded.

Fresh exclusive evidence directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-shared-dashboard-validation/browser-validation-01/`

Bounded phase checkpoints and each observed case outcome were written before invariant checks, so this fixture failure retained its actual values. No screenshots, console dumps, unrestricted traces or raw fixture/DOM/error data were captured.

### Completed/entered outcomes

| Case | Actual outcome |
| --- | --- |
| Parser-blocked document after matching URL | **PASS.** Held head script allowed URL matching before body parsing; controlled release produced the exact root and complete heading/sidebar acceptance. Recorded root stage **120 ms**, no condition failure. |
| Stylesheet plus following classic inline script; intended root beyond five seconds | **Fixture invariant FAIL.** Candidate passed with no condition failure, but gate release occurred **4,194 ms from fixture-case start** and root stage completed in **4,099 ms from candidate start**. Required greater-than-five-second root timing was not exercised. |

The second case's test-only confirmation is true because the positive Dashboard/heading/sidebar contract actually passed. The failed invariant concerns the fixture's intended late timing, not acceptance of a negative page or a protected authentication event. Candidate navigation/business-action counts remain zero.

### Why the fixture failed

The runner held the stylesheet until a comparison `expect.poll(..., {timeout:5000})` rejected, treating rejection as a minimum five-second clock milestone. That assumption is wrong. Installed `pollAgainstDeadline` stops when the next poll interval would reach/exceed the deadline; it can report timed out before five seconds of wall time have elapsed. The retained `formerFiveSecondWindowFailed=true` therefore does **not** prove a root was held beyond five seconds.

Installed source at `playwright-core/lib/coreBundle.js:4754` checks whether `deadline <= monotonicTime() + interval` and breaks before sleeping for that interval. This directly supports the observed early release. The candidate remained in metadata readiness at release and subsequently passed, but did so before the required late-root threshold. The runner correctly stopped rather than adjusting the threshold or rerunning.

**Classification: confirmed fixture timing defect; no demonstrated candidate defect.** Neither real shared-deadline exhaustion nor real wrong-document/fatal precedence can be certified from these two cases.

## Required matrix coverage and remaining work

| Requested behavior | Status |
| --- | --- |
| URL before body parsing | **PASS** in parser gate case |
| Stylesheet followed by parser-blocking inline script | Fixture path exercised; positive acceptance passed, intended late-duration condition **not met** |
| Exact root after former five-second window, before shared deadline | **NOT VALIDATED** |
| Root already available | **NOT RUN** as dedicated case |
| Footer/deferred resource pending with root ready | **NOT RUN** |
| No root through deadline / late root after exhaustion | **NOT RUN** |
| Login, malformed metadata, duplicates, wrong identity | **NOT RUN** in browser; offline coverage is not a substitute |
| Destination away, same-document entry, full replacement, brief root then redirect, away-and-back | **NOT RUN** |
| Fatal page/request events | **NOT RUN** |
| Missing/invisible heading; sidebar failure | **NOT RUN** |
| Persistence failure and original-failure preservation | **NOT RUN** in browser |

Twenty-two fixture cases were prepared; only the first two were entered. There were no automatic retries. The remaining cases must not be reported as passing based on source review or mocks.

## Preservation, decision and one next action

Fresh focused baseline: **5,087/5,087 files unchanged**, zero modified/missing. Attempts 01–10 and session/evidence bytes, credentials/ledger, identity history, existing protected drivers/helpers/manifests, application/framework/public sources, protected logs/caches/artifacts, all 81 previously inventoried PDF-named files, ten historical diagnostic JSON files and Batch G remain preserved. No file/log/cache/evidence was restored, truncated or cleared. Prior append/missing temporary-metadata exceptions remain unchanged. This is a focused inventory, not a whole vendor/node_modules or fresh database-state assertion.

**BLOCKED for Attempt 11 integration.** The shared-budget candidate has not completed its required real-browser matrix; the key beyond-five-seconds condition is unproven. Attempt 10's historical bottleneck and real authentication remain unresolved.

**One recommended next action:** separately authorize a fixture-only timing correction and fresh full-matrix validation. Release the late-root gate only after a measured monotonic observation establishes that the exact root remains absent beyond five seconds, within the existing shared deadline—not merely after an assertion rejects. Preserve this run; keep the candidate unchanged unless a new demonstrated defect requires review. No correction or rerun was performed after this failure.

**STOP.** No protected browser/authentication, PostgreSQL/Laravel bootstrap, Attempt 10 reuse, Attempt 11 preparation/execution, application/framework/router change, COR/ACSA lifecycle, migration/seeding/repair, B3/B4, commit, push or deployment occurred.
