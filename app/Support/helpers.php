<?php

use App\Core\Auth;
use App\Core\Csrf;

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);
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
    return $_ENV[$key] ?? getenv($key) ?: $default;
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
