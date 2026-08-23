<?php

namespace App\Core;

use PDO;

final class Auth
{
    private static ?self $instance = null;
    private ?array $user = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?array
    {
        if ($this->user !== null) {
            return $this->user;
        }

        if (empty($_SESSION['user_id'])) {
            return null;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT users.*, roles.name AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id AND users.status = "active"
             LIMIT 1'
        );
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->user = $user ?: null;
    }

    public function attempt(string $email, string $password): bool
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $success = $user && $user['status'] === 'active' && password_verify($password, $user['password_hash']);

        $this->recordLoginAttempt($email, $success);

        if (!$success) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_activity_at'] = time();

        Database::pdo()
            ->prepare('UPDATE users SET last_login_at = :last_login_at WHERE id = :id')
            ->execute(['last_login_at' => now(), 'id' => $user['id']]);

        AuditLog::write('login', 'auth', 'users', (int) $user['id']);
        return true;
    }

    public function logout(): void
    {
        if ($this->check()) {
            AuditLog::write('logout', 'auth', 'users', (int) $this->user()['id']);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function enforceSessionLifetime(): void
    {
        if (!$this->check()) {
            return;
        }

        $lifetime = config('security.session_lifetime_minutes', 60) * 60;
        if ((time() - ($_SESSION['last_activity_at'] ?? 0)) > $lifetime) {
            $this->logout();
            redirect('/login');
        }

        $_SESSION['last_activity_at'] = time();
    }

    public function tooManyAttempts(string $email): bool
    {
        $minutes = config('security.login_lock_minutes', 15);
        $max = (int) config('security.max_login_attempts', 5);
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        // 對單一 Email 鎖定,阻擋針對特定帳號的密碼猜測。
        $byEmail = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE email = :email AND success = 0 AND created_at >= :since'
        );
        $byEmail->execute(['email' => $email, 'since' => $since]);
        if ((int) $byEmail->fetchColumn() >= $max) {
            return true;
        }

        // 對單一來源 IP 鎖定(較高門檻),阻擋輪替 Email 的暴力嘗試。
        if ($ip !== '') {
            $ipMax = $max * 4;
            $byIp = Database::pdo()->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE ip_address = :ip AND success = 0 AND created_at >= :since'
            );
            $byIp->execute(['ip' => $ip, 'since' => $since]);
            if ((int) $byIp->fetchColumn() >= $ipMax) {
                return true;
            }
        }

        return false;
    }

    private function recordLoginAttempt(string $email, bool $success): void
    {
        Database::pdo()->prepare(
            'INSERT INTO login_attempts (email, ip_address, user_agent, success, created_at)
             VALUES (:email, :ip_address, :user_agent, :success, :created_at)'
        )->execute([
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'success' => $success ? 1 : 0,
            'created_at' => now(),
        ]);
    }
}
