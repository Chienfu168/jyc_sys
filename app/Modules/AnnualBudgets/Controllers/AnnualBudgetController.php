<?php

namespace App\Modules\AnnualBudgets\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

final class AnnualBudgetController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('annual_budgets.view');

        $stmt = Database::pdo()->query(
            'SELECT annual_budgets.*,
                    users.name AS created_by_name,
                    COALESCE(budget_totals.income_total, 0) AS income_total,
                    COALESCE(budget_totals.expense_total, 0) AS expense_total
             FROM annual_budgets
             LEFT JOIN users ON users.id = annual_budgets.created_by
             LEFT JOIN (
                SELECT annual_budget_id,
                       SUM(CASE WHEN item_type = "income" THEN amount ELSE 0 END) AS income_total,
                       SUM(CASE WHEN item_type = "expense" THEN amount ELSE 0 END) AS expense_total
                FROM annual_budget_items
                GROUP BY annual_budget_id
             ) AS budget_totals ON budget_totals.annual_budget_id = annual_budgets.id
             ORDER BY annual_budgets.fiscal_year DESC'
        );

        $this->render('annual-budgets.index', [
            'title' => '年度預算',
            'section' => '主要業務',
            'active' => 'annual-budgets',
            'budgets' => $stmt->fetchAll(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('annual_budgets.manage');

        $year = (int) date('Y') + 1;
        $this->render('annual-budgets.create', [
            'title' => '新增年度預算',
            'section' => '主要業務',
            'active' => 'annual-budgets',
            'budget' => [
                'fiscal_year' => $year,
                'budget_type' => 'annual',
                'title' => roc_year($year) . ' 年度預算',
                'period_start' => $year . '-01-01',
                'period_end' => $year . '-12-31',
                'status' => 'draft',
                'notes' => '',
                'purpose' => '',
                'legal_basis' => '',
                'expected_benefit' => '',
                'board_meeting_no' => '',
            ],
            'items' => $this->defaultItems(),
            'accounts' => $this->budgetAccounts(),
            'action' => '/annual-budgets',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('annual_budgets.manage');
        $this->validateBudget('/annual-budgets/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO annual_budgets
                 (fiscal_year, budget_type, title, period_start, period_end, status, notes, purpose, legal_basis, expected_benefit, board_meeting_no, created_by, created_at, updated_at)
                 VALUES
                 (:fiscal_year, :budget_type, :title, :period_start, :period_end, :status, :notes, :purpose, :legal_basis, :expected_benefit, :board_meeting_no, :created_by, :created_at, :updated_at)'
            )->execute([
                'fiscal_year' => normalize_fiscal_year($_POST['fiscal_year']),
                'budget_type' => $this->budgetTypeValue(),
                'title' => trim((string) $_POST['title']),
                'period_start' => $this->dateOrNull('period_start'),
                'period_end' => $this->dateOrNull('period_end'),
                'status' => $this->statusValue(),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'purpose' => trim((string) ($_POST['purpose'] ?? '')),
                'legal_basis' => trim((string) ($_POST['legal_basis'] ?? '')),
                'expected_benefit' => trim((string) ($_POST['expected_benefit'] ?? '')),
                'board_meeting_no' => trim((string) ($_POST['board_meeting_no'] ?? '')),
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id = (int) Database::pdo()->lastInsertId();
            $this->replaceItems($id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\PDOException) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/annual-budgets/create', $_POST, '年度預算儲存失敗，請確認年度沒有重複。');
        }

        if ($this->statusValue() === 'submitted') {
            ApprovalFlow::submit('annual_budgets', 'annual_budgets', $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('create', 'annual_budgets', 'annual_budgets', $id);
        flash('success', '年度預算已建立');
        redirect('/annual-budgets/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('annual_budgets.view');
        $budget = $this->findBudget((int) $id);
        $items = $this->items((int) $id);

        $this->render('annual-budgets.show', [
            'title' => $budget['title'],
            'section' => '主要業務',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $items,
            'totals' => $this->totals($items),
            'approvalHistory' => ApprovalFlow::history('annual_budgets', 'annual_budgets', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function execution(string $id): void
    {
        $this->requirePermission('annual_budgets.view');
        $budget = $this->findBudget((int) $id);
        $start = (string) ($budget['period_start'] ?: $budget['fiscal_year'] . '-01-01');
        $end = (string) ($budget['period_end'] ?: $budget['fiscal_year'] . '-12-31');
        $items = $this->executionItems((int) $id, $start, $end);

        $this->render('annual-budgets.execution', [
            'title' => '預算執行控管',
            'section' => '財務會計',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'startDate' => $start,
            'endDate' => $end,
            'items' => $items,
            'totals' => $this->executionTotals($items),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved' && !Permission::can('annual_budgets.approve')) {
            flash('error', '已核定的預算只有具核定權限者可以修改。');
            redirect('/annual-budgets/' . $id);
        }

        $this->render('annual-budgets.edit', [
            'title' => '編輯年度預算',
            'section' => '主要業務',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $this->items((int) $id),
            'accounts' => $this->budgetAccounts(),
            'action' => '/annual-budgets/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved' && !Permission::can('annual_budgets.approve')) {
            flash('error', '已核定的預算只有具核定權限者可以修改。');
            redirect('/annual-budgets/' . $id);
        }

        $this->validateBudget('/annual-budgets/' . $id . '/edit');
        $status = $this->statusValue();

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE annual_budgets
                 SET fiscal_year = :fiscal_year,
                     budget_type = :budget_type,
                     title = :title,
                     period_start = :period_start,
                     period_end = :period_end,
                     status = :status,
                     notes = :notes,
                     purpose = :purpose,
                     legal_basis = :legal_basis,
                     expected_benefit = :expected_benefit,
                     board_meeting_no = :board_meeting_no,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'fiscal_year' => normalize_fiscal_year($_POST['fiscal_year']),
                'budget_type' => $this->budgetTypeValue(),
                'title' => trim((string) $_POST['title']),
                'period_start' => $this->dateOrNull('period_start'),
                'period_end' => $this->dateOrNull('period_end'),
                'status' => $status,
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'purpose' => trim((string) ($_POST['purpose'] ?? '')),
                'legal_basis' => trim((string) ($_POST['legal_basis'] ?? '')),
                'expected_benefit' => trim((string) ($_POST['expected_benefit'] ?? '')),
                'board_meeting_no' => trim((string) ($_POST['board_meeting_no'] ?? '')),
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

            $this->replaceItems((int) $id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\PDOException) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/annual-budgets/' . $id . '/edit', $_POST, '年度預算更新失敗，請確認年度沒有重複。');
        }

        if ($status === 'submitted' && $budget['status'] !== 'submitted') {
            ApprovalFlow::submit('annual_budgets', 'annual_budgets', (int) $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('update', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已更新');
        redirect('/annual-budgets/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved') {
            flash('error', '已核准的年度預算不可重新送審。');
            redirect('/annual-budgets/' . $id);
        }

        ApprovalFlow::submit('annual_budgets', 'annual_budgets', (int) $budget['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE annual_budgets
             SET status = "submitted", updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $budget['id'],
        ]);

        AuditLog::write('submit', 'annual_budgets', 'annual_budgets', (int) $budget['id']);
        flash('success', '年度預算已送審。');
        redirect('/annual-budgets/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('annual_budgets.approve');
        ApprovalFlow::review('annual_budgets', 'annual_budgets', (int) $id, 'approved', trim((string) ($_POST['review_notes'] ?? '')));

        Database::pdo()->prepare(
            'UPDATE annual_budgets
             SET status = "approved", approved_by = :approved_by, approved_at = :approved_at, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'approved_by' => auth()->user()['id'] ?? null,
            'approved_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('approve', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已核定');
        redirect('/annual-budgets/' . $id);
    }

    public function reject(string $id): void
    {
        $this->requirePermission('annual_budgets.approve');
        ApprovalFlow::review('annual_budgets', 'annual_budgets', (int) $id, 'rejected', trim((string) ($_POST['review_notes'] ?? '')));

        Database::pdo()->prepare(
            'UPDATE annual_budgets
             SET status = "draft", approved_by = NULL, approved_at = NULL, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('reject', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已退回。');
        redirect('/annual-budgets/' . $id);
    }

    private function validateBudget(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'fiscal_year' => '年度',
            'title' => '預算名稱',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $year = normalize_fiscal_year($_POST['fiscal_year']);
        if ($year < 1912 || $year > 2100) {
            $this->backWithInput($path, $_POST, '年度格式不正確。');
        }
    }

    private function statusValue(): string
    {
        $status = (string) ($_POST['status'] ?? 'draft');
        if ($status === 'approved' && Permission::can('annual_budgets.approve')) {
            return 'approved';
        }

        return in_array($status, ['draft', 'submitted'], true) ? $status : 'draft';
    }

    private function replaceItems(int $budgetId, array $items): void
    {
        Database::pdo()->prepare('DELETE FROM annual_budget_items WHERE annual_budget_id = :annual_budget_id')
            ->execute(['annual_budget_id' => $budgetId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO annual_budget_items
             (annual_budget_id, item_type, account_id, category, item_name, description, unit, quantity, unit_price, amount, funding_source, sort_order, notes, created_at, updated_at)
             VALUES (:annual_budget_id, :item_type, :account_id, :category, :item_name, :description, :unit, :quantity, :unit_price, :amount, :funding_source, :sort_order, :notes, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            $category = trim((string) ($item['category'] ?? ''));
            $quantity = max(0, round((float) ($item['quantity'] ?? 1), 2));
            $unitPrice = max(0, round((float) ($item['unit_price'] ?? 0), 2));
            $amount = round((float) ($item['amount'] ?? 0), 2);
            if ($amount <= 0 && $quantity > 0 && $unitPrice > 0) {
                $amount = $quantity * $unitPrice;
            }
            $type = ($item['item_type'] ?? '') === 'expense' ? 'expense' : 'income';

            if ($name === '' && $category === '' && $amount == 0.0) {
                continue;
            }

            $stmt->execute([
                'annual_budget_id' => $budgetId,
                'item_type' => $type,
                'account_id' => $this->nullablePositiveInt($item['account_id'] ?? null),
                'category' => $category ?: '未分類',
                'item_name' => $name ?: '未命名項目',
                'description' => trim((string) ($item['description'] ?? '')),
                'unit' => trim((string) ($item['unit'] ?? '')),
                'quantity' => $quantity ?: 1,
                'unit_price' => $unitPrice,
                'amount' => max(0, $amount),
                'funding_source' => trim((string) ($item['funding_source'] ?? '')),
                'sort_order' => $sort++,
                'notes' => trim((string) ($item['notes'] ?? '')),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findBudget(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT annual_budgets.*, users.name AS created_by_name, approvers.name AS approved_by_name
             FROM annual_budgets
             LEFT JOIN users ON users.id = annual_budgets.created_by
             LEFT JOIN users AS approvers ON approvers.id = annual_budgets.approved_by
             WHERE annual_budgets.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到年度預算']);
            exit;
        }

        return $budget;
    }

    private function items(int $budgetId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT annual_budget_items.*,
                    accounting_accounts.code AS account_code,
                    accounting_accounts.name AS account_name
             FROM annual_budget_items
             LEFT JOIN accounting_accounts ON accounting_accounts.id = annual_budget_items.account_id
             WHERE annual_budget_id = :annual_budget_id
             ORDER BY sort_order, id'
        );
        $stmt->execute(['annual_budget_id' => $budgetId]);

        return $stmt->fetchAll();
    }

    private function executionItems(int $budgetId, string $start, string $end): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT annual_budget_items.*,
                    accounting_accounts.code AS account_code,
                    accounting_accounts.name AS account_name,
                    accounting_accounts.normal_balance,
                    COALESCE(actuals.debit_total, 0) AS debit_total,
                    COALESCE(actuals.credit_total, 0) AS credit_total
             FROM annual_budget_items
             LEFT JOIN accounting_accounts ON accounting_accounts.id = annual_budget_items.account_id
             LEFT JOIN (
                SELECT accounting_voucher_lines.account_id,
                       SUM(accounting_voucher_lines.debit) AS debit_total,
                       SUM(accounting_voucher_lines.credit) AS credit_total
                FROM accounting_voucher_lines
                INNER JOIN accounting_vouchers ON accounting_vouchers.id = accounting_voucher_lines.voucher_id
                WHERE accounting_vouchers.status = "posted"
                  AND accounting_vouchers.voucher_date BETWEEN :start_date AND :end_date
                GROUP BY accounting_voucher_lines.account_id
             ) AS actuals ON actuals.account_id = annual_budget_items.account_id
             WHERE annual_budget_items.annual_budget_id = :annual_budget_id
             ORDER BY annual_budget_items.item_type, annual_budget_items.sort_order, annual_budget_items.id'
        );
        $stmt->execute([
            'start_date' => $start,
            'end_date' => $end,
            'annual_budget_id' => $budgetId,
        ]);

        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $actual = 0.0;
            if (!empty($item['account_id'])) {
                $actual = $item['normal_balance'] === 'credit'
                    ? (float) $item['credit_total'] - (float) $item['debit_total']
                    : (float) $item['debit_total'] - (float) $item['credit_total'];
            }

            $budget = (float) $item['amount'];
            $item['actual_amount'] = max(0, $actual);
            $item['remaining_amount'] = $budget - (float) $item['actual_amount'];
            $item['execution_rate'] = $budget > 0 ? round(((float) $item['actual_amount'] / $budget) * 100, 2) : 0;
        }
        unset($item);

        return $items;
    }

    private function executionTotals(array $items): array
    {
        $totals = [
            'income_budget' => 0.0,
            'income_actual' => 0.0,
            'expense_budget' => 0.0,
            'expense_actual' => 0.0,
            'unmapped' => 0,
            'over_budget' => 0,
        ];

        foreach ($items as $item) {
            $type = $item['item_type'] === 'income' ? 'income' : 'expense';
            $totals[$type . '_budget'] += (float) $item['amount'];
            $totals[$type . '_actual'] += (float) $item['actual_amount'];
            if (empty($item['account_id'])) {
                $totals['unmapped']++;
            }
            if ($item['item_type'] === 'expense' && (float) $item['remaining_amount'] < 0) {
                $totals['over_budget']++;
            }
        }

        $totals['income_rate'] = $totals['income_budget'] > 0 ? round(($totals['income_actual'] / $totals['income_budget']) * 100, 2) : 0;
        $totals['expense_rate'] = $totals['expense_budget'] > 0 ? round(($totals['expense_actual'] / $totals['expense_budget']) * 100, 2) : 0;
        $totals['budget_balance'] = $totals['income_budget'] - $totals['expense_budget'];
        $totals['actual_balance'] = $totals['income_actual'] - $totals['expense_actual'];

        return $totals;
    }

    private function budgetAccounts(): array
    {
        return Database::pdo()->query(
            'SELECT id, code, name, account_type
             FROM accounting_accounts
             WHERE status = "active"
               AND account_type IN ("income", "expense")
             ORDER BY account_type, sort_order, code'
        )->fetchAll();
    }

    private function totals(array $items): array
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($items as $item) {
            if ($item['item_type'] === 'income') {
                $income += (float) $item['amount'];
            } else {
                $expense += (float) $item['amount'];
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    private function defaultItems(): array
    {
        return [
            ['item_type' => 'income', 'category' => '捐款收入', 'item_name' => '', 'description' => '', 'unit' => '', 'quantity' => 1, 'unit_price' => '', 'amount' => '', 'funding_source' => '民間捐款', 'notes' => ''],
            ['item_type' => 'income', 'category' => '補助收入', 'item_name' => '', 'description' => '', 'unit' => '', 'quantity' => 1, 'unit_price' => '', 'amount' => '', 'funding_source' => '政府補助', 'notes' => ''],
            ['item_type' => 'expense', 'category' => '人事費', 'item_name' => '', 'description' => '', 'unit' => '月', 'quantity' => 1, 'unit_price' => '', 'amount' => '', 'funding_source' => '', 'notes' => ''],
            ['item_type' => 'expense', 'category' => '業務費', 'item_name' => '', 'description' => '', 'unit' => '式', 'quantity' => 1, 'unit_price' => '', 'amount' => '', 'funding_source' => '', 'notes' => ''],
            ['item_type' => 'expense', 'category' => '行政管理費', 'item_name' => '', 'description' => '', 'unit' => '式', 'quantity' => 1, 'unit_price' => '', 'amount' => '', 'funding_source' => '', 'notes' => ''],
        ];
    }

    private function budgetTypeValue(): string
    {
        $type = (string) ($_POST['budget_type'] ?? 'annual');
        return in_array($type, ['annual', 'project', 'grant'], true) ? $type : 'annual';
    }

    private function dateOrNull(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }
}
