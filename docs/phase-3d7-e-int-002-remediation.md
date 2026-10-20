# E-INT-002 — Focused editor binding integrity

**Date:** 20 October 2026 (Asia/Manila). Targeted authorized remediation; final automated Batch E verification passed; human acceptance remains pending. No historical repair, migration, or policy change.

## Proven root cause

The original reproduction selected Question 2, edited its title, saved, and reloaded. Both Question 1 and Question 2 changed. The database faithfully persisted the unintended client state.

Native input-listener instrumentation located the first unintended mutation before any request or response reconciliation. The reused input displayed Question 2's `wire:model` path but retained a listener registered for Question 1's path. That listener changed Question 1, followed by the new listener changing Question 2. The installed Livewire model directive captures the binding expression when initializing its Alpine binding; changing the attribute on a reused control did not remove the earlier effective listener in this reproduction.

Before evidence is retained in `screenshots/phase-3d7-e/selection-integrity-before.json`, `selection-integrity-before.png`, and `selection-integrity-mechanism-before.json`. Authentication payloads are excluded from retained request traces.

## Correction

`resources/views/filament/pages/form-builder.blade.php` keys focused question and requirement editors by stable item identity **and current binding location**. Section outline and option-row keys also include their current binding location. Moving an item therefore remounts controls whose numeric model paths changed, while server selection continues to resolve stable keys. This removes stale bindings rather than masking their effects after persistence.

No vendor patch, arbitrary correctness delay, broad request-ordering adapter, or domain-service rewrite was introduced. The applicant C-INT-001 adapter is unchanged. Earlier diagnostic attempts that held a model response while waiting for a queued selection were harness deadlocks, not proof of another product race.

The title was independently reproduced before remediation. Related help, type, required, option, section, and document controls share the corrected container mechanism and receive post-remediation coverage; they are not all claimed independently reproduced before the fix.

## Related authoring usability and recovery

- Advanced settings retain their native open state across child updates using `wire:ignore.self` on the disclosure itself.
- Section title, description, and accessible move actions retain stable selection. Invalid condition-source ordering is rejected through existing validation.
- Immediate local dirty feedback, a leave warning, confirmed-save feedback, and RAM-only copy recovery distinguish local input from persisted configuration.
- Failed or uncertain consequential requests do not automatically retry. A committed response loss blocks further editing and offers the exact saved pair in a new tab for inspection.
- Expired sessions preserve the open tab; permission denials block the workspace and local-copy control. Server-side per-request authorization remains authoritative.
- Unexpected save failures expose a support reference and log only the exception class and reference, excluding configuration or exception text.
- Existing labels retain their exact case, spaces, and underscores when saved. No historical configuration is rewritten.
- Questionnaire overview metrics require an unambiguous pair; creation uses the existing approved-module workflow and permissions.

## Verification boundary

### Changed surfaces

- `resources/views/filament/pages/form-builder.blade.php`: keyed binding ownership, section controls, mobile outline, read-only fieldsets and recovery presentation.
- `resources/views/filament/pages/partials/builder-recovery.blade.php`: component-scoped request/message feedback, RAM-only recovery and denial handling.
- `app/Filament/Pages/FormBuilder.php`: section moves and safe unexpected-save failures; existing condition baseline and service boundary retained.
- `app/Services/Questionnaires/QuestionnaireAuthoringService.php`: exact label preservation, without changing validation or transaction rules.
- `resources/views/filament/pages/questionnaires.blade.php`: ambiguous-pair overview guidance.
- `resources/css/theme/components.css`: scoped outline/recovery layout using existing semantic colors.
- `tests/Feature/FormMakerTest.php`: preserved condition/isolation, authorization and failure-redaction regressions.
- Browser fixture/reproducer and `verify-builder-selection-matrix.mjs`, `verify-builder-journey.mjs`, `verify-builder-recovery.mjs`, `verify-builder-system.mjs`: client/PostgreSQL/reload, native flow and theme evidence. The ordering trace is diagnostic, not a replacement for the acceptance matrix.

Final results: original reproduction and 14 selection cases passed; condition journey 7 checks / 6 stable screens; recovery/authoring 14 checks / 30 stable screens; System/focus checks passed. The 36 stable screens have zero axe, page-overflow or uncaught-error findings. Original plus 11 expanded C-INT-001 cases passed. PostgreSQL: 29 / 375 focused and 117 / 1,518 complete. Pint, Vite build and working-tree diff check passed; pre-existing staged whitespace remains without index changes. See `phase-3d7-batch-e-report.md` for screenshots, measured contrast, provenance and limits. Browser matrices compare client state, PostgreSQL state, and reload results; server-only tests are insufficient to establish this binding correction.

E-INT-001's locked complete-condition baseline, explicit removal, protected complex rules, service validation, and optimistic locks remain in place. SEC-001–006, UTC connections, exact approval pairing, publication immutability, and applicant correction lineage are unchanged.

Fixtures use only `osds_test` and `osds_ui_browser_test`. No commands inspect, reset, reseed, or modify `osds_sandbox` or `osds_dev`. Human usability, screen-reader testing, and native browser zoom remain separate pending checks. Batch F and Phase 4 remain unstarted.
