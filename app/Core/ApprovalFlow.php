<?php

namespace App\Core;

use PDO;

final class ApprovalFlow
{
    public static function submit(string $module, string $targetType, int $targetId, ?string $notes = null): void
    {
        Database::pdo()->prepare(
            'INSERT INTO approval_requests
             (module, target_type, target_id, action, status, requested_by, requested_at, request_notes, created_at, updated_at)
             VALUES
             (:module, :target_type, :target_id, "submit", "pending", :requested_by, :requested_at, :request_notes, :created_at, :updated_at)'
        )->execute([
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'requested_by' => auth()->user()['id'] ?? null,
            'requested_at' => now(),
            'request_notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function review(string $module, string $targetType, int $targetId, string $status, ?string $notes = null): void
    {
        $request = self::latestPending($module, $targetType, $targetId);
        if (!$request) {
            self::submit($module, $targetType, $targetId);
            $request = self::latestPending($module, $targetType, $targetId);
        }

        Database::pdo()->prepare(
            'UPDATE approval_requests
             SET status = :status,
                 action = :action,
                 reviewed_by = :reviewed_by,
                 reviewed_at = :reviewed_at,
                 review_notes = :review_notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'status' => $status,
            'action' => $status,
            'reviewed_by' => auth()->user()['id'] ?? null,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'updated_at' => now(),
            'id' => (int) $request['id'],
        ]);
    }

    public static function history(string $module, string $targetType, int $targetId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT approval_requests.*,
                    requesters.name AS requested_by_name,
                    reviewers.name AS reviewed_by_name
             FROM approval_requests
             LEFT JOIN users AS requesters ON requesters.id = approval_requests.requested_by
             LEFT JOIN users AS reviewers ON reviewers.id = approval_requests.reviewed_by
             WHERE approval_requests.module = :module
               AND approval_requests.target_type = :target_type
               AND approval_requests.target_id = :target_id
             ORDER BY approval_requests.created_at DESC, approval_requests.id DESC'
        );
        $stmt->execute([
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);

        return $stmt->fetchAll();
    }

    private static function latestPending(string $module, string $targetType, int $targetId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT *
             FROM approval_requests
             WHERE module = :module
               AND target_type = :target_type
               AND target_id = :target_id
               AND status = "pending"
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        return $request ?: null;
    }
}
