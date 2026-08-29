<?php

namespace App\Modules\BoardMeetings\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\BoardMeetings\MeetingLabel;
use PDO;

/**
 * 董事會議管理:同一筆會議資料涵蓋會前的「會議議程」與會後補齊的「會議紀錄」,
 * 參考新北市教育局提供的董事會議紀錄範例格式(屆次、出列席、報告事項、
 * 討論事項之案由與決議、臨時動議),並可列印留存(含簽到表)。
 */
final class BoardMeetingController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('board_meetings.view');

        $status = in_array(($_GET['status'] ?? ''), ['draft', 'confirmed'], true) ? (string) $_GET['status'] : '';

        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT * FROM board_meetings';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY meeting_date DESC, term_no DESC, session_no DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $meetings = $stmt->fetchAll();

        $this->render('board-meetings.index', [
            'title' => '董事會議',
            'section' => '主管機關核備',
            'active' => 'board-meetings',
            'meetings' => $meetings,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('board_meetings.manage');

        $this->render('board-meetings.form', [
            'title' => '新增董事會議',
            'section' => '主管機關核備',
            'active' => 'board-meetings',
            'meeting' => $this->blankMeeting(),
            'attendees' => [],
            'agendaItems' => [['subject' => '', 'resolution' => '']],
            'action' => '/board-meetings',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('board_meetings.manage');
        $this->validateMeeting('/board-meetings/create');
        $attendees = $this->validatedAttendees();
        $agendaItems = $this->validatedAgendaItems();

        Database::pdo()->prepare(
            'INSERT INTO board_meetings
             (term_no, session_no, meeting_date, meeting_time, location, chairperson, recorder, chair_remarks, report_items, extempore_motions, attachments, status, notes, created_by, created_at, updated_at)
             VALUES
             (:term_no, :session_no, :meeting_date, :meeting_time, :location, :chairperson, :recorder, :chair_remarks, :report_items, :extempore_motions, :attachments, :status, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        $this->replaceAttendees($id, $attendees);
        $this->replaceAgendaItems($id, $agendaItems);

        AuditLog::write('create', 'board_meetings', 'board_meetings', $id);
        flash('success', '董事會議已建立。');
        redirect('/board-meetings/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('board_meetings.view');

        $meeting = $this->findMeeting((int) $id);

        $this->render('board-meetings.show', [
            'title' => MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no']),
            'section' => '主管機關核備',
            'active' => 'board-meetings',
            'meeting' => $meeting,
            'attendees' => $this->attendees((int) $id),
            'agendaItems' => $this->agendaItems((int) $id),
            'files' => $this->files((int) $id),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('board_meetings.manage');

        $meeting = $this->findMeeting((int) $id);
        $agendaItems = $this->agendaItems((int) $id);

        $this->render('board-meetings.form', [
            'title' => '編輯董事會議',
            'section' => '主管機關核備',
            'active' => 'board-meetings',
            'meeting' => $meeting,
            'attendees' => $this->attendees((int) $id),
            'agendaItems' => $agendaItems ?: [['subject' => '', 'resolution' => '']],
            'action' => '/board-meetings/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $meeting = $this->findMeeting((int) $id);
        $this->validateMeeting('/board-meetings/' . $id . '/edit', (int) $id);
        $attendees = $this->validatedAttendees();
        $agendaItems = $this->validatedAgendaItems();

        Database::pdo()->prepare(
            'UPDATE board_meetings
             SET term_no = :term_no,
                 session_no = :session_no,
                 meeting_date = :meeting_date,
                 meeting_time = :meeting_time,
                 location = :location,
                 chairperson = :chairperson,
                 recorder = :recorder,
                 chair_remarks = :chair_remarks,
                 report_items = :report_items,
                 extempore_motions = :extempore_motions,
                 attachments = :attachments,
                 status = :status,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => $meeting['id'],
        ]);

        $this->replaceAttendees((int) $id, $attendees);
        $this->replaceAgendaItems((int) $id, $agendaItems);

        AuditLog::write('update', 'board_meetings', 'board_meetings', (int) $id);
        flash('success', '董事會議已更新。');
        redirect('/board-meetings/' . $id);
    }

    public function confirm(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $meeting = $this->findMeeting((int) $id);

        if (trim((string) $meeting['chairperson']) === '' || trim((string) $meeting['recorder']) === '') {
            flash('error', '確認為會議紀錄前，請先填寫主席與紀錄姓名。');
            redirect('/board-meetings/' . $id . '/edit');
        }

        Database::pdo()->prepare('UPDATE board_meetings SET status = "confirmed", updated_at = :updated_at WHERE id = :id')
            ->execute(['updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('confirm', 'board_meetings', 'board_meetings', (int) $id);
        flash('success', '已確認為正式會議紀錄。');
        redirect('/board-meetings/' . $id);
    }

    public function print(string $id): void
    {
        $this->requirePermission('board_meetings.view');

        $meeting = $this->findMeeting((int) $id);
        $type = in_array(($_GET['type'] ?? ''), ['agenda', 'minutes', 'signin'], true) ? (string) $_GET['type'] : 'minutes';

        $this->render('board-meetings.print', [
            'title' => MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no']),
            'section' => '主管機關核備',
            'active' => 'board-meetings',
            'meeting' => $meeting,
            'attendees' => $this->attendees((int) $id),
            'agendaItems' => $this->agendaItems((int) $id),
            'files' => $this->files((int) $id),
            'type' => $type,
            'profile' => foundation_profile(),
            'printable' => false,
        ]);
    }

    public function uploadFile(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $meeting = $this->findMeeting((int) $id);

        $category = ($_POST['category'] ?? '') === 'signin_sheet' ? 'signin_sheet' : 'attachment';
        $back = '/board-meetings/' . $id;

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            $this->backWithInput($back, $_POST, '請選擇要上傳的檔案。');
        }
        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->backWithInput($back, $_POST, $this->uploadError((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)));
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 15 * 1024 * 1024) {
            $this->backWithInput($back, $_POST, '檔案大小需介於 1 byte 到 15MB。');
        }

        $originalName = basename((string) ($file['name'] ?? ''));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowed, true)) {
            $this->backWithInput($back, $_POST, '附件僅支援 PDF 或圖片(JPG／PNG／GIF／WEBP)。');
        }
        $mime = $this->detectMime((string) $file['tmp_name']);
        if (!$this->allowedUploadMime($extension, $mime, (string) $file['tmp_name'])) {
            $this->backWithInput($back, $_POST, '檔案內容與副檔名不符,請確認後重新上傳。');
        }

        $storedName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $relativePath = 'private_uploads/board_meetings/' . (int) $meeting['id'] . '/' . $storedName;
        $targetDir = storage_path('private_uploads/board_meetings/' . (int) $meeting['id']);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            $this->backWithInput($back, $_POST, '無法建立上傳目錄。');
        }
        if (!move_uploaded_file((string) $file['tmp_name'], storage_path($relativePath))) {
            $this->backWithInput($back, $_POST, '檔案上傳失敗,請確認 storage 目錄權限。');
        }

        $nextSort = (int) $this->scalar(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM board_meeting_files WHERE board_meeting_id = :id AND category = :category',
            ['id' => (int) $meeting['id'], 'category' => $category]
        );

        Database::pdo()->prepare(
            'INSERT INTO board_meeting_files
             (board_meeting_id, category, title, original_name, stored_path, mime_type, file_size, sort_order, uploaded_by, created_at)
             VALUES
             (:board_meeting_id, :category, :title, :original_name, :stored_path, :mime_type, :file_size, :sort_order, :uploaded_by, :created_at)'
        )->execute([
            'board_meeting_id' => (int) $meeting['id'],
            'category' => $category,
            'title' => trim((string) ($_POST['title'] ?? '')) ?: null,
            'original_name' => $originalName,
            'stored_path' => $relativePath,
            'mime_type' => $mime,
            'file_size' => $size,
            'sort_order' => $nextSort,
            'uploaded_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
        ]);

        AuditLog::write('upload', 'board_meetings', 'board_meeting_files', (int) Database::pdo()->lastInsertId());
        flash('success', $category === 'signin_sheet' ? '簽到簿掃描檔已上傳。' : '附件已上傳。');
        redirect($back);
    }

    public function downloadFile(string $id, string $fileId): void
    {
        $this->requirePermission('board_meetings.view');
        $this->findMeeting((int) $id);
        $file = $this->findFile((int) $id, (int) $fileId);
        $path = storage_path((string) $file['stored_path']);

        if (!$this->fileInStore($path) || !is_file($path)) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到附件檔案']);
            exit;
        }

        AuditLog::write('download', 'board_meetings', 'board_meeting_files', (int) $file['id']);
        header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . rawurlencode((string) $file['original_name']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function deleteFile(string $id, string $fileId): void
    {
        $this->requirePermission('board_meetings.manage');
        $this->findMeeting((int) $id);
        $file = $this->findFile((int) $id, (int) $fileId);

        $path = storage_path((string) $file['stored_path']);
        if ($this->fileInStore($path) && is_file($path)) {
            @unlink($path);
        }
        Database::pdo()->prepare('DELETE FROM board_meeting_files WHERE id = :id')->execute(['id' => (int) $file['id']]);

        AuditLog::write('delete', 'board_meetings', 'board_meeting_files', (int) $file['id']);
        flash('success', '附件已刪除。');
        redirect('/board-meetings/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $meeting = $this->findMeeting((int) $id);

        // 先清除上傳檔案實體(資料列由外鍵串聯刪除)。
        foreach ($this->files((int) $id) as $file) {
            $path = storage_path((string) $file['stored_path']);
            if ($this->fileInStore($path) && is_file($path)) {
                @unlink($path);
            }
        }

        Database::pdo()->prepare('DELETE FROM board_meetings WHERE id = :id')
            ->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'board_meetings', 'board_meetings', (int) $id, [
            'session' => MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no']),
        ]);
        flash('success', '董事會議已刪除。');
        redirect('/board-meetings');
    }

    private function validateMeeting(string $path, ?int $ignoreId = null): void
    {
        if ($error = Validator::required($_POST, [
            'term_no' => '屆別',
            'session_no' => '次別',
            'meeting_date' => '會議日期',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['meeting_date'] ?? ''))) {
            $this->backWithInput($path, $_POST, '會議日期格式不正確。');
        }

        $termNo = (int) ($_POST['term_no'] ?? 0);
        $sessionNo = (int) ($_POST['session_no'] ?? 0);
        if ($termNo <= 0 || $sessionNo <= 0) {
            $this->backWithInput($path, $_POST, '屆別與次別需為正整數。');
        }

        $sql = 'SELECT id FROM board_meetings WHERE term_no = :term_no AND session_no = :session_no';
        $params = ['term_no' => $termNo, 'session_no' => $sessionNo];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            $this->backWithInput($path, $_POST, '此屆次的董事會議紀錄已存在。');
        }
    }

    /**
     * @return array<int, array{name: string, role: string, attendance_status: string}>
     */
    private function validatedAttendees(): array
    {
        $posted = $_POST['attendees'] ?? [];
        if (!is_array($posted)) {
            return [];
        }

        $attendees = [];
        foreach ($posted as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $role = in_array($row['role'] ?? '', ['director', 'observer'], true) ? (string) $row['role'] : 'director';
            $attendanceStatus = in_array($row['attendance_status'] ?? '', ['present', 'leave', 'proxy'], true)
                ? (string) $row['attendance_status']
                : 'present';
            $attendees[] = ['name' => $name, 'role' => $role, 'attendance_status' => $attendanceStatus];
        }

        return $attendees;
    }

    /**
     * @return array<int, array{subject: string, resolution: string}>
     */
    private function validatedAgendaItems(): array
    {
        $posted = $_POST['agenda'] ?? [];
        if (!is_array($posted)) {
            return [];
        }

        $items = [];
        foreach ($posted as $row) {
            if (!is_array($row)) {
                continue;
            }
            $subject = trim((string) ($row['subject'] ?? ''));
            $explanation = trim((string) ($row['explanation'] ?? ''));
            $proposal = trim((string) ($row['proposal'] ?? ''));
            $resolution = trim((string) ($row['resolution'] ?? ''));
            if ($subject === '' && $explanation === '' && $proposal === '' && $resolution === '') {
                continue;
            }
            $items[] = [
                'subject' => $subject,
                'explanation' => $explanation,
                'proposal' => $proposal,
                'resolution' => $resolution,
            ];
        }

        return $items;
    }

    private function replaceAttendees(int $meetingId, array $attendees): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM board_meeting_attendees WHERE board_meeting_id = :id')->execute(['id' => $meetingId]);

        $stmt = $pdo->prepare(
            'INSERT INTO board_meeting_attendees (board_meeting_id, name, role, attendance_status, sort_order)
             VALUES (:board_meeting_id, :name, :role, :attendance_status, :sort_order)'
        );
        foreach (array_values($attendees) as $index => $attendee) {
            $stmt->execute([
                'board_meeting_id' => $meetingId,
                'name' => $attendee['name'],
                'role' => $attendee['role'],
                'attendance_status' => $attendee['attendance_status'],
                'sort_order' => $index,
            ]);
        }
    }

    private function replaceAgendaItems(int $meetingId, array $items): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM board_meeting_agenda_items WHERE board_meeting_id = :id')->execute(['id' => $meetingId]);

        $stmt = $pdo->prepare(
            'INSERT INTO board_meeting_agenda_items (board_meeting_id, sort_order, subject, explanation, proposal, resolution)
             VALUES (:board_meeting_id, :sort_order, :subject, :explanation, :proposal, :resolution)'
        );
        foreach (array_values($items) as $index => $item) {
            $stmt->execute([
                'board_meeting_id' => $meetingId,
                'sort_order' => $index,
                'subject' => $item['subject'],
                'explanation' => $item['explanation'] ?? '',
                'proposal' => $item['proposal'] ?? '',
                'resolution' => $item['resolution'],
            ]);
        }
    }

    private function payload(): array
    {
        return [
            'term_no' => (int) $_POST['term_no'],
            'session_no' => (int) $_POST['session_no'],
            'meeting_date' => (string) $_POST['meeting_date'],
            'meeting_time' => $this->nullableText('meeting_time'),
            'location' => $this->nullableText('location'),
            'chairperson' => $this->nullableText('chairperson'),
            'recorder' => $this->nullableText('recorder'),
            'chair_remarks' => trim((string) ($_POST['chair_remarks'] ?? '')),
            'report_items' => trim((string) ($_POST['report_items'] ?? '')),
            'extempore_motions' => trim((string) ($_POST['extempore_motions'] ?? '')),
            'attachments' => trim((string) ($_POST['attachments'] ?? '')),
            'status' => in_array($_POST['status'] ?? '', ['draft', 'confirmed'], true) ? (string) $_POST['status'] : 'draft',
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findMeeting(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM board_meetings WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$meeting) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到董事會議資料']);
            exit;
        }

        return $meeting;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attendees(int $meetingId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM board_meeting_attendees WHERE board_meeting_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $meetingId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function agendaItems(int $meetingId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM board_meeting_agenda_items WHERE board_meeting_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $meetingId]);

        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function files(int $meetingId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM board_meeting_files WHERE board_meeting_id = :id
             ORDER BY FIELD(category, "attachment", "signin_sheet"), sort_order, id'
        );
        $stmt->execute(['id' => $meetingId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed> */
    private function findFile(int $meetingId, int $fileId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM board_meeting_files WHERE id = :id AND board_meeting_id = :meeting_id LIMIT 1'
        );
        $stmt->execute(['id' => $fileId, 'meeting_id' => $meetingId]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$file) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到附件檔案']);
            exit;
        }
        return $file;
    }

    /** @param array<string, mixed> $params */
    private function scalar(string $sql, array $params): mixed
    {
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /** 確認路徑確實位於董事會議上傳目錄下,避免路徑穿越。 */
    private function fileInStore(string $path): bool
    {
        $base = storage_path('private_uploads/board_meetings');
        $real = realpath($path);
        $realBase = realpath($base);
        return $real !== false && $realBase !== false && str_starts_with($real, $realBase . DIRECTORY_SEPARATOR);
    }

    private function uploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => '檔案超過主機允許大小。',
            UPLOAD_ERR_PARTIAL => '檔案只有部分上傳,請重新上傳。',
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
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ];
        if (!in_array($mime, $allowed[$extension] ?? [], true)) {
            return false;
        }
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && @getimagesize($path) === false) {
            return false;
        }
        return true;
    }

    private function blankMeeting(): array
    {
        return [
            'term_no' => '',
            'session_no' => '',
            'meeting_date' => date('Y-m-d'),
            'meeting_time' => '',
            'location' => '',
            'chairperson' => '',
            'recorder' => '',
            'chair_remarks' => '',
            'report_items' => '',
            'extempore_motions' => '',
            'attachments' => '',
            'status' => 'draft',
            'notes' => '',
        ];
    }

    private function nullableText(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }
}
