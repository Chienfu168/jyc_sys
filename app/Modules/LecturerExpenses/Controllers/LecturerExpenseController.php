<?php

namespace App\Modules\LecturerExpenses\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class LecturerExpenseController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('lecturer_expenses.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $status = in_array(($_GET['status'] ?? ''), ['pending', 'paid', 'voided'], true) ? (string) $_GET['status'] : '';

        $where = ['DATE_FORMAT(lecturer_expenses.expense_date, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'lecturer_expenses.payment_status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT lecturer_expenses.*, lecturers.name AS lecturer_name, lecturers.display_name,
                    bank_accounts.bank_name, bank_accounts.account_no
             FROM lecturer_expenses
             INNER JOIN lecturers ON lecturers.id = lecturer_expenses.lecturer_id
             LEFT JOIN bank_accounts ON bank_accounts.id = lecturer_expenses.bank_account_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY lecturer_expenses.expense_date DESC, lecturer_expenses.id DESC'
        );
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        $this->render('lecturer-expenses.index', [
            'title' => '講師支出費用',
            'section' => '財務會計',
            'active' => 'lecturer-expenses',
            'month' => $month,
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
            'action' => '/lecturer-expenses',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('lecturer_expenses.manage');
        $this->validateExpense('/lecturer-expenses/create');

        Database::pdo()->prepare(
            'INSERT INTO lecturer_expenses
             (lecturer_id, expense_date, service_title, service_unit, project_name, activity_name, hours, hourly_rate, lecture_fee, transportation_fee, other_fee, withholding_tax, gross_total, net_total, payment_method, payment_status, paid_on, bank_account_id, receipt_no, notes, created_by, created_at, updated_at)
             VALUES
             (:lecturer_id, :expense_date, :service_title, :service_unit, :project_name, :activity_name, :hours, :hourly_rate, :lecture_fee, :transportation_fee, :other_fee, :withholding_tax, :gross_total, :net_total, :payment_method, :payment_status, :paid_on, :bank_account_id, :receipt_no, :notes, :created_by, :created_at, :updated_at)'
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
        $lectureFee = round($hours * $hourlyRate, 2);
        $transportationFee = $this->amountValue('transportation_fee');
        $otherFee = $this->amountValue('other_fee');
        $withholdingTax = $this->amountValue('withholding_tax');
        $grossTotal = round($lectureFee + $transportationFee + $otherFee, 2);
        $netTotal = round($grossTotal - $withholdingTax, 2);
        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
        $paidOn = trim((string) ($_POST['paid_on'] ?? ''));

        return [
            'lecturer_id' => (int) $_POST['lecturer_id'],
            'expense_date' => (string) $_POST['expense_date'],
            'service_title' => trim((string) $_POST['service_title']),
            'service_unit' => trim((string) ($_POST['service_unit'] ?? '')),
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
                    bank_accounts.bank_name, bank_accounts.branch_name, bank_accounts.account_no
             FROM lecturer_expenses
             INNER JOIN lecturers ON lecturers.id = lecturer_expenses.lecturer_id
             LEFT JOIN users ON users.id = lecturer_expenses.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = lecturer_expenses.bank_account_id
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
        try {
            return Database::pdo()->query('SELECT * FROM bank_accounts WHERE status = "active" ORDER BY bank_name, account_no')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
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
}
