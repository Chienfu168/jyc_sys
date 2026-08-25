<?php

namespace App\Modules\Roles\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class RoleController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('roles.view');

        $roles = Database::pdo()->query(
            'SELECT roles.id, roles.name, roles.description, roles.created_at, roles.updated_at,
                    COUNT(role_permissions.permission_id) AS permission_count
             FROM roles
             LEFT JOIN role_permissions ON role_permissions.role_id = roles.id
             GROUP BY roles.id, roles.name, roles.description, roles.created_at, roles.updated_at
             ORDER BY roles.id'
        )->fetchAll();

        $this->render('roles.index', [
            'title' => '角色權限',
            'section' => '後台管理',
            'active' => 'roles',
            'roles' => $roles,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('roles.manage');

        $this->render('roles.create', [
            'title' => '新增角色',
            'section' => '後台管理',
            'active' => 'roles',
            'role' => null,
            'permissions' => $this->permissionsByModule(),
            'selectedPermissions' => [],
            'action' => '/roles',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('roles.manage');

        if ($error = Validator::required($_POST, ['name' => '角色名稱'])) {
            $this->backWithInput('/roles/create', $_POST, $error);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO roles (name, description, created_at, updated_at)
                 VALUES (:name, :description, :created_at, :updated_at)'
            )->execute([
                'name' => trim((string) $_POST['name']),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleId = (int) $pdo->lastInsertId();
            $this->syncPermissions($roleId, $this->postedPermissionCodes());
            $pdo->commit();
        } catch (\PDOException) {
            $pdo->rollBack();
            $this->backWithInput('/roles/create', $_POST, '角色名稱已存在，請改用其他名稱。');
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            $this->backWithInput('/roles/create', $_POST, '角色建立失敗：' . $exception->getMessage());
        }

        AuditLog::write('create', 'roles', 'roles', $roleId);
        flash('success', '角色已建立。');
        redirect('/roles');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('roles.manage');
        $role = $this->findRole((int) $id);

        $this->render('roles.edit', [
            'title' => '編輯角色',
            'section' => '後台管理',
            'active' => 'roles',
            'role' => $role,
            'permissions' => $this->permissionsByModule(),
            'selectedPermissions' => (int) $role['id'] === 1
                ? $this->allPermissionCodes()
                : $this->rolePermissionCodes((int) $role['id']),
            'action' => '/roles/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('roles.manage');
        $role = $this->findRole((int) $id);

        if ($error = Validator::required($_POST, ['name' => '角色名稱'])) {
            $this->backWithInput('/roles/' . $id . '/edit', $_POST, $error);
        }

        $name = trim((string) $_POST['name']);
        if ((int) $role['id'] === 1) {
            $name = '系統管理員';
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE roles
                 SET name = :name,
                     description = :description,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'name' => $name,
                'description' => trim((string) ($_POST['description'] ?? '')),
                'updated_at' => now(),
                'id' => (int) $role['id'],
            ]);

            $this->syncPermissions((int) $role['id'], $this->postedPermissionCodes());
            $pdo->commit();
        } catch (\PDOException) {
            $pdo->rollBack();
            $this->backWithInput('/roles/' . $id . '/edit', $_POST, '角色名稱已存在，請改用其他名稱。');
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            $this->backWithInput('/roles/' . $id . '/edit', $_POST, '角色更新失敗：' . $exception->getMessage());
        }

        AuditLog::write('update', 'roles', 'roles', (int) $role['id']);
        flash('success', '角色權限已更新。');
        redirect('/roles');
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('roles.delete');
        $role = $this->findRole((int) $id);

        if ((int) $role['id'] === 1) {
            flash('error', '系統管理員角色不可刪除。');
            redirect('/roles');
        }

        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE role_id = :id');
        $stmt->execute(['id' => (int) $role['id']]);
        if ((int) $stmt->fetchColumn() > 0) {
            flash('error', '仍有使用者屬於此角色，請先調整這些帳號的角色後再刪除。');
            redirect('/roles');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :id')->execute(['id' => (int) $role['id']]);
            $pdo->prepare('DELETE FROM roles WHERE id = :id')->execute(['id' => (int) $role['id']]);
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            flash('error', '角色刪除失敗，可能仍有關聯資料。');
            redirect('/roles');
        }

        AuditLog::write('delete', 'roles', 'roles', (int) $role['id'], ['name' => $role['name'] ?? null]);
        flash('success', '角色已刪除。');
        redirect('/roles');
    }

    private function findRole(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM roles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到角色']);
            exit;
        }

        return $role;
    }

    private function permissionsByModule(): array
    {
        $permissions = Database::pdo()->query(
            'SELECT code, name, module
             FROM permissions
             ORDER BY module, code'
        )->fetchAll();

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = (string) $permission['module'];
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    private function rolePermissionCodes(int $roleId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT permissions.code
             FROM role_permissions
             INNER JOIN permissions ON permissions.id = role_permissions.permission_id
             WHERE role_permissions.role_id = :role_id'
        );
        $stmt->execute(['role_id' => $roleId]);

        return array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function allPermissionCodes(): array
    {
        return array_map(
            'strval',
            Database::pdo()->query('SELECT code FROM permissions ORDER BY module, code')
                ->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    private function postedPermissionCodes(): array
    {
        $permissions = $_POST['permissions'] ?? [];
        if (!is_array($permissions)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map('strval', $permissions),
            static fn (string $code): bool => preg_match('/^[a-z0-9_.]+$/', $code) === 1
        )));
    }

    private function syncPermissions(int $roleId, array $codes): void
    {
        if ($roleId === 1) {
            $codes = $this->allPermissionCodes();
        }

        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')
            ->execute(['role_id' => $roleId]);

        if (!$codes) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $stmt = $pdo->prepare('SELECT id FROM permissions WHERE code IN (' . $placeholders . ')');
        $stmt->execute($codes);
        $permissionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $insert = $pdo->prepare(
            'INSERT IGNORE INTO role_permissions (role_id, permission_id)
             VALUES (:role_id, :permission_id)'
        );

        foreach ($permissionIds as $permissionId) {
            $insert->execute([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
