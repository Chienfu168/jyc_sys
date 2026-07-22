<?php

namespace App\Core;

final class Permission
{
    public static function can(string $code): bool
    {
        $user = Auth::instance()->user();
        if (!$user) {
            return false;
        }

        if (($user['role_name'] ?? '') === '系統管理員') {
            return true;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*)
             FROM role_permissions
             INNER JOIN permissions ON permissions.id = role_permissions.permission_id
             WHERE role_permissions.role_id = :role_id AND permissions.code = :code'
        );
        $stmt->execute([
            'role_id' => $user['role_id'],
            'code' => $code,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
