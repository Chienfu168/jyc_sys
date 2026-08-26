<?php

namespace App\Modules\Payroll\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\Payroll\PayrollCalculator;
use App\Support\DateScope;
use PDO;

final class PayrollController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('payroll.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $scope = DateScope::normalize($_GET['scope'] ?? null);
        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $status = in_array(($_GET['status'] ?? ''), ['draft', 'confirmed', 'paid', 'voided'], true) ? (string) $_GET['status'] : '';

        [$dateWhere, $params] = DateScope::conditionForMonthString('payroll_records.payroll_month', $scope, $month, $year);
        $where = $dateWhere !== '' ? [$dateWhere] : [];
        if ($status !== '') {
            $where[] = 'payroll_records.payment_status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT payroll_records.*, personnel_employees.name AS employee_name,
                    personnel_employees.employee_no, personnel_employees.department, personnel_employees.job_title,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM payroll_records
             INNER JOIN personnel_employees ON personnel_employees.id = payroll_records.employee_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = payroll_records.accounting_voucher_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
             ORDER BY personnel_employees.department, personnel_employees.name, payroll_records.id DESC'
        );
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $this->render('payroll.index', [
            'title' => '薪資管理',
            'section' => '財務會計',
            'active' => 'payroll',
            'month' => $month,
            'scope' => $scope,
            'year' => $year,
            'status' => $status,
            'records' => $records,
            'totals' => $this->totals($records),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('payroll.manage');
        $employee = $this->selectedEmployee((int) ($_GET['employee_id'] ?? 0));

        $this->render('payroll.create', [
            'title' => '新增薪資紀錄',
            'section' => '財務會計',
            'active' => 'payroll',
            'record' => [
                'employee_id' => $employee['id'] ?? '',
                'payroll_month' => date('Y-m'),
                'pay_date' => date('Y-m-t'),
                'base_salary' => $employee['base_salary'] ?? 0,
                'allowance_total' => 0,
                'overtime_pay' => 0,
                'bonus' => 0,
                'labor_insurance_deduction' => 0,
                'health_insurance_deduction' => 0,
                'pension_self_deduction' => 0,
                'income_tax' => 0,
                'leave_deduction' => 0,
                'other_deduction' => 0,
                'employer_pension' => isset($employee['base_salary'], $employee['pension_rate']) ? PayrollCalculator::employerPension((float) $employee['base_salary'], (float) $employee['pension_rate']) : 0,
                'payment_method' => '匯款',
                'payment_status' => 'draft',
                'paid_on' => '',
                'bank_account_id' => '',
                'project_id' => '',
                'notes' => '',
            ],
            'employees' => $this->employees(),
            'bankAccounts' => $this->bankAccounts(),
            'projects' => $this->projects(),
            'action' => '/payroll',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('payroll.manage');
        $this->validateRecord('/payroll/create');

        Database::pdo()->prepare(
            'INSERT INTO payroll_records
             (employee_id, payroll_month, pay_date, base_salary, allowance_total, overtime_pay, bonus, gross_pay, labor_insurance_deduction, health_insurance_deduction, pension_self_deduction, income_tax, leave_deduction, other_deduction, deduction_total, net_pay, employer_pension, payment_method, payment_status, paid_on, bank_account_id, project_id, notes, created_by, created_at, updated_at)
             VALUES
             (:employee_id, :payroll_month, :pay_date, :base_salary, :allowance_total, :overtime_pay, :bonus, :gross_pay, :labor_insurance_deduction, :health_insurance_deduction, :pension_self_deduction, :income_tax, :leave_deduction, :other_deduction, :deduction_total, :net_pay, :employer_pension, :payment_method, :payment_status, :paid_on, :bank_account_id, :project_id, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'payroll', 'payroll_records', $id);
        flash('success', '薪資紀錄已建立。');
        redirect('/payroll/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('payroll.view');

        $this->render('payroll.show', [
            'title' => '薪資明細',
            'section' => '財務會計',
            'active' => 'payroll',
            'record' => $this->findRecord((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('payroll.manage');

        $this->render('payroll.edit', [
            'title' => '編輯薪資紀錄',
            'section' => '財務會計',
            'active' => 'payroll',
            'record' => $this->findRecord((int) $id),
            'employees' => $this->employees(),
            'bankAccounts' => $this->bankAccounts(),
            'projects' => $this->projects(),
            'action' => '/payroll/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('payroll.manage');
        $this->findRecord((int) $id);
        $this->validateRecord('/payroll/' . $id . '/edit', (int) $id);

        Database::pdo()->prepare(
            'UPDATE payroll_records
             SET employee_id = :employee_id,
                 payroll_month = :payroll_month,
                 pay_date = :pay_date,
                 base_salary = :base_salary,
                 allowance_total = :allowance_total,
                 overtime_pay = :overtime_pay,
                 bonus = :bonus,
                 gross_pay = :gross_pay,
                 labor_insurance_deduction = :labor_insurance_deduction,
                 health_insurance_deduction = :health_insurance_deduction,
                 pension_self_deduction = :pension_self_deduction,
                 income_tax = :income_tax,
                 leave_deduction = :leave_deduction,
                 other_deduction = :other_deduction,
                 deduction_total = :deduction_total,
                 net_pay = :net_pay,
                 employer_pension = :employer_pension,
                 payment_method = :payment_method,
                 payment_status = :payment_status,
                 paid_on = :paid_on,
                 bank_account_id = :bank_account_id,
                 project_id = :project_id,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'payroll', 'payroll_records', (int) $id);
        flash('success', '薪資紀錄已更新。');
        redirect('/payroll/' . $id);
    }

    public function confirm(string $id): void
    {
        $this->setStatus((int) $id, 'confirmed', null, '薪資紀錄已確認。');
    }

    public function markPaid(string $id): void
    {
        $this->setStatus((int) $id, 'paid', date('Y-m-d'), '薪資紀錄已標記為已付款。');
    }

    public function destroy(string $id): void
    {
        $record = $this->findRecord((int) $id);
        $this->requireManageOrOwner('payroll.delete', $record['created_by'] ?? null);

        if (!empty($record['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的薪資紀錄不可直接刪除，請先處理相關傳票。');
            redirect('/payroll/' . $id);
        }

        Database::pdo()->prepare('DELETE FROM payroll_records WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'payroll', 'payroll_records', (int) $id, [
            'net_pay' => $record['net_pay'] ?? null,
        ]);
        flash('success', '薪資紀錄已刪除。');
        redirect('/payroll');
    }

    public function void(string $id): void
    {
        $this->setStatus((int) $id, 'voided', null, '薪資紀錄已作廢。');
    }

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $record = $this->findRecord((int) $id);

        if (!in_array($record['payment_status'], ['confirmed', 'paid'], true)) {
            flash('error', '只有已確認或已付款的薪資紀錄可以建立會計傳票。');
            redirect('/payroll/' . $id);
        }

        if (!empty($record['accounting_voucher_id'])) {
            flash('error', '此薪資紀錄已建立會計傳票。');
            redirect('/accounting/vouchers/' . $record['accounting_voucher_id']);
        }

        $cashAccount = $this->accountByCode('1100');
        $payableAccount = $this->accountByCode('2100');
        $salaryAccount = $this->accountByCode('5100');

        if (!$cashAccount || !$payableAccount || !$salaryAccount) {
            flash('error', '找不到薪資拋轉所需會計科目，請先確認 1100、2100、5100 已建立。');
            redirect('/payroll/' . $id);
        }

        $voucherDate = $record['paid_on'] ?: ($record['pay_date'] ?: date('Y-m-d'));
        $grossPay = round((float) $record['gross_pay'], 2);
        $deductionTotal = round((float) $record['deduction_total'], 2);
        $netPay = round((float) $record['net_pay'], 2);
        $employerPension = round((float) $record['employer_pension'], 2);
        $debitTotal = round($grossPay + $employerPension, 2);
        $summary = sprintf('薪資：%s %s', $record['payroll_month'], $record['employee_name']);
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
                'source_type' => 'payroll_records',
                'source_id' => (int) $record['id'],
                'summary' => $summary,
                'status' => 'draft',
                'notes' => trim("由薪資紀錄自動拋轉\n" . (string) ($record['notes'] ?? '')),
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

            $sortOrder = 10;
            $lineStmt->execute([
                'voucher_id' => $voucherId,
                'account_id' => (int) $salaryAccount['id'],
                'description' => $summary . ' 應發薪資',
                'debit' => $grossPay,
                'credit' => 0,
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($employerPension > 0) {
                $sortOrder += 10;
                $lineStmt->execute([
                    'voucher_id' => $voucherId,
                    'account_id' => (int) $salaryAccount['id'],
                    'description' => $summary . ' 雇主退休金提繳',
                    'debit' => $employerPension,
                    'credit' => 0,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ($record['payment_status'] === 'paid' && $netPay > 0) {
                $sortOrder += 10;
                $lineStmt->execute([
                    'voucher_id' => $voucherId,
                    'account_id' => (int) $cashAccount['id'],
                    'description' => $summary . ' 實發薪資',
                    'debit' => 0,
                    'credit' => $netPay,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $payableAmount = $record['payment_status'] === 'paid'
                ? round($deductionTotal + $employerPension, 2)
                : $debitTotal;

            if ($payableAmount > 0) {
                $sortOrder += 10;
                $lineStmt->execute([
                    'voucher_id' => $voucherId,
                    'account_id' => (int) $payableAccount['id'],
                    'description' => $record['payment_status'] === 'paid' ? $summary . ' 代扣及應付款' : $summary . ' 應付薪資',
                    'debit' => 0,
                    'credit' => $payableAmount,
                    'sort_order' => $sortOrder,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $pdo->prepare(
                'UPDATE payroll_records
                 SET accounting_voucher_id = :voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $record['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/payroll/' . $id);
        }

        AuditLog::write('create_voucher', 'payroll', 'payroll_records', (int) $record['id']);
        AuditLog::write('create', 'accounting', 'accounting_vouchers', $voucherId);
        flash('success', '已建立薪資草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
    }

    private function setStatus(int $id, string $status, ?string $paidOn, string $message): void
    {
        $this->requirePermission('payroll.manage');
        $this->findRecord($id);

        Database::pdo()->prepare(
            'UPDATE payroll_records
             SET payment_status = :status,
                 paid_on = COALESCE(:paid_on, paid_on),
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'status' => $status,
            'paid_on' => $paidOn,
            'updated_at' => now(),
            'id' => $id,
        ]);

        AuditLog::write($status, 'payroll', 'payroll_records', $id);
        flash('success', $message);
        redirect('/payroll/' . $id);
    }

    private function validateRecord(string $path, ?int $ignoreId = null): void
    {
        if ($error = Validator::required($_POST, [
            'employee_id' => '人員',
            'payroll_month' => '薪資月份',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}$/', (string) $_POST['payroll_month'])) {
            $this->backWithInput($path, $_POST, '薪資月份格式不正確。');
        }

        if (!$this->selectedEmployee((int) $_POST['employee_id'])) {
            $this->backWithInput($path, $_POST, '請選擇有效的人事資料。');
        }

        $sql = 'SELECT id FROM payroll_records WHERE employee_id = :employee_id AND payroll_month = :payroll_month';
        $params = [
            'employee_id' => (int) $_POST['employee_id'],
            'payroll_month' => (string) $_POST['payroll_month'],
        ];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            $this->backWithInput($path, $_POST, '同一人員同一月份已有薪資紀錄。');
        }

        foreach (['base_salary', 'allowance_total', 'overtime_pay', 'bonus', 'labor_insurance_deduction', 'health_insurance_deduction', 'pension_self_deduction', 'income_tax', 'leave_deduction', 'other_deduction', 'employer_pension'] as $key) {
            if ($this->amountValue($key) < 0) {
                $this->backWithInput($path, $_POST, '薪資與扣款金額不可小於 0。');
            }
        }

        if (!in_array($_POST['payment_status'] ?? '', ['draft', 'confirmed', 'paid', 'voided'], true)) {
            $this->backWithInput($path, $_POST, '付款狀態不正確。');
        }
    }

    private function payload(): array
    {
        $gross = PayrollCalculator::grossPay(
            $this->amountValue('base_salary'),
            $this->amountValue('allowance_total'),
            $this->amountValue('overtime_pay'),
            $this->amountValue('bonus')
        );
        $deduction = PayrollCalculator::deductionTotal(
            $this->amountValue('labor_insurance_deduction'),
            $this->amountValue('health_insurance_deduction'),
            $this->amountValue('pension_self_deduction'),
            $this->amountValue('income_tax'),
            $this->amountValue('leave_deduction'),
            $this->amountValue('other_deduction')
        );
        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
        $paidOn = trim((string) ($_POST['paid_on'] ?? ''));

        return [
            'employee_id' => (int) $_POST['employee_id'],
            'payroll_month' => (string) $_POST['payroll_month'],
            'pay_date' => $this->nullableDate('pay_date'),
            'base_salary' => $this->amountValue('base_salary'),
            'allowance_total' => $this->amountValue('allowance_total'),
            'overtime_pay' => $this->amountValue('overtime_pay'),
            'bonus' => $this->amountValue('bonus'),
            'gross_pay' => $gross,
            'labor_insurance_deduction' => $this->amountValue('labor_insurance_deduction'),
            'health_insurance_deduction' => $this->amountValue('health_insurance_deduction'),
            'pension_self_deduction' => $this->amountValue('pension_self_deduction'),
            'income_tax' => $this->amountValue('income_tax'),
            'leave_deduction' => $this->amountValue('leave_deduction'),
            'other_deduction' => $this->amountValue('other_deduction'),
            'deduction_total' => $deduction,
            'net_pay' => PayrollCalculator::netPay($gross, $deduction),
            'employer_pension' => $this->amountValue('employer_pension'),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
            'payment_status' => (string) ($_POST['payment_status'] ?? 'draft'),
            'paid_on' => $paidOn !== '' ? $paidOn : null,
            'bank_account_id' => $bankAccountId > 0 ? $bankAccountId : null,
            'project_id' => $this->projectId(),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findRecord(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT payroll_records.*, personnel_employees.name AS employee_name,
                    personnel_employees.employee_no, personnel_employees.department, personnel_employees.job_title,
                    personnel_employees.bank_name AS employee_bank_name,
                    personnel_employees.bank_branch AS employee_bank_branch,
                    personnel_employees.bank_account_no AS employee_bank_account_no,
                    personnel_employees.bank_account_name AS employee_bank_account_name,
                    creators.name AS created_by_name,
                    bank_accounts.bank_name, bank_accounts.account_no,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM payroll_records
             INNER JOIN personnel_employees ON personnel_employees.id = payroll_records.employee_id
             LEFT JOIN users AS creators ON creators.id = payroll_records.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = payroll_records.bank_account_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = payroll_records.accounting_voucher_id
             WHERE payroll_records.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到薪資紀錄']);
            exit;
        }

        return $record;
    }

    private function employees(): array
    {
        return Database::pdo()->query(
            'SELECT id, employee_no, name, department, job_title, base_salary, pension_rate
             FROM personnel_employees
             WHERE status = "active"
             ORDER BY department, name'
        )->fetchAll();
    }

    private function selectedEmployee(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT id, name, base_salary, pension_rate FROM personnel_employees WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
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

    private function totals(array $records): array
    {
        $gross = 0.0;
        $deduction = 0.0;
        $net = 0.0;
        foreach ($records as $record) {
            if ($record['payment_status'] === 'voided') {
                continue;
            }
            $gross += (float) $record['gross_pay'];
            $deduction += (float) $record['deduction_total'];
            $net += (float) $record['net_pay'];
        }

        return [
            'gross' => $gross,
            'deduction' => $deduction,
            'net' => $net,
        ];
    }

    private function nullableDate(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
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
