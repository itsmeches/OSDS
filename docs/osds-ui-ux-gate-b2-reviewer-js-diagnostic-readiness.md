# Gate B2 — JavaScript diagnostics and attempt-04 readiness

**28 October 2026. PASS for source preparation and offline checks. Attempt 04 is NOT RUN or reserved. Live execution remains BLOCKED pending separate authorization.** No database, Laravel bootstrap, server, browser, authentication or lifecycle action occurred. B3/B4 remain closed.

## Evidence and diagnostic limitation

Reviewed attempt-03's report, private browser/server results and the reviewer driver, policy, launcher and bootstrap. The old handler was:

```javascript
page.on('pageerror', () => fatal('JavaScript page error'));
```

It discarded the original exception object: name, message and stack. The HTTP records omitted request methods and explicit login-state flags. Tracing starts only after confirmed login, so the retained evidence cannot recover the missing exception or conclusively establish credential acceptance. The underlying attempt-03 JavaScript cause remains **UNKNOWN**. No runtime or authorization defect is inferred.

## Files changed

| File | Change |
| --- | --- |
| `scripts/uiux-b2-error-diagnostics.mjs` — new | Pure, bounded exception/phase/path sanitization; no browser, filesystem, credentials or framework imports |
| `scripts/verify-uiux-b2-reviewer-only.mjs` | Preserve the first sanitized page error, add safe login-state diagnostics and GET/POST attribution, explicitly check visible controls/component and authenticated navigation; fixed attempt-04 output path |
| `scripts/Run-UiUxB2Reviewer.mjs` | Attempt-04 root, provisioning name and reservation ID only |
| `scripts/UiUxB2PersonnelBootstrap.php` | Attempt-04 root and reservation ID only |

The pure request policy, filesystem provisioning helper, inspector, application source and authentication services are unchanged. Offline comparisons confirm that launcher/bootstrap changes consist solely of advancing fixed attempt identifiers.

## Safe exception capture

Future `pageerror` evidence contains:

- A native exception name from a closed allowlist; unknown/custom names become `RestrictedError`.
- A normalized message only for a few recognized JavaScript templates. Property and identifier names are replaced, not exported. Unknown, sensitive or oversized messages become `[restricted exception details]`.
- At most **eight** sanitized stack frames, with bounded line/column numbers and categorized same-origin paths. Function names, raw frame text and asset filenames are not exported. Unrecognized frames become `restricted-frame`.
- Validated workflow phase, safe current pathname, and booleans for displayed login controls, attempted validated login POST and confirmed authentication.

Input messages over **2,048 characters** are restricted. Stacks over **8,192 characters** become an oversized-stack category. Output messages use fixed short templates; pathname values come from a small page allowlist or fixed categories. Arbitrary phase text is restricted.

Credential email/password matches and credential/token-like patterns cause text restriction before evidence serialization. Query strings, fragments, URL credentials, cookies, headers, request bodies and browser storage are never diagnostic inputs written to evidence. Same-origin page paths are retained only for `/admin`, `/admin/login` and `/admin/case-demonstration-documents`; assets/update endpoints are categorized, and external/unknown paths are restricted.

The workflow's final error handler also uses this sanitizer instead of copying arbitrary Playwright exception text. It records a fixed public-facing failure label. The first page-error diagnostic is retained before rejecting the fail-fast promise; errors are not suppressed and no reload or retry is added. Response history is capped at **80 records**. Existing authenticated-only tracing remains unchanged; no pre-login trace/body capture was introduced.

These restrictions deliberately reduce detail. In particular, unrecognized framework/Alpine messages may remain restricted, and stack asset categories do not identify an exact file. A sanitizer fallback is not a successful diagnosis. No guarantee about recovering the already discarded attempt-03 error is made.

## Login-flow source review

The installed source declares `Filament\Auth\Pages\Login::authenticate()`. Its email/password fields and sign-in controls match the existing selectors. Authentication includes credential validation, panel admission, rate limiting and any configured challenge flow. `LoginResponse` redirects to the intended URL or panel URL; HTTP 200 alone does not prove success. None of those application paths was changed.

The prepared driver now:

1. Records `login-get`, requires visible email/password/sign-in controls, then marks `loginFormDisplayed` without retaining field values.
2. Requires exactly one login component with the expected class identity; records only the result, not snapshots or form data.
3. Records `login-ready`, `login-submit` and `login-verifying`. `loginPostAttempted` becomes true only after the existing policy identifies a permitted login POST immediately before forwarding; this is not a claim that the server accepted credentials.
4. Requires the existing same-origin `/admin` URL condition and a visible authorized **Case-linked demonstration PDFs** navigation link before setting `authenticationConfirmed` and starting post-login tracing.

The request filter still receives its original `login` policy phase; diagnostic subphases do not widen the policy. Unexpected internal requests, MFA/redirect differences or batching remain fail-closed uncertainties requiring review. No generic network-idle wait was reintroduced.

## Attempt-04 path audit

Fixed root: `storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-only/attempt-04/`.

Launcher reservation ID, provisioning leaf, bootstrap identity/session path and reviewer diagnostics now agree on attempt 04. Inspector output still derives from the bootstrap. Atomic collision refusal, canonical session containment/write probe, independent PHP session checks and pinned historical identity hash remain unchanged. The original foundation credential path remains at its creation attempt; it was not advanced.

**The real attempt-04 directory remains absent.** Attempts 01–03 were not reused or edited. No office or personnel creation path was added; only the existing 47/48 reviewer workflow remains prepared.

## Offline verification

| Check | Actual result |
| --- | --- |
| Diagnostic sanitizer tests | **PASS: 39 checks**, including credential-like strings, token/header/cookie/CSRF patterns, unknown names, query-bearing/external URLs, oversized messages/stacks, bounded frames and a throwing getter |
| Existing request-policy / selector tests | **PASS: 294 checks**, including retained internal-request replay and denied arbitrary dispatch, lazy load, creation/generation, wrong IDs/locks and property updates |
| JavaScript syntax | **PASS**, diagnostics module, driver and launcher |
| PHP syntax / scoped Pint `--test` | **PASS**, bootstrap |
| Path isolation | **PASS**, matching attempt-04 identifiers, launcher/bootstrap path-only delta, real attempt directory absent |
| Scoped whitespace | **PASS** for changed tools; unrelated pre-existing documentation whitespace not edited |
| Database / browser / authentication / lifecycle | **NOT RUN** |

Tests import only pure helper modules or inspect source. They do not import the executable reviewer driver/launcher, launch Playwright, bootstrap Laravel or connect to PostgreSQL. Synthetic test secrets are not real account values.

## Preservation and stop

A fresh focused baseline recorded **2,988 existing files**, including all attempt-03 evidence/session bytes, prior attempt records, historical identity logs, foundation credentials/ledger, runtime source and the current shared Laravel log. Private snapshots/results are under `storage/app/private/uiux-consolidated-acceptance/gate-b2-reviewer-js-readiness/`. No logs or compiled caches were cleared or restored.

Final comparison: **2,985/2,988 unchanged**, with exactly the three authorized existing verification files changed; zero missing baseline files. All **81 current PDF-named files**, including the original **73**, ten historical diagnostic JSON files, attempts 01–03, historical identity logs, credentials/ledger, protected runtime and Batch G artifacts remain byte-identical. The new diagnostics helper and this report are additions. The known missing temporary fake-upload metadata remains the documented prior exception, not reconstructed.

Actual JavaScript cause, credential acceptance, live framework requests, current configuration locks and HTTP diagnostic behavior remain unverified. Fresh database preflight and live reviewer execution require separate authorization. No request-policy expansion is proposed.

**Source preparation PASS; live acceptance BLOCKED. STOP.** No attempt-04 reservation/execution, review/publication, PDFs, B3/B4, runtime change, commit, push or deployment occurred.
