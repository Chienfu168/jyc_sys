<?php

namespace App\Modules\Payroll\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\Bank\BankSlipSettings;
use App\Domain\Payroll\PayrollCalculator;
use App\Support\BankSlipCatalog;
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

    /** 月薪資表:單一月份全體薪資彙總,含欄位合計與用印,供列印／陳核。 */
    public function worksheet(): void
    {
        $this->requirePermission('payroll.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');

        $stmt = Database::pdo()->prepare(
            'SELECT payroll_records.*, personnel_employees.name AS employee_name,
                    personnel_employees.employee_no, personnel_employees.department, personnel_employees.job_title,
                    personnel_employees.employment_type
             FROM payroll_records
             INNER JOIN personnel_employees ON personnel_employees.id = payroll_records.employee_id
             WHERE payroll_records.payroll_month = :month
               AND payroll_records.payment_status != "voided"
             ORDER BY personnel_employees.department, personnel_employees.job_title, personnel_employees.name'
        );
        $stmt->execute(['month' => $month]);
        $records = $stmt->fetchAll();

        $this->render('payroll.worksheet', [
            'title' => '月薪資表',
            'section' => '財務會計',
            'active' => 'payroll',
            'month' => $month,
            'records' => $records,
            'totals' => $this->worksheetTotals($records),
            'profile' => foundation_profile(),
            'printable' => true,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('payroll.manage');
        $employee = $this->selectedEmployee((int) ($_GET['employee_id'] ?? 0));
        // 帶出該員工的「薪資基本資料」(最新固定金額),減少每月重複輸入。
        $standing = $this->standingFor($employee);

        $this->render('payroll.create', [
            'title' => '新增薪資紀錄',
            'section' => '財務會計',
            'active' => 'payroll',
            'record' => $standing + [
                'employee_id' => $employee['id'] ?? '',
                'payroll_month' => date('Y-m'),
                'pay_date' => date('Y-m-t'),
                'overtime_pay' => 0,
                'bonus' => 0,
                'income_tax' => 0,
                'leave_deduction' => 0,
                'other_deduction' => 0,
                'supplementary_premium' => 0,
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

    /** 薪資基本資料:列出在職員工與其固定薪資／勞健保／雇主負擔預設值。 */
    public function defaults(): void
    {
        $this->requirePermission('payroll.manage');

        if (!$this->defaultsTableExists()) {
            flash('error', '薪資基本資料表尚未建立,請先於「資料庫更新與檢查」執行更新。');
            redirect('/payroll');
        }

        $rows = Database::pdo()->query(
            'SELECT e.id, e.employee_no, e.name, e.department, e.job_title, e.base_salary,
                    d.base_salary AS d_base_salary, d.allowance_total, d.labor_insurance_deduction,
                    d.health_insurance_deduction, d.pension_self_deduction, d.employer_pension,
                    d.employer_labor_insurance, d.employer_health_insurance, d.occupational_insurance,
                    d.updated_at AS d_updated_at
             FROM personnel_employees e
             LEFT JOIN employee_payroll_defaults d ON d.employee_id = e.id
             WHERE e.status = "active"
             ORDER BY e.department, e.name'
        )->fetchAll();

        $this->render('payroll.defaults-index', [
            'title' => '薪資基本資料',
            'section' => '財務會計',
            'active' => 'payroll',
            'rows' => $rows,
        ]);
    }

    public function editDefaults(string $employeeId): void
    {
        $this->requirePermission('payroll.manage');
        $employee = $this->employeeRow((int) $employeeId);
        $defaults = $this->payrollDefaultsFor((int) $employeeId);

        $this->render('payroll.defaults-form', [
            'title' => '薪資基本資料 - ' . ($employee['name'] ?? ''),
            'section' => '財務會計',
            'active' => 'payroll',
            'employee' => $employee,
            'defaults' => $defaults,
            'action' => '/payroll/defaults/' . (int) $employeeId,
        ]);
    }

    public function saveDefaults(string $employeeId): void
    {
        $this->requirePermission('payroll.manage');
        if (!$this->defaultsTableExists()) {
            flash('error', '薪資基本資料表尚未建立,請先執行資料庫更新。');
            redirect('/payroll');
        }
        $this->employeeRow((int) $employeeId);

        foreach (self::STANDING_FIELDS as $field) {
            if ($this->amountValue($field) < 0) {
                $this->backWithInput('/payroll/defaults/' . (int) $employeeId . '/edit', $_POST, '金額不可小於 0。');
            }
        }

        $params = ['employee_id' => (int) $employeeId];
        foreach (self::STANDING_FIELDS as $field) {
            $params[$field] = $this->amountValue($field);
        }
        $params['notes'] = mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 255);
        $params['updated_by'] = auth()->user()['id'] ?? null;
        $params['created_at'] = now();
        $params['updated_at'] = now();

        Database::pdo()->prepare(
            'INSERT INTO employee_payroll_defaults
             (employee_id, base_salary, allowance_total, labor_insurance_deduction, health_insurance_deduction,
              pension_self_deduction, employer_pension, employer_labor_insurance, employer_health_insurance,
              occupational_insurance, notes, updated_by, created_at, updated_at)
             VALUES
             (:employee_id, :base_salary, :allowance_total, :labor_insurance_deduction, :health_insurance_deduction,
              :pension_self_deduction, :employer_pension, :employer_labor_insurance, :employer_health_insurance,
              :occupational_insurance, :notes, :updated_by, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
              base_salary = VALUES(base_salary), allowance_total = VALUES(allowance_total),
              labor_insurance_deduction = VALUES(labor_insurance_deduction),
              health_insurance_deduction = VALUES(health_insurance_deduction),
              pension_self_deduction = VALUES(pension_self_deduction), employer_pension = VALUES(employer_pension),
              employer_labor_insurance = VALUES(employer_labor_insurance),
              employer_health_insurance = VALUES(employer_health_insurance),
              occupational_insurance = VALUES(occupational_insurance), notes = VALUES(notes),
              updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)'
        )->execute($params);

        AuditLog::write('update_defaults', 'payroll', 'employee_payroll_defaults', (int) $employeeId);
        flash('success', '薪資基本資料已儲存,下次建立薪資將自動帶出。');
        redirect('/payroll/defaults');
    }

    /** 依薪資紀錄產生一張銀行匯款單(取款憑條),帶入收款人、帳號與實發金額。 */
    public function remittance(string $id): void
    {
        $this->requirePermission('bank_slips.manage');
        $record = $this->findRecord((int) $id);

        if ($record['payment_status'] === 'voided') {
            flash('error', '已作廢的薪資紀錄無法產生匯款單。');
            redirect('/payroll/' . $id);
        }
        if ((float) $record['net_pay'] <= 0) {
            flash('error', '實發金額為 0,無法產生匯款單。');
            redirect('/payroll/' . $id);
        }

        $bankCode = BankSlipCatalog::defaultCode();
        if (!BankSlipSettings::isUsable($bankCode)) {
            flash('error', BankSlipCatalog::name($bankCode) . ' 匯款單尚未啟用或設定,請先於「匯款單設定」完成設定。');
            redirect('/bank-slips/settings');
        }

        $settings = BankSlipSettings::forBank($bankCode);
        $profile = foundation_profile();
        $payingBank = trim((string) ($record['employee_bank_name'] ?? '') . ' ' . (string) ($record['employee_bank_branch'] ?? ''));

        Database::pdo()->prepare(
            'INSERT INTO bank_slips
             (bank_code, slip_date, remittance_type, withdrawal_bank_account_id, remitter_name, remitter_id_no, remitter_phone, agent_name,
              payee_name, payee_account, paying_bank_name, amount, message, sms_mobile, source_type, source_id, notes, created_by, created_at, updated_at)
             VALUES
             (:bank_code, :slip_date, :remittance_type, :withdrawal_bank_account_id, :remitter_name, :remitter_id_no, :remitter_phone, :agent_name,
              :payee_name, :payee_account, :paying_bank_name, :amount, :message, :sms_mobile, :source_type, :source_id, :notes, :created_by, :created_at, :updated_at)'
        )->execute([
            'bank_code' => $bankCode,
            'slip_date' => date('Y-m-d'),
            'remittance_type' => $settings['default_remittance_type'] ?: '一般跨行匯款(11)',
            'withdrawal_bank_account_id' => $settings['withdrawal_bank_account_id'] ?: null,
            'remitter_name' => $settings['remitter_name'] ?: ($profile['foundation_name'] ?? ''),
            'remitter_id_no' => $settings['remitter_id_no'] ?: ($profile['tax_id'] ?? ''),
            'remitter_phone' => $settings['remitter_phone'] ?: ($profile['phone'] ?? ''),
            'agent_name' => '',
            'payee_name' => (string) ($record['employee_bank_account_name'] ?: $record['employee_name']),
            'payee_account' => (string) ($record['employee_bank_account_no'] ?? ''),
            'paying_bank_name' => trim($payingBank),
            'amount' => (int) round((float) $record['net_pay']),
            'message' => mb_substr($record['payroll_month'] . ' 薪資', 0, 30),
            'sms_mobile' => '',
            'source_type' => 'payroll_records',
            'source_id' => (int) $record['id'],
            'notes' => '由薪資紀錄產生',
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slipId = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create_remittance', 'payroll', 'bank_slips', $slipId, ['payroll_id' => (int) $record['id']]);
        flash('success', '已產生匯款單,請確認收款帳號後列印。');
        redirect('/bank-slips/' . $slipId);
    }

    private function employeeRow(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, employee_no, name, department, job_title, base_salary, pension_rate
             FROM personnel_employees WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$employee) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到員工']);
            exit;
        }

        return $employee;
    }

    public function store(): void
    {
        $this->requirePermission('payroll.manage');
        $this->validateRecord('/payroll/create');

        Database::pdo()->prepare(
            'INSERT INTO payroll_records
             (employee_id, payroll_month, pay_date, base_salary, allowance_total, overtime_pay, bonus, gross_pay, labor_insurance_deduction, health_insurance_deduction, pension_self_deduction, income_tax, leave_deduction, other_deduction, supplementary_premium, deduction_total, net_pay, employer_pension, employer_labor_insurance, employer_health_insurance, occupational_insurance, payment_method, payment_status, paid_on, bank_account_id, project_id, notes, created_by, created_at, updated_at)
             VALUES
             (:employee_id, :payroll_month, :pay_date, :base_salary, :allowance_total, :overtime_pay, :bonus, :gross_pay, :labor_insurance_deduction, :health_insurance_deduction, :pension_self_deduction, :income_tax, :leave_deduction, :other_deduction, :supplementary_premium, :deduction_total, :net_pay, :employer_pension, :employer_labor_insurance, :employer_health_insurance, :occupational_insurance, :payment_method, :payment_status, :paid_on, :bank_account_id, :project_id, :notes, :created_by, :created_at, :updated_at)'
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
                 supplementary_premium = :supplementary_premium,
                 deduction_total = :deduction_total,
                 net_pay = :net_pay,
                 employer_pension = :employer_pension,
                 employer_labor_insurance = :employer_labor_insurance,
                 employer_health_insurance = :employer_health_insurance,
                 occupational_insurance = :occupational_insurance,
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
        $this->requireManageOrOwner('payroll.manage', $record['created_by'] ?? null);

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

        foreach (['base_salary', 'allowance_total', 'overtime_pay', 'bonus', 'labor_insurance_deduction', 'health_insurance_deduction', 'pension_self_deduction', 'income_tax', 'leave_deduction', 'other_deduction', 'supplementary_premium', 'employer_pension', 'employer_labor_insurance', 'employer_health_insurance', 'occupational_insurance'] as $key) {
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
            $this->amountValue('other_deduction'),
            $this->amountValue('supplementary_premium')
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
            'supplementary_premium' => $this->amountValue('supplementary_premium'),
            'deduction_total' => $deduction,
            'net_pay' => PayrollCalculator::netPay($gross, $deduction),
            'employer_pension' => $this->amountValue('employer_pension'),
            'employer_labor_insurance' => $this->amountValue('employer_labor_insurance'),
            'employer_health_insurance' => $this->amountValue('employer_health_insurance'),
            'occupational_insurance' => $this->amountValue('occupational_insurance'),
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

    /** 薪資「基本資料」中固定沿用的欄位(每月自動帶出)。 */
    private const STANDING_FIELDS = [
        'base_salary', 'allowance_total', 'labor_insurance_deduction', 'health_insurance_deduction',
        'pension_self_deduction', 'employer_pension', 'employer_labor_insurance',
        'employer_health_insurance', 'occupational_insurance',
    ];

    private function employees(): array
    {
        $hasDefaults = $this->defaultsTableExists();
        // 每位員工帶出「基本資料」的預填值:優先採 employee_payroll_defaults,否則以人事本薪與提繳率推算。
        $baseFill = $hasDefaults ? 'COALESCE(NULLIF(d.base_salary, 0), e.base_salary)' : 'e.base_salary';
        $pensionFill = $hasDefaults
            ? 'COALESCE(NULLIF(d.employer_pension, 0), ROUND(e.base_salary * e.pension_rate / 100, 0))'
            : 'ROUND(e.base_salary * e.pension_rate / 100, 0)';

        $cols = [
            'e.id', 'e.employee_no', 'e.name', 'e.department', 'e.job_title', 'e.base_salary', 'e.pension_rate',
            $baseFill . ' AS fill_base_salary',
            $pensionFill . ' AS fill_employer_pension',
        ];
        foreach (['allowance_total', 'labor_insurance_deduction', 'health_insurance_deduction', 'pension_self_deduction', 'employer_labor_insurance', 'employer_health_insurance', 'occupational_insurance'] as $f) {
            $cols[] = ($hasDefaults ? "COALESCE(d.$f, 0)" : '0') . " AS fill_$f";
        }

        $sql = 'SELECT ' . implode(', ', $cols) . ' FROM personnel_employees e'
            . ($hasDefaults ? ' LEFT JOIN employee_payroll_defaults d ON d.employee_id = e.id' : '')
            . ' WHERE e.status = "active" ORDER BY e.department, e.name';

        return Database::pdo()->query($sql)->fetchAll();
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

    /** 依員工的基本資料組出薪資固定欄位預填值(供伺服器端初次帶入)。 */
    private function standingFor(?array $employee): array
    {
        $out = array_fill_keys(self::STANDING_FIELDS, 0);
        if (!$employee) {
            return $out;
        }

        $out['base_salary'] = $employee['base_salary'] ?? 0;
        if (isset($employee['base_salary'], $employee['pension_rate'])) {
            $out['employer_pension'] = PayrollCalculator::employerPension((float) $employee['base_salary'], (float) $employee['pension_rate']);
        }

        $defaults = $this->payrollDefaultsFor((int) ($employee['id'] ?? 0));
        foreach (self::STANDING_FIELDS as $field) {
            if (array_key_exists($field, $defaults) && (float) $defaults[$field] != 0.0) {
                $out[$field] = $defaults[$field];
            }
        }

        return $out;
    }

    private function payrollDefaultsFor(int $employeeId): array
    {
        if ($employeeId <= 0 || !$this->defaultsTableExists()) {
            return [];
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM employee_payroll_defaults WHERE employee_id = :id LIMIT 1');
        $stmt->execute(['id' => $employeeId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function defaultsTableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t'
            );
            $stmt->execute(['t' => 'employee_payroll_defaults']);
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
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

    /** 月薪資表欄位合計(不含作廢),含雇主保險小計 B 與總負擔 D。 */
    private function worksheetTotals(array $records): array
    {
        $sum = array_fill_keys([
            'base_salary', 'allowance_total', 'overtime_pay', 'bonus', 'gross_pay',
            'income_tax', 'labor_insurance_deduction', 'health_insurance_deduction',
            'pension_self_deduction', 'leave_deduction', 'other_deduction',
            'supplementary_premium', 'deduction_total', 'net_pay',
            'employer_labor_insurance', 'employer_health_insurance', 'occupational_insurance',
            'employer_insurance_subtotal', 'employer_pension', 'employer_burden_total',
        ], 0.0);

        foreach ($records as $record) {
            foreach ($sum as $key => $value) {
                if (in_array($key, ['employer_insurance_subtotal', 'employer_burden_total'], true)) {
                    continue;
                }
                $sum[$key] += (float) ($record[$key] ?? 0);
            }
            $subtotal = PayrollCalculator::employerInsuranceSubtotal(
                (float) ($record['employer_labor_insurance'] ?? 0),
                (float) ($record['employer_health_insurance'] ?? 0),
                (float) ($record['occupational_insurance'] ?? 0)
            );
            $sum['employer_insurance_subtotal'] += $subtotal;
            $sum['employer_burden_total'] += PayrollCalculator::employerBurdenTotal($subtotal, (float) ($record['employer_pension'] ?? 0));
        }

        return $sum;
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
