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

        $canApproveIncomeExpenses = Permission::can('income_expenses.approve');

        $stats = [
            'users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'roles' => (int) Database::pdo()->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
            'active_users' => (int) Database::pdo()->query('SELECT COUNT(*) FROM users WHERE status = "active"')->fetchColumn(),
            'logs' => (int) Database::pdo()->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn(),
            'pending_income_expenses' => $this->pendingIncomeExpenseCount($canApproveIncomeExpenses),
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
            'pendingApprovals' => $this->pendingIncomeExpenseApprovals($canApproveIncomeExpenses),
            'canApproveIncomeExpenses' => $canApproveIncomeExpenses,
        ]);
    }

    private function pendingIncomeExpenseCount(bool $canApprove): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*)
             FROM approval_requests
             INNER JOIN income_expense_records ON income_expense_records.id = approval_requests.target_id
             WHERE ' . implode(' AND ', $this->pendingApprovalWhere($canApprove))
        );
        $stmt->execute($this->pendingApprovalParams($canApprove));

        return (int) $stmt->fetchColumn();
    }

    private function pendingIncomeExpenseApprovals(bool $canApprove): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT approval_requests.*,
                    requesters.name AS requested_by_name,
                    income_expense_records.subject,
                    income_expense_records.item_type,
                    income_expense_records.amount,
                    income_expense_records.occurred_on,
                    income_expense_records.category_name
             FROM approval_requests
             INNER JOIN income_expense_records ON income_expense_records.id = approval_requests.target_id
             LEFT JOIN users AS requesters ON requesters.id = approval_requests.requested_by
             WHERE ' . implode(' AND ', $this->pendingApprovalWhere($canApprove)) . '
             ORDER BY approval_requests.requested_at DESC, approval_requests.id DESC
             LIMIT 10'
        );
        $stmt->execute($this->pendingApprovalParams($canApprove));

        return $stmt->fetchAll();
    }

    private function pendingApprovalWhere(bool $canApprove): array
    {
        $where = [
            'approval_requests.module = "income_expenses"',
            'approval_requests.target_type = "income_expense_records"',
            'approval_requests.status = "pending"',
        ];

        if (!$canApprove) {
            $where[] = 'approval_requests.requested_by = :user_id';
        }

        return $where;
    }

    private function pendingApprovalParams(bool $canApprove): array
    {
        if ($canApprove) {
            return [];
        }

        return ['user_id' => auth()->user()['id'] ?? 0];
    }
}
