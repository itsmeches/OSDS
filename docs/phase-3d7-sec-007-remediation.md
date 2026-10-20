# SEC-007 — Target-account credential boundary remediation

**Date:** 20 October 2026 (Asia/Manila).
**Status:** Targeted remediation and final security/regression gates passed. Ordinary Batch F work may resume under the existing authorization. Batch F remains NOT READY. No production approval.

## Confirmed root cause and exposure

The generic Filament EditUser page inherited direct record persistence. `UserPolicy::update` allowed scoped users with `users.manage` to edit records without checking target-account authority. The form included email and password. An isolated Admin therefore persisted a new Super User password despite `AccountGovernance::canManageTargetRole` returning false. This was actual Filament/Livewire persistence, not a visibility-only finding. The original retained regression failed (1 test / 6 assertions).

Actual target sign-in, historical exploitation and production impact were not investigated. No manual account, credential or session was inspected or repaired.

## Existing semantics retained

| Operation | Boundary |
| --- | --- |
| View accounts | Existing `users.view`, active identity and staff scope/policy. No new role hierarchy. |
| Edit name | Existing `UserPolicy::update`: active actor, `users.manage`, effective shared regional/nationwide scope, including its existing self-record exception. |
| Change email/password | The same profile boundary plus existing target-specific creation permission through `canManageTargetRole`; self credential changes stay closed. |
| Status and role changes | Existing distinct permissions/services; not writable through the profile service. No new status/role UI. |
| Regional/organization authority | Existing separate grant/representation paths; never writable through profile data. |
| Create account | Existing delegated CreateUser/AccountGovernance workflow, role permission and scope. |
| Self service/recovery | No dedicated existing workflow. Name edits retain the existing policy exception; email/password changes are denied here. No new recovery semantics. |

Email is an authentication identifier in existing staff/applicant sign-in. It receives the same target-authority check as password. The application does not enable the User `MustVerifyEmail` contract or a dedicated account recovery UI. No new verification mail, identifier-change confirmation or recovery process is introduced; existing verification metadata is not writable through this editor.

## Implementation and files

- `app/Policies/UserPolicy.php`: `updateCredentials` combines existing profile scope with target-specific authority, and rejects self credential editing.
- `app/Services/Identity/AccountGovernance.php`: transactional `updateProfile` reloads actor/current authority, locks/reloads target, authorizes before mutation, permits only name/email/password and validates before saving. No partial write on rejection. Existing User hashing and observer auditing remain.
- `app/Filament/Resources/Users/Pages/EditUser.php`: current-actor authorization on mount/hydration/save, minimal form hydration, raw credential authorization before schema dehydration, guarded security fields and service-only persistence. Both inherited Save and component-only Save reach the protected handler.
- `app/Filament/Resources/Users/Schemas/UserForm.php`: login fields are read-only where target authority is absent. This is explanatory presentation; raw forged fields are independently denied server-side.
- `CreateUser.php` and `EditUser.php`: password state is removed before every Livewire response, including validation failures. Users re-enter passwords after a server validation error.
- `Pages/Concerns/HandlesAccountWrites.php`: unexpected failures log only support reference and exception class, and show safe field feedback. Authorization and validation retain their normal boundaries. No raw exception/SQL/credential diagnostics.
- `PolicyRefinementTest.php`: original regression retained and strengthened to send password plus Save atomically, as a real browser does. The nonmutation assertion remains. A separate fill request is insufficient after deliberate response redaction.
- `AccountEditSecurityTest.php`, isolated `Prepare-AccountSecurity.php` and `verify-account-security.mjs`: expanded transport, persistence, failure and browser tests.

Hidden create-only fields can produce null placeholders in whole-form updates. They are never persisted by the edit schema/service. The read-only synthetic flag is initialized from the actual record, preventing a hidden-field default from rejecting legitimate synthetic-account saves. The positive regression sends the browser's entire form state.

## Credential and response handling

The User hashed cast remains authoritative; passwords are neither stored as plaintext nor returned in response snapshots. Initial state excludes stored password/token values. Public `getRecord` and `refreshFormData` helper response tests retain hidden-value protection. Denied saves/public-helper calls after permission/scope revocation return 403/404 without credential or protected target email disclosure.

Audit observers retain attribution and changed-field names only, never credential values. Unexpected write failure tests verify redacted logs and unchanged account/audit state. The single failure test mocks only an injected service outage; the original exploit and authorization matrix use real persistence.

No forced historical session deletion, remember-token rotation or recovery policy is added. Existing staff AuthenticateSession behavior and existing status/role-service invalidation remain; the profile path previously had no separate session-reset action. Wider session-policy changes require separate approval.

## Verification so far

- Focused PostgreSQL/Livewire matrix: **7 tests / 122 assertions passed**.
- Browser journey: **5 checks**, including forbidden Super User password forgery, authorized delegated email/password change with one persisted update under double click, server validation redaction, permission revocation and scope revocation after mount.
- **4 light/dark desktop/mobile screenshots**, zero axe findings, page overflow or uncaught errors; no credential response leaks detected.
- Actual browser/native required validation blocks submission locally and keeps RAM input. To test server-validation redaction separately, the browser test disables native validation before sending the invalid request. This is explicit fault-path coverage, not a claim that native validation submits invalid forms.
- Original E-INT-002 reproducer and all 14 selection-isolation cases passed; original C-INT-001 reproducer and all 11 expanded applicant cases passed. The complete final PostgreSQL suite passed **124 tests / 1,640 assertions**, including E-INT-001 and SEC-001–006. Laravel Pint, production Vite build and working-tree diff check passed; pre-existing staged whitespace remains without index changes.

## Coverage and limits

The transport matrix covers Admin→Super User denial for email/password; Admin→OSDS and Super User→Admin permitted credentials; cross-region, expired, future, revoked, inactive and permissionless entry; changed actor/target authority after mount; self profile versus credentials; forged security fields; direct HTTP helpers/saves; invalid data; exact hashes, roles/status preservation and audit nonmutation.

There are no implemented role/permission editors, bulk account actions, reset API, self-profile or password-recovery pages. Existing table actions are Edit, synthetic SCHOOL enablement and verification; they do not accept arbitrary credential fields. Role/status governance exists as separate service methods; no new endpoints are introduced or claimed browser-tested. Framework public helper behavior is tested, not assumed safe from UI hiding.

Chrome automation is not human usability, screen-reader or full WCAG certification. Native zoom, third-party identity/recovery integration and historical credential-impact assessment remain outside this targeted verification. Only `osds_test` and `osds_ui_browser_test` are used; `osds_sandbox` and `osds_dev` remain untouched. Existing staged changes are preserved. Targeted gates are complete; resume ordinary Batch F work and pause before Batch G. Batch H and Phase 4 remain excluded.

## Browser evidence and reproduction

[Light desktop](screenshots/phase-3d7-sec007/validation-light-1440.png), [Dark desktop](screenshots/phase-3d7-sec007/validation-dark-1440.png), [Light mobile](screenshots/phase-3d7-sec007/validation-light-375.png), [Dark mobile](screenshots/phase-3d7-sec007/validation-dark-375.png). Developer screenshot inspection covered dark desktop and light mobile. These images show cleared password inputs and fictional accounts only. External avatar requests are blocked in isolated browser tests.

Run `php scripts/Prepare-AccountSecurity.php prepare` and `node scripts/verify-account-security.mjs` from the development shell against the reserved router on port 8017. The script prepares its own new fixtures and refuses other databases. Machine-readable results contain safe status/field-key booleans, not request values or hashes. The permission-revocation fixture retains all original permissions except `users.manage`, proving that read permission alone does not grant a write.

The original regression remains in PolicyRefinementTest, with the atomic actual form-update/Save request and unchanged nonmutation assertion. No production account inspection, historical repair, schema migration, role hierarchy, new permission or broad authentication change was performed. Password strength, additional confirmation/reverification and future recovery semantics remain separate policy questions; existing field behavior was preserved.
## Subsequent independent stop

On resuming authorized Batch F source inventory after the gates above, SEC-008 was independently reproduced: generic assignment editing permits self-extension of regional authority. The new failing regression is retained separately in PolicyRefinementTest. No SEC-008 fix was applied; Batch F is stopped pending targeted authorization. This does not reverse the passing SEC-007 credential boundary results, but it prevents an overall green/current-tree readiness claim. See the issue register and Batch F report. Historical or manual-testing accounts/grants remain untouched.

### Later checkpoint - 20 October 2026

The SEC-008 stop immediately above is historical and has been remediated under its separate authorization; see the SEC-008 document. The credential browser journey was rerun during ordinary Batch F and again passed five interactions/four light-dark desktop-mobile screens, with zero reported credential-response leaks, axe findings, overflow or page errors. This does not certify production or human acceptance.
