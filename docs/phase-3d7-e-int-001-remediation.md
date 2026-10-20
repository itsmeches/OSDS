# E-INT-001 — Condition-preservation remediation progress

## Post E-INT-002 verification — 20 October 2026

The selected-editor binding correction is now implemented under targeted authorization. Final E-INT-001 browser acceptance passed **7 interaction checks and 6 stable light/dark desktop/tablet/mobile screens**, with no axe findings, page overflow or uncaught page errors. This includes complete no-op/unrelated-edit tree preservation, nested preview semantics, intentional simple edit/removal, protected-projection rejection, exact published-manifest applicant parity and published read-only behavior.

The condition-shape provider now covers six shapes, including typed null and set operators in addition to the original leaf/all/any and nested cases. Final FormMaker PostgreSQL tests passed **29 tests / 375 assertions**; the full suite passed **117 / 1,518**. Original failures remain enabled and historical stop evidence below is retained. Broader final recovery/accessibility results are in the Batch E report. Human usability, native zoom and screen readers remain pending. No historical repair or manual database access occurred.

### Historical remediation progress before E-INT-002 authorization


**Date:** 19 October 2026. **Status:** Implemented with passing server regressions; acceptance incomplete. Work stopped on independent critical browser defect E-INT-002. No Batch F or Phase 4.

## Root cause and correction

The old loader projected only the first `all`/`any` child, ignoring standalone leaves. Every save reconstructed a one-leaf `all`, losing other branches. The new compiler preserves the complete original rule unless a representable condition is intentionally edited or the explicit Remove condition action is invoked.

### State ownership

- `conditionBaseline` is established from the authorized database models on mount and refreshed from persisted models only after the authoring service confirms a save. It is keyed separately by question and requirement stable keys.
- Livewire `#[Locked]` protects this baseline, explicit-clear state and expected version counters. Client changes to ordinary `condition_rules` fields are not used as authoritative rules.
- The compiler compares editable source/operator/value fields with the original faithful projection. Untouched rules retain the full baseline, including leaf/group shape, child order and typed values.
- Existing simple rules cannot be removed by emptying a projected source. Remove condition records server-side clear intent and resets its editor fields. It does not save automatically.
- Unsupported projections are blocked with a validation error; preview continues to evaluate the preserved rule. No successful save notification occurs on that failure.
- Shared `ConditionalLogic::validate` checks compiled conditions against earlier questions, or all questions for requirements, before authoring persistence. Missing/later sources and invalid option references retain the draft's existing stored state.
- Existing transactional service authorization, draft-only checks and exact-pair optimistic locking remain authoritative. Stale edits retain local input and cannot overwrite the newer version.

Standalone leaves and one-child `all` rules using the existing simple comparisons can be edited. Multi-child groups, `any`, nested groups, set operators and non-text scalar values are protected rather than projected into misleading controls. Their full rule is displayed recursively with question/choice labels and group descriptions. Explicit removal is available with confirmation. No new rule syntax or evaluator semantics was introduced.

## Persisted examples

| Input | Previous unchanged save | New unchanged save in PostgreSQL tests |
| --- | --- | --- |
| Standalone leaf | `null` | Same standalone leaf |
| `all` with two children | First child only | Same two children in the same order |
| `any` with two children | One-child `all` | Same `any` tree |
| Nested `any`/`all` | Lossy first-leaf projection | Same complete nested tree |

Tests compare the exact database-loaded JSONB before and after saving. PostgreSQL canonicalizes object-property ordering on the initial insert; the retained strict assertions compare against that actual stored baseline, not an artificial PHP insertion order. Group shape, child list order, scalar values and types remain exact. Original three reproductions remain enabled; a fourth nested case was added.

## Files

- `app/Filament/Pages/FormBuilder.php`: baseline/state ownership, projection protection, explicit clearing, condition validation and preview compilation.
- `resources/views/filament/pages/form-builder.blade.php`: shared condition editor and conditional outline markers.
- `resources/views/filament/pages/partials/builder-condition.blade.php`: faithful simple controls, read-only explanation and explicit removal.
- `resources/views/filament/pages/partials/builder-condition-summary.blade.php`: recursive rule summary.
- `tests/Feature/FormMakerTest.php`: original preservation cases plus question/document round trips, semantic evaluation, navigation/reload/unrelated changes, explicit edit/clear, invalid sources, ordering/removal, stale writer and post-revocation helper/action denial.
- `scripts/Prepare-BuilderJourney.php`: reserved-browser-database fixtures and persisted inspection; optional publication through existing services, not a new product path.
- `scripts/verify-builder-journey.mjs`: incomplete browser acceptance journey; failures remain recorded, not skipped.
- `scripts/reproduce-builder-selection-integrity.mjs`: independent E-INT-002 failing browser regression.
- Issue register, Batch E report/manual checklist and this document.

No schema, permissions, approval/publication services, UTC configuration, applicant ordering adapter, historical records or manual-testing records were changed by this remediation.

## Completed and incomplete verification

- Original preservation cases and expanded focused run: **21 tests / 245 assertions passed**.
- Complete PostgreSQL run at that revision: **109 tests / 1,388 assertions passed**, retaining existing security, publication and timestamp suites.
- Two additional focused tests then passed with the complete FormMaker file: **23 tests / 264 assertions**. They cover question editing/clearing/invalid references/staleness and permission revocation before helper/save/clear actions.
- The final small non-text-value protection adjustment was made after these runs; no final complete-suite pass is claimed for the exact stopped tree.
- Laravel Pint formatted the implementation/tests/fixture and Vite production build passed before the independent critical stop.
- Real Chrome browser completed complex question/document no-op save, reload and exact persisted-tree checks, plus unrelated editing/stage preservation of the trees. That unrelated edit exposed a separate cross-question title mutation. These checks are therefore **not** overall authoring-integrity acceptance.
- The minimal E-INT-002 reproduction confirms the unintended title in client state, PostgreSQL and reload, and exits with a failing assertion.

The full browser journey has not passed. Staff-preview/applicant applicability, intentional browser condition edits/removal, protected-operation feedback and light/dark/mobile/axe screens remain unfinished. PHP semantic tests exercise the shared evaluator on Alpha, Beta, Blocked and empty answers for both question and requirement rules; they do not replace browser/applicant acceptance. No human usability, native zoom or screen-reader verification is claimed.

All PostgreSQL tests used `osds_test`. Browser preparation/inspection used only `osds_ui_browser_test` on port 8017. No command inspected, reset, reseeded or modified either manual-testing database. No historical repair was attempted.
