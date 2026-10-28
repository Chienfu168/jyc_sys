<?php

namespace App\Modules\Charters\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

/**
 * 基金會章程管理:以單一名冊同時保存「歷史章程」的紀錄與編修,以及「現行章程」
 * (含經主管機關教育局核備之文號/日期)。status 標示現行(current)／歷史(historical),
 * 設為現行時會自動將原現行版本降為歷史,確保僅有一筆現行章程。
 */
final class CharterController extends Controller
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const ALLOWED_MIME = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
    ];

    public function index(): void
    {
        $this->requirePermission('charters.view');

        $status = 'all';
        if (isset($_GET['status']) && in_array($_GET['status'], ['current', 'historical'], true)) {
            $status = (string) $_GET['status'];
        }

        $where = [];
        $params = [];
        if ($status !== 'all') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT * FROM charters';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY (status = "current") DESC, enacted_on DESC, revision_no DESC, id DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $charters = $stmt->fetchAll();

        $current = null;
        foreach ($charters as $charter) {
            if ($charter['status'] === 'current') {
                $current = $charter;
                break;
            }
        }

        $this->render('charters.index', [
            'title' => '基金會章程',
            'section' => '主管機關核備',
            'active' => 'charters',
            'charters' => $charters,
            'current' => $current,
            'status' => $status,
            'canManage' => Permission::can('charters.manage'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('charters.manage');

        $this->render('charters.form', [
            'title' => '新增章程版本',
            'section' => '主管機關核備',
            'active' => 'charters',
            'charter' => $this->blankCharter(),
            'action' => '/charters',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('charters.manage');
        $this->validateCharter('/charters/create');

        $file = $this->handleUpload('/charters/create');

        Database::pdo()->beginTransaction();
        try {
            $payload = $this->payload();
            if ($payload['status'] === 'current') {
                $this->demoteCurrent();
            }

            Database::pdo()->prepare(
                'INSERT INTO charters
                 (revision_no, title, enacted_on, approval_authority, approval_doc_no, approval_date,
                  status, amendment_summary, content, original_name, stored_path, mime_type, file_size,
                  notes, created_by, created_at, updated_at)
                 VALUES
                 (:revision_no, :title, :enacted_on, :approval_authority, :approval_doc_no, :approval_date,
                  :status, :amendment_summary, :content, :original_name, :stored_path, :mime_type, :file_size,
                  :notes, :created_by, :created_at, :updated_at)'
            )->execute($payload + [
                'original_name' => $file['original_name'] ?? null,
                'stored_path' => $file['stored_path'] ?? null,
                'mime_type' => $file['mime_type'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'created_by' => $this->currentUserId() ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $id = (int) Database::pdo()->lastInsertId();
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            Database::pdo()->rollBack();
            if (!empty($file['stored_path'])) {
                @unlink(storage_path((string) $file['stored_path']));
            }
            throw $e;
        }

        AuditLog::write('create', 'charters', 'charters', $id);
        flash('success', '章程版本已建立。');
        redirect('/charters/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('charters.view');

        $this->render('charters.show', [
            'title' => '章程詳情',
            'section' => '主管機關核備',
            'active' => 'charters',
            'charter' => $this->findCharter((int) $id),
            'canManage' => Permission::can('charters.manage'),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('charters.manage');

        $this->render('charters.form', [
            'title' => '編輯章程版本',
            'section' => '主管機關核備',
            'active' => 'charters',
            'charter' => $this->findCharter((int) $id),
            'action' => '/charters/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('charters.manage');
        $existing = $this->findCharter((int) $id);
        $this->validateCharter('/charters/' . $id . '/edit');

        $removeFile = !empty($_POST['remove_file']);
        $file = $this->handleUpload('/charters/' . $id . '/edit');

        $fileFields = [
            'original_name' => $existing['original_name'],
            'stored_path' => $existing['stored_path'],
            'mime_type' => $existing['mime_type'],
            'file_size' => $existing['file_size'],
        ];
        $oldPathToDelete = null;
        if ($file !== null) {
            $oldPathToDelete = $existing['stored_path'];
            $fileFields = [
                'original_name' => $file['original_name'],
                'stored_path' => $file['stored_path'],
                'mime_type' => $file['mime_type'],
                'file_size' => $file['file_size'],
            ];
        } elseif ($removeFile) {
            $oldPathToDelete = $existing['stored_path'];
            $fileFields = ['original_name' => null, 'stored_path' => null, 'mime_type' => null, 'file_size' => null];
        }

        Database::pdo()->beginTransaction();
        try {
            $payload = $this->payload();
            if ($payload['status'] === 'current' && $existing['status'] !== 'current') {
                $this->demoteCurrent();
            }

            Database::pdo()->prepare(
                'UPDATE charters SET
                    revision_no = :revision_no, title = :title, enacted_on = :enacted_on,
                    approval_authority = :approval_authority, approval_doc_no = :approval_doc_no,
                    approval_date = :approval_date, status = :status, amendment_summary = :amendment_summary,
                    content = :content, original_name = :original_name, stored_path = :stored_path,
                    mime_type = :mime_type, file_size = :file_size, notes = :notes, updated_at = :updated_at
                 WHERE id = :id'
            )->execute($payload + $fileFields + [
                'updated_at' => now(),
                'id' => (int) $id,
            ]);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            Database::pdo()->rollBack();
            if ($file !== null && !empty($file['stored_path'])) {
                @unlink(storage_path((string) $file['stored_path']));
            }
            throw $e;
        }

        if ($oldPathToDelete) {
            $path = storage_path((string) $oldPathToDelete);
            if ($this->fileInStore($path) && is_file($path)) {
                @unlink($path);
            }
        }

        AuditLog::write('update', 'charters', 'charters', (int) $id);
        flash('success', '章程版本已更新。');
        redirect('/charters/' . $id);
    }

    /** 設為現行章程:自動將原現行版本降為歷史,確保僅有一筆現行。 */
    public function setCurrent(string $id): void
    {
        $this->requirePermission('charters.manage');
        $this->findCharter((int) $id);

        Database::pdo()->beginTransaction();
        try {
            $this->demoteCurrent();
            Database::pdo()->prepare('UPDATE charters SET status = "current", updated_at = :updated_at WHERE id = :id')
                ->execute(['updated_at' => now(), 'id' => (int) $id]);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            Database::pdo()->rollBack();
            throw $e;
        }

        AuditLog::write('set_current', 'charters', 'charters', (int) $id);
        flash('success', '已設為現行章程。');
        redirect('/charters/' . $id);
    }

    public function downloadFile(string $id): void
    {
        $this->requirePermission('charters.view');
        $charter = $this->findCharter((int) $id);

        if (empty($charter['stored_path'])) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到附件檔案']);
            exit;
        }

        $path = storage_path((string) $charter['stored_path']);
        if (!$this->fileInStore($path) || !is_file($path)) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到附件檔案']);
            exit;
        }

        AuditLog::write('download', 'charters', 'charters', (int) $charter['id']);
        header('Content-Type: ' . ($charter['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $charter['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('charters.manage');
        $charter = $this->findCharter((int) $id);

        if (!empty($charter['stored_path'])) {
            $path = storage_path((string) $charter['stored_path']);
            if ($this->fileInStore($path) && is_file($path)) {
                @unlink($path);
            }
        }

        Database::pdo()->prepare('DELETE FROM charters WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'charters', 'charters', (int) $id, ['title' => $charter['title']]);
        flash('success', '章程版本已刪除。');
        redirect('/charters');
    }

    // ---- 內部輔助 ----

    private function demoteCurrent(): void
    {
        Database::pdo()->exec('UPDATE charters SET status = "historical" WHERE status = "current"');
    }

    private function validateCharter(string $path): void
    {
        if ($error = Validator::required($_POST, ['title' => '章程名稱'])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $revisionNo = trim((string) ($_POST['revision_no'] ?? ''));
        if ($revisionNo !== '' && !ctype_digit($revisionNo)) {
            $this->backWithInput($path, $_POST, '修訂版次須為正整數。');
        }
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $revisionNo = trim((string) ($_POST['revision_no'] ?? ''));
        $status = ($_POST['status'] ?? 'historical') === 'current' ? 'current' : 'historical';

        return [
            'revision_no' => ($revisionNo !== '' && ctype_digit($revisionNo)) ? (int) $revisionNo : null,
            'title' => trim((string) $_POST['title']),
            'enacted_on' => $this->nullableDate('enacted_on'),
            'approval_authority' => $this->nullablePost('approval_authority'),
            'approval_doc_no' => $this->nullablePost('approval_doc_no'),
            'approval_date' => $this->nullableDate('approval_date'),
            'status' => $status,
            'amendment_summary' => $this->nullablePost('amendment_summary'),
            'content' => $this->nullablePost('content'),
            'notes' => $this->nullablePost('notes'),
        ];
    }

    /**
     * 處理章程文件上傳(選填),回傳新檔案的欄位,未選擇檔案時回傳 null。
     *
     * @return array{original_name: string, stored_path: string, mime_type: string, file_size: int}|null
     */
    private function handleUpload(string $back): ?array
    {
        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            return null;
        }
        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->backWithInput($back, $_POST, $this->uploadError((int) $file['error']));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 15 * 1024 * 1024) {
            $this->backWithInput($back, $_POST, '檔案大小需介於 1 byte 到 15MB。');
        }

        $originalName = basename((string) ($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->backWithInput($back, $_POST, '章程檔案僅支援 PDF 或圖片(JPG／PNG／GIF／WEBP)。');
        }
        $mime = $this->detectMime((string) $file['tmp_name']);
        if (!$this->allowedUploadMime($extension, $mime, (string) $file['tmp_name'])) {
            $this->backWithInput($back, $_POST, '檔案內容與副檔名不符,請確認後重新上傳。');
        }

        $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $relativePath = 'private_uploads/charters/' . $storedName;
        $targetDir = storage_path('private_uploads/charters');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            $this->backWithInput($back, $_POST, '無法建立上傳目錄。');
        }
        if (!move_uploaded_file((string) $file['tmp_name'], storage_path($relativePath))) {
            $this->backWithInput($back, $_POST, '檔案上傳失敗,請確認 storage 目錄權限。');
        }

        return [
            'original_name' => $originalName,
            'stored_path' => $relativePath,
            'mime_type' => $mime,
            'file_size' => $size,
        ];
    }

    private function fileInStore(string $path): bool
    {
        $base = storage_path('private_uploads/charters');
        $real = realpath($path);
        $realBase = realpath($base);
        return $real !== false && $realBase !== false && str_starts_with($real, $realBase . DIRECTORY_SEPARATOR);
    }

    private function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '檔案超過主機允許大小。',
            UPLOAD_ERR_PARTIAL => '檔案只有部分上傳,請重新上傳。',
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
        if (!in_array($mime, self::ALLOWED_MIME[$extension] ?? [], true)) {
            return false;
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && @getimagesize($path) === false) {
            return false;
        }
        return true;
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    private function nullableDate(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    /** @return array<string, mixed> */
    private function blankCharter(): array
    {
        $profile = foundation_profile();

        return [
            'revision_no' => '', 'title' => trim((string) $profile['foundation_name']) . '捐助章程',
            'enacted_on' => '', 'approval_authority' => $profile['competent_authority'] ?? '',
            'approval_doc_no' => '', 'approval_date' => '',
            'status' => 'historical', 'amendment_summary' => '', 'content' => '', 'notes' => '',
            'original_name' => null, 'stored_path' => null,
        ];
    }

    /** @return array<string, mixed> */
    private function findCharter(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT charters.*, users.name AS created_by_name
             FROM charters
             LEFT JOIN users ON users.id = charters.created_by
             WHERE charters.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $charter = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$charter) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到章程資料']);
            exit;
        }

        return $charter;
    }
}
