# Development roadmap and standards

**Phases 0–2 and Checkpoint 3A have been authorized.** Phase 3A implements versioned questionnaire configuration and a staff-only synthetic publication sandbox. Applicant submissions and evidence remain for later checkpoints and are not implemented. See [open decisions](./assumptions-and-open-questions.md) and the [Phase 3A completion report](./phase-3a-completion-report.md).

## Phase gates and acceptance

| Phase | Small deliverables | Exit criteria |
| --- | --- | --- |
| 0 — documentation | Seven review documents; environment check; artifact/legacy-system discovery list | Documents distinguish source statements from proposals, link to open decisions, and name owner for each blocker. |
| 1 — project foundation, **proposed next milestone** | Install verified compatible PHP/Composer/Node/PostgreSQL; initialize Git/Laravel; configure Filament/Livewire/Tailwind/Pest; local environment, health check, CI | Fresh checkout installs and tests reproducibly; secrets excluded; PostgreSQL connection and basic smoke test pass. Requires user approval first. |
| 2 — identity/data base | Users/five role names, regions, organizations/grants, audit/outbox, first migrations and policies | Deny-by-default cross-region/organization tests and transaction/audit tests pass; no policy-dependent approval grants. |
| 3A — configuration, completed locally | Draft/review/published form and requirement versions; controlled conditions; immutable sandbox manifests | Exact reviewed hash is published; PostgreSQL protects published configuration; sandbox is staff-only. |
| 3B–3D — not authorized | 3B: organization compatibility and safe applicant drafts; 3C: provider-neutral evidence and CHEDRO findings; 3D: atomic submission and corrections | See the [proposed Checkpoint 3B plan](./phase-3b-implementation-plan.md). Each checkpoint requires separate approval. |
| 4 — COR/ACSA intake | Separate module configs, applicant draft/submit, CHEDRO screening/correction, status tracking | Process-owner scenarios pass independently; no COR prerequisite or automatic approval unless approved. |
| 5 — controlled registry/issuance | Number assignment, approved templates, draft rendering, signature/undertaking evidence, issuance | Only delegated actor can issue after approved prerequisites; duplicate number/issuance tests pass; signed template accepted. |
| 6 — reporting and rollout | Scoped dashboards/exports; pilot; restore, accessibility, load and security assessment; support handover | Data reconciles, no scope leaks, approved VAPT/hosting gate met, runbook and maintenance owner accepted. |
| RA 11180 workstream | Existing-system inventory, approved cycle/questionnaire/migration decision, separate backlog | Owner approves whether first release, parallel, or later; never reuses certificate workflow as reporting state. |

## Phase 0 handoff checklist

Collect signed user requirements analysis (URA), separate COR/ACSA workflow and permissions matrices, approved certificate and five Office-template versions, registry/number/signature rules, applicant/jurisdiction rules, evidence custody and physical-original policy, RA 11180 legacy exports/eight sheets and migration choice, real school/concurrency counts, confirmed January/development dates, host/security clearance process, and records/retention owner. These are gates for later policy-dependent coding, not claims that they are already approved.

## Development standards for Phase 1 onward

- **Organization:** Domain modules own business actions and rules; HTTP/Filament/Livewire adapters call them. Use descriptive PascalCase classes, camelCase methods, snake_case table/column names, stable permission keys, and explicit interfaces only at real boundaries.
- **Migrations:** One reviewed schema change per concern; foreign keys, check/unique/index constraints in PostgreSQL; safe backfill before `NOT NULL`; no silent destructive rewrite. Test migration from empty database and representative old schema before production.
- **Validation/authorization:** Validate on server against published form version. Run a policy check at entry and state-changing service boundary. Never trust Filament visibility or Livewire parameters for access.
- **Errors:** Domain exceptions for forbidden scope, invalid transition, stale revision, missing/unsafe evidence; translate to non-sensitive UI messages and correlation-ID logs. Transactions roll back partial official changes.
- **Tests/fixtures:** Pest or PHPUnit with PostgreSQL integration database; unit tests for forms/rules, feature tests for every role/scope and case path, concurrency tests for numbering, document/link security, restore rehearsal. Use synthetic institutions and people only.
- **Dependencies/config:** Pin supported versions; review licensing/security updates; separate local/test/staging/production settings; no credentials in repository; use `.env.example` only for names and nonsecret defaults. Redis and Docker remain optional until justified.
- **Git:** Initialize repository only in Phase 1. Use short task branches, focused commits with migration/test/doc updates together, pull-request review for security/schema/workflow changes, and no generated secrets or applicant data in history.
- **Documentation:** Keep ADRs for architectural decisions, migration notes, setup/runbook, API/internal action contracts, form/template version changes, and user guides. Update the requirement-to-test trace when approved rules change.
