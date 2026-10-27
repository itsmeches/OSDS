# Gate B2 — Fictional office and personnel setup

**27 October 2026. BLOCKED at source-level scope/lifecycle review. No database connection or setup write occurred.** The foundation's earlier PASS remains historical. Office/personnel setup and B3 readiness are not complete. B3/B4 remain NOT AUTHORIZED; Batch G remains CONDITIONAL CLOSE.

## Authentication and execution-path conflict

The [approved execution package](osds-ui-ux-gate-b-execution-readiness.md), sections 2 and 7, requires real login forms and UI controls for fictional office creation and personnel creation/review/publication. It explicitly schedules that work after authorization for both configuration setup and the required logins. The latest authorization permits configuration setup but explicitly prohibits browser login.

Current source also requires a genuinely authenticated actor:

- `DemoOfficeDirectoryService::save()` requires `auth()->id() === $fresh->id`, active account access and the existing preview-management permissions.
- `DemonstrationDocumentService::createVersion()` and `advanceVersion()` call `CaseIssuanceService::actor()`, which requires the authenticated ID to match the fresh active synthetic actor and checks the relevant existing permission.
- Actual page handlers pass `auth()->user()` to these services. Merely passing a user model to a console call is not sufficient.

This does not mean non-browser credential authentication is technically impossible. It means switching to authenticated console/service calls would depart from the approved **UI-based setup contract**. No such exception was assumed. No `actingAs`, `setUser`, injected authenticated session, direct model insertion or review impersonation was used to bypass that contract. The required `osds` and `config-reviewer` permissions are present in the approved manifest at source level; no additional permission is proposed.

**Smallest authorization needed:** permit real browser login for the two already prepared synthetic configuration accounts, restricted to office/personnel setup through existing controls. That can be a narrowly bounded setup authorization without approving application journeys or the rest of B3. Alternatively, explicitly approve a separate, credential-authenticated service setup plan if that is the intended change of execution contract.

## Directory shape and requested region coverage

The latest request calls for exactly one new directory revision containing fictional primary and probe region information. The earlier approved manifest specified **one new fictional office entry**, with two personnel versions for the **primary region**. Probe-region authority was reserved for wrong-region denial.

`DemoOfficeDirectoryService::save()` currently:

1. Locks and checks the expected latest revision.
2. Validates one office's `label`, `director` and `designation`.
3. Copies the latest revision's complete office map.
4. Adds or updates exactly one office key.
5. Immediately creates the next immutable directory revision and its audit event.

There is no directory `draft → reviewed → published` lifecycle or separate directory publication action. Saving advances the latest directory used by the existing preview interface. Older revisions remain intact, but the current directory is global. The candidate must therefore be reviewed before the explicit save, with all old entries retained unchanged.

Office entries have no structured `region_id`. Region binding is stored in the separate personnel version. If “primary and probe” means **two separate new office entries**, the existing ordinary service creates **two revisions**, not the requested one. Creating a two-entry revision directly would bypass that service path and is not authorized. A combined label must not silently stand in for two distinct region mappings.

**Clarification required:** retain the original one-primary-office plan with the probe region remaining an unmapped negative control, or separately approve two office entries/two append-only revisions if both need explicit directory entries. This is a scope question, not evidence that the existing directory is defective. No new region-coverage rule was invented.

## Source-verified personnel lifecycle

The existing personnel service supports the intended two primary-region versions, one COR and one ACSA, provided setup can proceed through the approved authenticated path:

- `osds` creates each personnel draft using its `documents.personnel.manage` permission, active module/region and a complete existing directory entry.
- `config-reviewer` advances the exact draft to `reviewed` and then `published` using its explicit review/publish permissions.
- Optimistic versions and the module/kind/region published-slot boundary remain mandatory. No old mapping may be withdrawn to resolve a conflict.
- Personnel publication is a distinct lifecycle from the immediate directory save. No missing directory approval record will be fabricated.

This is source capability only. No service call, version allocation, approval or publication was attempted.

## Actual results

| Check / operation | Status |
| --- | --- |
| Service, model and page-handler source review | **Completed:** authentication, directory shape and personnel lifecycle identified |
| Current database identity | **NOT RUN:** stopped before any connection |
| Current foundation IDs / 18 permission unions / six eligibility grantors | **NOT REVALIDATED:** earlier B2 creation report remains historical |
| B0 projections, six roots and publication 128 | **NOT REVALIDATED** |
| Existing 16 published personnel mappings and full directory | **NOT REVALIDATED** |
| New office/personnel conflict scan | **NOT RUN** |
| Full resulting directory validation | **BLOCKED** pending scope and execution-path resolution |
| New directory revisions | **0 created** |
| New personnel versions / reviews / publications | **0 / 0 / 0** |
| New audit/history/permission records | **0** |
| Transaction/commit uncertainty | **Not applicable:** no database transaction began |
| New configuration ownership IDs | **None** |

No live database failure or change to an existing mapping is established. No new setup helper, credential use, browser server or authenticated session was created. No original personnel values were retrieved or copied.

## Preservation

Recorded HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and **26,847 baseline file hashes** under:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-personnel-setup/`

Post-review comparison: **26,847 unchanged; zero changed or missing**. This includes all **73 original PDFs**, all **81 current PDF-named artifacts**, ten historical diagnostic JSON files, prior gate reports/attempt evidence, the committed foundation ledger, Phase 1–3 runtime/evidence and unrelated Batch G artifacts. Existing generated logs and Blade caches were not cleared or restored. The previously missing temporary upload metadata remains the historical exception; it was not reconstructed.

Tracked whitespace checks passed with existing CRLF advisories. No application command, PHPUnit, migration, browser or database-capable helper was executed. The requested report and private preservation records are the only new deliverables. No runtime, workflow, permission manifest or existing report was edited.

## Required review and stop

Resolve the two bounded questions before setup:

1. Authorize the required real UI configuration logins, or explicitly review an alternative authenticated service-only execution plan.
2. Confirm one primary-office entry/one revision as originally planned, or approve the distinct revision count needed for two office entries. Keep exactly two primary-region personnel configurations unless their scope is separately amended.

Once resolved, revalidate the actual foundation, roots, directory and empty new personnel slots before any save. Retain every old entry in the new directory revision; verify its full map and old-record hashes, then use the existing review/publication workflow and record actual resulting states.

**Gate B2 is not yet fully ready for B3. STOP.** No configuration save/publication, browser login, application submission, evaluation, request, PDF, B3/B4, commit, push or deployment occurred.
