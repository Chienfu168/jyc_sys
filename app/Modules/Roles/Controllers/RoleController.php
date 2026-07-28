<?php

namespace App\Modules\Roles\Controllers;

use App\Core\Controller;
use App\Core\Database;

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
            'roles' => $roles,
        ]);
    }
}
