# Gate B2 — UI-based office and personnel setup

**27 October 2026. BLOCKED by a verification-driver selector defect before any configuration save.** The synthetic `osds` account successfully authenticated through the real login form. No new directory revision or personnel version was created. B2 configuration setup is incomplete; B3/B4 remain NOT AUTHORIZED. Batch G remains CONDITIONAL CLOSE.

## Scope and actual result

The authorization allowed only the existing synthetic `osds` and `config-reviewer` logins, one fictional primary-office entry producing one new directory revision, and two primary-region personnel versions through the existing UI. The probe region was to remain unmapped. No applicant workflow, PDF or unrelated configuration action was permitted.

| Step | Actual result |
| --- | --- |
| Foundation and protected-configuration preflight | **PASS**, read-only |
| Browser server / endpoint isolation | **PASS**, loopback port 8044, guarded browser database only |
| Real `osds` login | **PASS** in the second recorded browser execution |
| Mounted directory revision check | **FAIL in driver**, wrong Livewire component selected |
| Add demonstration office | **NOT RUN**; click/save not submitted |
| New directory revision ID | **None**; actual latest remains existing revision **25** |
| COR / ACSA personnel creation | **NOT RUN**; new version IDs **none** |
| `config-reviewer` login | **NOT RUN** |
| Personnel review / publication | **NOT RUN** |
| Read-only post-stop verification | **PASS:** foundation/configurations intact, zero new personnel versions |

The application was not observed rejecting an office or personnel mutation. The blocker is verification infrastructure, not an established runtime or authorization defect.

## Preflight and database identity

Executed `php scripts/Inspect-UiUxB2Personnel.php preflight` before authentication. The bootstrap validates the fixed endpoint before PDO, verifies actual identity before Laravel bootstrap, and guards each later PostgreSQL connection. It rejects cached configuration, unexpected target/URL overrides, other drivers and aliases. Environment files and application configuration were unchanged.

Preflight and post-stop inspections used explicit repeatable-read **READ ONLY** transactions and rolled back. B0 comparison retained its exact source projections with the reviewed transaction-local historical timezone; protected row digests used UTC.

| Check | Observed result |
| --- | --- |
| Allowed and observed target | **`pgsql`, `127.0.0.1:55432`, `osds_ui_browser_test`** |
| Identity observations | **61/61 PASS**, zero mismatches |
| First identity | **10:18:43 UTC**, backend PID **2236**, before application bootstrap |
| Last identity | **10:25:20 UTC**, backend PID **16144**, post-stop verification |
| Forbidden databases | **Not accessed**: no `osds_dev`, `osds_test` or fallback connection |

The configured and actual target were verified before server start, real login and any potential write-capable connection. The server used the task-specific `uiux_b2_personnel_session` cookie, private file sessions and array cache/mail. No injected authentication or impersonation was used.

### Foundation and configuration checks

Fresh database reads before authentication and after stopping confirmed:

- **18 accounts**, with all exact amended permission unions and expected active/inactive states; **154 direct permission pivots**, no role assignment or nationwide grants.
- **2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments and 20 reviewer assignments**, with their committed ledger relationships unchanged. All six eligibility grantors remain the synthetic `osds` identity.
- Original B0 projection values match exactly for the six fixed COR/ACSA roots and original personnel structures. Publication **128** remains the expected unretired staff preview.
- Original **16 published personnel mappings** remain unchanged.
- Existing directory history and all protected row digests match the foundation snapshot. Latest revision **25** contains **25 complete office entries**; none was edited.
- No namespace office already existed and no primary/probe personnel version occupied either intended slot at preflight. Post-stop inspection still found **zero** personnel versions for these new regions.
- Counts for applications, state history, decisions, issuance requests, documents, permission definitions, roles and nationwide grants remained unchanged.

The planned actors' existing permissions satisfy the directory-management and personnel-management/review/publication requirements at source and foundation-record level. No additional permission was added or proposed.

## Browser executions and exact blocker

Browser driver: `scripts/verify-uiux-b2-personnel.mjs`. All screenshots/traces and session evidence are private. External requests were blocked. POST filtering allowed only the expected login/directory/personnel Livewire actions, not application, request or PDF actions.

### First execution: endpoint-filter infrastructure failure

The driver initially expected `/livewire/update`. The actual login page advertises the same-origin generated endpoint **`/livewire-6d26c714/update`**. The route filter rejected the login POST before forwarding it, and the asynchronous route-handler error terminated the driver. No successful login or configuration mutation occurred in this execution.

Recorded this failure separately under `browser-attempt-01/`. A read-only fetch of the login page confirmed its generated endpoint. The verification-only filter was corrected to read and validate the page-advertised same-origin Livewire endpoint, retaining the action allowlist. No application handler or authorization check changed. This correction preceded any submitted login/configuration action; no consequential mutation was replayed.

### Second execution: real login, then snapshot-selector failure

Under separate retained `browser-attempt-02/` evidence, the `osds` login form succeeded through normal authentication and navigated to the dashboard. The driver then opened the existing demonstration directory page.

Before filling/saving, it attempted to inspect the mounted page state using:

```javascript
page.locator('[wire\\:snapshot]').first()
```

The first Livewire component in Filament's DOM is **`Filament\Livewire\Topbar`**, not the directory page. Its snapshot has no `expectedDirectory`, so the driver's equality assertion failed with:

```text
Mounted directory revision/key mismatch
```

The retained trace also contains the actual **`App\Filament\Pages\DemonstrationDocuments`** snapshot with **`expectedDirectory: 25`** and **`officeKey: null`**, matching the read-only preflight. Thus the observed mismatch was caused by selecting the wrong component, not by a changed directory revision.

The driver stopped before filling the office fields or clicking **Add demonstration office**. It did not continue to personnel creation, reviewer login or publication. No uncertain configuration commit exists. The successful authenticated context was closed and its private trace retained; the task-owned port-8044 server was stopped. No third browser execution occurred.

## Post-stop database verification

Ran only `php scripts/Inspect-UiUxB2Personnel.php post-stop`, a fixed read-only inspection, after stopping UI actions. Actual foundation rows, B0 projection values, protected histories, business/permission counts and empty new personnel slots matched preflight.

| Requested setup artifact | Actual resulting state |
| --- | --- |
| New fictional primary office | **Absent** |
| New office-directory revision | **0 created**; existing latest **25** unchanged |
| Probe-region office/personnel mapping | **Absent**, as required |
| New COR personnel version | **0 created** |
| New ACSA personnel version | **0 created** |
| New configuration lifecycle history/audit linkage | **None**, because no save occurred |

No direct service calls or database inserts were used to fabricate the missing setup. No committed history was deleted or silently repaired.

## Evidence, files and preservation

New private stage directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-ui-personnel/`

Contains preflight/post-stop inspection records, identity observations, private sessions, baseline/Git records, snapshot diagnostics and both browser execution directories. The second trace was examined offline to identify the Topbar-versus-page snapshot distinction. Passwords were read from the existing private B2 credentials file, never supplied in shell arguments, reports or public logs. Tracing began only after successful login; session/DOM evidence remains private.

New verification-only files:

- `scripts/UiUxB2PersonnelBootstrap.php` — browser-only fixed target/environment guard and private sessions.
- `scripts/Inspect-UiUxB2Personnel.php` — fixed read-only foundation/configuration/partial-state checks.
- `scripts/uiux-b2-personnel-server.php` — task-only loopback router.
- `scripts/verify-uiux-b2-personnel.mjs` — restricted real-login/configuration UI driver.

The existing preparation helper and foundation ledger/credentials were not modified. Baseline HEAD remains **`f7625a9de909a991013db6ce43c68bee65402ce0`**.

| Preservation / static check | Result |
| --- | --- |
| Existing file baseline | **26,854 unchanged; zero changed or missing** |
| Original PDFs | **73/73 unchanged**; all **81 current PDF-named files** unchanged |
| Historical diagnostic JSON | **10/10 unchanged** |
| Prior reports, attempts, inspections, foundation ledger/credentials and Batch G artifacts | **Unchanged** |
| Phase 1–3 runtime source/evidence | **Unchanged** |
| Existing generated logs / compiled Blade caches | **Unchanged**, not cleared or restored |
| Known missing temporary fake-upload metadata | Prior exception retained; not reconstructed |
| PHP syntax / scoped Pint / Node syntax / whitespace | **PASS** for new tooling; existing CRLF advisories remain |

Outside the task-private directory, new files before this report consisted only of the four verification tools. New private sessions/traces and inspection outputs were inventoried separately. No PDF or application source artifact was generated or edited.

## Recommended bounded follow-up and stop

Recommend review and authorization for a verification-only selector correction: locate the directory page's own Livewire component, verify its `memo.name` and mounted revision/key, and retain the original pre-save assertion. Do not remove that safety check or accept an arbitrary first component. Use a new non-overwriting browser attempt and revalidate current records before any save. The request-path correction is already retained; the snapshot-selector correction has **not** been implemented.

**Gate B2 configuration setup remains BLOCKED; it is not yet ready for B3.** The foundation remains intact and no runtime fix is indicated by this evidence. Await review before further UI execution.

**STOP. No office/personnel creation or publication, applicant workflow, issuance request, PDF generation/download, B3/B4, runtime change, commit, push or deployment occurred.**
