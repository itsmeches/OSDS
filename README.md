# CHED OSDS Management Platform

Phase 3A foundation for a modular OSDS web platform. It provides the Phase 2 identity and scoped authorization controls plus configurable, versioned Registration/ACSA questionnaires, documentary requirements, controlled review, immutable synthetic publication manifests, and a staff-only preview. Applicant submissions, evidence references, COR/ACSA validation, registry, certificates, issuance, and RA 11180 are not implemented. See the [requirements](docs/requirements.md), [technical specification](OSDS_TECHNICAL_SPECIFICATION.md), and [open decisions](docs/assumptions-and-open-questions.md) before adding business rules.

## Stack

Laravel 13, PHP 8.3 or newer, Filament 5, Livewire 4, Blade, Tailwind CSS 4, PostgreSQL 17, Spatie Laravel Permission 7, PHPUnit 12, Vite 8, and Node.js 24. Spatie is installed for later access control work; no roles or permissions are seeded. Redis is not required in Phase 1.

## Prerequisites

- PHP 8.3+ with `curl`, `fileinfo`, `intl`, `mbstring`, `openssl`, `pdo_pgsql`, and `zip` enabled
- Composer 2, Node.js 24 LTS with npm, PostgreSQL 17 server and client, and Git
- A locally created PostgreSQL development database and a separate test database. Use a local role with access to those databases. Do not use production data.

## Fresh setup

1. Clone the repository and enter its root. Run `composer install` and `npm ci`.
2. Create separate local PostgreSQL databases named `osds_dev` and `osds_test` (or choose your own names). Grant your local development role access to each. Keep the server listening on localhost only.
3. Copy `.env.example` to `.env`. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` for `osds_dev`. Run `php artisan key:generate`.
4. Copy `.env` to `.env.testing`. Set `APP_ENV=testing`, `APP_DEBUG=false`, and `DB_DATABASE=osds_test`; use the test database credentials. Run `php artisan key:generate --env=testing`. Both files are ignored by Git.
5. Run `php artisan migrate --force` and `php artisan migrate --env=testing --force`. Core OSDS and questionnaire-configuration tables are created; no real accounts, submissions, evidence, or official records are created.
6. Run `npm run build`, then `php artisan test`. Run `vendor/bin/pint --test` to check formatting.
7. Start the app with `php artisan serve` and, for CSS development, `npm run dev` in another terminal. By default the landing page is at `http://127.0.0.1:8000/`, health at `/up`, and the Filament panel at `/admin`.

### Current Windows portable environment

The Phase 1 tools on this workstation are portable and were not added to the system `PATH`. In a new PowerShell window, run the following from the repository root before using `php`, `composer`, `node`, or `npm`:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass -Force
. .\scripts\Enter-DevShell.ps1
.\scripts\Start-LocalPostgres.ps1
```

The policy change applies only to the current PowerShell window. The leading dot and space on the second command are required: they load the tool paths into that session. The third command starts the local PostgreSQL server when needed. You can then run `php artisan test`, `npm run build`, or `php artisan serve`. These helpers depend on tools under the Windows temporary directory; install the prerequisites normally if that directory is cleared.

The panel login route is available to active users with the explicit `platform.admin.access` permission. SCHOOL fixtures do not receive it. Resource policies also require active region or institution scope; a role name alone never grants nationwide access.

### Synthetic development fixtures

Fixtures use fictional names and are never loaded automatically. Set a temporary `OSDS_FIXTURE_PASSWORD` of at least 12 characters, then run `php artisan db:seed --class=DevelopmentFixtureSeeder`. This creates five local `@example.invalid` accounts, two synthetic regions, three fictional institutions, and example scope grants. The seeder refuses to run outside `local` or `testing`. Do not use this command or its password for production provisioning.

## Development structure

`app/` contains models, deny-by-default policies, scope/audit services, middleware, observers, and Filament resources. `resources/views/` and `resources/css/` hold Blade and Tailwind assets. `database/migrations/` contains framework tables plus Phase 2 identity, scope, permission, and audit tables. `tests/Feature/` covers the platform foundation and scoped authorization. Future modules should keep their domain rules behind these shared boundaries.

Local `.env*` files, `vendor/`, `node_modules/`, generated frontend assets, and runtime storage are excluded from Git. Local file storage is private by default; no public document disk is configured for case evidence.

## Common commands

| Purpose | Command |
| --- | --- |
| Migration status | `php artisan migrate:status` |
| Apply baseline migrations | `php artisan migrate --force` |
| Test database migration | `php artisan migrate --env=testing --force` |
| Run tests | `php artisan test` |
| Check PHP formatting | `vendor/bin/pint --test` |
| Format PHP | `vendor/bin/pint` |
| Build frontend | `npm run build` |
| Start development server | `php artisan serve` |

## Troubleshooting

- `could not find driver`: enable `pdo_pgsql` in the PHP configuration used by your terminal.
- PostgreSQL connection refused: confirm server, port, database names, credentials, and local firewall. Check `php artisan migrate:status` after editing `.env`.
- Vite manifest missing: run `npm ci && npm run build`, or start `npm run dev` while developing.
- Panel returns 403 after login: confirm the account is active and has `platform.admin.access`. Resource pages additionally require an active scope and their explicit permission.
- Do not commit `.env` or `.env.testing`. If a credential enters Git history, rotate it and follow the project security process.

See the [Phase 1 report](docs/phase-1-completion-report.md) and [Phase 2 report](docs/phase-2-completion-report.md) for verified local results and limits.
