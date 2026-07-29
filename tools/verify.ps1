$ErrorActionPreference = 'Stop'

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path

function Get-LocalPhp {
    $local = Get-ChildItem -Path (Join-Path $Root '.tools') -Recurse -Filter php.exe -ErrorAction SilentlyContinue |
        Sort-Object FullName -Descending |
        Select-Object -First 1

    if ($local) {
        return $local.FullName
    }

    $command = Get-Command php -ErrorAction SilentlyContinue
    if ($command) {
        return $command.Source
    }

    throw 'PHP CLI was not found. Run tools/env-check.ps1 for details.'
}

$php = Get-LocalPhp
$phpFiles = Get-ChildItem -Path (Join-Path $Root 'app'), (Join-Path $Root 'public'), (Join-Path $Root 'resources') -Recurse -Filter *.php
$failed = @()

Write-Host "Using PHP: $php" -ForegroundColor Cyan

foreach ($file in $phpFiles) {
    $output = & $php -l $file.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        $failed += [pscustomobject]@{
            File = $file.FullName
            Output = ($output -join "`n")
        }
    }
}

if ($failed.Count -gt 0) {
    Write-Host 'PHP lint failed:' -ForegroundColor Red
    $failed | ForEach-Object {
        Write-Host $_.File -ForegroundColor Red
        Write-Host $_.Output
    }
    exit 1
}

Write-Host "PHP lint OK ($($phpFiles.Count) files)" -ForegroundColor Green

git diff --check
if ($LASTEXITCODE -ne 0) {
    throw 'git diff --check failed.'
}

Write-Host 'Git whitespace check OK' -ForegroundColor Green
