# Security and records plan

**Phase 0 review draft.** The system is expected to face external school users and may contain personal data of representatives, coaches, and student-athletes. Records, privacy, IT, and process owners must approve controls before launch.

## Identity and authorization

- School/association enrollment must verify the organization's designated representative. Provisioning, recovery, staff identity provider use, and association mapping need an approved procedure.
- Require MFA for privileged staff accounts; assess applicant MFA/accessibility and session lifetime. Disable sessions and grants promptly on departure or reassignment.
- Spatie roles name broad actions; Laravel policies check active user, organization/region, case state, and delegation on every request and background action. Deny by default. No Super User bypass of records scope by name alone.
- Add login and share-link rate limits, session/CSRF protection, input validation, output encoding, secure cookies, and a content security policy compatible with Filament/Livewire. Treat Livewire action parameters as hostile input. [Laravel authorization](https://laravel.com/framework/docs/13.x/authorization), [Livewire security](https://livewire.laravel.com/docs/4.x/security).

## Evidence and document security

**Decision gate:** Choose hosted private files, restricted individual-file links, or controlled hybrid. The meeting contains both upload and link proposals; neither is final.

| Mode | Required controls before enablement |
| --- | --- |
| Hosted | Private Laravel disk, no public symlink for case files, size/type validation, malware scan/quarantine, hash, version and authorized temporary retrieval, backup/restore. |
| External file link | Individual-file URL only, encrypted URL if token-bearing, access and link-health verification, record owner/custodian, version/change detection, no link in logs/exports, contingency for expired access and records retention. |
| Physical original | Record requirement, receiving office/person, receipt date, storage location, and linkage to digital case without treating an unreceived original as present. |

For both digital modes, deny access to unverified evidence and reauthorize each retrieval. Do not place document URLs or payloads in audit logs or notification emails. [Laravel filesystem private storage and temporary URLs](https://laravel.com/framework/docs/filesystem), [Livewire upload behavior](https://livewire.laravel.com/docs/4.x/uploads).

## Audit, secrets, and recovery

- Append immutable audit events for login, grant changes, case transitions, review/approval, registry numbering, certificate issuance, exports, evidence access, and exceptional access. Include actor, role, region, resource, result, time, correlation ID; exclude secrets and full documents.
- Keep secrets in an approved secret manager or protected environment, never in source, fixtures, screenshots, or logs. Rotate keys/credentials under an owner-approved procedure.
- Encrypt database/storage backups, restrict restore access, define backup frequency and recovery targets with IT/records owners, and perform a documented restore drill before production.
- Queue jobs are idempotent, start after commit, and surface failed scans, rendering, notifications, and link checks. Failure cannot automatically advance official status.
- Monitor authentication anomalies, privileged changes, queue failures, storage capacity, and backup status. Define incident responder and escalation route.

## Release gate and security tests

The partial transcripts mention vulnerability/penetration testing clearance before public deployment but vary between “DICT” and “ICT” as the authority. Confirm actual approver, procedure, scope, and evidence with IT/security; do not present the transcript as a regulation. Test cross-region and cross-organization access, guessed identifiers, forged Livewire calls, expired/revoked share links, upload spoofing/malware, broken/changed external links, duplicate issuance, concurrent number assignment, backup restoration, and approximately 3,000-school planning load after real usage figures are obtained. Do not expose real applicant data in test environments.
