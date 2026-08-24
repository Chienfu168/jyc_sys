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
             (term_no, session_no, meeting_date, meeting_time, location, chairperson, recorder, report_items, extempore_motions, status, notes, created_by, created_at, updated_at)
             VALUES
             (:term_no, :session_no, :meeting_date, :meeting_time, :location, :chairperson, :recorder, :report_items, :extempore_motions, :status, :notes, :created_by, :created_at, :updated_at)'
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
                 report_items = :report_items,
                 extempore_motions = :extempore_motions,
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
        $type = ($_GET['type'] ?? '') === 'agenda' ? 'agenda' : 'minutes';

        $this->render('board-meetings.print', [
            'title' => MeetingLabel::sessionTitle((int) $meeting['term_no'], (int) $meeting['session_no']),
            'section' => '主管機關核備',
            'active' => 'board-meetings',
            'meeting' => $meeting,
            'attendees' => $this->attendees((int) $id),
            'agendaItems' => $this->agendaItems((int) $id),
            'type' => $type,
            'profile' => foundation_profile(),
            'printable' => false,
        ]);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $meeting = $this->findMeeting((int) $id);

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
            $resolution = trim((string) ($row['resolution'] ?? ''));
            if ($subject === '' && $resolution === '') {
                continue;
            }
            $items[] = ['subject' => $subject, 'resolution' => $resolution];
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
            'INSERT INTO board_meeting_agenda_items (board_meeting_id, sort_order, subject, resolution)
             VALUES (:board_meeting_id, :sort_order, :subject, :resolution)'
        );
        foreach (array_values($items) as $index => $item) {
            $stmt->execute([
                'board_meeting_id' => $meetingId,
                'sort_order' => $index,
                'subject' => $item['subject'],
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
            'report_items' => trim((string) ($_POST['report_items'] ?? '')),
            'extempore_motions' => trim((string) ($_POST['extempore_motions'] ?? '')),
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
            'report_items' => '',
            'extempore_motions' => '',
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
