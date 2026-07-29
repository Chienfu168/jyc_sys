$ErrorActionPreference = 'Stop'

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$envExample = Join-Path $Root '.env.example'

if (-not (Test-Path $envExample)) {
    throw '.env.example was not found.'
}

$versionLine = Select-String -Path $envExample -Pattern '^APP_VERSION=' | Select-Object -First 1
if (-not $versionLine) {
    throw 'APP_VERSION is missing from .env.example.'
}

$version = ($versionLine.Line -replace '^APP_VERSION=', '').Trim().Trim('"')
if ($version -notmatch '^\d+\.\d+\.\d+$') {
    throw "APP_VERSION must use x.y.z format. Current value: $version"
}

$status = git status --porcelain
$branch = git branch --show-current

Write-Host "Branch: $branch"
Write-Host "APP_VERSION: v$version"

if ($status) {
    Write-Host ''
    Write-Host 'Working tree has changes:' -ForegroundColor Yellow
    $status | ForEach-Object { Write-Host $_ }
    Write-Host ''
    Write-Host 'Commit these changes before pushing a release.' -ForegroundColor Yellow
    exit 1
}

Write-Host 'Working tree is clean. Ready to push or create release.' -ForegroundColor Green
