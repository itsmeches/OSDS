# Phase 3D.7 — Batch E OSDS Form Builder

**Date:** 20 October 2026 (Asia/Manila).
**Verdict: CONDITIONALLY READY for Batch E user review and development progression.** Automated gates passed. Human OSDS usability, native zoom and screen-reader verification remain pending. Batch F and Phase 4 remain unstarted.

## Changes and usability

| Area | Before | Implemented behavior |
| --- | --- | --- |
| Focused editing | Reused controls changed previous and selected questions. | Stable identity plus binding-location keys remount changed bindings; server selection uses stable keys. |
| Conditions | Unchanged saves discarded supported leaves or branches. | Locked complete trees survive unchanged saves/unrelated edits. Simple edits, explicit removal and protected complex rules are distinct. |
| Sections, choices, documents | Controls changed numeric binding locations; section editing was limited. | Keyed rows/editors, section title/description and accessible moves. Validation rejects invalid condition-source order. |
| Navigation | Updates closed advanced disclosures; outlines competed with narrow editors. | Native disclosure state retained; named keyboard controls collapse mobile outlines. One four-stage workflow remains. |
| Save/recovery | Local work and persisted state were difficult to distinguish. | Immediate dirty feedback, leave warning, confirmed-save state, preserved validation input and safe support references. |
| Uncertain actions | Lost responses left safe next steps unclear. | Block repeated consequential actions; inspect the exact saved pair in a new tab and explicitly copy RAM-only local work. No automatic replay. |
| Overview/creation | Ambiguous drafts implied misleading metrics. | Explicit pair choice with no inferred metrics. Existing creation creates a definition/initial pair under an approved module. |
| Labels | Persistence trimmed author labels. | Preserve exact case, spaces and underscores. No historical records rewritten. |

Details: [E-INT-002 remediation](phase-3d7-e-int-002-remediation.md), [E-INT-001 remediation](phase-3d7-e-int-001-remediation.md). Original failures remain in the issue register and screenshot directory.

## Browser interaction evidence

Chrome/Playwright uses only port 8017 and `osds_ui_browser_test`; external requests are blocked. These are automated interactions and developer screenshot inspection, not human usability testing.

- Native creation modal, approved-module pairing, choice validation, section moves and exact-label persistence.
- Required-field recovery and double-click saving with one version increment.
- Aborted requests, injected HTTP 503, response loss after persistence and cookie/session loss: local work retained without false success or automatic replay.
- Permission revocation blocks the open workspace, forged helper response and direct route. Admin role alone does not authorize OSDS authoring.
- Configuration check, temporary shared preview and one exact pair sent for Admin review despite double clicks. The in-review pair is read-only.
- Mobile keyboard outline expansion, rendered focus, and System media-preference changes.

**Original reproducer and 14 selection-isolation cases passed.** The clean 13-case run and supplemental Duplicate/section-description run are retained separately and combined with provenance in `selection-matrix.json`. Each applicable case checks client configuration, PostgreSQL and reload, including held model responses and reordered cross-tab responses with stale rejection. The condition journey passed 7 checks and 6 stable screens (zero axe/overflow/page errors). System light/dark changes and keyboard focus passed. Recovery/authoring rerun passed all 14 checks and 30 stable screens, with zero axe findings, page overflow or uncaught page errors. The original C-INT-001 reproducer and all 11 expanded applicant cases passed with zero failures; the applicant ordering adapter is unchanged. Its first visual pass caught sidebar fade contrast in two viewport transitions; `recovery-first-visual-run.json` is retained. Animation-complete remeasurement passed all 30 screens without axe findings. The first pass remains retained and is not retrospectively a zero-axe result; animation contrast is not certified.

## Visual and accessibility evidence

Screens cover light/dark at 1440px desktop, 768px tablet and 375px mobile. Developer inspection covered desktop light documents, mobile dark question editor, light mobile Send and dark desktop Review. Blocked external avatar requests appear broken in captures; this does not establish a production avatar defect.

| Screen | Light | Dark |
| --- | --- | --- |
| Question editor, mobile | [Image](screenshots/phase-3d7-e/question-editor-light-375.png) | [Image](screenshots/phase-3d7-e/question-editor-dark-375.png) |
| Documents, desktop | [Image](screenshots/phase-3d7-e/document-editor-light-1440.png) | [Image](screenshots/phase-3d7-e/document-editor-dark-1440.png) |
| Review, desktop | [Image](screenshots/phase-3d7-e/review-stage-light-1440.png) | [Image](screenshots/phase-3d7-e/review-stage-dark-1440.png) |
| Send, mobile | [Image](screenshots/phase-3d7-e/send-stage-light-375.png) | [Image](screenshots/phase-3d7-e/send-stage-dark-375.png) |
| Uncertain save, mobile | [Image](screenshots/phase-3d7-e/interrupted-save-light-375.png) | [Image](screenshots/phase-3d7-e/interrupted-save-dark-375.png) |

Computed text/background samples composite ancestor surfaces for headers, selected stages, editor inputs/labels, inline errors and header buttons. The 36 final stable screens across the two journeys have zero axe findings and page overflow. Computed sampled ratios are shown below.

| Sample (minimum across measured states) | Light | Dark |
| --- | --- | --- |
| Header | 12.34:1 | 15.18:1 |
| Selected workflow stage | 10.82:1 | 10.50:1 |
| Editor input/label text | 12.34:1 | 15.18:1 |
| Inline error | 6.15:1 | 12.03:1 |
| Header action text | 6.15:1 | 8.21:1 |

These samples do not certify animation opacity, every disabled/hover state, control boundaries or full WCAG compliance. System changes and a rendered 3px focus outline were confirmed. Native 200% zoom, screen readers and human first-time usability remain pending.

## Automated gates

| Gate | Result |
| --- | --- |
| Focused PostgreSQL FormMaker suite | 29 tests / 375 assertions passed |
| Complete PostgreSQL suite | 117 tests / 1,518 assertions passed on final runtime tree after formatting |
| Original and expanded C-INT-001 browser regressions | Passed; 11 expanded cases, zero failures |
| Laravel Pint | Passed after formatting fixture/test files |
| Production Vite build | Passed |
| Working-tree diff check | Passed; CRLF advisory only |
| Existing staged diff check | Pre-existing Markdown hard-break whitespace/blank lines and earlier CHEDRO script whitespace remain. User index preserved; no staging/reset/commit. |

No migration or historical repair. SEC-001–006, C-INT-001, locks, exact approval pairing and immutable publications/submissions remain. Neither `osds_sandbox` nor `osds_dev` was inspected or modified. Disposable browser publications are retired only through existing services inside the isolated browser database when needed.

## Limitations and review boundary

Recovery exists only in the open tab. Closing, crashing or reloading loses unconfirmed work. Copying is explicit; avoid shared clipboards/devices. Inspecting a saved pair never silently merges stale work. No sensitive browser storage or automatic authentication replay.

The existing Duplicate operation starts the new question without a condition; the source remains unchanged. Review applicability of the new copy before sending. A full complex-condition tree editor is not introduced; protected trees remain read-only except explicit removal.

Only Chrome is covered. Native zoom, screen readers, human OSDS usability, real external document access, every field/operator combination and unrelated role workflows are not certified. Before-fix reproduction covered question titles; related controls receive post-fix tests rather than a claim that all were reproduced defective.

Manual handoff: [Batch E checklist](phase-3d7-batch-e-manual-retesting.md). Pause for user review before Batch F. No Phase 4 or production authorization is implied.

## Reproduction and retained records

Run in the existing development shell with the isolated browser router on port 8017:

- `node scripts/reproduce-builder-selection-integrity.mjs`
- `node scripts/verify-builder-selection-matrix.mjs` (all 14 cases; `--from=14` runs the supplemental case only)
- `php scripts/Prepare-BuilderJourney.php prepare`, then `node scripts/verify-builder-journey.mjs`
- `node scripts/verify-builder-recovery.mjs`
- `node scripts/verify-builder-system.mjs` against the latest authorized fixture
- `node scripts/reproduce-applicant-answer-race.mjs` and `node scripts/verify-applicant-answer-matrix.mjs`

Do not retarget these scripts at a manual database/server. Browser suites share fixture files and must run sequentially. Machine-readable evidence is in `docs/screenshots/phase-3d7-e/`: `selection-integrity.json`, `selection-matrix.json`, `results.json`, `recovery-results.json` and `system-results.json`. Applicant evidence remains in `docs/screenshots/phase-3d7-c/answer-race.json` and `answer-matrix.json`. Native listener before-fix evidence and the first transient visual run are retained separately.