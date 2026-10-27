# Gate B2 — Foundational browser-test preparation

**27 October 2026. BLOCKED at source-level manifest validation. No database connection or fixture write occurred.** Gate B1's targeted PASS remains historical; B3 and B4 remain NOT AUTHORIZED. Batch G remains CONDITIONAL CLOSE.

## Confirmed prerequisite conflict

The approved readiness manifest requests six module-specific eligibility grants but gives none of its 18 accounts `applicant.sandbox.publish`. This was an omission in the readiness package, not an application regression.

The existing `ApplicantSandboxEligibilityGrant` model resolves the account identified by `granted_by` and requires it to be active and have that permission before creation:

```php
$actor = User::find($grant->granted_by);
if (! app(ApplicantSandboxGuard::class)->allowed()
    || ! $institution->is_synthetic
    || ! $actor?->isActive()
    || ! $actor->can('applicant.sandbox.publish')) {
    throw new AuthorizationException;
}
```

Evidence: `app/Models/ApplicantSandboxEligibilityGrant.php:16` and the corresponding resource access check at `app/Filament/Resources/ApplicantSandboxEligibilityGrants/ApplicantSandboxEligibilityGrantResource.php:34`. Existing synthetic fixture code also grants this permission to its eligibility grantor; it was inspected as source only and was not executed.

The [approved readiness package](osds-ui-ux-gate-b-execution-readiness.md), section 5, defines closed permission unions and explicitly states:

> No omitted permission may be added to make a failure pass. If a destination requires another existing permission, report the exact dependency and request an amended manifest.

This B2 authorization also requires stopping for a missing manifest dependency. Therefore preparation stopped before opening a connection, creating any account or beginning a write transaction. No permission was silently added, existing grantor borrowed, role bundle expanded, model event disabled or direct-SQL insertion used to bypass the requirement.

## Preflight outcomes

| Check | Actual result |
| --- | --- |
| Manifest/source dependency review | **FAIL:** no authorized fixture identity can act as eligibility grantor under the existing model contract |
| Requested target | Only `pgsql`, `127.0.0.1:55432`, `osds_ui_browser_test` is authorized for this stage |
| Configured/actual database identity | **BLOCKED / not attempted:** stopped at the source dependency gate |
| COR roots 198 / 54 / 43 | **NOT REVALIDATED**; prior Gate A/B0 findings remain historical |
| ACSA roots 199 / 55 / 45 | **NOT REVALIDATED** |
| Staff-preview publication 128 and existing personnel mappings | **NOT REVALIDATED**; no rows accessed or changed |
| Namespace `uiux_20261027_a01` | **UNKNOWN currently**; not queried or reserved |
| Live schema, permissions, indexes and constraints | **NOT REVALIDATED** |
| Further fixture dependencies | **Not fully certified:** additional checks remain required after manifest amendment |
| Database mutations / transaction rollback / uncertain commit | **NONE / not applicable** |

No root incompatibility or database identity failure is established. These database-dependent checks are blocked, not passed. No other database, including `osds_dev` or `osds_test`, was accessed.

## Created records and ledger

| Approved foundation | Created |
| --- | --- |
| Synthetic accounts | **0 / 18** |
| Regions | **0 / 2** |
| Institutions | **0 / 3** |
| Representation bindings | **0 / 3** |
| Eligibility grants | **0 / 6** |
| Explicit permission pivots, regional authority and application reviewer assignments | **0** |

No ownership ledger with created IDs or private credentials exists because nothing was created. No partial foundation was committed. Stage-local file-only evidence is under:

`storage/app/private/uiux-consolidated-acceptance/gate-b2-foundation/`

It contains HEAD/status, discovered paths, file hashes, a private hash utility and preservation comparison. The proposed browser preparation/connection helpers were not implemented or invoked after the confirmed prerequisite stop. No application bootstrap, migration, broad seeder, login, office-directory operation, personnel version, application, correction, decision, request or PDF operation occurred.

## Preservation

Recorded Git HEAD **`f7625a9de909a991013db6ce43c68bee65402ce0`**, dirty state and **26,785 existing file hashes**. The post-inspection comparison finds **26,785 unchanged, zero changed or missing** relative to this stage's baseline.

- All **73 original baseline PDFs** remain byte-identical. The current snapshot also includes the eight additional PDF-named test artifacts from attempt 03: **81 PDF-named files total**, all unchanged in this stage.
- All ten historical diagnostic JSON files, previous Gate A/B0/B1 reports/logs/manifests, Phase 1–3 implementation/evidence and unrelated Batch G artifacts remain unchanged.
- Existing generated Laravel logs and compiled Blade caches were not cleared or restored.
- The known missing temporary fake-upload metadata file remains a documented historical exception. It was already absent before this stage; it was not reconstructed or counted as newly lost.
- Database configuration preservation was not newly measured because no connection opened. This stage made no database changes.

The only new public deliverable is this report; private baseline outputs are new verification artifacts. No runtime, test, permission manifest or existing helper file was edited.

## Smallest proposed amendment and next step

Request separate approval to add **only the existing `applicant.sandbox.publish` permission to the namespace's `osds` fixture identity**, designate it as `granted_by` for all six eligibility grants, and explicitly record that additional permission in the private ledger and subsequent navigation expectations. Keep every other permission union unchanged. This expands one fixture's authority and navigation visibility, so it is a proposal requiring review, not an inferred implementation choice.

No new application permission, role, workflow rule, schema change or runtime correction is proposed. Do not add `questionnaires.retire`, a nationwide grant or any authority to mutate protected published roots. The existing application contracts should remain intact.

After an amended manifest is explicitly approved, resume B2 only: implement the fixed browser-only guards/preparer, perform the outstanding read-only identity/root/schema/namespace checks, validate the entire operation set, and only then create the additive foundational records transactionally. Stop for any additional missing dependency. B3/B4, office-directory/personnel setup and browser authentication remain excluded.

**Foundations are not ready for personnel setup or authenticated acceptance. STOP for review of the manifest amendment.** No database operation, commit, push, deployment or subsequent stage occurred.
