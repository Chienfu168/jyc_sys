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

    return $null
}

function Get-CommandPath($Name) {
    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($command) {
        return $command.Source
    }

    return $null
}

function Write-Check($Name, $Ok, $Detail, $Required = $false) {
    $status = if ($Ok) { 'OK' } elseif ($Required) { 'MISSING' } else { 'WARN' }
    $line = "{0,-8} {1,-22} {2}" -f $status, $Name, $Detail
    if ($Ok) {
        Write-Host $line -ForegroundColor Green
    } elseif ($Required) {
        Write-Host $line -ForegroundColor Red
    } else {
        Write-Host $line -ForegroundColor Yellow
    }
}

$missingRequired = 0

Write-Host "Foundation system environment check" -ForegroundColor Cyan
Write-Host "Root: $Root"
Write-Host ''

$git = Get-CommandPath 'git'
if ($git) {
    $gitVersion = (& $git --version) -join ' '
    Write-Check 'Git' $true "$gitVersion ($git)" $true
} else {
    Write-Check 'Git' $false 'Required for commit/push workflow.' $true
    $missingRequired++
}

$php = Get-LocalPhp
if ($php) {
    $phpVersion = (& $php -r 'echo PHP_VERSION;')
    Write-Check 'PHP CLI' $true "PHP $phpVersion ($php)" $true

    $modules = & $php -m
    foreach ($module in @('PDO', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'zip', 'json', 'session')) {
        $hasModule = $modules -contains $module
        Write-Check "ext-$module" $hasModule $(if ($hasModule) { 'Loaded' } else { 'Required PHP extension is not loaded.' }) $true
        if (-not $hasModule) {
            $missingRequired++
        }
    }
} else {
    Write-Check 'PHP CLI' $false 'Required. Install PHP 8.2+ or keep .tools/php-*/php.exe.' $true
    $missingRequired++
}

$composer = Get-CommandPath 'composer'
Write-Check 'Composer' ([bool] $composer) $(if ($composer) { $composer } else { 'Optional now; required if external PHP packages are added later.' })

$mysql = Get-CommandPath 'mysql'
Write-Check 'MySQL CLI' ([bool] $mysql) $(if ($mysql) { $mysql } else { 'Optional locally; useful for importing/exporting cPanel database dumps.' })

$gh = Get-CommandPath 'gh'
Write-Check 'GitHub CLI' ([bool] $gh) $(if ($gh) { $gh } else { 'Optional. GitHub Actions already creates releases after push.' })

$node = Get-CommandPath 'node'
Write-Check 'Node.js' ([bool] $node) $(if ($node) { $node } else { 'Not required for the current PHP/MySQL system.' })

foreach ($dir in @('storage/logs', 'storage/cache', 'storage/private_uploads', 'storage/updates', 'storage/backups')) {
    $path = Join-Path $Root $dir
    Write-Check $dir (Test-Path $path) $(if (Test-Path $path) { 'Exists' } else { 'Missing storage directory.' }) $true
    if (-not (Test-Path $path)) {
        $missingRequired++
    }
}

$envExample = Join-Path $Root '.env.example'
if (Test-Path $envExample) {
    $version = Select-String -Path $envExample -Pattern '^APP_VERSION=' | Select-Object -First 1
    Write-Check 'APP_VERSION' ([bool] $version) $(if ($version) { $version.Line } else { 'APP_VERSION is missing from .env.example.' }) $true
    if (-not $version) {
        $missingRequired++
    }
} else {
    Write-Check '.env.example' $false 'Missing sample environment file.' $true
    $missingRequired++
}

Write-Host ''
if ($missingRequired -gt 0) {
    Write-Host "Result: $missingRequired required item(s) need attention." -ForegroundColor Red
    exit 1
}

Write-Host 'Result: required environment is ready.' -ForegroundColor Green
