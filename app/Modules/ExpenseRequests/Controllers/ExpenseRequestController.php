<?php

namespace App\Modules\ExpenseRequests\Controllers;

use App\Core\ApprovalFlow;
use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\ImageCompressor;
use App\Core\Permission;
use App\Core\Validator;
use App\Domain\ExpenseRequests\ExpenseRequestSupport;
use PDO;

/**
 * 員工費用申請（代墊／請款核銷）：員工提出小額代墊費用申請，主管/會計核定後併入零用金，
 * 由會計確認以現金或匯款支付。
 */
final class ExpenseRequestController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('expense_requests.view');

        // 有核定或付款權限者可看全部;一般員工只看自己的。
        $canReview = Permission::can('expense_requests.approve') || Permission::can('expense_requests.pay');
        $params = [];
        $where = '';
        if (!$canReview) {
            $where = 'WHERE expense_requests.applicant_id = :uid OR expense_requests.created_by = :uid2';
            $params = ['uid' => $this->currentUserId(), 'uid2' => $this->currentUserId()];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT expense_requests.*, applicants.name AS applicant_name, reviewers.name AS reviewed_by_name
             FROM expense_requests
             LEFT JOIN users AS applicants ON applicants.id = expense_requests.applicant_id
             LEFT JOIN users AS reviewers ON reviewers.id = expense_requests.reviewed_by '
            . $where . '
             ORDER BY expense_requests.id DESC'
        );
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        $this->render('expense-requests.index', [
            'title' => '費用申請',
            'section' => '支出與核銷',
            'active' => 'expense-requests',
            'requests' => $requests,
            'canReview' => $canReview,
            'canApprove' => Permission::can('expense_requests.approve'),
            'canPay' => Permission::can('expense_requests.pay'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('expense_requests.view');

        $this->render('expense-requests.form', [
            'title' => '新增費用申請',
            'section' => '支出與核銷',
            'active' => 'expense-requests',
            'request' => [
                'occurred_on' => date('Y-m-d'),
                'item_name' => '',
                'petty_cash_item_id' => '',
                'amount' => '',
                'reason' => '',
                'payment_type' => 'cash',
                'bank_name' => '',
                'bank_branch' => '',
                'bank_account' => '',
                'bank_account_name' => '',
                'status' => 'draft',
            ],
            'items' => $this->pettyCashItems(),
            'action' => '/expense-requests',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('expense_requests.view');
        $this->validateRequest('/expense-requests/create');

        $submit = ($_POST['action'] ?? '') === 'submit';
        $now = now();
        $userId = $this->currentUserId();
        $date = (string) $_POST['occurred_on'];

        Database::pdo()->prepare(
            'INSERT INTO expense_requests
             (request_no, applicant_id, occurred_on, petty_cash_item_id, item_name, amount, reason,
              payment_type, bank_name, bank_branch, bank_account, bank_account_name,
              status, submitted_at, created_by, created_at, updated_at)
             VALUES
             (:request_no, :applicant_id, :occurred_on, :petty_cash_item_id, :item_name, :amount, :reason,
              :payment_type, :bank_name, :bank_branch, :bank_account, :bank_account_name,
              :status, :submitted_at, :created_by, :created_at, :updated_at)'
        )->execute([
            'request_no' => $this->nextRequestNo($date),
            'applicant_id' => $userId ?: null,
            'occurred_on' => $date,
            'petty_cash_item_id' => $this->selectedItemId(),
            'item_name' => $this->itemNameValue(),
            'amount' => $this->amountValue(),
            'reason' => $this->nullable('reason'),
            'payment_type' => $this->paymentType(),
            'bank_name' => $this->nullable('bank_name'),
            'bank_branch' => $this->nullable('bank_branch'),
            'bank_account' => $this->nullable('bank_account'),
            'bank_account_name' => $this->nullable('bank_account_name'),
            'status' => $submit ? 'submitted' : 'draft',
            'submitted_at' => $submit ? $now : null,
            'created_by' => $userId ?: null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        $stored = $this->storeUploadedReceipts($id);
        if ($submit) {
            ApprovalFlow::submit('expense_requests', 'expense_requests', $id, $this->nullable('reason'));
        }

        AuditLog::write('create', 'expense_requests', 'expense_requests', $id);
        flash('success', $submit ? '費用申請已送出待核定。' : ('費用申請已儲存為草稿' . ($stored ? '(含 ' . $stored . ' 張憑證)' : '') . '。'));
        redirect('/expense-requests/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id, true);

        $this->render('expense-requests.show', [
            'title' => '費用申請明細',
            'section' => '支出與核銷',
            'active' => 'expense-requests',
            'request' => $request,
            'attachments' => $this->attachments((int) $id),
            'approvalHistory' => ApprovalFlow::history('expense_requests', 'expense_requests', (int) $id),
            'canApprove' => Permission::can('expense_requests.approve'),
            'canPay' => Permission::can('expense_requests.pay'),
            'isOwner' => $this->ownsRequest($request),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id);
        $this->requireEditable($request);

        $this->render('expense-requests.form', [
            'title' => '編輯費用申請',
            'section' => '支出與核銷',
            'active' => 'expense-requests',
            'request' => $request,
            'items' => $this->pettyCashItems(),
            'action' => '/expense-requests/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id);
        $this->requireEditable($request);
        $this->validateRequest('/expense-requests/' . $id . '/edit');

        $submit = ($_POST['action'] ?? '') === 'submit';
        $now = now();
        $date = (string) $_POST['occurred_on'];

        Database::pdo()->prepare(
            'UPDATE expense_requests SET
                occurred_on = :occurred_on, petty_cash_item_id = :petty_cash_item_id, item_name = :item_name,
                amount = :amount, reason = :reason, payment_type = :payment_type,
                bank_name = :bank_name, bank_branch = :bank_branch, bank_account = :bank_account,
                bank_account_name = :bank_account_name, status = :status, submitted_at = :submitted_at,
                updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'occurred_on' => $date,
            'petty_cash_item_id' => $this->selectedItemId(),
            'item_name' => $this->itemNameValue(),
            'amount' => $this->amountValue(),
            'reason' => $this->nullable('reason'),
            'payment_type' => $this->paymentType(),
            'bank_name' => $this->nullable('bank_name'),
            'bank_branch' => $this->nullable('bank_branch'),
            'bank_account' => $this->nullable('bank_account'),
            'bank_account_name' => $this->nullable('bank_account_name'),
            'status' => $submit ? 'submitted' : 'draft',
            'submitted_at' => $submit ? ($request['submitted_at'] ?? $now) : null,
            'updated_at' => $now,
            'id' => (int) $request['id'],
        ]);

        $this->storeUploadedReceipts((int) $request['id']);
        if ($submit) {
            ApprovalFlow::submit('expense_requests', 'expense_requests', (int) $request['id'], $this->nullable('reason'));
        }

        AuditLog::write('update', 'expense_requests', 'expense_requests', (int) $request['id']);
        flash('success', $submit ? '費用申請已送出待核定。' : '費用申請已更新。');
        redirect('/expense-requests/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id);
        $this->requireEditable($request);

        Database::pdo()->prepare(
            'UPDATE expense_requests SET status = "submitted", submitted_at = :now, updated_at = :now2 WHERE id = :id'
        )->execute(['now' => now(), 'now2' => now(), 'id' => (int) $request['id']]);
        ApprovalFlow::submit('expense_requests', 'expense_requests', (int) $request['id'], trim((string) ($_POST['request_notes'] ?? '')));

        AuditLog::write('submit', 'expense_requests', 'expense_requests', (int) $request['id']);
        flash('success', '費用申請已送出待核定。');
        redirect('/expense-requests/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('expense_requests.approve');
        $request = $this->findRequest((int) $id);

        if ($request['status'] !== 'submitted') {
            flash('error', '僅「待核定」的申請可核定。');
            redirect('/expense-requests/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        $now = now();
        $reviewerId = $this->currentUserId();

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            // 核定後併入零用金：建立一筆已核定的零用金支出，支付對象為申請者。
            $payTo = $this->applicantName($request);
            $pdo->prepare(
                'INSERT INTO petty_cash_entries
                 (occurred_on, item_type, petty_cash_item_id, item_name, amount, payment_to, receipt_no, notes,
                  approval_status, submitted_at, reviewed_by, reviewed_at, created_by, created_at, updated_at)
                 VALUES
                 (:occurred_on, "expense", :petty_cash_item_id, :item_name, :amount, :payment_to, :receipt_no, :notes,
                  "approved", :submitted_at, :reviewed_by, :reviewed_at, :created_by, :created_at, :updated_at)'
            )->execute([
                'occurred_on' => $request['occurred_on'],
                'petty_cash_item_id' => $request['petty_cash_item_id'] ?: null,
                'item_name' => $request['item_name'],
                'amount' => $request['amount'],
                'payment_to' => $payTo,
                'receipt_no' => $request['request_no'],
                'notes' => '員工費用申請 ' . $request['request_no'] . ($request['reason'] ? '：' . $request['reason'] : ''),
                'submitted_at' => $request['submitted_at'] ?: $now,
                'reviewed_by' => $reviewerId ?: null,
                'reviewed_at' => $now,
                'created_by' => $request['created_by'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $entryId = (int) $pdo->lastInsertId();

            $pdo->prepare(
                'UPDATE expense_requests SET status = "approved", reviewed_by = :reviewer, reviewed_at = :now,
                    review_notes = :notes, petty_cash_entry_id = :entry_id, updated_at = :now2 WHERE id = :id'
            )->execute([
                'reviewer' => $reviewerId ?: null,
                'now' => $now,
                'notes' => $notes !== '' ? $notes : null,
                'entry_id' => $entryId,
                'now2' => $now,
                'id' => (int) $request['id'],
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        ApprovalFlow::review('expense_requests', 'expense_requests', (int) $request['id'], 'approved', $notes);
        AuditLog::write('approve', 'expense_requests', 'expense_requests', (int) $request['id']);
        flash('success', '已核定並併入零用金,待會計付款。');
        redirect('/expense-requests/' . $id);
    }

    public function reject(string $id): void
    {
        $this->requirePermission('expense_requests.approve');
        $request = $this->findRequest((int) $id);

        if ($request['status'] !== 'submitted') {
            flash('error', '僅「待核定」的申請可退回。');
            redirect('/expense-requests/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        Database::pdo()->prepare(
            'UPDATE expense_requests SET status = "rejected", reviewed_by = :reviewer, reviewed_at = :now,
                review_notes = :notes, updated_at = :now2 WHERE id = :id'
        )->execute([
            'reviewer' => $this->currentUserId() ?: null,
            'now' => now(),
            'notes' => $notes !== '' ? $notes : null,
            'now2' => now(),
            'id' => (int) $request['id'],
        ]);

        ApprovalFlow::review('expense_requests', 'expense_requests', (int) $request['id'], 'rejected', $notes);
        AuditLog::write('reject', 'expense_requests', 'expense_requests', (int) $request['id']);
        flash('success', '費用申請已退回。');
        redirect('/expense-requests/' . $id);
    }

    public function pay(string $id): void
    {
        $this->requirePermission('expense_requests.pay');
        $request = $this->findRequest((int) $id);

        if ($request['status'] !== 'approved') {
            flash('error', '僅「已核定待付款」的申請可確認付款。');
            redirect('/expense-requests/' . $id);
        }

        $method = ($_POST['paid_method'] ?? '') === 'bank' ? 'bank' : 'cash';
        $notes = trim((string) ($_POST['payment_notes'] ?? ''));
        Database::pdo()->prepare(
            'UPDATE expense_requests SET status = "paid", paid_by = :payer, paid_at = :now,
                paid_method = :method, payment_notes = :notes, updated_at = :now2 WHERE id = :id'
        )->execute([
            'payer' => $this->currentUserId() ?: null,
            'now' => now(),
            'method' => $method,
            'notes' => $notes !== '' ? $notes : null,
            'now2' => now(),
            'id' => (int) $request['id'],
        ]);

        AuditLog::write('pay', 'expense_requests', 'expense_requests', (int) $request['id']);
        flash('success', '已確認付款（' . ExpenseRequestSupport::paymentLabel($method) . '）。');
        redirect('/expense-requests/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id);

        // 一般員工僅能刪除自己且尚未送出/核定的申請;有核定權限者可刪除草稿或退回件。
        $canManage = Permission::can('expense_requests.approve');
        if (!$canManage && !$this->ownsRequest($request)) {
            $this->forbid();
        }
        if (!in_array($request['status'], ['draft', 'rejected'], true)) {
            flash('error', '已送出或已核定的申請不可刪除,請改為退回。');
            redirect('/expense-requests/' . $id);
        }

        foreach ($this->attachments((int) $id) as $file) {
            $path = storage_path((string) $file['stored_path']);
            if ($this->fileInStore($path) && is_file($path)) {
                @unlink($path);
            }
        }
        Database::pdo()->prepare('DELETE FROM expense_requests WHERE id = :id')->execute(['id' => (int) $request['id']]);

        AuditLog::write('delete', 'expense_requests', 'expense_requests', (int) $request['id']);
        flash('success', '費用申請已刪除。');
        redirect('/expense-requests');
    }

    public function uploadAttachment(string $id): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id);
        if (!Permission::can('expense_requests.approve') && !$this->ownsRequest($request)) {
            $this->forbid();
        }

        $stored = $this->storeUploadedReceipts((int) $request['id']);
        if ($stored === 0) {
            $this->backWithInput('/expense-requests/' . $id, [], '沒有可上傳的憑證,或檔案格式不支援。');
        }
        flash('success', '已上傳 ' . $stored . ' 張憑證。');
        redirect('/expense-requests/' . $id);
    }

    public function downloadAttachment(string $id, string $fileId): void
    {
        $this->requirePermission('expense_requests.view');
        $this->findRequest((int) $id, true);
        $file = $this->findAttachment((int) $id, (int) $fileId);
        $path = storage_path((string) $file['stored_path']);

        if (!$this->fileInStore($path) || !is_file($path)) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到憑證檔案']);
            exit;
        }

        AuditLog::write('download', 'expense_requests', 'expense_request_attachments', (int) $file['id']);
        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode((string) $file['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function deleteAttachment(string $id, string $fileId): void
    {
        $this->requirePermission('expense_requests.view');
        $request = $this->findRequest((int) $id);
        if (!Permission::can('expense_requests.approve') && !$this->ownsRequest($request)) {
            $this->forbid();
        }
        $file = $this->findAttachment((int) $id, (int) $fileId);

        $path = storage_path((string) $file['stored_path']);
        if ($this->fileInStore($path) && is_file($path)) {
            @unlink($path);
        }
        Database::pdo()->prepare('DELETE FROM expense_request_attachments WHERE id = :id')->execute(['id' => (int) $file['id']]);

        AuditLog::write('delete', 'expense_requests', 'expense_request_attachments', (int) $file['id']);
        flash('success', '憑證已刪除。');
        redirect('/expense-requests/' . $id);
    }

    // ---- 內部輔助 ----

    private function validateRequest(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'occurred_on' => '費用日期',
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
        if ($this->itemNameValue() === '') {
            $this->backWithInput($path, $_POST, '請選擇常用項目或輸入費用項目名稱。');
        }
        if ($this->paymentType() === 'bank' && trim((string) ($_POST['bank_account'] ?? '')) === '') {
            $this->backWithInput($path, $_POST, '選擇匯款時,請填寫收款帳號。');
        }
    }

    private function requireEditable(array $request): void
    {
        if (!in_array($request['status'], ['draft', 'rejected'], true)) {
            flash('error', '已送出或已核定的申請不可編輯。');
            redirect('/expense-requests/' . $request['id']);
        }
        if (!Permission::can('expense_requests.approve') && !$this->ownsRequest($request)) {
            $this->forbid();
        }
    }

    private function ownsRequest(array $request): bool
    {
        $uid = $this->currentUserId();
        return $uid > 0 && ((int) ($request['applicant_id'] ?? 0) === $uid || (int) ($request['created_by'] ?? 0) === $uid);
    }

    private function forbid(): never
    {
        http_response_code(403);
        view('errors.403', ['title' => '沒有權限']);
        exit;
    }

    /** @return array<string, mixed> */
    private function findRequest(int $id, bool $enforceOwnership = false): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT expense_requests.*, applicants.name AS applicant_name,
                    reviewers.name AS reviewed_by_name, payers.name AS paid_by_name
             FROM expense_requests
             LEFT JOIN users AS applicants ON applicants.id = expense_requests.applicant_id
             LEFT JOIN users AS reviewers ON reviewers.id = expense_requests.reviewed_by
             LEFT JOIN users AS payers ON payers.id = expense_requests.paid_by
             WHERE expense_requests.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$request) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到費用申請']);
            exit;
        }
        // 一般員工只能檢視自己的申請。
        if ($enforceOwnership
            && !Permission::can('expense_requests.approve')
            && !Permission::can('expense_requests.pay')
            && !$this->ownsRequest($request)) {
            $this->forbid();
        }
        return $request;
    }

    private function applicantName(array $request): string
    {
        $name = trim((string) ($request['applicant_name'] ?? ''));
        return $name !== '' ? $name : '員工';
    }

    private function nextRequestNo(string $date): string
    {
        $prefix = 'ER' . str_replace('-', '', substr($date, 0, 10)) . '-';
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM expense_requests WHERE request_no LIKE :prefix');
        $stmt->execute(['prefix' => $prefix . '%']);
        return ExpenseRequestSupport::formatNo($date, (int) $stmt->fetchColumn() + 1);
    }

    private function pettyCashItems(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM petty_cash_items WHERE status = "active" AND item_type = "expense" ORDER BY sort_order, name'
        )->fetchAll();
    }

    private function selectedItemId(): ?int
    {
        $id = (int) ($_POST['petty_cash_item_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function itemNameValue(): string
    {
        $name = trim((string) ($_POST['item_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
        $id = $this->selectedItemId();
        if ($id !== null) {
            $stmt = Database::pdo()->prepare('SELECT name FROM petty_cash_items WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            return (string) ($stmt->fetchColumn() ?: '');
        }
        return '';
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }

    private function paymentType(): string
    {
        return ($_POST['payment_type'] ?? '') === 'bank' ? 'bank' : 'cash';
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function attachments(int $requestId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM expense_request_attachments WHERE expense_request_id = :id ORDER BY id'
        );
        $stmt->execute(['id' => $requestId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed> */
    private function findAttachment(int $requestId, int $fileId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM expense_request_attachments WHERE id = :id AND expense_request_id = :req LIMIT 1'
        );
        $stmt->execute(['id' => $fileId, 'req' => $requestId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$file) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到憑證檔案']);
            exit;
        }
        return $file;
    }

    private function storeUploadedReceipts(int $requestId): int
    {
        if (empty($_FILES['receipts']) || !is_array($_FILES['receipts']['name'] ?? null)) {
            return 0;
        }
        $count = count($_FILES['receipts']['name']);
        $stored = 0;
        $targetDir = storage_path('private_uploads/expense_requests/' . $requestId);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return 0;
        }

        for ($i = 0; $i < $count; $i++) {
            if ($stored >= 10) {
                break;
            }
            if ((int) ($_FILES['receipts']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
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
                $relativePath = 'private_uploads/expense_requests/' . $requestId . '/' . $storedName . '.jpg';
                if (!ImageCompressor::compressToJpeg($tmp, storage_path($relativePath), 1600, 75)) {
                    continue;
                }
                $finalMime = 'image/jpeg';
                $finalSize = (int) @filesize(storage_path($relativePath));
            } elseif ($ext === 'pdf' && $mime === 'application/pdf') {
                $relativePath = 'private_uploads/expense_requests/' . $requestId . '/' . $storedName . '.pdf';
                if (!move_uploaded_file($tmp, storage_path($relativePath))) {
                    continue;
                }
                $finalMime = 'application/pdf';
                $finalSize = $size;
            } else {
                continue;
            }

            Database::pdo()->prepare(
                'INSERT INTO expense_request_attachments
                 (expense_request_id, original_name, stored_path, mime_type, file_size, uploaded_by, created_at)
                 VALUES (:req, :original_name, :stored_path, :mime_type, :file_size, :uploaded_by, :created_at)'
            )->execute([
                'req' => $requestId,
                'original_name' => $originalName !== '' ? $originalName : ($storedName . '.jpg'),
                'stored_path' => $relativePath,
                'mime_type' => $finalMime,
                'file_size' => $finalSize,
                'uploaded_by' => $this->currentUserId() ?: null,
                'created_at' => now(),
            ]);
            $stored++;
        }

        return $stored;
    }

    private function fileInStore(string $path): bool
    {
        $base = storage_path('private_uploads/expense_requests');
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
}
