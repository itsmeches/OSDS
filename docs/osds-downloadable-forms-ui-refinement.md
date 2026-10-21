# Downloadable forms — UI/UX refinement

**21 October 2026. Scoped presentation refinement.** Based on the existing [UI design system](ui-design-system.md), questionnaire cards, applicant portal and shared semantic theme tokens. Existing local-system activation and permissions remain: OSDS manages; CHEDRO/applicants view and download only. No schema, authorization, file-validation, transaction, audit or download behavior changed.

## Changes

- Task introduction and explicit management/read-only context; catalogue count, format badges, available/withdrawn text and readable version/size metadata.
- Collapsible OSDS add/replace editor; initially open for an empty catalogue. Grouped fields, multiline instructions, native file chooser with format/size help, clear required labels and separated action footer. Existing publish/upload and validation behavior retained.
- Replacement opens the editor and moves focus to its title. Native disclosure open state survives Livewire uploads through `wire:ignore.self`; child inputs/validation remain reactive. No custom replacement file-picker or consequential replay.
- Short visible Download/Replace/Withdraw actions retain unique accessible names that include the visible label. Reader views contain no management controls.
- Version history is secondary; verification hashes are nested support details. Recovery guidance remains accessible through a labelled disclosure.
- Applicant cards use the existing portal pattern; responsive actions wrap, instructions retain line breaks and content uses shared light/dark colours. All CSS selectors are scoped to `downloads-*`; no global control restyle or new colour palette.

Affected presentation: `resources/views/filament/pages/downloadable-forms.blade.php`, `resources/views/applicant/downloadable-forms.blade.php`, and scoped rules in `resources/css/theme/components.css`. Browser fixture/runner changes are isolated tooling (`Prepare-DownloadableForms.php`, `verify-downloadable-forms-ui.mjs`). No manual/local catalogue records were changed during this refinement.

## Verification

- Final focused PostgreSQL/actual Filament matrix: **16 tests / 138 assertions passed**, covering existing upload/replacement/withdrawal, history, audit rollback, download authorization and CHEDRO mutation denial. Private output: `storage/app/private/downloadable-forms-ui-focused-final.txt`.
- Actual Chrome/Playwright, guarded local port 8032 against **osds_ui_browser_test**: **eight functional checks / 16 captures passed**, covering editor keyboard opening, original DOCX publishing, XLSX replacement, old-link denial, applicant download, CHEDRO read-only page, withdrawal, management-permission loss and session loss. [Results](screenshots/downloadable-forms-ui/results.json).
- Four view groups (editor, staff/history, applicant, CHEDRO), each Light/Dark at 1440px/375px. Default axe plus explicitly enabled target-size, no exclusions/allowances: **zero violations, overflow or uncaught page errors** in final sampled states. Existing notification dismissal/finite-animation stabilization precedes measurement; no timed-animation accessibility certification.
- Developer inspected [staff light desktop](screenshots/downloadable-forms-ui/staff-light-1440.png) and [editor dark mobile](screenshots/downloadable-forms-ui/editor-dark-375.png). Broken external avatars reflect fixture request blocking.
- First UI attempt caught accessible-name/visible-label mismatches; action names were corrected and history's unnecessary aria-label removed. Next attempt exposed editor collapse during upload; preserving the disclosure's own open attribute corrected that UI issue. Both failed results remain separately in `first-label-name-attempt.json` and `editor-open-state-attempt.json`; no persistence assertion was weakened or mutation automatically replayed.
- Pint `--test`, production asset build and diff/new-file whitespace checks passed. Private records: `downloadable-forms-ui-pint.txt`, `downloadable-forms-ui-build-final.txt`. This is asset compilation, not deployment. No new full-suite pass is claimed; earlier full-suite evidence is historical.

Human screen-reader, native zoom, touch-device and first-time usability acceptance remain open. Long-term file retention and previous feature limitations remain as recorded in the [feature report](osds-dynamic-downloadable-forms.md). Existing Batch G CONDITIONAL CLOSE/sign-offs are unchanged. No reset, permission grant, migration, manual data change, commit/push, deployment, certificate workflow, Batch H or Phase 4. The task-owned verification server is stopped after checks.
