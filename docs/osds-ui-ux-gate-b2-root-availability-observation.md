# Gate B2 — Minimal root-availability observation

**28 October 2026. PASS: source implementation and focused offline validation.** A separate diagnostic-only helper distinguishes absent roots, missing snapshot attributes and other well-formed component identities without changing acceptance. It is ready for integration review; browser collection remains unvalidated. No protected driver/package or Attempt 10 was prepared or executed.

## New files and source identity

Applied OSDS Verification Gates and Evidence Preservation. Reviewed the [Attempt 09 assessment](osds-ui-ux-gate-b2-attempt-09-dashboard-root-cause-assessment.md) and existing exact-identity collector; no completed framework investigation was repeated.

- `scripts/uiux-b2-root-availability.mjs`: self-contained read-only browser collector and one-shot observation adapter.
- `scripts/check-uiux-b2-root-availability.mjs`: focused synthetic node/adapter checks.
- This report and private filesystem baseline/preservation helper/results under `gate-b2-root-availability-observation/`.

Helper SHA-256:

`68cc4348d7666fbe6c88b328b8972c84c0c5255c4c273c684f4fa55e3215fa4c`

No existing helper, driver, pins, policy, application or framework source changed. No existing protected workflow imports the new helper.

## Closed observation schema

Every record is newly constructed with exactly these fields:

| Field | Allowed value / meaning |
| --- | --- |
| `idRoots` | Count of document `[wire\:id]` elements, capped at 2 |
| `snapshotRoots` | Count of document `[wire\:snapshot]` elements, capped at 2 |
| `idRootsWithoutSnapshot` | ID roots lacking that snapshot attribute, capped at 2 |
| `dashboardSnapshots` | Exact expected Dashboard memo-name matches, capped at 2 |
| `loginSnapshots` | Exact expected Login memo-name matches, capped at 2 |
| `otherSnapshots` | Other string memo names, capped at 2; names are never exported |
| `malformed` | Boolean: invalid snapshot JSON or missing/non-string memo name |
| `readyState` | `loading`, `interactive`, `complete`, `unavailable` |
| `destinationMatch` | Exact `http://127.0.0.1:8044` origin and `/admin` pathname; query/fragment semantics unchanged |
| `fatalEvent` | Existing caller fatal status, authoritative before/after collection |
| `observationAvailable` | Whether the document sample was obtained successfully |
| `phase` | `AFTER_ADMIN_DESTINATION` or `DASHBOARD_FAILURE` |

Two means **multiple**, not an exact total. Individually capped counts must not be added to infer a total. Snapshot validity here concerns JSON/memo-name shape, consistent with the existing collector; it is not server checksum validation or proof of component authorization.

`inspectRootAvailability()` queries the current document only. It checks attribute presence without reading any `wire:id` value, and reads/parses snapshots internally solely to classify exact known names or OTHER/malformed. The exact identities remain `App\Filament\Pages\Dashboard` and `Filament\Auth\Pages\Login`. No arbitrary identifier, attribute, snapshot, DOM content or exception is returned.

## Two fixed boundaries and failure behavior

A future reviewed caller would create **one** `createRootAvailabilityObserver({page, hasFailed})` for its workflow, then invoke it at most once for each fixed phase:

1. Immediately after the existing exact destination assertion succeeds.
2. At the existing dashboard-verification failure boundary, if reached.

The adapter performs at most one `page.evaluate` per phase. It refuses unknown/repeated phases, including retry after an unavailable observation. It creates no polling, timeout, sleep, browser listener or navigation. It does not schedule either boundary itself; those call sites are not implemented here.

If a fatal event is already set, collection is skipped and returns unavailable/fatal true. If the flag changes during evaluation, the returned observation retains `fatalEvent=true`. Existing fail-fast handling must remain authoritative; this diagnostic record never converts a failure into acceptance. It does not introduce or alter the application's fatal-event policy.

Evaluation failure or invalid/extra output fields produce a fixed unavailable record without exposing error text. Partial samples are discarded. Such a record has default zero/false counts, but `observationAvailable=false`; those defaults are **not observed absence**. An entirely unexecuted phase has no record and must likewise remain unknown in future reporting. Unknown readiness maps to `unavailable`, rather than exporting an arbitrary value.

The helper neither receives nor assigns authentication state. Expected identity, Dashboard/heading checks, sidebar verification, request permissions and all time bounds remain unchanged. The adapter has no filesystem/network API and performs no persistence; a future caller must persist only this closed shape.

## Focused offline results

Executed:

```powershell
node scripts/check-uiux-b2-root-availability.mjs
```

**PASS: 68 checks**, covering 11 synthetic root arrangements plus readiness, destination, fatal, unavailable and representation controls:

| Coverage | Actual offline result |
| --- | --- |
| No roots | Observed zero ID/snapshot counts, availability true |
| ID-only roots | Snapshot count zero; missing-snapshot count positive |
| Dashboard / Login / other | Exact independent counts; arbitrary identity reduced to OTHER |
| Mixed Dashboard/other and snapshot-only root | Correct classification without requiring `wire:id` on every selected snapshot element |
| Malformed JSON, missing memo name, null snapshot | Malformed boolean true; no raw data/error export |
| Multiple roots | All count fields stay within 0–2 |
| Loading / interactive / complete / unknown readiness | Closed enum only |
| Wrong path/trailing slash/origin | Destination false, with no acceptance action |
| Fatal before collection | No evaluation; fatal true and unavailable |
| Fatal during collection | Measured sample retained with fatal true |
| Evaluation failure / extra output field | Unavailable record; no private detail exported |
| Repeated/invalid phase | Fixed rejection; no automatic second sample |
| Data minimization | Poison value getter not read; arbitrary names, synthetic private snapshot data and error text absent from output; no ID attribute reads |
| Authentication | No helper authentication assignment; negative fixture observations leave the frozen false authentication state untouched |

The real collector runs against in-memory node doubles with explicit selector checks; the adapter is tested with controlled evaluation/fatal dependencies. No real DOM engine, browser, Playwright poll loop or application runs. These results establish the schema/collector logic, not protected rendering behavior. Node syntax and scoped whitespace checks passed. No broad regression framework or unrelated suite was added.

## Interpretation limits

- Zero ID and snapshot roots distinguishes no selected Livewire roots in this document from positive OTHER counts.
- Positive ID roots lacking snapshots demonstrates unavailable attributes, not that those components will mount later.
- Snapshot roots with positive OTHER and zero Dashboard counts demonstrate other memo identities, without revealing which arbitrary names they use.
- `loading` plus unavailable/missing roots is consistent with incomplete parsing, but not proof of eventual Dashboard appearance. Two fixed samples may show a change; they cannot prove future success or isolate every rendering/session cause.
- The document query does not enter other frames or shadow trees. Counts/ready state do not establish real authentication or reviewer identity.
- Observation failure remains unknown, never permission to bypass the existing verification contract.

## Preservation and decision

Fresh focused baseline: **4,999/4,999 files unchanged**, zero modified/missing. Attempts 01–09 and their session/evidence bytes, original credentials/ledger, identity records, protected drivers/helpers/pins, application/framework/public sources, historical logs/caches/artifacts, all 81 previously inventoried PDF-named files, ten historical diagnostic JSON files and Batch G remain preserved. Credentials/session files were hashed only. No evidence/log/cache was cleared, restored or reconstructed; prior historical append/missing-metadata exceptions remain unchanged. This is not a whole vendor/node_modules or database-state assertion.

**Ready as an offline-tested candidate for future integration review, not protected execution readiness.** No acceptance condition was changed and no protected integration was made.

**One smallest recommended next action:** separately authorize a focused disposable-DOM validation of this exact helper and its two-phase adapter, confirming actual attribute selectors, count capping, availability and fatal handling without OSDS access. No runner or protected attempt for that action was prepared here.

**STOP.** No protected browser/authentication, PostgreSQL/Laravel bootstrap, Attempt 09 reuse, Attempt 10 preparation/execution, COR/ACSA lifecycle, migration/seeding/repair, PDF/directory recreation, B3/B4, commit, push or deployment occurred.
