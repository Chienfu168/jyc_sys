<?php

namespace App\Modules\PettyCash\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\ImageCompressor;
use App\Core\Validator;
use App\Domain\PettyCash\PettyCashReport;
use App\Support\DateScope;
use PDO;

final class PettyCashController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('petty_cash.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $scope = DateScope::normalize($_GET['scope'] ?? null);
        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }

        [$dateWhere, $params] = DateScope::condition('petty_cash_entries.occurred_on', $scope, $month, $year);
        $stmt = Database::pdo()->prepare(
            'SELECT petty_cash_entries.*, users.name AS created_by_name,
                    bank_accounts.bank_name, bank_accounts.account_no,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM petty_cash_entries
             LEFT JOIN users ON users.id = petty_cash_entries.created_by
             LEFT JOIN bank_accounts ON bank_accounts.id = petty_cash_entries.bank_account_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = petty_cash_entries.accounting_voucher_id'
            . ($dateWhere !== '' ? ' WHERE ' . $dateWhere : '') . '
             ORDER BY petty_cash_entries.occurred_on DESC, petty_cash_entries.id DESC'
        );
        $stmt->execute($params);
        $entries = $stmt->fetchAll();

        $this->render('petty-cash.index', [
            'title' => '零用金',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entries' => $entries,
            'month' => $month,
            'scope' => $scope,
            'year' => $year,
            'totals' => $this->totals($entries),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('petty_cash.manage');

        $this->render('petty-cash.create', [
            'title' => '新增零用金紀錄',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entry' => [
                'occurred_on' => date('Y-m-d'),
                'item_type' => 'expense',
                'petty_cash_item_id' => '',
                'project_id' => '',
                'item_name' => '',
                'amount' => '',
                'payment_to' => '',
                'receipt_no' => '',
                'approval_status' => 'draft',
                'notes' => '',
                'created_by_name' => auth()->user()['name'] ?? '',
            ],
            'items' => $this->items(),
            'projects' => $this->projects(),
            'action' => '/petty-cash',
        ]);
    }

    /** 手機快速記帳表單:精簡欄位、可拍照上傳憑證,先存草稿,之後可在電腦上完善。 */
    public function quick(): void
    {
        $this->requirePermission('petty_cash.manage');

        $this->render('petty-cash.quick', [
            'title' => '零用金快速記帳',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'items' => $this->items(),
            'today' => date('Y-m-d'),
            'recent' => $this->recentQuickEntries(),
        ]);
    }

    /** 儲存快速記帳:建立草稿紀錄並存入壓縮後的憑證照片。 */
    public function quickStore(): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->validateEntry('/petty-cash/quick');

        $item = $this->selectedItem();
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        if ($item && $itemName === '') {
            $itemName = $item['name'];
        }

        Database::pdo()->prepare(
            'INSERT INTO petty_cash_entries
             (occurred_on, item_type, petty_cash_item_id, project_id, item_name, amount, payment_to, receipt_no, notes, approval_status, created_by, created_at, updated_at)
             VALUES (:occurred_on, :item_type, :petty_cash_item_id, NULL, :item_name, :amount, :payment_to, :receipt_no, :notes, "draft", :created_by, :created_at, :updated_at)'
        )->execute([
            'occurred_on' => $_POST['occurred_on'],
            'item_type' => $item['item_type'] ?? $this->typeValue(),
            'petty_cash_item_id' => $item['id'] ?? null,
            'item_name' => $itemName,
            'amount' => $this->amountValue(),
            'payment_to' => trim((string) ($_POST['payment_to'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        $stored = $this->storeUploadedReceipts($id);

        AuditLog::write('quick_create', 'petty_cash', 'petty_cash_entries', $id);
        flash('success', '已快速記錄一筆零用金' . ($stored > 0 ? ('(含 ' . $stored . ' 張憑證)') : '') . ',可稍後於電腦上完善。');
        redirect('/petty-cash/quick');
    }

    public function uploadAttachment(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $entry = $this->findEntry((int) $id);

        $stored = $this->storeUploadedReceipts((int) $entry['id']);
        if ($stored === 0) {
            $this->backWithInput('/petty-cash/' . $id, [], '沒有可上傳的憑證,或檔案格式不支援。');
        }

        flash('success', '已上傳 ' . $stored . ' 張憑證。');
        redirect('/petty-cash/' . $id);
    }

    public function downloadAttachment(string $id, string $fileId): void
    {
        $this->requirePermission('petty_cash.view');
        $this->findEntry((int) $id);
        $file = $this->findAttachment((int) $id, (int) $fileId);
        $path = storage_path((string) $file['stored_path']);

        if (!$this->fileInStore($path) || !is_file($path)) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到憑證檔案']);
            exit;
        }

        AuditLog::write('download', 'petty_cash', 'petty_cash_attachments', (int) $file['id']);
        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode((string) $file['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function deleteAttachment(string $id, string $fileId): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->findEntry((int) $id);
        $file = $this->findAttachment((int) $id, (int) $fileId);

        $path = storage_path((string) $file['stored_path']);
        if ($this->fileInStore($path) && is_file($path)) {
            @unlink($path);
        }
        Database::pdo()->prepare('DELETE FROM petty_cash_attachments WHERE id = :id')->execute(['id' => (int) $file['id']]);

        AuditLog::write('delete', 'petty_cash', 'petty_cash_attachments', (int) $file['id']);
        flash('success', '憑證已刪除。');
        redirect('/petty-cash/' . $id);
    }

    public function report(): void
    {
        $this->requirePermission('petty_cash.view');

        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }

        $month = preg_match('/^(0[1-9]|1[0-2])$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : '';
        $mode = ($_GET['mode'] ?? '') === 'detail' ? 'detail' : 'summary';

        [$dateWhere, $params] = $this->dateScope($year, $month);

        $entriesStmt = Database::pdo()->prepare(
            "SELECT petty_cash_entries.*, users.name AS created_by_name
             FROM petty_cash_entries
             LEFT JOIN users ON users.id = petty_cash_entries.created_by
             WHERE {$dateWhere}
             ORDER BY petty_cash_entries.occurred_on, petty_cash_entries.id"
        );
        $entriesStmt->execute($params);
        $entries = $entriesStmt->fetchAll();

        $summaryStmt = Database::pdo()->prepare(
            "SELECT item_type, item_name, COUNT(*) AS entry_count, SUM(amount) AS subtotal
             FROM petty_cash_entries
             WHERE {$dateWhere}
             GROUP BY item_type, item_name
             ORDER BY item_type, subtotal DESC, item_name"
        );
        $summaryStmt->execute($params);
        $summary = $summaryStmt->fetchAll();

        $monthlyStmt = Database::pdo()->prepare(
            'SELECT DATE_FORMAT(occurred_on, "%Y-%m") AS report_month,
                    SUM(CASE WHEN item_type = "income" THEN amount ELSE 0 END) AS income_total,
                    SUM(CASE WHEN item_type = "expense" THEN amount ELSE 0 END) AS expense_total
             FROM petty_cash_entries
             WHERE YEAR(occurred_on) = :year
             GROUP BY DATE_FORMAT(occurred_on, "%Y-%m")
             ORDER BY report_month'
        );
        $monthlyStmt->execute(['year' => $year]);

        $totals = $this->totals($entries);
        $summary = PettyCashReport::summaryWithRatios($summary, $totals['income'], $totals['expense']);

        // 年度期初餘額與結轉:期末 = 期初 + 全年收入 − 全年支出(不受月份篩選影響)。
        $annualStmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(CASE WHEN item_type = "income" THEN amount ELSE 0 END), 0) AS income,
                    COALESCE(SUM(CASE WHEN item_type = "expense" THEN amount ELSE 0 END), 0) AS expense
             FROM petty_cash_entries
             WHERE YEAR(occurred_on) = :year'
        );
        $annualStmt->execute(['year' => $year]);
        $annual = $annualStmt->fetch() ?: ['income' => 0, 'expense' => 0];
        $openingBalance = $this->openingBalance('petty_cash', $year);
        $carry = \App\Domain\OpeningBalances\OpeningBalanceLedger::summary(
            $openingBalance,
            (float) $annual['income'],
            (float) $annual['expense']
        );

        $this->render('petty-cash.report', [
            'title' => '零用金統計表',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'year' => $year,
            'month' => $month,
            'mode' => $mode,
            'entries' => $entries,
            'summary' => $summary,
            'monthly' => $monthlyStmt->fetchAll(),
            'totals' => $totals,
            'expenseTotal' => $totals['expense'],
            'incomeTotal' => $totals['income'],
            'openingBalance' => $openingBalance,
            'carry' => $carry,
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->validateEntry('/petty-cash/create');

        $item = $this->selectedItem();
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        if ($item && $itemName === '') {
            $itemName = $item['name'];
        }

        Database::pdo()->prepare(
            'INSERT INTO petty_cash_entries
             (occurred_on, item_type, petty_cash_item_id, project_id, item_name, amount, payment_to, receipt_no, notes, approval_status, created_by, created_at, updated_at)
             VALUES (:occurred_on, :item_type, :petty_cash_item_id, :project_id, :item_name, :amount, :payment_to, :receipt_no, :notes, "draft", :created_by, :created_at, :updated_at)'
        )->execute([
            'occurred_on' => $_POST['occurred_on'],
            'item_type' => $item['item_type'] ?? $this->typeValue(),
            'petty_cash_item_id' => $item['id'] ?? null,
            'project_id' => $this->projectId(),
            'item_name' => $itemName,
            'amount' => $this->amountValue(),
            'payment_to' => trim((string) ($_POST['payment_to'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'petty_cash', 'petty_cash_entries', $id);
        flash('success', '零用金紀錄已建立');
        redirect('/petty-cash?month=' . substr((string) $_POST['occurred_on'], 0, 7));
    }

    public function show(string $id): void
    {
        $this->requirePermission('petty_cash.view');

        $this->render('petty-cash.show', [
            'title' => '零用金明細',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entry' => $this->findEntry((int) $id),
            'attachments' => $this->attachments((int) $id),
            'approvalHistory' => ApprovalFlow::history('petty_cash', 'petty_cash_entries', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $entry = $this->findEntry((int) $id);

        $this->render('petty-cash.edit', [
            'title' => '編輯零用金紀錄',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entry' => $entry,
            'items' => $this->items(),
            'projects' => $this->projects(),
            'action' => '/petty-cash/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->findEntry((int) $id);
        $this->validateEntry('/petty-cash/' . $id . '/edit');

        $item = $this->selectedItem();
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        if ($item && $itemName === '') {
            $itemName = $item['name'];
        }

        Database::pdo()->prepare(
            'UPDATE petty_cash_entries
             SET occurred_on = :occurred_on,
                 item_type = :item_type,
                 petty_cash_item_id = :petty_cash_item_id,
                 project_id = :project_id,
                 item_name = :item_name,
                 amount = :amount,
                 payment_to = :payment_to,
                 receipt_no = :receipt_no,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'occurred_on' => $_POST['occurred_on'],
            'item_type' => $item['item_type'] ?? $this->typeValue(),
            'petty_cash_item_id' => $item['id'] ?? null,
            'project_id' => $this->projectId(),
            'item_name' => $itemName,
            'amount' => $this->amountValue(),
            'payment_to' => trim((string) ($_POST['payment_to'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'petty_cash', 'petty_cash_entries', (int) $id);
        flash('success', '零用金紀錄已更新');
        redirect('/petty-cash?month=' . substr((string) $_POST['occurred_on'], 0, 7));
    }

    /**
     * 硬刪除零用金明細。已建立會計傳票或已連結銀行交易者不可刪除,
     * 避免破壞帳務/對帳關聯。刪除為不可復原動作,僅限具 petty_cash.delete 權限者。
     */
    public function destroy(string $id): void
    {
        $entry = $this->findEntry((int) $id);
        $this->requireManageOrOwner('petty_cash.manage', $entry['created_by'] ?? null);

        if (!empty($entry['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的零用金紀錄不可直接刪除，請先處理相關傳票。');
            redirect('/petty-cash/' . $id);
        }

        if (!empty($entry['bank_account_transaction_id'])) {
            flash('error', '已連結銀行交易的零用金紀錄不可直接刪除，請先解除銀行交易關聯。');
            redirect('/petty-cash/' . $id);
        }

        // 先清除憑證實體檔(資料列由外鍵串聯刪除)。
        foreach ($this->attachments((int) $id) as $file) {
            $path = storage_path((string) $file['stored_path']);
            if ($this->fileInStore($path) && is_file($path)) {
                @unlink($path);
            }
        }

        Database::pdo()->prepare('DELETE FROM petty_cash_entries WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'petty_cash', 'petty_cash_entries', (int) $id, [
            'item_name' => $entry['item_name'] ?? null,
            'amount' => $entry['amount'] ?? null,
        ]);
        flash('success', '零用金紀錄已刪除。');
        redirect('/petty-cash?month=' . substr((string) $entry['occurred_on'], 0, 7));
    }

    public function submit(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $entry = $this->findEntry((int) $id);

        if (!empty($entry['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的零用金紀錄不可重新送審。');
            redirect('/petty-cash/' . $id);
        }

        ApprovalFlow::submit('petty_cash', 'petty_cash_entries', (int) $entry['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE petty_cash_entries
             SET approval_status = "submitted",
                 submitted_at = :submitted_at,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'submitted_at' => now(),
            'updated_at' => now(),
            'id' => (int) $entry['id'],
        ]);

        AuditLog::write('submit', 'petty_cash', 'petty_cash_entries', (int) $entry['id']);
        flash('success', '零用金紀錄已送審。');
        redirect('/petty-cash/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('petty_cash.approve');
        $this->review((int) $id, 'approved', '零用金紀錄已核准。');
    }

    public function reject(string $id): void
    {
        $this->requirePermission('petty_cash.approve');
        $this->review((int) $id, 'rejected', '零用金紀錄已退回。');
    }

    public function createVoucher(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $entry = $this->findEntry((int) $id);

        if (!empty($entry['accounting_voucher_id'])) {
            flash('error', '此零用金紀錄已建立會計傳票。');
            redirect('/accounting/vouchers/' . $entry['accounting_voucher_id']);
        }

        if (($entry['approval_status'] ?? '') !== 'approved') {
            flash('error', '只有已核准的零用金紀錄可以拋轉會計傳票。');
            redirect('/petty-cash/' . $id);
        }

        $pettyCashAccount = $this->accountByCode('1110');
        $cashAccount = $this->accountByCode('1100');
        $counterAccount = $entry['item_type'] === 'income'
            ? $this->pettyCashIncomeAccount($entry)
            : $this->pettyCashExpenseAccount($entry);

        if (!$pettyCashAccount || !$cashAccount || !$counterAccount) {
            flash('error', '找不到零用金拋轉所需會計科目，請先確認 1110、1100 與費用科目已建立。');
            redirect('/petty-cash?month=' . substr((string) $entry['occurred_on'], 0, 7));
        }

        $amount = round((float) $entry['amount'], 2);
        if ($amount <= 0) {
            flash('error', '零用金金額需大於 0 才能建立會計傳票。');
            redirect('/petty-cash?month=' . substr((string) $entry['occurred_on'], 0, 7));
        }

        $summary = sprintf('%s：%s', $entry['item_type'] === 'income' ? '零用金收入' : '零用金支出', $entry['item_name']);
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
                'voucher_no' => $this->nextVoucherNo((string) $entry['occurred_on']),
                'voucher_date' => (string) $entry['occurred_on'],
                'source_type' => 'petty_cash_entries',
                'source_id' => (int) $entry['id'],
                'summary' => $summary,
                'status' => 'draft',
                'notes' => trim("由零用金紀錄自動拋轉\n" . (string) ($entry['notes'] ?? '')),
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

            if ($entry['item_type'] === 'income') {
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $pettyCashAccount['id'], $summary, $amount, 0, 10);
                $creditAccount = $this->isPettyCashTransfer($entry) ? $cashAccount : $counterAccount;
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $creditAccount['id'], $summary, 0, $amount, 20);
            } else {
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $counterAccount['id'], $summary, $amount, 0, 10);
                $this->insertVoucherLine($lineStmt, $voucherId, (int) $pettyCashAccount['id'], $summary, 0, $amount, 20);
            }

            $pdo->prepare(
                'UPDATE petty_cash_entries
                 SET accounting_voucher_id = :voucher_id, updated_at = :updated_at
                 WHERE id = :id'
            )->execute([
                'voucher_id' => $voucherId,
                'updated_at' => now(),
                'id' => (int) $entry['id'],
            ]);

            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            flash('error', '會計傳票建立失敗：' . $exception->getMessage());
            redirect('/petty-cash?month=' . substr((string) $entry['occurred_on'], 0, 7));
        }

        AuditLog::write('create_voucher', 'petty_cash', 'petty_cash_entries', (int) $entry['id']);
        AuditLog::write('create', 'accounting', 'accounting_vouchers', $voucherId);
        flash('success', '已建立零用金草稿會計傳票，請檢查後過帳。');
        redirect('/accounting/vouchers/' . $voucherId);
    }

    private function review(int $id, string $approvalStatus, string $message): void
    {
        $entry = $this->findEntry($id);
        if (!empty($entry['accounting_voucher_id'])) {
            flash('error', '已建立會計傳票的零用金紀錄不可變更簽核狀態。');
            redirect('/petty-cash/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        ApprovalFlow::review('petty_cash', 'petty_cash_entries', $id, $approvalStatus, $notes);
        Database::pdo()->prepare(
            'UPDATE petty_cash_entries
             SET approval_status = :approval_status,
                 reviewed_by = :reviewed_by,
                 reviewed_at = :reviewed_at,
                 review_notes = :review_notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'approval_status' => $approvalStatus,
            'reviewed_by' => auth()->user()['id'] ?? null,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'updated_at' => now(),
            'id' => $id,
        ]);

        AuditLog::write($approvalStatus, 'petty_cash', 'petty_cash_entries', $id);
        flash('success', $message);
        redirect('/petty-cash/' . $id);
    }

    /** 查詢某年度的零用金期初餘額;未設定回傳 0。 */
    private function openingBalance(string $module, int $year): float
    {
        $stmt = Database::pdo()->prepare(
            'SELECT opening_balance FROM opening_balances
             WHERE module = :module AND reference_id = 0 AND fiscal_year = :year
             LIMIT 1'
        );
        $stmt->execute(['module' => $module, 'year' => $year]);
        $value = $stmt->fetchColumn();

        return $value === false ? 0.0 : (float) $value;
    }

    private function validateEntry(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'occurred_on' => '日期',
            'item_type' => '類型',
            'amount' => '金額',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['occurred_on'])) {
            $this->backWithInput($path, $_POST, '日期格式不正確。');
        }

        if ($this->amountValue() <= 0) {
            $this->backWithInput($path, $_POST, '金額必須大於 0。');
        }

        if (!$this->selectedItem() && trim((string) ($_POST['item_name'] ?? '')) === '') {
            $this->backWithInput($path, $_POST, '請選擇常用項目或輸入項目名稱。');
        }
    }

    private function findEntry(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT petty_cash_entries.*, users.name AS created_by_name,
                    reviewers.name AS reviewed_by_name,
                    bank_accounts.bank_name,
                    bank_accounts.account_no,
                    projects.name AS project_name,
                    projects.project_code,
                    accounting_vouchers.voucher_no, accounting_vouchers.status AS voucher_status
             FROM petty_cash_entries
             LEFT JOIN users ON users.id = petty_cash_entries.created_by
             LEFT JOIN users AS reviewers ON reviewers.id = petty_cash_entries.reviewed_by
             LEFT JOIN bank_accounts ON bank_accounts.id = petty_cash_entries.bank_account_id
             LEFT JOIN projects ON projects.id = petty_cash_entries.project_id
             LEFT JOIN accounting_vouchers ON accounting_vouchers.id = petty_cash_entries.accounting_voucher_id
             WHERE petty_cash_entries.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到零用金紀錄']);
            exit;
        }

        return $entry;
    }

    private function items(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM petty_cash_items
             WHERE status = "active"
             ORDER BY item_type, sort_order, name'
        )->fetchAll();
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

    private function selectedItem(): ?array
    {
        $id = (int) ($_POST['petty_cash_item_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT * FROM petty_cash_items WHERE id = :id AND status = "active" LIMIT 1');
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        return $item ?: null;
    }

    private function accountByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, code, name FROM accounting_accounts WHERE code = :code AND status = "active" LIMIT 1');
        $stmt->execute(['code' => $code]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        return $account ?: null;
    }

    private function pettyCashExpenseAccount(array $entry): ?array
    {
        $name = (string) $entry['item_name'];
        if (str_contains($name, '交通') || str_contains($name, '差旅')) {
            return $this->accountByCode('5500');
        }
        if (str_contains($name, '講師')) {
            return $this->accountByCode('5400');
        }
        if (str_contains($name, '活動') || str_contains($name, '教材') || str_contains($name, '印刷')) {
            return $this->accountByCode('5200');
        }

        return $this->accountByCode('5300');
    }

    private function pettyCashIncomeAccount(array $entry): ?array
    {
        if ($this->isPettyCashTransfer($entry)) {
            return $this->accountByCode('1100');
        }

        return $this->accountByCode('4100');
    }

    private function isPettyCashTransfer(array $entry): bool
    {
        $text = (string) ($entry['item_name'] ?? '') . ' ' . (string) ($entry['notes'] ?? '') . ' ' . (string) ($entry['payment_to'] ?? '');

        return str_contains($text, '撥補') || str_contains($text, '銀行');
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

    private function totals(array $entries): array
    {
        return PettyCashReport::totals($entries);
    }

    private function typeValue(): string
    {
        return ($_POST['item_type'] ?? '') === 'income' ? 'income' : 'expense';
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }

    private function projectId(): ?int
    {
        $id = (int) ($_POST['project_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    /** 快速記帳頁顯示最近幾筆,方便手機上確認剛記的內容。 */
    private function recentQuickEntries(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, occurred_on, item_type, item_name, amount
             FROM petty_cash_entries
             ORDER BY id DESC
             LIMIT 5'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    private function attachments(int $entryId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM petty_cash_attachments WHERE petty_cash_entry_id = :id ORDER BY id'
        );
        $stmt->execute(['id' => $entryId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed> */
    private function findAttachment(int $entryId, int $fileId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM petty_cash_attachments WHERE id = :id AND petty_cash_entry_id = :entry_id LIMIT 1'
        );
        $stmt->execute(['id' => $fileId, 'entry_id' => $entryId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$file) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到憑證檔案']);
            exit;
        }
        return $file;
    }

    /**
     * 處理 $_FILES['receipts'](可多張)的憑證上傳:驗證、影像壓縮後存放並寫入資料表。
     * 回傳成功存入的張數。
     */
    private function storeUploadedReceipts(int $entryId): int
    {
        if (empty($_FILES['receipts']) || !is_array($_FILES['receipts']['name'] ?? null)) {
            return 0;
        }

        $names = $_FILES['receipts']['name'];
        $count = count($names);
        $stored = 0;
        $targetDir = storage_path('private_uploads/petty_cash/' . $entryId);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return 0;
        }

        for ($i = 0; $i < $count; $i++) {
            if ($stored >= 10) {
                break; // 單次最多 10 張,避免濫用。
            }
            $error = (int) ($_FILES['receipts']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = (string) ($_FILES['receipts']['tmp_name'][$i] ?? '');
            $size = (int) ($_FILES['receipts']['size'][$i] ?? 0);
            if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > 25 * 1024 * 1024) {
                continue;
            }

            $originalName = basename((string) ($_FILES['receipts']['name'][$i] ?? ''));
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $mime = $this->detectMime($tmp);

            $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(6));

            if (ImageCompressor::isCompressible($mime)) {
                // 影像一律壓縮並轉存為 JPEG。
                $relativePath = 'private_uploads/petty_cash/' . $entryId . '/' . $storedName . '.jpg';
                if (!ImageCompressor::compressToJpeg($tmp, storage_path($relativePath), 1600, 75)) {
                    continue;
                }
                $finalMime = 'image/jpeg';
                $finalSize = (int) @filesize(storage_path($relativePath));
            } elseif ($ext === 'pdf' && $mime === 'application/pdf') {
                // PDF 憑證原樣存放(不壓縮)。
                $relativePath = 'private_uploads/petty_cash/' . $entryId . '/' . $storedName . '.pdf';
                if (!move_uploaded_file($tmp, storage_path($relativePath))) {
                    continue;
                }
                $finalMime = 'application/pdf';
                $finalSize = $size;
            } else {
                continue; // 其他格式不收。
            }

            Database::pdo()->prepare(
                'INSERT INTO petty_cash_attachments
                 (petty_cash_entry_id, original_name, stored_path, mime_type, file_size, uploaded_by, created_at)
                 VALUES (:entry_id, :original_name, :stored_path, :mime_type, :file_size, :uploaded_by, :created_at)'
            )->execute([
                'entry_id' => $entryId,
                'original_name' => $originalName !== '' ? $originalName : ($storedName . '.jpg'),
                'stored_path' => $relativePath,
                'mime_type' => $finalMime,
                'file_size' => $finalSize,
                'uploaded_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
            ]);
            $stored++;
        }

        return $stored;
    }

    /** 確認路徑位於零用金上傳目錄下,避免路徑穿越。 */
    private function fileInStore(string $path): bool
    {
        $base = storage_path('private_uploads/petty_cash');
        $real = realpath($path);
        $realBase = realpath($base);
        return $real !== false && $realBase !== false && str_starts_with($real, $realBase . DIRECTORY_SEPARATOR);
    }

    private function detectMime(string $path): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $path);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    return $mime;
                }
            }
        }
        return 'application/octet-stream';
    }

    private function dateScope(int $year, string $month): array
    {
        if ($month !== '') {
            return [
                'DATE_FORMAT(petty_cash_entries.occurred_on, "%Y-%m") = :report_month',
                ['report_month' => $year . '-' . $month],
            ];
        }

        return [
            'YEAR(petty_cash_entries.occurred_on) = :report_year',
            ['report_year' => $year],
        ];
    }
}
