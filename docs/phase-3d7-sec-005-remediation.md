# SEC-005 — CHEDRO Livewire disclosure remediation

**Date:** 19 October 2026. Targeted authorization covers current/historical intake helpers and related CHEDRO response surfaces. Batch D acceptance remains separate.

## Root cause and correction

Livewire v4.4.7 serializes public action return values into `effects.returns`. Intake `selected()` returned an Eloquent application with submitted answers loaded after checking only `applications.intake.view`. A Blade answer-permission conditional could not protect that response. The historical `submittedCopy()` helper had the same exposure pattern.

The two pages now use request-local `#[Computed]` presentation properties. Views access them as properties. The installed framework's `BaseComputed::call()` explicitly rejects direct action invocation; real HTTP forged-action tests verify rejection rather than assuming the attribute works. Query-builder helpers are protected. No computed value is persisted or shared between requests.

Intake `selected` loads authorized case metadata and history, without loading submitted answers. `submittedCopy` resolves a copy exclusively through the authorized application's submissions. It loads answers **only** with `applications.intake.answers.view`, and restricts that query to applicable answers. Exact published question labels/options drive the read-only renderer. Hidden/inapplicable submitted answers remain stored but are not loaded for this interface.

Evidence item/revision relationships load only with the respective workflow's document-opening permission. Phase 3C finding history loads only with its history permission; the view does not trigger lazy loads when permission is missing. Phase 3C never loads general applicant answers. General queue/case permission does not imply answer, link-opening, finding-history or mutation permission.

## Surface audit

| Surface | Treatment |
| --- | --- |
| Intake `selected`, `submittedCopy`, `applications` | Request-local computed presentation; direct calls unavailable |
| Evidence `selected`, `cases` | Same; separate Phase 3C authorization remains |
| Shared `queueOptions` | Computed; options derive from workflow-specific guarded queries |
| Both `queueQuery` methods | Protected, unavailable as Livewire actions |
| `beginScreening`, `requestCorrection`, `saveFinding` | Void actions; retain case/service authorization and lifecycle checks |
| Search/filter/pagination actions | Scalar state and void changes; guarded queries remain authoritative |
| Public component state | Selectors, filters, pagination, and reviewer-entered form input; no loaded answer/model aggregate stored in public properties |
| Initial snapshots | Checked for synthetic answer markers and answer relationships; computed aggregates are absent |
| Later responses | Checked after refresh, permission revocation and forged helper calls |

Framework/Filament inherited actions were assessed for interaction with this component state; no sensitive aggregate was added to public state or exposed through a new public return path. This is a targeted audit, not a certification of every framework action or unrelated page.

## Regression design

The original failing regression remains and now verifies that a real Livewire helper invocation throws the installed computed-method rejection. Separate real HTTP requests use a signed component snapshot and the Livewire request header, then inspect denied response content. Computed calls are rejected with the installed framework's HTTP 500 error; protected unknown-method requests are rejected with HTTP 419. Neither path returns application/answer aggregates. These are rejected forged requests, not normal user actions.

The expanded matrix creates two immutable submitted copies through existing screening/correction/resubmission services. It tests metadata-only access, no answer SELECT without explicit permission, current/historical disclosure exclusion, initial snapshots and later response effects, protected helper rejection, positive answer-authorized access, inapplicable answer exclusion, permission revocation, assignment revocation, Phase 3C separation, and unchanged submitted hashes/status-history count after read probes. Existing queue tests cover ineffective assignments, exact region/module pairs, wrong workflow, sandbox denial, filtering/counting/pagination and subsequent action reauthorization.

No raw response payload is published here. Tests use fictional answer markers in `osds_test`; browser work uses only `osds_ui_browser_test`. No manual-testing database change or data repair is required. No migration, permission definition, domain policy, encryption, immutable-copy guard, correction lineage or applicant ordering adapter was changed.

## Targeted verification

- Original reproduction: **1 test, 12 assertions passed**.
- Expanded current/history/HTTP-response matrix: **1 test, 87 assertions passed**.
- Complete PostgreSQL suite: **91 tests, 1,112 assertions passed**, including SEC-001–004, C-INT-001's server regression and queue pagination/filter/revocation coverage.
- Laravel Pint ran successfully; Vite production build and `git diff --check` passed (existing line-ending advisories only).

These results lift the SEC-005 stop under the user's targeted authorization. Batch D browser work resumes; this does not establish full CHEDRO browser, accessibility or human usability acceptance. No command targeted a manual-testing database.

### Subsequent Batch D stop

SEC-005 remains fixed. Later Batch D checks added two protected-evidence/history query assertions; the full suite then passed **91 tests / 1,114 assertions**. Final screenshot inspection independently discovered SEC-006, a PHP/PostgreSQL clock/binding mismatch that can defeat real assignment expiry. Its new regressions fail and Batch D is stopped again. See the issue register; the preceding SEC-005 acceptance does not certify time-bound authorization correctness.
