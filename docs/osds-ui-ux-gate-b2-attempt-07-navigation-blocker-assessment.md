# Gate B2 — Attempt 07 navigation-blocker assessment

**28 October 2026. Source assessment PASS. Classification: incorrect test assertion (unscoped single-link assumption); exact historical failure cause remains UNRESOLVED.** The application supplies the expected label and permission contract, but can render that exact link twice on the dashboard. The smallest justified correction is to target the intended sidebar navigation, rather than change application labels or reviewer permissions. No correction or new attempt was prepared or executed. Protected acceptance remains BLOCKED.

## Confirmed authentication contract

Inspected installed framework source and application configuration, applying OSDS Verification Gates, Livewire Browser Diagnostics and Authorization Review.

- [AdminPanelProvider](../app/Providers/Filament/AdminPanelProvider.php) uses the standard Login, `admin` path, discovered pages/widgets and Filament Authenticate middleware. No custom Login, SPA, top-navigation or custom navigation builder is configured there.
- Installed `filament/filament/routes/web.php` places registered protected pages inside `getAuthMiddleware()`. The inherited Dashboard route is `/`, hence `/admin` under this panel. `App\Filament\Pages\Dashboard` supplies the heading **Your workspace**.
- Installed `Http/Middleware/Authenticate.php` checks `Filament::auth()->check()`, redirects unauthenticated access to the Login URL, and rejects panel access when `User::canAccessPanel()` fails. [User](../app/Models/User.php:29) requires active status and `platform.admin.access`. `EnsureAccountIsActive` logs out an inactive account and aborts 403.
- Standard Login credential/panel failures throw validation errors; successful authentication regenerates the session and returns `LoginResponse`, which redirects to the intended URL/panel URL. Login's mount redirects an already authenticated user away from Login.

**The inspected standard route does not intentionally render the protected dashboard for an unauthenticated user, nor render the Login form at `/admin`.** An unauthenticated request for `/admin` should redirect to `/admin/login`; failed credentials normally leave Login showing validation errors. A lost session after a successful redirect could cause a subsequent protected request to redirect to Login. These are source contracts, not proof of what Attempt 07 rendered. No retained body, component identity or redirect chain establishes its final content; HTTP 200 and a sampled pathname remain insufficient.

A supported privacy-safe future acceptance contract is exact destination **plus** one expected `App\Filament\Pages\Dashboard` component (using the existing memo-name-only inspection approach), visible expected workspace heading and a uniquely scoped authorized navigation link. Reject a Login component/form, unexpected component or fatal event; recheck destination around UI verification. Emit only counts/booleans and closed outcomes. This demonstrates access to the middleware-protected UI; it does not independently identify user 1192. Retain fresh isolated context, original fixed credentials and reviewer preflight, and do not claim a new browser-level identity proof without one. No session inspection, identity endpoint or application change is proposed.

## Navigation registration and the actionable mismatch

| Contract | Confirmed source |
| --- | --- |
| Page | `App\Filament\Pages\CaseDemonstrationDocuments`, discovered under `app/Filament/Pages` |
| Label/title | **Case-linked demonstration PDFs**, exact spelling matches the driver |
| Sidebar group / sort | **Demonstration Documents** / 20 |
| Route | Default class-name slug `case-demonstration-documents`; panel URL `/admin/case-demonstration-documents`; default route name `filament.admin.pages.case-demonstration-documents` |
| Registration | Inherited `shouldRegisterNavigation=true`; Page registers the item only if `canAccess()` succeeds. No local hidden/parent/cluster override |
| Location | Standard sidebar (`#fi-main-sidebar`), not configured top navigation |
| Additional location | Dashboard **Forms and Demonstration Documents** task-card group, linking to the same page |

[CaseDemonstrationDocuments](../app/Filament/Pages/CaseDemonstrationDocuments.php:24) declares the label. Installed `Pages/Page.php:127` gates navigation registration and builds `NavigationItem::make(static::getNavigationLabel())`; `HasRoutes` derives the route. Page mount/hydration also enforce `canAccess()` through `CanAuthorizeAccess`, so visibility is not the only security boundary.

Crucially, [TaskOrientation](../app/Filament/Widgets/TaskOrientation.php:48) independently registers the same label and destination. It is non-lazy, discovered by the panel and included by the inherited Dashboard widget path. Its `taskGroups()` filters each destination with the same `canAccess()`. The [widget view](../resources/views/filament/widgets/task-orientation.blade.php:13) renders a separate anchor whose name is the task label; the icon is aria-hidden and its descriptive paragraph is outside the anchor. The standard sidebar anchor also takes the same navigation label. Both links are valid application UI.

The driver uses an **unscoped** `getByRole('link', {name:'Case-linked demonstration PDFs', exact:true})` for both visibility and the later click. Exact name matching does not establish uniqueness across these two locations. Installed Playwright's single-element locator/assertion machinery rejects ambiguous matches with strict-mode errors. Thus the intended authenticated dashboard can violate the test's implicit one-link contract even when navigation and permissions work correctly. Changing only the visibility assertion would leave the subsequent click ambiguous.

Sidebar collapse/group state can additionally affect visibility: installed sidebar/group views use `x-show` and persisted sidebar state. Groups default to uncollapsed; the panel enables desktop sidebar collapse. A fresh context weighs against inherited collapse state, but no Attempt 07 DOM/state record proves its actual presentation. This is a secondary uncertainty, not a reason to clear storage, force state or add waits.

## Reviewer authorization

The page's `canAccess()` requires all of:

1. Authenticated active user.
2. Synthetic account.
3. `ApplicantSandboxGuard::allowed()`.
4. `platform.admin.access`.
5. At least one of `documents.view`, template manage/review/publish or personnel manage/review/publish.

The sandbox guard accepts only local/testing/sandbox environments, excludes production, and outside testing requires the configured database name to contain `sandbox` or `test`. Attempt 07's guarded source configures sandbox and `osds_ui_browser_test`, satisfying that source-level condition. No database connection was opened during this assessment.

The documented reviewer 1192 manifest contains `platform.admin.access`, `documents.view`, `documents.personnel.review` and `documents.personnel.publish`; it therefore supplies the relevant permission alternatives without any new grant. Attempt 07's historical fresh preflight/postflight verified the active synthetic reviewer/foundation manifest. No separate role-name, region, assignment or feature flag is required for this page's admission or its two navigation entries. Case listing/generation and service mutations have their own authorization boundaries; page visibility does not grant those operations and none was tested here.

No source evidence supports an additional navigation-only permission requirement or a reviewer grant correction. Actual session user and effective runtime page access remain unconfirmed.

## Attempt 07 evidence and classification

Reviewed its preserved [execution report](osds-ui-ux-gate-b2-attempt-07-execution-report.md) and bounded `browser-result.json`. Destination check completed at sequence 29 (20,732.466 ms); link check began at 30 (20,732.498 ms); failure occurred at 33 (25,741.644 ms), with `AUTHORIZED_UI_CONDITION_FAILED`, safe pathname `/admin` and authentication false. No link-completion marker or personnel-page step exists. No page error or explicit request-policy violation was retained.

**Primary source classification: Incorrect test assertion — high confidence that the unscoped uniqueness assumption is unsound. Historical causal attribution: unresolved.** The source predicts duplicate named links on an authorized dashboard, but Attempt 07 retained neither match count nor original assertion subtype. A strict-mode failure is a strong actionable explanation, not a recovered exception. The roughly five-second interval does not distinguish strictness, absence or visibility failure. Do not relabel authentication as successful or infer the missing DOM.

Navigation registration/label mismatch and reviewer permission mismatch are not supported by inspected source. Authentication not established remains an acceptance status, not a confirmed cause of the blocker. No application navigation defect was established.

## Minimal proposed correction and validation

Under separate source-change authorization, scope **both** the existing visibility assertion and later link click to the intended existing sidebar container, keeping the exact role/name and requiring exactly one match with the expected same-origin `/admin/case-demonstration-documents` href. Do not use `.first()`, broaden the name, bypass visibility, replace navigation with a direct GET or change reviewer permissions. Keep destination checks and the proposed protected-dashboard component/heading checks; HTTP 200 or URL-only acceptance remains inadmissible. This is a verification-tool correction only, not an application fix.

Minimum tests before a new protected package:

- Offline: verify both link uses share the same scoped contract, exact href/origin, expected dashboard component, both readiness checks, single submission, fatal precedence and existing request policy.
- Separately authorized disposable browser: render both same-name links; demonstrate the unscoped ambiguity and scoped uniqueness. Cover sidebar missing/hidden, wrong href, duplicate links within sidebar, Login content at the target URL, redirect away and page/request errors. Failed assertions must leave authentication false. Use the actual installed Playwright behavior; do not claim these source checks replace it.
- Only after passing validation: separately authorize new package preparation and fresh pins, then separately authorize one guarded protected execution with unchanged drafts/roots, preflight/postflight and immutable prior evidence. No lifecycle authority is implied.

**A new protected attempt is not justified for execution yet.** A small source correction and focused disposable validation are justified; no new attempt, adapter or patch was prepared here.

## Offline checks and preservation

Filesystem-only command:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-07-navigation-assessment/check.cjs test
```

**PASS: 23 focused static source/retained-evidence checks.** They check page label/group/access clauses, discovery and middleware registration, non-lazy task-card duplication/rendering, navigation generation, both unscoped driver uses, retained failure status and installed strict-mode implementation presence. They do not execute PHP authorization, render Blade, compute browser accessible names or run Playwright. No framework/application module was imported. No database-backed or browser suite ran.

Fresh focused filesystem baseline: **4,859/4,859 unchanged**, zero modified/missing. It extends the prior execution baseline with current gate-private evidence (including Attempt 07), relevant sources/package bundle and the execution report. Prior attempts, identity records, original credentials/ledger, protected application/framework assets, all 81 previously inventoried PDF-named artifacts, ten historical diagnostic JSON files and Batch G remain preserved. Credentials/session files were hashed only. No logs/caches/evidence were cleared, restored or reconstructed; prior append/missing-metadata exceptions remain historical exceptions. This is not a whole vendor/node_modules or fresh database-state assertion.

New artifacts are this report and private filesystem check/baseline/result/preservation records under `gate-b2-attempt-07-navigation-assessment/`. No existing driver, guard, policy, credential, application or framework file changed.

**STOP.** Next authorization should cover only the scoped navigation/authenticated-UI verification correction and its focused offline/disposable validation. No browser/authentication, PostgreSQL/Laravel bootstrap, Attempt 07 reuse, new attempt preparation/execution, lifecycle action, directory recreation, migration/PDF, B3/B4, commit, push or deployment occurred.
