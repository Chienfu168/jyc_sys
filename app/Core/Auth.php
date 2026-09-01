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

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $success = $user && $user['status'] === 'active' && password_verify($password, $user['password_hash']);

        $this->recordLoginAttempt($email, $success);

        if (!$success) {
            // 累計同一來源 IP 的登入失敗,達門檻即自動暫時封鎖(fail2ban 式)。
            IpGuard::registerLoginFailure(IpGuard::currentClientIp());
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['last_activity_at'] = time();
        $_SESSION['remember'] = $remember;

        if ($remember) {
            $this->issueRememberToken((int) $user['id']);
        }

        Database::pdo()
            ->prepare('UPDATE users SET last_login_at = :last_login_at WHERE id = :id')
            ->execute(['last_login_at' => now(), 'id' => $user['id']]);

        AuditLog::write('login', 'auth', 'users', (int) $user['id']);
        return true;
    }

    /**
     * 若目前尚未登入,嘗試以「記住我」持久 cookie 還原登入狀態。
     *
     * 採 selector:validator 雙段式權杖:selector 明碼查詢、validator 以 sha256
     * 雜湊比對(hash_equals),資料庫不存明碼。命中即建立 session 並以滑動方式
     * 延長權杖效期,供 App / PWA 於 session cookie 或閒置逾時後免重新登入。
     */
    public function attemptRememberLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            return;
        }

        $cookie = (string) ($_COOKIE[$this->rememberCookieName()] ?? '');
        if ($cookie === '' || !str_contains($cookie, ':')) {
            return;
        }

        [$selector, $validator] = explode(':', $cookie, 2);
        if ($selector === '' || $validator === '') {
            $this->forgetRememberCookie();
            return;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT remember_tokens.*, users.status AS user_status
             FROM remember_tokens
             INNER JOIN users ON users.id = remember_tokens.user_id
             WHERE remember_tokens.selector = :selector
               AND remember_tokens.expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token || !hash_equals((string) $token['validator_hash'], hash('sha256', $validator))) {
            // selector 命中但 validator 不符,視為權杖失竊或失效,刪除該筆並清 cookie。
            if ($token) {
                Database::pdo()->prepare('DELETE FROM remember_tokens WHERE id = :id')
                    ->execute(['id' => $token['id']]);
            }
            $this->forgetRememberCookie();
            return;
        }

        if ($token['user_status'] !== 'active') {
            Database::pdo()->prepare('DELETE FROM remember_tokens WHERE id = :id')
                ->execute(['id' => $token['id']]);
            $this->forgetRememberCookie();
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $token['user_id'];
        $_SESSION['last_activity_at'] = time();
        $_SESSION['remember'] = true;

        // 滑動延長效期,並更新 cookie(不更換 validator,避免 App 並發請求互相失效)。
        $expiresAt = time() + ($this->rememberLifetimeDays() * 86400);
        Database::pdo()->prepare(
            'UPDATE remember_tokens SET expires_at = :expires_at, last_used_at = :used_at WHERE id = :id'
        )->execute([
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'used_at' => now(),
            'id' => $token['id'],
        ]);
        $this->writeRememberCookie($selector . ':' . $validator, $expiresAt);
    }

    public function logout(): void
    {
        if ($this->check()) {
            AuditLog::write('logout', 'auth', 'users', (int) $this->user()['id']);
        }

        $this->clearRememberToken();

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

        // 勾選「記住我」的登入採較長的閒置容許時間(與持久權杖效期一致),
        // 讓 App / PWA 可長時間免重新登入;未勾選者維持既有較短的閒置逾時。
        $lifetime = empty($_SESSION['remember'])
            ? config('security.session_lifetime_minutes', 60) * 60
            : $this->rememberLifetimeDays() * 86400;

        if ((time() - ($_SESSION['last_activity_at'] ?? 0)) > $lifetime) {
            $this->logout();
            redirect('/login');
        }

        $_SESSION['last_activity_at'] = time();
    }

    private function rememberCookieName(): string
    {
        return (string) config('security.remember_cookie', 'foundation_remember');
    }

    private function rememberLifetimeDays(): int
    {
        return max(1, (int) config('security.remember_lifetime_days', 30));
    }

    /** 產生並儲存「記住我」持久權杖,同時寫入 cookie。 */
    private function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expiresAt = time() + ($this->rememberLifetimeDays() * 86400);

        Database::pdo()->prepare(
            'INSERT INTO remember_tokens (user_id, selector, validator_hash, user_agent, expires_at, created_at, last_used_at)
             VALUES (:user_id, :selector, :validator_hash, :user_agent, :expires_at, :created_at, :last_used_at)'
        )->execute([
            'user_id' => $userId,
            'selector' => $selector,
            'validator_hash' => hash('sha256', $validator),
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
            'created_at' => now(),
            'last_used_at' => now(),
        ]);

        $this->writeRememberCookie($selector . ':' . $validator, $expiresAt);
    }

    /** 刪除目前 cookie 對應的持久權杖並清除 cookie。 */
    private function clearRememberToken(): void
    {
        $cookie = (string) ($_COOKIE[$this->rememberCookieName()] ?? '');
        if ($cookie !== '' && str_contains($cookie, ':')) {
            [$selector] = explode(':', $cookie, 2);
            if ($selector !== '') {
                Database::pdo()->prepare('DELETE FROM remember_tokens WHERE selector = :selector')
                    ->execute(['selector' => $selector]);
            }
        }
        $this->forgetRememberCookie();
    }

    private function writeRememberCookie(string $value, int $expiresAt): void
    {
        setcookie($this->rememberCookieName(), $value, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[$this->rememberCookieName()] = $value;
    }

    private function forgetRememberCookie(): void
    {
        setcookie($this->rememberCookieName(), '', [
            'expires' => time() - 42000,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[$this->rememberCookieName()]);
    }

    public function tooManyAttempts(string $email): bool
    {
        $minutes = config('security.login_lock_minutes', 15);
        $max = (int) config('security.max_login_attempts', 5);
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $ip = IpGuard::currentClientIp();

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
            'ip_address' => IpGuard::currentClientIp(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'success' => $success ? 1 : 0,
            'created_at' => now(),
        ]);
    }
}
