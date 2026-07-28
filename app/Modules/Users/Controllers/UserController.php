<?php

namespace App\Modules\Users\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class UserController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('users.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $params = [];
        $where = '';

        if ($keyword !== '') {
            $where = 'WHERE users.name LIKE :keyword OR users.email LIKE :keyword';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            "SELECT users.*, roles.name AS role_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             {$where}
             ORDER BY users.id DESC"
        );
        $stmt->execute($params);

        $this->render('users.index', [
            'title' => '使用者管理',
            'section' => '後台管理',
            'users' => $stmt->fetchAll(),
            'keyword' => $keyword,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('users.create');
        $this->render('users.create', [
            'title' => '新增使用者',
            'section' => '後台管理',
            'roles' => $this->roles(),
            'user' => null,
            'action' => '/users',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('users.create');

        if ($error = Validator::required($_POST, [
            'name' => '姓名',
            'email' => 'Email',
            'password' => '密碼',
            'role_id' => '角色',
        ])) {
            $this->backWithInput('/users/create', $_POST, $error);
        }

        if (strlen((string) $_POST['password']) < 10) {
            $this->backWithInput('/users/create', $_POST, '密碼至少需要 10 個字元');
        }

        try {
            Database::pdo()->prepare(
                'INSERT INTO users (name, email, password_hash, role_id, status, password_changed_at, created_at, updated_at)
                 VALUES (:name, :email, :password_hash, :role_id, :status, :password_changed_at, :created_at, :updated_at)'
            )->execute([
                'name' => trim((string) $_POST['name']),
                'email' => trim((string) $_POST['email']),
                'password_hash' => password_hash((string) $_POST['password'], PASSWORD_DEFAULT),
                'role_id' => (int) $_POST['role_id'],
                'status' => $_POST['status'] ?? 'active',
                'password_changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\PDOException $e) {
            $this->backWithInput('/users/create', $_POST, 'Email 已存在或資料格式錯誤');
        }

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'users', 'users', $id);
        flash('success', '使用者已建立');
        redirect('/users');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('users.update');
        $user = $this->findUser((int) $id);

        $this->render('users.edit', [
            'title' => '編輯使用者',
            'section' => '後台管理',
            'roles' => $this->roles(),
            'user' => $user,
            'action' => '/users/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('users.update');

        if ($error = Validator::required($_POST, [
            'name' => '姓名',
            'email' => 'Email',
            'role_id' => '角色',
        ])) {
            $this->backWithInput('/users/' . $id . '/edit', $_POST, $error);
        }

        $params = [
            'name' => trim((string) $_POST['name']),
            'email' => trim((string) $_POST['email']),
            'role_id' => (int) $_POST['role_id'],
            'status' => $_POST['status'] ?? 'active',
            'updated_at' => now(),
            'id' => (int) $id,
        ];

        $passwordSql = '';
        if (trim((string) ($_POST['password'] ?? '')) !== '') {
            if (strlen((string) $_POST['password']) < 10) {
                $this->backWithInput('/users/' . $id . '/edit', $_POST, '密碼至少需要 10 個字元');
            }
            $passwordSql = ', password_hash = :password_hash, password_changed_at = :password_changed_at';
            $params['password_hash'] = password_hash((string) $_POST['password'], PASSWORD_DEFAULT);
            $params['password_changed_at'] = now();
        }

        try {
            Database::pdo()->prepare(
                "UPDATE users
                 SET name = :name, email = :email, role_id = :role_id, status = :status, updated_at = :updated_at {$passwordSql}
                 WHERE id = :id"
            )->execute($params);
        } catch (\PDOException $e) {
            $this->backWithInput('/users/' . $id . '/edit', $_POST, 'Email 已存在或資料格式錯誤');
        }

        AuditLog::write('update', 'users', 'users', (int) $id);
        flash('success', '使用者已更新');
        redirect('/users');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('users.update');
        $user = $this->findUser((int) $id);
        $status = $user['status'] === 'active' ? 'disabled' : 'active';

        Database::pdo()->prepare('UPDATE users SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute(['status' => $status, 'updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('toggle_status', 'users', 'users', (int) $id, ['status' => $status]);
        flash('success', '帳號狀態已更新');
        redirect('/users');
    }

    private function roles(): array
    {
        return Database::pdo()->query('SELECT id, name FROM roles ORDER BY id')->fetchAll();
    }

    private function findUser(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到使用者']);
            exit;
        }

        return $user;
    }
}
