$ErrorActionPreference = 'Stop'

$toolRoot = Join-Path $env:TEMP 'osds-phase1-tools'
$postgresBin = Join-Path $toolRoot 'postgresql\pgsql\bin'
$dataDirectory = Join-Path $toolRoot 'pgdata'
$logFile = Join-Path $toolRoot 'postgresql.log'
$pgIsReady = Join-Path $postgresBin 'pg_isready.exe'
$pgCtl = Join-Path $postgresBin 'pg_ctl.exe'

foreach ($requiredPath in @($pgIsReady, $pgCtl, $dataDirectory)) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "Portable PostgreSQL component is missing: $requiredPath"
    }
}

& $pgIsReady -h 127.0.0.1 -p 55432 *> $null

if ($LASTEXITCODE -eq 0) {
    Write-Host 'PostgreSQL is already accepting connections on 127.0.0.1:55432.' -ForegroundColor Green
    return
}

& $pgCtl start -D $dataDirectory -l $logFile -o '-p 55432 -h 127.0.0.1' -w

if ($LASTEXITCODE -ne 0) {
    throw "PostgreSQL did not start. Review $logFile"
}

Write-Host 'PostgreSQL started on 127.0.0.1:55432.' -ForegroundColor Green
