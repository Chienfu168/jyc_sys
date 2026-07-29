<?php

namespace App\Modules\Payroll\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class PayrollController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('payroll.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $status = in_array(($_GET['status'] ?? ''), ['draft', 'confirmed', 'paid', 'voided'], true) ? (string) $_GET['status'] : '';

        $where = ['payroll_records.payroll_month = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'payroll_records.payment_status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT payroll_records.*, personnel_employees.name AS employee_name,
                    personnel_employees.employee_no, personnel_employees.department, personnel_employees.job_title
             FROM payroll_records
             INNER JOIN personnel_employees ON personnel_employees.id = payroll_records.employee_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY personnel_employees.department, personnel_employees.name, payroll_records.id DESC'
        );
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        $this->render('payroll.index', [
            'title' => '薪資管理',
            'section' => '財務會計',
            'active' => 'payroll',
            'month' => $month,
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
                'employer_pension' => isset($employee['base_salary'], $employee['pension_rate']) ? round((float) $employee['base_salary'] * (float) $employee['pension_rate'] / 100, 0) : 0,
                'payment_method' => '匯款',
                'payment_status' => 'draft',
                'paid_on' => '',
                'bank_account_id' => '',
                'notes' => '',
            ],
            'employees' => $this->employees(),
            'bankAccounts' => $this->bankAccounts(),
            'action' => '/payroll',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('payroll.manage');
        $this->validateRecord('/payroll/create');

        Database::pdo()->prepare(
            'INSERT INTO payroll_records
             (employee_id, payroll_month, pay_date, base_salary, allowance_total, overtime_pay, bonus, gross_pay, labor_insurance_deduction, health_insurance_deduction, pension_self_deduction, income_tax, leave_deduction, other_deduction, deduction_total, net_pay, employer_pension, payment_method, payment_status, paid_on, bank_account_id, notes, created_by, created_at, updated_at)
             VALUES
             (:employee_id, :payroll_month, :pay_date, :base_salary, :allowance_total, :overtime_pay, :bonus, :gross_pay, :labor_insurance_deduction, :health_insurance_deduction, :pension_self_deduction, :income_tax, :leave_deduction, :other_deduction, :deduction_total, :net_pay, :employer_pension, :payment_method, :payment_status, :paid_on, :bank_account_id, :notes, :created_by, :created_at, :updated_at)'
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

    public function void(string $id): void
    {
        $this->setStatus((int) $id, 'voided', null, '薪資紀錄已作廢。');
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
        $gross = round($this->amountValue('base_salary') + $this->amountValue('allowance_total') + $this->amountValue('overtime_pay') + $this->amountValue('bonus'), 2);
        $deduction = round(
            $this->amountValue('labor_insurance_deduction')
            + $this->amountValue('health_insurance_deduction')
            + $this->amountValue('pension_self_deduction')
            + $this->amountValue('income_tax')
            + $this->amountValue('leave_deduction')
            + $this->amountValue('other_deduction'),
            2
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
            'net_pay' => round($gross - $deduction, 2),
            'employer_pension' => $this->amountValue('employer_pension'),
            'payment_method' => trim((string) ($_POST['payment_method'] ?? '')),
            'payment_status' => (string) ($_POST['payment_status'] ?? 'draft'),
            'paid_on' => $paidOn !== '' ? $paidOn : null,
            'bank_account_id' => $bankAccountId > 0 ? $bankAccountId : null,
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
                    bank_accounts.bank_name, bank_accounts.account_no
             FROM payroll_records
             INNER JOIN personnel_employees ON personnel_employees.id = payroll_records.employee_id
             LEFT JOIN users AS creators ON creators.id = payroll_records.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = payroll_records.bank_account_id
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
        try {
            return Database::pdo()->query('SELECT * FROM bank_accounts WHERE status = "active" ORDER BY bank_name, account_no')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
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
}
