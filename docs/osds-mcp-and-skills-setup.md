# OSDS MCPs and skills

Installed/configured 28 October 2026. This setup does not authorize browser acceptance, database work or another Gate B2 attempt.

## MCP status

| Server | Status | Configuration |
| --- | --- | --- |
| Laravel Boost | Previously installed and verified | `application-info` / `search-docs` only; database connections and code execution disabled by its launcher |
| Chrome DevTools | **Installed: 1.12.0; registered, disabled** | Isolated headless profile, usage statistics and CrUX transmission disabled; initial tool allowlist limited to page list/snapshot |
| Playwright | **Installed: @playwright/mcp 0.0.83; registered, disabled** | Isolated headless Chrome, loopback origin allowlist, initial tool allowlist limited to tabs/snapshot |
| GitHub | **Registered, authentication pending** | Official remote `/mcp/readonly` endpoint; repository, issue, PR and Actions toolsets; no GitHub write capability configured |
| Context7 | **Registered; public lookup verified** | Official remote documentation endpoint; `resolve-library-id` / `query-docs` allowlist |

Codex's configuration listing confirms all five registrations. Context7 resolved the public Livewire documentation library successfully without a saved login. This does not guarantee unlimited anonymous access. GitHub needs your account sign-in, for example `codex mcp login github`; no token is embedded in project configuration and no authentication browser was opened during setup.

Browser packages are pinned in ignored `storage/app/private/osds-mcp-tools/package.json` and its lockfile, installed with scripts disabled. Project application `package.json`/lockfile were not changed. The configured launch commands use the portable Node binary and installed package entry points, avoiding an unpinned `npx @latest` download at startup.

Both browser servers remain disabled because installation is separate from the currently closed browser-execution gate. Their `--help` commands were checked; no browser was launched. Raw console/network tools are not in the initial allowlists. Before any future use, review the particular capture/action scope, sanitization and existing one-shot driver safeguards. MCP browser controls are not a substitute for the reviewed acceptance runner, and origin restrictions alone are not a complete security boundary.

All server definitions are project-local in ignored `.codex/config.toml`. The prior Boost definition is preserved. This file contains machine-specific absolute paths; reinstall/reconfigure if the workspace or portable tools move. Reload Codex if a newly installed server or skill does not appear.

## Six project skills

These are installed in the discoverable project directory `.agents/skills/`, with normal automatic selection and no mandatory dependency on a new MCP:

- [osds-verification-gates](../.agents/skills/osds-verification-gates/SKILL.md): operation/database boundaries, isolated attempts and truthful acceptance results.
- [osds-livewire-diagnostics](../.agents/skills/osds-livewire-diagnostics/SKILL.md): exact component targeting, safe diagnostics, initialization distinctions and one-shot actions.
- [osds-authorization-review](../.agents/skills/osds-authorization-review/SKILL.md): permission/scope checks, revocation, immutable bindings and stage separation.
- [osds-evidence-preservation](../.agents/skills/osds-evidence-preservation/SKILL.md): baselines, historical evidence, private artifacts and preservation exceptions.
- [osds-accessibility-acceptance](../.agents/skills/osds-accessibility-acceptance/SKILL.md): responsive/axe/keyboard checks and separate human acceptance.
- [osds-document-integrity](../.agents/skills/osds-document-integrity/SKILL.md): request/PDF separation, explicit generation, private downloads and immutable-original recovery boundaries.

They may also be invoked explicitly, such as `$osds-livewire-diagnostics`. Skills provide workflow instructions, not new authorization. Current user instructions take precedence; prior reports do not grant standing execution permission. No AGENTS.md or existing skill was overwritten.

## Validation and preservation

- Both MCP packages installed successfully in the isolated tools directory; their CLI help entry points ran without browser startup.
- Codex recognized the new definitions, with Chrome/Playwright disabled and GitHub/Context7 enabled.
- All six installed skill frontmatter/name/description checks passed; all six repository reference links and whitespace checks passed.
- Context7's public documentation-resolution tool responded successfully. No GitHub repository/account data was queried.
- The 3,009-file protected baseline remains unchanged, including historical gate evidence, original PDFs, runtime source and Batch G. New skills/configuration/tool packages are setup additions, not a claim of whole-vendor-tree preservation.
- Private previews, previous configuration, package help output and validation scripts are under `storage/app/private/osds-mcp-setup/`.

No PostgreSQL access, Laravel application bootstrap, browser startup, login, personnel review/publication, PDF, test suite, commit, push or deployment occurred in this setup. B2's ReferenceError remains unresolved; attempt 05 and B3/B4 remain unauthorized. The earlier Boost Composer advisory remains separately recorded in [Boost setup notes](laravel-boost-setup.md).
