---
name: osds-evidence-preservation
description: Preserve OSDS verification attempts, historical reports, diagnostic JSON and PDFs with explicit baselines and truthful generated-artifact inventories.
---

Apply [OSDS Verification Gates](../osds-verification-gates/SKILL.md) as the shared safety contract, using the current user authorization.


Use for evidence-producing verification or preservation reviews. Read the latest relevant report and [consolidated acceptance plan](../../../docs/osds-ui-ux-consolidated-acceptance-plan.md). Distinguish current discovered counts from historical baselines.

- Record HEAD, dirty state, relevant source/tool hashes and a clearly scoped pre-operation baseline. Hashes are not backups; privately back up disposable fake-disk artifacts when their retention is required.
- Keep attempts append-only and separately identified. Refuse overwriting existing evidence paths. Never rewrite a historical FAIL after remediation or truncate an unexpected append to conceal it.
- Keep credentials, cookies, session files, evidence locators, raw traces and private PDF keys out of public reports and Git. Export only necessary IDs, safe findings and redacted diagnostics.
- Inventory generated Laravel logs, compiled views, fake-upload files and new PDF-named artifacts separately from runtime source. A filename extension is not evidence of a valid PDF.
- Compare protected source, historical JSON, original PDF bytes and prior attempt files after work. Report missing/changed files and authorized deltas explicitly. Never invent lost metadata or silently restore history.
- For separately authorized historical-output writers, archive new results then restore original bytes only under the agreed checksum/restoration plan; restoration does not erase a failing execution.

Do not turn an incomplete hash inventory into a whole-tree preservation claim. Preserve known exceptions, including prior temporary fake-upload metadata loss, without reconstructing them. Respect the user's actual authorized cleanup scope.

## Evidence and completion

Required evidence: pre-operation scoped hashes, previous attempt identity, intended deltas and final comparison. Complete with unchanged/changed/missing counts, generated-artifact inventory and every exception; never rewrite previous acceptance outcomes.
