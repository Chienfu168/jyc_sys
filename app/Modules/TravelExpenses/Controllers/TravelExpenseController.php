<?php

namespace App\Modules\TravelExpenses\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class TravelExpenseController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('travel_expenses.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $status = in_array(($_GET['status'] ?? ''), ['pending', 'paid', 'voided'], true) ? (string) $_GET['status'] : '';

        $where = ['DATE_FORMAT(travel_start, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'payment_status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT travel_expenses.*, bank_accounts.bank_name, bank_accounts.account_no
             FROM travel_expenses
             LEFT JOIN bank_accounts ON bank_accounts.id = travel_expenses.bank_account_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY travel_start DESC, id DESC'
        );
        $stmt->execute($params);
        $expenses = $stmt->fetchAll();

        $this->render('travel-expenses.index', [
            'title' => '出差費用',
            'section' => '財務會計',
            'active' => 'travel-expenses',
            'month' => $month,
            'status' => $status,
            'expenses' => $expenses,
            'totals' => $this->totals($expenses),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('travel_expenses.manage');
        $user = auth()->user();

        $this->render('travel-expenses.create', [
            'title' => '新增出差費用',
            'section' => '財務會計',
            'active' => 'travel-expenses',
            'expense' => [
                'traveler_user_id' => $user['id'] ?? '',
                'traveler_name' => $user['name'] ?? '',
                'travel_start' => date('Y-m-d'),
                'travel_end' => date('Y-m-d'),
                'destination' => '',
                'purpose' => '',
                'project_name' => '',
                'transportation_fee' => 0,
                'accommodation_fee' => 0,
                'meal_fee' => 0,
                'miscellaneous_fee' => 0,
                'advance_amount' => 0,
                'payment_method' => '匯款',
                'payment_status' => 'pending',
                'paid_on' => '',
                'bank_account_id' => '',
                'receipt_no' => '',
                'notes' => '',
            ],
            'users' => $this->users(),
            'bankAccounts' => $this->bankAccounts(),
            'action' => '/travel-expenses',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('travel_expenses.manage');
        $this->validateExpense('/travel-expenses/create');

        Database::pdo()->prepare(
            'INSERT INTO travel_expenses
             (traveler_user_id, traveler_name, travel_start, travel_end, destination, purpose, project_name, transportation_fee, accommodation_fee, meal_fee, miscellaneous_fee, advance_amount, total_amount, reimbursable_amount, payment_method, payment_status, paid_on, bank_account_id, receipt_no, notes, created_by, created_at, updated_at)
             VALUES
             (:traveler_user_id, :traveler_name, :travel_start, :travel_end, :destination, :purpose, :project_name, :transportation_fee, :accommodation_fee, :meal_fee, :miscellaneous_fee, :advance_amount, :total_amount, :reimbursable_amount, :payment_method, :payment_status, :paid_on, :bank_account_id, :receipt_no, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'travel_expenses', 'travel_expenses', $id);
        flash('success', '出差費用已建立。');
        redirect('/travel-expenses/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('travel_expenses.view');

        $this->render('travel-expenses.show', [
            'title' => '出差費用明細',
            'section' => '財務會計',
            'active' => 'travel-expenses',
            'expense' => $this->findExpense((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('travel_expenses.manage');

        $this->render('travel-expenses.edit', [
            'title' => '編輯出差費用',
            'section' => '財務會計',
            'active' => 'travel-expenses',
            'expense' => $this->findExpense((int) $id),
            'users' => $this->users(),
            'bankAccounts' => $this->bankAccounts(),
            'action' => '/travel-expenses/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('travel_expenses.manage');
        $this->findExpense((int) $id);
        $this->validateExpense('/travel-expenses/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE travel_expenses
             SET traveler_user_id = :traveler_user_id,
                 traveler_name = :traveler_name,
                 travel_start = :travel_start,
                 travel_end = :travel_end,
                 destination = :destination,
                 purpose = :purpose,
                 project_name = :project_name,
                 transportation_fee = :transportation_fee,
                 accommodation_fee = :accommodation_fee,
                 meal_fee = :meal_fee,
                 miscellaneous_fee = :miscellaneous_fee,
                 advance_amount = :advance_amount,
                 total_amount = :total_amount,
                 reimbursable_amount = :reimbursable_amount,
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

        AuditLog::write('update', 'travel_expenses', 'travel_expenses', (int) $id);
        flash('success', '出差費用已更新。');
        redirect('/travel-expenses/' . $id);
    }

    public function markPaid(string $id): void
    {
        $this->requirePermission('travel_expenses.manage');
        $this->findExpense((int) $id);

        Database::pdo()->prepare(
            'UPDATE travel_expenses
             SET payment_status = "paid", paid_on = COALESCE(paid_on, :paid_on), updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'paid_on' => date('Y-m-d'),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('mark_paid', 'travel_expenses', 'travel_expenses', (int) $id);
        flash('success', '出差費用已標記為已付款。');
        redirect('/travel-expenses/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('travel_expenses.manage');
        $this->findExpense((int) $id);

        Database::pdo()->prepare('UPDATE travel_expenses SET payment_status = "voided", updated_at = :updated_at WHERE id = :id')
            ->execute([
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('void', 'travel_expenses', 'travel_expenses', (int) $id);
        flash('success', '出差費用已作廢。');
        redirect('/travel-expenses/' . $id);
    }

    private function validateExpense(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'traveler_name' => '出差人',
            'travel_start' => '起始日期',
            'travel_end' => '結束日期',
            'destination' => '出差地點',
            'purpose' => '出差事由',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['travel_start'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['travel_end'])) {
            $this->backWithInput($path, $_POST, '出差日期格式不正確。');
        }

        if ((string) $_POST['travel_end'] < (string) $_POST['travel_start']) {
            $this->backWithInput($path, $_POST, '結束日期不可早於起始日期。');
        }

        foreach (['transportation_fee', 'accommodation_fee', 'meal_fee', 'miscellaneous_fee', 'advance_amount'] as $key) {
            if ($this->amountValue($key) < 0) {
                $this->backWithInput($path, $_POST, '費用金額不可小於 0。');
            }
        }
    }

    private function payload(): array
    {
        $transportation = $this->amountValue('transportation_fee');
        $accommodation = $this->amountValue('accommodation_fee');
        $meal = $this->amountValue('meal_fee');
        $misc = $this->amountValue('miscellaneous_fee');
        $advance = $this->amountValue('advance_amount');
        $total = round($transportation + $accommodation + $meal + $misc, 2);
        $reimbursable = round($total - $advance, 2);
        $bankAccountId = (int) ($_POST['bank_account_id'] ?? 0);
        $travelerUserId = (int) ($_POST['traveler_user_id'] ?? 0);
        $paidOn = trim((string) ($_POST['paid_on'] ?? ''));

        return [
            'traveler_user_id' => $travelerUserId > 0 ? $travelerUserId : null,
            'traveler_name' => trim((string) $_POST['traveler_name']),
            'travel_start' => (string) $_POST['travel_start'],
            'travel_end' => (string) $_POST['travel_end'],
            'destination' => trim((string) $_POST['destination']),
            'purpose' => trim((string) $_POST['purpose']),
            'project_name' => trim((string) ($_POST['project_name'] ?? '')),
            'transportation_fee' => $transportation,
            'accommodation_fee' => $accommodation,
            'meal_fee' => $meal,
            'miscellaneous_fee' => $misc,
            'advance_amount' => $advance,
            'total_amount' => $total,
            'reimbursable_amount' => $reimbursable,
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
            'SELECT travel_expenses.*, users.name AS traveler_account_name, creators.name AS created_by_name,
                    bank_accounts.bank_name, bank_accounts.branch_name, bank_accounts.account_no
             FROM travel_expenses
             LEFT JOIN users ON users.id = travel_expenses.traveler_user_id
             LEFT JOIN users AS creators ON creators.id = travel_expenses.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = travel_expenses.bank_account_id
             WHERE travel_expenses.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$expense) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到出差費用']);
            exit;
        }

        return $expense;
    }

    private function users(): array
    {
        return Database::pdo()->query('SELECT id, name FROM users WHERE status = "active" ORDER BY name')->fetchAll();
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
        $total = 0.0;
        $advance = 0.0;
        $reimbursable = 0.0;
        foreach ($expenses as $expense) {
            if ($expense['payment_status'] === 'voided') {
                continue;
            }
            $total += (float) $expense['total_amount'];
            $advance += (float) $expense['advance_amount'];
            $reimbursable += (float) $expense['reimbursable_amount'];
        }

        return [
            'total' => $total,
            'advance' => $advance,
            'reimbursable' => $reimbursable,
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
