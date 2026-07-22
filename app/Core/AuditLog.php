<?php

namespace App\Core;

final class AuditLog
{
    public static function write(string $action, string $module, ?string $targetType = null, ?int $targetId = null, array $detail = []): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        Database::pdo()->prepare(
            'INSERT INTO audit_logs
             (user_id, action, module, target_type, target_id, ip_address, user_agent, detail_json, created_at)
             VALUES (:user_id, :action, :module, :target_type, :target_id, :ip_address, :user_agent, :detail_json, :created_at)'
        )->execute([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'detail_json' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }
}
