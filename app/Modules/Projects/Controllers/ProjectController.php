<?php

namespace App\Modules\Projects\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\Projects\CostSummary;
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

    /**
     * 合作學校課程報表:跨所有專案(學校)彙整課程,可依全部／指定學期／指定學年度篩選並列印。
     */
    public function courseReport(): void
    {
        $this->requirePermission('projects.view');

        $scope = in_array($_GET['scope'] ?? '', ['all', 'semester', 'year'], true) ? (string) $_GET['scope'] : 'all';
        $semester = trim((string) ($_GET['semester'] ?? ''));
        $year = preg_match('/^\d{1,3}$/', (string) ($_GET['year'] ?? '')) ? (string) $_GET['year'] : '';

        $where = [];
        $params = [];
        if ($scope === 'semester' && $semester !== '') {
            $where[] = 'project_courses.semester_label = :semester';
            $params['semester'] = $semester;
        } elseif ($scope === 'year' && $year !== '') {
            $where[] = 'project_courses.semester_label LIKE :year_prefix';
            $params['year_prefix'] = $year . '學年度%';
        }

        $sql = 'SELECT project_courses.*, projects.name AS project_name, projects.project_code
                FROM project_courses
                INNER JOIN projects ON projects.id = project_courses.project_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY projects.name ASC, project_courses.sort_order ASC, project_courses.id ASC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $courses = $stmt->fetchAll();

        $this->render('projects.course-report', [
            'title' => '合作學校課程報表',
            'section' => '業務與人事',
            'active' => 'projects',
            'courses' => $courses,
            'scope' => $scope,
            'semester' => $semester,
            'year' => $year,
            'semesterOptions' => $this->courseSemesterOptions(),
            'yearOptions' => $this->courseYearOptions(),
            'profile' => foundation_profile(),
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
            'courses' => $this->projectCourses((int) $id),
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

    /**
     * 合作學校課程明細(如深耕教育計畫各校課程):學校、校長、負責主任、課程名稱、
     * 授課老師、每週幾、學期與備註。部分欄位可能待確認而留白,不代表資料不存在。
     */
    public function courseCreate(string $id): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);

        $this->render('projects.course-form', [
            'title' => '新增合作學校課程',
            'section' => '業務與人事',
            'active' => 'projects',
            'project' => $project,
            'course' => $this->blankCourse($project),
            'action' => '/projects/' . $id . '/courses',
        ]);
    }

    public function courseStore(string $id): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);
        $this->validateCourse('/projects/' . $id . '/courses/create');

        Database::pdo()->prepare(
            'INSERT INTO project_courses
             (project_id, school_name, principal_name, director_name, course_name, teacher_name, weekday, semester_label, notes, sort_order, created_at, updated_at)
             VALUES
             (:project_id, :school_name, :principal_name, :director_name, :course_name, :teacher_name, :weekday, :semester_label, :notes, :sort_order, :created_at, :updated_at)'
        )->execute($this->coursePayload((int) $project['id']) + [
            'sort_order' => $this->nextCourseSortOrder((int) $project['id']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $courseId = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'project_courses', 'project_courses', $courseId);
        flash('success', '課程已新增。');
        redirect('/projects/' . $id);
    }

    public function courseEdit(string $id, string $courseId): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);

        $this->render('projects.course-form', [
            'title' => '編輯合作學校課程',
            'section' => '業務與人事',
            'active' => 'projects',
            'project' => $project,
            'course' => $this->findCourse((int) $project['id'], (int) $courseId),
            'action' => '/projects/' . $id . '/courses/' . $courseId,
        ]);
    }

    public function courseUpdate(string $id, string $courseId): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);
        $this->findCourse((int) $project['id'], (int) $courseId);
        $this->validateCourse('/projects/' . $id . '/courses/' . $courseId . '/edit');

        Database::pdo()->prepare(
            'UPDATE project_courses
             SET school_name = :school_name,
                 principal_name = :principal_name,
                 director_name = :director_name,
                 course_name = :course_name,
                 teacher_name = :teacher_name,
                 weekday = :weekday,
                 semester_label = :semester_label,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id AND project_id = :project_id'
        )->execute($this->coursePayload((int) $project['id']) + [
            'updated_at' => now(),
            'id' => (int) $courseId,
        ]);

        AuditLog::write('update', 'project_courses', 'project_courses', (int) $courseId);
        flash('success', '課程資料已更新。');
        redirect('/projects/' . $id);
    }

    public function courseDestroy(string $id, string $courseId): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);
        $this->findCourse((int) $project['id'], (int) $courseId);

        Database::pdo()->prepare('DELETE FROM project_courses WHERE id = :id AND project_id = :project_id')
            ->execute(['id' => (int) $courseId, 'project_id' => (int) $project['id']]);

        AuditLog::write('delete', 'project_courses', 'project_courses', (int) $courseId);
        flash('success', '課程已刪除。');
        redirect('/projects/' . $id);
    }

    /**
     * 依專案批次產生課程週次活動(搭配學期的深耕課程,預設 16 週)。
     *
     * 以開始日期為第一次上課,每隔 N 週產生一次,遇到排除日期(假日／考試週)略過,
     * 直到累計出指定的實際上課次數為止;每筆同步建立行事曆事件並歸屬本專案,
     * 週次自既有最大週次接續編號,可分批產生。
     */
    public function generateSessions(string $id): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);
        $projectId = (int) $project['id'];
        $back = '/projects/' . $projectId;

        $startDate = trim((string) ($_POST['start_date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !$this->isValidDate($startDate)) {
            $this->backWithInput($back, $_POST, '開始日期格式不正確。');
        }

        $startTime = $this->timeValue((string) ($_POST['start_time'] ?? '14:00'));
        if ($startTime === null) {
            $this->backWithInput($back, $_POST, '上課開始時間格式不正確(請用 HH:MM)。');
        }
        $endTimeRaw = trim((string) ($_POST['end_time'] ?? ''));
        $endTime = $endTimeRaw === '' ? null : $this->timeValue($endTimeRaw);
        if ($endTimeRaw !== '' && $endTime === null) {
            $this->backWithInput($back, $_POST, '上課結束時間格式不正確(請用 HH:MM)。');
        }
        if ($endTime !== null && $endTime <= $startTime) {
            $this->backWithInput($back, $_POST, '結束時間必須晚於開始時間。');
        }

        $weeks = (int) ($_POST['weeks'] ?? 16);
        if ($weeks < 1 || $weeks > 40) {
            $this->backWithInput($back, $_POST, '週數請填 1 到 40。');
        }
        $intervalWeeks = (int) ($_POST['interval_weeks'] ?? 1);
        if ($intervalWeeks < 1 || $intervalWeeks > 8) {
            $this->backWithInput($back, $_POST, '間隔週數請填 1 到 8。');
        }

        $status = in_array(($_POST['status'] ?? ''), ['draft', 'published', 'closed', 'cancelled'], true)
            ? (string) $_POST['status']
            : 'published';
        $location = trim((string) ($_POST['location'] ?? ''));
        $titlePrefix = trim((string) ($_POST['title_prefix'] ?? '')) ?: (string) $project['name'];

        // 排除日期(假日／考試週):以換行、逗號或空白分隔,保留合法且存在的日期。
        $excludeSet = [];
        foreach (preg_split('/[\s,]+/', (string) ($_POST['exclude_dates'] ?? '')) ?: [] as $raw) {
            $raw = trim($raw);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) && $this->isValidDate($raw)) {
                $excludeSet[$raw] = true;
            }
        }

        $startNo = (int) $this->maxSessionNo($projectId) + 1;

        $cursor = new \DateTimeImmutable($startDate);
        $stepDays = $intervalWeeks * 7;
        $cap = $weeks + count($excludeSet) + 60; // 迴圈安全上限,避免排除過多造成無限延伸。
        $created = 0;
        $skipped = 0;
        $iterations = 0;

        while ($created < $weeks && $iterations <= $cap) {
            $iterations++;
            $dateStr = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+' . $stepDays . ' days');

            if (isset($excludeSet[$dateStr])) {
                $skipped++;
                continue;
            }

            $sessionNo = $startNo + $created;
            $activityId = $this->insertSessionActivity([
                'project_id' => $projectId,
                'session_no' => $sessionNo,
                'title' => $titlePrefix . ' 第' . $sessionNo . '週',
                'starts_at' => $dateStr . ' ' . $startTime . ':00',
                'ends_at' => $endTime !== null ? $dateStr . ' ' . $endTime . ':00' : null,
                'location' => $location,
                'status' => $status,
            ]);
            $this->syncSessionCalendarEvent($activityId, $titlePrefix . ' 第' . $sessionNo . '週', $dateStr . ' ' . $startTime . ':00', $endTime !== null ? $dateStr . ' ' . $endTime . ':00' : null, $location, $status);
            $created++;
        }

        AuditLog::write('generate-sessions', 'projects', 'projects', $projectId, [
            'created' => $created,
            'skipped' => $skipped,
        ]);

        $message = '已產生 ' . $created . ' 週課程活動並歸屬本專案。';
        if ($skipped > 0) {
            $message .= '(略過 ' . $skipped . ' 個排除日期)';
        }
        flash('success', $message);
        redirect($back);
    }

    private function maxSessionNo(int $projectId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(MAX(session_no), 0) FROM activities WHERE project_id = :project_id');
        $stmt->execute(['project_id' => $projectId]);
        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    private function insertSessionActivity(array $data): int
    {
        Database::pdo()->prepare(
            'INSERT INTO activities
             (project_id, session_no, title, starts_at, ends_at, location, status, created_by, created_at, updated_at)
             VALUES
             (:project_id, :session_no, :title, :starts_at, :ends_at, :location, :status, :created_by, :created_at, :updated_at)'
        )->execute($data + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    private function syncSessionCalendarEvent(int $activityId, string $title, string $startsAt, ?string $endsAt, string $location, string $status): void
    {
        $eventStatus = match ($status) {
            'closed' => 'done',
            'cancelled' => 'cancelled',
            default => 'scheduled',
        };

        Database::pdo()->prepare(
            'INSERT INTO calendar_events
             (title, event_type, starts_at, ends_at, all_day, location, owner_name, reminder_minutes, status, description, source_module, source_id, created_by, created_at, updated_at)
             VALUES
             (:title, "activity", :starts_at, :ends_at, 0, :location, :owner_name, 60, :status, NULL, "activities", :source_id, :created_by, :created_at, :updated_at)'
        )->execute([
            'title' => $title,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'location' => $location !== '' ? $location : null,
            'owner_name' => auth()->user()['name'] ?? null,
            'status' => $eventStatus,
            'source_id' => $activityId,
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isValidDate(string $date): bool
    {
        $parts = explode('-', $date);
        return count($parts) === 3 && checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
    }

    private function timeValue(string $value): ?string
    {
        $value = trim($value);
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $min = (int) $m[2];
        if ($h > 23 || $min > 59) {
            return null;
        }
        return sprintf('%02d:%02d', $h, $min);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('projects.manage');
        $project = $this->findProject((int) $id);

        // 各支出/收入模組的 project_id 皆設定 ON DELETE SET NULL,刪除專案不會影響既有紀錄,僅解除歸屬。
        Database::pdo()->prepare('DELETE FROM projects WHERE id = :id')
            ->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'projects', 'projects', (int) $id, [
            'name' => $project['name'],
        ]);
        flash('success', '專案已刪除。');
        redirect('/projects');
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
             ORDER BY activities.session_no IS NULL, activities.session_no ASC, activities.starts_at ASC, activities.id ASC'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    private function projectCourses(int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM project_courses
             WHERE project_id = :project_id
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    private function findCourse(int $projectId, int $courseId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM project_courses WHERE id = :id AND project_id = :project_id LIMIT 1'
        );
        $stmt->execute(['id' => $courseId, 'project_id' => $projectId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到課程資料']);
            exit;
        }

        return $course;
    }

    private function validateCourse(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'school_name' => '學校',
            'course_name' => '課程名稱',
            'semester_label' => '學年度＋學期',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }
    }

    private function coursePayload(int $projectId): array
    {
        return [
            'project_id' => $projectId,
            'school_name' => trim((string) $_POST['school_name']),
            'principal_name' => $this->nullableText('principal_name'),
            'director_name' => $this->nullableText('director_name'),
            'course_name' => trim((string) $_POST['course_name']),
            'teacher_name' => $this->nullableText('teacher_name'),
            'weekday' => $this->nullableText('weekday'),
            'semester_label' => trim((string) $_POST['semester_label']),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function nextCourseSortOrder(int $projectId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM project_courses WHERE project_id = :project_id');
        $stmt->execute(['project_id' => $projectId]);
        return (int) $stmt->fetchColumn() + 1;
    }

    private function blankCourse(array $project): array
    {
        // 專案名稱若為「深耕教育計畫－OO國小」格式(一校一專案),預先帶出破折號後的學校名稱。
        $school = '';
        if (preg_match('/[－-]\s*(.+)$/u', trim((string) ($project['name'] ?? '')), $m)) {
            $school = trim($m[1]);
        }

        return [
            'school_name' => $school,
            'principal_name' => '',
            'director_name' => '',
            'course_name' => '',
            'teacher_name' => '',
            'weekday' => '',
            'semester_label' => $this->currentSemesterLabel(),
            'notes' => '',
        ];
    }

    /**
     * 依今日日期推算目前所屬「學年度＋學期」(ROC 學制:8月起第1學期,隔年2月起第2學期)。
     * 僅作為新增課程時的預設值,使用者仍可自行修改。
     */
    private function currentSemesterLabel(): string
    {
        $month = (int) date('n');
        $year = (int) date('Y') - 1911;
        if ($month >= 8) {
            return $year . '學年度第1學期';
        }
        if ($month >= 2) {
            return ($year - 1) . '學年度第2學期';
        }
        // 1 月仍屬前一年 8 月起始的第 1 學期。
        return ($year - 1) . '學年度第1學期';
    }

    /** 依現有課程資料彙整可篩選的學年度＋學期清單,依時間先後排序。 */
    private function courseSemesterOptions(): array
    {
        $labels = Database::pdo()->query('SELECT DISTINCT semester_label FROM project_courses')
            ->fetchAll(PDO::FETCH_COLUMN);
        usort($labels, fn (string $a, string $b): int => $this->semesterSortKey($a) <=> $this->semesterSortKey($b));
        return $labels;
    }

    /** 依現有課程資料彙整可篩選的學年度清單(去除學期別,由小到大排序)。 */
    private function courseYearOptions(): array
    {
        $labels = Database::pdo()->query('SELECT DISTINCT semester_label FROM project_courses')
            ->fetchAll(PDO::FETCH_COLUMN);
        $years = [];
        foreach ($labels as $label) {
            if (preg_match('/^(\d{1,3})學年度/u', (string) $label, $m)) {
                $years[(int) $m[1]] = true;
            }
        }
        $years = array_keys($years);
        sort($years);
        return $years;
    }

    /** 將「113學年度第2學期」轉為 [113, 2] 供依時間排序,格式不符者排最前。 */
    private function semesterSortKey(string $label): array
    {
        if (preg_match('/^(\d{1,3})學年度第(\d)學期$/u', $label, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [0, 0];
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

        return CostSummary::build($budgetAmount, [
            ['label' => '收支紀錄', 'count' => $incomeExpense['count'], 'amount' => $incomeExpense['amount']],
            ['label' => '零用金', 'count' => $pettyCash['count'], 'amount' => $pettyCash['amount']],
            ['label' => '講師費', 'count' => $lecturerExpense['count'], 'amount' => $lecturerExpense['amount']],
            ['label' => '差旅費', 'count' => $travelExpense['count'], 'amount' => $travelExpense['amount']],
            ['label' => '薪資', 'count' => $payroll['count'], 'amount' => $payroll['amount']],
        ]);
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
