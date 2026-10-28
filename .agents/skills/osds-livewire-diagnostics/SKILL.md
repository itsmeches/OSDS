---
name: osds-livewire-diagnostics
description: Investigate OSDS Filament and Livewire browser failures with precise component targeting, safe diagnostics and one-shot action handling.
---

Apply [OSDS Verification Gates](../osds-verification-gates/SKILL.md) as the shared safety contract, using the current user authorization.


Read the relevant driver and latest failure evidence before proposing a correction. Use [the ReferenceError investigation](../../../docs/osds-ui-ux-gate-b2-referenceerror-investigation.md) for the historical login blocker; do not assume its candidates are confirmed causes or that another attempt is authorized.

- Identify exact `memo.name` and require one matching component. Never select the first Livewire snapshot by DOM order. Avoid exporting snapshot data or credential fields.
- Discover and validate the page-advertised same-origin Livewire endpoint; do not assume `/livewire/update`. Keep permitted component/action/argument combinations closed and stage-specific.
- A visible form is not initialized framework state; an HTTP 200 is not authentication. Check explicit controls, accepted policy calls, authenticated URL and authorized navigation separately. Presence booleans do not prove readiness.
- Record bounded diagnostic categories, monotonic operation ordering and sanitized public asset families when authorized. Never export raw errors, console dumps, request bodies, cookies, storage, session IDs or pre-login traces. Unknown identifiers remain restricted unless an exact public-source mapping was reviewed.
- Keep page-error and unexpected-request handling fail-fast. Do not permit arbitrary `__dispatch` or `__lazyLoad`, suppress errors, increase waits or retry mutations to manufacture a pass.
- Distinguish browser-context exceptions, driver-process failures and delayed framework rethrows. Line/column resemblance and synthetic reproductions are clues, not attribution.

Use installed source and offline policy/sanitizer tests during source-only tasks. Browser MCP connection or installation is not authorization to start a browser or authenticate. Existing committed office/personnel artifacts must be revalidated, never recreated as retry preparation.

## Evidence and completion

Required evidence: relevant driver/source hashes, retained failure metadata, policy and sanitizer tests. Complete with a source-versus-runtime attribution table, confirmed limitations and scoped PASS/FAIL/BLOCKED/NOT RUN results; never infer a live fix from an offline reproduction.
