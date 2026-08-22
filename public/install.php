<?php

declare(strict_types=1);

$basePath = $_SERVER['APP_BASE_PATH'] ?? dirname(__DIR__);
define('BASE_PATH', realpath($basePath) ?: $basePath);

$installLock = BASE_PATH . '/storage/installed.lock';
$errors = [];
$messages = [];

if (file_exists($installLock)) {
    render_page('系統已安裝', 'finish', [
        'errors' => [],
        'messages' => ['系統已完成安裝。如需重新安裝，請先移除 storage/installed.lock。'],
        'locked' => true,
    ]);
    exit;
}

$step = $_GET['step'] ?? 'check';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        match ($_POST['action'] ?? '') {
            'save_config' => handle_save_config(),
            'import_database' => handle_import_database(),
            'create_admin' => handle_create_admin(),
            'finish' => handle_finish(),
            default => throw new RuntimeException('未知的安裝動作'),
        };
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

render_page(title_for_step($step), $step, [
    'errors' => $errors,
    'messages' => $messages,
    'checks' => environment_checks(),
    'config' => load_env_values(),
]);

function handle_save_config(): void
{
    $values = [
        'APP_NAME' => trim((string) ($_POST['app_name'] ?? '游榮吉教育基金會管理系統')),
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'APP_URL' => rtrim(trim((string) ($_POST['app_url'] ?? '')), '/'),
        'APP_TIMEZONE' => 'Asia/Taipei',
        'APP_VERSION' => trim((string) ($_POST['app_version'] ?? '0.6.20')),
        'GITHUB_REPO' => trim((string) ($_POST['github_repo'] ?? '')),
        'GITHUB_TOKEN' => trim((string) ($_POST['github_token'] ?? '')),
        'UPDATE_CHANNEL' => trim((string) ($_POST['update_channel'] ?? 'stable')),
        'DB_HOST' => trim((string) ($_POST['db_host'] ?? 'localhost')),
        'DB_PORT' => trim((string) ($_POST['db_port'] ?? '3306')),
        'DB_DATABASE' => trim((string) ($_POST['db_database'] ?? '')),
        'DB_USERNAME' => trim((string) ($_POST['db_username'] ?? '')),
        'DB_PASSWORD' => (string) ($_POST['db_password'] ?? ''),
        'SESSION_NAME' => trim((string) ($_POST['session_name'] ?? 'foundation_session')),
        'SESSION_LIFETIME_MINUTES' => trim((string) ($_POST['session_lifetime_minutes'] ?? '60')),
        'MAIL_ENABLED' => 'false',
        'MAIL_FROM_ADDRESS' => trim((string) ($_POST['mail_from_address'] ?? 'no-reply@example.com')),
        'MAIL_FROM_NAME' => trim((string) ($_POST['mail_from_name'] ?? '基金會管理系統')),
    ];

    foreach (['APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
        if ($values[$key] === '') {
            throw new RuntimeException($key . ' 不可空白');
        }
    }

    test_database_connection($values);
    ensure_storage_directories();

    $env = '';
    foreach ($values as $key => $value) {
        $env .= $key . '=' . env_quote($value) . PHP_EOL;
        if (in_array($key, ['APP_VERSION', 'UPDATE_CHANNEL', 'DB_PASSWORD', 'SESSION_LIFETIME_MINUTES'], true)) {
            $env .= PHP_EOL;
        }
    }

    if (file_put_contents(BASE_PATH . '/.env', $env) === false) {
        throw new RuntimeException('無法寫入 .env，請確認專案目錄權限');
    }

    header('Location: install.php?step=database');
    exit;
}

function handle_import_database(): void
{
    $pdo = pdo_from_env();

    execute_sql_file($pdo, BASE_PATH . '/database/schema.sql');
    execute_sql_file($pdo, BASE_PATH . '/database/seeds/initial.sql');
    apply_pending_migrations($pdo);

    header('Location: install.php?step=admin');
    exit;
}

function handle_create_admin(): void
{
    $name = trim((string) ($_POST['admin_name'] ?? '系統管理員'));
    $email = trim((string) ($_POST['admin_email'] ?? ''));
    $password = (string) ($_POST['admin_password'] ?? '');
    $confirm = (string) ($_POST['admin_password_confirmation'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        throw new RuntimeException('管理員姓名、Email、密碼不可空白');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('管理員 Email 格式不正確');
    }

    if (strlen($password) < 10) {
        throw new RuntimeException('管理員密碼至少需要 10 個字元');
    }

    if ($password !== $confirm) {
        throw new RuntimeException('兩次密碼輸入不一致');
    }

    $pdo = pdo_from_env();
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
    $stmt->execute(['name' => '系統管理員']);
    $roleId = $stmt->fetchColumn();

    if (!$roleId) {
        throw new RuntimeException('找不到系統管理員角色，請先完成資料庫匯入');
    }

    $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existing->execute(['email' => $email]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET name = :name, password_hash = :password_hash, role_id = :role_id, status = "active",
                 password_changed_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $roleId,
            'id' => $existingId,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, status, password_changed_at, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :role_id, "active", NOW(), NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role_id' => $roleId,
        ]);
    }

    header('Location: install.php?step=finish');
    exit;
}

function handle_finish(): void
{
    ensure_storage_directories();
    $content = json_encode([
        'installed_at' => date('c'),
        'app_version' => load_env_values()['APP_VERSION'] ?? '0.6.20',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if (file_put_contents(BASE_PATH . '/storage/installed.lock', $content . PHP_EOL) === false) {
        throw new RuntimeException('無法寫入 storage/installed.lock，請確認 storage 權限');
    }

    header('Location: index.php');
    exit;
}

function test_database_connection(array $values): void
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $values['DB_HOST'],
        (int) $values['DB_PORT'],
        $values['DB_DATABASE']
    );

    new PDO($dsn, $values['DB_USERNAME'], $values['DB_PASSWORD'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function pdo_from_env(): PDO
{
    $env = load_env_values();
    foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'] as $key) {
        if (($env[$key] ?? '') === '') {
            throw new RuntimeException('資料庫設定尚未完成，請先完成設定步驟');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'],
        (int) $env['DB_PORT'],
        $env['DB_DATABASE']
    );

    return new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function execute_sql_file(PDO $pdo, string $path): void
{
    if (!file_exists($path)) {
        throw new RuntimeException('找不到 SQL 檔案：' . basename($path));
    }

    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('無法讀取 SQL 檔案：' . basename($path));
    }

    $sql = preg_replace('/CREATE\s+DATABASE\b.*?;\s*/is', '', $sql) ?? $sql;
    $sql = preg_replace('/USE\s+[`"]?[A-Za-z0-9_-]+[`"]?\s*;\s*/is', '', $sql) ?? $sql;
    $sql = preg_replace('/CREATE\s+TABLE\s+(?!IF\s+NOT\s+EXISTS)/i', 'CREATE TABLE IF NOT EXISTS ', $sql) ?? $sql;

    foreach (split_sql($sql) as $statement) {
        $trimmed = trim($statement);
        if ($trimmed === '') {
            continue;
        }
        $pdo->exec($trimmed);
    }
}

function apply_pending_migrations(PDO $pdo): void
{
    $dir = BASE_PATH . '/database/migrations';
    if (!is_dir($dir)) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(190) NOT NULL UNIQUE,
            applied_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $applied = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
    sort($files);

    $stmt = $pdo->prepare('INSERT IGNORE INTO schema_migrations (migration, applied_at) VALUES (:migration, NOW())');
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }

        execute_sql_file($pdo, $file);
        $stmt->execute(['migration' => $name]);
    }
}

function split_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($quote === null && $char === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if ($quote === null && $char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        if ($quote === null && $char === '/' && $next === '*') {
            $i += 2;
            while ($i < $length && !($sql[$i] === '*' && ($sql[$i + 1] ?? '') === '/')) {
                $i++;
            }
            $i++;
            continue;
        }

        if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
            $quote = $quote === $char ? null : ($quote ?? $char);
        }

        if ($char === ';' && $quote === null) {
            $statements[] = $buffer;
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    if (trim($buffer) !== '') {
        $statements[] = $buffer;
    }

    return $statements;
}

function environment_checks(): array
{
    $paths = [
        '.env' => BASE_PATH . '/.env',
        'storage' => BASE_PATH . '/storage',
        'storage/logs' => BASE_PATH . '/storage/logs',
        'storage/cache' => BASE_PATH . '/storage/cache',
        'storage/private_uploads' => BASE_PATH . '/storage/private_uploads',
        'storage/updates' => BASE_PATH . '/storage/updates',
        'database/schema.sql' => BASE_PATH . '/database/schema.sql',
        'database/seeds/initial.sql' => BASE_PATH . '/database/seeds/initial.sql',
    ];

    return [
        ['label' => 'PHP 版本 8.2+', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
        ['label' => 'PDO extension', 'ok' => extension_loaded('pdo')],
        ['label' => 'pdo_mysql extension', 'ok' => extension_loaded('pdo_mysql')],
        ['label' => '.env 可寫入或可建立', 'ok' => is_writable(BASE_PATH) || is_writable($paths['.env'])],
        ['label' => 'storage 可寫入', 'ok' => is_dir($paths['storage']) && is_writable($paths['storage'])],
        ['label' => 'schema.sql 存在', 'ok' => file_exists($paths['database/schema.sql'])],
        ['label' => 'initial.sql 存在', 'ok' => file_exists($paths['database/seeds/initial.sql'])],
    ];
}

function ensure_storage_directories(): void
{
    foreach (['storage', 'storage/logs', 'storage/cache', 'storage/private_uploads', 'storage/updates'] as $dir) {
        $path = BASE_PATH . '/' . $dir;
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('無法建立目錄：' . $dir);
        }
    }
}

function load_env_values(): array
{
    $defaults = [
        'APP_NAME' => '游榮吉教育基金會管理系統',
        'APP_URL' => current_url_base(),
        'APP_VERSION' => '0.6.20',
        'GITHUB_REPO' => '',
        'GITHUB_TOKEN' => '',
        'UPDATE_CHANNEL' => 'stable',
        'DB_HOST' => 'localhost',
        'DB_PORT' => '3306',
        'DB_DATABASE' => '',
        'DB_USERNAME' => '',
        'DB_PASSWORD' => '',
        'SESSION_NAME' => 'foundation_session',
        'SESSION_LIFETIME_MINUTES' => '60',
        'MAIL_FROM_ADDRESS' => 'no-reply@example.com',
        'MAIL_FROM_NAME' => '基金會管理系統',
    ];

    $path = BASE_PATH . '/.env';
    if (!file_exists($path)) {
        return $defaults;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $defaults[trim($key)] = $value;
    }

    return $defaults;
}

function current_url_base(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $dir = $dir === '' || $dir === '.' ? '' : $dir;

    return $scheme . '://' . $host . $dir;
}

function env_quote(string $value): string
{
    if ($value === '' || preg_match('/\s|#|"|\'|=/', $value)) {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    return $value;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function title_for_step(string $step): string
{
    return match ($step) {
        'config' => '系統設定',
        'database' => '資料庫匯入',
        'admin' => '建立管理員',
        'finish' => '完成安裝',
        default => '環境檢查',
    };
}

function render_page(string $title, string $step, array $data): void
{
    $config = $data['config'] ?? load_env_values();
    $checks = $data['checks'] ?? [];
    $errors = $data['errors'] ?? [];
    $messages = $data['messages'] ?? [];
    $locked = (bool) ($data['locked'] ?? false);
    $stepOrder = ['check', 'config', 'database', 'admin', 'finish'];
    $stepIndex = array_search($step, $stepOrder, true);
    if ($stepIndex === false) {
        $stepIndex = 0;
    }
    ?>
    <!doctype html>
    <html lang="zh-Hant">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | 基金會管理系統安裝</title>
        <style>
            :root {
                --bg: #f4f7f5;
                --surface: #fff;
                --line: #dfe6e2;
                --text: #18201c;
                --muted: #66736c;
                --primary: #1f7a5b;
                --primary-strong: #155f47;
                --danger-bg: #fff1f1;
                --danger: #9f2d2d;
                --success-bg: #edf8f1;
                --success: #226a43;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                background: var(--bg);
                color: var(--text);
                font-family: "Noto Sans TC", "Microsoft JhengHei", Arial, sans-serif;
                font-size: 15px;
            }
            .wrap {
                width: min(980px, calc(100vw - 32px));
                margin: 34px auto;
            }
            .header {
                display: flex;
                justify-content: space-between;
                gap: 20px;
                align-items: flex-start;
                margin-bottom: 18px;
            }
            h1 { margin: 0 0 8px; font-size: 28px; }
            p { color: var(--muted); line-height: 1.7; }
            .card {
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 22px;
                margin-bottom: 16px;
            }
            .steps {
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 8px;
            }
            .step {
                padding: 10px;
                border: 1px solid var(--line);
                border-radius: 8px;
                color: var(--muted);
                text-align: center;
            }
            .step.active,
            .step.done {
                background: var(--success-bg);
                color: var(--success);
                border-color: #bfe7ca;
                font-weight: 700;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }
            label span {
                display: block;
                color: var(--muted);
                margin-bottom: 6px;
            }
            input,
            select {
                width: 100%;
                min-height: 42px;
                padding: 9px 11px;
                border: 1px solid var(--line);
                border-radius: 8px;
                font: inherit;
            }
            input:focus,
            select:focus {
                outline: 2px solid rgba(31, 122, 91, .2);
                border-color: var(--primary);
            }
            .span-2 { grid-column: span 2; }
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 40px;
                padding: 9px 15px;
                border: 1px solid var(--line);
                border-radius: 8px;
                background: #fff;
                color: var(--text);
                text-decoration: none;
                cursor: pointer;
                font: inherit;
            }
            .btn.primary {
                border-color: var(--primary);
                background: var(--primary);
                color: #fff;
            }
            .btn.primary:hover { background: var(--primary-strong); }
            .actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 18px;
            }
            .alert {
                padding: 12px 14px;
                border-radius: 8px;
                margin-bottom: 12px;
            }
            .alert.error {
                background: var(--danger-bg);
                border: 1px solid #f2c1c1;
                color: var(--danger);
            }
            .alert.success {
                background: var(--success-bg);
                border: 1px solid #bfe7ca;
                color: var(--success);
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th,
            td {
                border-bottom: 1px solid var(--line);
                padding: 12px 10px;
                text-align: left;
            }
            th { color: var(--muted); }
            .ok { color: var(--success); font-weight: 700; }
            .fail { color: var(--danger); font-weight: 700; }
            code {
                background: #eef2ef;
                border-radius: 6px;
                padding: 2px 6px;
            }
            @media (max-width: 760px) {
                .header,
                .grid,
                .steps {
                    grid-template-columns: 1fr;
                    display: grid;
                }
                .span-2 { grid-column: span 1; }
            }
        </style>
    </head>
    <body>
    <main class="wrap">
        <div class="header">
            <div>
                <h1>基金會管理系統安裝</h1>
                <p>依序完成環境檢查、系統設定、資料庫匯入、管理員建立。cPanel 上請先建立好 MySQL 資料庫與使用者。</p>
            </div>
        </div>

        <div class="card steps">
            <?php foreach ($stepOrder as $index => $item): ?>
                <div class="step <?= $index < $stepIndex ? 'done' : ($index === $stepIndex ? 'active' : '') ?>">
                    <?= e(title_for_step($item)) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?= e((string) $error) ?></div>
        <?php endforeach; ?>
        <?php foreach ($messages as $message): ?>
            <div class="alert success"><?= e((string) $message) ?></div>
        <?php endforeach; ?>

        <?php if ($locked): ?>
            <section class="card">
                <p>安裝鎖定檔位於 <code>storage/installed.lock</code>。正式環境請保留此檔，避免他人重新執行安裝。</p>
                <a class="btn primary" href="index.php">前往系統</a>
            </section>
        <?php elseif ($step === 'config'): ?>
            <?php render_config_step($config); ?>
        <?php elseif ($step === 'database'): ?>
            <?php render_database_step(); ?>
        <?php elseif ($step === 'admin'): ?>
            <?php render_admin_step(); ?>
        <?php elseif ($step === 'finish'): ?>
            <?php render_finish_step(); ?>
        <?php else: ?>
            <?php render_check_step($checks); ?>
        <?php endif; ?>
    </main>
    </body>
    </html>
    <?php
}

function render_check_step(array $checks): void
{
    $allOk = array_reduce($checks, static fn (bool $carry, array $check) => $carry && $check['ok'], true);
    ?>
    <section class="card">
        <h2>環境檢查</h2>
        <table>
            <tbody>
            <?php foreach ($checks as $check): ?>
                <tr>
                    <td><?= e($check['label']) ?></td>
                    <td class="<?= $check['ok'] ? 'ok' : 'fail' ?>"><?= $check['ok'] ? '通過' : '未通過' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="actions">
            <?php if ($allOk): ?>
                <a class="btn primary" href="install.php?step=config">下一步</a>
            <?php else: ?>
                <a class="btn" href="install.php">重新檢查</a>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

function render_config_step(array $config): void
{
    ?>
    <section class="card">
        <h2>系統與資料庫設定</h2>
        <form method="post" class="grid">
            <input type="hidden" name="action" value="save_config">
            <label>
                <span>系統名稱</span>
                <input name="app_name" value="<?= e($config['APP_NAME']) ?>" required>
            </label>
            <label>
                <span>網站網址</span>
                <input name="app_url" value="<?= e($config['APP_URL']) ?>" required>
            </label>
            <label>
                <span>資料庫主機</span>
                <input name="db_host" value="<?= e($config['DB_HOST']) ?>" required>
            </label>
            <label>
                <span>資料庫 Port</span>
                <input name="db_port" value="<?= e($config['DB_PORT']) ?>" required>
            </label>
            <label>
                <span>資料庫名稱</span>
                <input name="db_database" value="<?= e($config['DB_DATABASE']) ?>" required>
            </label>
            <label>
                <span>資料庫使用者</span>
                <input name="db_username" value="<?= e($config['DB_USERNAME']) ?>" required>
            </label>
            <label class="span-2">
                <span>資料庫密碼</span>
                <input type="password" name="db_password" value="<?= e($config['DB_PASSWORD']) ?>">
            </label>
            <label>
                <span>Session 名稱</span>
                <input name="session_name" value="<?= e($config['SESSION_NAME']) ?>">
            </label>
            <label>
                <span>Session 逾時分鐘</span>
                <input name="session_lifetime_minutes" value="<?= e($config['SESSION_LIFETIME_MINUTES']) ?>">
            </label>
            <label>
                <span>目前版本</span>
                <input name="app_version" value="<?= e($config['APP_VERSION']) ?>">
            </label>
            <label>
                <span>GitHub Repo</span>
                <input name="github_repo" value="<?= e($config['GITHUB_REPO']) ?>" placeholder="your-org/foundation-system">
            </label>
            <label>
                <span>GitHub Token</span>
                <input type="password" name="github_token" value="<?= e($config['GITHUB_TOKEN']) ?>">
            </label>
            <label>
                <span>更新通道</span>
                <select name="update_channel">
                    <option value="stable" <?= $config['UPDATE_CHANNEL'] === 'stable' ? 'selected' : '' ?>>stable</option>
                    <option value="all" <?= $config['UPDATE_CHANNEL'] === 'all' ? 'selected' : '' ?>>all</option>
                </select>
            </label>
            <label>
                <span>寄件 Email</span>
                <input name="mail_from_address" value="<?= e($config['MAIL_FROM_ADDRESS']) ?>">
            </label>
            <label>
                <span>寄件名稱</span>
                <input name="mail_from_name" value="<?= e($config['MAIL_FROM_NAME']) ?>">
            </label>
            <div class="actions span-2">
                <a class="btn" href="install.php">上一步</a>
                <button class="btn primary" type="submit">測試連線並寫入 .env</button>
            </div>
        </form>
    </section>
    <?php
}

function render_database_step(): void
{
    ?>
    <section class="card">
        <h2>匯入資料庫</h2>
        <p>此步驟會匯入 <code>database/schema.sql</code> 與 <code>database/seeds/initial.sql</code>。cPanel 已建立好的資料庫會直接使用，不會執行 CREATE DATABASE。</p>
        <form method="post">
            <input type="hidden" name="action" value="import_database">
            <div class="actions">
                <a class="btn" href="install.php?step=config">上一步</a>
                <button class="btn primary" type="submit">開始匯入</button>
            </div>
        </form>
    </section>
    <?php
}

function render_admin_step(): void
{
    ?>
    <section class="card">
        <h2>建立第一個系統管理員</h2>
        <form method="post" class="grid">
            <input type="hidden" name="action" value="create_admin">
            <label>
                <span>姓名</span>
                <input name="admin_name" value="系統管理員" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="admin_email" required>
            </label>
            <label>
                <span>密碼</span>
                <input type="password" name="admin_password" required>
            </label>
            <label>
                <span>確認密碼</span>
                <input type="password" name="admin_password_confirmation" required>
            </label>
            <div class="actions span-2">
                <a class="btn" href="install.php?step=database">上一步</a>
                <button class="btn primary" type="submit">建立管理員</button>
            </div>
        </form>
    </section>
    <?php
}

function render_finish_step(): void
{
    ?>
    <section class="card">
        <h2>完成安裝</h2>
        <p>最後一步會建立 <code>storage/installed.lock</code>，之後 <code>install.php</code> 會自動鎖定，避免重複安裝。</p>
        <form method="post">
            <input type="hidden" name="action" value="finish">
            <div class="actions">
                <a class="btn" href="install.php?step=admin">上一步</a>
                <button class="btn primary" type="submit">完成並進入系統</button>
            </div>
        </form>
    </section>
    <?php
}
