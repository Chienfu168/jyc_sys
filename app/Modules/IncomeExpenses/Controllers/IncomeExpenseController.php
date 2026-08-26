<?php

namespace App\Modules\IncomeExpenses\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\IncomeExpenses\IncomeExpenseReport;
use App\Support\DateScope;
use PDO;

final class IncomeExpenseController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('income_expenses.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $scope = DateScope::normalize($_GET['scope'] ?? null);
        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $type = in_array(($_GET['type'] ?? ''), ['income', 'expense'], true) ? (string) $_GET['type'] : '';

        [$dateWhere, $params] = DateScope::condition('income_expense_records.occurred_on', $scope, $month, $year);
        $where = $dateWhere !== '' ? [$dateWhere] : [];
        if ($type !== '') {
            $where[] = 'income_expense_records.item_type = :type';
            $params['type'] = $type;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT income_expense_records.*,
                    users.name AS created_by_name,
                    bank_accounts.bank_name,
                    bank_accounts.account_no,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status
             FROM income_expense_records
             LEFT JOIN users ON users.id = income_expense_records.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = income_expense_records.bank_account_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = income_expense_records.accounting_voucher_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
             ORDER BY income_expense_records.occurred_on DESC, income_expense_records.id DESC'
        );
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $this->render('income-expenses.index', [
            'title' => '收支紀錄',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'month' => $month,
            'scope' => $scope,
            'year' => $year,
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
        $summaryRows = $summaryStmt->fetchAll();

        $recordsStmt = Database::pdo()->prepare(
            "SELECT income_expense_records.*, users.name AS created_by_name
             FROM income_expense_records
             LEFT JOIN users ON users.id = income_expense_records.created_by
             WHERE {$where}
             ORDER BY income_expense_records.occurred_on, income_expense_records.id"
        );
        $recordsStmt->execute($params);
        $records = $recordsStmt->fetchAll();

        $totals = $this->totals($records);

        $this->render('income-expenses.report', [
            'title' => '收支統計表',
            'section' => '財務會計',
            'active' => 'income-expenses',
            'year' => $year,
            'month' => $month,
            'summary' => IncomeExpenseReport::summaryWithRatios($summaryRows, $totals['income'], $totals['expense']),
            'records' => $records,
            'totals' => $totals,
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
                'project_id' => '',
                'project_name' => '',
                'receipt_no' => '',
                'receipt_status' => 'pending',
                'status' => 'draft',
                'approval_status' => 'draft',
                'notes' => '',
            ],
            'categories' => $this->categories(),
            'bankAccounts' => $this->bankAccounts(),
            'projects' => $this->projects(),
            'action' => '/income-expenses',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('income_expenses.manage');
        $this->validateRecord('/income-expenses/create');

        Database::pdo()->prepare(
            'INSERT INTO income_expense_records
             (occurred_on, item_type, category_id, category_name, subject, amount, counterparty, counterparty_tax_id, payment_method, bank_account_id, project_id, project_name, receipt_no, receipt_status, notes, status, approval_status, created_by, created_at, updated_at)
             VALUES
             (:occurred_on, :item_type, :category_id, :category_name, :subject, :amount, :counterparty, :counterparty_tax_id, :payment_method, :bank_account_id, :project_id, :project_name, :receipt_no, :receipt_status, :notes, :status, :approval_status, :created_by, :created_at, :updated_at)'
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
            'paymentReceipt' => $this->paymentReceiptForSource('income_expense_records', (int) $id),
            'approvalHistory' => ApprovalFlow::history('income_expenses', 'income_expense_records', (int) $id),
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
            'projects' => $this->projects(),
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
                 project_id = :project_id,
                 project_name = :project_name,
                 receipt_no = :receipt_no,
                 receipt_status = :receipt_status,
                 notes = :notes,
                 status = :status,
                 approval_status = :approval_status,
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

    public function submit(string $id): void
    {
        $this->requirePermission('income_expenses.manage');
        $record = $this->findRecord((int) $id);

        if ($record['status'] === 'voided') {
            flash('error', '作廢紀錄不可送審。');
            redirect('/income-expenses/' . $id);
        }

        ApprovalFlow::submit('income_expenses', 'income_expense_records', (int) $record['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE income_expense_records
             SET status = "draft",
                 approval_status = "submitted",
                 submitted_at = :submitted_at,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'submitted_at' => now(),
            'updated_at' => now(),
            'id' => (int) $record['id'],
        ]);

        AuditLog::write('submit', 'income_expenses', 'income_expense_records', (int) $record['id']);
        flash('success', '收支紀錄已送審。');
        redirect('/income-expenses/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('income_expenses.approve');
        $this->review((int) $id, 'approved', 'confirmed', '收支紀錄已核准。');
    }

    public function reject(string $id): void
    {
        $this->requirePermission('income_expenses.approve');
        $this->review((int) $id, 'rejected', 'draft', '收支紀錄已退回。');
    }

    public function destroy(string $id): void
    {
        $record = $this->findRecord((int) $id);
        $this->requireManageOrOwner('income_expenses.delete', $record['created_by'] ?? null);

        if (!empty($record['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的收支紀錄不可直接刪除，請先處理相關傳票。');
            redirect('/income-expenses/' . $id);
        }

        Database::pdo()->prepare('DELETE FROM income_expense_records WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'income_expenses', 'income_expense_records', (int) $id, [
            'summary' => $record['summary'] ?? null,
            'amount' => $record['amount'] ?? null,
        ]);
        flash('success', '收支紀錄已刪除。');
        redirect('/income-expenses');
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

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $record = $this->findRecord((int) $id);

        if ($record['status'] !== 'confirmed' || ($record['approval_status'] ?? '') !== 'approved') {
            flash('error', '只有已確認的收支紀錄可以拋轉會計傳票。');
            redirect('/income-expenses/' . $id);
        }
        if (!empty($record['accounting_voucher_id'])) {
            flash('error', '此收支紀錄已建立會計傳票。');
            redirect('/accounting/vouchers/' . $record['accounting_voucher_id']);
        }

        $cashAccount = $this->accountByCode('1100');
        $targetAccount = $this->accountByCode($this->accountCodeForRecord($record));
        if (!$cashAccount || !$targetAccount) {
            flash('error', '找不到自動拋轉所需會計科目，請先確認 1100 與收支類別對應科目已建立。');
            redirect('/income-expenses/' . $id);
        }

        $amount = round((float) $record['amount'], 2);
        $summary = trim(($record['item_type'] === 'income' ? '收入：' : '支出：') . $record['subject']);
        $voucherNo = $this->nextVoucherNo((string) $record['occurred_on']);
        $lines = $record['item_type'] === 'income'
            ? [
                ['account_id' => (int) $cashAccount['id'], 'description' => $summary, 'debit' => $amount, 'credit' => 0],
                ['account_id' => (int) $targetAccount['id'], 'description' => $summary, 'debit' => 0, 'credit' => $amount],
            ]
            : [
                ['account_id' => (int) $targetAccount['id'], 'description' => $summary, 'debit' => $amount, 'credit' => 0],
                ['account_id' => (int) $cashAccount['id'], 'description' => $summary, 'debit' => 0, 'credit' => $amount],
            ];

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO accounting_vouchers
                 (voucher_no, voucher_date, source_type, source_id, summary, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:voucher_no, :voucher_date, "income_expense_records", :source_id, :summary, "draft", :notes, :created_by, :created_at, :updated_at)'
            )->execute([
                'voucher_no' => $voucherNo,
                'voucher_date' => $record['occurred_on'],
                'source_id' => (int) $record['id'],
                'summary' => $summary,
                'notes' => trim('由收支紀錄自動拋轉' . "\n" . ($record['notes'] ?? '')),
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $voucherId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO accounting_voucher_lines
                 (voucher_id, account_id, description, debit, credit, sort_order, created_at, updated_at)
                 VALUES
                 (:voucher_id, :account_id, :description, :debit, :credit, :sort_order, :created_at, :updated_at)'
            );
            foreach ($lines as $index => $line) {
                $stmt->execute($line + [
                    'voucher_id' => $voucherId,
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $pdo->prepare(
                'UPDATE income_expense_records
                 SET accounting_voucher_id = :accounting_voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'accounting_voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $record['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/income-expenses/' . $id);
        }

        AuditLog::write('create_voucher', 'income_expenses', 'accounting_vouchers', $voucherId, [
            'source' => 'income_expense_records',
            'source_id' => (int) $record['id'],
        ]);
        flash('success', '已建立草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
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
        $status = $this->statusValue();

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
            'project_id' => $this->projectId(),
            'project_name' => trim((string) ($_POST['project_name'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'receipt_status' => $this->receiptStatusValue(),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'status' => $status,
            'approval_status' => $status === 'confirmed' ? 'approved' : 'draft',
        ];
    }

    private function review(int $id, string $approvalStatus, string $recordStatus, string $message): void
    {
        $record = $this->findRecord($id);
        if ($record['status'] === 'voided') {
            flash('error', '作廢紀錄不可簽核。');
            redirect('/income-expenses/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        ApprovalFlow::review('income_expenses', 'income_expense_records', $id, $approvalStatus, $notes);
        Database::pdo()->prepare(
            'UPDATE income_expense_records
             SET status = :status,
                 approval_status = :approval_status,
                 reviewed_by = :reviewed_by,
                 reviewed_at = :reviewed_at,
                 review_notes = :review_notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'status' => $recordStatus,
            'approval_status' => $approvalStatus,
            'reviewed_by' => auth()->user()['id'] ?? null,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'updated_at' => now(),
            'id' => $id,
        ]);

        AuditLog::write($approvalStatus, 'income_expenses', 'income_expense_records', $id);
        flash('success', $message);
        redirect('/income-expenses/' . $id);
    }

    private function findRecord(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT income_expense_records.*,
                    users.name AS created_by_name,
                    bank_accounts.bank_name,
                    bank_accounts.account_no,
                    projects.name AS linked_project_name,
                    projects.project_code,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status
             FROM income_expense_records
             LEFT JOIN users ON users.id = income_expense_records.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = income_expense_records.bank_account_id
             LEFT JOIN projects ON projects.id = income_expense_records.project_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = income_expense_records.accounting_voucher_id
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

    private function paymentReceiptForSource(string $sourceType, int $sourceId): ?array
    {
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT id, receipt_no, status
                 FROM payment_receipts
                 WHERE source_type = :source_type
                   AND source_id = :source_id
                   AND status != "voided"
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $stmt->execute([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

            return $receipt ?: null;
        } catch (\Throwable) {
            return null;
        }
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
        return \App\Core\Lookups::activeBankAccounts();
    }

    private function projects(): array
    {
        try {
            return Database::pdo()->query(
                'SELECT id, project_code, name
                 FROM projects
                 WHERE status IN ("planning", "active")
                 ORDER BY start_date DESC, project_code, name'
            )->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    private function totals(array $records): array
    {
        return IncomeExpenseReport::totals($records);
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
        $status = (string) ($_POST['status'] ?? 'draft');
        if ($status === 'confirmed') {
            return \App\Core\Permission::can('income_expenses.approve') ? 'confirmed' : 'draft';
        }

        return in_array($status, ['draft', 'voided'], true) ? $status : 'draft';
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

    private function projectId(): ?int
    {
        $id = (int) ($_POST['project_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }

    private function accountByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, code, name
             FROM accounting_accounts
             WHERE code = :code AND status = "active"
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function accountCodeForRecord(array $record): string
    {
        $category = (string) $record['category_name'];
        if ($record['item_type'] === 'income') {
            if (str_contains($category, '補助')) {
                return '4200';
            }
            if (str_contains($category, '利息')) {
                return '4300';
            }
            return '4100';
        }

        if (str_contains($category, '人事')) {
            return '5100';
        }
        if (str_contains($category, '業務')) {
            return '5200';
        }
        if (str_contains($category, '行政')) {
            return '5300';
        }
        if (str_contains($category, '講師')) {
            return '5400';
        }
        if (str_contains($category, '差旅')) {
            return '5500';
        }
        return '5900';
    }

    private function nextVoucherNo(string $date): string
    {
        $prefix = 'V' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_vouchers WHERE voucher_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }
}
