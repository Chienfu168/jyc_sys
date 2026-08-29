<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Router;

$basePath = $_SERVER['APP_BASE_PATH'] ?? dirname(__DIR__);
define('BASE_PATH', realpath($basePath) ?: $basePath);

require BASE_PATH . '/app/Support/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

Env::load(base_path('.env'));
date_default_timezone_set(config('app.timezone', 'Asia/Taipei'));

$appDebug = (bool) config('app.debug', false);
ini_set('display_errors', $appDebug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting($appDebug ? E_ALL : (E_ALL & ~E_DEPRECATED & ~E_NOTICE));

set_exception_handler(function (\Throwable $e) use ($appDebug): void {
    error_log(sprintf(
        '[%s] %s: %s @ %s:%d',
        date('Y-m-d H:i:s'),
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($appDebug) {
        echo '<pre style="white-space:pre-wrap;padding:20px;font-family:monospace">';
        echo e($e->getMessage()) . "\n\n" . e($e->getTraceAsString());
        echo '</pre>';
        return;
    }

    try {
        view('errors.500', ['title' => '系統發生錯誤']);
    } catch (\Throwable) {
        echo '<!doctype html><meta charset="utf-8"><title>系統發生錯誤</title>'
            . '<h1>系統發生錯誤</h1><p>請稍後再試,或聯絡系統管理員。</p>';
    }
});

register_shutdown_function(function () use ($appDebug): void {
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }

    error_log(sprintf('[%s] FATAL: %s @ %s:%d', date('Y-m-d H:i:s'), $error['message'], $error['file'], $error['line']));

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($appDebug) {
        echo '<pre style="white-space:pre-wrap;padding:20px;font-family:monospace">'
            . e($error['message']) . ' @ ' . e($error['file']) . ':' . $error['line'] . '</pre>';
        return;
    }

    try {
        view('errors.500', ['title' => '系統發生錯誤']);
    } catch (\Throwable) {
        echo '<!doctype html><meta charset="utf-8"><title>系統發生錯誤</title>'
            . '<h1>系統發生錯誤</h1><p>請稍後再試,或聯絡系統管理員。</p>';
    }
});

if (!file_exists(base_path('storage/installed.lock')) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $installUrl = ($scriptDir === '' || $scriptDir === '.') ? '/install.php' : $scriptDir . '/install.php';
    header('Location: ' . $installUrl, true, 302);
    exit;
}

$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
if (file_exists(base_path('storage/maintenance.lock')) && !str_contains($requestPath, '/system-update')) {
    http_response_code(503);
    echo '<!doctype html><html lang="zh-Hant"><head><meta charset="utf-8"><title>系統維護中</title></head><body style="font-family:Arial,sans-serif;padding:40px"><h1>系統維護中</h1><p>系統正在套用更新，請稍後再試。</p></body></html>';
    exit;
}

// 僅限台灣 IP 連線管制(啟用時阻擋國外連線;內網／允許清單放行,出錯放行)。
\App\Core\GeoAccess::enforce();

if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-XSS-Protection: 0');
}

session_name(config('security.session_name', 'foundation_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
}

Auth::instance()->attemptRememberLogin();
Auth::instance()->enforceSessionLifetime();

$router = new Router();

foreach (glob(app_path('Modules/*/routes.php')) ?: [] as $routeFile) {
    require $routeFile;
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
