<?php

namespace App\Modules\Accounting\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Domain\Accounting\VoucherBalance;
use App\Support\DateScope;
use PDO;

final class VoucherController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('accounting.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $scope = DateScope::normalize($_GET['scope'] ?? null);
        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $status = in_array(($_GET['status'] ?? ''), ['draft', 'posted', 'voided'], true) ? (string) $_GET['status'] : '';

        [$dateWhere, $params] = DateScope::condition('accounting_vouchers.voucher_date', $scope, $month, $year);
        $where = $dateWhere !== '' ? [$dateWhere] : [];
        if ($status !== '') {
            $where[] = 'accounting_vouchers.status = :status';
            $params['status'] = $status;
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $stmt = Database::pdo()->prepare(
            'SELECT accounting_vouchers.*,
                    users.name AS created_by_name,
                    COALESCE(SUM(accounting_voucher_lines.debit), 0) AS debit_total,
                    COALESCE(SUM(accounting_voucher_lines.credit), 0) AS credit_total
             FROM accounting_vouchers
             LEFT JOIN users ON users.id = accounting_vouchers.created_by
             LEFT JOIN accounting_voucher_lines ON accounting_voucher_lines.voucher_id = accounting_vouchers.id'
            . $whereSql . '
             GROUP BY accounting_vouchers.id
             ORDER BY accounting_vouchers.voucher_date DESC, accounting_vouchers.id DESC'
        );
        $stmt->execute($params);
        $vouchers = $stmt->fetchAll();

        $this->render('accounting.vouchers.index', [
            'title' => '會計傳票',
            'section' => '財務會計',
            'active' => 'accounting',
            'month' => $month,
            'scope' => $scope,
            'year' => $year,
            'status' => $status,
            'vouchers' => $vouchers,
            'totals' => $this->totals($vouchers),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('accounting.manage');

        $this->render('accounting.vouchers.create', [
            'title' => '新增會計傳票',
            'section' => '財務會計',
            'active' => 'accounting',
            'voucher' => [
                'voucher_no' => $this->nextVoucherNo(date('Y-m-d')),
                'voucher_date' => date('Y-m-d'),
                'summary' => '',
                'status' => 'draft',
                'source_type' => '',
                'source_id' => '',
                'notes' => '',
                'lines' => [
                    ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''],
                    ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''],
                ],
            ],
            'accounts' => $this->accounts(),
            'action' => '/accounting/vouchers',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('accounting.manage');
        $payload = $this->validatedPayload('/accounting/vouchers/create');

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO accounting_vouchers
                 (voucher_no, voucher_date, source_type, source_id, summary, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:voucher_no, :voucher_date, :source_type, :source_id, :summary, :status, :notes, :created_by, :created_at, :updated_at)'
            )->execute($payload['voucher'] + [
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id = (int) $pdo->lastInsertId();
            $this->replaceLines($id, $payload['lines']);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            $this->backWithInput('/accounting/vouchers/create', $_POST, '傳票建立失敗：' . $exception->getMessage());
        }

        AuditLog::write('create', 'accounting', 'accounting_vouchers', $id);
        flash('success', '會計傳票已建立。');
        redirect('/accounting/vouchers/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('accounting.view');

        $voucher = $this->findVoucher((int) $id);

        $lines = $this->lines((int) $id);

        $this->render('accounting.vouchers.show', [
            'title' => '會計傳票',
            'section' => '財務會計',
            'active' => 'accounting',
            'voucher' => $voucher,
            'lines' => $lines,
            'totals' => $this->lineTotals($lines),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $voucher = $this->findVoucher((int) $id);

        if ($voucher['status'] === 'posted') {
            flash('error', '已過帳傳票不可編輯，請先建立調整傳票。');
            redirect('/accounting/vouchers/' . $id);
        }

        $voucher['lines'] = $this->lines((int) $id);

        $this->render('accounting.vouchers.edit', [
            'title' => '編輯會計傳票',
            'section' => '財務會計',
            'active' => 'accounting',
            'voucher' => $voucher,
            'accounts' => $this->accounts(),
            'action' => '/accounting/vouchers/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $voucher = $this->findVoucher((int) $id);
        if ($voucher['status'] === 'posted') {
            flash('error', '已過帳傳票不可編輯，請先建立調整傳票。');
            redirect('/accounting/vouchers/' . $id);
        }

        $payload = $this->validatedPayload('/accounting/vouchers/' . $id . '/edit', (int) $id);
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE accounting_vouchers
                 SET voucher_no = :voucher_no,
                     voucher_date = :voucher_date,
                     source_type = :source_type,
                     source_id = :source_id,
                     summary = :summary,
                     status = :status,
                     notes = :notes,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($payload['voucher'] + [
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

            $this->replaceLines((int) $id, $payload['lines']);
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            $this->backWithInput('/accounting/vouchers/' . $id . '/edit', $_POST, '傳票更新失敗：' . $exception->getMessage());
        }

        AuditLog::write('update', 'accounting', 'accounting_vouchers', (int) $id);
        flash('success', '會計傳票已更新。');
        redirect('/accounting/vouchers/' . $id);
    }

    public function post(string $id): void
    {
        $this->requirePermission('accounting.post');
        $voucher = $this->findVoucher((int) $id);
        if ($voucher['status'] !== 'draft') {
            flash('error', '只有草稿傳票可以過帳。');
            redirect('/accounting/vouchers/' . $id);
        }

        if (!VoucherBalance::isBalanced($this->lines((int) $id))) {
            flash('error', '借貸金額不平衡，無法過帳。');
            redirect('/accounting/vouchers/' . $id);
        }

        Database::pdo()->prepare(
            'UPDATE accounting_vouchers
             SET status = "posted", posted_by = :posted_by, posted_at = :posted_at, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'posted_by' => auth()->user()['id'] ?? null,
            'posted_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('post', 'accounting', 'accounting_vouchers', (int) $id);
        flash('success', '會計傳票已過帳。');
        redirect('/accounting/vouchers/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $this->findVoucher((int) $id);

        Database::pdo()->prepare('UPDATE accounting_vouchers SET status = "voided", updated_at = :updated_at WHERE id = :id')
            ->execute([
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('void', 'accounting', 'accounting_vouchers', (int) $id);
        flash('success', '會計傳票已作廢。');
        redirect('/accounting/vouchers/' . $id);
    }

    private function validatedPayload(string $path, ?int $ignoreId = null): array
    {
        $voucherNo = trim((string) ($_POST['voucher_no'] ?? ''));
        $voucherDate = trim((string) ($_POST['voucher_date'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));

        if ($voucherNo === '' || $voucherDate === '' || $summary === '') {
            $this->backWithInput($path, $_POST, '請填寫傳票號碼、日期與摘要。');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $voucherDate)) {
            $this->backWithInput($path, $_POST, '傳票日期格式不正確。');
        }

        $sql = 'SELECT id FROM accounting_vouchers WHERE voucher_no = :voucher_no';
        $params = ['voucher_no' => $voucherNo];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            $this->backWithInput($path, $_POST, '傳票號碼已存在。');
        }

        $lines = $this->validatedLines($path);
        if (count($lines) < 2 || !VoucherBalance::isBalanced($lines)) {
            $this->backWithInput($path, $_POST, '傳票至少需要兩筆分錄，且借方與貸方金額必須相等。');
        }

        $status = in_array($_POST['status'] ?? '', ['draft', 'voided'], true) ? (string) $_POST['status'] : 'draft';
        $sourceId = (int) ($_POST['source_id'] ?? 0);

        return [
            'voucher' => [
                'voucher_no' => $voucherNo,
                'voucher_date' => $voucherDate,
                'source_type' => trim((string) ($_POST['source_type'] ?? '')),
                'source_id' => $sourceId > 0 ? $sourceId : null,
                'summary' => $summary,
                'status' => $status,
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ],
            'lines' => $lines,
        ];
    }

    private function validatedLines(string $path): array
    {
        $postedLines = $_POST['lines'] ?? [];
        if (!is_array($postedLines)) {
            $this->backWithInput($path, $_POST, '分錄資料格式不正確。');
        }

        $validAccounts = array_flip(array_map('intval', array_column($this->accounts(), 'id')));
        $lines = [];
        foreach ($postedLines as $line) {
            if (!is_array($line)) {
                continue;
            }

            $accountId = (int) ($line['account_id'] ?? 0);
            $description = trim((string) ($line['description'] ?? ''));
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($accountId <= 0 && $description === '' && $debit <= 0 && $credit <= 0) {
                continue;
            }

            if (!isset($validAccounts[$accountId])) {
                $this->backWithInput($path, $_POST, '分錄含有無效的會計科目。');
            }
            if (!VoucherBalance::lineIsValid($debit, $credit)) {
                $this->backWithInput($path, $_POST, '每筆分錄只能填借方或貸方其中一邊，且金額需大於 0。');
            }

            $lines[] = [
                'account_id' => $accountId,
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return $lines;
    }

    private function replaceLines(int $voucherId, array $lines): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM accounting_voucher_lines WHERE voucher_id = :voucher_id')
            ->execute(['voucher_id' => $voucherId]);

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
    }

    private function findVoucher(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT accounting_vouchers.*, users.name AS created_by_name, posters.name AS posted_by_name
             FROM accounting_vouchers
             LEFT JOIN users ON users.id = accounting_vouchers.created_by
             LEFT JOIN users AS posters ON posters.id = accounting_vouchers.posted_by
             WHERE accounting_vouchers.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$voucher) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到會計傳票']);
            exit;
        }

        return $voucher;
    }

    private function lines(int $voucherId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT accounting_voucher_lines.*, accounting_accounts.code, accounting_accounts.name, accounting_accounts.account_type
             FROM accounting_voucher_lines
             INNER JOIN accounting_accounts ON accounting_accounts.id = accounting_voucher_lines.account_id
             WHERE accounting_voucher_lines.voucher_id = :voucher_id
             ORDER BY accounting_voucher_lines.sort_order, accounting_voucher_lines.id'
        );
        $stmt->execute(['voucher_id' => $voucherId]);
        return $stmt->fetchAll();
    }

    private function accounts(): array
    {
        return Database::pdo()->query(
            'SELECT id, code, name, account_type
             FROM accounting_accounts
             WHERE status = "active"
             ORDER BY sort_order, code'
        )->fetchAll();
    }

    private function nextVoucherNo(string $date): string
    {
        $prefix = 'V' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_vouchers WHERE voucher_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);
        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function lineTotals(array $lines): array
    {
        return VoucherBalance::totals($lines);
    }

    private function totals(array $vouchers): array
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($vouchers as $voucher) {
            if ($voucher['status'] === 'voided') {
                continue;
            }
            $debit += (float) $voucher['debit_total'];
            $credit += (float) $voucher['credit_total'];
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $debit - $credit,
        ];
    }
}
