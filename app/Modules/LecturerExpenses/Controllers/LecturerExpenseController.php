<?php

namespace App\Modules\LecturerExpenses\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\LecturerExpenses\LecturerFeeCalculator;
use App\Support\DateScope;
use PDO;

final class LecturerExpenseController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('lecturer_expenses.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $scope = DateScope::normalize($_GET['scope'] ?? null);
        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $status = in_array(($_GET['status'] ?? ''), ['pending', 'paid', 'voided'], true) ? (string) $_GET['status'] : '';

        [$dateWhere, $params] = DateScope::condition('lecturer_expenses.expense_date', $scope, $month, $year);
        $where = $dateWhere !== '' ? [$dateWhere] : [];
        if ($status !== '') {
            $where[] = 'lecturer_expenses.payment_status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT lecturer_expenses.*, lecturers.name AS lecturer_name, lecturers.display_name,
                    bank_accounts.bank_name, bank_accounts.account_no,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM lecturer_expenses
             INNER JOIN lecturers ON lecturers.id = lecturer_expenses.lecturer_id
             LEFT JOIN bank_accounts ON bank_accounts.id = lecturer_expenses.bank_account_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = lecturer_expenses.accounting_voucher_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
             ORDER BY lecturer_expenses.expense_date DESC, lecturer_expenses.id DESC'
        );
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        $this->render('lecturer-expenses.index', [
            'title' => '講師支出費用',
            'section' => '財務會計',
            'active' => 'lecturer-expenses',
            'month' => $month,
            'scope' => $scope,
            'year' => $year,
            'status' => $status,
            'expenses' => $expenses,
            'totals' => $this->totals($expenses),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('lecturer_expenses.manage');

        $lecturer = $this->selectedLecturer((int) ($_GET['lecturer_id'] ?? 0));

        $this->render('lecturer-expenses.create', [
            'title' => '新增講師費用',
            'section' => '財務會計',
            'active' => 'lecturer-expenses',
            'expense' => [
                'lecturer_id' => $lecturer['id'] ?? '',
                'expense_date' => date('Y-m-d'),
                'service_title' => '',
                'service_unit' => '',
                'project_id' => '',
                'project_name' => '',
                'activity_name' => '',
                'hours' => '',
                'hourly_rate' => $lecturer['hourly_rate'] ?? '',
                'transportation_fee' => 0,
                'other_fee' => 0,
                'withholding_tax' => 0,
                'payment_method' => '匯款',
                'payment_status' => 'pending',
                'paid_on' => '',
                'bank_account_id' => '',
                'receipt_no' => '',
                'notes' => '',
            ],
            'lecturers' => $this->lecturers(),
            'bankAccounts' => $this->bankAccounts(),
            'projects' => $this->projects(),
            'action' => '/lecturer-expenses',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('lecturer_expenses.manage');
        $this->validateExpense('/lecturer-expenses/create');

        Database::pdo()->prepare(
            'INSERT INTO lecturer_expenses
             (lecturer_id, expense_date, service_title, service_unit, project_id, project_name, activity_name, hours, hourly_rate, lecture_fee, transportation_fee, other_fee, withholding_tax, gross_total, net_total, payment_method, payment_status, paid_on, bank_account_id, receipt_no, notes, created_by, created_at, updated_at)
             VALUES
             (:lecturer_id, :expense_date, :service_title, :service_unit, :project_id, :project_name, :activity_name, :hours, :hourly_rate, :lecture_fee, :transportation_fee, :other_fee, :withholding_tax, :gross_total, :net_total, :payment_method, :payment_status, :paid_on, :bank_account_id, :receipt_no, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'lecturer_expenses', 'lecturer_expenses', $id);
        flash('success', '講師費用已建立。');
        redirect('/lecturer-expenses/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('lecturer_expenses.view');

        $this->render('lecturer-expenses.show', [
            'title' => '講師費用明細',
            'section' => '財務會計',
            'active' => 'lecturer-expenses',
            'expense' => $this->findExpense((int) $id),
            'paymentReceipt' => $this->paymentReceiptForSource('lecturer_expenses', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('lecturer_expenses.manage');

        $this->render('lecturer-expenses.edit', [
            'title' => '編輯講師費用',
            'section' => '財務會計',
            'active' => 'lecturer-expenses',
            'expense' => $this->findExpense((int) $id),
            'lecturers' => $this->lecturers(),
            'bankAccounts' => $this->bankAccounts(),
            'projects' => $this->projects(),
            'action' => '/lecturer-expenses/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('lecturer_expenses.manage');
        $this->findExpense((int) $id);
        $this->validateExpense('/lecturer-expenses/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE lecturer_expenses
             SET lecturer_id = :lecturer_id,
                 expense_date = :expense_date,
                 service_title = :service_title,
                 service_unit = :service_unit,
                 project_id = :project_id,
                 project_name = :project_name,
                 activity_name = :activity_name,
                 hours = :hours,
                 hourly_rate = :hourly_rate,
                 lecture_fee = :lecture_fee,
                 transportation_fee = :transportation_fee,
                 other_fee = :other_fee,
                 withholding_tax = :withholding_tax,
                 gross_total = :gross_total,
                 net_total = :net_total,
                 payment_method = :payment_method,
                 payment_status = :payment_status,
                 paid_on = :paid_on,
                 bank_account_id = :bank_account_id,
                 receipt_no = :receipt_no,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'lecturer_expenses', 'lecturer_expenses', (int) $id);
        flash('success', '講師費用已更新。');
        redirect('/lecturer-expenses/' . $id);
    }

    public function markPaid(string $id): void
    {
        $this->requirePermission('lecturer_expenses.manage');
        $this->findExpense((int) $id);

        Database::pdo()->prepare(
            'UPDATE lecturer_expenses
             SET payment_status = "paid", paid_on = COALESCE(paid_on, :paid_on), updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'paid_on' => date('Y-m-d'),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('mark_paid', 'lecturer_expenses', 'lecturer_expenses', (int) $id);
        flash('success', '講師費用已標記為已付款。');
        redirect('/lecturer-expenses/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('lecturer_expenses.delete');
        $expense = $this->findExpense((int) $id);

        if (!empty($expense['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的講師支出不可直接刪除，請先處理相關傳票。');
            redirect('/lecturer-expenses/' . $id);
        }

        Database::pdo()->prepare('DELETE FROM lecturer_expenses WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'lecturer_expenses', 'lecturer_expenses', (int) $id, [
            'amount' => $expense['amount'] ?? null,
        ]);
        flash('success', '講師支出費用已刪除。');
        redirect('/lecturer-expenses');
    }

    public function void(string $id): void
    {
        $this->requirePermission('lecturer_expenses.manage');
        $this->findExpense((int) $id);

        Database::pdo()->prepare('UPDATE lecturer_expenses SET payment_status = "voided", updated_at = :updated_at WHERE id = :id')
            ->execute([
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('void', 'lecturer_expenses', 'lecturer_expenses', (int) $id);
        flash('success', '講師費用已作廢。');
        redirect('/lecturer-expenses/' . $id);
    }

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $expense = $this->findExpense((int) $id);

        if ($expense['payment_status'] === 'voided') {
            flash('error', '作廢的講師支出不可建立會計傳票。');
            redirect('/lecturer-expenses/' . $id);
        }

        if (!empty($expense['accounting_voucher_id'])) {
            flash('error', '此講師支出已建立會計傳票。');
            redirect('/accounting/vouchers/' . $expense['accounting_voucher_id']);
        }

        $cashAccount = $this->accountByCode('1100');
        $payableAccount = $this->accountByCode('2100');
        $expenseAccount = $this->accountByCode('5400');

        if (!$cashAccount || !$payableAccount || !$expenseAccount) {
            flash('error', '找不到講師支出拋轉所需會計科目，請先確認 1100、2100、5400 已建立。');
            redirect('/lecturer-expenses/' . $id);
        }

        $grossTotal = round((float) $expense['gross_total'], 2);
        $netTotal = round((float) $expense['net_total'], 2);
        $withholdingTax = round((float) $expense['withholding_tax'], 2);
        if ($grossTotal <= 0) {
            flash('error', '講師支出金額需大於 0 才能建立會計傳票。');
            redirect('/lecturer-expenses/' . $id);
        }

        $voucherDate = $expense['paid_on'] ?: $expense['expense_date'];
        $summary = sprintf('講師支出：%s', $expense['service_title']);
        $voucherId = 0;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO accounting_vouchers
                 (voucher_no, voucher_date, source_type, source_id, summary, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:voucher_no, :voucher_date, :source_type, :source_id, :summary, :status, :notes, :created_by, :created_at, :updated_at)'
            )->execute([
                'voucher_no' => $this->nextVoucherNo($voucherDate),
                'voucher_date' => $voucherDate,
                'source_type' => 'lecturer_expenses',
                'source_id' => (int) $expense['id'],
                'summary' => $summary,
                'status' => 'draft',
                'notes' => trim("由講師支出自動拋轉\n" . (string) ($expense['notes'] ?? '')),
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $voucherId = (int) $pdo->lastInsertId();
            $lineStmt = $pdo->prepare(
                'INSERT INTO accounting_voucher_lines
                 (voucher_id, account_id, description, debit, credit, sort_order, created_at, updated_at)
                 VALUES
                 (:voucher_id, :account_id, :description, :debit, :credit, :sort_order, :created_at, :updated_at)'
            );

            $lineStmt->execute([
                'voucher_id' => $voucherId,
                'account_id' => (int) $expenseAccount['id'],
                'description' => $summary,
                'debit' => $grossTotal,
                'credit' => 0,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($expense['payment_status'] === 'paid' && $netTotal > 0) {
                $lineStmt->execute([
                    'voucher_id' => $voucherId,
                    'account_id' => (int) $cashAccount['id'],
                    'description' => $summary . ' 實付金額',
                    'debit' => 0,
                    'credit' => $netTotal,
                    'sort_order' => 20,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $payableAmount = $expense['payment_status'] === 'paid' ? $withholdingTax : $grossTotal;
            if ($payableAmount > 0) {
                $lineStmt->execute([
                    'voucher_id' => $voucherId,
                    'account_id' => (int) $payableAccount['id'],
                    'description' => $expense['payment_status'] === 'paid' ? $summary . ' 扣繳稅額' : $summary . ' 應付款',
                    'debit' => 0,
                    'credit' => $payableAmount,
                    'sort_order' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $pdo->prepare(
                'UPDATE lecturer_expenses
                 SET accounting_voucher_id = :voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $expense['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/lecturer-expenses/' . $id);
        }

        AuditLog::write('create_voucher', 'lecturer_expenses', 'lecturer_expenses', (int) $expense['id']);
        AuditLog::write('create', 'accounting', 'accounting_vouchers', $voucherId);
        flash('success', '已建立講師支出草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
    }

    private function validateExpense(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'lecturer_id' => '講師',
            'expense_date' => '費用日期',
            'service_title' => '服務內容',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['expense_date'])) {
            $this->backWithInput($path, $_POST, '費用日期格式不正確。');
        }

        if (!$this->selectedLecturer((int) $_POST['lecturer_id'])) {
            $this->backWithInput($path, $_POST, '請選擇有效的講師。');
        }

        foreach (['hours', 'hourly_rate', 'transportation_fee', 'other_fee', 'withholding_tax'] as $key) {
            if ($this->amountValue($key) < 0) {
                $this->backWithInput($path, $_POST, '金額與時數不可小於 0。');
            }
        }
    }

    private function payload(): array
    {
        $hours = $this->amountValue('hours');
        $hourlyRate = $this->amountValue('hourly_rate');
        $lectureFee = LecturerFeeCalculator::lectureFee($hours, $hourlyRate);
        $transportationFee = $this->amountValue('transportation_fee');
        $otherFee = $this->amountValue('other_fee');
        $withholdingTax = $this->amountValue('withholding_tax');
        $grossTotal = LecturerFeeCalculator::grossTotal($lectureFee, $transportationFee, $otherFee);
        $netTotal = LecturerFeeCalculator::netTotal($grossTotal, $withholdingTax);
        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
        $paidOn = trim((string) ($_POST['paid_on'] ?? ''));

        return [
            'lecturer_id' => (int) $_POST['lecturer_id'],
            'expense_date' => (string) $_POST['expense_date'],
            'service_title' => trim((string) $_POST['service_title']),
            'service_unit' => trim((string) ($_POST['service_unit'] ?? '')),
            'project_id' => $this->projectId(),
            'project_name' => trim((string) ($_POST['project_name'] ?? '')),
            'activity_name' => trim((string) ($_POST['activity_name'] ?? '')),
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'lecture_fee' => $lectureFee,
            'transportation_fee' => $transportationFee,
            'other_fee' => $otherFee,
            'withholding_tax' => $withholdingTax,
            'gross_total' => $grossTotal,
            'net_total' => $netTotal,
            'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
            'payment_status' => $this->statusValue(),
            'paid_on' => $paidOn !== '' ? $paidOn : null,
            'bank_account_id' => $bankAccountId > 0 ? $bankAccountId : null,
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findExpense(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT lecturer_expenses.*, lecturers.name AS lecturer_name, lecturers.display_name,
                    lecturers.tax_id, lecturers.bank_name AS lecturer_bank_name,
                    lecturers.bank_branch AS lecturer_bank_branch,
                    lecturers.bank_account_no AS lecturer_bank_account_no,
                    lecturers.bank_account_name AS lecturer_bank_account_name,
                    users.name AS created_by_name,
                    bank_accounts.bank_name, bank_accounts.branch_name, bank_accounts.account_no,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM lecturer_expenses
             INNER JOIN lecturers ON lecturers.id = lecturer_expenses.lecturer_id
             LEFT JOIN users ON users.id = lecturer_expenses.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = lecturer_expenses.bank_account_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = lecturer_expenses.accounting_voucher_id
             WHERE lecturer_expenses.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$expense) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到講師費用']);
            exit;
        }

        return $expense;
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

    private function lecturers(): array
    {
        return Database::pdo()->query(
            'SELECT id, name, display_name, hourly_rate
             FROM lecturers
             ORDER BY status, name'
        )->fetchAll();
    }

    private function selectedLecturer(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT id, name, display_name, hourly_rate FROM lecturers WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);

        return $lecturer ?: null;
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

    private function accountByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, code, name FROM accounting_accounts WHERE code = :code AND status = "active" LIMIT 1');
        $stmt->execute(['code' => $code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function nextVoucherNo(string $date): string
    {
        $prefix = 'V' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_vouchers WHERE voucher_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);

        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function totals(array $expenses): array
    {
        $gross = 0.0;
        $tax = 0.0;
        $net = 0.0;
        foreach ($expenses as $expense) {
            if ($expense['payment_status'] === 'voided') {
                continue;
            }
            $gross += (float) $expense['gross_total'];
            $tax += (float) $expense['withholding_tax'];
            $net += (float) $expense['net_total'];
        }

        return [
            'gross' => $gross,
            'tax' => $tax,
            'net' => $net,
        ];
    }

    private function statusValue(): string
    {
        $status = (string) ($_POST['payment_status'] ?? 'pending');
        return in_array($status, ['pending', 'paid', 'voided'], true) ? $status : 'pending';
    }

    private function amountValue(string $key): float
    {
        return round((float) ($_POST[$key] ?? 0), 2);
    }

    private function projectId(): ?int
    {
        $id = (int) ($_POST['project_id'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
