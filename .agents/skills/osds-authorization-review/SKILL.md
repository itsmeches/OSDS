---
name: osds-authorization-review
description: Review OSDS Laravel authorization, regional and module scope, stage separation and revoked-access behavior without weakening workflow protections.
---

Apply [OSDS Verification Gates](../osds-verification-gates/SKILL.md) as the shared safety contract, using the current user authorization.


Consult [architecture](../../../docs/architecture.md), the actual policies/services and the relevant acceptance requirement. Review routes, component handlers and final mutations separately from navigation visibility.

- Derive task visibility from existing effective permissions and destination access checks, never role-name branches. A hidden control is not a denial test; an approved case is not proof of issuance eligibility.
- Trace active/synthetic-account admission, owner/representation access, region/module authority, exact reviewer assignment and stage permission through the final service call. Fresh authority matters after a mounted page or revoked permission.
- Preserve case-wide evaluator/Chief/Director separation across revisions. Combined permissions may produce combined descriptions but cannot bypass same-person exclusions.
- Check stale optimistic locks, exact current immutable submission/decision binding and duplicate prevention. Do not weaken negative assertions, alter permissions or change stored state to make a scenario pass.
- Treat applicant-facing history and internal notes as different disclosure surfaces. Avoid collecting unrelated applicant records during review.

Prefer source traces and meaningful existing tests. Execute database tests or browser negatives only within the current explicit operation/database authorization. Report gaps as unresolved, rather than proving access from a simulated profile or static permission catalogue.

## Evidence and completion

Required evidence: route/page access contract, policy and final-service call chain, relevant scope/permission fixtures or authorized test results. Complete with each reviewed boundary, preserved negative assertions and explicit unresolved runtime gaps.
