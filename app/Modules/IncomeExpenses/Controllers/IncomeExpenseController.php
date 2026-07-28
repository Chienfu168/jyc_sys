<?php

namespace App\Modules\IncomeExpenses\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class IncomeExpenseController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('income_expenses.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $type = in_array(($_GET['type'] ?? ''), ['income', 'expense'], true) ? (string) $_GET['type'] : '';

        $where = ['DATE_FORMAT(income_expense_records.occurred_on, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($type !== '') {
            $where[] = 'income_expense_records.item_type = :type';
            $params['type'] = $type;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT income_expense_records.*, users.name AS created_by_name, bank_accounts.bank_name, bank_accounts.account_no
             FROM income_expense_records
             LEFT JOIN users ON users.id = income_expense_records.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = income_expense_records.bank_account_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY income_expense_records.occurred_on DESC, income_expense_records.id DESC'
        );
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $this->render('income-expenses.index', [
            'title' => '收支紀錄',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'month' => $month,
            'type' => $type,
            'records' => $records,
            'totals' => $this->totals($records),
        ]);
    }

    public function report(): void
    {
        $this->requirePermission('income_expenses.view');

        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $month = preg_match('/^(0[1-9]|1[0-2])$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : '';

        [$where, $params] = $this->dateScope($year, $month);

        $summaryStmt = Database::pdo()->prepare(
            "SELECT item_type, category_name, COUNT(*) AS record_count, SUM(amount) AS subtotal
             FROM income_expense_records
             WHERE {$where} AND status != 'voided'
             GROUP BY item_type, category_name
             ORDER BY item_type, subtotal DESC, category_name"
        );
        $summaryStmt->execute($params);
        $summary = $summaryStmt->fetchAll();

        $recordsStmt = Database::pdo()->prepare(
            "SELECT income_expense_records.*, users.name AS created_by_name
             FROM income_expense_records
             LEFT JOIN users ON users.id = income_expense_records.created_by
             WHERE {$where}
             ORDER BY income_expense_records.occurred_on, income_expense_records.id"
        );
        $recordsStmt->execute($params);
        $records = $recordsStmt->fetchAll();

        $this->render('income-expenses.report', [
            'title' => '收支統計表',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'year' => $year,
            'month' => $month,
            'summary' => $summary,
            'records' => $records,
            'totals' => $this->totals(array_values(array_filter($records, static fn (array $record): bool => $record['status'] !== 'voided'))),
            'profile' => foundation_profile(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('income_expenses.manage');

        $this->render('income-expenses.create', [
            'title' => '新增收支紀錄',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'record' => [
                'occurred_on' => date('Y-m-d'),
                'item_type' => 'expense',
                'category_id' => '',
                'category_name' => '',
                'subject' => '',
                'amount' => '',
                'counterparty' => '',
                'counterparty_tax_id' => '',
                'payment_method' => '',
                'bank_account_id' => '',
                'project_name' => '',
                'receipt_no' => '',
                'receipt_status' => 'pending',
                'status' => 'confirmed',
                'notes' => '',
            ],
            'categories' => $this->categories(),
            'bankAccounts' => $this->bankAccounts(),
            'action' => '/income-expenses',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('income_expenses.manage');
        $this->validateRecord('/income-expenses/create');

        Database::pdo()->prepare(
            'INSERT INTO income_expense_records
             (occurred_on, item_type, category_id, category_name, subject, amount, counterparty, counterparty_tax_id, payment_method, bank_account_id, project_name, receipt_no, receipt_status, notes, status, created_by, created_at, updated_at)
             VALUES
             (:occurred_on, :item_type, :category_id, :category_name, :subject, :amount, :counterparty, :counterparty_tax_id, :payment_method, :bank_account_id, :project_name, :receipt_no, :receipt_status, :notes, :status, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'income_expenses', 'income_expense_records', $id);
        flash('success', '收支紀錄已建立。');
        redirect('/income-expenses/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('income_expenses.view');

        $this->render('income-expenses.show', [
            'title' => '收支紀錄',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'record' => $this->findRecord((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('income_expenses.manage');

        $this->render('income-expenses.edit', [
            'title' => '編輯收支紀錄',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'record' => $this->findRecord((int) $id),
            'categories' => $this->categories(),
            'bankAccounts' => $this->bankAccounts(),
            'action' => '/income-expenses/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('income_expenses.manage');
        $this->findRecord((int) $id);
        $this->validateRecord('/income-expenses/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE income_expense_records
             SET occurred_on = :occurred_on,
                 item_type = :item_type,
                 category_id = :category_id,
                 category_name = :category_name,
                 subject = :subject,
                 amount = :amount,
                 counterparty = :counterparty,
                 counterparty_tax_id = :counterparty_tax_id,
                 payment_method = :payment_method,
                 bank_account_id = :bank_account_id,
                 project_name = :project_name,
                 receipt_no = :receipt_no,
                 receipt_status = :receipt_status,
                 notes = :notes,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'income_expenses', 'income_expense_records', (int) $id);
        flash('success', '收支紀錄已更新。');
        redirect('/income-expenses/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('income_expenses.manage');
        $this->findRecord((int) $id);

        Database::pdo()->prepare(
            'UPDATE income_expense_records SET status = "voided", receipt_status = "voided", updated_at = :updated_at WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('void', 'income_expenses', 'income_expense_records', (int) $id);
        flash('success', '收支紀錄已作廢。');
        redirect('/income-expenses/' . $id);
    }

    private function validateRecord(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'occurred_on' => '日期',
            'item_type' => '類型',
            'subject' => '摘要',
            'amount' => '金額',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['occurred_on'])) {
            $this->backWithInput($path, $_POST, '日期格式不正確。');
        }

        if (!in_array($_POST['item_type'] ?? '', ['income', 'expense'], true)) {
            $this->backWithInput($path, $_POST, '類型不正確。');
        }

        if ($this->amountValue() <= 0) {
            $this->backWithInput($path, $_POST, '金額必須大於 0。');
        }
    }

    private function payload(): array
    {
        $category = $this->selectedCategory();
        $categoryName = trim((string) ($_POST['category_name'] ?? ''));
        if ($category && $categoryName === '') {
            $categoryName = $category['name'];
        }

        return [
            'occurred_on' => $_POST['occurred_on'],
            'item_type' => ($_POST['item_type'] ?? '') === 'income' ? 'income' : 'expense',
            'category_id' => $category['id'] ?? null,
            'category_name' => $categoryName ?: '未分類',
            'subject' => trim((string) $_POST['subject']),
            'amount' => $this->amountValue(),
            'counterparty' => trim((string) ($_POST['counterparty'] ?? '')),
            'counterparty_tax_id' => trim((string) ($_POST['counterparty_tax_id'] ?? '')),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
            'bank_account_id' => $this->bankAccountId(),
            'project_name' => trim((string) ($_POST['project_name'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'receipt_status' => $this->receiptStatusValue(),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'status' => $this->statusValue(),
        ];
    }

    private function findRecord(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT income_expense_records.*, users.name AS created_by_name, bank_accounts.bank_name, bank_accounts.account_no
             FROM income_expense_records
             LEFT JOIN users ON users.id = income_expense_records.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = income_expense_records.bank_account_id
             WHERE income_expense_records.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到收支紀錄']);
            exit;
        }

        return $record;
    }

    private function categories(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM income_expense_categories
             WHERE status = "active"
             ORDER BY item_type, sort_order, name'
        )->fetchAll();
    }

    private function selectedCategory(): ?array
    {
        $id = (int) ($_POST['category_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT * FROM income_expense_categories WHERE id = :id AND status = "active" LIMIT 1');
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        return $category ?: null;
    }

    private function bankAccounts(): array
    {
        try {
            return Database::pdo()->query('SELECT * FROM bank_accounts WHERE status = "active" ORDER BY bank_name, account_no')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    private function totals(array $records): array
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($records as $record) {
            if ($record['status'] === 'voided') {
                continue;
            }

            if ($record['item_type'] === 'income') {
                $income += (float) $record['amount'];
            } else {
                $expense += (float) $record['amount'];
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    private function dateScope(int $year, string $month): array
    {
        if ($month !== '') {
            return [
                'DATE_FORMAT(occurred_on, "%Y-%m") = :report_month',
                ['report_month' => $year . '-' . $month],
            ];
        }

        return [
            'YEAR(occurred_on) = :report_year',
            ['report_year' => $year],
        ];
    }

    private function statusValue(): string
    {
        $status = (string) ($_POST['status'] ?? 'confirmed');
        return in_array($status, ['draft', 'confirmed', 'voided'], true) ? $status : 'confirmed';
    }

    private function receiptStatusValue(): string
    {
        $status = (string) ($_POST['receipt_status'] ?? 'pending');
        return in_array($status, ['not_required', 'pending', 'issued', 'voided'], true) ? $status : 'pending';
    }

    private function bankAccountId(): ?int
    {
        $id = (int) ($_POST['bank_account_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }
}
