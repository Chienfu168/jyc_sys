<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\ApprovalCatalog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $sources = ApprovalCatalog::sources();
        $pendingApprovals = $this->pendingApprovals($sources);

        $stats = [
            'users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'roles' => (int) Database::pdo()->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
            'active_users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
            'logs' => (int) Database::pdo()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
            'pending_approvals' => count($pendingApprovals),
        ];

        $stmt = Database::pdo()->query(
            'SELECT audit_logs.*, users.name AS user_name
             FROM audit_logs
             LEFT JOIN users ON users.id = audit_logs.user_id
             ORDER BY audit_logs.created_at DESC
             LIMIT 8'
        );

        $this->render('dashboard.index', [
            'title' => '工作台',
            'section' => '主功能',
            'active' => 'dashboard',
            'stats' => $stats,
            'logs' => $stmt->fetchAll(),
            'pendingApprovals' => $pendingApprovals,
            'canApproveAny' => $this->canApproveAny($sources),
        ]);
    }

    private function pendingApprovals(array $sources): array
    {
        $rows = [];
        foreach ($sources as $source) {
            $source['can_approve'] = Permission::can($source['permission']);
            array_push($rows, ...$this->pendingApprovalsForSource($source));
        }

        usort($rows, static function (array $a, array $b): int {
            return strcmp((string) ($b['requested_at'] ?? ''), (string) ($a['requested_at'] ?? ''));
        });

        return array_slice($rows, 0, 12);
    }

    private function canApproveAny(array $sources): bool
    {
        foreach ($sources as $source) {
            if (Permission::can($source['permission'])) {
                return true;
            }
        }

        return false;
    }

    private function pendingApprovalsForSource(array $source): array
    {
        $where = [
            'approval_requests.module = :module',
            'approval_requests.target_type = :target_type',
            'approval_requests.status = "pending"',
        ];
        $params = [
            'module' => $source['module'],
            'target_type' => $source['target_type'],
        ];

        if (!$source['can_approve']) {
            $where[] = 'approval_requests.requested_by = :user_id';
            $params['user_id'] = auth()->user()['id'] ?? 0;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT approval_requests.*,
                    requesters.name AS requested_by_name,
                    target.' . $source['subject_column'] . ' AS subject,
                    target.' . $source['category_column'] . ' AS category_name,
                    target.' . $source['date_column'] . ' AS occurred_on,
                    target.' . $source['amount_column'] . ' AS amount,
                    target.' . $source['type_column'] . ' AS item_type
             FROM approval_requests
             INNER JOIN ' . $source['table'] . ' AS target ON target.id = approval_requests.target_id
             LEFT JOIN users AS requesters ON requesters.id = approval_requests.requested_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY approval_requests.requested_at DESC, approval_requests.id DESC
             LIMIT 12'
        );
        $stmt->execute($params);

        return array_map(static function (array $row) use ($source): array {
            $targetId = (int) $row['target_id'];
            $row['source_label'] = $source['label'];
            $row['can_approve'] = $source['can_approve'];
            $row['show_url'] = $source['show_path'] . $targetId;
            $row['approve_url'] = sprintf($source['approve_path'], $targetId);
            $row['reject_url'] = sprintf($source['reject_path'], $targetId);

            return $row;
        }, $stmt->fetchAll());
    }
}
