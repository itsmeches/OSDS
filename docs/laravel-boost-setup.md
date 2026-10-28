# Laravel Boost MCP setup

Installed 28 October 2026: **Laravel Boost v2.10.3**, development dependency (`^2.10`). Composer added `laravel/mcp v1.1.2`, `laravel/roster v1.0.0`, `composer/semver 3.5.2` and `symfony/yaml v8.1.9`. No existing locked package was updated or removed.

Installation used `composer require laravel/boost --dev --no-scripts --no-plugins --no-interaction --minimal-changes`. Application Composer scripts, Filament upgrades, migrations and `boost:install` were not run. No generated agent guidelines replaced existing project instructions.

## Connection

Project-local `.codex/config.toml` registers `laravel-boost` using the portable PHP binary and [scripts/BoostMcp.php](../scripts/BoostMcp.php). Codex `mcp get laravel-boost --json` confirmed the registration is enabled. The ignored configuration contains machine-specific absolute paths; it must be recreated if this workspace or portable PHP location moves.

Reload the Codex session/extension to load the new MCP registration. The current chat's already-loaded tools may not refresh automatically.

Enabled tools:

- `application-info`: PHP/framework/package metadata; database engine is configuration metadata, not evidence of an opened connection.
- `search-docs`: version-aware Laravel ecosystem documentation search.

The wrapper excludes all other Boost tools and routes its tool subprocesses back through itself. Database query/schema access, Tinker, raw browser/application logs and rule-writing tools are unavailable. Every PostgreSQL PDO attempt is rejected; other database drivers are rejected too. No test or development database was connected to during installation/verification. The browser-test name in the isolated configuration is not connection authorization.

[config/boost.php](../config/boost.php) defaults Boost off outside the MCP launcher and disables its browser-log watcher/rule writes. Existing browser journeys therefore do not acquire Boost's browser logging scripts or route by default. The wrapper uses array cache/sessions/mail and stderr logging, refuses cached application configuration and does not edit `.env` or `.env.testing`.

## Verification and limits

- Composer installation completed successfully; lock comparison confirms five additions only.
- PHP syntax and scoped Pint checks passed.
- Actual MCP initialize, tools/list and application-info invocation passed; exactly `application-info` and `search-docs` were exposed, including the guarded tool subprocess.
- No-PDO guard checks passed for the configured informational connection and rejected `osds_dev` / `osds_test` before any connection could open.
- Documentation search is configured but its remote API was not exercised by the smoke test.
- A comparison of 3,009 previously baselined protected source/evidence/artifact files found no changes, including 81 PDF-named files (73 originals), historical diagnostic JSON, prior gate evidence and Batch G. Composer manifests, vendor additions/autoload metadata and the new setup files are intentional installation changes outside that protected comparison.

Composer reported **one security vulnerability advisory affecting one package**. A separate read-only advisory lookup timed out; the affected package/advisory was not established. Do not assume it concerns Boost or a pre-existing dependency. No unrelated upgrade or advisory suppression was performed. Private installation snapshots and smoke-test records are under `storage/app/private/laravel-boost-install/`.

Gate B2 remains blocked at the previously documented login ReferenceError. No attempt 05, browser login, configuration lifecycle action, PDF, regression suite, B3/B4, commit, push or deployment occurred. Installing MCP does not authorize any of those operations.
