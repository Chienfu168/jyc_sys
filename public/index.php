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

if (!file_exists(base_path('storage/installed.lock')) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $installUrl = ($scriptDir === '' || $scriptDir === '.') ? '/install.php' : $scriptDir . '/install.php';
    header('Location: ' . $installUrl, true, 302);
    exit;
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

Auth::instance()->enforceSessionLifetime();

$router = new Router();

foreach (glob(app_path('Modules/*/routes.php')) ?: [] as $routeFile) {
    require $routeFile;
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
