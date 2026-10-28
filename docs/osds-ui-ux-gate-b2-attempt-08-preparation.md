# Gate B2 — Protected reviewer Attempt 08 preparation

**28 October 2026. READY for separately authorized login/read-only-navigation execution. Preparation and guarded read-only preflight PASS.** Attempt 08 was not executed or reserved. No browser, server, authentication or lifecycle operation occurred. Versions 47/48 remain draft/lock 1; B2 lifecycle acceptance remains incomplete and B3/B4 remain closed.

## Prepared package and integrity

Applied OSDS Verification Gates and Evidence Preservation. Reused the reviewed Attempt 07 architecture and the [validated sidebar candidate](osds-ui-ux-gate-b2-sidebar-navigation-correction.md), without repeating the framework investigations or modifying historical files.

| New file | Responsibility / SHA-256 |
| --- | --- |
| `scripts/Run-UiUxB2Attempt08.mjs` | Fixed one-shot launcher, atomic reservation, sessions, fresh preflight, owned server/browser invocation, postflight/cleanup. `ac53764be135acabcf4134acdd3b03fc79f8970485e6c42b00221978b02446d6` |
| `scripts/UiUxB2Attempt08Bootstrap.php` | Fixed database identity/resolver/session guards and read-only CLI default. `7016716c74e5b909ac656b9b8a73b00f30073e062372525eb689e98c675f06d7` |
| `scripts/Inspect-UiUxB2Attempt08.php` | Guarded preparation/preflight/postflight; unchanged draft-only criteria. `403abf6861bedc97f62a5cde2ed625150d323aec0983956ed1ec73eaf6b7d997` |
| `scripts/uiux-b2-attempt-08-server.php` | Future loopback-only router, Attempt 08 bootstrap. `5c60477420527e16bef902a11d07fe099572a978a6ac83b153e10880e1481718` |
| `scripts/verify-uiux-b2-attempt-08.mjs` | Validated candidate with necessary attempt-specific substitutions only. `0a479270f0822a944053fc2932ebf96fec1df0fc2bfad0b23d5e4188cb67b3dc` |
| `scripts/uiux-b2-attempt-08-pins.json` | Twenty source/framework hashes, fixed target/path/scope and validated sidebar provenance. `c8f49db993cdc6ca52964479b916f7d9474cf383a000ed2a7a4b4c7bf3c5ef52` |

The four supporting tools are byte-identical to Attempt 07 after explicit identifier/path substitutions (`07` to `08`, `b207` to `b208`, preparation constant and entry-point names). The driver is otherwise byte-identical to the validated complete candidate. Its navigation/confirmation block is byte-identical without normalization. No substantive guard, inspection, policy or navigation logic was changed.

Validated complete candidate SHA-256:

`0222faf0daf5dacf06dfa09dd6be4596eff25646592975aa62c54dbdba5fb9ba`

Unchanged sidebar helper SHA-256:

`336a5d26c4afc4ce7738fda28eb9d68160e49be962edfba84a7db1093cca5692`

Unchanged readiness helper SHA-256:

`55b4b6a6b82cbbe7d71554fb0730542463583572cfff552b0b22e5cf2a9117a0`

All 20 pins match. The manifest retains prior navigation provenance and adds the exact sidebar candidate/helper and ten-case PASS result-file hash. The additional twentieth pin protects the new shared sidebar helper. The package checks hashes at its prepared entry points; unexpected changes must block, not trigger automatic repinning. The manifest's own hash above must also be verified before future execution. No filesystem immutability mechanism is claimed beyond this source-pinning/non-overwrite contract.

## Reservation and credentials

Fixed future execution evidence path:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-08/`

**Absent before and after preparation. Not reserved.** Only the future authorized launcher provisions it atomically; collisions, fallback attempts and reuse of attempts 01–07 are refused. No source uses an earlier attempt as its new output destination.

The authorized preparation inspection used a different fixed directory:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-attempt-08-preparation/inspection-01/`

That directory was exclusively created with private session-path metadata and a reservation identifying **read-only preparation**, not execution. HTTP processes cannot select preparation mode. The original foundation credential path is unchanged; no credential value or session content was copied into the report, pins or inspection summary.

## Actual guarded read-only preflight

Executed once with the portable PHP executable:

```powershell
php scripts/Inspect-UiUxB2Attempt08.php prepare
```

**Exit 0 / PASS.** The launcher `run` operation was not invoked. Guarded Laravel CLI bootstrap was used only for the reviewed model-based verifier, after connection read-only defaults and actual database identity validation.

| Boundary | Actual finding |
| --- | --- |
| Allowed connection | **pgsql / 127.0.0.1:55432 / osds_ui_browser_test** |
| Identity observations | **3/3 PASS**, recorded **2026-10-28 08:28:23 UTC / 16:28:23 Asia/Manila** |
| Inspection backend PID | **25944** |
| CLI connection default | **READ ONLY**, before application bootstrap/handoff |
| Inspection transaction | Explicit **REPEATABLE READ READ ONLY**, observed `repeatable read` |
| Read-only before/after checks | **on / on** |
| Closure | **ROLLBACK** |

The reused guards validate fixed configuration before PDO and actual identity before handoff/reconnect, reject cached configuration, URL/environment/alias/connector-routing overrides and other drivers, and isolate identity logs and sessions. No `osds_dev`, alternate database, mutation probe, migration, seed or repair was used.

| Protected material | Fresh result |
| --- | --- |
| COR 47 / module 1 | **draft / lock 1**; history 237, `version_created`, actor 1191 |
| ACSA 48 / module 2 | **draft / lock 1**; history 238, `version_created`, actor 1191 |
| Original immutable bindings/history/audits | Reviewed exact criteria passed; no review/publication history |
| Directory 26 | Latest revision/exact protected office map comparison **PASS** |
| B0 roots / protected rows / business counts | **PASS** |
| Fixed COR roots | Publication 198, form/requirements 591, definition 54, template 43 |
| Fixed ACSA roots | Publication 199, form/requirements 592, definition 55, template 45 |
| Preview / prior personnel mappings | Preview 128 and reviewed historical mappings preserved |
| Foundation unions | 18 accounts, 154 permission pivots, 2 regions, 3 institutions, 3 representations, 6 eligibility grants, 12 regional assignments, 20 reviewer assignments |

Reviewer **1192** matched the existing active synthetic `config-reviewer` foundation account and exact permission manifest:

`platform.admin.access`, `questionnaires.view`, `questionnaires.review`, `questionnaires.publication.view`, `issuance.definitions.review`, `issuance.definitions.publish`, `documents.view`, `documents.personnel.review`, `documents.personnel.publish`.

No grants/roles/assignments were added. Possessing review/publication permissions does not authorize their use in this package. No missing or changed record was recreated. Payload/content/hash checks retain the reviewed inspector scope; they do not claim a new independent full payload audit.

## Preserved acceptance contract

- Both validated password-readiness checkpoints remain, before the single email/password fill and immediately before the one Sign in click.
- Exact `/admin` origin/path state poll remains bounded at 30 seconds, preserving query/fragment semantics. HTTP 200 alone cannot confirm authentication.
- Exactly one expected Dashboard component, visible exact **Your workspace** level-one heading, zero Login components, no Login-form signature and valid bounded metadata are required.
- One sidebar container and one exact named link are required. Visibility, actionability and exact same-origin personnel href are checked; visibility and click share the same locator. Count/href/destination/fatal state are rechecked before the actual link click.
- Destination revalidation, shared fatal signal and closed failure categories remain. The existing request/endpoint allowlist and login/idle phases prohibit personnel transitions.
- Subsequent exact personnel URL/component and draft/version headings for 47/48 are unchanged. No review/publication call or lifecycle phase exists in the driver.
- No timeout increase, sleep, retry, alternate credential path, error suppression, trace or screenshot capture was added. Postflight requires both original drafts unchanged.

## Verification and preservation

| Check | Actual result |
| --- | --- |
| New package integrity | **PASS: 35 checks**, including 20 pins, exact candidate/block comparisons, one-shot controls, unchanged supporting architecture and unused execution path |
| Existing focused sidebar tests | **PASS: 20 checks** |
| Existing readiness suite | **PASS: 70 checks** |
| Existing diagnostics/policy/source suite | **PASS: 98 checks** |
| PHP syntax | **PASS**, new bootstrap/inspector/router |
| JavaScript syntax | **PASS**, new launcher/driver |
| Scoped whitespace | **PASS** |
| Guarded read-only preparation inspection | **PASS**, exit 0 |
| Browser/disposable/protected authentication | **NOT RUN** during this task |

No historical test output was rerun or overwritten. The existing ten-context disposable PASS was verified by its candidate/helper hashes and result provenance, not counted as a new browser run.

Fresh focused baseline: **4,875/4,875 unchanged**, zero modified/missing. It covers prior attempts/evidence including Attempt 07 and sidebar validation, historical identity records, original credentials/ledger, validated helpers, protected application/framework/public sources, logs/compiled views, Batch G, all 81 previously inventoried PDF-named artifacts and ten historical diagnostic JSON files. No log/cache/evidence was restored, cleared or truncated. Historical append and missing temporary-metadata exceptions remain unchanged. This is not a whole vendor/node_modules inventory.

Historical identity-log SHA-256 remains:

`5152846D0C16625BB743F1BA06E6FC233AF842D4FBC2BF53EA5483C72EF7064B`

All three fresh observations went to the separate preparation identity file. Private file-generation, baseline/integrity/preservation records and inspection output are under `gate-b2-attempt-08-preparation/`. New artifacts are those records, the six package files and this report; all pre-existing files were preserved.

## Future execution and decision

Exact future command — **not executed**:

```powershell
node scripts/Run-UiUxB2Attempt08.mjs run
```

Under separate authorization for one login/read-only-navigation attempt, this would verify pins/historical integrity, refuse an occupied port 8044 without terminating unrelated processes, atomically reserve Attempt 08, provision private sessions and repeat fresh guarded preflight. Only then could it start its loopback server/browser, enter existing credentials once after readiness, verify the protected dashboard/sidebar and follow that link to inspect the unchanged drafts. It closes task-owned resources and performs guarded read-only postflight. Any prerequisite, JavaScript, request-policy, readiness, destination, UI, identity or state failure stops without retry or repair.

**READY at this snapshot for separately authorized execution.** Port availability, browser/runtime dependencies and protected state must be checked fresh at execution; they are not certified indefinitely by preparation. Actual credential acceptance, reviewer session identity, real dashboard/sidebar behavior and personnel-page assertions remain unverified by Attempt 08. A passing login/navigation attempt would not complete lifecycle acceptance or authorize any personnel mutation.

**STOP.** No Attempt 08 execution reservation, protected browser/server, authentication/reviewer POST, COR/ACSA transition, migration/seeding/repair, directory recreation, PDF/applicant workflow, B3/B4, commit, push or deployment occurred.
