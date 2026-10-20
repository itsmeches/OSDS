# G-INT-007 - Atomic institutional-representation creation

**20 October 2026. Targeted development acceptance: PASS for the integrity/security gates below.** Generic representation creation only. The known selector target-size finding remains open. G-INT-001 through G-INT-006 remain preserved. Batch G is incomplete; pause for user review. No Batch H, Phase 4, production, migration or historical repair.

## Root cause and preserved contract

The actual `CreateInstitutionRepresentative` page inherited Filament's default model insertion. Its registered synchronous `CoreIdentityObserver` subsequently wrote the mandatory audit without one operation transaction. Authorized diagnostics first proved normal Create (1 test / 20 assertions), then retained a real-action rollback failure (1 test / 13 assertions): representation persisted, audit count unchanged.

Inspected the actual resource, form, policy, model, observer registration, AuditRecorder, AccountGovernance, AccessGovernance and ScopeAuthorizer before changing runtime code. Existing creation requires active authenticated staff-panel admission, explicit `institution_representatives.assign`, the target role's configured creation permission, non-self target and canonical institution access. Canonical access retains effective regional, nationwide and verified/effective representation alternatives. The form's existing scoped selectors remain unchanged, including its separate regional/nationwide user visibility. No role name alone authorizes creation.

Generic creation does not require synthetic runtime targets or a new sandbox-only permission. Synthetic fixtures and isolated test-server restrictions are verification boundaries, not a new business policy. No verification/activation, institution-active rule or new manager-region rule is introduced. The separate account-creation and `AccessGovernance::grantInstitution` workflows retain their own transactions/audits; this page is not redirected through them.

Existing PostgreSQL constraints reject a second unrevoked user/institution grant even if its dates have expired, and retain user/institution/start uniqueness. Revoked history allows another grant with an appropriate new start. No idempotency token or new duplicate semantics.

## Exact runtime correction

`RepresentationCreationService::create` owns one default PostgreSQL transaction with **one attempt**, spanning real model insertion and the unchanged observer's sole mandatory audit. It validates only `user_id`, `institution_id`, `starts_at`, `ends_at`; locks actor/target users in ID order, actor regional/nationwide/representation authority, current institution binding and existing relevant permission/role membership rows; then checks current actor/target authorization before insertion. Existing delegated-edit lock ordering is reused. The institution is loaded after any shared-row lock wait so a concurrent routing-region change cannot authorize creation using a stale binding.

Fresh actor/target objects avoid mounted/cached relations. The existing model's creating guard runs again. `granted_by` is fixed to the authorized actor. Caller-supplied verification, revocation and attribution fields are excluded; the new representation starts unverified/unrevoked. No model observer, audit semantics, blanket panel transaction, compensation delete, schema or duplicate manual event is changed.

The real Create page overrides only its protected persistence handler, using the existing support-reference handler. Unexpected failure produces fixed persistent `data.starts_at` feedback and retains local entries. Logs contain only the fixed support message, reference and exception class; no SQL/exception text or credentials. Authorization/validation retain their normal denial behavior. The existing staff network/session/uncertainty hook is additionally scoped to this Create page; it links to the authorized saved list and does not replay privileged mutations.

Locks establish authorization at the operation's check, not a new rule that grantor authority must remain effective throughout a committed representation's lifetime. Arbitrary external SQL schedules and every possible deadlock are not certified; failures roll back and show safe recovery rather than silently retrying.

## Regression evidence

Both original diagnostics remain on the real Filament page/Livewire action/model/registered observer. The normal control retains its exact one-audit assertions. The original failure test still requires its audit sink exactly once and retains the exact no-persist comparison; acknowledgement now checks safe inline errors/notification instead of requiring a raw injected exception to escape. **Original pair after correction: 2 tests / 39 assertions passed.**

Focused expanded PostgreSQL matrix: **22 tests / 216 assertions passed**. Includes normal pairing; before-audit failure; real audit insertion then failure; genuine PostgreSQL audit foreign-key rejection; same PDO and nested operation depth; clean outer transaction after failed SQL; post-mount assignment/panel/delegation/status/deactivation/regional date/revocation/target-role/institution-binding changes; invalid input; self/cross-institution/detached identity; forged attribution/verification/revocation fields; unchanged duplicate/expired-unrevoked/revoked-history rules; canonical verified representation and effective nationwide alternatives; safe response/log serialization.

The first expanded attempt failed while arranging an out-of-scope fixture institution: its existing model guard correctly rejected setup. Suppressing events **only for that unrelated disposable fixture setup** then exposed its required `public_id` generation; the setup was completed with an explicit UUID. The tested representation operation never suppresses events. Both failed attempts are retained privately (`g-int-007-matrix-first.txt`, `g-int-007-matrix-final.txt`); corrected matrix is `g-int-007-matrix-final-corrected.txt`. No runtime guard or assertion was weakened.

## Real PostgreSQL concurrency

[Concurrency evidence](screenshots/phase-3d7-g-int007/concurrency.json): **six scenarios passed**, using independent PHP workers with distinct PostgreSQL backend PIDs and database-confirmed waits (`pg_blocking_pids`).

- Competing creates: one committed representation/audit, one existing unique-constraint rejection; zero uncommitted representations visible externally.
- An earlier regional-scope revocation, permission deletion, actor deactivation, target-role change or institution-region change holds its transaction while the creation waits. After that change commits, creation denies and leaves zero new representations.

Fixture synchronization barriers are isolated test controls; no runtime delay/retry is added. These checks exercise the real service transaction outside PHPUnit's wrapping fixture transaction. Direct authority-state manipulation is confined to disposable synthetic fixtures; it is not historical repair or a claim of browser testing those management UIs. Verified representation/nationwide alternatives are covered by feature tests; all possible competing paths are not exhaustively certified.

## Browser, recovery and accessibility

Real Chrome/Playwright form checks run on guarded local port **8027**, `osds_ui_browser_test` only. Final functional journey passed **seven checks / eight light/dark desktop/mobile screen checks** at 1440px/375px: account/institution search and selection, native required-field validation, post-audit rollback with retained entries, delivered success/double click, separate duplicate rejection, committed-response loss, saved-list inspection, mounted permission loss and actual cookie loss. A duplicate is not promised the same success response. No automatic consequential replay.

A copied generic helper labelled this manager as Admin in first-run metadata even though fixtures use the existing CHEDRO permission bundle explicitly. The label is corrected to Staff and the complete target rerun passed; `browser-first-role-label.json` retains the original record. No runtime authority changed. A failed PowerShell helper-generation expression was corrected by writing the preservation runner explicitly; it performed no runtime/database mutation.

Known selector accessibility follow-up is retained: both existing selector clear controls are 16px, with two `target-size` nodes recorded in each sampled state. The reused capture helper keeps all axe rules and records that exact clear-button finding, failing any other violation. Final screens show no page-level overflow, uncaught page errors or checked response leaks. Sampled computed contrast minima are **6.15:1 light / 8.72:1 dark**, applicable only to the measured selectors/states. This targeted atomicity fix does not include a global/scoped selector restyle or claim zero axe findings. Native zoom, screen readers, human usability and timed-animation contrast remain pending. External avatar requests are blocked in fixtures.

[Final target browser results](screenshots/phase-3d7-g-int007/results.json). Developer image inspection includes [light desktop rollback](screenshots/phase-3d7-g-int007/representation-creation-rollback-light-1440.png) and [dark mobile uncertainty](screenshots/phase-3d7-g-int007/representation-creation-uncertain-dark-375.png). Other light/dark desktop/mobile images are in the same directory. No full WCAG or human acceptance is claimed.

## Final gates

Complete current-tree PostgreSQL passed **252 tests / 2,831 assertions**, retaining G-INT-001–006, SEC-001–008 and applicant/builder/UTC/immutability protections. Private output: `storage/app/private/g-int-007-full-final.txt`. Original pair **2 / 39**, focused matrix **22 / 216**, six real concurrency scenarios and final target browser **7 / 8** passed as detailed above.

Serial browser preservation passed the original applicant reproduction plus **11 expanded cases**; original builder reproduction plus **14 selection cases**; **seven condition/preview/publication checks / six screens**; CHEDRO **12 interactions / 30 screens**, including correction/resubmission and scope denial; staff **15 interactions / 94 screens**, including account verification and actual session loss. [Preservation index](screenshots/phase-3d7-g-int007/preservation/results.json). No preservation case failed in this run. The staff capture wrapper reuses the recorded G-INT-006 notification-close polling before measurement; it changes no workflow assertions, runtime code or mutation retries. The original broader dedicated builder recovery assessment remains separate from these preservation cases.

Full Laravel Pint `--test`, production Vite build and final working-tree diff checks passed; existing CRLF advisories remain. Private records: `g-int-007-pint-final.txt`, `g-int-007-build.txt`, and `g-int-007-preservation.txt` under `storage/app/private`. Browser preparation, concurrency and PostgreSQL suites were serialized. Earlier accepted 230 / 2,615 results remain historical. No new independent critical defect was confirmed during this targeted remediation. Accessibility measurements were completed with the known target-size exception recorded above; they are not an all-accessibility-gates pass.

## Affected files

- `app/Services/Authorization/RepresentationCreationService.php`: new operation-owned transaction.
- `app/Filament/Resources/InstitutionRepresentatives/Pages/CreateInstitutionRepresentative.php`: persistence delegation and safe persistent feedback.
- `app/Providers/Filament/AdminPanelProvider.php`: existing recovery hook scoped to this page.
- `tests/Feature/RepresentationCreationAtomicityTest.php`: retained original diagnostics/rollback comparison and current safe acknowledgement.
- `tests/Feature/RepresentationCreationWorkflowTest.php`: expanded actual-action/service matrix.
- `scripts/Prepare-RepresentationCreation.php`, `RepresentationCreationWorker.php`, `verify-representation-concurrency.mjs`, `g-int-007-browser-server.php`, `verify-representation-creation.mjs`, `verify-g-int-007-preservation.mjs`: guarded isolated fixture/concurrency/browser/preservation tooling.
- Remediation/checkpoint documentation and safe result/screenshots.

Resource/form/policy, InstitutionRepresentative, CoreIdentityObserver/registration, AuditRecorder, AccessGovernance, prior G-INT services and schema are unchanged. Fault injection is registered only by the local port/database-constrained test server, not application routes/providers. The pre-existing G-INT-006 selector issue and other inventory rows remain separate work.

## Manual review and remaining Batch G work

1. With disposable accounts, use current assignment permission, target-role delegation and effective institution scope; select through the unchanged real form and Create once. Confirm saved attribution/dates, unverified state and one matching audit where separate audit access permits inspection.
2. Try self/out-of-scope/unmanageable targets, then change permission/scope/target authority after mount in isolated fixtures. Expect no new representation.
3. On a duplicate unrevoked grant, inspect the saved representation; do not infer new date-based duplicate policy.
4. For failed or uncertain response, retain the tab and inspect saved representations in another authorized tab before any new explicit Create. No automatic replay or guaranteed identical retry result.
5. Fault injection belongs only in approved disposable automation. Human keyboard/native-zoom/screen-reader/first-time usability acceptance remains open.

Broader Batch G authority-inventory dispositions, dedicated builder recovery, outstanding selector target size, performance/reliability closure and human acceptance remain outside this targeted correction. No historical audit completeness or production readiness is claimed. Only `osds_test` / `osds_ui_browser_test` are used; manual-testing databases, history, prior evidence and staged content are preserved. No commit, push, deployment, migration or history repair. After targeted verification, pause for review; do not resume remaining Batch G or begin H/Phase 4 automatically.
