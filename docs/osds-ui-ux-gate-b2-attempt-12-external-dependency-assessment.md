# Gate B2 — Attempt 12 external-origin dependency assessment

**29 October 2026. Assessment PASS.** The configured Filament user-menu path has a confirmed source-supported remote avatar dependency. It is the strongest candidate for an external-origin rejection during Dashboard loading. **The exact Attempt 12 request remains unidentified:** its retained diagnostic contains no request origin, resource type or source element. No application fix, policy change, browser, authentication or successor attempt was prepared or executed.

## Confirmed Attempt 12 facts

Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation. Reviewed the [execution report](osds-ui-ux-gate-b2-attempt-12-execution.md), immutable first-fatal checkpoint and current installed/application source.

- Exact `/admin` destination passed; Dashboard readiness was unfinished and authentication remained false.
- First fatal was **`EXTERNAL_ORIGIN_BLOCKED`**, sequence 1, 25,684 rounded monotonic ms, phase `login-verifying`, current-page pathname `/admin`, cleanup false.
- Shared readiness stopped at 15,068 ms, before its 30,000 ms maximum. Its last metadata observation had no matching Dashboard; heading/sidebar/personnel acceptance was not reached.
- The later requestfailed callback occurred during cleanup and did not replace the first cause.

The reason proves that a routed request's parsed **origin** differed from the permitted base. “External” can include another scheme/port on loopback; it does not inherently identify an Internet host. The diagnostic pathname describes the current page, not the rejected request. No hostname, request method, resource category, source element or missing DOM was reconstructed.

## Actual page and asset construction

1. [bootstrap/providers.php](../bootstrap/providers.php) registers the application provider and `AdminPanelProvider`. The [panel](../app/Providers/Filament/AdminPanelProvider.php:57) selects `admin`, standard Login, the application Dashboard, discovered widgets and `resources/css/filament/admin/theme.css`. It does not set a custom font provider, avatar provider, remote logo/favicon, plugin list or top-navigation mode.
2. Application [Dashboard](../app/Filament/Pages/Dashboard.php) inherits Filament's Dashboard and changes its heading/subheading. Installed `Pages/Page.php:88` selects `filament-panels::components.layout.index`; that wraps `layout.base` and renders the default topbar, sidebar and page slot. No `resources/views/vendor/filament-panels` override exists in this checkout.
3. [The base layout](../vendor/filament/filament/resources/views/components/layout/base.blade.php:78) renders registered styles, the selected Vite theme, font preloads/styles, then the existing head theme script. Body composition includes the layout/page content, notifications and Filament scripts. These are source ordering facts, not recovered Attempt 12 HTML.
4. `HasTheme.php:52` invokes Laravel Vite for the configured CSS entry. The existing manifest resolves it to **`public/build/assets/theme-BOAVZ6VW.css`**. The manifest's portal CSS and `resources/js/app.js` entries also exist, but their existence does not make them imports of this Filament CSS entry. No application-provider registration adding that portal JS to this Dashboard was found.
5. `FilamentServiceProvider.php:123` registers bundled Inter fonts, panel/core scripts and the default theme. Asset classes generate local public paths through `asset()` unless explicitly supplied a remote path. Local published Filament/Alpine component assets and Livewire's registered script/update routes are the normal loading path. Their URLs can still depend on asset configuration, discussed below.
6. Discovered application widgets are `TaskOrientation` and `WorkflowOverview`. The panel's two custom Blade hooks are scoped to listed resource pages, not Dashboard. Reviewed application providers/widgets/partials contain no registered remote stylesheet/script/image dependency. Ordinary navigation anchors are not automatically fetched resources merely because their destinations exist.
7. Laravel Boost is installed as a development dependency, but project configuration disables its browser log watcher and defaults Boost to disabled. Its watcher-gated response injection is not a demonstrated external Dashboard dependency. Filament Echo setup is gated by broadcasting configuration; no project broadcasting configuration supporting a remote Dashboard connection was identified. Dependency names or remote literals inside unused library code do not establish a request.

## Source-supported external-origin inventory

Labels below identify source families, **not observed runtime destinations**. No private URL, query value or environment value is exported.

| Family / source | Registration and resource category | Can affect `/admin`? Conditions and origin evidence |
| --- | --- | --- |
| **REMOTE_AVATAR_DEFAULT** — [HasAvatars.php:10](../vendor/filament/filament/src/Panel/Concerns/HasAvatars.php:10), [UiAvatarsProvider.php:13](../vendor/filament/filament/src/AvatarProviders/UiAvatarsProvider.php:13) | Default provider generates a fixed public remote-service image URL with name-derived initials and display parameters | **Yes, on the standard authenticated user-menu path when fallback is used.** No panel override exists. The provider's returned HTTPS origin is explicitly remote in source; no request to it was made by this assessment |
| **USER_AVATAR_OVERRIDE** — [FilamentManager.php:575](../vendor/filament/filament/src/FilamentManager.php:575) | A `HasAvatar` implementation or filled `avatar_url` can supply an image before fallback | Data/configuration-dependent. An absolute supplied URL could be external. OSDS User does not implement `HasAvatar` or declare a custom avatar method; no local avatar field/accessor was found in reviewed model/migration source. Runtime attribute values were not queried, so fallback use for reviewer 1192 is not independently established |
| **ASSET_HOST_OVERRIDE** — Laravel `RoutingServiceProvider.php:66`, framework `config/app.php:62`; Filament `Css.php:35`, `Js.php:174`, font preload generation | `ASSET_URL`/asset-root configuration can relocate styles, scripts, fonts and Vite output | Conditional generated URLs, potentially a different host/scheme/port. Current `.env`, `.env.testing` and assessment-process `ASSET_URL` are absent/empty. Those observations are not a historical environment snapshot. No source override forcing an external asset root was found |
| **VITE_DEVELOPMENT_ORIGIN** — Laravel `Vite.php:389` and hot-file branch | A hot file can produce dev-server module/client URLs on another origin | Conditional; **`public/hot` is absent now**. Existing manifest points to local build files. This does not recover historical hot-file contents; no hot server was contacted |
| **REMOTE_FONT_PROVIDER** — installed Google/Bunny provider classes, `HasFont.php:83` | Remote CSS and subsequent font requests if selected/customized | **Not selected by this panel's inspected configuration.** No `->font(...)` override exists; default provider is LocalFontProvider. Presence of Google/Bunny classes is not registration |
| **EXPLICIT_REMOTE_ASSET / HOOK** — Filament asset classes support remote paths and render hooks support custom markup | Remote script/style/image if application/package configuration registers one | Mechanism exists, but no additional active remote Dashboard registration was found in the reviewed application chain. It is not evidence that arbitrary third-party code injected a resource |
| **BROADCASTING_CONNECTION** — base layout line 154, Echo configuration gate | A configured broadcaster can initiate connections | Configuration-dependent; no enabling project configuration was identified. WebSocket coverage differs from HTTP route interception; no such connection or rejected request was observed here |
| **REDIRECT_OR_GENERATED_URL** — standard LoginResponse intended/panel redirect, Laravel URL generation | A generated URL or redirect could target a different origin | Source supports conditional redirects; configured panel/default APP_URL is loopback and the prepared bootstrap forces the expected APP_URL. No source proves an external redirect in this run. Session intended values and response chains were not inspected/reconstructed |

### Why the default avatar is an actual candidate

This is not based only on finding a provider class:

- `HasTopbar.php:11` defaults the topbar on; `HasUserMenu` defaults the menu on and chooses topbar placement when present.
- The layout index mounts the configured topbar. [topbar.blade.php:276](../vendor/filament/filament/resources/views/livewire/topbar.blade.php:276) renders the user menu under its authentication/menu checks.
- [user-menu.blade.php:60](../vendor/filament/filament/resources/views/components/user-menu.blade.php:60) renders `avatar.user` with lazy loading. That component calls `getUserAvatarUrl()` and passes its result to the support avatar component, which emits an `<img>`.
- `getUserAvatarUrl()` uses the configured default provider when no user-specific avatar is filled. The panel inherits **UiAvatarsProvider**; its `get()` constructs an external URL from public provider source. It is a resource URL, not merely an inert hyperlink.

This confirms a potential remote request in the normal page composition. Lazy image loading and conditional rendering mean it does not prove when a browser requested it, which user was present, or that this image initiated Attempt 12's fatal event. A last zero-root sample cannot exclude resources encountered between polling observations. Conversely, this conditional source path must not be used to infer successful historical authentication.

### Fonts, CSS and scripts: rejected assumptions

Installed `HasFont` defaults to **Inter Variable**, **LocalFontProvider** when no custom family is configured. `LocalFontProvider` emits no remote link when its URL is blank. Registered bundled fonts supply local CSS/preloads; no change to fonts is justified by an assumption that Filament necessarily uses Google Fonts.

The current built theme contains **43 CSS `url(...)` references, all data URIs**, with zero remote URL references in that scan. Published Inter CSS contains **7 relative font URLs**, zero remote URL references. The source theme imports vendor/local CSS files. These findings support local/inline delivery in the inspected artifacts; they do not certify every asset in every installed package or prove historical browser URL resolution.

Livewire's frontend asset source allows configured asset-URL overrides, but normal endpoints are locally registered. Attempt 12 validated the advertised same-origin update endpoint before submission and recorded its permitted POST response. This does not make all possible future script/redirect URLs inherently same-origin. Alpine's existence, bundled dynamic component loaders and portal JS files do not by themselves establish an external HTTP dependency.

## Ranking and causal limits

1. **Default remote avatar — strongest source support.** Actual configured fallback and default user-menu rendering path lead to a remote image. This is a confirmed source-level incompatibility with strict same-origin execution when that resource is requested. Exact Attempt 12 attribution remains unconfirmed.
2. **Generated asset-origin mismatch — conditional, weaker current support.** Framework mechanisms exist, but current asset override/hot-file checks are negative and observed local resources returned 200. Different runtime URL generation or a data-supplied avatar cannot be excluded from retained evidence.
3. **External redirect/custom injected dependency — possible mechanisms, no positive active registration evidence.** No particular redirect, broadcaster, plugin or resource is implicated by the first-fatal category.

Remote Google/Bunny fonts are **not supported as the configured default cause**. No specific external stylesheet/script/CDN or missing local asset was confirmed. Do not relabel the historical blocker as an avatar request, font timeout or authentication defect.

## Policy and security implications

The unchanged candidate's `createAttributedRoute()` applies `url.origin !== base` before method/endpoint handling. The base is exactly **`http://127.0.0.1:8044`**. The context HTTP routing guard does not exempt images, fonts, stylesheets, scripts or document requests; a routed remote avatar is rejected just like any other mismatching origin. There is no resource-category exception. This is distinct from claiming HTTP routing covers every browser protocol or WebSocket.

A legitimate UI dependency can therefore conflict with this intentionally strict acceptance policy. The confirmed source configuration exposes such a conflict; the guard behaved as designed. Allowing external requests wholesale, disabling fatal handling or interpreting the block as authentication PASS would be unjustified.

The remote avatar implementation includes name-derived initials in its generated parameters. Fetching it can disclose those initials and network metadata to a third party. No actual initials, query contents, credentials or session material were read/exported here. First-party avatar delivery avoids that dependency and privacy exposure while preserving the user menu and strict policy.

## Remediation proposals — not implemented

| Supported finding | Smallest safe option | Conditions / limits |
| --- | --- | --- |
| Default remote-avatar fallback | Configure an application-owned avatar provider returning a bundled same-origin static avatar or locally generated initials image | Preserve accessible avatar/menu behavior, authentication and permissions. Validate exact URL origin and avoid remote calls/storage mutation. Prefer this focused configuration change over vendor edits or a policy exception |
| A future proven asset-root/dev-origin mismatch | Correct that specific first-party URL-generation/build configuration | Do not clear caches, rebuild assets or alter runtime configuration without authorization; current evidence does not justify those operations |
| A future proven unnecessary remote hook/resource | Remove or serve that dependency locally | Identify actual registration and purpose first; do not remove unrelated integrations speculatively |
| Remote fonts | Already local in the inspected default | No font-provider change is justified by present findings |

If an external dependency is indispensable, an exception would require explicit separate authorization and evidence identifying the public source registration, exact origin/path family, resource category, required method, redirects and transmitted data. It must be narrowly bounded and must not permit credentials, arbitrary destinations or broader Livewire/lifecycle actions. None is proposed for execution here; a same-origin avatar is the smaller security-preserving option.

**Runtime attribution remains necessary to identify the exact rejected request in a future occurrence.** Existing Attempt 12 evidence cannot recover it, and another observation cannot retroactively prove its missing hostname/source element. A future separately reviewed diagnostic could retain only a closed public-source family and resource-category enum at the existing rejection point, without raw URLs or queries. No such diagnostic or new attempt was implemented. Runtime attribution is not required to establish that the inspected default avatar itself is externally hosted and incompatible with the strict policy when fetched.

## Static checks and preservation

**PASS: 49 focused source/hash/manifest/evidence assertions**, using private `gate-b2-attempt-12-external-dependencies/check.mjs check`. They verify all 26 pins and manifest, panel/provider/menu/avatar chain, default local font registration, absence of the reviewed override/hot paths, manifest asset existence, bounded CSS reference classification and the immutable fatal code. Environment output contains only `ABSENT_OR_EMPTY` classifications, never values. No application/framework module was imported or executed.

All **26 source/framework pins** match; final manifest remains `8d6113a3021069cee7a966382f1422110bc17306270afaa64cf1fadec0fb6a46`. Shared readiness and first-fatal candidates are unchanged. Source reads and filesystem-only scripts are not a rendered-page or network test.

Fresh focused preservation: **8,081/8,081 files unchanged**, zero changed/missing/unavailable. Scope includes Attempt 12's entire execution/session evidence and first-fatal file, prior attempts/preparation/disposable evidence, credentials/ledger and identity records, relevant application/framework/public sources including selected avatar/font/Vite sources and build/font artifacts, logs/caches and historical PDF/diagnostic inventories. Credentials/environment/session files were hashed privately; no values were exported. No data directory or database records were accessed. Historical exceptions remain unchanged; no evidence/log/cache was cleared, restored or truncated. This is a focused inventory, not a whole vendor/node_modules or database-state assertion.

New artifacts are this report and private source-check/baseline/comparison records outside immutable attempt directories. No protected source, policy, asset or prior report was modified.

**One recommended next action:** separately authorize a minimal same-origin avatar-provider correction candidate with focused offline/disposable validation, preserving the strict route policy and all acceptance predicates. Treat it as removal of a confirmed external dependency, not a proven fix for the unidentified Attempt 12 request; do not authorize another protected login on this assessment alone.

**STOP.** No source correction, policy relaxation, browser/authentication, OSDS HTTP request, database login/SQL, Laravel bootstrap, service operation, Attempt 13 preparation/execution, lifecycle action, migration/seeding/repair, PDF/applicant workflow, B3/B4, commit, push or deployment occurred.
