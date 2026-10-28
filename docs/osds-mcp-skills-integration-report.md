# OSDS — MCP and skills integration review

**28 October 2026. PASS for configuration inventory, existing-skill refinement and offline checks. Live execution remains BLOCKED by the current authorization boundary.** No MCP server was started, remote tool invoked, PostgreSQL connection opened, Laravel bootstrapped or browser launched in this task. Attempt 05 was not prepared or reserved.

## Actual MCP inventory

Inspected project `.codex/config.toml`, installed entry points, existing setup notes and the tool catalogue exposed to this chat. `codex mcp list --json` parsed the actual configuration and returned registration/authentication status without starting servers. Installation, configuration, exposure and a successful fresh connection are distinct findings.

| Server | Verified configuration / discovery | Current scope and read-only assessment | Sensitive-data exposure / safe-use limitation | Recommended OSDS use |
| --- | --- | --- | --- | --- |
| Chrome DevTools | Configured; **disabled**; no tools exposed in this chat. Installed 1.12.0. | Initial allowlist is `list_pages`, `take_snapshot`. These inspect pages but can require a browser process; they are not source-only operations. | DOM/snapshots can disclose private data. Isolated headless profile; usage statistics/CrUX disabled. No loopback-origin restriction is configured here. Raw console/network tools are excluded. Remain disabled pending an authorized, reviewed diagnostic scope. | Separately authorized browser inspection with sanitized diagnostics; not an alternative to the guarded acceptance driver. |
| Playwright | Configured; **disabled**; no tools exposed. Installed @playwright/mcp 0.0.83. | Initial allowlist is `browser_tabs`, `browser_snapshot`; no click/fill/run-code tools enabled. | Isolated headless Chrome and loopback-origin allowlist. Snapshot data can still be sensitive; an origin allowlist is not a complete security boundary. No browser connection was tested. | Authorized UI/accessibility exploration alongside repeatable acceptance scripts. |
| GitHub | Configured/enabled; CLI status **not_logged_in**; no GitHub tools exposed to this chat. | Official remote `/mcp/readonly`, with repos/issues/PRs/Actions toolsets. Read-only endpoint excludes write operations; it does not restrict access to one repository. | Authentication remains outstanding. OAuth/token grants determine accessible private repositories and CI logs; do not assume OSDS-only scope. No repository/account request was made. | Read-only review of OSDS commits, PRs and CI after account authorization. |
| Context7 | Configured/enabled; **two tools exposed**: `resolve-library-id`, `query-docs`. CLI has no saved login. | Documentation lookup only. Earlier setup successfully resolved a public library; no lookup was repeated here. | Queries leave the local machine. Use public library/version questions; do not send credentials, private source or applicant data. No fresh authentication/connectivity assertion. | Public version-aware frontend/library documentation. |
| Laravel Boost | Configured/enabled; **two tools exposed**: `application-info`, `search-docs`. | Existing wrapper permits only informational tools and denies PDO connections in parent/tool subprocesses. No tool invoked here because it would bootstrap Laravel. | Raw logs, browser-log injection, Tinker, rule writing and database tools remain unavailable. Application metadata is not proof of actual database identity. | Package/version metadata and Laravel documentation under the established launcher restrictions. |

The MCP configuration and all permission settings were left **unchanged**. Known obstacles are disabled browser servers, unauthenticated GitHub, deliberately narrow tool allowlists and outstanding reviewed browser capture requirements. These are not silently repaired by enabling servers or widening tools. No MCP is treated as an authorization grant.

## Existing skill architecture

Repository discovery found six existing OSDS skills and no root AGENTS.md in the inspected repository search. Reused those exact six directories; no duplicate skill or agent configuration was created. Each retains supported `SKILL.md` YAML `name`/`description` metadata and normal automatic selection. No unsupported dependency or MCP schema was introduced.

**OSDS Verification Gates is now the explicit common safety contract.** Each of the five specialist skills links to it. It covers current operation/database authorization, source-only/execution separation, target identity, atomic reservation, one-shot mutations, immutable historical evidence, PASS/FAIL/BLOCKED/NOT RUN reporting, separately authorized B3/B4 and deployment, and user-defined stopping boundaries. Prior authorization is not repeated when it already covers the work. The current user's instructions remain authoritative.

| Existing skill / trigger | Required evidence | Prohibited expansion / completion |
| --- | --- | --- |
| **Verification Gates** — stage planning or verification execution | Current authorization, latest applicable report, guard/source identity and preservation scope | No inferred database/execution approval, fallback target or successor attempt. Finish with actual scoped results, partial commits and exceptions. |
| **Livewire Diagnostics** — browser/Filament failure investigation | Driver/source hashes, retained diagnostics and policy/sanitizer tests | No arbitrary components, internal actions, raw secrets or retries. Finish with source/runtime attribution and unresolved evidence gaps. |
| **Authorization Review** — permissions, scope or lifecycle authority review | Route/page access, policy/final-service chain and relevant negative-test evidence | No weakened denials, role-name shortcuts or test permission inflation. Finish per boundary with runtime gaps explicit. |
| **Evidence Preservation** — evidence-producing work or preservation audit | Scoped before/after hashes, attempt identity and intended deltas | No overwritten history, fabricated lost metadata or concealed generated artifacts. Finish with unchanged/changed/missing counts and exceptions. |
| **Accessibility Acceptance** — responsive/accessibility checks | Approved scenario matrix, raw axe/incomplete results and keyboard/capture scope | No relaxed rules or simulated-authentication claims. Finish with separate automated, sampled manual and pending human acceptance. |
| **Document Integrity** — request/PDF binding or private-download review | Service contracts, immutable linkage and authorized byte/page inspection | No original-byte damage, inferred eligibility or automatic generation/recovery. Finish per artifact with checksums, markings and limitations. |

Skills remain guidance, not a security sandbox or live-test proof. Tool and database guards still enforce actual runtime boundaries. Each specialist now explicitly states its evidence and completion requirements without copying the entire common contract.

## Actual files changed

- Updated the six existing `.agents/skills/osds-*/SKILL.md` files: shared-contract links, evidence/completion criteria and the common contract's explicit B3/B4/deployment boundary.
- Updated verification-only `scripts/uiux-b2-error-diagnostics.mjs` and `scripts/verify-uiux-b2-reviewer-only.mjs` as detailed in the [diagnostic correction report](osds-ui-ux-gate-b2-diagnostic-correction-report.md).
- Added `scripts/check-osds-diagnostics.mjs`, a pure offline test script, and the two requested reports.
- No MCP config, application/runtime/authentication/asset/service, package manifest, launcher, inspector or request-policy edit.

Protected-directory skill writes used reviewed staging, existing-file hash checks and explicit filesystem escalation. No existing skill was deleted or renamed.

## Offline results and preservation

| Check | Actual result |
| --- | --- |
| Diagnostics / secret handling / event ordering tests | **PASS: 98 checks** |
| Existing request-policy/selector suite | **PASS: 294 checks**, unchanged policy |
| Integration/config/skill consistency | **PASS: 56 checks**, shared contract references, paths, config unchanged and attempt-05 absence |
| Six skill YAML metadata checks | **PASS** |
| JavaScript syntax | **PASS**, changed helpers/driver and new offline test |
| PHP syntax / scoped Pint `--test` | **PASS**, inspected Boost wrapper/config; no Laravel bootstrap |
| Scoped whitespace | **PASS**; unrelated existing documentation whitespace left untouched |
| Live MCP/API, database, server, browser or authentication | **NOT RUN** |

A focused **3,025-file** pre-task baseline records **3,017 unchanged**, exactly **eight authorized changes** (six skills and two verification files), and zero missing. All **81 PDF-named files**, including the original **73**, the ten historical diagnostic JSON files, attempts 01–04/session evidence, identity logs, foundation credentials/ledger, protected application code, current Laravel log and Batch G are unchanged. MCP config and dependency manifests are unchanged. The new offline test and reports are additions outside the baseline count.

Private snapshots, baseline/comparison and validation scripts are under `storage/app/private/osds-integration-diagnostics/`. No log/cache clearing or restoration occurred. The previously missing temporary fake-upload metadata remains a historical exception and was not reconstructed. This is a focused preservation comparison, not a whole-vendor-tree certification.

## Readiness and stop

Skill integration and offline diagnostic preparation **PASS**. The actual ReferenceError cause, authenticated reviewer state, current database state and new diagnostics' live behavior remain unverified. Attempt-05 preparation is now justifiable as a separately authorized next task because new closed categories and event ordering could add evidence; it was **not** performed here.

**STOP. No attempt 05, directory reservation, version 47/48 action, office recreation, PDF, migration, B3/B4, commit, push or deployment.**
