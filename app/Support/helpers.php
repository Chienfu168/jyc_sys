<?php

use App\Core\Auth;
use App\Core\Csrf;

function base_path(string $path = ''): string
{
    $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

function app_path(string $path = ''): string
{
    return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }

    return $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key, $default);
    if (is_bool($value)) {
        return $value;
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
}

function config(string $key, mixed $default = null): mixed
{
    static $config = [];

    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($config[$file])) {
        $path = base_path('config' . DIRECTORY_SEPARATOR . $file . '.php');
        $config[$file] = file_exists($path) ? require $path : [];
    }

    return $item === null ? $config[$file] : ($config[$file][$item] ?? $default);
}

function asset_url(string $path): string
{
    $assetPath = '/' . ltrim($path, '/');
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    if ($scriptDir === '/public' || str_ends_with($scriptDir, '/public')) {
        $scriptDir = substr($scriptDir, 0, -7);
    }

    if ($scriptDir === '' || $scriptDir === '.' || $scriptDir === '/') {
        return $assetPath;
    }

    return $scriptDir . $assetPath;
}

function asset_version(): string
{
    return preg_replace('/^v/i', '', (string) config('app.version', '0'));
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $viewPath = base_path('resources/views/' . str_replace('.', '/', $template) . '.php');

    if (!file_exists($viewPath)) {
        throw new RuntimeException("View not found: {$template}");
    }

    require $viewPath;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function auth(): Auth
{
    return Auth::instance();
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function roc_year(int|string|null $year = null): int
{
    $year = (int) ($year ?: date('Y'));
    return $year > 1911 ? $year - 1911 : $year;
}

function gregorian_year_from_roc(int|string|null $year = null): int
{
    $year = (int) ($year ?: date('Y'));
    return $year < 1912 ? $year + 1911 : $year;
}

function normalize_fiscal_year(int|string|null $year = null): int
{
    return gregorian_year_from_roc($year);
}

function roc_year_label(int|string|null $year = null): string
{
    return '民國 ' . roc_year($year) . ' 年';
}

function roc_date(?string $date): string
{
    if (!$date || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
        return '-';
    }

    return sprintf('民國 %d 年 %d 月 %d 日', ((int) $matches[1]) - 1911, (int) $matches[2], (int) $matches[3]);
}

function roc_date_range(?string $start, ?string $end): string
{
    return roc_date($start) . ' ~ ' . roc_date($end);
}

function foundation_profile(): array
{
    static $profile = null;

    if ($profile !== null) {
        return $profile;
    }

    $fallback = [
        'foundation_name' => config('app.name'),
        'english_name' => '',
        'tax_id' => '',
        'registration_no' => '',
        'competent_authority' => '',
        'approval_date' => '',
        'approval_doc_no' => '',
        'representative' => '',
        'executive_director' => '',
        'undertaker' => '',
        'phone' => '',
        'email' => '',
        'website' => '',
        'address' => '',
        'mission' => '',
        'service_area' => '',
        'fiscal_year_start_month' => 1,
    ];

    try {
        $stmt = \App\Core\Database::pdo()->query('SELECT * FROM foundation_profiles WHERE id = 1 LIMIT 1');
        $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: $fallback;
    } catch (Throwable) {
        $profile = $fallback;
    }

    return $profile + $fallback;
}

function foundation_name(): string
{
    return foundation_profile()['foundation_name'] ?: config('app.name');
}
