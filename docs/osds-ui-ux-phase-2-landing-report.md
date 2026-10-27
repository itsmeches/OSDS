# Phase 2 — Landing page and responsive design

**27 October 2026. Approved landing-page changes implemented. Database-free browser preview and static checks PASS. Laravel-rendered/authenticated application acceptance remains unverified. Stop for review.**

## Changes

The public landing page now explains the current synthetic-data demonstration rather than the earlier project foundation. It retains the CHED-inspired blue/navy palette, typography, design tokens, shared theme controls and existing sign-in destinations.

The page contains:

- CHED · OSDS institutional identity, About / Demonstration process / Access anchors, existing theme control and a skip-to-content link.
- The approved heading, “OSDS application workflows, in one place,” with a concise audience and purpose statement.
- A prominent static demonstration notice: synthetic data only, no official public applications and no valid certificate issuance.
- Separate applicant/representative and CHED personnel access cards describing their responsibilities.
- COR and ACSA capability descriptions without advertising future services or resolving the outstanding COR terminology question.
- A semantic six-step process overview that explicitly allows correction and reevaluation and describes authorized, explicit demonstration PDF generation.
- Assistance guidance and an institutional footer without fabricated contact information.

No public registration, processing-time guarantee, automatic approval, legal validity, seal, new branding asset or additional functionality was introduced. Assistance refers users to the coordinator who supplied their account; publication of actual support details remains a future content decision.

## Exact files

Approved runtime changes only:

1. `resources/views/welcome.blade.php` — page markup, copy, metadata, section anchors and skip link.
2. `resources/css/theme/landing.css` — new responsive stylesheet; every style selector is scoped beneath `.public-page`.
3. `resources/css/app.css` — one added stylesheet import, no other changes.

New verification/documentation:

- `scripts/verify-phase2-landing.mjs`.
- This report.
- `docs/screenshots/phase2-landing/`: eight standard PNGs, six supplemental PNGs, raw `results.json` and `preservation.json`.

Ignored private Git/hash records, standalone syntax tooling and build/check output are under `storage/app/private/phase2-landing/`. They are verification artifacts, not application code.

## Responsive behavior

The header wraps without a custom mobile menu. At narrow widths, the brand, section links and theme control stack naturally. At tablet width, the header uses two rows. At laptop/desktop widths, the header and introduction use columns. Access and capability cards become two columns from 48rem; mobile cards and their sign-in actions use the available width.

The main container is capped at 72rem with fluid horizontal padding. Headings and section spacing use `clamp()`, paragraph widths remain bounded and no hero height is fixed. The process remains a vertical ordered list with readable explanations. No animation was added.

| Width | Light | Dark | Measured result |
| --- | --- | --- | --- |
| 375px | PASS | PASS | Stacked layout; touch actions fit; no horizontal overflow |
| 768px | PASS | PASS | Wrapping header and paired cards; no horizontal overflow |
| 1024px | PASS | PASS | Column introduction/header; no horizontal overflow |
| 1440px | PASS | PASS | Bounded content width and balanced columns; no horizontal overflow |

Supplemental samples also PASS in both themes: **320px reflow**, **375px with 200% root text sizing**, and **1440px with 200% root text sizing**. The text-sizing check changes root font size in the browser preview only; it is not a certification of native browser zoom, every user font override or assistive technology.

Final light 1440px and dark 375px full-page images were visually inspected. The dark mobile page is intentionally scrollable vertically; no content is hidden to shorten the demonstration explanation.

## Verification and evidence

The driver reads the landing Blade source and existing shared theme components, replaces the route/Vite/component expressions with their known static equivalents and loads the actual built CSS/JavaScript through a local intercepted browser response. No application server, Laravel bootstrap, account, fixture, database or external request is used. Authentication links are inspected, not submitted.

This is a **source-derived static browser preview**, not an authenticated application test or proof of Laravel middleware/route execution. The unchanged source bindings remain `route('applicant.login')` and `url('/admin')`; the corresponding existing route definitions were inspected during planning.

| Check | Outcome |
| --- | --- |
| Standard width/theme matrix | **PASS:** 8/8 captures |
| Supplemental reflow/text sizing | **PASS:** 6/6 captures |
| Automated accessibility | **PASS:** zero axe violations and zero incomplete findings across all 14 samples; default rules retained with target-size explicitly enabled |
| Overflow and header layout | **PASS:** no measured page horizontal overflow or overlap between header grid children |
| Touch targets | **PASS:** measured non-skip controls at least 44px high and at least 24px wide; axe target-size also enabled |
| Keyboard skip behavior | **PASS** in all eight standard samples: first Tab reaches skip link, Enter focuses main, next Tab reaches applicant sign-in |
| Section anchors | **PASS:** targets exist; clicking About, Demonstration process and Access updates the expected section fragment |
| Theme control | **PASS:** Light / Dark / System pressed states and root theme match; System follows both light and dark media changes |
| Unavailable storage | **PASS:** blocked browser storage produces no uncaught errors and the Dark control still works |
| Browser errors | **PASS:** no uncaught page errors in measured samples |
| Frontend build | **PASS:** Vite build; bundler timing advice on the initial build is advisory |
| JavaScript syntax | **PASS:** Node syntax check of the new driver |
| Blade expression syntax | **PASS, bounded:** standalone Blade compilation with component-tag resolution disabled, followed by PHP lint of the compiled output; no application bootstrap |
| Formatting | **PASS:** full Pint `--test`; no formatter rewrites of existing application code |
| Whitespace | **PASS:** tracked diff and explicit new-file checks; existing CRLF advisories remain |
| Laravel-rendered component/helper resolution | **BLOCKED / not executed:** static preview does not exercise runtime Blade component or route-helper integration |
| Authenticated sign-in and workflow regressions | **BLOCKED / not executed:** outside authorized database-free verification; Phase 1 database-backed acceptance remains pending |
| Human screen-reader / full WCAG acceptance | **PENDING:** automated results do not establish complete accessibility |

Raw browser details and screenshot filenames are in [results.json](screenshots/phase2-landing/results.json). Existing B4 incomplete findings remain unresolved by this phase; a landing-page pass does not supersede them.

The complete matrix passed on its first execution. A second execution expanded evidence with explicit section-link clicks and header-overlap measurements. No accessibility rule, threshold, timing or application handler was weakened. Shared theme JavaScript remains unchanged.

## Preservation

Before editing, Git HEAD and the dirty working-tree state were recorded, followed by SHA-256 hashes for **2,618 baseline files**. HEAD remained `f7625a9de909a991013db6ce43c68bee65402ce0`.

[Preservation comparison](screenshots/phase2-landing/preservation.json) shows:

- Exactly two existing baseline files changed: the approved landing Blade view and the import-only application stylesheet.
- **2,616 baseline files byte-identical**, with zero missing baseline files.
- All **71 baseline PDF files** remain byte-identical, including source artifacts and private stored documents.
- Phase 1 runtime changes, reports and evidence; the ten published historical evidence JSON files; pending accessibility remediation; shared theme JavaScript; routes; workflow/authorization services; migrations; and the unrelated Batch G script are unchanged.

The new scoped stylesheet and new Phase 2 deliverables are additions and are not included in the baseline-identical count. No database was accessed or modified. No migration, fixture preparation, database-writing test, manual activation, commit, push or deployment occurred. Batch G remains CONDITIONAL CLOSE.

## Remaining issues and recommended Phase 3

Actual account access remains dependent on existing authorization and environment configuration; a public sign-in link does not activate an unavailable environment. Native zoom, screen-reader and human usability acceptance remain pending. COR naming and approved real-world support contacts remain business/content decisions, not changes made here.

For Phase 3, first prepare a bounded dashboard-orientation plan: permission-filtered task links, clear empty states and descriptions of existing counts. Do not invent operational metrics or infer authority from role names. Authenticated verification and any required isolated fixture preparation need separate approval before execution.

**Phase 2 stops here for review. Phase 3 has not started.**
