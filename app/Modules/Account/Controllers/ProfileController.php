<?php

namespace App\Modules\Account\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        $this->requireAuth();
        $user = $this->user();

        $this->render('account.profile', [
            'title' => '我的帳號資料',
            'section' => '個人設定',
            'active' => 'account-profile',
            'user' => $user,
            'printable' => false,
        ]);
    }

    public function update(): void
    {
        $this->requireAuth();
        $user = $this->user();

        if ($error = Validator::required($_POST, [
            'name' => '姓名',
            'email' => 'Email',
        ])) {
            $this->backWithInput('/account/profile', $_POST, $error);
        }

        try {
            Database::pdo()->prepare(
                'UPDATE users
                 SET name = :name,
                     email = :email,
                     phone = :phone,
                     department = :department,
                     job_title = :job_title,
                     employee_no = :employee_no,
                     profile_notes = :profile_notes,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'name' => trim((string) $_POST['name']),
                'email' => trim((string) $_POST['email']),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'department' => trim((string) ($_POST['department'] ?? '')),
                'job_title' => trim((string) ($_POST['job_title'] ?? '')),
                'employee_no' => trim((string) ($_POST['employee_no'] ?? '')),
                'profile_notes' => trim((string) ($_POST['profile_notes'] ?? '')),
                'updated_at' => now(),
                'id' => (int) $user['id'],
            ]);
        } catch (\PDOException) {
            $this->backWithInput('/account/profile', $_POST, 'Email 已被使用，請改用其他信箱。');
        }

        AuditLog::write('update_profile', 'account', 'users', (int) $user['id']);
        flash('success', '帳號資料已更新');
        redirect('/account/profile');
    }

    private function user(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT users.*, roles.name AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             WHERE users.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => auth()->user()['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            redirect('/login');
        }

        return $user;
    }
}
