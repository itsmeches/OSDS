# Phase 1 — Navigation and information hierarchy

**27 October 2026. Presentation changes implemented; authorized static and database-free component checks PASS. Authenticated application acceptance and PostgreSQL regression BLOCKED pending separate database-writing authorization. Stop for review.**

This phase preserves the blue/navy identity and existing architecture. It changes navigation metadata, applicant menu presentation, sidebar styles and short orientation text. No routes, permissions, services, workflow rules, database structures or PDF rendering contracts were changed. Batch G remains CONDITIONAL CLOSE with separate sign-offs pending.

## Before and after

### Staff

Before: Dashboard and operational/configuration pages were largely ungrouped. Questionnaires, questionnaire approvals, publications, application intake, assigned test reviews and document pages mixed daily tasks with configuration. Account resources used “User and access management”; audit used “Security and oversight”. Several document pages relied on default navigation ordering/icons. Existing advanced resources were deliberately hidden.

After: Dashboard remains first and ungrouped. Filament supplies navigation authorization, active state, group collapse, collapsed-sidebar tooltips and the mobile drawer. Groups follow the order below; Filament omits groups without visible authorized entries.

| Group | Item order |
| --- | --- |
| Applications and Reviews | Applications and decisions; Assigned evidence test reviews |
| Forms and Requirements | Application questionnaires; Questionnaire configuration reviews / My review submissions; Published questionnaires; Download blank forms |
| Demonstration Documents | Issuance request definitions; Case-linked demonstration PDFs; Unlinked layout previews |
| Institutions and Access | Institutions; Institution representatives; Users; Regional assignments; Application reviewer assignments; Evidence reviewer assignments; Applicant sandbox eligibility |
| Configuration and Audit | Audit history |

The configuration-review label still depends on existing review permission. Existing advanced module/form/version/requirement/provider resources remain hidden and retain their routes and contextual entry points. There are no new menu levels or empty placeholder features. Outlined Heroicons remain consistent with each task.

| Account responsibilities | Before | After, subject to unchanged permission checks |
| --- | --- | --- |
| OSDS / configuration staff | Mixed ungrouped configuration and document destinations | Forms and Requirements and Demonstration Documents clearly separated; administration grouped independently |
| Evaluator | Application intake and Assigned test reviews | Applications and decisions and Assigned evidence test reviews appear together |
| Chief | Shared Application intake destination | Same authorized destination, named Applications and decisions, with an explanation of recommendation responsibilities |
| Director | Shared Application intake destination | Same authorized destination, named Applications and decisions, with an explanation of final decisions |
| Combined permissions | Several unrelated ungrouped entries | Union of existing authorized destinations in the ordered groups; no role-name branching added |
| Read-only / restricted staff | Entries allowed by existing policies | Same policies; navigation reorganization grants no additional access |

This is a metadata/source assessment of those profiles. Their authenticated rendering has **not** been newly exercised. No separate evaluator/Chief/Director queue, workflow stage, permission or route was introduced.

### Applicants

Before: Unlinked layout preview → Downloadable forms → Issuance requests → Start application → My applications → Account and organization.

After:

1. My applications.
2. Start application.
3. Issuance requests.
4. Download blank forms.
5. Account and organization.
6. Secondary demonstration tools: Layout preview.

The secondary section states: “Try a sample layout. This does not submit an issuance request or create a case-linked document.” The preview is preserved. Existing sandbox visibility conditions are unchanged; outside that boundary the layout does not expose the application/request/account links.

Icons now occupy their own fixed-width SVG column while labels wrap normally. Current pages have a consistent active style and `aria-current="page"`; draft/history pages highlight their My applications parent without claiming the parent URL is the current page. The shared theme-switcher supplies a named group and existing pressed states. Local sidebar styles preserve navy-theme contrast. Desktop navigation can scroll within the sidebar on shorter displays.

Existing mobile disclosure and Escape/focus-return JavaScript is unchanged. Collapsed content uses the existing `display: none` behavior, removing its links from keyboard navigation. No custom drawer or animation system was introduced.

### Page orientation

Relevant headings match the revised menu labels. Short explanations distinguish application decisions, issuance definitions, case-linked PDFs and unlinked preview settings. The applicant blank-download heading matches its menu label. Existing sidebar destinations and Filament navigation provide return paths. No forms, dashboard statistics, actions or generation controls were redesigned.

## Actual verification

| Check | Result / limits |
| --- | --- |
| Pre-edit baseline | Git HEAD `f7625a9de909a991013db6ce43c68bee65402ce0`, dirty working tree recorded; 2,587 file hashes recorded before editing |
| PHP class/metadata inspection | PASS: 18 changed classes load through Composer without Laravel application bootstrap; group, label and order metadata captured |
| Existing PHP methods | PASS: token-based comparison finds existing non-navigation methods unchanged; panel navigation configuration and configuration-review navigation label are the intentional exceptions |
| PHP syntax | PASS for all 18 changed PHP classes |
| Full Pint `--test` | PASS; scoped formatting was applied to changed PHP classes before the check |
| JavaScript syntax | PASS for the new component verification driver |
| IDE diagnostics | Unavailable: diagnostic tool returned an unsupported-namespace error; no IDE-clean claim |
| Vite frontend build | PASS; no application server, database or deployment involved |
| Whitespace | PASS; Git retains existing CRLF conversion advisories |
| Applicant component, 375 / 768 / 1024 / 1440px, light and dark | PASS: eight final captures, zero default axe violations, zero enabled target-size violations, zero measured page overflow and zero uncaught page errors |
| Mobile keyboard interaction | PASS in the component at both mobile/tablet widths in both themes: collapsed initially; Enter opens; Tab reaches My applications; Escape closes and restores toggle focus |
| Human image inspection | Final dark 375px and light 1024px component images inspected |
| Authenticated staff drawer/collapse and applicant route states | **BLOCKED**: not executed against application records |
| Representative/combined permission rendering and direct-route denial | **BLOCKED** for runtime verification; authorization implementation and routes remain source-identical |
| Database-backed focused tests and full PostgreSQL suite | **BLOCKED** pending separate authorization; no historical test pass is substituted |

The browser driver renders the applicant layout's source-derived markup, actual built CSS/JavaScript and existing Heroicon assets into a static page. It shows the sandbox menu branch with inert placeholder destinations. It uses no account, application server, fixture or database. It is a **component inspection**, not a Blade-rendered authenticated application journey, staff-sidebar test or B4 rerun. Raw results and screenshots are in [phase1-navigation](screenshots/phase1-navigation/component-results.json). The driver is [verify-phase1-navigation.mjs](../scripts/verify-phase1-navigation.mjs).

The first driver execution required an explicit Playwright context for axe; that harness issue was corrected. The first completed matrix exposed theme-control contrast failures in four light samples; the next exposed a long-label layout conflict affecting sign-out in two desktop light samples. The shared theme component's public/auth styles and an old icon-span width caused those findings. Scoped sidebar overrides corrected them. Both failed matrices and captures remain separately under `first-theme-attempt` and `second-layout-attempt`; thresholds and axe rules were not weakened.

All eight final component samples retain axe **incomplete** `color-contrast` findings for the nontext information glyph. No complete WCAG or screen-reader acceptance is claimed. Existing B4 incomplete dropdown/glyph findings remain separate; this phase does not resolve vendor dropdown semantics. The applicant theme group now uses the existing shared semantic component, but authenticated revalidation remains pending.

## Preservation

[Preservation comparison](screenshots/phase1-navigation/preservation.json) finds exactly **26 existing source files changed**, **2,561 baseline files identical**, and **zero missing baseline files**. All **71 baseline PDF files**, including repository artifacts and private stored PDFs, are byte-identical. Existing historical evidence, including the ten published evidence JSON files, is unchanged.

The pending changes in `resources/css/theme/components.css` and `resources/views/filament/pages/form-builder.blade.php` are byte-identical to the pre-Phase-1 working-tree baseline. The unrelated `scripts/.batch-g-recovery-current.mjs`, source artifacts, B1–B4 reports and remediation evidence were preserved. Private Git/hash records and static-inspection tooling are under `storage/app/private/phase1-navigation/`; the safe metadata summary is [static-classes.json](screenshots/phase1-navigation/static-classes.json).

No database was connected to, inspected, migrated, reset, seeded or otherwise modified. No application activation, commit, push or deployment occurred.

## Exact files changed by Phase 1

Existing PHP files:

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/CaseDemonstrationDocuments.php`
- `app/Filament/Pages/DemonstrationDocuments.php`
- `app/Filament/Pages/DownloadableForms.php`
- `app/Filament/Pages/IssuanceDefinitions.php`
- `app/Filament/Pages/Questionnaires.php`
- `app/Filament/Pages/SyntheticApplicationIntake.php`
- `app/Filament/Pages/SyntheticEvidenceReviews.php`
- `app/Filament/Resources/ApplicantSandboxEligibilityGrants/ApplicantSandboxEligibilityGrantResource.php`
- `app/Filament/Resources/AuditEvents/AuditEventResource.php`
- `app/Filament/Resources/ConfigurationReviewRequests/ConfigurationReviewRequestResource.php`
- `app/Filament/Resources/InstitutionRepresentatives/InstitutionRepresentativeResource.php`
- `app/Filament/Resources/Institutions/InstitutionResource.php`
- `app/Filament/Resources/PublicationManifests/PublicationManifestResource.php`
- `app/Filament/Resources/SyntheticApplicationReviewerAssignments/SyntheticApplicationReviewerAssignmentResource.php`
- `app/Filament/Resources/SyntheticEvidenceReviewerAssignments/SyntheticEvidenceReviewerAssignmentResource.php`
- `app/Filament/Resources/UserRegionAssignments/UserRegionAssignmentResource.php`
- `app/Filament/Resources/Users/UserResource.php`

Existing presentation files:

- `resources/css/filament/admin/theme.css`
- `resources/css/theme/applicant.css`
- `resources/views/layouts/applicant.blade.php`
- `resources/views/applicant/downloadable-forms.blade.php`
- `resources/views/filament/pages/case-demonstration-documents.blade.php`
- `resources/views/filament/pages/demonstration-documents.blade.php`
- `resources/views/filament/pages/issuance-definitions.blade.php`
- `resources/views/filament/pages/synthetic-application-intake.blade.php`

New deliverables: this report; `scripts/verify-phase1-navigation.mjs`; and new safe evidence under `docs/screenshots/phase1-navigation/` (eight final PNGs, final raw component results, preservation/class summaries and two retained eight-capture failed matrices). Ignored private baseline/check scripts are verification artifacts, not application code.

## Remaining work and Phase 2 recommendation

Before declaring Phase 1 fully accepted, separately authorize database-backed verification: representative/combined permission menus, direct-route denials, authenticated staff collapse/mobile drawer, active draft/history navigation, focused regressions and the complete PostgreSQL suite. Automated fixture/migration operations require their own explicit authorization. No manual database activation is implied.

Heading and navigation wording changes can require browser-test locator maintenance after review; workflow handlers must remain real and persistence assertions must remain intact. Do not rewrite historical harnesses or evidence just to accommodate labels. Human keyboard, screen-reader, zoom and usability review remain pending.

Recommend Phase 2 address the landing page's outdated foundation wording, responsive entry points and concise explanation of demonstration capabilities. Dashboard redesign, new operational metrics and workflow changes remain separate. **Phase 2 has not started.**
