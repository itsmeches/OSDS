param(
    [int] $Port = 8001,
    [switch] $SetupOnly
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot

. (Join-Path $PSScriptRoot 'Enter-DevShell.ps1')
& (Join-Path $PSScriptRoot 'Start-LocalPostgres.ps1')

function Read-DotEnvValue([string] $Name) {
    $line = Get-Content -LiteralPath (Join-Path $projectRoot '.env') |
        Where-Object { $_ -match "^$([regex]::Escape($Name))=" } |
        Select-Object -First 1
    if (-not $line) { return $null }
    return ($line -split '=', 2)[1].Trim().Trim('"').Trim("'")
}

$databaseName = 'osds_sandbox'
$databaseHost = Read-DotEnvValue 'DB_HOST'
$databasePort = Read-DotEnvValue 'DB_PORT'
$databaseUser = Read-DotEnvValue 'DB_USERNAME'
$databasePassword = Read-DotEnvValue 'DB_PASSWORD'

if (-not $databaseHost) { $databaseHost = '127.0.0.1' }
if (-not $databasePort) { $databasePort = '55432' }
if (-not $databaseUser) { throw 'DB_USERNAME is missing from .env.' }

$previousPgPassword = $env:PGPASSWORD
$env:PGPASSWORD = $databasePassword
try {
    $exists = & psql -h $databaseHost -p $databasePort -U $databaseUser -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname='$databaseName'"
    if (($exists | Out-String).Trim() -ne '1') {
        & createdb -h $databaseHost -p $databasePort -U $databaseUser $databaseName
        if ($LASTEXITCODE -ne 0) { throw "Could not create $databaseName." }
        Write-Host "Created isolated database $databaseName." -ForegroundColor Green
    }
} finally {
    $env:PGPASSWORD = $previousPgPassword
}

$sandboxEnvPath = Join-Path $projectRoot '.env.sandbox'
$sandboxEnv = Get-Content -LiteralPath (Join-Path $projectRoot '.env') -Raw
$sandboxEnv = [regex]::Replace($sandboxEnv, '(?m)^APP_ENV=.*$', 'APP_ENV=sandbox')
$sandboxEnv = [regex]::Replace($sandboxEnv, '(?m)^DB_DATABASE=.*$', "DB_DATABASE=$databaseName")
[System.IO.File]::WriteAllText($sandboxEnvPath, $sandboxEnv, [System.Text.UTF8Encoding]::new($false))
if (-not $env:OSDS_FIXTURE_PASSWORD) {
    $fixturePasswordFile = Join-Path $env:TEMP 'osds-phase1-tools\fixture-password.txt'
    if (-not (Test-Path -LiteralPath $fixturePasswordFile)) {
        throw 'Set OSDS_FIXTURE_PASSWORD to at least 12 characters or restore the local fixture-password.txt file.'
    }
    $env:OSDS_FIXTURE_PASSWORD = (Get-Content -LiteralPath $fixturePasswordFile -Raw).Trim()
}

php artisan config:clear --env=sandbox
if ($LASTEXITCODE -ne 0) { throw 'Could not clear sandbox configuration.' }
php artisan migrate --env=sandbox --force
if ($LASTEXITCODE -ne 0) { throw 'Sandbox migration failed.' }
php artisan db:seed --env=sandbox --class=DevelopmentFixtureSeeder --force
if ($LASTEXITCODE -ne 0) { throw 'Sandbox fixture seeding failed.' }

if ($SetupOnly) {
    Write-Host 'Applicant sandbox database is ready.' -ForegroundColor Green
    return
}

Write-Host "Applicant sandbox: http://127.0.0.1:$Port/applicant/login" -ForegroundColor Cyan
Write-Host 'Press Ctrl+C to stop this sandbox server.' -ForegroundColor DarkGray
php -S "127.0.0.1:$Port" -t public scripts/applicant-sandbox-server.php
