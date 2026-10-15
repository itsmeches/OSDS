# Save Draft correction and application-wide error-handling plan

**Status:** Stage A implemented and verified; Stage B proposed for approval
**Date:** 15 October 2026

## Stage A — Save Draft investigation and correction

### Root-cause report

The stored ACSA questionnaire v2 and Documents v2 pair was loaded from the development PostgreSQL database and passed the complete authoring transaction inside an outer rolled-back diagnostic transaction. The stored records are valid and were not corrupted.

The observed failure occurs when the submitted working state fails backend validation. The Form Builder caught `ValidationException`, changed only the global state to **Save failed**, and added service error keys directly to Livewire. Documentary requirement validation uses service keys such as `requirements.0.provider_ids`, while the component property is `documentRequirements.0.provider_ids`. Those errors therefore had no matching input or rendered error block. Several other fields also lacked inline error output. The user saw a generic failure even though the backend knew the exact cause.

The original failed browser payload cannot be reconstructed after the fact: it correctly was not persisted, and raw Livewire payloads are not logged. Consequently, the exact field that was invalid in that historical click cannot be identified safely. Reproducing an invalid document-provider selection demonstrated the same generic failure and key mismatch. Reproducing the current persisted pair demonstrated that a valid configuration saves.

### Data impact

- The save service validates before entering its destructive synchronization transaction.
- A validation failure writes no section, question, option, requirement, lock-version, or success-audit change.
- The browser retained its Livewire working state, although the prior interface did not explain this.
- Published and in-review configuration protections were not involved and remain unchanged.

### Applied correction

- Translate backend `requirements.*` and `title` validation paths to their actual Livewire properties.
- Render a persistent page-level error summary with links to the affected editing area.
- Render inline errors for questionnaire title, section title, document name, link counts, and accepted provider types.
- Preserve current working values and explain that they remain available.
- Keep the save state as **Saved** only after the service transaction commits and returns new lock versions.
- Add a persistent warning notification for validation failures while retaining the page-level details.
- Preserve the existing conflict message and all authorization, transaction, lock, trigger, and immutability checks.

### Regression coverage

- A fully loaded valid draft saves through the Livewire page and advances both lock versions.
- An invalid document-provider selection shows the mapped field error and error summary.
- Unsaved changed text remains in component state after the failed request.
- The stored requirement and both lock versions remain unchanged after failure.

No database migration was introduced.

## Stage B — Existing error-handling audit

Stage B is not implemented by this document.

| Area | Current finding | Risk |
| --- | --- | --- |
| Form Builder | Validation now has mapped feedback, but authorization, session loss, transport failure, and unexpected exceptions have no coordinated recovery contract. | Users may not know whether the server received a request. |
| Applicant draft editor | All validation errors are flattened into one `save` error; no field mapping or persistent summary exists. A new random idempotency key is generated for every invocation and is not retained for an uncertain-response retry. | Weak correction guidance and no safe reconciliation after a lost response. |
| Sandbox publication | Broad `Throwable` catches display `$exception->getMessage()` directly. | SQL, paths, hashes, or other internal details could be exposed. |
| Review, clone, retire, identity and scope actions | Success feedback is common; expected domain failures mostly depend on Filament/Laravel defaults. | Inconsistent wording and recovery actions. |
| Authentication and account state | Inactive accounts return a direct 403 message; session expiration follows framework behavior. | Livewire requests may present unclear expired-session or access-change behavior. |
| Global exception configuration | JSON negotiation exists, but there is no application reference ID, safe unexpected-error envelope, or shared domain-to-boundary mapper. | Support cannot reliably correlate safe user reports with diagnostics. |
| Diagnostics | Laravel logs exceptions, but operations and sanitization are not standardized. Audit records and diagnostic logs are conceptually separate but not governed by one error policy. | Excessive context may be logged inconsistently, while useful operation context may be absent. |
| Accessibility | Some errors use inline text and `role=alert`; there is no shared focus/summary behavior or tested notification contract. | Keyboard and screen-reader users may miss blocking errors. |

## Proposed architecture

### 1. Expected failure categories

Introduce a small presentation-oriented classification without replacing domain exceptions:

| Category | Boundary behavior | User treatment |
| --- | --- | --- |
| Validation | Laravel validation errors; HTTP 422 where applicable | Inline errors plus persistent summary and direct links |
| Authentication/session | Framework authentication; HTTP 401/419 as applicable | Explain that the session ended and offer a sign-in/reload recovery path without echoing private state |
| Authorization | Policy/domain denial; HTTP 403 or privacy-preserving 404 | Explain unavailable action only when disclosure is safe |
| Not found/inaccessible | HTTP 404 | Do not confirm restricted record existence |
| Concurrency conflict | Dedicated domain conflict exception or mapped validation key; HTTP 409 at JSON boundaries | Preserve local input and offer **Review latest version** |
| Invalid workflow state | Domain exception; HTTP 409/422 depending boundary | State why the action is unavailable and the permitted next step |
| Integrity conflict | Map known constraint cases after domain validation; HTTP 409 | Plain-language conflict without SQL details |
| Rate limit | HTTP 429 | Explain retry timing |
| Temporary service/transport | Appropriate 503/network client state | Keep input and distinguish confirmed rollback from unknown outcome |
| Unexpected internal error | Existing exception reporting plus safe reference ID; HTTP 500 | Generic persistent message with support reference |

Domain services retain business-specific exceptions and transaction rules. A shared mapper converts only known exceptions at HTTP, Livewire, and Filament boundaries.

### 2. Shared feedback contract

Create reusable presentation support for:

- a page-level error summary with heading, explanation, recovery action, and field anchors;
- field-level validation messages using the actual component property path;
- persistent blocking alerts for conflicts, lost access, read-only state, and uncertain saves;
- brief toasts for confirmed success and nonblocking information;
- consistent loading/disabled states for in-flight actions;
- screen-reader live regions and predictable focus movement to the error summary;
- light/dark semantic status tokens from the established design system.

Every message answers what happened and what the user can safely do next. A reason is included only when the application knows it and disclosure is safe.

### 3. Unsaved-work and uncertain-response rules

- Keep Livewire fields intact on validation, authorization changes, conflicts, and transport errors.
- Maintain an explicit last-confirmed server version and local dirty state.
- Disable repeated action controls while a request is active.
- Retain an operation idempotency key until a response is confirmed for operations that support retry.
- After an uncertain network result, display **Save status unknown** and reconcile through an idempotent read-back before declaring success or failure.
- Preserve existing before-unload warnings and apply the pattern to other editable implemented workflows.
- Never place private draft values into session flash messages or redirect query strings.

### 4. Safe diagnostic references

For unexpected exceptions:

1. Generate a random correlation ID formatted for display, for example `OSDS-7K4M2Q`.
2. Return only the reference and safe generic guidance to the user.
3. Log category, operation name, timestamp, exception class, safe resource public ID/version, actor ID when appropriate, and correlation ID.
4. Exclude raw Livewire payloads, applicant answers, evidence locators, passwords, tokens, cookies, personal details, questionnaire content, SQL bindings, and stack traces from user responses.
5. Keep the business audit trail separate. Failed diagnostics must never be recorded as successful domain actions.

Laravel's exception reporter may retain stack traces in protected server logs; the application-level structured context must remain sanitized and production access controlled.

## Proposed implementation packages

### EH1 — Shared classification and safe unexpected-error boundary

**Likely files:** `bootstrap/app.php`, new classes under `app/Exceptions/` and `app/Support/Errors/`, logging configuration, focused tests.

- Define domain-safe conflict/workflow exception types where current validation keys are overloaded.
- Add a mapper for Livewire/Filament/HTTP presentation.
- Generate and log safe support references for unexpected failures.
- Replace direct exception-message disclosure in sandbox publication actions.
- Preserve Laravel status codes and privacy-preserving 404 behavior.

**Acceptance:** expected failures retain their status/category; unexpected failures expose no technical message and can be correlated through a sanitized reference.

### EH2 — Shared feedback components and accessibility

**Likely files:** reusable Blade/Livewire components, Filament notification helpers, shared CSS tokens, component tests.

- Add error summary, blocking alert, save-state, and recovery-action components.
- Standardize success, information, warning, error, processing, retry, denial, session, and conflict language.
- Move focus to blocking summaries and announce state changes without duplicate screen-reader output.
- Verify contrast in Light, Dark, and System themes.

**Acceptance:** keyboard and screen-reader users can locate errors and their fields; serious failures remain visible until addressed.

### EH3 — Editable workflow adoption

**Likely files:** Form Builder, applicant `DraftEditor`, questionnaire creation, related domain services and views.

- Apply field mapping and error summaries consistently.
- Preserve dirty values and last confirmed version.
- Give concurrency conflicts an explicit recovery action.
- Make applicant idempotency keys stable across an uncertain retry and add read-back reconciliation.
- Add session-expiry and transport-failure hooks without storing private answers outside the component/browser state.

**Acceptance:** validation, stale-tab, revocation, session, and network scenarios preserve local work and never falsely report success.

### EH4 — Consequential staff action adoption

**Likely files:** review/approval/publication pages, clone/retire actions, user/access resources and governance services.

- Map known workflow and authorization failures to safe persistent feedback.
- Keep confirmations and idempotency appropriate to each action.
- Prevent exception strings from appearing in notifications.
- Provide next actions for expired publication, immutable configuration, and changed permissions where disclosure is permitted.

**Acceptance:** OSDS, Admin, and authorized account-management actions use the same categories without weakening domain checks.

### EH5 — Verification and operational documentation

- Add regression cases listed in the authorization request, including rollback, concurrency, expired sessions, changed permissions, uncertain network behavior, duplicate requests, log redaction, and accessibility.
- Document message-writing rules and the diagnostic-reference support procedure.
- Run the full PostgreSQL suite, Livewire tests, Pint, production build, and diff checks.
- Record manual accessibility results only when actually performed.

## Security boundaries

- Do not turn 404 denials into messages that reveal private records.
- Do not catch and convert authorization failures into successful HTTP responses.
- Do not expose database messages, exception strings, internal paths, hashes, or stack traces.
- Do not log raw applicant answers, evidence links, credentials, sessions, or full Livewire payloads.
- Do not weaken publication hashes, immutable triggers, exact version binding, row locks, transactions, policies, audit append-only behavior, or applicant ownership.
- No Phase 3C or new production workflow is included.

## Stage B approval boundary

The application-wide redesign above remains a proposal. Stage A changed only the Form Builder validation feedback and its regression tests. Stage B implementation must wait for explicit approval.
