<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::instance()->check()) {
            redirect('/');
        }

        $this->render('auth.login', ['title' => '系統登入']);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($error = Validator::required($_POST, ['email' => 'Email', 'password' => '密碼'])) {
            $this->backWithInput('/login', ['email' => $email], $error);
        }

        if (Auth::instance()->tooManyAttempts($email)) {
            $this->backWithInput('/login', ['email' => $email], '登入失敗次數過多，請稍後再試');
        }

        if (!Auth::instance()->attempt($email, $password)) {
            $this->backWithInput('/login', ['email' => $email], '帳號或密碼錯誤');
        }

        redirect('/');
    }

    public function logout(): void
    {
        Auth::instance()->logout();
        redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->render('auth.forgot-password', ['title' => '忘記密碼']);
    }

    public function sendResetLink(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email === '') {
            $this->backWithInput('/forgot-password', [], 'Email 不可空白');
        }

        $stmt = Database::pdo()->prepare('SELECT id, email FROM users WHERE email = :email AND status = "active" LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + (config('security.password_reset_minutes', 30) * 60));

            Database::pdo()->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at)
                 VALUES (:user_id, :token_hash, :expires_at, :created_at)'
            )->execute([
                'user_id' => $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'created_at' => now(),
            ]);

            if (!env_bool('MAIL_ENABLED', false)) {
                $link = rtrim(config('app.url'), '/') . '/reset-password?token=' . $token . '&email=' . urlencode($email);
                file_put_contents(storage_path('logs/password-reset.log'), '[' . now() . '] ' . $email . ' ' . $link . PHP_EOL, FILE_APPEND);
            }
        }

        flash('success', '如果帳號存在，系統會寄出重設密碼通知；本機開發環境可查看 storage/logs/password-reset.log');
        redirect('/forgot-password');
    }

    public function showResetPassword(): void
    {
        $this->render('auth.reset-password', [
            'title' => '重設密碼',
            'token' => $_GET['token'] ?? '',
            'email' => $_GET['email'] ?? '',
        ]);
    }

    public function resetPassword(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if ($error = Validator::required($_POST, [
            'email' => 'Email',
            'token' => '重設 token',
            'password' => '新密碼',
            'password_confirmation' => '確認密碼',
        ])) {
            $this->backWithInput('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email), [], $error);
        }

        if (strlen($password) < 10) {
            $this->backWithInput('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email), [], '密碼至少需要 10 個字元');
        }

        if ($password !== $passwordConfirmation) {
            $this->backWithInput('/reset-password?token=' . urlencode($token) . '&email=' . urlencode($email), [], '兩次密碼輸入不一致');
        }

        $stmt = Database::pdo()->prepare(
            'SELECT password_reset_tokens.*, users.email
             FROM password_reset_tokens
             INNER JOIN users ON users.id = password_reset_tokens.user_id
             WHERE users.email = :email
               AND password_reset_tokens.token_hash = :token_hash
               AND password_reset_tokens.used_at IS NULL
               AND password_reset_tokens.expires_at >= NOW()
             ORDER BY password_reset_tokens.id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'email' => $email,
            'token_hash' => hash('sha256', $token),
        ]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            $this->backWithInput('/forgot-password', [], '重設連結無效或已逾期');
        }

        Database::pdo()->beginTransaction();
        Database::pdo()->prepare(
            'UPDATE users SET password_hash = :password_hash, password_changed_at = :password_changed_at, updated_at = :updated_at WHERE id = :id'
        )->execute([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'password_changed_at' => now(),
            'updated_at' => now(),
            'id' => $reset['user_id'],
        ]);

        Database::pdo()->prepare('UPDATE password_reset_tokens SET used_at = :used_at WHERE user_id = :user_id AND used_at IS NULL')
            ->execute(['used_at' => now(), 'user_id' => $reset['user_id']]);
        Database::pdo()->commit();

        flash('success', '密碼已更新，請使用新密碼登入');
        redirect('/login');
    }
}
