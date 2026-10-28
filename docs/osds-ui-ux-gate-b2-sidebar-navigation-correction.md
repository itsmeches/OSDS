# Gate B2 — Sidebar navigation correction and disposable validation

**28 October 2026. PASS — verification correction and one disposable-browser validation.** Ten cases passed in ten fresh contexts, covering all nine requested categories. The duplicate-link case demonstrated unscoped ambiguity and successful sidebar-scoped navigation. Every negative case kept `authenticationConfirmed=false` and performed no navigation action. This supports separately authorized new protected package preparation; it does not authorize it or establish Attempt 07's historical cause.

## Exact files and implementation boundary

New reusable verification source:

- `scripts/uiux-b2-sidebar-navigation.mjs`: bounded dashboard metadata collector, authenticated-dashboard/sidebar assertions and a closure that clicks the same validated locator.
- `scripts/check-uiux-b2-sidebar-navigation.mjs`: focused offline source/collector checks.

Private files under `storage/app/private/uiux-consolidated-acceptance/gate-b2-sidebar-validation/`:

- `candidate-driver.mjs`: complete corrected reviewer-driver candidate derived from Attempt 07; not executed as a protected driver.
- `prepare.cjs`, `source-pins.json`: deterministic candidate construction and source hashes.
- `run.mjs`: fixed one-shot disposable runner using the candidate's exact navigation/confirmation block and the real helper with installed Playwright.
- `preserve.cjs`, `baseline.json`, `preservation.json`: filesystem-only preservation records.
- `browser-validation-01/result.json`: new, exclusively created validation evidence.

This report is also new. **No pre-existing file changed.** Attempt 07's pinned driver and evidence remain historical and unchanged. The corrected driver exists as the private candidate, rather than overwriting that executed driver. It retains the occupied Attempt 07 path/non-reuse checks and is not a new protected execution package. No successor attempt identifier, launcher, reservation or pins package was prepared.

| Source | SHA-256 |
| --- | --- |
| Historical Attempt 07 driver | `c431dc5541890f710a250b8f635d953795932eb91954ae7a4a2bbc633c7f6fb3` |
| Corrected complete candidate | `0222faf0daf5dacf06dfa09dd6be4596eff25646592975aa62c54dbdba5fb9ba` |
| Shared verification helper | `336a5d26c4afc4ce7738fda28eb9d68160e49be962edfba84a7db1093cca5692` |

## Corrected acceptance contract

The original 30-second destination poll is unchanged: exact origin and `/admin` pathname, with query/fragment ignored. Before login confirmation, the helper additionally requires:

1. Exactly one `App\Filament\Pages\Dashboard` component, zero `Filament\Auth\Pages\Login` components and no malformed component metadata. Other legitimate dashboard child components are allowed.
2. A visible, exact **Your workspace** level-one heading. The level avoids ambiguity with the task widget's separate level-two heading.
3. No form containing both email and password inputs; no Login component. Collection returns capped counts and booleans only, never snapshot contents or form values.
4. Exactly one `#fi-main-sidebar`, containing exactly one exact-role/name **Case-linked demonstration PDFs** link.
5. Link visibility, expected resolved href exactly equal to the same-origin `/admin/case-demonstration-documents` destination, and Playwright trial-click actionability. Query/fragment or alternate destination in this href is rejected. Relative and equivalent absolute same-origin hrefs resolve to the same destination.

The same locator object is retained inside the returned navigation closure. Before its single real click, count, href, current dashboard destination and fatal state are rechecked. The click races the existing failure signal. No `.first()`, `.nth()`, broad-text selector or direct destination navigation replaces the link. Trial actionability performs no navigation/submission.

Destination is revalidated after each dashboard/sidebar verification stage; observed fatal events take precedence. Fixed condition codes are `DASHBOARD_CONDITION_FAILED`, `SIDEBAR_CONDITION_FAILED`, `DESTINATION_CHANGED` and `EXISTING_FATAL_EVENT`, alongside the retained initial destination failure code. Unknown raw exception details are replaced by a fixed verification message.

The candidate preserves both readiness checks, one Sign in click, original request handler/fatal listeners, source guards and subsequent personnel URL/component/draft assertions. Authentication confirmation remains after destination, dashboard and authorized-link checks. A valid synthetic confirmation is an acceptance-state test, not real authentication or proof of reviewer identity 1192.

Original assertion defaults remain in force: 30-second destination bound and 5-second Playwright expectation bound. Trial click is explicitly bounded at 5 seconds; the existing real click uses the existing page bound. There are no sleeps, timeout increases, reloads, submission retries, permission changes or lifecycle calls.

## Offline results

Executed portable Node v24.20.0 with no application/framework bootstrap:

| Check | Actual result |
| --- | --- |
| `node scripts/check-uiux-b2-sidebar-navigation.mjs` | **PASS: 20 checks** |
| Existing diagnostic/policy/source suite | **PASS: 98 checks**, unchanged |
| Existing login-readiness suite | **PASS: 70 checks**, unchanged |
| Helper, candidate and browser-runner syntax | **PASS**, parsing only |
| Scoped whitespace | **PASS** |

Focused checks verify exact locator/href requirements, no unscoped link use in the candidate, shared click closure, bounded metadata shape, Login/form/malformed/duplicate-dashboard rejection, authentication ordering and fatal checks. The complete pre-navigation driver prefix is byte-identical after removing the new helper import, proving readiness, one-shot submission, request policy and listeners were preserved. The subsequent personnel assertions/reporting/cleanup suffix is identical. Collector checks use node doubles; browser results below supply real DOM validation.

## One disposable execution

Executed exactly once:

```powershell
node storage/app/private/uiux-consolidated-acceptance/gate-b2-sidebar-validation/run.mjs run
```

**Exit 0 / PASS.** Start **2026-10-28 08:22:37.948 UTC**, finish **08:23:05.625 UTC** (16:22:37.948–16:23:05.625 Asia/Manila). Recorded runtime: **Node v24.20.0**, installed **Playwright 1.64.1**, actual `browser.version()` **156.0.8078.4**. Ten fresh nonpersistent contexts closed; browser closure recorded. No scenario was retried or candidate changed after execution.

The runner checked candidate/helper/historical-driver hashes before launch. It verified loopback port 18107 was available and used `http://127.0.0.1:18107` solely as an in-memory route-fulfilled synthetic origin. No HTTP server started. Only GET `/admin`, the exact synthetic personnel destination and the deliberate request-failure control `/fatal` were admitted; other origins, ports, methods, queries and paths were rejected. WebSockets were closed and treated as violations. Service workers and downloads were disabled. No OSDS routes, state, credentials, profiles or sessions were loaded. Synthetic component memo identities were fixture metadata, not hydrated Laravel components.

| Case | Actual observation | Confirmation / completed navigation actions | Result |
| --- | --- | --- | --- |
| Two same-name links | Unscoped count 2 and visibility assertion rejected; sidebar-scoped candidate succeeded | true / 1 | **PASS** |
| Missing sidebar link | Exact count condition rejected | false / 0 | **PASS** |
| Hidden sidebar link | Visibility condition rejected | false / 0 | **PASS** |
| Wrong href | Exact destination condition rejected | false / 0 | **PASS** |
| Duplicate sidebar links | Sidebar link count condition rejected | false / 0 | **PASS** |
| Login component/form at `/admin` | Dashboard/Login-content contract rejected | false / 0 | **PASS** |
| Redirect away during verification | Destination revalidation rejected | false / 0 | **PASS** |
| Deliberate page error | Existing fatal signal aborted verification | false / 0 | **PASS** |
| Deliberate request failure | Existing fatal signal aborted verification | false / 0 | **PASS** |
| Valid dashboard/sidebar | Dashboard metadata, heading, unique visible/actionable link and real synthetic click passed | true / 1 | **PASS** |

The page/request negative controls intentionally generated failures and verified immediate fail-closed handling; neither expanded a protected error allowance. No unexpected framework error or resource-policy violation was observed. Expected assertion failures were negative-test outcomes, not hidden failures repaired for PASS.

The runner extracted the candidate block through its actual `authenticationConfirmed=true` assignment and used real Playwright polling/assertions. It then invoked the candidate's shared navigation closure. Losing assertions were terminated by context closure; after cleanup and a Node microtask drain, negative cases still had false confirmation and zero completed navigation actions. `Promise.race` itself does not cancel losing operations; these tests establish the tested cleanup/orderings, not universal scheduler cancellation.

Only closed phases, outcomes/codes, counts, timings, hashes and versions were persisted. No raw exceptions, DOM, snapshots, URLs with private data, screenshots, cookies, console dumps or unrestricted traces were retained.

## Preservation and limits

Fresh focused baseline: **4,864/4,864 unchanged**, zero modified/missing. Scope includes attempts 01–07, historical identity/evidence, credentials/ledger, protected sources/assets, prior diagnostic/disposable records, Batch G, all 81 previously inventoried PDF-named artifacts and ten historical diagnostic JSON files. No log, cache or historical file was restored, truncated or reconstructed. Historical identity-log append and missing temporary-metadata exceptions remain unchanged. This is not a whole vendor/node_modules inventory or fresh database-state verification.

The synthetic fixture does not establish real credential acceptance, sessions, reviewer identity, real dashboard hydration or personnel authorization. Duplicate-name ambiguity was demonstrated in a disposable browser; Attempt 07's historical exception subtype remains unrecovered. Destination/visibility sampling cannot rule out every transient change. The click closure rechecks its target, but does not make DOM evolution atomic. No production fix is claimed.

**Decision: PASS for considering separately authorized new protected package preparation.** The corrected candidate/helper must be incorporated into a newly reviewed package with fresh source pins, unchanged one-shot gates and guarded preflight/postflight under that separate authorization. Neither protected execution nor reviewer lifecycle activity follows from this validation.

**STOP.** No protected browser execution/authentication, PostgreSQL/Laravel bootstrap, Attempt 07 reuse, new protected attempt preparation/execution, COR/ACSA transition, directory recreation, migration/PDF, B3/B4, commit, push or deployment occurred.
