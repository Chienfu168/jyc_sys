<?php

namespace App\Modules\Projects\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class ProjectController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('projects.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['planning', 'active', 'closed', 'cancelled'], true) ? (string) $_GET['status'] : '';
        $year = preg_match('/^\d{4}$/', (string) ($_GET['year'] ?? '')) ? (string) $_GET['year'] : '';

        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(name LIKE :keyword OR project_code LIKE :keyword OR owner_name LIKE :keyword OR department LIKE :keyword OR funding_source LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($year !== '') {
            $where[] = '(
                (start_date IS NULL AND end_date IS NULL)
                OR (start_date <= :year_end AND (end_date IS NULL OR end_date >= :year_start))
            )';
            $params['year_start'] = $year . '-01-01';
            $params['year_end'] = $year . '-12-31';
        }

        $sql = 'SELECT * FROM projects';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY FIELD(status, "active", "planning", "closed", "cancelled"), start_date DESC, id DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $projects = $stmt->fetchAll();

        $this->render('projects.index', [
            'title' => '專案管理',
            'section' => '業務與人事',
            'active' => 'projects',
            'projects' => $projects,
            'keyword' => $keyword,
            'status' => $status,
            'year' => $year,
            'summary' => $this->summary($projects),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('projects.manage');

        $this->render('projects.create', [
            'title' => '新增專案',
            'section' => '業務與人事',
            'active' => 'projects',
            'project' => $this->blankProject(),
            'action' => '/projects',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('projects.manage');
        $this->validateProject('/projects/create');

        Database::pdo()->prepare(
            'INSERT INTO projects
             (project_code, name, project_type, owner_name, department, funding_source, start_date, end_date, budget_amount, status, purpose, expected_outcome, notes, created_by, created_at, updated_at)
             VALUES
             (:project_code, :name, :project_type, :owner_name, :department, :funding_source, :start_date, :end_date, :budget_amount, :status, :purpose, :expected_outcome, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'projects', 'projects', $id);
        flash('success', '專案資料已建立。');
        redirect('/projects/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('projects.view');
        $project = $this->findProject((int) $id);

        $this->render('projects.show', [
            'title' => '專案資料',
            'section' => '業務與人事',
            'active' => 'projects',
            'project' => $project,
            'activities' => $this->projectActivities((int) $id),
            'costSummary' => $this->costSummary((int) $id, (float) $project['budget_amount']),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('projects.manage');

        $this->render('projects.edit', [
            'title' => '編輯專案',
            'section' => '業務與人事',
            'active' => 'projects',
            'project' => $this->findProject((int) $id),
            'action' => '/projects/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);
        $this->validateProject('/projects/' . $id . '/edit', (int) $project['id']);

        Database::pdo()->prepare(
            'UPDATE projects
             SET project_code = :project_code,
                 name = :name,
                 project_type = :project_type,
                 owner_name = :owner_name,
                 department = :department,
                 funding_source = :funding_source,
                 start_date = :start_date,
                 end_date = :end_date,
                 budget_amount = :budget_amount,
                 status = :status,
                 purpose = :purpose,
                 expected_outcome = :expected_outcome,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $project['id'],
        ]);

        AuditLog::write('update', 'projects', 'projects', (int) $id);
        flash('success', '專案資料已更新。');
        redirect('/projects/' . $id);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('projects.manage');
        $this->findProject((int) $id);

        $status = in_array(($_POST['status'] ?? ''), ['planning', 'active', 'closed', 'cancelled'], true)
            ? (string) $_POST['status']
            : 'planning';

        Database::pdo()->prepare('UPDATE projects SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('status', 'projects', 'projects', (int) $id);
        flash('success', '專案狀態已更新。');
        redirect('/projects/' . $id);
    }

    private function validateProject(string $path, ?int $ignoreId = null): void
    {
        if ($error = Validator::required($_POST, [
            'name' => '專案名稱',
            'project_type' => '專案類型',
            'status' => '狀態',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!in_array($_POST['project_type'] ?? '', ['program', 'grant', 'administration', 'event', 'other'], true)) {
            $this->backWithInput($path, $_POST, '專案類型不正確。');
        }
        if (!in_array($_POST['status'] ?? '', ['planning', 'active', 'closed', 'cancelled'], true)) {
            $this->backWithInput($path, $_POST, '狀態不正確。');
        }

        foreach (['start_date', 'end_date'] as $key) {
            $date = trim((string) ($_POST[$key] ?? ''));
            if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $this->backWithInput($path, $_POST, '日期格式不正確。');
            }
        }

        if ((string) ($_POST['start_date'] ?? '') !== ''
            && (string) ($_POST['end_date'] ?? '') !== ''
            && (string) $_POST['end_date'] < (string) $_POST['start_date']) {
            $this->backWithInput($path, $_POST, '結束日期不可早於開始日期。');
        }

        if ($this->amountValue('budget_amount') < 0) {
            $this->backWithInput($path, $_POST, '預算金額不可小於 0。');
        }

        $projectCode = trim((string) ($_POST['project_code'] ?? ''));
        if ($projectCode !== '') {
            $sql = 'SELECT id FROM projects WHERE project_code = :project_code';
            $params = ['project_code' => $projectCode];
            if ($ignoreId !== null) {
                $sql .= ' AND id != :ignore_id';
                $params['ignore_id'] = $ignoreId;
            }
            $sql .= ' LIMIT 1';
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetchColumn()) {
                $this->backWithInput($path, $_POST, '專案代碼已存在。');
            }
        }
    }

    private function payload(): array
    {
        return [
            'project_code' => $this->nullableText('project_code'),
            'name' => trim((string) $_POST['name']),
            'project_type' => (string) $_POST['project_type'],
            'owner_name' => $this->nullableText('owner_name'),
            'department' => $this->nullableText('department'),
            'funding_source' => $this->nullableText('funding_source'),
            'start_date' => $this->nullableText('start_date'),
            'end_date' => $this->nullableText('end_date'),
            'budget_amount' => $this->amountValue('budget_amount'),
            'status' => (string) $_POST['status'],
            'purpose' => trim((string) ($_POST['purpose'] ?? '')),
            'expected_outcome' => trim((string) ($_POST['expected_outcome'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findProject(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT projects.*, users.name AS created_by_name
             FROM projects
             LEFT JOIN users ON users.id = projects.created_by
             WHERE projects.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到專案資料']);
            exit;
        }

        return $project;
    }

    private function summary(array $projects): array
    {
        return [
            'total' => count($projects),
            'active' => count(array_filter($projects, static fn (array $project): bool => $project['status'] === 'active')),
            'budget_total' => array_sum(array_map(static fn (array $project): float => (float) $project['budget_amount'], $projects)),
        ];
    }

    private function projectActivities(int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT activities.*,
                    COALESCE(volunteer_stats.volunteer_hours, 0) AS volunteer_hours
             FROM activities
             LEFT JOIN (
                SELECT activity_id, SUM(hours) AS volunteer_hours
                FROM volunteer_service_logs
                GROUP BY activity_id
             ) AS volunteer_stats ON volunteer_stats.activity_id = activities.id
             WHERE activities.project_id = :project_id
             ORDER BY activities.starts_at DESC, activities.id DESC'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    private function costSummary(int $projectId, float $budgetAmount): array
    {
        $incomeExpense = $this->sourceCost(
            'income_expense_records',
            'amount',
            'project_id = :project_id AND item_type = "expense" AND status != "voided"',
            $projectId
        );
        $pettyCash = $this->sourceCost(
            'petty_cash_entries',
            'amount',
            'project_id = :project_id AND item_type = "expense"',
            $projectId
        );
        $lecturerExpense = $this->sourceCost(
            'lecturer_expenses',
            'gross_total',
            'project_id = :project_id AND payment_status != "voided"',
            $projectId
        );
        $travelExpense = $this->sourceCost(
            'travel_expenses',
            'reimbursable_amount',
            'project_id = :project_id AND payment_status != "voided"',
            $projectId
        );
        $payroll = $this->sourceCost(
            'payroll_records',
            'gross_pay + employer_pension',
            'project_id = :project_id AND payment_status != "voided"',
            $projectId
        );

        $actual = (float) $incomeExpense['amount']
            + (float) $pettyCash['amount']
            + (float) $lecturerExpense['amount']
            + (float) $travelExpense['amount']
            + (float) $payroll['amount'];

        return [
            'budget' => $budgetAmount,
            'actual' => $actual,
            'remaining' => $budgetAmount - $actual,
            'execution_rate' => $budgetAmount > 0 ? round(($actual / $budgetAmount) * 100, 2) : 0,
            'sources' => [
                ['label' => '收支紀錄', 'count' => (int) $incomeExpense['count'], 'amount' => (float) $incomeExpense['amount']],
                ['label' => '零用金', 'count' => (int) $pettyCash['count'], 'amount' => (float) $pettyCash['amount']],
                ['label' => '講師費', 'count' => (int) $lecturerExpense['count'], 'amount' => (float) $lecturerExpense['amount']],
                ['label' => '差旅費', 'count' => (int) $travelExpense['count'], 'amount' => (float) $travelExpense['amount']],
                ['label' => '薪資', 'count' => (int) $payroll['count'], 'amount' => (float) $payroll['amount']],
            ],
        ];
    }

    private function sourceCost(string $table, string $amountExpression, string $where, int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) AS record_count, COALESCE(SUM({$amountExpression}), 0) AS amount
             FROM {$table}
             WHERE {$where}"
        );
        $stmt->execute(['project_id' => $projectId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'count' => (int) ($row['record_count'] ?? 0),
            'amount' => (float) ($row['amount'] ?? 0),
        ];
    }

    private function blankProject(): array
    {
        return [
            'project_code' => '',
            'name' => '',
            'project_type' => 'program',
            'owner_name' => auth()->user()['name'] ?? '',
            'department' => '',
            'funding_source' => '',
            'start_date' => date('Y-m-d'),
            'end_date' => '',
            'budget_amount' => '',
            'status' => 'planning',
            'purpose' => '',
            'expected_outcome' => '',
            'notes' => '',
        ];
    }

    private function nullableText(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    private function amountValue(string $key): float
    {
        return round((float) ($_POST[$key] ?? 0), 2);
    }
}
