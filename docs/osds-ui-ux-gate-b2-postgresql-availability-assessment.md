# Gate B2 — Isolated PostgreSQL availability assessment

**29 October 2026. Assessment complete. Original infrastructure identified; endpoint currently reachable. Protected preparation remains BLOCKED pending a separately authorized fresh guarded inspection.** No service was started, stopped, restarted or replaced. No database authentication, SQL, Laravel bootstrap or Attempt 11 execution occurred.

## Observed endpoint and process state

Applied OSDS Verification Gates and Evidence Preservation. Read-only host-level checks found:

| Check | Result |
| --- | --- |
| TCP listener | `127.0.0.1:55432`, owned by **PID 10380** |
| Bounded TCP connection, no PostgreSQL protocol/authentication | **PASS** |
| `pg_ctl status -D <existing pgdata>` | **Server running, PID 10380** |
| Main process | Existing portable `postgres.exe`, arguments `-D <existing pgdata> -p 55432 -h 127.0.0.1` |
| Process start | **29 October 2026 00:32:09 UTC / 08:32:09 Asia/Manila** |
| Failed Attempt 11 preparation reservation | **00:28:32 UTC / 08:28:32 Asia/Manila**, before current process start |
| Relevant Windows services | No PostgreSQL or Docker service returned by host service inventory |
| Docker | CLI not found; no matching Docker Desktop/backend process observed; no project Compose configuration identified |

The initial sandboxed checks reported no listener and `pg_ctl: no server running`, despite visible PostgreSQL process names. Read-only checks outside that sandbox established the listener, exact process command and successful TCP connection. Sandbox visibility/reachability is therefore not reliable evidence of current host service absence. Do not start another server in response to that sandbox result.

The current process began after the failed preparation. Its start was not performed by this assessment; who started it and why the earlier endpoint was unavailable are unknown. The earlier connection refusal does not establish a persistent storage or Laravel configuration defect. Current reachability does not retrospectively prove reachability at that failure boundary.

## Expected infrastructure and provisioning trace

[Start-LocalPostgres.ps1](../scripts/Start-LocalPostgres.ps1) identifies the original **portable PostgreSQL cluster**, rather than a Docker container:

- Executables: `%TEMP%\osds-phase1-tools\postgresql\pgsql\bin\`.
- Existing data directory: `%TEMP%\osds-phase1-tools\pgdata`.
- Startup log destination: `%TEMP%\osds-phase1-tools\postgresql.log`.
- Native loopback binding: `127.0.0.1:55432`; no container port mapping is involved.

The host process command and retained `postmaster.opts` agree with those paths and binding. `PG_VERSION` is **17**. The existing directory, `base`, `global/pg_control` (8,192 bytes), and `pg_wal` are present. This supports identification of an existing initialized cluster; it is not an integrity audit or proof that a particular database's current records are intact. No database files were opened to inspect unrelated database contents. No volume was created, deleted, mounted or replaced.

The [README portable-environment procedure](../README.md) documents loading the development shell and invoking `Start-LocalPostgres.ps1`. The script checks existing portable components, checks readiness and, only if needed, uses `pg_ctl start` with the existing data directory. It contains no initialization, database creation, migration or seeding operation.

Historical [Batch B documentation](phase-3d7-batch-b-report.md) names `scripts/Prepare-UiBrowser.php` as browser fixture preparation. Static inspection of that script shows a conditional `CREATE DATABASE osds_ui_browser_test`, followed by migrations and seeding. This identifies the historical provisioning mechanism; it is **not a recovery command** and must not be rerun. The precise original creation timestamp and current catalog-to-storage mapping were not queried. Later protected identity reports establish historical use of the named database, not fresh state verification today.

## Cause and minimum recovery procedure

**Confirmed current finding:** the expected original portable server is running and the host endpoint accepts TCP connections. **Historical cause: unresolved.** The current start time follows the failed preparation, so availability changed between those observations. No evidence establishes a damaged cluster, wrong port mapping, missing volume or need for replacement.

**Minimum action now: no service recovery command.** Do not restart the live server or rerun provisioning.

If a future host-level assessment confirms that this same cluster is stopped, its documented non-destructive startup command, requiring separate authorization, is:

```powershell
.\scripts\Start-LocalPostgres.ps1
```

Its underlying existing-cluster startup is:

```powershell
& "$env:TEMP\osds-phase1-tools\postgresql\pgsql\bin\pg_ctl.exe" start -D "$env:TEMP\osds-phase1-tools\pgdata" -l "$env:TEMP\osds-phase1-tools\postgresql.log" -o '-p 55432 -h 127.0.0.1' -w
```

**Neither command was executed.** Do not use either while the current process is running. Startup affects the cluster, not just one database, and requires its own approval. Missing storage, another owner of the port, or inconsistent process/data references must block startup rather than trigger initialization, PID-file deletion, replacement or credential changes.

## Preservation and limits

Fresh focused baseline/comparison: **7,946/7,946 unchanged**, zero changed or unavailable. It includes the previous protected baseline, Attempt 11 package/preparation records, attempts 01–10 and historical private evidence/session bytes, credentials/ledger, identity records, application/framework sources, logs/caches/artifacts, and hashes of existing `PG_VERSION` and `postmaster.opts`. Credentials/session bytes were hashed only. Original PDF/diagnostic inventories and prior documented exceptions remain preserved.

Cluster storage was left untouched by this assessment. Because PostgreSQL is already running, its normal background activity may change WAL/control/data files; no whole-volume byte-immutability or database-record preservation claim is made. Only storage references/metadata were inspected. No database login or query was used, including against unrelated databases. Historical logs were not cleared or modified; raw server log contents were not exported.

New files are this report and private baseline/comparison/assessment records under `gate-b2-postgresql-availability-assessment/`. No Attempt 11 file or previous report was changed. Its execution directory remains absent. No guarded inspection was rerun.

Remaining uncertainties are current database identity and protected content/permissions, the earlier service interruption cause, and whether a future execution environment can reach the host endpoint. TCP success alone cannot establish those database facts.

**One recommended next action:** separately authorize one fresh, non-overwriting guarded read-only preparation inspection against `osds_ui_browser_test` from an execution context with verified host reachability. Preserve the failed inspection reservation; do not reuse it. No replacement inspector, recovery or protected workflow was prepared here.

**STOP.** No recovery, Attempt 11 execution, browser/authentication, database provisioning/migration/seed/repair, lifecycle action, PDF, B3/B4, commit, push or deployment occurred.
