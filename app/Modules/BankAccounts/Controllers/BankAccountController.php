<?php

namespace App\Modules\BankAccounts\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class BankAccountController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('bank_accounts.view');

        $accounts = Database::pdo()->query(
            'SELECT bank_accounts.*,
                    COALESCE(transaction_totals.deposit_total, 0) AS deposit_total,
                    COALESCE(transaction_totals.withdrawal_total, 0) AS withdrawal_total,
                    bank_accounts.opening_balance
                      + COALESCE(transaction_totals.deposit_total, 0)
                      - COALESCE(transaction_totals.withdrawal_total, 0) AS current_balance
             FROM bank_accounts
             LEFT JOIN (
                SELECT bank_account_id,
                       SUM(CASE WHEN transaction_type IN ("deposit", "interest") THEN amount ELSE 0 END) AS deposit_total,
                       SUM(CASE WHEN transaction_type IN ("withdrawal", "transfer_to_petty_cash", "fee") THEN amount ELSE 0 END) AS withdrawal_total
                FROM bank_account_transactions
                GROUP BY bank_account_id
             ) AS transaction_totals ON transaction_totals.bank_account_id = bank_accounts.id
             ORDER BY bank_accounts.status, bank_accounts.bank_name, bank_accounts.account_no'
        )->fetchAll();

        $this->render('bank-accounts.index', [
            'title' => '銀行帳戶',
            'section' => '財務會計',
            'active' => 'bank-accounts',
            'accounts' => $accounts,
            'totals' => $this->totals($accounts),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('bank_accounts.manage');

        $this->render('bank-accounts.create', [
            'title' => '新增銀行帳戶',
            'section' => '財務會計',
            'active' => 'bank-accounts',
            'account' => [
                'bank_name' => '',
                'branch_name' => '',
                'account_name' => '',
                'account_no' => '',
                'currency' => 'TWD',
                'opening_balance' => '0',
                'notes' => '',
                'status' => 'active',
            ],
            'action' => '/bank-accounts',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('bank_accounts.manage');
        $this->validateAccount('/bank-accounts/create');

        try {
            Database::pdo()->prepare(
                'INSERT INTO bank_accounts
                 (bank_name, branch_name, account_name, account_no, currency, opening_balance, notes, status, created_by, created_at, updated_at)
                 VALUES (:bank_name, :branch_name, :account_name, :account_no, :currency, :opening_balance, :notes, :status, :created_by, :created_at, :updated_at)'
            )->execute($this->accountPayload());
        } catch (\PDOException) {
            $this->backWithInput('/bank-accounts/create', $_POST, '銀行帳戶儲存失敗，請確認帳號沒有重複。');
        }

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'bank_accounts', 'bank_accounts', $id);
        flash('success', '銀行帳戶已建立。');
        redirect('/bank-accounts');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('bank_accounts.manage');

        $this->render('bank-accounts.edit', [
            'title' => '編輯銀行帳戶',
            'section' => '財務會計',
            'active' => 'bank-accounts',
            'account' => $this->findAccount((int) $id),
            'action' => '/bank-accounts/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('bank_accounts.manage');
        $this->findAccount((int) $id);
        $this->validateAccount('/bank-accounts/' . $id . '/edit');

        $payload = $this->accountPayload(false);
        $payload['id'] = (int) $id;

        try {
            Database::pdo()->prepare(
                'UPDATE bank_accounts
                 SET bank_name = :bank_name,
                     branch_name = :branch_name,
                     account_name = :account_name,
                     account_no = :account_no,
                     currency = :currency,
                     opening_balance = :opening_balance,
                     notes = :notes,
                     status = :status,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($payload);
        } catch (\PDOException) {
            $this->backWithInput('/bank-accounts/' . $id . '/edit', $_POST, '銀行帳戶更新失敗，請確認帳號沒有重複。');
        }

        AuditLog::write('update', 'bank_accounts', 'bank_accounts', (int) $id);
        flash('success', '銀行帳戶已更新。');
        redirect('/bank-accounts');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('bank_accounts.manage');
        $account = $this->findAccount((int) $id);
        $status = $account['status'] === 'active' ? 'disabled' : 'active';

        Database::pdo()->prepare(
            'UPDATE bank_accounts SET status = :status, updated_at = :updated_at WHERE id = :id'
        )->execute([
            'status' => $status,
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('toggle', 'bank_accounts', 'bank_accounts', (int) $id, ['status' => $status]);
        flash('success', '銀行帳戶狀態已更新。');
        redirect('/bank-accounts');
    }

    private function validateAccount(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'bank_name' => '銀行名稱',
            'account_name' => '戶名',
            'account_no' => '帳號',
            'currency' => '幣別',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if ($this->amountValue('opening_balance') < 0) {
            $this->backWithInput($path, $_POST, '期初餘額不可小於 0。');
        }
    }

    private function accountPayload(bool $forInsert = true): array
    {
        $payload = [
            'bank_name' => trim((string) $_POST['bank_name']),
            'branch_name' => trim((string) ($_POST['branch_name'] ?? '')),
            'account_name' => trim((string) $_POST['account_name']),
            'account_no' => trim((string) $_POST['account_no']),
            'currency' => strtoupper(trim((string) ($_POST['currency'] ?? 'TWD'))) ?: 'TWD',
            'opening_balance' => $this->amountValue('opening_balance'),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'status' => ($_POST['status'] ?? '') === 'disabled' ? 'disabled' : 'active',
            'updated_at' => now(),
        ];

        if (!$forInsert) {
            return $payload;
        }

        return $payload + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
        ];
    }

    private function findAccount(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM bank_accounts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到銀行帳戶']);
            exit;
        }

        return $account;
    }

    private function totals(array $accounts): array
    {
        $opening = 0.0;
        $deposit = 0.0;
        $withdrawal = 0.0;
        $balance = 0.0;

        foreach ($accounts as $account) {
            if ($account['status'] !== 'active') {
                continue;
            }

            $opening += (float) $account['opening_balance'];
            $deposit += (float) $account['deposit_total'];
            $withdrawal += (float) $account['withdrawal_total'];
            $balance += (float) $account['current_balance'];
        }

        return compact('opening', 'deposit', 'withdrawal', 'balance');
    }

    private function amountValue(string $key): float
    {
        return round((float) ($_POST[$key] ?? 0), 2);
    }
}
