# Batch G — Scoped assignment-selector target size

**21 October 2026. Scoped automated verification: PASS.** Institutional-representation and regional-assignment selectors only. No authorization, audit, transaction, permission, schema or workflow changes. G-INT-001–007 are preserved; this is not Batch G completion or Batch H authorization. Pause for review.

## Cause and exact correction

Installed Filament's `support/resources/css/components/input/select.css` gives `.fi-select-input-value-remove-btn` `size-4` (16px). Its masked glyph also clips an outline placed on the button itself. Existing reviewer-specific 24px adapters did not apply to these two forms.

- Add `authority-assignment-selector` through `extraAttributes` on the User/Institution and User/Region selects in `InstitutionRepresentativeForm.php` and `UserRegionAssignmentForm.php`. Selector queries, validation, disabled-on-edit behavior and callbacks are unchanged.
- Extend the existing local rule in `resources/css/filament/admin/theme.css` with that class: `min-inline-size: 24px; min-block-size: 24px`.
- When the clear button has `:focus-visible`, draw a 3px outline with the existing `--osds-focus` token and 2px offset on its containing selector button. No JavaScript handlers, global select styles or other controls are changed.

## Measurements and actual browser checks

Chrome/Playwright, local guarded ports 8026/8027, `osds_ui_browser_test` only. Fixture creation uses the existing isolated controls, serialized. Both selected clear buttons in each form are measured in Light/Dark at 1440px and 375px: **eight page states / sixteen target measurements** per run.

| Form | Before, each clear target | After, each clear target | Final axe violations |
| --- | --- | --- | --- |
| Institutional representation | 16 × 16 CSS px | 24 × 24 CSS px | 0 in all four states |
| Regional assignment | 16 × 16 CSS px | 24 × 24 CSS px | 0 in all four states |

Final axe uses every default rule plus explicitly enabled `target-size`, without exclusions, tags, target-size allowance or notification resampling. The first before scan used axe defaults, which omit target-size; its zero findings do not invalidate the earlier G-INT-006/007 recorded target-size violations. Its actual 16px measurements remain valid. Final scans explicitly include the rule and require zero violations.

At every after state, Tab reaches each clear button, a computed 3px solid focus outline is present, Enter clears it, and Space opens the selector followed by search, ArrowDown and Enter to reselect. **Sixteen clear/reselection cycles passed.** Both forms retain native required-date validation and submit through the actual Create action, producing one assignment and one matching audit per successful operation. No page-level overflow or uncaught page errors were recorded. Developer inspection of the dark mobile representation focus screenshot shows separated text/clear/chevron controls and a visible containing outline. These composite controls retain Filament's existing padding/layout; no spacing exception is relied on to satisfy 24px sizing.

Evidence: [representation after](screenshots/phase-3d7-selector-accessibility/after/representation/results.json), [regional after](screenshots/phase-3d7-selector-accessibility/after/regional/results.json), [dark mobile focus](screenshots/phase-3d7-selector-accessibility/after/representation/focus-0-dark-375.png). Before screenshots/results and other focus screenshots are in the same evidence root.

## Harness corrections and limits

Earlier keyboard attempts are retained as `keyboard-first-attempt.json` through `keyboard-fourth-attempt.json`: initial sequences used Enter on the closed combobox, direct option focus without its internal keyboard index, or Tab from search. Installed Filament intentionally submits on closed-combobox Enter; its supported Space/search/ArrowDown/Enter path passes. The search-Tab attempt closed the popup before the expected option focus; that sequence is not certified or changed by this styling task. No independent critical defect was confirmed. A subsequent successful Create redirected to the real edit route while the harness expected a list; `create-redirect-first-attempt.json` preserves that assertion error. The final harness expects the actual edit route and checks persisted assignment/audit counts. No runtime keyboard or redirect behavior was modified to make tests pass.

The local PostgreSQL service was initially stopped; its existing helper failed to start within the sandbox, then succeeded with approved escalation. No database defaults, manual records or service configuration were changed.

## Preservation and final gates

The existing institutional-representation and regional-creation browser journeys passed with the earlier target-size allowance removed, retaining actual recovery/persistence assertions and separate artifact paths: **seven interactions / eight screen checks each; fourteen / sixteen combined**. Both exercise required validation, audit-side rollback with retained input, double click, duplicate rejection, committed-response loss, saved-list inspection, permission loss and cookie loss. All sixteen screens have zero axe violations, zero page overflow and no uncaught page errors; no transient-notification resampling was needed. [Representation preservation](screenshots/phase-3d7-selector-accessibility/preservation/representation/results.json), [regional preservation](screenshots/phase-3d7-selector-accessibility/preservation/regional/results.json). No security remediation was reopened or changed.

Full Pint `--test`, production Vite build and final diff checks passed (existing CRLF advisory only). Private logs: `selector-pint.txt`, `selector-build.txt`, `selector-after-final.txt` and `selector-preservation.txt` under `storage/app/private`. New tooling: `scripts/verify-assignment-selector-accessibility.mjs` and `scripts/verify-selector-preservation.mjs`; temporary wrappers are removed after execution and previous evidence is retained. No new PHP tests are introduced for this styling-only correction. Earlier 252-test / 2,831-assertion PostgreSQL result is historical; no full-suite rerun is claimed here.

## Human acceptance and boundary

Human screen-reader announcements, native zoom, touch-device usability, timed-animation contrast and first-time task testing remain open. Automated axe and screenshots are not full WCAG certification. Avatar requests are blocked by isolated browser fixtures. Earlier integrity/security reports and their outcomes remain unchanged; no historical repair, manual-testing database inspection/mutation, staging, commit, push, deployment, Batch H or Phase 4. Pause for review after this scoped correction.
