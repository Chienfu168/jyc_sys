<?php

namespace App\Modules\Donations\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class DonationController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('donations.view');

        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $receiptStatus = in_array(($_GET['receipt_status'] ?? ''), ['not_required', 'pending', 'issued', 'voided'], true)
            ? (string) $_GET['receipt_status']
            : '';
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $where = ['YEAR(donations.donated_at) = :year'];
        $params = ['year' => $year];
        if ($receiptStatus !== '') {
            $where[] = 'donations.receipt_status = :receipt_status';
            $params['receipt_status'] = $receiptStatus;
        }
        if ($keyword !== '') {
            $where[] = '(donors.name LIKE :keyword OR donations.receipt_no LIKE :keyword OR donations.project_name LIKE :keyword OR donations.payment_method LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT donations.*,
                    donors.name AS donor_name,
                    donors.receipt_title,
                    donors.tax_id,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = donations.accounting_voucher_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY donations.donated_at DESC, donations.id DESC'
        );
        $stmt->execute($params);
        $donations = $stmt->fetchAll();

        $this->render('donations.index', [
            'title' => '捐款紀錄',
            'section' => '財務會計',
            'active' => 'donations',
            'donations' => $donations,
            'year' => $year,
            'receiptStatus' => $receiptStatus,
            'keyword' => $keyword,
            'summary' => $this->summary($donations),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('donations.manage');

        $this->render('donations.create', [
            'title' => '新增捐款',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => [
                'donor_id' => (int) ($_GET['donor_id'] ?? 0),
                'donated_at' => date('Y-m-d'),
                'amount' => '',
                'payment_method' => '',
                'receipt_no' => '',
                'receipt_status' => 'pending',
                'project_name' => '',
                'notes' => '',
            ],
            'donors' => $this->donors(),
            'action' => '/donations',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('donations.manage');
        $this->validateDonation('/donations/create');

        Database::pdo()->prepare(
            'INSERT INTO donations
             (donor_id, donated_at, amount, payment_method, receipt_no, receipt_status, project_name, notes, created_by, created_at, updated_at)
             VALUES
             (:donor_id, :donated_at, :amount, :payment_method, :receipt_no, :receipt_status, :project_name, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'donations', 'donations', $id);
        flash('success', '捐款紀錄已建立。');
        redirect('/donations/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('donations.view');

        $this->render('donations.show', [
            'title' => '捐款紀錄',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => $this->findDonation((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('donations.manage');

        $this->render('donations.edit', [
            'title' => '編輯捐款',
            'section' => '財務會計',
            'active' => 'donations',
            'donation' => $this->findDonation((int) $id),
            'donors' => $this->donors(),
            'action' => '/donations/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('donations.manage');
        $donation = $this->findDonation((int) $id);

        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的捐款紀錄不可直接編輯，請以調整傳票處理。');
            redirect('/donations/' . $id);
        }

        $this->validateDonation('/donations/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE donations
             SET donor_id = :donor_id,
                 donated_at = :donated_at,
                 amount = :amount,
                 payment_method = :payment_method,
                 receipt_no = :receipt_no,
                 receipt_status = :receipt_status,
                 project_name = :project_name,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'donations', 'donations', (int) $id);
        flash('success', '捐款紀錄已更新。');
        redirect('/donations/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('donations.manage');
        $donation = $this->findDonation((int) $id);

        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的捐款紀錄不可直接作廢，請先建立調整傳票。');
            redirect('/donations/' . $id);
        }

        Database::pdo()->prepare('UPDATE donations SET receipt_status = "voided", updated_at = :updated_at WHERE id = :id')
            ->execute([
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('void', 'donations', 'donations', (int) $id);
        flash('success', '捐款紀錄已作廢。');
        redirect('/donations/' . $id);
    }

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $donation = $this->findDonation((int) $id);

        if ($donation['receipt_status'] === 'voided') {
            flash('error', '已作廢的捐款不可建立會計傳票。');
            redirect('/donations/' . $id);
        }
        if (!empty($donation['accounting_voucher_id'])) {
            flash('error', '此捐款已建立會計傳票。');
            redirect('/accounting/vouchers/' . $donation['accounting_voucher_id']);
        }

        if ($existing = $this->existingVoucherForSource((int) $donation['id'])) {
            Database::pdo()->prepare('UPDATE donations SET accounting_voucher_id = :voucher_id, updated_at = :updated_at WHERE id = :id')
                ->execute([
                    'voucher_id' => (int) $existing['id'],
                    'updated_at' => now(),
                    'id' => (int) $donation['id'],
                ]);
            flash('success', '已連結既有捐款會計傳票。');
            redirect('/accounting/vouchers/' . $existing['id']);
        }

        $cashAccount = $this->accountByCode('1100');
        $incomeAccount = $this->accountByCode('4100');
        if (!$cashAccount || !$incomeAccount) {
            flash('error', '找不到捐款拋轉所需會計科目，請先確認 1100 與 4100 已建立。');
            redirect('/donations/' . $id);
        }

        $amount = round((float) $donation['amount'], 2);
        $summary = '捐款收入：' . $donation['donor_name'];
        $voucherId = 0;

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO accounting_vouchers
                 (voucher_no, voucher_date, source_type, source_id, summary, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:voucher_no, :voucher_date, "donations", :source_id, :summary, "draft", :notes, :created_by, :created_at, :updated_at)'
            )->execute([
                'voucher_no' => $this->nextVoucherNo((string) $donation['donated_at']),
                'voucher_date' => $donation['donated_at'],
                'source_id' => (int) $donation['id'],
                'summary' => $summary,
                'notes' => trim('由捐款紀錄自動拋轉' . "\n" . ($donation['notes'] ?? '')),
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
            $this->insertVoucherLine($lineStmt, $voucherId, (int) $cashAccount['id'], $summary, $amount, 0, 10);
            $this->insertVoucherLine($lineStmt, $voucherId, (int) $incomeAccount['id'], $summary, 0, $amount, 20);

            $pdo->prepare(
                'UPDATE donations
                 SET accounting_voucher_id = :accounting_voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'accounting_voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $donation['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/donations/' . $id);
        }

        AuditLog::write('create_voucher', 'donations', 'accounting_vouchers', $voucherId, [
            'source' => 'donations',
            'source_id' => (int) $donation['id'],
        ]);
        flash('success', '已建立捐款草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
    }

    private function validateDonation(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'donor_id' => '捐款人',
            'donated_at' => '捐款日期',
            'amount' => '金額',
            'payment_method' => '捐款方式',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['donated_at'])) {
            $this->backWithInput($path, $_POST, '捐款日期格式不正確。');
        }
        if ($this->amountValue() <= 0) {
            $this->backWithInput($path, $_POST, '金額必須大於 0。');
        }
        if (!$this->donorExists((int) ($_POST['donor_id'] ?? 0))) {
            $this->backWithInput($path, $_POST, '選擇的捐款人不存在。');
        }
        if (!in_array($_POST['receipt_status'] ?? '', ['not_required', 'pending', 'issued', 'voided'], true)) {
            $this->backWithInput($path, $_POST, '收據狀態不正確。');
        }
    }

    private function payload(): array
    {
        return [
            'donor_id' => (int) $_POST['donor_id'],
            'donated_at' => (string) $_POST['donated_at'],
            'amount' => $this->amountValue(),
            'payment_method' => trim((string) $_POST['payment_method']),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'receipt_status' => (string) ($_POST['receipt_status'] ?? 'pending'),
            'project_name' => trim((string) ($_POST['project_name'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findDonation(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT donations.*,
                    donors.name AS donor_name,
                    donors.receipt_title,
                    donors.tax_id,
                    donors.phone AS donor_phone,
                    donors.email AS donor_email,
                    donors.address AS donor_address,
                    users.name AS created_by_name,
                    accounting_vouchers.voucher_no,
                    accounting_vouchers.status AS voucher_status
             FROM donations
             INNER JOIN donors ON donors.id = donations.donor_id
             LEFT JOIN users ON users.id = donations.created_by
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = donations.accounting_voucher_id
             WHERE donations.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donation) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到捐款紀錄']);
            exit;
        }

        return $donation;
    }

    private function donors(): array
    {
        return Database::pdo()->query(
            'SELECT id, donor_type, name, receipt_title, tax_id, status
             FROM donors
             ORDER BY FIELD(status, "active", "archived"), name'
        )->fetchAll();
    }

    private function donorExists(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM donors WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    private function accountByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, code, name FROM accounting_accounts WHERE code = :code AND status = "active" LIMIT 1');
        $stmt->execute(['code' => $code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function existingVoucherForSource(int $donationId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, voucher_no
             FROM accounting_vouchers
             WHERE source_type = "donations" AND source_id = :source_id
             LIMIT 1'
        );
        $stmt->execute(['source_id' => $donationId]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        return $voucher ?: null;
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

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }

    private function summary(array $donations): array
    {
        $valid = array_values(array_filter($donations, static fn (array $donation): bool => $donation['receipt_status'] !== 'voided'));

        return [
            'count' => count($valid),
            'amount' => array_sum(array_map(static fn (array $donation): float => (float) $donation['amount'], $valid)),
            'pending_receipts' => count(array_filter($valid, static fn (array $donation): bool => $donation['receipt_status'] === 'pending')),
        ];
    }
}
