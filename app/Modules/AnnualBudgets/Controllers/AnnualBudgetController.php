<?php

namespace App\Modules\AnnualBudgets\Controllers;

use App\Core\AuditLog;
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
                    COALESCE(SUM(CASE WHEN annual_budget_items.item_type = "income" THEN annual_budget_items.amount ELSE 0 END), 0) AS income_total,
                    COALESCE(SUM(CASE WHEN annual_budget_items.item_type = "expense" THEN annual_budget_items.amount ELSE 0 END), 0) AS expense_total
             FROM annual_budgets
             LEFT JOIN users ON users.id = annual_budgets.created_by
             LEFT JOIN annual_budget_items ON annual_budget_items.annual_budget_id = annual_budgets.id
             GROUP BY annual_budgets.id, annual_budgets.fiscal_year, annual_budgets.title, annual_budgets.status,
                      annual_budgets.notes, annual_budgets.created_by, annual_budgets.approved_by,
                      annual_budgets.approved_at, annual_budgets.created_at, annual_budgets.updated_at,
                      users.name
             ORDER BY annual_budgets.fiscal_year DESC'
        );

        $this->render('annual-budgets.index', [
            'title' => '年度預算',
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
            'active' => 'annual-budgets',
            'budget' => [
                'fiscal_year' => $year,
                'title' => $year . ' 年度預算',
                'status' => 'draft',
                'notes' => '',
            ],
            'items' => $this->defaultItems(),
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
                'INSERT INTO annual_budgets (fiscal_year, title, status, notes, created_by, created_at, updated_at)
                 VALUES (:fiscal_year, :title, :status, :notes, :created_by, :created_at, :updated_at)'
            )->execute([
                'fiscal_year' => (int) $_POST['fiscal_year'],
                'title' => trim((string) $_POST['title']),
                'status' => $this->statusValue(),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id = (int) Database::pdo()->lastInsertId();
            $this->replaceItems($id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\PDOException $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/annual-budgets/create', $_POST, '年度不可重複，或預算資料格式錯誤');
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
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $items,
            'totals' => $this->totals($items),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved' && !Permission::can('annual_budgets.approve')) {
            flash('error', '已核定預算不可由此帳號修改');
            redirect('/annual-budgets/' . $id);
        }

        $this->render('annual-budgets.edit', [
            'title' => '編輯年度預算',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $this->items((int) $id),
            'action' => '/annual-budgets/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved' && !Permission::can('annual_budgets.approve')) {
            flash('error', '已核定預算不可由此帳號修改');
            redirect('/annual-budgets/' . $id);
        }

        $this->validateBudget('/annual-budgets/' . $id . '/edit');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE annual_budgets
                 SET fiscal_year = :fiscal_year, title = :title, status = :status, notes = :notes, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'fiscal_year' => (int) $_POST['fiscal_year'],
                'title' => trim((string) $_POST['title']),
                'status' => $this->statusValue(),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

            $this->replaceItems((int) $id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\PDOException $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/annual-budgets/' . $id . '/edit', $_POST, '年度不可重複，或預算資料格式錯誤');
        }

        AuditLog::write('update', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已更新');
        redirect('/annual-budgets/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('annual_budgets.approve');

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

    private function validateBudget(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'fiscal_year' => '年度',
            'title' => '預算名稱',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $year = (int) $_POST['fiscal_year'];
        if ($year < 2000 || $year > 2100) {
            $this->backWithInput($path, $_POST, '年度格式不正確');
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
             (annual_budget_id, item_type, category, item_name, amount, sort_order, notes, created_at, updated_at)
             VALUES (:annual_budget_id, :item_type, :category, :item_name, :amount, :sort_order, :notes, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            $category = trim((string) ($item['category'] ?? ''));
            $amount = (float) ($item['amount'] ?? 0);
            $type = ($item['item_type'] ?? '') === 'expense' ? 'expense' : 'income';

            if ($name === '' && $category === '' && $amount == 0.0) {
                continue;
            }

            $stmt->execute([
                'annual_budget_id' => $budgetId,
                'item_type' => $type,
                'category' => $category ?: '未分類',
                'item_name' => $name ?: '未命名項目',
                'amount' => max(0, $amount),
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
            'SELECT * FROM annual_budget_items
             WHERE annual_budget_id = :annual_budget_id
             ORDER BY sort_order, id'
        );
        $stmt->execute(['annual_budget_id' => $budgetId]);

        return $stmt->fetchAll();
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
            ['item_type' => 'income', 'category' => '補助收入', 'item_name' => '', 'amount' => '', 'notes' => ''],
            ['item_type' => 'income', 'category' => '捐款收入', 'item_name' => '', 'amount' => '', 'notes' => ''],
            ['item_type' => 'expense', 'category' => '人事費', 'item_name' => '', 'amount' => '', 'notes' => ''],
            ['item_type' => 'expense', 'category' => '業務費', 'item_name' => '', 'amount' => '', 'notes' => ''],
            ['item_type' => 'expense', 'category' => '行政管理費', 'item_name' => '', 'amount' => '', 'notes' => ''],
        ];
    }
}
