<?php

namespace App\Modules\SystemUpdate\Services;

use App\Core\Database;

final class UpdateLogService
{
    public function latest(int $limit = 10): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT system_update_logs.*, users.name AS user_name
             FROM system_update_logs
             LEFT JOIN users ON users.id = system_update_logs.created_by
             ORDER BY system_update_logs.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function latestSuccessfulDownload(): ?array
    {
        $stmt = Database::pdo()->query(
            'SELECT *
             FROM system_update_logs
             WHERE action = "download"
               AND status = "success"
               AND package_path IS NOT NULL
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(string $action, string $status, array $payload = []): void
    {
        Database::pdo()->prepare(
            'INSERT INTO system_update_logs
             (action, status, version_from, version_to, package_path, package_sha256, message, created_by, created_at)
             VALUES (:action, :status, :version_from, :version_to, :package_path, :package_sha256, :message, :created_by, :created_at)'
        )->execute([
            'action' => $action,
            'status' => $status,
            'version_from' => $payload['version_from'] ?? config('app.version', ''),
            'version_to' => $payload['version_to'] ?? null,
            'package_path' => $payload['package_path'] ?? null,
            'package_sha256' => $payload['package_sha256'] ?? null,
            'message' => $payload['message'] ?? null,
            'created_by' => $_SESSION['user_id'] ?? null,
            'created_at' => now(),
        ]);
    }
}
