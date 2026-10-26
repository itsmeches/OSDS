# B4 accessibility remediation — diagnosis and approval plan

**26 October 2026. Inspection and planning only. Awaiting implementation approval.** No application code, vendor file, browser harness, database or historical evidence was changed during this inspection. No live reproduction, tests or new accessibility passes are claimed. Batch G remains CONDITIONAL CLOSE.

## Baseline and diagnosis

The [B4 report](osds-b4-end-to-end-acceptance-report.md) records 27 passing and five failing captures. The original [capture manifest](screenshots/b4-end-to-end/capture-manifest.json), screenshots and private traces remain the before evidence.

| Finding | Inspected cause | Confidence |
| --- | --- | --- |
| Four builder captures: three landmark findings each | `resources/views/filament/pages/form-builder.blade.php:40` creates `<main class="focused-builder-main">` inside the Filament layout's `<main id="fi-main-content">` (`vendor/filament/filament/resources/views/components/layout/index.blade.php:109`). The inner main produces nested/duplicate main landmarks. CSS targets its class, not its HTML tag. | Confirmed in source and B4 DOM findings. |
| Four builder captures: visible-label/name mismatches | Section buttons display **Move section up/down**, but their `aria-label` inserts a section number inside those phrases. Question buttons display **Move up/down**, but `aria-label` says **Move selected question up/down**. The complete visible wording is absent from the accessible name. | Confirmed at builder lines 47 and 59. |
| One ACSA evaluator light capture: notification-title contrast | The stage action sends a Filament success notification. Vendor title styles use gray-950/light and white/dark; OSDS overrides uncolored notification backgrounds through theme tokens. The notification component also animates entrance/exit and opacity. The retained trace contains success-notification enter/leave classes; the saved screenshot contains no visible toast. The B4 harness dismisses notifications before axe and saves its screenshot afterward. | **Root cause not yet proven.** Transition timing, theme interaction and stable-state contrast require measurement. Trace classes alone do not establish the failing instant or contrast ratio. |

Inspected notification sources: `app/Filament/Pages/SyntheticApplicationIntake.php:88`, `resources/css/theme/components.css:88,116`, `resources/css/theme/tokens.css`, and vendor notification CSS/JavaScript. The full original trace is private: `storage/app/private/b4/trace-1793001123881.zip`. Existing evidence stores the axe rule/target but not full computed color/contrast details, so a precise notification fix cannot yet be selected responsibly.

## Proposed implementation boundary

### 1. Builder landmark correction

Replace the inner `<main>` and its closing tag with a `<div>`, preserving `focused-builder-main`, content, layout and all Livewire/Alpine bindings. Retain Filament's existing main landmark and skip-link target. Do not add another `role="main"` or a redundant region merely to satisfy axe.

### 2. Button names

Preserve visible wording and provide context after it:

- `Move section up: section N` / `Move section down: section N`.
- `Move up: selected question` / `Move down: selected question`.

Change only the accessible labels of the four affected controls. Preserve their handlers, arguments, disabled states, focus behavior and order. Inspect the remaining builder controls for the same pattern; report any wider issue before expanding the patch.

### 3. Notification reproduction and evidence-gated fix

Before changing notification styling, reproduce the real evaluator success notice using an authorized UI action in `osds_ui_browser_test`. Do not fabricate a successful application action or reuse terminal records as if they were under evaluation.

Record before/after action and theme state, notification classes, computed foreground/background and ancestor opacity, transition/animation timing, full axe failure details and synchronized screenshots. Check light/dark, desktop/mobile, stable visible notice and entrance/exit. Include the COR counterpart to distinguish an ACSA-specific defect from a shared notice defect. A non-reproduction is recorded explicitly, not converted to PASS.

Only after evidence identifies the cause:

- For a stable color-pair defect, propose the smallest application-owned notification theme override with verified contrast in both themes.
- For a confirmed opacity/animation defect, identify the actual rendering layer responsible and propose a local presentation fix that preserves notice content, dismissal and keyboard behavior. Do not assume a CSS duration override fixes vendor-managed JavaScript animations.
- Do not edit vendor assets or broadly restyle every notification without justification. If remediation requires a dependency change, vendor JavaScript override or broader behavior change, stop for additional approval with the reproduction evidence.

Do not suppress axe rules, exclude notification nodes, dismiss notices earlier, prolong waits, force reduced motion, or change capture logic to manufacture passing results. Additional diagnostics may observe intermediate states; the existing acceptance checks and their measurement criteria remain intact.

## Verification after approval

1. **Preservation first:** snapshot current Git state and source hashes; hash all prior evidence, including the complete B4 directory and the ten original published JSON files. Preserve existing B1–B3 pending changes. New outputs go under `docs/screenshots/b4-accessibility-remediation/`; originals are never overwritten.
2. **Isolation:** assert configured and actual PostgreSQL identities before preparation and test execution. Use only `osds_ui_browser_test` for browser work and `osds_test` for automated regression. No access or activation of `osds_dev` or another manual database.
3. **Focused gates:** run existing `FormMakerTest` and `QuestionnaireConfigurationTest`; include `ApplicationDecisionFoundationTest` for evaluator-notification interaction. Verify rendered landmark/name semantics and actual keyboard/button behavior without implementation-mirroring test proliferation.
4. **Full 32-capture rerun:** repeat both modules at the same eight B4 checkpoints, 1440px light and 375px dark. Use fresh isolated, uniquely named fixtures and real UI transitions for transient application states; do not reset historical browser records or substitute terminal-state screenshots for earlier workflow checkpoints. Foundational preparation retains the approved B4 restrictions. Preserve the original harness; any separate evidence-directory/namespace driver adaptation must be explicit and retain identical axe, target-size, overflow and uncaught-error checks.
5. **Additional notification checks:** retain the live notice and timed diagnostic observations separately from the 32 B4 samples. Passing settled samples alone does not establish transient-state correctness. Do not conceal a reproduced notification failure by relying on dismissal.
6. **Regression:** Blade/CSS changes are application runtime changes. Therefore run the complete current-tree PostgreSQL suite after targeted tests, using explicit `osds_test` identity checks and the established runner memory setting. Archive outputs from historical-evidence-writing tests separately and restore their original published evidence byte-for-byte, checking every hash. Record failures and interrupted attempts honestly.
7. **Final gates:** full Pint `--test`, relevant PHP/JavaScript syntax, Vite production asset build and tracked/new-file whitespace checks. Recheck all preservation hashes. Reconfirm exact stored demonstration PDF checksums and B1–B3 linkage/authorization preservation without regenerating completed originals. Stop task-owned servers.

Do not alter application services, stage permissions, eligibility, schemas, state constraints, audit contracts, stored PDFs or document rendering to solve these presentation findings. An unexpected workflow/regression failure must be reported and scoped separately.

## Remediation report and acceptance criteria

Deliver `docs/osds-b4-accessibility-remediation-report.md` after authorized execution, with:

- Exact changed files and evidence-supported root causes.
- Original five failing captures linked to new corresponding results, plus **all 32** new captures and full axe details.
- Notification reproduction attempts, measured colors/opacity/timing, screenshots and the selected fix's rationale.
- Actual targeted/full PostgreSQL totals, build/static results and preservation hashes.
- PASS/FAIL/BLOCKED for each gate, retained failures and unresolved human acceptance.

The target is zero unrestricted axe findings, target-size failures, overflow and uncaught errors across all 32 samples, with the notification finding either reproduced and corrected or explicitly unresolved. No rule waivers or historical-pass substitutions. Human accessibility and supervisor/business acceptance remain separate.

### Current before/after status

| Gate | Before | After |
| --- | --- | --- |
| B4 sampled accessibility | FAIL — 5/32 captures | NOT RUN — approval pending |
| Landmark root cause | Confirmed | No fix applied |
| Button-name root cause | Confirmed | No fix applied |
| Notification root cause | Unresolved; transition/theme hypotheses identified | Live reproduction pending |
| Targeted/full regression | Historical results only | NOT RUN |

**Approval requested:** the two exact builder markup/name changes, isolated notification diagnosis with the conditional scope above, and the complete verification/evidence sequence. Stop for review before implementing. No manual activation, commit, push, deployment or phase progression is included.
