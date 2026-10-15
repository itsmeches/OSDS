# Provisional account and permission matrix

**Phase 3A checkpoint, 14 October 2026.** These are approved technical defaults for implementation and synthetic testing. They are not final CHED policy and confer no COR, ACSA validation, certificate, registry, approval, signing, issuance, export, or RA 11180 authority.

The five roles do not inherit from one another. Every operation requires an explicit permission plus current region, institution, or nationwide scope. Nationwide visibility is never implied by a role.

## Account governance

| Action | Super User | Admin | OSDS | CHEDRO | SCHOOL |
| --- | :---: | :---: | :---: | :---: | :---: |
| Create Admin | Allow | Deny | Deny | Deny | Deny |
| Create OSDS | Deny | Allow | Deny | Deny | Deny |
| Create CHEDRO | Deny | Deny | Allow | Deny | Deny |
| Create SCHOOL | Deny | Deny | Deny | Allow in authorized institution | Deny |
| Assign the permitted target role | Allow for Admin | Allow for OSDS | Allow for CHEDRO | Allow for SCHOOL | Deny |
| Verify SCHOOL representative | Deny | Deny | Deny | Allow in authorized institution | Deny |
| Activate, suspend, deactivate, reactivate | Only directly managed account type | Only directly managed account type | Only directly managed account type | Only directly managed SCHOOL | Deny |
| Assign/revoke regional scope | Explicit permission and scope | Explicit permission and scope | Explicit permission and scope | Deny | Deny |
| Assign/revoke institution representation | Explicit permission and scope | Explicit permission and scope | Deny | Explicit permission and scope | Deny |
| Assign/revoke nationwide scope | Explicit permission | Deny | Deny | Deny | Deny |
| View security audit | Scoped permission | Scoped permission | Scoped permission | Deny by default | Deny |
| View national security audit | Requires `audit.view.national` and an active nationwide grant | Deny | Deny | Deny | Deny |

Target-specific creation permissions are configured in `config/account-governance.php`. Policies and domain services enforce them; the displayed role order is never compared as a rank. Self-assignment, self-escalation, and delegation outside the actor's scope are denied.

## Implemented permission keys

- Panel and directories: `platform.admin.access`, `users.view`, `users.manage`, `regions.view`, `regions.manage`, `institutions.view`, `institutions.manage`.
- Creation: `accounts.create.admin`, `accounts.create.osds`, `accounts.create.chedro`, `accounts.create.school`.
- Lifecycle: `accounts.verify.school`, `accounts.activate`, `accounts.suspend`, `accounts.deactivate`, `accounts.reactivate`.
- Delegation: `roles.assign`, `region_scopes.assign`, `region_scopes.revoke`, `institution_representatives.assign`, `institution_representatives.revoke`, `nationwide_scopes.assign`, `nationwide_scopes.revoke`.
- Audit: `audit.view`, `audit.view.national`.
- Questionnaire configuration: `questionnaires.view`, `questionnaires.draft.manage`, `questionnaires.review.submit`, `questionnaires.review`, `questionnaires.publish.sandbox`, `questionnaires.retire`, `questionnaires.preview.sandbox`, `questionnaires.publication.view`.
- Applicant sandbox: `applicant.sandbox.publish`, `applicant.portal.access`, `applications.draft.create`, `applications.draft.view`, `applications.draft.update`.

## Phase 3B applicant sandbox

| Action | Super User | Admin | OSDS | CHEDRO | SCHOOL |
| --- | :---: | :---: | :---: | :---: | :---: |
| Publish approved configuration to applicant sandbox | Deny | Allow | Deny | Deny | Deny |
| Grant synthetic organization/module eligibility | Deny | Allow | Deny | Deny | Deny |
| Enter applicant portal | Deny | Deny | Deny | Deny | Allow with all sandbox safeguards |
| Create/view/update own synthetic draft | Deny | Deny | Deny | Deny | Allow with verified representation and eligibility |
| View another applicant's answers | Deny | Deny | Deny | Deny | Deny |

These permission assignments do not establish real COR/ACSA eligibility. Production and anonymous publication channels do not exist. Applicant access additionally requires the isolated environment and database, synthetic identity and organization, active verified representation, explicit effective eligibility, manifest effectivity, and ownership checks.

## Questionnaire configuration

| Action | Super User | Admin | OSDS | CHEDRO | SCHOOL |
| --- | :---: | :---: | :---: | :---: | :---: |
| View configuration | Deny | Allow | Allow | Deny | Deny |
| Create/edit/reorder draft | Deny | Deny | Allow | Deny | Deny |
| Submit exact versions for review | Deny | Deny | Allow | Deny | Deny |
| Approve/reject reviewed hash | Deny | Allow | Deny | Deny | Deny |
| Publish to synthetic sandbox | Deny | Allow | Deny | Deny | Deny |
| Preview synthetic manifest | Deny | Allow | Allow | Deny | Deny |
| Retire sandbox manifest | Deny | Allow | Deny | Deny | Deny |

Super User deliberately receives no questionnaire permission through its role bundle. Production publication has no permission, channel, or service in Phase 3A.

`users.manage` remains for backward-compatible directory editing. It does not by itself authorize account creation, role assignment, lifecycle changes, or scope delegation.

## Configuration and code boundaries

| Change requested later | Mechanism |
| --- | --- |
| Which role receives an existing permission | Seeder/configuration change plus review and tests |
| Which permission is required to create a target account type | `account-governance.creation_permissions` configuration |
| Whether a SCHOOL creator may also verify | `OSDS_ALLOW_SCHOOL_CREATOR_TO_VERIFY`; defaults to false |
| Which production roles require MFA | Deployment configuration after MFA is implemented |
| Add a new lifecycle state or transition | Code, migration, policy, and tests |
| Permit multiple primary roles | Forward migration dropping the single-role index, redesigned conflict rules, policies, UI, and tests |
| Change scope/delegation semantics | Policy/domain-service code and tests |
| Add an athletic-association account type | Stakeholder decision followed by organization/schema and authorization review |
| Set retention durations | Approved records schedule plus configuration; deletion remains disabled until approved |

Security configuration is version controlled and deployment reviewed. It is not editable through a general Filament settings page.

## Formal approval still required

CHED must approve the first Super User custodian and recovery process; creator-versus-verifier separation; verification evidence; final lifecycle authorities; nationwide grant custodians; production MFA and SSO policy; emergency access; audit reviewers; retention periods; transfers; and athletic-association mapping.
