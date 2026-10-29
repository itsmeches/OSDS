# Gate B2 — Attempt 14 WorkflowOverview integration and preparation

**29 October 2026. READY at this snapshot for separately authorized reviewer login/read-only-navigation execution.** Source integration, focused offline checks, all source pins and one guarded read-only preparation inspection PASS. The validated WorkflowOverview candidate is wired into the actual route handler. No protected browser, server or authentication ran. Attempt 14's execution directory remains absent. COR 47 and ACSA 48 remain draft/lock 1; no lifecycle authority is implied.

## New package and exact identities

Applied OSDS Verification Gates, Livewire Diagnostics and Evidence Preservation. Reused Attempt 13's architecture and the unchanged [22-case validated candidate](osds-ui-ux-gate-b2-workflow-overview-fixture-correction-validation.md). Historical attempts, manifests and evidence were not edited.

| New file under `scripts/` | SHA-256 |
| --- | --- |
| `Run-UiUxB2Attempt14.mjs` | `d3290d133f69813177417e9113201b707d87540b8e732a50cfd5fbe06799492c` |
| `UiUxB2Attempt14Bootstrap.php` | `8ffebdb738a1d2e5407e8febdc6afcac7fe472f7f7e6089676d672d4a5eb26b8` |
| `Inspect-UiUxB2Attempt14.php` | `6949162cfce52db128977bdb03978653837e7266fcc269b622f07bd4790ffe68` |
| `uiux-b2-attempt-14-server.php` | `9fdf6ea1f31f5207175e9ce34997b68abdefff90bc85a98bb4f28c3e735b1494` |
| `verify-uiux-b2-attempt-14.mjs` | `7541470503e8809e6847648cc9264fad6b30673fbe1d3b9744995a67bb201803` |
| `uiux-b2-attempt-14-navigation.mjs` | `a20568c7b8ddea3a60b0ff7bda9202df7183ac38dbd7fef204d0394ecca524ae` |
| `uiux-b2-attempt-14-fatal.mjs` | `8f41ee07122dd27fed7fbb37e266a736ff0ded25855e71dcc89aeab86ac0bb55` |
| `uiux-b2-attempt-14-pins.json` | `f5734bb9bcf295ad4d93f0152df34e75a92e5a6547068211431ce9591a2864d8` |
| `check-uiux-b2-attempt-14.mjs` | `26ab438a143795ff8eeac1e800a651ba86a3d5d9fb4210aed512d44ff5624e36` |

The [new manifest](../scripts/uiux-b2-attempt-14-pins.json) contains the complete path/hash inventory: **30/30 matching source/framework/policy pins**. All **29 historical Attempt 13 pins** first matched their original manifest, SHA-256 `f5426fa2a5f8c01468de383b98ff612c275880c40580816b45c4013ff27e1e09`. The new contract substitutes only attempt-specific entry points and adds the validated candidate:

`scripts/uiux-b2-workflow-overview-policy-candidate.mjs`

`8eecd269b80509afa02485eda84d0808f76b1e7d3d9ad98ce12175f632561c6e`

The manifest retains prior diagnostic/readiness/avatar provenance and adds the exact passing 22-case result-file hash. The checker is separately hashed above, outside runtime dependencies. Future execution must verify the final manifest externally as well as all pins; no automatic repinning is authorized. These are selected dependencies, not a complete vendor/node_modules inventory.

## Actual runtime integration

Launcher, bootstrap, inspector, router, navigation and fatal-persistence modules are byte-identical to Attempt 13 after fixed attempt names, paths, constants and function identifiers. The driver has these focused changes:

1. Imports `createWorkflowDashboardObserver` and `createWorkflowRouteCandidate` from the validated candidate, replacing its old `createAttributedRoute` use.
2. Creates the page, then one observer with the actual page, fixed base, existing fatal flag and original submitted flag.
3. Registers **`context.route('**/*', createWorkflowRouteCandidate(...))`**, passing the actual endpoint/phase/submission accessors, Login-attempt marker, existing event marker, existing first-fatal function and that observer. Thus candidate validation and accounting govern routed requests; this is not an unused import or pin.
4. Disposes the observer during cleanup, after joining started navigation verification and before context closure. A disposal fault blocks the result and records the existing bounded cleanup flag.

The candidate remains unchanged: exact WorkflowOverview identity; single component/call; empty updates; document-bound current snapshot and lazy token; exact lazy/refresh arguments; prior Login submission; correct Dashboard/heading/document context. Widget traffic uses separate `workflow-lazy` / `workflow-refresh` kinds, one lazy and at most 12 refreshes per instance. It is not a notification exemption and cannot reset the Login flag. Server signed-snapshot and widget authorization protections remain authoritative. This policy does not independently verify a protected HMAC or establish reviewer identity.

The driver’s entire workflow from `login-get-start` through personnel assertions is byte-identical to Attempt 13 after attempt substitutions. It retains both readiness checkpoints, one fill per credential field, one Sign in click, exact permitted endpoint/origin/method, exact notification contract and one-shot Login accounting. Unauthorized lifecycle methods and unknown traffic remain blocked.

The shared 30,000 ms destination/root budget, exact Dashboard identity/count, Login/form/malformed rejection, exact visible level-one **Your workspace**, complete sidebar uniqueness/href/visibility/actionability and single validated click remain unchanged. Personnel URL/component and 47/48 draft-heading requirements remain unchanged. **Authentication confirmation still follows complete Dashboard and sidebar acceptance**, with the existing fatal check. Widget readiness, HTTP 200 and pathname equality cannot confirm it.

First-fatal wiring/persistence, closed categories, capture-before-signal, later-callback retention, exclusive evidence writes and persistence-failure blocking remain unchanged. Root-observation boundaries and closed schema remain unchanged. No application business logic, widget timing, local-avatar source, request allowance beyond the validated candidate or lifecycle action was added.

## Focused validation

| Executed check | Actual result |
| --- | --- |
| `node scripts/check-uiux-b2-attempt-14.mjs` | **PASS: 186 assertions / 10 integration cases**, 30 pins |
| WorkflowOverview candidate regression | **PASS: 62 checks**, ten negative contracts / eight route cases |
| First-fatal/shared-readiness replay | **PASS: 66 checks / 11 cases** |
| First-fatal suite | **PASS: 270 checks**, 36 policy comparisons / 12 route cases |
| Shared Dashboard readiness | **PASS: 65 checks / 11 cases** |
| Node syntax | **PASS**, launcher, driver, navigation and fatal modules |
| PHP syntax | **PASS**, bootstrap, inspector and router |
| Guarded preparation inspection | **PASS**, one invocation / exit 0 / rollback |
| Fresh browser execution | **NOT RUN** |

The integration checker executes the route/observer registration text extracted from the **actual new driver**, with synthetic observations/persistence and the real validated route candidate and new fatal wrapper. Cases cover valid Login/lazy/repeated refresh flow, duplicate Login, lifecycle method, unknown component/method, unexpected updates, external origin, failed Dashboard context, pre-existing fatal precedence and refresh cap. All retain false synthetic authentication confirmation. Static comparisons establish that original acceptance/personnel logic and copied security guards remain unchanged. The protected driver is never imported by tests.

Two initial checker development failures were confined to the test harness: its extracted awaited registration needed an async wrapper, and its direct fatal control initially bypassed the harness's simulated caller flag. Those fixture issues were corrected; no runtime candidate or protected package behavior was changed to pass them. No database/browser invocation occurred during those failures.

The previously passing **22/22 Chromium** result and candidate hash were verified, not rerun. Prior installed checksum checks remain retained provenance. These results validate integration wiring and synthetic contracts, not protected rendering or actual credential acceptance.

## Single guarded read-only preparation

Exclusive preparation evidence/session directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-14-preparation/inspection-01/`

The host execution context verified the original portable PostgreSQL cluster, main **PID 10380**, existing data directory and fixed port arguments; `pg_ctl status` reported running and a bounded TCP probe passed immediately before PHP. No service operation occurred.

Executed once:

```powershell
php scripts/Inspect-UiUxB2Attempt14.php prepare
```

| Boundary | Actual result |
| --- | --- |
| Command | **PASS / exit 0** |
| Start / finish UTC | **2026-10-29 10:23:43.8569696 / 10:23:44.8498830** |
| Asia/Manila | **18:23:43.8569696 / 18:23:44.8498830** |
| Fixed configured/observed target | **pgsql / 127.0.0.1:55432 / osds_ui_browser_test** |
| Identity observations | **3/3 PASS**, at **10:23:44 UTC** |
| Inspection backend PID | **35400** |
| Connection default before application bootstrap | **READ ONLY** |
| Explicit transaction | **REPEATABLE READ READ ONLY**, observed `repeatable read` |
| Effective read-only before/after | **on / on** |
| Closure | **ROLLBACK** |

Pre-PDO target checks, actual identity before connection handoff/reconnect, cached-config/environment/URL/alias/routing rejection and other-driver denial are unchanged. Guarded Laravel CLI bootstrap was used solely for the reviewed model-based verifier. No authentication/business handler, fallback database or mutation probe ran.

| Protected material | Fresh result |
| --- | --- |
| COR 47 / module 1 | **draft / lock 1**, original history **237**, `version_created`, actor **1191** |
| ACSA 48 / module 2 | **draft / lock 1**, original history **238**, `version_created`, actor **1191** |
| Original histories/audits, immutable content/bindings | Prepared exact comparisons **PASS**; no lifecycle advancement |
| Directory revision 26 | Exact/latest office map comparison **PASS** |
| B0 roots, protected rows and business counts | **PASS**, reviewed historical mappings preserved |
| Fixed roots | COR **198/591/54/43**, ACSA **199/592/55/45**, preview **128** preserved |
| Reviewer 1192 | Active synthetic identity and exact existing permission manifest **PASS** |
| Foundation | **18 accounts, 154 permission pivots, 2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments, 20 reviewer assignments** |

Reviewer permissions remain `platform.admin.access`, `questionnaires.view`, `questionnaires.review`, `questionnaires.publication.view`, `issuance.definitions.review`, `issuance.definitions.publish`, `documents.view`, `documents.personnel.review`, `documents.personnel.publish`. No grants or assignments changed; possession does not authorize lifecycle use. Inspection conclusions are limited to the established verifier scope, not an independent whole-database audit.

## Preservation and explicit log exception

Fresh focused comparison: **8,260/8,260 baseline entries unchanged**, zero changed/missing/unavailable. It preserves attempts 01–13, execution/preparation/session evidence, failed and passing disposable runs, credentials/ledger and identity history, candidate/original policies, local avatar, selected source/framework/public files, historical logs/caches/artifacts and inherited PDF/diagnostic inventories. Credentials/session bytes were hashed only. The inventory retains inherited aliases; it is not a unique-file count, whole vendor/node_modules or whole-cluster byte claim.

All **30 new pins** and **29 historical pins** match. Historical identity-log SHA-256 remains `5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B`. Fresh identities went only to preparation inspection-01. New artifacts are the nine package/check files, this report and private generation/baseline/reservation/inspection/comparison records. No prior file was restored, truncated, cleared or overwritten.

Log classification remains **APPLICATION ERROR INDICATED — PHP_EXECUTION_TIME_LIMIT — ATTRIBUTION NARROWED**:

| Segment | Preserved identity |
| --- | --- |
| Original prefix, 17,218,021 bytes | `0d946a870e5da4838859b275795effa46593517f1dca563c90c92c4a32dc1bab` |
| Known suffix, 370 bytes | `3fe483ba74ae6708d06ef7e54f2fcf51c74c06694328dae3faefe3ddc5aeb772` |
| Complete log, 17,218,391 bytes | `a71c2bd7f7d4fb8180decea1d7bbc13cf6b943b4c9a8a5fc428d8a0b3182e44b` |
| Additional bytes | **0** |

The known suffix remains an explicit error exception, not benign activity or permission to absorb later appends. No PostgreSQL storage operation occurred; normal running-cluster background activity is outside this filesystem assertion.

## Future execution boundary

`storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-14/`

**Absent before and after preparation; never reserved.** The unchanged provisioning architecture in the new launcher reserves it exclusively only during separately authorized execution. Previous attempt directories cannot be reused.

Exact future command — **not executed**:

```powershell
node scripts/Run-UiUxB2Attempt14.mjs run
```

**READY at this snapshot, subject to separate one-shot execution authorization.** Future execution must freshly verify final manifest/pins, documented log exception, historical identity, dependencies, host reachability, free port 8044 and unused path before launch, then pass fresh guarded preflight and read-only postflight. No repair, repinning, retry or substitute resource is authorized. Real authentication/sidebar/personnel acceptance and Attempt 13's exact historical rejected request remain unresolved.

**One recommended next action:** separately authorize exactly one unchanged Attempt 14 reviewer login/read-only-navigation execution, preserving all prerequisites, first-fatal handling, one-shot Login and postflight requirements.

**STOP.** No protected browser/server/authentication, Attempt 14 execution reservation, application business-logic change, additional policy expansion, lifecycle action, migration/seeding/repair, service operation, B3/B4, commit, push or deployment occurred.
