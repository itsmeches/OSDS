# SEC-008 — Regional assignment edit boundary

**20 October 2026. Status: targeted security and regression gates passed. The completed Batch F handoff is recorded separately.**

## Root cause and scope

The generic regional editor treated record visibility as mutation authority and inherited direct Eloquent persistence. Creation checked self-delegation and target-account authority, but updates did not. The retained real Filament regression originally persisted an Admin's own extended end date (one test, six assertions, one failure). No historical exploitation or manual-testing record assessment is claimed.

The institutional representation editor has the same generic persistence pattern. The targeted approval explicitly covers this shared root cause; both editors now invoke the same transactional boundary.

## Existing rules reused

- Explicit assignment permission, active identity, effective scope, no self-delegation, and `AccountGovernance::canManageTargetRole` remain authoritative. Role names do not establish rank.
- Existing effective nationwide grants remain valid scope alternatives.
- A metadata read is not permission to change delegated authority. Read admission remains policy-protected; Save independently enforces current mutation authority.
- Ordinary authorized date amendments remain possible. Identity bindings and attribution cannot be changed through a date edit. Existing `transferRegion` performs revoke plus a new grant; generic rebinding is not a replacement for that service.
- Existing grant/revoke services remain separate. Revoked or expired rows cannot be reopened through the generic date editor. No revival policy is added.
- No new permissions, migration, historical correction, or authentication/session redesign.

## Implementation

`AccessGovernance::amendAssignment` locks accounts, authority rows and the edited grant, reloads the actor/target, applies current delegation and original scope, rejects terminal grants, checks the expected record fingerprint and validates date ordering. Dates are normalized to UTC before Eloquent serialization. An altered target/region/attribution/verification/revocation field is rejected rather than silently discarded. Authorized model changes retain the existing audit observer.

The shared `EditsDelegatedAssignment` trait reauthorizes reads on hydration, captures a locked version fingerprint, checks raw form fields before schema dehydration and routes persistence through the service. Confirmed Save acknowledges only the row returned from its own locked transaction, without rereading another writer's newer version; a deterministic interleaving regression covers this acknowledgement boundary. Target-authority checks avoid caching full User aggregates on the public record, and real helper returns are checked for email/credential exclusion. stale forms retain local date input with an inline reopen instruction. Binding selectors are disabled on edit as explanatory presentation only; forged fields remain server-rejected.

Both policies now distinguish viewing from updating and require current target authority for updates. Self metadata can still be visible when existing read scope permits it; a real Save request fails. No raw user aggregate or credentials are added to component state.

## Files

- `app/Services/Authorization/AccessGovernance.php`
- `app/Policies/UserRegionAssignmentPolicy.php`, `InstitutionRepresentativePolicy.php`
- Both assignment editor pages and their shared `EditsDelegatedAssignment` trait
- Both assignment form schemas
- Original `PolicyRefinementTest` and `tests/Feature/AssignmentEditSecurityTest.php`
- Isolated `Prepare-AssignmentSecurity.php` and `verify-assignment-security.mjs`

## Verification checkpoint

The original exploit now receives 403 and PostgreSQL retains the original effective end instant. The first seven-method matrix passed; a first full PostgreSQL run passed **132 tests / 1,711 assertions**. Additional two-editor and explicit-offset cases were added afterward, so this is not yet a final-tree full-suite claim.

Final expanded assignment plus SEC-007 matrix: **18 tests / 216 assertions passed**. Final runtime PostgreSQL suite: **135 tests / 1,734 assertions passed**, including previous security/condition-preservation regressions. Global Pint, production Vite build and working-tree diff check passed. Pint normalized imports in the retained regression without changing its transport/persistence assertions. Pre-existing staged changes and staged whitespace remain preserved.

The focused Chrome journey passed **six actual interactions and four stable screens**: own-grant denial; unauthorized target denial; authorized native date-field/button save; stale second-tab denial; an older committed response delivered after a newer save followed by stale-write denial; and scope revocation after mount. Denied attempts retain PostgreSQL dates and assignment audit counts. Screens at 1440px/375px in light/dark have zero reported axe violations, page overflow or uncaught errors. See `docs/screenshots/phase-3d7-sec008/results.json` and `stale-{light,dark}-{1440,375}.png`. Developer screenshot inspection includes mobile dark; this is not human acceptance or a complete accessibility assessment.

Initial harness attempts used an incorrect native control selector and revoked a fixture through the session-invalidating model hook; the final revocation probe keeps the disposable session alive to test scope reauthorization independently. A subsequent screenshot run caught a theme transition at 1.09:1; final stable-screen checks wait for finite animations. Contrast during timed transitions is not certified. External avatars are blocked by the local-only browser request guard.

The original applicant browser reproduction and all 11 expanded cases passed. The original Form Builder isolation reproduction and all 14 expanded cases passed. These are actual reruns on the targeted security changes. Browser runs use only `osds_ui_browser_test`; PHPUnit uses only `osds_test`. Neither manual-testing database has been inspected or changed.

## Limits

No historical grant or timestamp repair; no production access certification; no human, screen-reader or native-zoom acceptance. Database-row locks and version checks protect this editor against stale requests; this is not certification of arbitrary external SQL clients or a general redesign of every identity-governance operation. Batch G/H, Phase 4 and deployment remain excluded.


## Final Batch F checkpoint

The final full suite passes 140 tests / 1,815 assertions. The assignment browser rerun again passes six checks/four screens with zero reported page errors. Ordinary Batch F adds explicit read-only date-edit guidance and hides its Save control when mutation authority is absent; the original forged/real Save still fails at the server boundary. No delegation predicate or service exception was relaxed. Human acceptance remains pending; see the Batch F report. Pause before Batch G.
