# B4 accessibility remediation — implementation and verification

**27 October 2026. Authorized bounded remediation implemented. Automated violation checks: PASS. Human accessibility and supervisor/business acceptance: NOT CLAIMED. Stop for review.** Batch G remains CONDITIONAL CLOSE.

The five original failed captures have no corresponding automated violations in the fresh final matrix. Separate axe **incomplete** results remain unresolved and are explicitly retained below; this is not complete accessibility acceptance.

## Runtime changes

Only two application files changed from the verified starting HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**:

1. `resources/views/filament/pages/form-builder.blade.php`: changed the nested `main.focused-builder-main` and closing tag to `div`. Filament's existing main landmark and skip-link target remain. Corrected the four accessible names to **Move section up/down: section N** and **Move up/down: selected question**. Visible labels, handlers, arguments, ordering, disabled states and bindings are unchanged.
2. `resources/css/theme/components.css`: added two application-owned success-notification transition selectors, keeping opacity at 1 for `fi-transition-enter-start` and `fi-transition-leave-end`. Existing translation, transition duration, notification lifetime, dismissal handlers and colors remain. No vendor files or JavaScript behavior were modified.

There are no changes to application services, permissions, approval rules, migrations, audit contracts, document rendering or stored originals.

## Notification diagnosis and evidence-gated correction

Real evaluator actions were exercised independently for **COR and ACSA × light/dark × 1440px desktop/375px mobile**. Diagnostic applications and Chief returns for reevaluation were performed through existing authorized UI controls. No notification success was fabricated or substituted for an application action.

Per-frame observations retained computed title/background colors, the actual underlying background, ancestor opacity, current classes, transition duration/property and animation progress. Effective contrast was calculated from composited sRGB colors. Stable visible notices also received unrestricted axe plus enabled target-size checks. Notifications were allowed to follow their natural lifecycle; diagnostics did not dismiss them early or alter clocks, motion preferences or thresholds.

| Measurement | Before CSS correction | After CSS correction |
| --- | --- | --- |
| Eight real-action diagnostic combinations | Low-contrast visible frames in all eight | Zero low-contrast visible frames in all eight |
| Minimum observed effective contrast | Approximately **1.00–1.02:1** during opacity transitions | **19.90:1 light**, **17.74:1 dark** |
| Stable title contrast | **19.90:1 light**, **17.74:1 dark** | Same colors and stable contrast |
| Stable visible-notice axe violations | Zero | Zero |

The stable palette was not the defect. Root opacity reduced both the background and title into the underlying page during entrance, producing insufficient effective contrast; ACSA light mobile also recorded this during exit. The root styles and transition classes match the inspected vendor implementation. The application-owned CSS prevents that opacity loss while retaining the position transition. The exact historical B4 failing frame cannot be reconstructed from its summary alone; the new real-action observations establish the component defect directly.

The light title's computed OKLCH color converts to sRGB `9,9,11` over `255,255,255`; dark uses `255,255,255` over `17,24,39`. Post-fix visible samples have ancestor opacity 1. The recorded transition duration remains **0.3 seconds** before and after the fix.

Valid before measurements are in [normalized before results](screenshots/b4-accessibility-remediation/notifications-before/normalized-results.json); [after results](screenshots/b4-accessibility-remediation/notifications-after/results.json) and [paired comparison](screenshots/b4-accessibility-remediation/notification-comparison.json) provide the correction evidence. Full frame recordings, visible-notice screenshots and axe results are retained beside those files.

The initial diagnostic numeric parser did not support the browser's OKLCH title color. Its original files were preserved. A separate Chrome-canvas sRGB conversion recalculated contrast from the retained computed colors and opacity, recording original-file hashes. **Only the normalized before ratios are used for conclusions.** Both observed backdrops were verified as opaque sRGB white or `17,24,39`; the incorrect preliminary ratios are not presented as accepted measurements. After diagnostics used the corrected conversion directly.

## Final 32-capture acceptance matrix

**32/32 samples have zero automated axe violations. No measured horizontal overflow or uncaught page errors.** See [final results](screenshots/b4-accessibility-remediation/results.json) and the [capture manifest](screenshots/b4-accessibility-remediation/capture-manifest.json).

All eight B4 checkpoints were repeated independently for both modules, at light desktop 1440px and dark mobile 375px:

1. Fresh questionnaire authoring/save/reload.
2. Applicant R1 completion/submission journey.
3. Correction and applicant R2 resubmission journey.
4. Technical evaluation.
5. Chief recommendation and Director decision.
6. Issuance-definition review/publication.
7. Applicant issuance-request draft/submission.
8. Explicit staff generation and applicant stored-PDF download.

The final run used namespace `b4_20261027_a11y_02` after all runtime changes and the asset build. Earlier diagnosis/preliminary journeys used `b4_20261027_a11y_01` and are retained separately. Each namespace has fresh foundational identities, institutions, representation, eligibility and region/module assignments. Business/configuration records were created through actual UI handlers; old browser records were not reset or reused as transient states.

The original B4 harness is byte-identical. Separate drivers change namespace, port and output locations and save raw axe output after analysis. Existing capture waits, notification-dismissal behavior, finite-animation handling, unrestricted default axe rules, target-size, overflow and error checks were not relaxed. Additional notification lifecycle diagnostics are separate from the standard matrix. The final author captures show actual fresh editable questionnaires; original B4 author readback captures remain untouched.

### Before/after mapping of the original failures

| Original failed sample | Final corresponding sample | Automated result |
| --- | --- | --- |
| COR author readback, light desktop | COR fresh author, light desktop | PASS — no landmark/name violations |
| COR author readback, dark mobile | COR fresh author, dark mobile | PASS — no landmark/name violations |
| ACSA author readback, light desktop | ACSA fresh author, light desktop | PASS — no landmark/name violations |
| ACSA author readback, dark mobile | ACSA fresh author, dark mobile | PASS — no landmark/name violations |
| ACSA evaluator, light desktop | ACSA evaluator, light desktop | PASS — no notification contrast violation |

The [original B4 manifest](screenshots/b4-end-to-end/capture-manifest.json) and all original images remain unchanged. Representative final builder and PDF images were visually inspected; this is not human accessibility acceptance.

### Unresolved axe incomplete checks — not waived

All 32 samples also contain one or more **incomplete** rule results. Across the matrix there are 40 rule-result entries:

- **16 `color-contrast`** entries on non-text/decorative characters, including status dots and navigation symbols. Axe reports that the element contains only non-text characters.
- **16 `aria-valid-attr-value`** entries involving Filament dropdown triggers: axe could not determine whether the `aria-controls` target exists for the `aria-haspopup` control.
- **8 `aria-prohibited-attr`** entries on the applicant `.theme-switcher`: an `aria-label` on a div without a valid role requires review.

These results are included in each safe full axe record and summarized in the capture manifest/results. No node, rule or finding was suppressed. They concern untouched components and were not expanded into additional runtime changes under this bounded approval. **Their disposition is unresolved; manual/accessibility review is BLOCKED pending review and any separately approved follow-up.** Zero automated violations must not be read as clearance of incomplete checks or complete WCAG compliance.

## Actual tests and final gates

| Gate | Actual result |
| --- | --- |
| Initial focused questionnaire/configuration/evaluator tests | PASS — **49 tests / 553 assertions** |
| Focused tests after the notification CSS correction | PASS — **49 tests / 553 assertions**, 108,811 ms |
| Complete current-tree PostgreSQL suite | PASS — **431 tests / 4,627 assertions**, 776,610 ms |
| Final standard accessibility captures | PASS for automated violations — **32/32**, zero overflow/errors |
| Additional notification matrix after correction | PASS — **8/8**, no low-contrast frames or axe violations |
| Full Pint `--test` | PASS |
| Four new PHP helper syntax checks; seven new JavaScript helper syntax checks | PASS |
| Vite production asset build | PASS; existing bundler timing advice remains |
| Tracked/new-file whitespace and report links | PASS |
| Human accessibility, incomplete-check disposition, supervisor/business acceptance | NOT ACCEPTED / review pending |

The focused classes were `FormMakerTest`, `QuestionnaireConfigurationTest` and `ApplicationDecisionFoundationTest`. They retain existing reorder, selection, save/review and evaluator/state-integrity behavior checks. The complete suite includes retained B1–B3 authorization, workflow, document, questionnaire, applicant, correction and downloadable-form coverage. These are new executions against the current working tree, not historical-pass substitutions or a committed-tree certificate.

Private logs: `storage/app/private/b4-accessibility-remediation/focused.txt`, `focused-final.txt`, `full-final.txt`, `build.txt`, `pint-final.txt`, and staged UI/diagnostic logs. The full runner explicitly sets 512 MB for its PHPUnit child, preserving the established verification setting rather than changing application memory configuration.

A packaging attempt initially could not parse PowerShell's UTF-16 test logs. That attempt is retained privately; the reader was corrected to detect encoding and extract the actual PHPUnit summaries. No test, persistence assertion or acceptance threshold was weakened. The preliminary matrix remains available but is not substituted for the final post-change matrix.

## Preservation and database boundaries

[Preservation evidence](screenshots/b4-accessibility-remediation/preservation.json) confirms:

- Baseline: **2,298 tracked/reference files**, including the unrelated temporary Batch G script. Exactly the two authorized runtime files changed; **zero unexpected baseline changes**.
- All existing B3/B4 reports, captures, harnesses and artifacts remained byte-identical.
- **All 45 pre-existing private PDFs remained checksum-identical.** They were not regenerated or overwritten.
- **All ten published historical evidence JSON files were restored byte-for-byte** after the unchanged regression writers ran. Their newly generated outputs and before/regenerated/restored hashes are [archived separately](screenshots/b4-accessibility-remediation/regression-evidence/restoration.json).
- Git HEAD remains `f7625a9`. No commit, push or deployment was performed.

Browser preparation and requests checked configured and actual PostgreSQL identity as `osds_ui_browser_test`. Targeted and full tests checked both identities as `osds_test`. No queries, migrations, fixture operations or activation were performed against `osds_dev` or any manual database. Existing isolated publications were retired/withdrawn through their normal UI controls where uniqueness required replacement; records were retained. No synchronizing authorization seeder ran.

Both final main cases retain distinct evaluator/Chief/Director accounts, two immutable submissions, one submitted case-linked request, and one completed original with one render attempt. New PDFs retain exact R2 identity/address and immutable case/decision/template/personnel linkage, and every page has fixed demonstration markings. See [actual PDF inspection](screenshots/b4-accessibility-remediation/final-matrix/pdf-inspection.json). Original generation services, schemas and audit contracts are hash-identical to baseline.

The task-owned port-8042 server was stopped after browser work. Unrelated servers and the PostgreSQL service were preserved.

## Delivered files and remaining review

- This report and evidence under `docs/screenshots/b4-accessibility-remediation/`.
- Two runtime presentation files listed above.
- `scripts/Prepare-B4Accessibility.php`, `Prepare-B4AccessibilityFinal.php`, `Run-B4AccessibilityTests.php`, `b4-accessibility-server.php`: guarded isolated preparation, inspection, runner and router.
- `scripts/rehearse-b4-accessibility.mjs`, `rehearse-b4-accessibility-final.mjs`, `run-b4-accessibility-matrix.mjs`: separate original-compatible UI drivers.
- `scripts/diagnose-b4-notifications.mjs`, `normalize-b4-notification-colors.mjs`: live observation and retained-color normalization.
- `scripts/inspect-b4-accessibility-pdfs.mjs`, `package-b4-accessibility-evidence.mjs`: actual output inspection and evidence assertions.

Raw session-bearing traces/logs stay private. Public axe records omit raw HTML while retaining rules, targets, findings and measurement data; raw complete records remain private. Evidence serialization did not alter the DOM or the checks.

**Stop for review.** The approved builder and success-notification defects are corrected and the required automated verification completed. Incomplete axe checks, human keyboard/zoom/screen-reader review and supervisor/business acceptance remain outstanding. No claim of whole-system accessibility, national production readiness, official issuance, manual activation or another development phase is made.
