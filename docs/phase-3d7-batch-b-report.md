# Phase 3D.7 — Batch B shared design foundation

**Date:** 19 October 2026 (Asia/Manila)  
**Boundary:** Shared presentation, accessibility semantics, theme preferences, and isolated browser verification. No lifecycle, scope, permission, encryption, or database-schema change. Batch C is not started.

## Design changes

The navy/blue identity and Light/Dark/System controls remain. `resources/css/theme/tokens.css` now defines action foreground/background/hover pairs separately from link colors, stronger meaningful borders, readable placeholder and disabled colors, informational states, shared typography, spacing, and control height. This resolves the previous reuse of pale dark-mode link colors as backgrounds behind white button text.

`resources/css/theme/components.css` is the single shared foundation imported by both Vite theme entry points. It supplies scoped card/form/heading/action/progress patterns and small Filament adapters. Filament retains table, dialog, dropdown, focus-trap, pagination, and action mechanics. A provisional overly broad modal-width adapter was removed after screenshot inspection; built-in modal sizing remains authoritative.

| Semantic role | Light | Dark |
| --- | --- | --- |
| Canvas / surface | #f3f6fa / #ffffff | #0b1220 / #111827 |
| Main / supporting text | #24364a / #52657d | #e7eef7 / #adbbcc |
| Primary action / text | #245f9d / #ffffff | #93c5fd / #10243a |
| Primary action hover | #1b4a7c | #bfdbfe |
| Link | #245f9d | #93c5fd |
| Meaningful input border | #64748b | #8295af |
| Disabled surface / text | #e2e8f0 / #475569 | #26344a / #c6d2e3 |
| Error surface / text | #fef2f2 / #991b1b | #450a0a / #fecaca |

Default body/form text is 1rem; supporting instructions use .875rem. Typography uses the system Segoe UI/sans-serif family. Primary controls have a 2.75rem minimum height. Keyboard focus uses a visible solid outline. Small metadata is retained only where secondary; the progress descriptions no longer use reduced opacity that caused contrast failures.

The applicant sidebar uses a solid navy surface. Mobile progress and navigation wrap within the page rather than relying on page-wide overflow. Public home and staff sandbox preview now use theme-aware surfaces and existing theme preference behavior. Theme initialization is shared by Blade shells; storage restrictions are handled without breaking theme application.

The shared questionnaire renderer now gives its text/number/date controls explicit accessible names from the question legend and associates configured help text. Radio groups receive stable group names. Existing Livewire value bindings and conditional logic remain intact. Empty email-verification cells in the staff users table now say “Not verified,” giving their existing row links a readable name.

## Verification environment and isolation

- Local Playwright Chromium/Chrome and axe-core run against port 8017 and **osds_ui_browser_test** only.
- The new preparation script creates this reserved database if needed and migrates/seeds synthetic fixtures without dropping or resetting any database. Its database guard rejects other names. The router pins every browser request to that database and rejects cached configuration.
- Browser fixtures use fictional identities and synthetic references. Screenshots contain no keys, passwords, answer records from manual testing, or evidence locators.
- `osds_sandbox` and `osds_dev` were not reset, reseeded, or modified by this batch.
- The integrated browser connector was unavailable. Local Playwright provides the actual browser evidence in this report.
- Browser checks block external requests in the main screen matrix. An external avatar may therefore appear unavailable; this is not evidence that its service was verified.

## Actual contrast measurements

These measurements use computed foreground/background colors in the rendered browser, converted to sRGB relative luminance. They are not inferred solely from CSS class names. The main page matrix additionally uses axe's rendered contrast checks. Gradient-backed/transparent decorative elements are not treated as reliable numerical samples by the custom sampler.

| Rendered sample/state | Light ratio | Dark ratio | Result |
| --- | --- | --- | --- |
| Applicant primary action, normal | 6.57:1 | 8.72:1 | Meets normal-text target |
| Primary action, hover | 9.06:1 | 11.07:1 | Meets normal-text target |
| Primary action, keyboard focus while hovered | 9.06:1 | 11.07:1 | Text readable; solid 3px focus outline observed |
| Disabled action label | 6.15:1 | 8.21:1 | Readable; disabled controls are separately classified from WCAG contrast applicability |
| Login validation error | 7.60:1 | 11.16:1 | Meets normal-text target |
| Filament login validation error | 8.31:1 | 12.26:1 | Meets normal-text target; invalid-input boundary remains visible |
| Focused applicant input boundary against input surface | 4.85:1 | 8.38:1 | Exceeds 3:1 component-boundary target |
| Visible evidence-link input placeholder | 5.97:1 | 9.14:1 | Meets normal-text target |

The original browser baseline recorded dark-mode white-on-blue action failures and progress/helper contrast failures; shared text/action tokens correct those combinations. Axe also identified missing questionnaire input labels and unnamed empty table-cell links; both were corrected as described above.

`interaction-states.json` records computed input-border and placeholder measurements, theme transitions, keyboard results, and reflow. `measurements.json` records every inspected page/theme/viewport combination and all remaining axe findings without suppressing rules.

## Inspected screens and interaction coverage

The representative matrix covers applicant sign in, My applications, questionnaire editing, document links, staff sign in, Form Builder, and staff user tables in light/dark at 320, 375, 768, 1024, and 1440px. It also covers an actual Create questionnaire dialog and both CHEDRO review workflows at representative mobile/desktop sizes. Final counts and exceptions are recorded after the completed run below.

Additional browser checks cover:

- Invalid applicant login: visible alert, preserved email, masked password, readable error colors.
- Keyboard Tab from Password reaches Sign in with a visible 3px outline.
- System theme responds to simulated OS preference changes in both directions.
- Reduced-motion preference is recognized and transition duration is reduced.
- Review-stage reflow at 720px and 360px has no page-level overflow. These are equivalent CSS viewport/reflow probes; they do **not** claim native browser 200%/400% zoom was tested.
- Disabled/busy control styling is sampled with a temporary DOM state. This verifies presentation only; it does **not** claim a throttled network/submission recovery scenario was exercised.
- The real Filament create dialog is opened and dismissed with Escape; no questionnaire is created by that test.

## Automated regression gates

- Focused UI foundation/Form Maker tests: **18 tests, 89 assertions passed**.
- Complete PostgreSQL suite after shared changes: **83 tests, 944 assertions passed**, including SEC-001–004.
- Vite production build: passed.
- Laravel Pint: passed. `git diff --check`: passed, with line-ending advisories on existing working-copy files.
- Browser development dependencies: Playwright and axe-core added; npm reported zero dependency vulnerabilities at installation.

The browser helpers are `scripts/Prepare-UiBrowser.php`, `scripts/ui-browser-server.php`, `scripts/verify-ui-foundation.mjs`, and `scripts/verify-ui-states.mjs`. Run preparation with the project development shell, serve the dedicated router on 8017, then run the two Node helpers. The existing manual sandbox launcher is not used for these tests.

From a development shell with PHP, PostgreSQL and Node available:

```text
php scripts/Prepare-UiBrowser.php
npm run build
php -S 127.0.0.1:8017 -t public scripts/ui-browser-server.php
```

In a second development shell:

```text
node scripts/verify-ui-foundation.mjs
node scripts/verify-ui-states.mjs
```

The preparation command writes only isolated synthetic fixtures. It is unnecessary to rerun it for ordinary visual inspection once fixtures exist. Browser evidence is a point-in-time record; subsequent editing of fixture questionnaires can change later screenshots.

## Screenshots and visual review

Safe screenshots and machine-readable evidence are under `docs/screenshots/phase-3d7-b/`. Review the applicant editor, sign-in screen, Form Builder, CHEDRO intake, user table, and create dialog in both themes and mobile/desktop sizes. The final handoff links representative images.

| Representative screen | Light | Dark |
| --- | --- | --- |
| Applicant editor, desktop | [Screenshot](screenshots/phase-3d7-b/applicant-editor-light-1440.png) | [Screenshot](screenshots/phase-3d7-b/applicant-editor-dark-1440.png) |
| Applicant editor, mobile | [Screenshot](screenshots/phase-3d7-b/applicant-editor-light-375.png) | [Screenshot](screenshots/phase-3d7-b/applicant-editor-dark-375.png) |
| Form Builder, desktop | [Screenshot](screenshots/phase-3d7-b/form-builder-light-1440.png) | [Screenshot](screenshots/phase-3d7-b/form-builder-dark-1440.png) |
| CHEDRO intake, desktop | [Screenshot](screenshots/phase-3d7-b/chedro-intake-light-1440.png) | [Screenshot](screenshots/phase-3d7-b/chedro-intake-dark-1440.png) |
| Create questionnaire dialog | [Screenshot](screenshots/phase-3d7-b/staff-create-modal-light-1440.png) | [Screenshot](screenshots/phase-3d7-b/staff-create-modal-dark-1440.png) |

Please review the darker light-mode action blue, pale-blue/dark-text primary buttons in dark mode, larger helper text, solid navy navigation, stronger input boundaries, and vertically stacked mobile progress. These are local changes within the approved identity; no public deployment is performed. Batch C waits for the user's visual review.

## Limits and readiness

This batch is not a full WCAG conformance or human usability assessment. Screen-reader testing, native browser zoom, all possible table/dropdown/notification permutations, every catalogue/support resource, real staff usability, and the later role-specific workflow refinements remain outstanding. Existing plain-language/flow issues from Batch A are not claimed resolved by shared styling.

The mobile applicant navigation is readable and stays within the viewport, but remains tall and pushes task content below the initial screen. Its task/navigation simplification remains for Batch C after visual approval. Wide tables retain a local horizontal scrolling region; this is distinct from page-level overflow.

### Final Batch B result

The final production build was exercised in **82 page/theme/viewport combinations**: seven core screens at five widths in both themes, plus the actual creation dialog and two CHEDRO workflows at mobile/desktop widths in both themes. **Zero axe violations and zero page-level horizontal-overflow findings** were recorded in that matrix. Four actual creation dialogs were dismissed with Escape without creating a questionnaire. Additional light/dark public-home and actual staff-login-error screens also record zero axe/overflow findings. The separate interaction checks cover validation, focus, theme preference, reduced motion and reflow as described above.

**Verdict: CONDITIONALLY READY for Batch B visual acceptance.** The automated gates passed; no unresolved contrast failure was found in the tested screen matrix. This is not platform-wide production readiness or completion of A–H. Review the screenshots and the disclosed limits before approving Batch C. No additional brand change beyond the approved navy/blue foundation is proposed. Batch C and Phase 4 remain unstarted.
