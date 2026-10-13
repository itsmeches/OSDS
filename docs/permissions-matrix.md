# Provisional account and permission matrix

**Review draft.** The five account names/order were confirmed to the user by a supervisor but are not final. The hierarchy `Super User → Admin → OSDS → CHEDRO → SCHOOL` gives **no automatic permission inheritance**. This matrix proposes access for review; it does not authorize any CHED action.

**Phase 2 implementation note:** The five role names and foundational directory permissions are implemented for technical validation. Role grants remain provisional. No COR/ACSA approval, signature, issuance, registry, certificate, export, or RA 11180 permission exists in code. Region and institution scope is checked independently of the role label.

## Enforcement model

Use one top-level Spatie role per account on the `web` guard. Name permissions by resource and action, such as `application.screen`, `registry.create`, `certificate.issue`, `form.publish`, `export.case_data`. Laravel policies additionally check user status, active regional/organization grant, case state, and formal delegation. Deny by default. Filament navigation and UI visibility never replace policy checks. Audit role/grant changes and privileged access.

`P` = proposed within assigned scope; `C` = only with a separate, approved action grant/delegation; `—` = deny. No `C` permission is seeded as effective by default.

| Action | Super User | Admin | OSDS | CHEDRO | SCHOOL |
| --- | :---: | :---: | :---: | :---: | :---: |
| Change platform configuration | P | C | — | — | — |
| Provision/deactivate accounts and scope grants | P | P | C | C | — |
| Draft/publish forms, requirements, templates | C | C | C | — | — |
| View national aggregate counts | C | C | P | — | — |
| View detailed case | C | C | C | P, region | P, represented organization |
| Submit/correct own case | — | — | — | — | P |
| Screen and request correction | — | — | C | P, region | — |
| Validate and record findings | — | — | C | P, region | — |
| Create registry entry or official number | — | — | C | C, region | — |
| Record approval/signature/issuance | — | — | C | C, delegated case | — |
| Export case-level data or audit | C | C | C | C, region | — |
| View own released certificate | — | — | — | — | P |

**Nationwide recognition lookup:** Candidate SCHOOL/HEI and staff capability over a small approved registry projection (association name, certificate/status, validity if authorized). It does not grant case detail or document access. Its audience and visible fields need registry-owner approval.

**Applicant representation:** A school/association user may submit only for organizations with a verified `user_organization_grant`. The meeting mentioned school athletic directors/sports coordinators and association secretariats as likely focal persons; these are not approved identity checks. Decide whether associations use SCHOOL-tier accounts or another future account type before enrollment.

## Supervisor and process-owner decisions

1. Who creates the first Super User and administers recovery; who provisions each lower tier; whether regional staff may provision SCHOOL accounts.
2. Whether Admin/OSDS can view national case details or only aggregates; export approval; emergency access and audit review.
3. Whether a person can hold multiple top-level roles; if not, how temporary assignments work; whether CHEDRO screening and validation must be separate people.
4. Which office owns national versus regional athletic association cases and transfers across regions.
5. Who is legally authorized to approve, sign, assign registry/certificate numbers, and issue COR/ACSA. The account label alone never establishes authority.
6. RA 11180 HEI, regional sports-coordinator, and Central Office mapping into these tiers, separately from certificate duties.

## Acceptance tests for implementation

For every role/action combination, test allow and deny at route, Filament resource/action, Livewire action, export, and file/link retrieval. Repeat with wrong region, wrong organization, deactivated user, revoked grant, stale delegation, and forged case ID. Test that a higher account tier cannot infer lower-tier privileges or sign/issue without explicit authority.
