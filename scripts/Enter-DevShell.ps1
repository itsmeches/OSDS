$ErrorActionPreference = 'Stop'

$toolRoot = Join-Path $env:TEMP 'osds-phase1-tools'
$phpDirectory = Join-Path $toolRoot 'php'
$nodeDirectory = Join-Path $toolRoot 'node\node-v24.20.0-win-x64'
$postgresDirectory = Join-Path $toolRoot 'postgresql\pgsql\bin'
$composerPhar = Join-Path $toolRoot 'composer.phar'

$requiredTools = @(
    (Join-Path $phpDirectory 'php.exe'),
    (Join-Path $nodeDirectory 'node.exe'),
    (Join-Path $nodeDirectory 'npm.cmd'),
    $composerPhar
)

$missingTools = $requiredTools | Where-Object { -not (Test-Path -LiteralPath $_) }

if ($missingTools.Count -gt 0) {
    throw "Portable development tools are missing. Follow the README prerequisites and setup instructions. Missing: $($missingTools -join ', ')"
}

$pathEntries = @($phpDirectory, $nodeDirectory)

if (Test-Path -LiteralPath $postgresDirectory) {
    $pathEntries += $postgresDirectory
}

$env:Path = (($pathEntries + $env:Path.Split([IO.Path]::PathSeparator)) | Select-Object -Unique) -join [IO.Path]::PathSeparator
$env:OSDS_COMPOSER_PHAR = $composerPhar

function global:composer {
    & php $env:OSDS_COMPOSER_PHAR @args
}

Write-Host 'OSDS development shell is ready.' -ForegroundColor Green
Write-Host "PHP:        $(php --version | Select-Object -First 1)"
Write-Host "Node.js:    $(node --version)"
Write-Host "npm:        $(npm --version)"
Write-Host "PostgreSQL: $(pg_isready -h 127.0.0.1 -p 55432 2>$null)"
