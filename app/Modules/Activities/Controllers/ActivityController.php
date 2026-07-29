<?php

namespace App\Modules\Activities\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class ActivityController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('activities.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $status = in_array(($_GET['status'] ?? ''), ['draft', 'published', 'closed', 'cancelled'], true) ? (string) $_GET['status'] : '';
        $projectId = (int) ($_GET['project_id'] ?? 0);
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $where = ['DATE_FORMAT(activities.starts_at, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'activities.status = :status';
            $params['status'] = $status;
        }
        if ($projectId > 0) {
            $where[] = 'activities.project_id = :project_id';
            $params['project_id'] = $projectId;
        }
        if ($keyword !== '') {
            $where[] = '(activities.title LIKE :keyword OR activities.location LIKE :keyword OR activities.description LIKE :keyword OR projects.name LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT activities.*,
                    projects.name AS project_name,
                    projects.project_code,
                    COALESCE(volunteer_stats.volunteer_hours, 0) AS volunteer_hours,
                    COALESCE(volunteer_stats.service_log_count, 0) AS service_log_count,
                    COALESCE(participant_stats.registered_count, 0) AS registered_count,
                    COALESCE(participant_stats.attended_count, 0) AS attended_count
             FROM activities
             LEFT JOIN projects ON projects.id = activities.project_id
             LEFT JOIN (
                SELECT activity_id, SUM(hours) AS volunteer_hours, COUNT(id) AS service_log_count
                FROM volunteer_service_logs
                GROUP BY activity_id
             ) AS volunteer_stats ON volunteer_stats.activity_id = activities.id
             LEFT JOIN (
                SELECT activity_id,
                       COUNT(id) AS registered_count,
                       SUM(CASE WHEN status = "attended" THEN 1 ELSE 0 END) AS attended_count
                FROM activity_participants
                GROUP BY activity_id
             ) AS participant_stats ON participant_stats.activity_id = activities.id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY activities.starts_at DESC, activities.id DESC'
        );
        $stmt->execute($params);
        $activities = $stmt->fetchAll();

        $this->render('activities.index', [
            'title' => '活動管理',
            'section' => '業務與人事',
            'active' => 'activities',
            'month' => $month,
            'status' => $status,
            'projectId' => $projectId,
            'keyword' => $keyword,
            'activities' => $activities,
            'projects' => $this->projects(),
            'summary' => $this->summary($activities),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('activities.manage');

        $this->render('activities.create', [
            'title' => '新增活動',
            'section' => '業務與人事',
            'active' => 'activities',
            'activity' => [
                'title' => '',
                'starts_at' => date('Y-m-d H:i'),
                'ends_at' => '',
                'location' => '',
                'project_id' => '',
                'status' => 'draft',
                'description' => '',
            ],
            'projects' => $this->projects(),
            'action' => '/activities',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('activities.manage');
        $this->validateActivity('/activities/create');

        Database::pdo()->prepare(
            'INSERT INTO activities
             (project_id, title, starts_at, ends_at, location, status, description, created_by, created_at, updated_at)
             VALUES
             (:project_id, :title, :starts_at, :ends_at, :location, :status, :description, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        $activity = $this->findActivity($id);
        $this->syncCalendarEvent($activity);
        AuditLog::write('create', 'activities', 'activities', $id);
        flash('success', '活動資料已建立。');
        redirect('/activities/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('activities.view');

        $this->render('activities.show', [
            'title' => '活動資料',
            'section' => '業務與人事',
            'active' => 'activities',
            'activity' => $this->findActivity((int) $id),
            'calendarEvent' => $this->calendarEvent((int) $id),
            'participants' => $this->participants((int) $id),
            'outcome' => $this->outcome((int) $id),
            'volunteerLogs' => $this->volunteerLogs((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('activities.manage');

        $this->render('activities.edit', [
            'title' => '編輯活動',
            'section' => '業務與人事',
            'active' => 'activities',
            'activity' => $this->findActivity((int) $id),
            'projects' => $this->projects(),
            'action' => '/activities/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('activities.manage');
        $this->findActivity((int) $id);
        $this->validateActivity('/activities/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE activities
             SET project_id = :project_id,
                 title = :title,
                 starts_at = :starts_at,
                 ends_at = :ends_at,
                 location = :location,
                 status = :status,
                 description = :description,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        $this->syncCalendarEvent($this->findActivity((int) $id));
        AuditLog::write('update', 'activities', 'activities', (int) $id);
        flash('success', '活動資料已更新。');
        redirect('/activities/' . $id);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('activities.manage');
        $this->findActivity((int) $id);

        $status = in_array(($_POST['status'] ?? ''), ['draft', 'published', 'closed', 'cancelled'], true)
            ? (string) $_POST['status']
            : 'draft';

        Database::pdo()->prepare('UPDATE activities SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        $this->syncCalendarEvent($this->findActivity((int) $id));
        AuditLog::write('status', 'activities', 'activities', (int) $id);
        flash('success', '活動狀態已更新。');
        redirect('/activities/' . $id);
    }

    public function storeParticipant(string $id): void
    {
        $this->requirePermission('activities.manage');
        $activity = $this->findActivity((int) $id);

        if ($error = Validator::required($_POST, [
            'name' => '參加者姓名',
        ])) {
            $this->backWithInput('/activities/' . $id, $_POST, $error);
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput('/activities/' . $id, $_POST, 'Email 格式不正確。');
        }

        $status = in_array(($_POST['status'] ?? ''), ['registered', 'attended', 'absent', 'cancelled'], true)
            ? (string) $_POST['status']
            : 'registered';

        Database::pdo()->prepare(
            'INSERT INTO activity_participants
             (activity_id, name, phone, email, organization, status, checked_in_at, notes, created_by, created_at, updated_at)
             VALUES
             (:activity_id, :name, :phone, :email, :organization, :status, :checked_in_at, :notes, :created_by, :created_at, :updated_at)'
        )->execute([
            'activity_id' => (int) $activity['id'],
            'name' => trim((string) $_POST['name']),
            'phone' => $this->nullablePost('phone'),
            'email' => $email !== '' ? $email : null,
            'organization' => $this->nullablePost('organization'),
            'status' => $status,
            'checked_in_at' => $status === 'attended' ? now() : null,
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::write('create', 'activities', 'activity_participants', (int) Database::pdo()->lastInsertId());
        flash('success', '參加者已加入名冊。');
        redirect('/activities/' . $id);
    }

    public function updateParticipantStatus(string $id, string $participantId): void
    {
        $this->requirePermission('activities.manage');
        $this->findActivity((int) $id);

        $status = in_array(($_POST['status'] ?? ''), ['registered', 'attended', 'absent', 'cancelled'], true)
            ? (string) $_POST['status']
            : 'registered';

        Database::pdo()->prepare(
            'UPDATE activity_participants
             SET status = :status,
                 checked_in_at = CASE WHEN :status_for_checkin = "attended" AND checked_in_at IS NULL THEN :checked_in_at ELSE checked_in_at END,
                 updated_at = :updated_at
             WHERE id = :id AND activity_id = :activity_id'
        )->execute([
            'status' => $status,
            'status_for_checkin' => $status,
            'checked_in_at' => now(),
            'updated_at' => now(),
            'id' => (int) $participantId,
            'activity_id' => (int) $id,
        ]);

        AuditLog::write('status', 'activities', 'activity_participants', (int) $participantId);
        flash('success', '參加者出席狀態已更新。');
        redirect('/activities/' . $id);
    }

    public function updateOutcome(string $id): void
    {
        $this->requirePermission('activities.manage');
        $activity = $this->findActivity((int) $id);

        $actualParticipants = max(0, (int) ($_POST['actual_participants'] ?? 0));
        $satisfactionScore = trim((string) ($_POST['satisfaction_score'] ?? ''));
        if ($satisfactionScore !== '' && ((float) $satisfactionScore < 0 || (float) $satisfactionScore > 5)) {
            $this->backWithInput('/activities/' . $id, $_POST, '滿意度分數請填 0 到 5。');
        }

        Database::pdo()->prepare(
            'INSERT INTO activity_outcomes
             (activity_id, actual_participants, satisfaction_score, outcome_summary, improvement_notes, updated_by, created_at, updated_at)
             VALUES
             (:activity_id, :actual_participants, :satisfaction_score, :outcome_summary, :improvement_notes, :updated_by, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
               actual_participants = VALUES(actual_participants),
               satisfaction_score = VALUES(satisfaction_score),
               outcome_summary = VALUES(outcome_summary),
               improvement_notes = VALUES(improvement_notes),
               updated_by = VALUES(updated_by),
               updated_at = VALUES(updated_at)'
        )->execute([
            'activity_id' => (int) $activity['id'],
            'actual_participants' => $actualParticipants,
            'satisfaction_score' => $satisfactionScore !== '' ? round((float) $satisfactionScore, 2) : null,
            'outcome_summary' => trim((string) ($_POST['outcome_summary'] ?? '')),
            'improvement_notes' => trim((string) ($_POST['improvement_notes'] ?? '')),
            'updated_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::write('update', 'activities', 'activity_outcomes', (int) $activity['id']);
        flash('success', '活動成果紀錄已更新。');
        redirect('/activities/' . $id);
    }

    private function validateActivity(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'title' => '活動名稱',
            'starts_at' => '開始時間',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!$this->datetimeValue('starts_at')) {
            $this->backWithInput($path, $_POST, '開始時間格式不正確。');
        }

        $endsAt = $this->datetimeValue('ends_at');
        if (trim((string) ($_POST['ends_at'] ?? '')) !== '' && !$endsAt) {
            $this->backWithInput($path, $_POST, '結束時間格式不正確。');
        }

        if ($endsAt && $endsAt < $this->datetimeValue('starts_at')) {
            $this->backWithInput($path, $_POST, '結束時間不可早於開始時間。');
        }

        if (!in_array($_POST['status'] ?? '', ['draft', 'published', 'closed', 'cancelled'], true)) {
            $this->backWithInput($path, $_POST, '活動狀態不正確。');
        }

        $projectId = (int) ($_POST['project_id'] ?? 0);
        if ($projectId > 0 && !$this->projectExists($projectId)) {
            $this->backWithInput($path, $_POST, '選擇的專案不存在。');
        }
    }

    private function payload(): array
    {
        $projectId = (int) ($_POST['project_id'] ?? 0);

        return [
            'project_id' => $projectId > 0 ? $projectId : null,
            'title' => trim((string) $_POST['title']),
            'starts_at' => $this->datetimeValue('starts_at'),
            'ends_at' => $this->datetimeValue('ends_at'),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'draft'),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];
    }

    private function findActivity(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT activities.*,
                    users.name AS created_by_name,
                    projects.name AS project_name,
                    projects.project_code
             FROM activities
             LEFT JOIN users ON users.id = activities.created_by
             LEFT JOIN projects ON projects.id = activities.project_id
             WHERE activities.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $activity = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$activity) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到活動資料']);
            exit;
        }

        return $activity;
    }

    private function projects(): array
    {
        return Database::pdo()->query(
            'SELECT id, project_code, name, status
             FROM projects
             ORDER BY FIELD(status, "active", "planning", "closed", "cancelled"), start_date DESC, id DESC'
        )->fetchAll();
    }

    private function projectExists(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    private function calendarEvent(int $activityId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, status FROM calendar_events
             WHERE source_module = "activities" AND source_id = :activity_id
             LIMIT 1'
        );
        $stmt->execute(['activity_id' => $activityId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        return $event ?: null;
    }

    private function syncCalendarEvent(array $activity): void
    {
        $eventStatus = match ($activity['status']) {
            'closed' => 'done',
            'cancelled' => 'cancelled',
            default => 'scheduled',
        };

        $existing = $this->calendarEvent((int) $activity['id']);
        $payload = [
            'title' => $activity['title'],
            'starts_at' => $activity['starts_at'],
            'ends_at' => $activity['ends_at'] ?: null,
            'location' => $activity['location'] ?: null,
            'owner_name' => auth()->user()['name'] ?? null,
            'status' => $eventStatus,
            'description' => $activity['description'] ?: null,
            'updated_at' => now(),
        ];

        if ($existing) {
            Database::pdo()->prepare(
                'UPDATE calendar_events
                 SET title = :title,
                     event_type = "activity",
                     starts_at = :starts_at,
                     ends_at = :ends_at,
                     all_day = 0,
                     location = :location,
                     owner_name = COALESCE(owner_name, :owner_name),
                     reminder_minutes = CASE WHEN reminder_minutes = 0 THEN 60 ELSE reminder_minutes END,
                     status = :status,
                     description = :description,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($payload + ['id' => (int) $existing['id']]);
            return;
        }

        Database::pdo()->prepare(
            'INSERT INTO calendar_events
             (title, event_type, starts_at, ends_at, all_day, location, owner_name, reminder_minutes, status, description, source_module, source_id, created_by, created_at, updated_at)
             VALUES
             (:title, "activity", :starts_at, :ends_at, 0, :location, :owner_name, 60, :status, :description, "activities", :source_id, :created_by, :created_at, :updated_at)'
        )->execute($payload + [
            'source_id' => (int) $activity['id'],
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
        ]);
    }

    private function volunteerLogs(int $activityId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT volunteer_service_logs.*, volunteers.name AS volunteer_name
             FROM volunteer_service_logs
             INNER JOIN volunteers ON volunteers.id = volunteer_service_logs.volunteer_id
             WHERE volunteer_service_logs.activity_id = :activity_id
             ORDER BY volunteer_service_logs.served_on, volunteers.name'
        );
        $stmt->execute(['activity_id' => $activityId]);
        return $stmt->fetchAll();
    }

    private function participants(int $activityId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT activity_participants.*, users.name AS created_by_name
             FROM activity_participants
             LEFT JOIN users ON users.id = activity_participants.created_by
             WHERE activity_participants.activity_id = :activity_id
             ORDER BY FIELD(activity_participants.status, "attended", "registered", "absent", "cancelled"), activity_participants.id'
        );
        $stmt->execute(['activity_id' => $activityId]);
        return $stmt->fetchAll();
    }

    private function outcome(int $activityId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT activity_outcomes.*, users.name AS updated_by_name
             FROM activity_outcomes
             LEFT JOIN users ON users.id = activity_outcomes.updated_by
             WHERE activity_outcomes.activity_id = :activity_id
             LIMIT 1'
        );
        $stmt->execute(['activity_id' => $activityId]);
        $outcome = $stmt->fetch(PDO::FETCH_ASSOC);

        return $outcome ?: [
            'actual_participants' => '',
            'satisfaction_score' => '',
            'outcome_summary' => '',
            'improvement_notes' => '',
            'updated_by_name' => '',
            'updated_at' => '',
        ];
    }

    private function summary(array $activities): array
    {
        return [
            'total' => count($activities),
            'published' => count(array_filter($activities, static fn (array $activity): bool => $activity['status'] === 'published')),
            'volunteer_hours' => array_sum(array_map(static fn (array $activity): float => (float) $activity['volunteer_hours'], $activities)),
        ];
    }

    private function nullablePost(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    private function datetimeValue(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return null;
        }

        return $value . ':00';
    }
}
