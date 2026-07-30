<?php

namespace App\Modules\Dashboard\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $sources = $this->approvalSources();
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

    private function approvalSources(): array
    {
        return [
            [
                'module' => 'income_expenses',
                'target_type' => 'income_expense_records',
                'label' => '收支紀錄',
                'permission' => 'income_expenses.approve',
                'table' => 'income_expense_records',
                'subject_column' => 'subject',
                'category_column' => 'category_name',
                'date_column' => 'occurred_on',
                'amount_column' => 'amount',
                'type_column' => 'item_type',
                'show_path' => '/income-expenses/',
                'approve_path' => '/income-expenses/%d/approve',
                'reject_path' => '/income-expenses/%d/reject',
            ],
            [
                'module' => 'petty_cash',
                'target_type' => 'petty_cash_entries',
                'label' => '零用金',
                'permission' => 'petty_cash.approve',
                'table' => 'petty_cash_entries',
                'subject_column' => 'item_name',
                'category_column' => 'item_name',
                'date_column' => 'occurred_on',
                'amount_column' => 'amount',
                'type_column' => 'item_type',
                'show_path' => '/petty-cash/',
                'approve_path' => '/petty-cash/%d/approve',
                'reject_path' => '/petty-cash/%d/reject',
            ],
            [
                'module' => 'leave_requests',
                'target_type' => 'leave_requests',
                'label' => '人事請假',
                'permission' => 'leave_requests.approve',
                'table' => 'leave_requests',
                'subject_column' => 'reason',
                'category_column' => 'reason',
                'date_column' => 'start_date',
                'amount_column' => 'total_hours',
                'type_column' => 'status',
                'show_path' => '/leave-requests/',
                'approve_path' => '/leave-requests/%d/approve',
                'reject_path' => '/leave-requests/%d/reject',
            ],
        ];
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
