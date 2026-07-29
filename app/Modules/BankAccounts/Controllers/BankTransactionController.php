<?php

namespace App\Modules\BankAccounts\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class BankTransactionController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('bank_accounts.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $accountId = (int) ($_GET['bank_account_id'] ?? 0);

        $where = ['DATE_FORMAT(bank_account_transactions.transacted_on, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($accountId > 0) {
            $where[] = 'bank_account_transactions.bank_account_id = :bank_account_id';
            $params['bank_account_id'] = $accountId;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT bank_account_transactions.*, bank_accounts.bank_name, bank_accounts.account_no, bank_accounts.account_name,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM bank_account_transactions
             INNER JOIN bank_accounts ON bank_accounts.id = bank_account_transactions.bank_account_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = bank_account_transactions.accounting_voucher_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY bank_account_transactions.transacted_on DESC, bank_account_transactions.id DESC'
        );
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        $this->render('bank-accounts.transactions.index', [
            'title' => '銀行交易',
            'section' => '財務會計',
            'active' => 'bank-accounts',
            'transactions' => $transactions,
            'accounts' => $this->accounts(false),
            'month' => $month,
            'accountId' => $accountId,
            'totals' => $this->totals($transactions),
        ]);
    }

    public function reconciliation(): void
    {
        $this->requirePermission('bank_accounts.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $accountId = (int) ($_GET['bank_account_id'] ?? 0);
        $status = in_array(($_GET['status'] ?? ''), ['unreconciled', 'reconciled', 'ignored'], true)
            ? (string) $_GET['status']
            : '';

        $where = ['DATE_FORMAT(bank_account_transactions.transacted_on, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($accountId > 0) {
            $where[] = 'bank_account_transactions.bank_account_id = :bank_account_id';
            $params['bank_account_id'] = $accountId;
        }
        if ($status !== '') {
            $where[] = 'bank_account_transactions.reconciliation_status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT bank_account_transactions.*,
                    bank_accounts.bank_name,
                    bank_accounts.account_no,
                    bank_accounts.account_name,
                    users.name AS reconciled_by_name
             FROM bank_account_transactions
             INNER JOIN bank_accounts ON bank_accounts.id = bank_account_transactions.bank_account_id
             LEFT JOIN users ON users.id = bank_account_transactions.reconciled_by
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY bank_account_transactions.transacted_on, bank_account_transactions.id'
        );
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        $this->render('bank-accounts.transactions.reconciliation', [
            'title' => '銀行對帳',
            'section' => '財務會計',
            'active' => 'bank-accounts',
            'transactions' => $transactions,
            'accounts' => $this->accounts(false),
            'month' => $month,
            'accountId' => $accountId,
            'status' => $status,
            'totals' => $this->reconciliationTotals($transactions),
            'profile' => foundation_profile(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('bank_accounts.manage');

        $this->render('bank-accounts.transactions.create', [
            'title' => '新增銀行交易',
            'section' => '財務會計',
            'active' => 'bank-accounts',
            'transaction' => [
                'bank_account_id' => $_GET['bank_account_id'] ?? '',
                'transacted_on' => date('Y-m-d'),
                'transaction_type' => $_GET['type'] ?? 'deposit',
                'subject' => '',
                'amount' => '',
                'reference_no' => '',
                'counterparty' => '',
                'notes' => '',
            ],
            'accounts' => $this->accounts(true),
            'types' => $this->types(),
            'action' => '/bank-transactions',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('bank_accounts.manage');
        $this->validateTransaction('/bank-transactions/create');

        $account = $this->findActiveAccount((int) $_POST['bank_account_id']);
        $type = $this->typeValue();
        $subject = trim((string) $_POST['subject']);
        if ($type === 'transfer_to_petty_cash' && $subject === '') {
            $subject = '銀行撥補零用金';
        }

        try {
            Database::pdo()->beginTransaction();

            Database::pdo()->prepare(
                'INSERT INTO bank_account_transactions
                 (bank_account_id, transacted_on, transaction_type, subject, amount, reference_no, counterparty, notes, petty_cash_entry_id, created_by, created_at, updated_at)
                 VALUES (:bank_account_id, :transacted_on, :transaction_type, :subject, :amount, :reference_no, :counterparty, :notes, :petty_cash_entry_id, :created_by, :created_at, :updated_at)'
            )->execute([
                'bank_account_id' => (int) $_POST['bank_account_id'],
                'transacted_on' => $_POST['transacted_on'],
                'transaction_type' => $type,
                'subject' => $subject,
                'amount' => $this->amountValue(),
                'reference_no' => trim((string) ($_POST['reference_no'] ?? '')),
                'counterparty' => trim((string) ($_POST['counterparty'] ?? '')),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'petty_cash_entry_id' => null,
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $transactionId = (int) Database::pdo()->lastInsertId();

            if ($type === 'transfer_to_petty_cash') {
                $pettyCashId = $this->createPettyCashEntry($transactionId, $account, $subject);
                Database::pdo()->prepare(
                    'UPDATE bank_account_transactions
                     SET petty_cash_entry_id = :petty_cash_entry_id, updated_at = :updated_at
                     WHERE id = :id'
                )->execute([
                    'petty_cash_entry_id' => $pettyCashId,
                    'updated_at' => now(),
                    'id' => $transactionId,
                ]);
            }

            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/bank-transactions/create', $_POST, '銀行交易儲存失敗：' . $e->getMessage());
        }

        AuditLog::write('create', 'bank_accounts', 'bank_account_transactions', $transactionId);
        flash('success', $type === 'transfer_to_petty_cash' ? '銀行交易已建立，並已同步新增零用金收入。' : '銀行交易已建立。');
        redirect('/bank-transactions?month=' . substr((string) $_POST['transacted_on'], 0, 7));
    }

    public function updateReconciliation(string $id): void
    {
        $this->requirePermission('bank_accounts.manage');

        $transaction = $this->findTransaction((int) $id);
        $status = in_array(($_POST['reconciliation_status'] ?? ''), ['unreconciled', 'reconciled', 'ignored'], true)
            ? (string) $_POST['reconciliation_status']
            : 'unreconciled';
        $reconciledOn = trim((string) ($_POST['reconciled_on'] ?? ''));
        if ($status === 'reconciled' && $reconciledOn === '') {
            $reconciledOn = date('Y-m-d');
        }
        if ($reconciledOn !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $reconciledOn)) {
            $this->backWithInput('/bank-transactions/reconciliation?month=' . substr((string) $transaction['transacted_on'], 0, 7), $_POST, '對帳日期格式不正確。');
        }

        Database::pdo()->prepare(
            'UPDATE bank_account_transactions
             SET reconciliation_status = :reconciliation_status,
                 reconciled_on = :reconciled_on,
                 reconciliation_note = :reconciliation_note,
                 reconciled_by = :reconciled_by,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'reconciliation_status' => $status,
            'reconciled_on' => $status === 'reconciled' ? $reconciledOn : null,
            'reconciliation_note' => trim((string) ($_POST['reconciliation_note'] ?? '')),
            'reconciled_by' => $status === 'unreconciled' ? null : (auth()->user()['id'] ?? null),
            'updated_at' => now(),
            'id' => (int) $transaction['id'],
        ]);

        AuditLog::write('reconcile', 'bank_accounts', 'bank_account_transactions', (int) $transaction['id'], [
            'status' => $status,
        ]);
        flash('success', '銀行交易對帳狀態已更新。');
        redirect('/bank-transactions/reconciliation?month=' . substr((string) $transaction['transacted_on'], 0, 7) . '&bank_account_id=' . (int) $transaction['bank_account_id']);
    }

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $transaction = $this->findTransaction((int) $id);

        if (!empty($transaction['accounting_voucher_id'])) {
            flash('error', '此銀行交易已建立會計傳票。');
            redirect('/accounting/vouchers/' . $transaction['accounting_voucher_id']);
        }

        $bankAccount = $this->accountByCode('1100');
        $pettyCashAccount = $this->accountByCode('1110');
        $counterAccount = $this->bankTransactionCounterAccount($transaction);

        if (!$bankAccount || !$counterAccount || ($transaction['transaction_type'] === 'transfer_to_petty_cash' && !$pettyCashAccount)) {
            flash('error', '找不到銀行交易拋轉所需會計科目，請先確認 1100、1110 與對應收入/費用科目已建立。');
            redirect('/bank-transactions?month=' . substr((string) $transaction['transacted_on'], 0, 7));
        }

        $amount = round((float) $transaction['amount'], 2);
        if ($amount <= 0) {
            flash('error', '銀行交易金額需大於 0 才能建立會計傳票。');
            redirect('/bank-transactions?month=' . substr((string) $transaction['transacted_on'], 0, 7));
        }

        $summary = sprintf('銀行交易：%s', $transaction['subject']);
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
                'voucher_no' => $this->nextVoucherNo((string) $transaction['transacted_on']),
                'voucher_date' => (string) $transaction['transacted_on'],
                'source_type' => 'bank_account_transactions',
                'source_id' => (int) $transaction['id'],
                'summary' => $summary,
                'status' => 'draft',
                'notes' => trim("由銀行交易自動拋轉\n" . (string) ($transaction['notes'] ?? '')),
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

            if (in_array($transaction['transaction_type'], ['deposit', 'interest'], true)) {
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $bankAccount['id'], $summary, $amount, 0, 10);
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $counterAccount['id'], $summary, 0, $amount, 20);
            } elseif ($transaction['transaction_type'] === 'transfer_to_petty_cash') {
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $pettyCashAccount['id'], $summary, $amount, 0, 10);
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $bankAccount['id'], $summary, 0, $amount, 20);
            } else {
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $counterAccount['id'], $summary, $amount, 0, 10);
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $bankAccount['id'], $summary, 0, $amount, 20);
            }

            $pdo->prepare(
                'UPDATE bank_account_transactions
                 SET accounting_voucher_id = :voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $transaction['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/bank-transactions?month=' . substr((string) $transaction['transacted_on'], 0, 7));
        }

        AuditLog::write('create_voucher', 'bank_accounts', 'bank_account_transactions', (int) $transaction['id']);
        AuditLog::write('create', 'accounting', 'accounting_vouchers', $voucherId);
        flash('success', '已建立銀行交易草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
    }

    private function validateTransaction(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'bank_account_id' => '銀行帳戶',
            'transacted_on' => '日期',
            'transaction_type' => '交易類型',
            'amount' => '金額',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['transacted_on'])) {
            $this->backWithInput($path, $_POST, '日期格式不正確。');
        }

        if (!array_key_exists($this->typeValue(), $this->types())) {
            $this->backWithInput($path, $_POST, '交易類型不正確。');
        }

        if ($this->amountValue() <= 0) {
            $this->backWithInput($path, $_POST, '金額必須大於 0。');
        }

        $this->findActiveAccount((int) $_POST['bank_account_id']);
    }

    private function createPettyCashEntry(int $transactionId, array $account, string $subject): int
    {
        $item = $this->pettyCashTransferItem();
        $accountLabel = trim($account['bank_name'] . ' ' . $account['account_no']);
        $notes = trim((string) ($_POST['notes'] ?? ''));

        Database::pdo()->prepare(
            'INSERT INTO petty_cash_entries
             (occurred_on, item_type, petty_cash_item_id, bank_account_id, bank_account_transaction_id, item_name, amount, payment_to, receipt_no, notes, created_by, created_at, updated_at)
             VALUES (:occurred_on, "income", :petty_cash_item_id, :bank_account_id, :bank_account_transaction_id, :item_name, :amount, :payment_to, :receipt_no, :notes, :created_by, :created_at, :updated_at)'
        )->execute([
            'occurred_on' => $_POST['transacted_on'],
            'petty_cash_item_id' => $item['id'] ?? null,
            'bank_account_id' => (int) $account['id'],
            'bank_account_transaction_id' => $transactionId,
            'item_name' => $item['name'] ?? '銀行撥補零用金',
            'amount' => $this->amountValue(),
            'payment_to' => $accountLabel,
            'receipt_no' => trim((string) ($_POST['reference_no'] ?? '')),
            'notes' => trim($subject . ($notes !== '' ? "\n" . $notes : '')),
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pettyCashId = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'petty_cash', 'petty_cash_entries', $pettyCashId, [
            'source' => 'bank_account_transactions',
            'source_id' => $transactionId,
        ]);

        return $pettyCashId;
    }

    private function accounts(bool $activeOnly): array
    {
        $sql = 'SELECT * FROM bank_accounts';
        if ($activeOnly) {
            $sql .= ' WHERE status = "active"';
        }
        $sql .= ' ORDER BY status, bank_name, account_no';

        return Database::pdo()->query($sql)->fetchAll();
    }

    private function findActiveAccount(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM bank_accounts WHERE id = :id AND status = "active" LIMIT 1');
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            $this->backWithInput('/bank-transactions/create', $_POST, '請選擇有效的銀行帳戶。');
        }

        return $account;
    }

    private function findTransaction(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM bank_account_transactions WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$transaction) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到銀行交易']);
            exit;
        }

        return $transaction;
    }

    private function accountByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, code, name FROM accounting_accounts WHERE code = :code AND status = "active" LIMIT 1');
        $stmt->execute(['code' => $code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function bankTransactionCounterAccount(array $transaction): ?array
    {
        return match ($transaction['transaction_type']) {
            'interest' => $this->accountByCode('4300'),
            'deposit' => $this->accountByCode('4100'),
            'fee' => $this->accountByCode('5300'),
            'withdrawal' => $this->accountByCode('5300'),
            'transfer_to_petty_cash' => $this->accountByCode('1110'),
            default => null,
        };
    }

    private function insertVoucherLine(\PDOStatement $stmt, int $voucherId, int $accountId, string $description, float $debit, float $credit, int $sortOrder): void
    {
        $stmt->execute([
            'voucher_id' => $voucherId,
            'account_id' => $accountId,
            'description' => $description,
            'debit' => $debit,
            'credit' => $credit,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextVoucherNo(string $date): string
    {
        $prefix = 'V' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_vouchers WHERE voucher_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);

        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function pettyCashTransferItem(): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM petty_cash_items
             WHERE item_type = "income" AND name = "銀行撥補零用金"
             LIMIT 1'
        );
        $stmt->execute();
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        return $item ?: null;
    }

    private function totals(array $transactions): array
    {
        $deposit = 0.0;
        $withdrawal = 0.0;
        $pettyCash = 0.0;

        foreach ($transactions as $transaction) {
            $amount = (float) $transaction['amount'];
            if (in_array($transaction['transaction_type'], ['deposit', 'interest'], true)) {
                $deposit += $amount;
                continue;
            }

            $withdrawal += $amount;
            if ($transaction['transaction_type'] === 'transfer_to_petty_cash') {
                $pettyCash += $amount;
            }
        }

        return compact('deposit', 'withdrawal', 'pettyCash');
    }

    private function reconciliationTotals(array $transactions): array
    {
        $deposit = 0.0;
        $withdrawal = 0.0;
        $reconciled = 0;
        $unreconciled = 0;
        $ignored = 0;

        foreach ($transactions as $transaction) {
            $amount = (float) $transaction['amount'];
            if (in_array($transaction['transaction_type'], ['deposit', 'interest'], true)) {
                $deposit += $amount;
            } else {
                $withdrawal += $amount;
            }

            if ($transaction['reconciliation_status'] === 'reconciled') {
                $reconciled++;
            } elseif ($transaction['reconciliation_status'] === 'ignored') {
                $ignored++;
            } else {
                $unreconciled++;
            }
        }

        return compact('deposit', 'withdrawal', 'reconciled', 'unreconciled', 'ignored');
    }

    private function types(): array
    {
        return [
            'deposit' => '入帳',
            'withdrawal' => '提款 / 支出',
            'transfer_to_petty_cash' => '撥補零用金',
            'fee' => '銀行手續費',
            'interest' => '利息收入',
        ];
    }

    private function typeValue(): string
    {
        return (string) ($_POST['transaction_type'] ?? '');
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }
}
