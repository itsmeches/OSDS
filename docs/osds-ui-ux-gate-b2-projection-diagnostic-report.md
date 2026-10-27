# Gate B2 — Read-only projection mismatch diagnosis

**27 October 2026. Diagnostic inspection PASS; B2 preparation remains BLOCKED pending review.** One authorized read-only connection established the representation differences that explain the failed comparison. No fixture or configuration writes occurred. The original B2 failure and B0 evidence remain unchanged. B3/B4 remain NOT AUTHORIZED; Batch G remains CONDITIONAL CLOSE.

## Root cause

The failed comparison mixes two different PDO row formats and two timestamp display timezones:

1. Historical B0 explicitly sets `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` in `scripts/Run-UiUxAcceptance.php:150`. Its shared projection function uses `fetchAll()` without a per-call mode.
2. B2 creates PDO through Laravel's `PostgresConnector` but does not set an associative default. The connector's defaults do not set a fetch mode. The observed mode was **4 (`PDO::FETCH_BOTH`)**, so each selected column also appears under a numeric key.
3. B2 explicitly configures the connection timezone as **UTC** at `scripts/Prepare-UiUxBrowser.php:144`. B0's recorded publication timestamps use `+08`. PostgreSQL renders the same `timestamptz` instant differently under these session settings.

These are **verification-helper defects / projection-format differences**, not observed changes to protected configuration values. The previous message “Protected B0 configuration projections changed” did not distinguish those causes and was too broad to establish a persisted change.

## Identity and authorized inspection

Implemented a separate verification-only diagnostic, `scripts/Diagnose-UiUxB2Projection.php`. It accepts no arguments and atomically reserves the fixed `inspection-01` directory. It does not invoke the B2 preparation helper or bootstrap Laravel. Existing helpers and configuration checks were not edited.

Executed once:

```powershell
php scripts/Diagnose-UiUxB2Projection.php
```

| Field | Required / observed | Outcome |
| --- | --- | --- |
| Driver | PostgreSQL / `pgsql` | PASS |
| Database | `osds_ui_browser_test` | PASS |
| Host / port | `127.0.0.1:55432` | PASS |
| Backend PID | **24532** | Recorded |
| Effective transaction read-only | **on** | PASS |
| Default transaction read-only | **on** | PASS |
| Isolation | Explicit `REPEATABLE READ READ ONLY` | Established before configuration queries |
| Current session timezone | **UTC**, matching B2's connection configuration | Recorded |
| Read-only immediately before closure | **on** | PASS |
| Transaction closure | **ROLLBACK** | PASS |

Only the approved browser database was connected to. No `osds_dev`, `osds_test`, fallback target or second inspection connection was attempted. Session-local read-only/timeouts did not alter persisted records. Neither environment file changed.

## Exact comparison and query scope

Historical input:

`storage/app/private/uiux-consolidated-acceptance/gate-b0/osds_ui_browser_test-20261027-071758-a279dc.json` → `compatibility.data`.

Current input:

The same `uiuxCompatibility()` function, using the same `uiuxCompatibilityProjections()` definitions, executed with B2's PDO configuration. Only the function definitions were reused; the inventory CLI and its other database mode were not executed. Source hashes and query text were recorded privately.

**The historical and current SQL projection dictionaries are exactly identical**, including field selections, fixed IDs and ordering clauses. The same PHP named-field JSON normalization is used. The difference occurs in the PDO row shape and session-dependent timestamp strings before comparison, not because a newer root was selected.

| Projection | Records / exact IDs |
| --- | --- |
| Publications | 2: **198, 199** |
| Issuance definitions | 2: **54, 55** |
| Templates | 2: **43, 45** |
| Modules | 2: **1, 2** |
| Intake forms / requirement sets | 2 each: **591, 592** |
| Sections | 2: **1963, 1965** |
| Questions | 7: **4550–4556** |
| Question options | 0 |
| Documentary requirements | 2: **1495, 1496** |
| Provider bindings | 4: each requirement paired with providers **1 and 2** |
| Configuration approvals | 4: **264–267** |
| Definition structures | 2: **54, 55** |
| Template structures | 2: **43, 45** |
| Personnel roots / completeness projections | 16 each: **2, 4, 6, 8, 12, 14, 16, 19, 22, 25, 28, 31, 40, 42, 44, 46** |

Total: **16 projection groups / 67 projected rows**. IDs are reported once per projection; the repeated personnel/definition/template rows are distinct projections, not additional configurations. Only approved configuration metadata, structure and derived personnel completeness booleans were selected. No applicant answers, personal names, credentials, evidence locators or external contents were retrieved.

Observed projections were saved privately **before** either equality result was computed. The diagnostic separately records missing/added IDs, record ordering, every differing field and canonical copies. No original comparison result was replaced by the canonical outcome.

## Field-level results

| Difference | Historical | Current | Classification / effect |
| --- | --- | --- | --- |
| **567 added numeric column keys**, across all nonempty projections | Named fields only | Same named fields plus numeric column aliases | **Historical projection-format mismatch caused by verification-helper defect.** Numeric keys duplicate selected column values; they are not added database fields or records. Full group/ID/key/value list retained privately. |
| Publication **198**, `effective_from` | `2026-10-27 09:12:58+08` | `2026-10-27 01:12:58+00` | **Timestamp serialization difference.** Both are the same UTC instant. No observed schedule change. |
| Publication **199**, `effective_from` | `2026-10-27 09:13:07+08` | `2026-10-27 01:13:07+00` | **Timestamp serialization difference.** Both are the same UTC instant. No observed schedule change. |
| Missing records | — | **0** in every group | No missing fixed-record projection |
| Added records | — | **0** in every group | No substituted or extra record |
| Reordered records | Historical ID order | **Identical in all 16 groups** | No ordering discrepancy observed |
| Other named-field changes | Historical values | **None** | No further inspected-value difference |

There are **569 field-level differences**: 567 numeric aliases and two named timestamp strings. The numerical aliases include copies of timestamp/JSON columns in their PDO-returned representation; they are not independent persisted changes. Each numeric alias is identified by group, record ID and column index in private `result.json`.

### Original versus diagnostic equality

| Comparison | Result |
| --- | --- |
| Original B2 comparison, `$now == $before` | **false — original failure reproduced** |
| PHP strict comparison, `$now === $before` | **false** |
| Canonical copies: remove numeric PDO aliases and normalize only nonnull publication `effective_from` / `effective_until` instants to UTC; then strict comparison | **true** |

The existing guard is described operationally as strict, but its implementation at `Prepare-UiUxBrowser.php:159` uses PHP `!=`, not `!==`. This diagnostic preserved that exact comparison and additionally reported `===`; it did not modify either helper's comparison.

Canonicalization did not reorder lists, replace IDs, ignore named columns, coerce arbitrary values or rewrite stored data. It was diagnostic only. **Canonical equality does not authorize fixture writes or waive any byte-identical evidence requirement.** The historical log remains byte-identical; the current raw rows remain different representations.

## Configuration conclusion and limits

**No actual protected configuration change was found in the approved inspected projections.** COR roots 198/54/43 and ACSA roots 199/55/45 still pass the existing fixed-root/module/source/state/date prerequisites. All 16 fixed personnel mappings passed the existing linkage checks, allowing their completeness projection to execute. No change in inspected configuration hashes, bindings, required structures or personnel completeness flags was observed.

The original failed attempt did not retain its rows, so its exact prior values cannot be reconstructed. The current single inspection reproduces its failure and demonstrates sufficient source-level causes; no claim is made to have recovered the missing prior observation.

These limited projections do not constitute a full payload rehash or proof that every unselected configuration field is unchanged. Raw labels/instructions/personnel wording remain outside the approved read scope. Runtime success, future publication expiry, concurrent changes after the snapshot and business acceptance remain unverified. No workflow effect from a persisted change was established by this diagnosis.

## Smallest proposed correction — not implemented

For separate approval, correct the B2 preflight's representation while retaining the original full comparison:

1. Explicitly set its PDO projection fetch mode to **`PDO::FETCH_ASSOC`** before calling the shared projection function.
2. Serialize timestamps consistently with the recorded B0 representation during the **read-only preflight transaction only**. A reviewed local timezone setting matching the recorded `+08` dates, such as `Asia/Manila` for these fixed timestamps, can preserve the original strings; end it with rollback so application/write connections retain UTC. Confirm the resulting raw equality in a separately authorized inspection rather than accepting this proposal as a pass.
3. Persist approved observed projections and field differences privately before any failure assertion. Keep the prior failed log and this diagnostic separate and unchanged.

This approach avoids dropping checks or accepting canonical equality as a new preparation gate. Alternatively, a specifically approved timestamp-semantic comparison policy could normalize just the two temporal fields on both copies, with all other fields strictly compared; that would be a separately reviewed comparison-contract decision, not a silent substitution.

Do not change application timezone contracts, payload hashes, SQL root IDs, published records or existing historical evidence. No correction to `Prepare-UiUxBrowser.php` or `Run-UiUxAcceptance.php` was made here.

## Preservation and evidence

Before implementation, recorded HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and **26,802 existing file hashes**.

| Preservation/check | Actual result |
| --- | --- |
| Baseline comparison | **26,802 unchanged; zero changed or missing** |
| Original PDFs | **73/73 preserved**; all **81 current PDF-named files** unchanged |
| Historical diagnostic JSON files | **10/10 unchanged** |
| Previous B0/B2 failure results, identity logs, reports and manifests | **Unchanged** |
| Runtime, Phase 1–3 evidence, B1 attempts and Batch G artifacts | **Unchanged** |
| Existing generated logs / Blade caches | **Unchanged**, not cleared or restored |
| Known missing temporary upload metadata | Remains prior documented exception; not fabricated |
| Diagnostic PHP syntax / scoped Pint `--test` / whitespace | **PASS**, existing CRLF advisories remain |

New verification file: `scripts/Diagnose-UiUxB2Projection.php`. New private evidence:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-projection-diagnostic/inspection-01/`

Contains `identity.json`, `queries.json`, `observed-projections.json`, `canonical-projections.json` and `result.json` with source hashes and complete field/record differences. Baseline/Git/preservation outputs are in its parent diagnostic directory. This report is new documentation. No existing helper, report, log or configuration was overwritten.

## Outstanding B2 gates and next authorization

Still required before foundational preparation: approved helper correction, a successful full read-only preflight with the fixed comparison, publication 128 verification, full current published-personnel slot-list check, namespace availability, live schema/FK/unique constraints, existing permission definitions, amended manifest and operation-set validation. The prior source-level `applicant.sandbox.publish` amendment remains approved but no account or grant exists.

Recommend authorizing **only the bounded verification-helper correction and one non-overwriting read-only preflight** next. Review those results before separately authorizing any fixture writes. No real configuration repair or replacement is indicated by these findings.

**STOP. One read-only diagnostic completed; no automatic retry, prepare mode, Laravel bootstrap, fixture, permission pivot, migration, office/personnel operation, login, PDF, B3/B4, commit, push or deployment occurred.**
