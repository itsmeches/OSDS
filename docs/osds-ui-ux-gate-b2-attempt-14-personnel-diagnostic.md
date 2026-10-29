# Gate B2 — Attempt 14 personnel diagnostic

**29 October 2026. Diagnostic implementation/offline validation PASS. Historical failed boundary remains UNRESOLVED.** Added an isolated, one-shot closed diagnostic preserving the original personnel predicates. Attempt 14's pinned driver and evidence remain unchanged. No browser, authentication, database or successor package ran.

Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation.

## Existing sequence and attribution limit

After the accepted sidebar's single navigation click, [Attempt 14's driver](../scripts/verify-uiux-b2-attempt-14.mjs:106) performs:

| Order | Exact existing verification |
| --- | --- |
| 1 — line 107 | `expect(page).toHaveURL(base+'/admin/case-demonstration-documents')` |
| 2 — lines 108–109 | Collect document `[wire\:snapshot]` attributes and call the existing `componentSnapshots()` validator |
| 3 — lines 110–112 | Exact heading name **Personnel #47 · version 1 · draft**, count exactly 1 |
| 4 — same loop | Exact heading name **Personnel #48 · version 1 · draft**, count exactly 1 |

The [component validator](../scripts/uiux-b2-reviewer-policy.mjs:13) parses every selected snapshot and requires exactly one `App\Filament\Pages\CaseDemonstrationDocuments` memo identity. Malformed JSON, failed collection and zero/duplicate expected components can fail this boundary. Heading verification originally requires count, not a separate visibility or level assertion; the diagnostic retains that exact requirement.

The driver writes only a grouped personnel-success step after all checks. Its outer catch sanitizes the thrown exception and records a general workflow-failure event. There is no per-check entry/completion marker. Attempt 14 retained the personnel pathname, authentication true, a restricted `Error`, no first-fatal record and no grouped success. These facts **cannot uniquely identify URL, component, COR or ACSA failure**. Current pathname is not proof that the exact URL assertion completed. No missing snapshot, heading, exception or eventual document content was reconstructed.

## Minimal diagnostic candidate

New [helper](../scripts/uiux-b2-personnel-diagnostic.mjs) exports `createPersonnelDiagnostic({page, expect, base, onRecord})`; it performs the same four checks in the same order, calling the unchanged component validator. It does not navigate, click, authenticate or assign authentication state. It adds no DOM query, poll, timeout, sleep or policy allowance beyond the existing verification operations.

Each executed boundary emits `ENTERED`, then `PASS` or `FAIL`. The record has exactly:

| Field | Closed values |
| --- | --- |
| `boundary` | `PERSONNEL_URL`, `PERSONNEL_COMPONENT`, `COR_47_HEADING`, `ACSA_48_HEADING` |
| `state` | `ENTERED`, `PASS`, `FAIL` |
| `passed` | Boolean, true only for `PASS`; false on entry does not mean failed |

The single-use instance retains at most **eight immutable records**, exposes copies of the journal and rejects another `run()`. A thrown verification error produces a failure marker and is rethrown unchanged; later checks do not run. Thus a future caller can distinguish the last entered boundary from one that completed. The component boundary deliberately groups attribute collection, parsing and exact-identity validation; it does not expose raw snapshots or distinguish their internal failures.

`onRecord` is an optional **synchronous** closed-record callback. There is no filesystem API in the helper. A callback exception sets a bounded `persistenceFailed` flag and blocks further progression. If failure reporting itself throws, the original verification exception survives and the in-memory failure record remains. An entry-write fault stops before that check executes; a pass-write fault may leave a measured PASS in memory while overall execution is blocked. A future caller must inspect the flag and retain existing fatal handling/cleanup. Async persistence, crash survival and a future outer failure race are not certified here.

No arbitrary URL, ID, component name, heading text, count payload, DOM, snapshot, credential/session material or exception text is placed in records. The fixed component and heading predicates remain internal. This helper has **not been integrated into any protected driver**; changing Attempt 14 would invalidate its historical pins. No Attempt 15 package was prepared.

| New source | SHA-256 |
| --- | --- |
| `scripts/uiux-b2-personnel-diagnostic.mjs` | `f8cebc599dfc53a93d76246d31813e4cdb92b5decf7eee15e3fe5d4f1307bd82` |
| `scripts/check-uiux-b2-personnel-diagnostic.mjs` | `5760452c843accb1ff5dc3629f37142778913827b4debaa9b5224b45e5e43b68` |

## Offline validation

Executed:

```powershell
node scripts/check-uiux-b2-personnel-diagnostic.mjs
```

**PASS: 171 assertions**, using synthetic page/assertion/persistence dependencies and the actual diagnostic/helper validator. Helper syntax passed.

| Case | Result |
| --- | --- |
| All four boundaries pass | Eight records in exact order; original selectors, exact URL/heading names and count 1 checked |
| URL fails | URL ENTERED/FAIL; no component or heading operation |
| Component absent | URL PASS, component FAIL; no heading operation |
| COR heading fails | Earlier checks PASS, COR FAIL; ACSA not entered |
| ACSA heading fails | Earlier checks PASS, ACSA FAIL |
| Malformed / duplicate personnel snapshots | Component FAIL, no heading operation |
| Failure-record callback throws | Original verification error preserved; persistence flag true |
| Entry/pass callback throws | Blocks progression at the corresponding recording boundary |
| Repeated invocation / output minimization | Reuse rejected; journal bounded/frozen; exact keys/enums/booleans only; synthetic private data/error absent |

Nine negative cases cover four check failures, two metadata variants and three callback faults. Earlier boundaries passing in the negative cases plus the full PASS case establish both outcomes for each sequential check. These tests do not execute Playwright, a DOM engine or application code, and do not identify Attempt 14's historical failed predicate.

## Preservation and decision

Fresh focused comparison: **8,303/8,303 baseline entries unchanged**, zero changed/missing/unavailable. Attempts 01–14, preparation/disposable/execution/session bytes, original credentials/identities, application/avatar/framework/public sources, policies, historical logs/caches and artifact inventories remain preserved. Credentials/session bytes were hashed only. All **30 Attempt 14 pins** match; manifest remains `f5734bb9bcf295ad4d93f0152df34e75a92e5a6547068211431ce9591a2864d8`.

Laravel log remains **17,218,391 bytes**, SHA-256 `a71c2bd7f7d4fb8180decea1d7bbc13cf6b943b4c9a8a5fc428d8a0b3182e44b`; **new bytes: 0**. The known 370-byte exception remains **APPLICATION ERROR INDICATED — PHP_EXECUTION_TIME_LIMIT — ATTRIBUTION NARROWED**. No historical file was restored, truncated, cleared or repinned. This is a focused inventory with inherited path aliases, not whole-cluster or whole vendor/node_modules verification.

New artifacts are the two helper/check files, this report and private baseline/comparison records under `gate-b2-attempt-14-personnel-diagnostic/`. HEAD remains `cf9d9618758b343b079c222168d1b3fa6002fb65`; no commit/push occurred.

**A narrowly scoped future protected diagnostic execution is justified in principle to obtain missing boundary attribution, but is not ready or authorized here.** It first needs separate reviewed integration/pinning, guarded preparation and execution authorization. There is no evidence supporting an application repair or relaxed acceptance predicate.

**One recommended next action:** separately authorize review and integration of this unchanged diagnostic into a new protected package, preserving all existing requirements and ensuring its closed journal is settled/persisted on failure. Do not rerun Attempt 14 or infer its missing component/heading from the restricted exception.

**STOP.** No protected browser/login, database operation, Attempt 15 preparation/execution, application business-logic change, lifecycle action, migration/seeding/repair, policy expansion, service operation, B3/B4, commit, push or deployment occurred.
