<?php

namespace App\Modules\LeaveRequests\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use DateTimeImmutable;
use PDO;

final class LeaveRequestController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('leave_requests.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');
        $status = in_array(($_GET['status'] ?? ''), ['draft', 'submitted', 'approved', 'rejected', 'cancelled'], true) ? (string) $_GET['status'] : '';

        $where = ['DATE_FORMAT(leave_requests.start_date, "%Y-%m") = :month'];
        $params = ['month' => $month];
        if ($status !== '') {
            $where[] = 'leave_requests.status = :status';
            $params['status'] = $status;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT leave_requests.*, personnel_employees.name AS employee_name,
                    personnel_employees.department, personnel_employees.job_title,
                    leave_types.name AS leave_type_name
             FROM leave_requests
             INNER JOIN personnel_employees ON personnel_employees.id = leave_requests.employee_id
             INNER JOIN leave_types ON leave_types.id = leave_requests.leave_type_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY leave_requests.start_date DESC, leave_requests.id DESC'
        );
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        $this->render('leave-requests.index', [
            'title' => '人事請假',
            'section' => '業務與人事',
            'active' => 'leave-requests',
            'month' => $month,
            'status' => $status,
            'requests' => $requests,
            'totals' => $this->totals($requests),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('leave_requests.manage');

        $this->render('leave-requests.create', [
            'title' => '新增請假申請',
            'section' => '業務與人事',
            'active' => 'leave-requests',
            'request' => [
                'employee_id' => '',
                'leave_type_id' => '',
                'start_date' => date('Y-m-d'),
                'end_date' => date('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '18:00',
                'total_hours' => '8',
                'reason' => '',
                'handover_person' => '',
                'status' => 'submitted',
                'review_notes' => '',
                'notes' => '',
            ],
            'employees' => $this->employees(),
            'leaveTypes' => $this->leaveTypes(),
            'action' => '/leave-requests',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('leave_requests.manage');
        $this->validateRequest('/leave-requests/create');
        $payload = $this->payload();

        Database::pdo()->prepare(
            'INSERT INTO leave_requests
             (employee_id, leave_type_id, start_date, end_date, start_time, end_time, total_hours, reason, handover_person, status, notes, created_by, created_at, updated_at)
             VALUES
             (:employee_id, :leave_type_id, :start_date, :end_date, :start_time, :end_time, :total_hours, :reason, :handover_person, :status, :notes, :created_by, :created_at, :updated_at)'
        )->execute($payload + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        if ($payload['status'] === 'submitted') {
            ApprovalFlow::submit('leave_requests', 'leave_requests', $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('create', 'leave_requests', 'leave_requests', $id);
        flash('success', '請假申請已建立。');
        redirect('/leave-requests/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('leave_requests.view');

        $this->render('leave-requests.show', [
            'title' => '請假申請單',
            'section' => '業務與人事',
            'active' => 'leave-requests',
            'request' => $this->findRequest((int) $id),
            'approvalHistory' => ApprovalFlow::history('leave_requests', 'leave_requests', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('leave_requests.manage');

        $this->render('leave-requests.edit', [
            'title' => '編輯請假申請',
            'section' => '業務與人事',
            'active' => 'leave-requests',
            'request' => $this->findRequest((int) $id),
            'employees' => $this->employees(),
            'leaveTypes' => $this->leaveTypes(),
            'action' => '/leave-requests/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('leave_requests.manage');
        $existing = $this->findRequest((int) $id);
        $this->validateRequest('/leave-requests/' . $id . '/edit');
        $payload = $this->payload();

        Database::pdo()->prepare(
            'UPDATE leave_requests
             SET employee_id = :employee_id,
                 leave_type_id = :leave_type_id,
                 start_date = :start_date,
                 end_date = :end_date,
                 start_time = :start_time,
                 end_time = :end_time,
                 total_hours = :total_hours,
                 reason = :reason,
                 handover_person = :handover_person,
                 status = :status,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($payload + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        if ($payload['status'] === 'submitted' && $existing['status'] !== 'submitted') {
            ApprovalFlow::submit('leave_requests', 'leave_requests', (int) $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('update', 'leave_requests', 'leave_requests', (int) $id);
        flash('success', '請假申請已更新。');
        redirect('/leave-requests/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('leave_requests.manage');
        $request = $this->findRequest((int) $id);

        if (in_array($request['status'], ['approved', 'cancelled'], true)) {
            flash('error', '已核准或已取消的請假單不可重新送審。');
            redirect('/leave-requests/' . $id);
        }

        ApprovalFlow::submit('leave_requests', 'leave_requests', (int) $request['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE leave_requests
             SET status = "submitted",
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $request['id'],
        ]);

        AuditLog::write('submit', 'leave_requests', 'leave_requests', (int) $request['id']);
        flash('success', '請假單已送審。');
        redirect('/leave-requests/' . $id);
    }

    public function approve(string $id): void
    {
        $this->review((int) $id, 'approved', '請假申請已核准。');
    }

    public function reject(string $id): void
    {
        $this->review((int) $id, 'rejected', '請假申請已退回。');
    }

    public function cancel(string $id): void
    {
        $this->requirePermission('leave_requests.manage');
        $this->findRequest((int) $id);

        Database::pdo()->prepare(
            'UPDATE leave_requests
             SET status = "cancelled", review_notes = :review_notes, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'review_notes' => trim((string) ($_POST['review_notes'] ?? '')),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('cancel', 'leave_requests', 'leave_requests', (int) $id);
        flash('success', '請假申請已取消。');
        redirect('/leave-requests/' . $id);
    }

    private function review(int $id, string $status, string $message): void
    {
        $this->requirePermission('leave_requests.approve');
        $request = $this->findRequest($id);

        if ($request['status'] === 'cancelled') {
            flash('error', '已取消的請假單不可簽核。');
            redirect('/leave-requests/' . $id);
        }

        $notes = trim((string) ($_POST['review_notes'] ?? ''));
        ApprovalFlow::review('leave_requests', 'leave_requests', $id, $status, $notes);
        Database::pdo()->prepare(
            'UPDATE leave_requests
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

        AuditLog::write($status, 'leave_requests', 'leave_requests', $id);
        flash('success', $message);
        redirect('/leave-requests/' . $id);
    }

    private function validateRequest(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'employee_id' => '請假人',
            'leave_type_id' => '假別',
            'start_date' => '起始日期',
            'end_date' => '結束日期',
            'reason' => '請假事由',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['start_date'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['end_date'])) {
            $this->backWithInput($path, $_POST, '請假日期格式不正確。');
        }

        if ((string) $_POST['end_date'] < (string) $_POST['start_date']) {
            $this->backWithInput($path, $_POST, '結束日期不可早於起始日期。');
        }

        if (!$this->employeeExists((int) $_POST['employee_id'])) {
            $this->backWithInput($path, $_POST, '請選擇有效的人事資料。');
        }

        if (!$this->leaveTypeExists((int) $_POST['leave_type_id'])) {
            $this->backWithInput($path, $_POST, '請選擇有效假別。');
        }

        if ($this->hoursValue() <= 0 && $this->estimatedHours() <= 0) {
            $this->backWithInput($path, $_POST, '請假時數需大於 0。');
        }

        if (!in_array($_POST['status'] ?? '', ['draft', 'submitted', 'approved', 'rejected', 'cancelled'], true)) {
            $this->backWithInput($path, $_POST, '狀態不正確。');
        }
    }

    private function payload(): array
    {
        $status = $this->statusValue();

        return [
            'employee_id' => (int) $_POST['employee_id'],
            'leave_type_id' => (int) $_POST['leave_type_id'],
            'start_date' => (string) $_POST['start_date'],
            'end_date' => (string) $_POST['end_date'],
            'start_time' => $this->nullableTime('start_time'),
            'end_time' => $this->nullableTime('end_time'),
            'total_hours' => $this->hoursValue() > 0 ? $this->hoursValue() : $this->estimatedHours(),
            'reason' => trim((string) $_POST['reason']),
            'handover_person' => trim((string) ($_POST['handover_person'] ?? '')),
            'status' => $status,
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function statusValue(): string
    {
        $status = (string) ($_POST['status'] ?? 'submitted');
        if (in_array($status, ['approved', 'rejected'], true)) {
            return Permission::can('leave_requests.approve') ? $status : 'submitted';
        }

        return in_array($status, ['draft', 'submitted', 'cancelled'], true) ? $status : 'submitted';
    }

    private function findRequest(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT leave_requests.*, personnel_employees.name AS employee_name,
                    personnel_employees.employee_no, personnel_employees.department, personnel_employees.job_title,
                    leave_types.name AS leave_type_name, leave_types.paid,
                    reviewers.name AS reviewed_by_name, creators.name AS created_by_name
             FROM leave_requests
             INNER JOIN personnel_employees ON personnel_employees.id = leave_requests.employee_id
             INNER JOIN leave_types ON leave_types.id = leave_requests.leave_type_id
             LEFT JOIN users AS reviewers ON reviewers.id = leave_requests.reviewed_by
             LEFT JOIN users AS creators ON creators.id = leave_requests.created_by
             WHERE leave_requests.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到請假申請']);
            exit;
        }

        return $request;
    }

    private function employees(): array
    {
        return Database::pdo()->query(
            'SELECT id, employee_no, name, department, job_title
             FROM personnel_employees
             WHERE status IN ("active", "on_leave")
             ORDER BY department, name'
        )->fetchAll();
    }

    private function leaveTypes(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM leave_types WHERE status = "active" ORDER BY sort_order, name'
        )->fetchAll();
    }

    private function employeeExists(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM personnel_employees WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function leaveTypeExists(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM leave_types WHERE id = :id AND status = "active"');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function totals(array $requests): array
    {
        $hours = 0.0;
        $approved = 0.0;
        foreach ($requests as $request) {
            if ($request['status'] === 'cancelled' || $request['status'] === 'rejected') {
                continue;
            }
            $hours += (float) $request['total_hours'];
            if ($request['status'] === 'approved') {
                $approved += (float) $request['total_hours'];
            }
        }

        return [
            'count' => count($requests),
            'hours' => $hours,
            'approved_hours' => $approved,
        ];
    }

    private function estimatedHours(): float
    {
        try {
            $start = new DateTimeImmutable((string) $_POST['start_date']);
            $end = new DateTimeImmutable((string) $_POST['end_date']);
            $days = max(1, $start->diff($end)->days + 1);

            $startTime = $this->nullableTime('start_time');
            $endTime = $this->nullableTime('end_time');
            if ($days === 1 && $startTime && $endTime) {
                $startStamp = strtotime((string) $_POST['start_date'] . ' ' . $startTime);
                $endStamp = strtotime((string) $_POST['end_date'] . ' ' . $endTime);
                if ($startStamp !== false && $endStamp !== false && $endStamp > $startStamp) {
                    return round(($endStamp - $startStamp) / 3600, 2);
                }
            }

            return round($days * 8, 2);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function nullableTime(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{2}:\d{2}$/', $value) ? $value . ':00' : null;
    }

    private function hoursValue(): float
    {
        return round((float) ($_POST['total_hours'] ?? 0), 2);
    }
}
