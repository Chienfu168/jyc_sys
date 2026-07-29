<?php

namespace App\Modules\Volunteers\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class VolunteerController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('volunteers.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['active', 'inactive'], true) ? (string) $_GET['status'] : '';

        $where = [];
        $params = ['month' => $month];
        if ($keyword !== '') {
            $where[] = '(volunteers.name LIKE :keyword OR volunteers.phone LIKE :keyword OR volunteers.email LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        if ($status !== '') {
            $where[] = 'volunteers.status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT volunteers.*,
                       COALESCE(SUM(CASE WHEN DATE_FORMAT(volunteer_service_logs.served_on, "%Y-%m") = :month THEN volunteer_service_logs.hours ELSE 0 END), 0) AS month_hours,
                       COALESCE(SUM(volunteer_service_logs.hours), 0) AS total_hours
                FROM volunteers
                LEFT JOIN volunteer_service_logs ON volunteer_service_logs.volunteer_id = volunteers.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY volunteers.id ORDER BY volunteers.status, volunteers.name, volunteers.id DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $volunteers = $stmt->fetchAll();

        $this->render('volunteers.index', [
            'title' => '志工管理',
            'section' => '業務與人事',
            'active' => 'volunteers',
            'month' => $month,
            'keyword' => $keyword,
            'status' => $status,
            'volunteers' => $volunteers,
            'summary' => $this->summary($volunteers),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('volunteers.manage');

        $this->render('volunteers.create', [
            'title' => '新增志工',
            'section' => '業務與人事',
            'active' => 'volunteers',
            'volunteer' => [
                'name' => '',
                'phone' => '',
                'email' => '',
                'status' => 'active',
                'notes' => '',
            ],
            'action' => '/volunteers',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('volunteers.manage');
        $this->validateVolunteer('/volunteers/create');

        Database::pdo()->prepare(
            'INSERT INTO volunteers
             (name, phone, email, status, notes, created_by, created_at, updated_at)
             VALUES
             (:name, :phone, :email, :status, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->volunteerPayload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'volunteers', 'volunteers', $id);
        flash('success', '志工資料已建立。');
        redirect('/volunteers/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('volunteers.view');
        $volunteer = $this->findVolunteer((int) $id);

        $this->render('volunteers.show', [
            'title' => '志工資料',
            'section' => '業務與人事',
            'active' => 'volunteers',
            'volunteer' => $volunteer,
            'logs' => $this->serviceLogs((int) $id),
            'totals' => $this->logTotals((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('volunteers.manage');

        $this->render('volunteers.edit', [
            'title' => '編輯志工',
            'section' => '業務與人事',
            'active' => 'volunteers',
            'volunteer' => $this->findVolunteer((int) $id),
            'action' => '/volunteers/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('volunteers.manage');
        $this->findVolunteer((int) $id);
        $this->validateVolunteer('/volunteers/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE volunteers
             SET name = :name,
                 phone = :phone,
                 email = :email,
                 status = :status,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->volunteerPayload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'volunteers', 'volunteers', (int) $id);
        flash('success', '志工資料已更新。');
        redirect('/volunteers/' . $id);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('volunteers.manage');
        $volunteer = $this->findVolunteer((int) $id);
        $status = $volunteer['status'] === 'active' ? 'inactive' : 'active';

        Database::pdo()->prepare('UPDATE volunteers SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('toggle', 'volunteers', 'volunteers', (int) $id);
        flash('success', '志工狀態已更新。');
        redirect('/volunteers/' . $id);
    }

    public function createServiceLog(string $id): void
    {
        $this->requirePermission('volunteers.manage');
        $volunteer = $this->findVolunteer((int) $id);

        $this->render('volunteer-service-logs.create', [
            'title' => '新增服務時數',
            'section' => '業務與人事',
            'active' => 'volunteers',
            'volunteer' => $volunteer,
            'log' => [
                'activity_id' => '',
                'served_on' => date('Y-m-d'),
                'hours' => '',
                'description' => '',
            ],
            'activities' => $this->activities(),
            'action' => '/volunteers/' . $id . '/service-logs',
        ]);
    }

    public function storeServiceLog(string $id): void
    {
        $this->requirePermission('volunteers.manage');
        $this->findVolunteer((int) $id);
        $this->validateServiceLog('/volunteers/' . $id . '/service-logs/create');

        Database::pdo()->prepare(
            'INSERT INTO volunteer_service_logs
             (volunteer_id, activity_id, served_on, hours, description, created_by, created_at, updated_at)
             VALUES
             (:volunteer_id, :activity_id, :served_on, :hours, :description, :created_by, :created_at, :updated_at)'
        )->execute($this->serviceLogPayload() + [
            'volunteer_id' => (int) $id,
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $logId = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'volunteers', 'volunteer_service_logs', $logId);
        flash('success', '服務時數已建立。');
        redirect('/volunteers/' . $id);
    }

    public function editServiceLog(string $id): void
    {
        $this->requirePermission('volunteers.manage');
        $log = $this->findServiceLog((int) $id);

        $this->render('volunteer-service-logs.edit', [
            'title' => '編輯服務時數',
            'section' => '業務與人事',
            'active' => 'volunteers',
            'volunteer' => $this->findVolunteer((int) $log['volunteer_id']),
            'log' => $log,
            'activities' => $this->activities(),
            'action' => '/volunteer-service-logs/' . $id,
        ]);
    }

    public function updateServiceLog(string $id): void
    {
        $this->requirePermission('volunteers.manage');
        $log = $this->findServiceLog((int) $id);
        $this->validateServiceLog('/volunteer-service-logs/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE volunteer_service_logs
             SET activity_id = :activity_id,
                 served_on = :served_on,
                 hours = :hours,
                 description = :description,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->serviceLogPayload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'volunteers', 'volunteer_service_logs', (int) $id);
        flash('success', '服務時數已更新。');
        redirect('/volunteers/' . $log['volunteer_id']);
    }

    private function validateVolunteer(string $path): void
    {
        if ($error = Validator::required($_POST, ['name' => '姓名'])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput($path, $_POST, 'Email 格式不正確。');
        }

        if (!in_array($_POST['status'] ?? '', ['active', 'inactive'], true)) {
            $this->backWithInput($path, $_POST, '狀態不正確。');
        }
    }

    private function validateServiceLog(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'served_on' => '服務日期',
            'hours' => '服務時數',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['served_on'])) {
            $this->backWithInput($path, $_POST, '服務日期格式不正確。');
        }

        if ($this->hoursValue() <= 0) {
            $this->backWithInput($path, $_POST, '服務時數需大於 0。');
        }
    }

    private function volunteerPayload(): array
    {
        return [
            'name' => trim((string) $_POST['name']),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'status' => (string) ($_POST['status'] ?? 'active'),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function serviceLogPayload(): array
    {
        $activityId = (int) ($_POST['activity_id'] ?? 0);

        return [
            'activity_id' => $activityId > 0 ? $activityId : null,
            'served_on' => (string) $_POST['served_on'],
            'hours' => $this->hoursValue(),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];
    }

    private function findVolunteer(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT volunteers.*, users.name AS created_by_name
             FROM volunteers
             LEFT JOIN users ON users.id = volunteers.created_by
             WHERE volunteers.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $volunteer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$volunteer) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到志工資料']);
            exit;
        }

        return $volunteer;
    }

    private function findServiceLog(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM volunteer_service_logs WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到服務時數']);
            exit;
        }

        return $log;
    }

    private function serviceLogs(int $volunteerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT volunteer_service_logs.*, activities.title AS activity_title
             FROM volunteer_service_logs
             LEFT JOIN activities ON activities.id = volunteer_service_logs.activity_id
             WHERE volunteer_service_logs.volunteer_id = :volunteer_id
             ORDER BY volunteer_service_logs.served_on DESC, volunteer_service_logs.id DESC'
        );
        $stmt->execute(['volunteer_id' => $volunteerId]);
        return $stmt->fetchAll();
    }

    private function logTotals(int $volunteerId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) AS log_count,
                    COALESCE(SUM(hours), 0) AS total_hours,
                    COALESCE(SUM(CASE WHEN YEAR(served_on) = YEAR(CURDATE()) THEN hours ELSE 0 END), 0) AS year_hours
             FROM volunteer_service_logs
             WHERE volunteer_id = :volunteer_id'
        );
        $stmt->execute(['volunteer_id' => $volunteerId]);
        return $stmt->fetch() ?: ['log_count' => 0, 'total_hours' => 0, 'year_hours' => 0];
    }

    private function activities(): array
    {
        try {
            return Database::pdo()->query('SELECT id, title FROM activities ORDER BY starts_at DESC, title')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    private function summary(array $volunteers): array
    {
        return [
            'total' => count($volunteers),
            'active' => count(array_filter($volunteers, static fn (array $volunteer): bool => $volunteer['status'] === 'active')),
            'month_hours' => array_sum(array_map(static fn (array $volunteer): float => (float) $volunteer['month_hours'], $volunteers)),
        ];
    }

    private function hoursValue(): float
    {
        return round((float) ($_POST['hours'] ?? 0), 2);
    }
}
