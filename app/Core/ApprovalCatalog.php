<?php

namespace App\Core;

final class ApprovalCatalog
{
    public static function sources(): array
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
            [
                'module' => 'annual_budgets',
                'target_type' => 'annual_budgets',
                'label' => '年度預算',
                'permission' => 'annual_budgets.approve',
                'table' => 'annual_budgets',
                'subject_column' => 'title',
                'category_column' => 'budget_type',
                'date_column' => 'period_start',
                'amount_column' => 'fiscal_year',
                'type_column' => 'status',
                'show_path' => '/annual-budgets/',
                'approve_path' => '/annual-budgets/%d/approve',
                'reject_path' => '/annual-budgets/%d/reject',
            ],
            [
                'module' => 'work_plans',
                'target_type' => 'work_plans',
                'label' => '工作計畫',
                'permission' => 'work_plans.approve',
                'table' => 'work_plans',
                'subject_column' => 'title',
                'category_column' => 'department',
                'date_column' => 'period_start',
                'amount_column' => 'fiscal_year',
                'type_column' => 'status',
                'show_path' => '/work-plans/',
                'approve_path' => '/work-plans/%d/approve',
                'reject_path' => '/work-plans/%d/reject',
            ],
        ];
    }

    public static function find(string $module): ?array
    {
        foreach (self::sources() as $source) {
            if ($source['module'] === $module) {
                return $source;
            }
        }

        return null;
    }
}
