# Gate B2 — Attempt 07 reviewer login/navigation execution

**28 October 2026. BLOCKED.** The prepared launcher executed exactly once, without source changes. Both login-readiness checkpoints passed by pinned control flow; the form was submitted once and the exact `/admin` destination assertion passed. The required authorized-link visibility assertion failed. Authentication acceptance remains **unconfirmed**; personnel-page verification was **NOT RUN**. Read-only preflight/postflight passed, with versions 47/48 still draft/lock 1. No lifecycle operation occurred.

## Execution and source integrity

Applied OSDS Verification Gates, Livewire Browser Diagnostics and Evidence Preservation. Reviewed the [Attempt 07 preparation](osds-ui-ux-gate-b2-attempt-07-preparation.md) and prepared launcher, bootstrap and driver before execution.

Executed once using portable Node v24.20.0:

```powershell
node scripts/Run-UiUxB2Attempt07.mjs run
```

| Boundary | Actual result |
| --- | --- |
| Launcher / browser exit | **1 / 1** |
| Read-only postflight exit | **0** |
| Atomic reservation | **2026-10-28 08:04:26.807 UTC / 16:04:26.807 Asia/Manila** |
| Launcher result file timestamp | **08:04:56.488 UTC / 16:04:56.488 Asia/Manila** |
| Reservation-to-result interval | **29.681 seconds**; not an exact invocation-duration measurement |
| Source/framework pins | **19/19 matched**, before invocation and at prepared entry points |
| Manifest SHA-256 | `9830d6c4cc103661c798bc78ca9aa56ce6a22733505da10550e4c1cbbc3ac572`, matched |
| Historical identity-log hash | Matched before and after; launcher flag true |
| Both disposable-validation result hashes | Matched manifest provenance |
| Attempt 07 output | Absent before invocation; atomically reserved by launcher |
| Loopback port 8044 | Free before invocation; launcher checked again; connection refused after cleanup |

Local PHP, Node and Chrome executables were present; local Playwright was **1.64.1**. The driver used its prepared fresh Chrome-channel context. Installed Chrome executable metadata was **156.0.8078.48**; the unchanged driver does not record `browser.version()` or browser PID, so this is not an independently queried running-browser version. No dependency was installed/downloaded or alternate executable selected.

The pinned framework contract remains Laravel 13.35.0, Filament 5.10.1, Livewire 4.4.7 and Alpine 3.17.4. No launcher, driver, manifest, guard, policy, application source or framework asset was modified to obtain a result.

## Database preflight and postflight

The prepared launcher performed its fresh guarded preflight before starting the application server/browser. Preflight and postflight both returned **PASS**.

All **28/28 identity observations passed**, across **26 distinct backend PIDs**, from **08:04:27 UTC** through **08:04:56 UTC**. Every retained observation identified **osds_ui_browser_test / 127.0.0.1:55432**, under the prepared **pgsql** guard. No fallback or forbidden database was used. CLI inspections enforced read-only defaults before bootstrap, explicit **REPEATABLE READ READ ONLY**, read-only on before/after inspection, and rollback. Browser authentication had its authorized normal session effects; it was not represented as a read-only operation.

| Protected state | Fresh findings |
| --- | --- |
| COR 47 / module 1 | **draft / lock 1**; original history 237, `version_created`, actor 1191 |
| ACSA 48 / module 2 | **draft / lock 1**; original history 238, `version_created`, actor 1191 |
| Original history/audit and immutable bindings | Prepared integrity criteria passed; no review/publication history added |
| Directory 26 | Exact protected comparison passed, latest revision unchanged |
| Reviewer 1192 | Existing active synthetic reviewer and exact foundation permission/ownership manifest passed |
| Foundation unions | 18 accounts, 154 permission pivots, 2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments, 20 reviewer assignments |
| Fixed roots / preview / protected rows | B0 and protected-row comparisons passed; COR 198/591/54/43, ACSA 199/592/55/45 and preview 128 retained |
| Business counts and unrelated protected records | Prepared comparisons passed |

No missing record was recreated, and no review, approval, rejection, publication or directory transition was performed.

## Actual login and navigation observations

The safe recorder retained **33 events, zero dropped**. All retained HTTP responses were 200. No page-error, explicit request-policy violation or request-failed event was retained.

Times are Node observer elapsed milliseconds, not original browser expression times or server commit times.

| Sequence | Elapsed ms | Observation |
| --- | ---: | --- |
| 17 | 13,639.197 | Exact Login component inspection completed |
| 19–22 | 14,747.596–14,776.743 | One email fill and one password fill completed after first readiness checkpoint |
| 23–24 | 14,780.023–14,835.883 | Single Sign in click completed after second readiness checkpoint |
| 25 | 14,836.519 | Destination verification started |
| 26 | 14,851.260 | One permitted login POST forwarded |
| 27 | 18,094.138 | Login POST response 200 |
| 28 | 20,089.116 | `/admin` response 200 |
| 29 | 20,732.466 | Exact origin/path destination-state assertion completed |
| 30 | 20,732.498 | Authorized-link verification started |
| 33 | 25,741.644 | Workflow stopped at authorized UI condition; safe path `/admin` |

Both readiness checks passed by the unchanged driver's control flow: credential-entry markers follow the first successful return, and the single click follows the second. These are not separate serialized readiness snapshots. Neither historical factory nor reveal-state ReferenceError was observed.

The destination polling correction passed its real protected destination condition. The next assertion required:

```javascript
expect(page.getByRole('link', {
    name: 'Case-linked demonstration PDFs', exact: true,
})).toBeVisible()
```

The recorded closed code is **`AUTHORIZED_UI_CONDITION_FAILED`**. The retained failure is Error / RESTRICTED, phase `login-verifying`, safe pathname `/admin`, form displayed true, login POST attempted true, authentication confirmed false. Failure followed UI-check start by **5,009.146 ms**, consistent with the existing assertion bound; restricted diagnostics do not establish the original exception subtype or explain why visibility failed.

No auth-link completion, authentication-confirmed marker, successful login step or personnel-page step exists. The authorized link was not followed, and personnel component/headings were not inspected. Reaching `/admin` or receiving HTTP 200 does not independently prove authentication. Missing, hidden, delayed, differently named or inaccessible UI cannot be distinguished from this evidence; none is a confirmed cause. No selector, timeout, policy or source correction was attempted.

## Cleanup and preservation

The prepared browser/context cleanup and launcher-owned server shutdown completed before postflight/result writing. A subsequent TCP-only probe received connection refused on 8044. No unrelated process was terminated. No second launcher invocation, reload, authentication resubmission or extra database inspection occurred.

Attempt evidence is permanently reserved at:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-07/`

There are **11 root evidence/log files**, plus private attempt-local session storage. Credentials, cookies and session contents were not exported. No screenshot, unrestricted trace or PDF was generated by the prepared driver. Private file inventory and execution-preservation helpers/results use `run07-*` names under `gate-b2-attempt-07-preparation/`.

Fresh focused baseline: **4,485 files**. Final comparison: **4,485/4,485 unchanged**, zero changed/missing. Scope includes previous attempts/evidence, historical identity records, original credentials/ledger, current package and validated guard/policy, application/runtime and selected framework/public assets, existing logs/compiled views and Batch G artifacts. All **81 inventoried PDF-named files**, including the original 73, and ten historical diagnostic JSON files remain preserved. HEAD remained `0a367df01aabab3e313a7d8b7c84c36d336c5f85`; the pre-existing dirty tree was not repaired or committed.

Historical identity-log SHA-256 remains:

`5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B`

Current observations went only to Attempt 07's own identity file. Prior identity-log append and missing temporary fake-upload metadata exceptions remain historical exceptions; nothing was truncated, restored or reconstructed. This is a focused inventory, not a complete vendor/node_modules or filesystem claim. New artifacts are Attempt 07 evidence, task-private filesystem records/helpers and this report.

## Decision and stop

**BLOCKED for reviewer lifecycle acceptance.** Destination verification advanced beyond Attempt 06, but the mandatory authorized UI condition failed and authentication acceptance remains unconfirmed. Versions 47/48 and directory 26 are preserved.

Recommend a separately authorized, focused source-only review of the exact link's navigation label, visibility and reviewer authorization contracts against the retained evidence. Do not change the assertion or rerun authentication automatically. Attempt 07 must never be reused.

**STOP.** No retry/fallback attempt, reviewer lifecycle operation, directory recreation, migration/seeding/repair, applicant/PDF workflow, B3/B4, commit, push or deployment occurred. Even a future login/navigation PASS would require separate authorization for lifecycle work.
