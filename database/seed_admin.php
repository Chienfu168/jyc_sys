<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Support/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

\App\Core\Env::load(base_path('.env'));
date_default_timezone_set(config('app.timezone', 'Asia/Taipei'));

$email = $argv[1] ?? 'admin@example.com';
$password = $argv[2] ?? null;

if (!$password || strlen($password) < 10) {
    fwrite(STDERR, "Usage: php database/seed_admin.php admin@example.com StrongPassword123\n");
    fwrite(STDERR, "Password must be at least 10 characters.\n");
    exit(1);
}

$pdo = \App\Core\Database::pdo();
$stmt = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
$stmt->execute(['name' => '系統管理員']);
$roleId = $stmt->fetchColumn();

if (!$roleId) {
    fwrite(STDERR, "Role not found. Import database/schema.sql and database/seeds/initial.sql first.\n");
    exit(1);
}

$existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
$existing->execute(['email' => $email]);

if ($existing->fetchColumn()) {
    $pdo->prepare(
        'UPDATE users
         SET password_hash = :password_hash, role_id = :role_id, status = "active",
             password_changed_at = :password_changed_at, updated_at = :updated_at
         WHERE email = :email'
    )->execute([
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role_id' => $roleId,
        'password_changed_at' => now(),
        'updated_at' => now(),
        'email' => $email,
    ]);
    echo "Admin user updated: {$email}\n";
    exit(0);
}

$pdo->prepare(
    'INSERT INTO users (name, email, password_hash, role_id, status, password_changed_at, created_at, updated_at)
     VALUES (:name, :email, :password_hash, :role_id, "active", :password_changed_at, :created_at, :updated_at)'
)->execute([
    'name' => '系統管理員',
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role_id' => $roleId,
    'password_changed_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Admin user created: {$email}\n";
