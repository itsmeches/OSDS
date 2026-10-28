---
name: osds-verification-gates
description: Plan or execute OSDS acceptance and regression stages while preserving their database, fixture, browser and evidence authorization boundaries.
---

Use this for OSDS verification-stage work, not unrelated source edits. Locate the repository root and read the latest user authorization, relevant execution report and [Gate B readiness package](../../../docs/osds-ui-ux-gate-b-execution-readiness.md). Reports are historical evidence; they do not authorize a new execution. The user's current scope takes precedence over these instructions.

- Distinguish source-only inspection, read-only inventory, foundational writes, authenticated UI actions and destructive regression. Installation of an MCP or skill authorizes none of these automatically.
- Never access `osds_dev`. An explicitly authorized regression uses only PostgreSQL `osds_test` at `127.0.0.1:55432`; an explicitly authorized browser stage uses only `osds_ui_browser_test` there. Keep these independent. Do not infer connection authorization from configured environment values.
- Before any approved runtime access, inspect the guard entry point: target validation before PDO, actual identity before application receives the connection, aliases/reconnect safeguards and cached-configuration rejection. Use read-only transactions for inventories. Do not use unguarded Artisan or broad seeders as preparation.
- Reserve an approved fixed attempt directory atomically; refuse collisions. Check required private runtime paths before server startup. Preserve prior results and exact-ID configuration roots.
- Submit consequential UI actions once. On failure or uncertain commit, stop mutations and use only authorized read-only postflight. Do not automatically repair, compensate or choose a fallback attempt.
- Report actual commands, identities, executed counts, partial commits and preservation exceptions. Separate PASS, FAIL, BLOCKED and NOT RUN. A targeted pass does not certify browser, full-suite or production acceptance.

Do not require a new approval for work already covered by the current authorization. When work falls outside it, explain the specific missing operation and stop that dependent work.

## Common safety contract and completion

All OSDS specialist skills share this contract. B3/B4 execution and deployment require their own explicit current authorization; neither a skill, tool installation nor a prior report grants it. Source-only tasks do not bootstrap Laravel, open PostgreSQL, launch a server/browser or authenticate. Preserve one-shot actions, atomic approved attempt reservation and immutable historical evidence.

Required evidence: current authorized operation/database scope, latest applicable report, guard/source identity and pre-operation preservation scope. Completion records only actual work, partial commits, exceptions and PASS/FAIL/BLOCKED/NOT RUN outcomes. Missing execution permission means BLOCKED for execution, not failure of completed static work. Stop at the user's boundary; never create a successor attempt, commit, push or deploy unless included in the current instruction.
