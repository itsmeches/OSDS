# SEC-006 — UTC session contract and timestamp impact assessment

**Date:** 19 October 2026. Targeted timestamp/authorization remediation; no historical repair or production/server change.

## Root cause and implementation

PHP/Laravel uses UTC. Previously, PostgreSQL sessions inherited the server default, observed as `Asia/Kuala_Lumpur` (UTC+08) in the isolated environment. Laravel's installed connection and Eloquent date serialization use offset-free `Y-m-d H:i:s` bindings. PostgreSQL interpreted these bindings in UTC+08 for `timestamptz` comparisons and inserts, producing an approximately **28,800-second** discrepancy from the intended PHP instant.

`config/database.php` now sets the PostgreSQL connection's supported **`timezone => UTC`** option. The installed `PostgresConnector::configureTimezone()` establishes this session setting on connection creation, including reconnection. This does not alter PostgreSQL server/database defaults, the host clock, column types, query inclusivity, permissions, or stored records. No vendor code or per-query offset workaround is used.

Unchanged grant semantics remain `starts_at <= now`, `ends_at > now` or null, and unrevoked. Region/module matching and separate Phase 3C/3D assignments remain unchanged.

### Exact targeted files

- `config/database.php`: the only production runtime-code change for SEC-006; supported connection option and explanation.
- `tests/Feature/TimestampCoherenceTest.php`: original clock regression, actual column types, non-UTC startup/reuse/reconnect and SELECT-only legacy assessment.
- `tests/Feature/ApplicantSandboxTest.php`: original real-expiry regression plus explicit-instant assignment/Livewire/regional/representation/eligibility boundary tests.
- `scripts/Prepare-ChedroJourney.php`: isolated explicit-offset one-minute-expired fixtures, database expiry confirmation and safe clock/timing metrics.
- `scripts/verify-chedro-journey.mjs`: near-boundary direct-route and timing assertions; stable-page toast dismissal before multi-viewport measurement.
- Issue register, Batch D report and manual guide: evidence, historical concerns, limitations and review boundary.

## Schema and serialization findings

Information-schema tests inspected actual `osds_test` columns before the configuration change. No schema change was made.

| Data | Existing storage | Consequence |
| --- | --- | --- |
| Regional grants and SCHOOL representation effective dates | `timestamptz` | Represents instants; offset-free query bindings require the UTC session contract |
| Eligibility and both reviewer assignment types | `timestamptz` | Same contract applies to inserts and effectiveness comparisons |
| Publication effectivity, retirement and configuration history | `timestamptz` | Correct instant interpretation; exact-version and retirement rules remain unchanged |
| Draft saves, submissions, evidence revisions, findings, corrections and lifecycle history | `timestamptz` | New PHP-supplied timestamps now agree with database-clock timestamps |
| Audit event creation | `timestamptz`, database default | `AuditRecorder` omits `created_at`; PostgreSQL default records the real instant |
| Older users/identity bookkeeping `created_at`/`updated_at`, verification and failed-job metadata where defined with `timestamp()` | `timestamp without time zone` | Stores a wall-clock literal; changing session time zone does not rewrite or add an offset to that literal |
| Queue scheduling/session activity integer columns | Epoch integers | No timestamp-zone interpretation; unchanged |
| Questionnaire calendar-date answers | Typed answer values | Not SQL instant columns; unchanged |

PostgreSQL stores a `timestamptz` instant independently of its displayed session offset. The driver returns zone-bearing values for those columns; Laravel's immutable-datetime casts retain the represented instant. A `timestamp without time zone` value has no intrinsic instant: the application's existing UTC interpretation remains in place. Precision and existing second-resolution PHP binding format are unchanged.

## Date-sensitive path audit

| Path | Current clock use | Assessment |
| --- | --- | --- |
| `ReviewerQueueScope`, both reviewer services | PHP-bound `now()` in effective-assignment SQL | SEC-006 originally confirmed here; real-clock and boundary regressions now cover both queues and direct/Livewire access |
| `ScopeAuthorizer`, `StaffListScope`, scoped staff selectors and `UserPolicy` | PHP-bound Carbon instant for regional/nationwide grants and representation | Same binding exposure confirmed by source; expired regional grant and staff-list tests verify correction; prior SEC-004 tests retained |
| Applicant tracking/draft policies and portal lists | PHP-bound instant for representation/eligibility SQL | Same exposure; real explicit-offset representation/eligibility expiry and draft/tracking denial tests added |
| Draft/evidence services | PHP-bound representation/eligibility checks; loaded publication dates compared in PHP | Central connection correction covers SQL bindings and subsequent cast instants; ownership/eligibility policy unchanged |
| Portal publication choices | PHP-bound `effective_from`/`effective_until` SQL | Same binding exposure; corrected by connection contract |
| Publication service/draft editability | PHP Carbon comparisons of loaded manifest instants, plus retirement relationship | No new comparison policy; loaded `timestamptz` instants are interpreted correctly, but an already miswritten stored instant cannot be inferred or repaired automatically |
| Audit defaults and database timestamp defaults | Database clock | Real epoch does not change with session display zone; simulated legacy/current sessions verify this |
| Workflow/audit writes explicitly supplying PHP dates | PHP datetime bindings | New writes corrected; historical writes require separate assessment |
| Scheduled/queued authorization | No application-specific scheduled authorization command or queued authorization job found | `routes/console.php` contains the existing inspiration command; future workers using the configured PostgreSQL connection inherit its contract. No real background authorization job is claimed tested |

The common binding mismatch is confirmed; the audit does not assert that every historical record or every listed path actually caused an unauthorized disclosure. Exact permissions and scope restrictions still apply.

## Historical timestamp assessment — read-only, isolated

The assessment performs SELECT-only comparisons on isolated connections. It never rewrites application records.

- Under a simulated legacy UTC+08 session, interpreting an offset-free PHP UTC string as `timestamptz` shifts it **−28,800 seconds** relative to the same explicit-offset instant.
- Under the corrected UTC session, that discrepancy is zero within the unchanged second-resolution binding precision.
- A database-clock/default `timestamptz` has the same epoch in either session; only its displayed offset changes.
- A `timestamp without time zone` literal remains identical. Its correctness depends on the writer's intended wall-clock convention.

Therefore historical rows may contain a mixture of correctly stored database-default/explicit-offset instants and shifted PHP-supplied instants. In this code, audit-event and review-copy defaults differ from explicit submission, finding, evidence-revision and correction timestamps. A blanket eight-hour adjustment would corrupt correctly stored data and immutable lineage. Values alone cannot reliably identify the writer or intended instant.

**No claim of historical timestamp integrity is made.** `osds_sandbox`, `osds_dev`, production data and their settings were not inspected or modified. Whether any of those records need repair is undetermined. Any historical assessment/repair beyond these isolated simulations needs separate authorization, provenance analysis and an approved migration/rollback strategy that respects immutable records. The connection fix does not require such a rewrite.

## Connection lifecycle and deployment boundary

Tests use a PostgreSQL startup option specifying UTC+08, then verify Laravel establishes UTC. They check same-connection reuse and disconnect/reconnect. Separate PHP browser-fixture commands and HTTP requests use the same central configuration, independently of the server default.

No running production worker or server was changed. Any future approved deployment must refresh cached application configuration and restart long-lived workers/processes or reconnect their databases; an already-open PDO session cannot acquire a new startup setting retroactively. Custom external transaction/session pool behavior and out-of-application SQL clients are not certified by these tests.

## Verification

Both original failing regressions are preserved and pass. Expanded tests cover actual database-clock agreement, explicit-offset near-boundary fixtures, exact-end exclusion, future/current/open-ended/revoked assignments, filtered queue totals/pagination, direct cases, Livewire expiry, regional grants, SCHOOL tracking/drafts and eligibility. The CHEDRO browser fixtures now use explicit-offset assignments expired one minute ago and require PostgreSQL itself to confirm expiry.

### Completed gates

- Both original regressions: **2 tests / 4 assertions passed**.
- Expanded focused matrix including originals: **7 tests / 76 assertions passed**.
- Complete PostgreSQL suite: **98 tests / 1,190 assertions passed**.
- CHEDRO browser journey: **12 interactions / 30 stable screen checks passed**, including one-minute-expired explicit-offset assignments, direct URL denial and SCHOOL correction/resubmission integration.
- Final browser measurement: session UTC; **0.078041-second** binding/clock skew; finding **97 seconds after** the test copy. The binding retains second precision; subsecond equality is not claimed.
- Original C-INT-001 browser reproduction and **11 complete expanded browser cases** passed with zero failures.
- Laravel Pint, Vite production build and Git diff check passed; existing line-ending advisories remain.

One first browser rerun caught a toast's auto-dismiss fade crossing the contrast threshold. That attempt is retained; final stable-page measurement closes transient toasts using their normal controls. Inline errors remain checked. This does not certify contrast throughout timed animations or full WCAG conformance.

No historical record correction, production/server configuration change, destructive migration or manual-testing data modification was performed. **Batch E and Phase 4 remain unstarted. Pause for Batch D review.**
