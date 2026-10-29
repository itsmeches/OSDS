# Gate B2 — Attempt 13 Livewire request-contract assessment

**29 October 2026. Source assessment PASS.** The policy excludes legitimate automatic traffic from the configured **`App\Filament\Widgets\WorkflowOverview`**: lazy initialization and subsequent statistics polling. This is a confirmed coverage gap, **not confirmation of Attempt 13's exact rejected request**. No policy/application change or protected execution occurred.

## Existing contract and retained evidence

Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation. The [validator](../scripts/uiux-b2-reviewer-policy.mjs:3) requires one component, parseable snapshot with memo ID/name, and exactly one call. It admits only:

- Exact Notifications `__dispatch`, parameters `['notificationsSent', []]`, no updates.
- Exact Login `authenticate`, empty parameters and only email/password/remember updates, while phase is `login`.
- The existing personnel `advance` contract only under an explicit object phase. Attempt 13 supplies only string `login`/`idle` phases, so this is not an active lifecycle allowance.

During sidebar verification the policy phase is still **`login`**; the diagnostic label `login-verifying` is separate. The [route adapter](../scripts/uiux-b2-first-fatal-candidate.mjs:68) also rejects every second non-notification consequential request after login. Therefore simply adding a new allowed widget return kind would encounter that second gate; it must not be disguised as a notification or weaken the one-login rule.

Attempt 13 retained successful exact Dashboard metadata and heading verification, then first cause **`COMPONENT_ACTION_BLOCKED`** before cleanup. The category groups disallowed component/method/arguments/updates; it stores none of those identities. It does not prove a second login, lifecycle attempt or harmless refresh. No actual sidebar navigation click occurred. Sidebar checks are DOM/assertion/actionability operations, not an explicit Livewire business call; an automatic request can coincide with that stage.

## Source-supported automatic requests

| Candidate | Exact source path and trigger | Expected contract / policy conflict |
| --- | --- | --- |
| **WorkflowOverview lazy mount — strongest initialization candidate** | [Application widget](../app/Filament/Widgets/WorkflowOverview.php:16) extends StatsOverviewWidget. Panel discovers widgets; [Page.php:585](../vendor/filament/filament/src/Pages/Page.php:585) passes their default properties into Livewire mounting. [CanBeLazy.php:9](../vendor/filament/support/src/Concerns/CanBeLazy.php:9) defaults true; [Widget.php:100](../vendor/filament/widgets/src/Widget.php:100) adds `lazy=true`. WorkflowOverview does not override it | [SupportLazyLoading.php:192](../vendor/livewire/livewire/src/Features/SupportLazyLoading/SupportLazyLoading.php:192) emits an automatic intersection-triggered `__lazyLoad` with **one encoded mount-snapshot argument**. Handler at line 139 restores mount parameters. No policy branch admits this widget/method |
| **WorkflowOverview polling — confirmed ongoing candidate** | [StatsOverviewWidget.php:17](../vendor/filament/widgets/src/StatsOverviewWidget.php:17) uses CanPoll; [CanPoll.php:7](../vendor/filament/widgets/src/Concerns/CanPoll.php:7) defaults **5s**. [Widget view:17](../vendor/filament/widgets/resources/views/stats-overview-widget.blade.php:17) emits expressionless `wire:poll.5s` | Installed [Livewire client:17322](../vendor/livewire/livewire/dist/livewire.js:17322) chooses `$refresh`; [line 7414](../vendor/livewire/livewire/dist/livewire.js:7414) sends it through `fireAction`. A single `$refresh` call, ordinarily empty parameters/updates when nothing changed, is not admitted. Exact browser payload/update state remains unobserved |
| **Notifications event delivery** | Base layout mounts `Filament\Livewire\Notifications`; [NotificationsServiceProvider.php:56](../vendor/filament/notifications/src/NotificationsServiceProvider.php:56) dispatches `notificationsSent` when session notifications exist; [listener:34](../vendor/filament/notifications/src/Livewire/Notifications.php:34) consumes them | Client listener transport uses `__dispatch` ([livewire.js:15718](../vendor/livewire/livewire/dist/livewire.js:15718)). The ordinary exact empty-array form is already allowed. Different event/payload/update shapes or bundling could fail; none was retained here. No broad dispatch exemption is justified |

WorkflowOverview is discoverable and its `canView()` admits active users with questionnaire view/review/publication permissions, present in reviewer 1192's documented manifest. This establishes source eligibility, not browser session identity. Its [getStats()](../app/Filament/Widgets/WorkflowOverview.php:50) builds permission-filtered **count queries and links**, with no explicit protected-business mutation in that method. Widget authorization [rechecks `canView()` before mount/hydrate, including lazy mounts](../vendor/filament/widgets/src/Concerns/CanAuthorizeAccess.php:9). “Business-read-only” does not mean every framework/session effect is a read-only database transaction.

Lazy decoding is security-sensitive: [SupportLazyLoading.php:230](../vendor/livewire/livewire/src/Features/SupportLazyLoading/SupportLazyLoading.php:230) restores the signed snapshot through `fromSnapshot()` and checks the mount container's component binding. An arbitrary encoded string or any-component `__lazyLoad` allowance is not an acceptable substitute.

## Other components and user-triggered behavior

- **TaskOrientation** explicitly disables lazy loading ([line 25](../app/Filament/Widgets/TaskOrientation.php:25)); its catalogue links do not establish another automatic request.
- **Topbar/Sidebar** expose `refresh-topbar` / `refresh-sidebar` listeners ([Topbar.php:22](../vendor/filament/filament/src/Livewire/Topbar.php:22), [Sidebar.php:22](../vendor/filament/filament/src/Livewire/Sidebar.php:22)). Event-driven `__dispatch` would conflict with the policy, but no initialization publisher was found in reviewed application/Filament source. Listener existence alone is not evidence of an automatic request.
- **User menu/sidebar link** use client-side dropdown/Alpine behavior and ordinary navigation ([sidebar item:39](../vendor/filament/filament/resources/views/components/sidebar/item.blade.php:39)). User-selected actions can invoke Livewire action machinery; those are not blanket read-only allowances. No such user-menu/business action was performed by the prepared driver.
- **Database notifications** support lazy loading, polling and notification actions, but default **off** ([HasNotifications.php:13](../vendor/filament/filament/src/Panel/Concerns/HasNotifications.php:13)); this panel does not enable them. They are not a supported active cause.
- Search/input changes, user-selected actions, alternate events, protected personnel operations and malformed/unexpected calls remain possible rejected shapes in general. They are not identified by this checkpoint and must stay denied unless individually authorized.

Livewire also supports bundling and other payload forms. Multiple components or zero/multiple calls would normally produce the diagnostic's count/shape categories rather than this observed action category; they should not be conflated with its first cause. No timing argument proves which widget request happened: its intersection and polling timer origins were not retained.

## Smallest safe next correction or diagnostic

**A narrow source-level correction candidate is justified without knowing the historical request**, because the configured statistics widget predictably requires currently excluded automatic traffic. That does not justify calling it the Attempt 13 fix or applying a generic framework-method allowance.

The smallest defensible candidate would explicitly identify **WorkflowOverview only**, separately handle its verified lazy-mount and `$refresh` shapes, deny unrelated updates/actions, preserve server snapshot/component binding and authorization, and restrict the allowance to the reviewed Dashboard/document/phase context. It would need an explicit business-read-only traffic kind and corresponding route-accounting rule, preserving exactly one Login submission and the exact notification exemption. No additional permissions, business methods or arbitrary `__dispatch` should be admitted.

Before integration, focused offline/disposable validation must establish actual installed payload shapes, lazy-call uniqueness, refresh arguments/updates, multi-component rejection and fail-fast precedence. No policy change is authorized by this report. Disabling lazy loading or polling would instead change application behavior and move work/timing; it is not justified merely to silence the guard.

If the exact rejected contract must be identified first, the minimal diagnostic is a **closed component family plus call family and argument/update-shape counts/booleans** at the existing rejection boundary: known WorkflowOverview lazy/refresh, known notification/topbar/sidebar event families, or OTHER. Never retain arbitrary names, raw parameters, encoded snapshots or bodies. This remains a proposal, not an implemented observer.

**One recommended next action:** separately authorize preparation and focused offline/disposable validation of the narrowly scoped WorkflowOverview lazy/poll contract candidate, including route-accounting changes and strong negative tests, without preparing another protected attempt. Further runtime attribution remains necessary to claim the exact historical cause; it is not necessary to demonstrate this independent source coverage gap.

## Verification and preservation

**PASS: 46 source-text/hash/retained-evidence checks**, including all **29 pins**, original manifest, configured widget inheritance/defaults, installed client transport and the immutable first-fatal/stage/authentication observations. No framework/policy module, browser or database code was executed.

Fresh focused preservation: **8,167/8,167 inventory entries unchanged**, zero changed/missing/unavailable. Attempts 01–13, preparation/session/evidence bytes, original credentials/identities, current avatar and protected sources/assets, logs/caches and historical artifacts remain preserved. The known 370-byte Laravel error suffix remains explicit and unchanged; no new log activity occurred. The inventory retains path aliases and is not whole-cluster or whole vendor/node_modules verification.

New artifacts are this report and private filesystem/static-check records outside historical attempt directories. No application/policy/source correction, protected browser/authentication, database access, service operation, retry, Attempt 14 preparation, lifecycle action, migration/PDF, B3/B4, commit, push or deployment occurred. **STOP.**
