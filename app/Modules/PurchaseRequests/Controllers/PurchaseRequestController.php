<?php

namespace App\Modules\PurchaseRequests\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Support\DateScope;
use PDO;

final class PurchaseRequestController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('purchase_requests.view');

        $filters = $this->filters();
        $requests = $this->requestRows($filters['where'], $filters['params']);

        $this->render('purchase-requests.index', [
            'title' => '採購申請',
            'section' => '財務會計',
            'active' => 'purchase-requests',
            'requests' => $requests,
            'month' => $filters['month'],
            'scope' => $filters['scope'],
            'year' => $filters['year'],
            'status' => $filters['status'],
            'keyword' => $filters['keyword'],
            'summary' => $this->summary($requests),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('purchase_requests.manage');

        $this->render('purchase-requests.create', [
            'title' => '新增採購申請',
            'section' => '財務會計',
            'active' => 'purchase-requests',
            'request' => [
                'requested_on' => date('Y-m-d'),
                'requester_name' => auth()->user()['name'] ?? '',
                'accounting_no' => '',
                'purchase_category' => '',
                'request_unit' => '',
                'subject' => '',
                'reason' => '',
                'purpose' => '',
                'purchase_method' => '',
                'vendor_name' => '',
                'quotation_attached' => '1',
                'quotation_missing_reason' => '',
                'notes' => '',
            ],
            'items' => [['item_name' => '', 'specification' => '', 'quantity' => '1', 'unit_price' => '', 'amount' => '', 'notes' => '']],
            'action' => '/purchase-requests',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('purchase_requests.manage');
        $this->validateRequest('/purchase-requests/create');
        $items = $this->validatedItems('/purchase-requests/create');
        $total = $this->itemsTotal($items);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $requestNo = $this->nextRequestNo((string) $_POST['requested_on']);
            $pdo->prepare(
                'INSERT INTO purchase_requests
                 (request_no, requested_on, requester_name, requester_id, accounting_no, purchase_category, request_unit, subject, reason, purpose, purchase_method, vendor_name, quotation_attached, quotation_missing_reason, total_amount, status, notes, created_by, created_at, updated_at)
                 VALUES
                 (:request_no, :requested_on, :requester_name, :requester_id, :accounting_no, :purchase_category, :request_unit, :subject, :reason, :purpose, :purchase_method, :vendor_name, :quotation_attached, :quotation_missing_reason, :total_amount, \'draft\', :notes, :created_by, :created_at, :updated_at)'
            )->execute($this->payload($total) + [
                'request_no' => $requestNo,
                'requester_id' => auth()->user()['id'] ?? null,
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
            ]);

            $id = (int) $pdo->lastInsertId();
            $this->replaceItems($id, $items);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->backWithInput('/purchase-requests/create', $_POST, '採購申請建立失敗：' . $exception->getMessage());
        }

        AuditLog::write('create', 'purchase_requests', 'purchase_requests', $id);
        flash('success', '採購申請已建立。');
        redirect('/purchase-requests/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('purchase_requests.view');

        $this->render('purchase-requests.show', [
            'title' => '採購申請',
            'section' => '財務會計',
            'active' => 'purchase-requests',
            'request' => $this->findRequest((int) $id),
            'items' => $this->items((int) $id),
            'attachments' => $this->attachments((int) $id),
            'approvalHistory' => ApprovalFlow::history('purchase_requests', 'purchase_requests', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function printForm(string $id): void
    {
        $this->requirePermission('purchase_requests.view');

        $request = $this->findRequest((int) $id);
        $this->render('purchase-requests.print', [
            'title' => '採購申請單',
            'section' => '財務會計',
            'active' => 'purchase-requests',
            'request' => $request,
            'items' => $this->items((int) $id),
            'attachments' => $this->attachments((int) $id),
            'approvalHistory' => ApprovalFlow::history('purchase_requests', 'purchase_requests', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);
        if (!in_array($request['status'], ['draft', 'rejected'], true)) {
            flash('error', '只有草稿或退回的採購申請可以編輯。');
            redirect('/purchase-requests/' . $id);
        }

        $this->render('purchase-requests.edit', [
            'title' => '編輯採購申請',
            'section' => '財務會計',
            'active' => 'purchase-requests',
            'request' => $request,
            'items' => $this->items((int) $id),
            'action' => '/purchase-requests/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);
        if (!in_array($request['status'], ['draft', 'rejected'], true)) {
            flash('error', '只有草稿或退回的採購申請可以編輯。');
            redirect('/purchase-requests/' . $id);
        }

        $this->validateRequest('/purchase-requests/' . $id . '/edit');
        $items = $this->validatedItems('/purchase-requests/' . $id . '/edit');
        $total = $this->itemsTotal($items);

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE purchase_requests
                 SET requested_on = :requested_on,
                     requester_name = :requester_name,
                     accounting_no = :accounting_no,
                     purchase_category = :purchase_category,
                     request_unit = :request_unit,
                     subject = :subject,
                     reason = :reason,
                     purpose = :purpose,
                     purchase_method = :purchase_method,
                     vendor_name = :vendor_name,
                     quotation_attached = :quotation_attached,
                     quotation_missing_reason = :quotation_missing_reason,
                     total_amount = :total_amount,
                     notes = :notes,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($this->payload($total) + ['id' => (int) $id]);

            $this->replaceItems((int) $id, $items);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->backWithInput('/purchase-requests/' . $id . '/edit', $_POST, '採購申請更新失敗：' . $exception->getMessage());
        }

        AuditLog::write('update', 'purchase_requests', 'purchase_requests', (int) $id);
        flash('success', '採購申請已更新。');
        redirect('/purchase-requests/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);
        if (!in_array($request['status'], ['draft', 'rejected'], true)) {
            flash('error', '只有草稿或退回的採購申請可以送審。');
            redirect('/purchase-requests/' . $id);
        }

        ApprovalFlow::submit('purchase_requests', 'purchase_requests', (int) $request['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE purchase_requests
             SET status = \'submitted\',
                 submitted_at = :submitted_at,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'submitted_at' => now(),
            'updated_at' => now(),
            'id' => (int) $request['id'],
        ]);

        AuditLog::write('submit', 'purchase_requests', 'purchase_requests', (int) $request['id']);
        flash('success', '採購申請已送審。');
        redirect('/purchase-requests/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('purchase_requests.approve');
        $this->review((int) $id, 'approved', '採購申請已核准。');
    }

    public function reject(string $id): void
    {
        $this->requirePermission('purchase_requests.approve');
        $this->review((int) $id, 'rejected', '採購申請已退回。');
    }

    public function markOrdered(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);
        if ($request['status'] !== 'approved') {
            flash('error', '只有已核准的採購申請可以標記為採購中。');
            redirect('/purchase-requests/' . $id);
        }

        Database::pdo()->prepare(
            'UPDATE purchase_requests
             SET status = \'ordered\',
                 ordered_at = :ordered_at,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'ordered_at' => now(),
            'updated_at' => now(),
            'id' => (int) $request['id'],
        ]);

        AuditLog::write('mark_ordered', 'purchase_requests', 'purchase_requests', (int) $request['id']);
        flash('success', '採購申請已標記為採購中。');
        redirect('/purchase-requests/' . $id);
    }

    public function markReceived(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);
        if (!in_array($request['status'], ['approved', 'ordered'], true)) {
            flash('error', '只有已核准或採購中的申請可以標記完成驗收。');
            redirect('/purchase-requests/' . $id);
        }

        Database::pdo()->prepare(
            'UPDATE purchase_requests
             SET status = \'received\',
                 received_at = :received_at,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'received_at' => now(),
            'updated_at' => now(),
            'id' => (int) $request['id'],
        ]);

        AuditLog::write('mark_received', 'purchase_requests', 'purchase_requests', (int) $request['id']);
        flash('success', '採購申請已完成驗收。');
        redirect('/purchase-requests/' . $id);
    }

    public function void(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);
        if ($request['status'] === 'received') {
            flash('error', '已完成驗收的採購申請不可作廢。');
            redirect('/purchase-requests/' . $id);
        }

        Database::pdo()->prepare(
            'UPDATE purchase_requests
             SET status = \'voided\',
                 voided_at = :voided_at,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'voided_at' => now(),
            'updated_at' => now(),
            'id' => (int) $request['id'],
        ]);

        AuditLog::write('void', 'purchase_requests', 'purchase_requests', (int) $request['id']);
        flash('success', '採購申請已作廢。');
        redirect('/purchase-requests/' . $id);
    }

    public function destroy(string $id): void
    {
        $request = $this->findRequest((int) $id);
        $this->requireManageOrOwner('purchase_requests.manage', $request['created_by'] ?? null);

        // 已完成驗收的採購申請不可刪除(保留軌跡),請改用作廢。
        if (($request['status'] ?? '') === 'received') {
            flash('error', '已完成驗收的採購申請不可刪除，請改用作廢。');
            redirect('/purchase-requests/' . $id);
        }

        $pdo = Database::pdo();

        // 先移除附件實體檔案,再刪明細、附件與主檔。
        $attachments = $pdo->prepare('SELECT stored_path FROM purchase_request_attachments WHERE purchase_request_id = :id');
        $attachments->execute(['id' => (int) $id]);
        foreach ($attachments->fetchAll(PDO::FETCH_COLUMN) as $storedPath) {
            $path = storage_path((string) $storedPath);
            if ($this->attachmentPathIsSafe($path) && is_file($path)) {
                @unlink($path);
            }
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM purchase_request_attachments WHERE purchase_request_id = :id')->execute(['id' => (int) $id]);
            $pdo->prepare('DELETE FROM purchase_request_items WHERE purchase_request_id = :id')->execute(['id' => (int) $id]);
            $pdo->prepare('DELETE FROM purchase_requests WHERE id = :id')->execute(['id' => (int) $id]);
            $pdo->commit();
        } catch (\Throwable) {
            $pdo->rollBack();
            flash('error', '採購申請刪除失敗，可能仍有關聯資料。');
            redirect('/purchase-requests/' . $id);
        }

        AuditLog::write('delete', 'purchase_requests', 'purchase_requests', (int) $id, ['subject' => $request['subject'] ?? null]);
        flash('success', '採購申請已刪除。');
        redirect('/purchase-requests');
    }

    public function storeAttachment(string $id): void
    {
        $this->requirePermission('purchase_requests.manage');
        $request = $this->findRequest((int) $id);

        if (!$this->attachmentTableExists()) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '採購附件資料表尚未建立，請先執行系統更新。');
        }

        if (empty($_FILES['attachment']) || !is_array($_FILES['attachment'])) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '請選擇要上傳的檔案。');
        }

        $file = $_FILES['attachment'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, $this->uploadError((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '檔案大小需介於 1 byte 到 10MB。');
        }

        $originalName = basename((string) ($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        // 僅限圖片檔:確保每個附件都能於採購申請單列印時一併內嵌印出。
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '目前僅接受圖片檔（JPG／PNG／GIF／WEBP），以便列印時一併印出。');
        }

        $detectedMime = $this->detectMime((string) $file['tmp_name']);
        if (!$this->allowedUploadMime($extension, $detectedMime, (string) $file['tmp_name'])) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '檔案內容與副檔名不符，請確認檔案格式後重新上傳。');
        }

        $category = $this->attachmentCategory();
        $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $relativePath = 'private_uploads/purchase_requests/' . (int) $request['id'] . '/' . $storedName;
        $targetDir = storage_path('private_uploads/purchase_requests/' . (int) $request['id']);
        $targetPath = storage_path($relativePath);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '無法建立上傳目錄。');
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '檔案上傳失敗，請確認 storage 目錄權限。');
        }

        $attachmentId = 0;
        try {
            Database::pdo()->prepare(
                'INSERT INTO purchase_request_attachments
                 (purchase_request_id, category, title, original_name, stored_path, mime_type, file_size, notes, uploaded_by, created_at)
                 VALUES
                 (:purchase_request_id, :category, :title, :original_name, :stored_path, :mime_type, :file_size, :notes, :uploaded_by, :created_at)'
            )->execute([
                'purchase_request_id' => (int) $request['id'],
                'category' => $category,
                'title' => trim((string) ($_POST['title'] ?? '')) ?: $originalName,
                'original_name' => $originalName,
                'stored_path' => $relativePath,
                'mime_type' => $detectedMime,
                'file_size' => $size,
                'notes' => trim((string) ($_POST['notes'] ?? '')),
                'uploaded_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
            ]);
            $attachmentId = (int) Database::pdo()->lastInsertId();

            if ($category === 'quotation') {
                Database::pdo()->prepare(
                    'UPDATE purchase_requests
                     SET quotation_attached = 1,
                         quotation_missing_reason = NULL,
                         updated_at = :updated_at
                     WHERE id = :id'
                )->execute([
                    'updated_at' => now(),
                    'id' => (int) $request['id'],
                ]);
            }
        } catch (\Throwable $exception) {
            if (is_file($targetPath)) {
                @unlink($targetPath);
            }
            $this->backWithInput('/purchase-requests/' . $id, $_POST, '採購附件上傳失敗：' . $exception->getMessage());
        }

        AuditLog::write('upload', 'purchase_requests', 'purchase_request_attachments', $attachmentId);
        flash('success', '採購附件已上傳。');
        redirect('/purchase-requests/' . $id);
    }

    public function downloadAttachment(string $id, string $attachmentId): void
    {
        $this->requirePermission('purchase_requests.view');
        $this->findRequest((int) $id);
        $attachment = $this->findAttachment((int) $id, (int) $attachmentId);
        $path = storage_path((string) $attachment['stored_path']);

        if (!$this->attachmentPathIsSafe($path) || !is_file($path)) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到採購附件檔案']);
            exit;
        }

        AuditLog::write('download', 'purchase_requests', 'purchase_request_attachments', (int) $attachment['id']);
        header('Content-Type: ' . ($attachment['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $attachment['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function deleteAttachment(string $id, string $attachmentId): void
    {
        $this->requirePermission('purchase_requests.manage');
        $this->findRequest((int) $id);
        $attachment = $this->findAttachment((int) $id, (int) $attachmentId);
        $path = storage_path((string) $attachment['stored_path']);

        Database::pdo()->prepare(
            'DELETE FROM purchase_request_attachments
             WHERE purchase_request_id = :purchase_request_id AND id = :id'
        )->execute([
            'purchase_request_id' => (int) $id,
            'id' => (int) $attachmentId,
        ]);

        if ($this->attachmentPathIsSafe($path) && is_file($path)) {
            @unlink($path);
        }

        AuditLog::write('delete', 'purchase_requests', 'purchase_request_attachments', (int) $attachment['id']);
        flash('success', '採購附件已刪除。');
        redirect('/purchase-requests/' . $id);
    }

    private function filters(): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $scope = DateScope::normalize($_GET['scope'] ?? null);
        $year = normalize_fiscal_year($_GET['year'] ?? date('Y'));
        if ($year < 1912 || $year > 2100) {
            $year = (int) date('Y');
        }
        $status = in_array($_GET['status'] ?? '', $this->statuses(), true) ? (string) $_GET['status'] : '';
        $keyword = trim(is_scalar($_GET['q'] ?? null) ? (string) $_GET['q'] : '');

        [$dateWhere, $params] = DateScope::condition('purchase_requests.requested_on', $scope, $month, $year);
        $where = $dateWhere !== '' ? [$dateWhere] : [];
        if ($status !== '') {
            $where[] = 'purchase_requests.status = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(purchase_requests.request_no LIKE :keyword
                OR purchase_requests.accounting_no LIKE :keyword
                OR purchase_requests.subject LIKE :keyword
                OR purchase_requests.vendor_name LIKE :keyword
                OR purchase_requests.purchase_category LIKE :keyword
                OR purchase_requests.request_unit LIKE :keyword
                OR purchase_requests.requester_name LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        return [
            'month' => $month,
            'scope' => $scope,
            'year' => $year,
            'status' => $status,
            'keyword' => $keyword,
            'where' => $where,
            'params' => $params,
        ];
    }

    private function requestRows(array $where, array $params): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT purchase_requests.*,
                    creators.name AS created_by_name,
                    reviewers.name AS reviewed_by_name
             FROM purchase_requests
             LEFT JOIN users AS creators ON creators.id = purchase_requests.created_by
             LEFT JOIN users AS reviewers ON reviewers.id = purchase_requests.reviewed_by'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . '
             ORDER BY purchase_requests.requested_on DESC, purchase_requests.id DESC'
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function validateRequest(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'requested_on' => '申請日期',
            'requester_name' => '申請人',
            'purchase_category' => '請購類別',
            'request_unit' => '請購單位',
            'subject' => '申請項目',
            'reason' => '請購事由',
            'purpose' => '申請目的',
            'purchase_method' => '採購方式',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['requested_on'])) {
            $this->backWithInput($path, $_POST, '申請日期格式不正確。');
        }

        if ($this->quotationAttached() === 0 && trim((string) ($_POST['quotation_missing_reason'] ?? '')) === '') {
            $this->backWithInput($path, $_POST, '未檢附報價單時，請填寫原因。');
        }
    }

    private function payload(float $total): array
    {
        return [
            'requested_on' => (string) $_POST['requested_on'],
            'requester_name' => trim((string) $_POST['requester_name']),
            'accounting_no' => trim((string) ($_POST['accounting_no'] ?? '')),
            'purchase_category' => trim((string) $_POST['purchase_category']),
            'request_unit' => trim((string) $_POST['request_unit']),
            'subject' => trim((string) $_POST['subject']),
            'reason' => trim((string) $_POST['reason']),
            'purpose' => trim((string) $_POST['purpose']),
            'purchase_method' => trim((string) $_POST['purchase_method']),
            'vendor_name' => trim((string) ($_POST['vendor_name'] ?? '')),
            'quotation_attached' => $this->quotationAttached(),
            'quotation_missing_reason' => trim((string) ($_POST['quotation_missing_reason'] ?? '')),
            'total_amount' => $total,
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'updated_at' => now(),
        ];
    }

    private function validatedItems(string $path): array
    {
        $posted = $_POST['items'] ?? [];
        if (!is_array($posted)) {
            $this->backWithInput($path, $_POST, '請購明細格式不正確。');
        }

        $items = [];
        foreach ($posted as $line) {
            if (!is_array($line)) {
                continue;
            }

            $name = trim((string) ($line['item_name'] ?? ''));
            $specification = trim((string) ($line['specification'] ?? ''));
            $quantity = round((float) ($line['quantity'] ?? 0), 2);
            $unitPriceRaw = $line['unit_price'] ?? '';
            $unitPrice = $unitPriceRaw === '' ? null : round((float) $unitPriceRaw, 2);
            $amount = round((float) ($line['amount'] ?? 0), 2);
            if ($amount <= 0 && $quantity > 0 && $unitPrice !== null && $unitPrice > 0) {
                $amount = round($quantity * $unitPrice, 2);
            }

            $empty = $name === '' && $specification === '' && $quantity <= 0 && ($unitPrice === null || $unitPrice <= 0) && $amount <= 0;
            if ($empty) {
                continue;
            }

            if ($name === '') {
                $this->backWithInput($path, $_POST, '請購明細需填寫品名。');
            }
            if ($quantity <= 0) {
                $this->backWithInput($path, $_POST, '請購明細數量需大於 0。');
            }
            if ($amount <= 0) {
                $this->backWithInput($path, $_POST, '請購明細金額需大於 0。');
            }

            $items[] = [
                'item_name' => $name,
                'specification' => $specification,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $amount,
                'notes' => trim((string) ($line['notes'] ?? '')),
            ];
        }

        if (!$items) {
            $this->backWithInput($path, $_POST, '請至少填寫一筆請購明細。');
        }

        return $items;
    }

    private function replaceItems(int $requestId, array $items): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM purchase_request_items WHERE purchase_request_id = :purchase_request_id')
            ->execute(['purchase_request_id' => $requestId]);

        $stmt = $pdo->prepare(
            'INSERT INTO purchase_request_items
             (purchase_request_id, item_name, specification, quantity, unit_price, amount, sort_order, notes, created_at, updated_at)
             VALUES
             (:purchase_request_id, :item_name, :specification, :quantity, :unit_price, :amount, :sort_order, :notes, :created_at, :updated_at)'
        );

        foreach ($items as $index => $item) {
            $stmt->execute($item + [
                'purchase_request_id' => $requestId,
                'sort_order' => ($index + 1) * 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function review(int $id, string $status, string $message): void
    {
        $request = $this->findRequest($id);
        if ($request['status'] !== 'submitted') {
            flash('error', '只有送審中的採購申請可以簽核。');
            redirect('/purchase-requests/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        ApprovalFlow::review('purchase_requests', 'purchase_requests', $id, $status, $notes);
        Database::pdo()->prepare(
            'UPDATE purchase_requests
             SET status = :status,
                 reviewed_by = :reviewed_by,
                 reviewed_at = :reviewed_at,
                 review_notes = :review_notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'status' => $status,
            'reviewed_by' => auth()->user()['id'] ?? null,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'updated_at' => now(),
            'id' => $id,
        ]);

        AuditLog::write($status === 'approved' ? 'approve' : 'reject', 'purchase_requests', 'purchase_requests', $id);
        flash('success', $message);
        redirect('/purchase-requests/' . $id);
    }

    private function findRequest(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT purchase_requests.*,
                    creators.name AS created_by_name,
                    reviewers.name AS reviewed_by_name
             FROM purchase_requests
             LEFT JOIN users AS creators ON creators.id = purchase_requests.created_by
             LEFT JOIN users AS reviewers ON reviewers.id = purchase_requests.reviewed_by
             WHERE purchase_requests.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到採購申請']);
            exit;
        }

        return $request;
    }

    private function items(int $requestId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT *
             FROM purchase_request_items
             WHERE purchase_request_id = :purchase_request_id
             ORDER BY sort_order, id'
        );
        $stmt->execute(['purchase_request_id' => $requestId]);

        return $stmt->fetchAll();
    }

    private function attachments(int $requestId): array
    {
        if (!$this->attachmentTableExists()) {
            return [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT purchase_request_attachments.*, users.name AS uploaded_by_name
             FROM purchase_request_attachments
             LEFT JOIN users ON users.id = purchase_request_attachments.uploaded_by
             WHERE purchase_request_attachments.purchase_request_id = :purchase_request_id
             ORDER BY FIELD(purchase_request_attachments.category, \'quotation\', \'contract\', \'invoice\', \'delivery\', \'payment\', \'other\'), purchase_request_attachments.id DESC'
        );
        $stmt->execute(['purchase_request_id' => $requestId]);

        return $stmt->fetchAll();
    }

    private function findAttachment(int $requestId, int $attachmentId): array
    {
        if (!$this->attachmentTableExists()) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到採購附件']);
            exit;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT *
             FROM purchase_request_attachments
             WHERE purchase_request_id = :purchase_request_id AND id = :id
             LIMIT 1'
        );
        $stmt->execute([
            'purchase_request_id' => $requestId,
            'id' => $attachmentId,
        ]);
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attachment) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到採購附件']);
            exit;
        }

        return $attachment;
    }

    private function nextRequestNo(string $date): string
    {
        $prefix = 'PR' . str_replace('-', '', $date) . '-';
        $stmt = Database::pdo()->prepare('SELECT MAX(CAST(SUBSTRING(request_no, :offset) AS UNSIGNED)) FROM purchase_requests WHERE request_no LIKE :prefix');
        $stmt->execute(['offset' => strlen($prefix) + 1, 'prefix' => $prefix . '%']);

        return $prefix . str_pad((string) ((int) $stmt->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    private function quotationAttached(): int
    {
        return (string) ($_POST['quotation_attached'] ?? '1') === '0' ? 0 : 1;
    }

    private function itemsTotal(array $items): float
    {
        return array_sum(array_map(static fn (array $item): float => (float) $item['amount'], $items));
    }

    private function attachmentCategory(): string
    {
        $category = (string) ($_POST['category'] ?? 'other');
        return in_array($category, ['quotation', 'invoice', 'contract', 'delivery', 'payment', 'other'], true)
            ? $category
            : 'other';
    }

    private function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '檔案超過主機允許大小。',
            UPLOAD_ERR_PARTIAL => '檔案只有部分上傳，請重新上傳。',
            UPLOAD_ERR_NO_FILE => '請選擇要上傳的檔案。',
            UPLOAD_ERR_NO_TMP_DIR => '主機缺少暫存目錄。',
            UPLOAD_ERR_CANT_WRITE => '主機無法寫入上傳檔案。',
            default => '檔案上傳失敗。',
        };
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

    private function allowedUploadMime(string $extension, string $mime, string $path): bool
    {
        $allowed = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ];

        if (!in_array($mime, $allowed[$extension] ?? [], true)) {
            return false;
        }

        // 圖片一律以 getimagesize 二次驗證內容確為影像。
        return @getimagesize($path) !== false;
    }

    private function attachmentPathIsSafe(string $path): bool
    {
        $base = rtrim(str_replace('\\', '/', storage_path('private_uploads/purchase_requests')), '/') . '/';
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, $base);
    }

    private function attachmentTableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $stmt = Database::pdo()->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => 'purchase_request_attachments']);
            $exists = (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            $exists = false;
        }

        return $exists;
    }

    private function summary(array $requests): array
    {
        $active = array_values(array_filter($requests, static fn (array $request): bool => $request['status'] !== 'voided'));

        return [
            'count' => count($active),
            'amount' => array_sum(array_map(static fn (array $request): float => (float) $request['total_amount'], $active)),
            'submitted' => count(array_filter($active, static fn (array $request): bool => $request['status'] === 'submitted')),
            'approved' => count(array_filter($active, static fn (array $request): bool => in_array($request['status'], ['approved', 'ordered', 'received'], true))),
        ];
    }

    private function statuses(): array
    {
        return ['draft', 'submitted', 'approved', 'rejected', 'ordered', 'received', 'voided'];
    }
}
