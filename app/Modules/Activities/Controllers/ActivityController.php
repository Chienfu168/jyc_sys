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
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $where = ['DATE_FORMAT(activities.starts_at, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'activities.status = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(activities.title LIKE :keyword OR activities.location LIKE :keyword OR activities.description LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }

        $stmt = Database::pdo()->prepare(
            'SELECT activities.*,
                    COALESCE(SUM(volunteer_service_logs.hours), 0) AS volunteer_hours,
                    COUNT(volunteer_service_logs.id) AS service_log_count
             FROM activities
             LEFT JOIN volunteer_service_logs ON volunteer_service_logs.activity_id = activities.id
             WHERE ' . implode(' AND ', $where) . '
             GROUP BY activities.id
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
            'keyword' => $keyword,
            'activities' => $activities,
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
                'status' => 'draft',
                'description' => '',
            ],
            'action' => '/activities',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('activities.manage');
        $this->validateActivity('/activities/create');

        Database::pdo()->prepare(
            'INSERT INTO activities
             (title, starts_at, ends_at, location, status, description, created_by, created_at, updated_at)
             VALUES
             (:title, :starts_at, :ends_at, :location, :status, :description, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
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
             SET title = :title,
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

        AuditLog::write('status', 'activities', 'activities', (int) $id);
        flash('success', '活動狀態已更新。');
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
    }

    private function payload(): array
    {
        return [
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
            'SELECT activities.*, users.name AS created_by_name
             FROM activities
             LEFT JOIN users ON users.id = activities.created_by
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

    private function summary(array $activities): array
    {
        return [
            'total' => count($activities),
            'published' => count(array_filter($activities, static fn (array $activity): bool => $activity['status'] === 'published')),
            'volunteer_hours' => array_sum(array_map(static fn (array $activity): float => (float) $activity['volunteer_hours'], $activities)),
        ];
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
