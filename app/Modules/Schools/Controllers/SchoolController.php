<?php

namespace App\Modules\Schools\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

/**
 * 學校通訊錄:以「學校」為中心的記錄。校長、主任等人員任期內會更替,故人員
 * 資料獨立管理於 school_staff,保留現任與歷任(前任)紀錄;活動管理主要
 * 依附學校辦理,故本頁亦彙整該校相關活動。
 * 屬全機構共用資料——具檢視權限者皆可查閱;具管理權限者可新增／編輯／刪除。
 */
final class SchoolController extends Controller
{
    /** 常用學校類別(供下拉建議,亦可自行輸入)。 */
    private const CATEGORIES = ['國小', '國中', '高中', '大學', '幼兒園', '其他'];

    /** 常見職稱(供下拉建議,亦可自行輸入)。 */
    private const TITLES = ['校長', '教務主任', '學務主任', '總務主任', '輔導主任', '主任', '幹事'];

    public function index(): void
    {
        $this->requirePermission('schools.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['active', 'inactive'], true) ? (string) $_GET['status'] : 'active';

        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(schools.name LIKE :kw OR schools.address LIKE :kw2 OR schools.phone LIKE :kw3)';
            $like = '%' . $keyword . '%';
            $params += ['kw' => $like, 'kw2' => $like, 'kw3' => $like];
        }
        if ($category !== '') {
            $where[] = 'schools.category = :category';
            $params['category'] = $category;
        }
        if ($status !== '') {
            $where[] = 'schools.status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT schools.*,
                       (SELECT name FROM school_staff
                        WHERE school_id = schools.id AND status = "current" AND title = "校長"
                        ORDER BY id LIMIT 1) AS principal_name,
                       COALESCE(staff_stats.current_count, 0) AS current_staff_count
                FROM schools
                LEFT JOIN (
                    SELECT school_id, COUNT(id) AS current_count
                    FROM school_staff
                    WHERE status = "current"
                    GROUP BY school_id
                ) AS staff_stats ON staff_stats.school_id = schools.id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY schools.category, schools.name, schools.id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $schools = $stmt->fetchAll();

        $this->render('schools.index', [
            'title' => '學校通訊錄',
            'section' => '業務推動',
            'active' => 'schools',
            'schools' => $schools,
            'keyword' => $keyword,
            'category' => $category,
            'status' => $status,
            'categories' => $this->categoryOptions(),
            'canManage' => Permission::can('schools.manage'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('schools.manage');

        $this->render('schools.form', [
            'title' => '新增學校',
            'section' => '業務推動',
            'active' => 'schools',
            'school' => $this->blankSchool(),
            'categories' => $this->categoryOptions(),
            'action' => '/schools',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('schools.manage');
        $this->validateSchool('/schools/create');

        Database::pdo()->prepare(
            'INSERT INTO schools
             (name, category, address, phone, fax, notes, status, created_by, created_at, updated_at)
             VALUES
             (:name, :category, :address, :phone, :fax, :notes, :status, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => $this->currentUserId() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'schools', 'schools', $id);
        flash('success', '學校資料已建立。');
        redirect('/schools/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('schools.view');
        $school = $this->findSchool((int) $id);

        $this->render('schools.show', [
            'title' => '學校資料',
            'section' => '業務推動',
            'active' => 'schools',
            'school' => $school,
            'currentStaff' => $this->staff((int) $id, 'current'),
            'formerStaff' => $this->staff((int) $id, 'former'),
            'activities' => $this->relatedActivities((int) $id),
            'titles' => $this->titleOptions(),
            'canManage' => Permission::can('schools.manage'),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('schools.manage');

        $this->render('schools.form', [
            'title' => '編輯學校',
            'section' => '業務推動',
            'active' => 'schools',
            'school' => $this->findSchool((int) $id),
            'categories' => $this->categoryOptions(),
            'action' => '/schools/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('schools.manage');
        $this->findSchool((int) $id);
        $this->validateSchool('/schools/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE schools SET
                name = :name, category = :category, address = :address, phone = :phone, fax = :fax,
                notes = :notes, status = :status, updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'schools', 'schools', (int) $id);
        flash('success', '學校資料已更新。');
        redirect('/schools/' . $id);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('schools.manage');
        $school = $this->findSchool((int) $id);
        $status = $school['status'] === 'active' ? 'inactive' : 'active';

        Database::pdo()->prepare('UPDATE schools SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute(['status' => $status, 'updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('toggle', 'schools', 'schools', (int) $id);
        flash('success', $status === 'active' ? '已恢復啟用。' : '已封存(停用)。');
        redirect('/schools/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('schools.manage');
        $school = $this->findSchool((int) $id);

        Database::pdo()->prepare('DELETE FROM schools WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'schools', 'schools', (int) $id, ['name' => $school['name']]);
        flash('success', '學校資料已刪除。');
        redirect('/schools');
    }

    // ---- 學校人員(校長、主任等) ----

    public function storeStaff(string $id): void
    {
        $this->requirePermission('schools.manage');
        $school = $this->findSchool((int) $id);

        if ($error = Validator::required($_POST, ['title' => '職稱', 'name' => '姓名'])) {
            $this->backWithInput('/schools/' . $id, $_POST, $error);
        }

        $startedOn = $this->nullableDate('started_on');
        if (trim((string) ($_POST['started_on'] ?? '')) !== '' && $startedOn === null) {
            $this->backWithInput('/schools/' . $id, $_POST, '到任日期格式不正確。');
        }

        $status = ($_POST['status'] ?? 'current') === 'former' ? 'former' : 'current';
        $endedOn = $this->nullableDate('ended_on');
        if (trim((string) ($_POST['ended_on'] ?? '')) !== '' && $endedOn === null) {
            $this->backWithInput('/schools/' . $id, $_POST, '卸任日期格式不正確。');
        }
        if ($status === 'former' && $endedOn === null) {
            $endedOn = date('Y-m-d');
        }
        if ($status === 'current') {
            $endedOn = null;
        }

        Database::pdo()->prepare(
            'INSERT INTO school_staff
             (school_id, title, name, phone, mobile, email, status, started_on, ended_on, notes,
              created_by, created_at, updated_at)
             VALUES
             (:school_id, :title, :name, :phone, :mobile, :email, :status, :started_on, :ended_on, :notes,
              :created_by, :created_at, :updated_at)'
        )->execute([
            'school_id' => (int) $school['id'],
            'title' => trim((string) $_POST['title']),
            'name' => trim((string) $_POST['name']),
            'phone' => $this->nullablePost('phone'),
            'mobile' => $this->nullablePost('mobile'),
            'email' => $this->nullablePost('email'),
            'status' => $status,
            'started_on' => $startedOn,
            'ended_on' => $endedOn,
            'notes' => $this->nullablePost('notes'),
            'created_by' => $this->currentUserId() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLog::write('create', 'schools', 'school_staff', (int) Database::pdo()->lastInsertId());
        flash('success', '人員資料已新增。');
        redirect('/schools/' . $id);
    }

    public function editStaff(string $id, string $staffId): void
    {
        $this->requirePermission('schools.manage');
        $school = $this->findSchool((int) $id);
        $staff = $this->findStaff((int) $id, (int) $staffId);

        $this->render('schools.staff-edit', [
            'title' => '編輯人員資料',
            'section' => '業務推動',
            'active' => 'schools',
            'school' => $school,
            'staff' => $staff,
            'titles' => $this->titleOptions(),
            'action' => '/schools/' . $id . '/staff/' . $staffId,
        ]);
    }

    public function updateStaff(string $id, string $staffId): void
    {
        $this->requirePermission('schools.manage');
        $this->findSchool((int) $id);
        $this->findStaff((int) $id, (int) $staffId);

        if ($error = Validator::required($_POST, ['title' => '職稱', 'name' => '姓名'])) {
            $this->backWithInput('/schools/' . $id . '/staff/' . $staffId . '/edit', $_POST, $error);
        }

        $startedOn = $this->nullableDate('started_on');
        if (trim((string) ($_POST['started_on'] ?? '')) !== '' && $startedOn === null) {
            $this->backWithInput('/schools/' . $id . '/staff/' . $staffId . '/edit', $_POST, '到任日期格式不正確。');
        }

        $status = ($_POST['status'] ?? 'current') === 'former' ? 'former' : 'current';
        $endedOn = $this->nullableDate('ended_on');
        if (trim((string) ($_POST['ended_on'] ?? '')) !== '' && $endedOn === null) {
            $this->backWithInput('/schools/' . $id . '/staff/' . $staffId . '/edit', $_POST, '卸任日期格式不正確。');
        }
        if ($status === 'former' && $endedOn === null) {
            $endedOn = date('Y-m-d');
        }
        if ($status === 'current') {
            $endedOn = null;
        }

        Database::pdo()->prepare(
            'UPDATE school_staff SET
                title = :title, name = :name, phone = :phone, mobile = :mobile, email = :email,
                status = :status, started_on = :started_on, ended_on = :ended_on, notes = :notes,
                updated_at = :updated_at
             WHERE id = :id AND school_id = :school_id'
        )->execute([
            'title' => trim((string) $_POST['title']),
            'name' => trim((string) $_POST['name']),
            'phone' => $this->nullablePost('phone'),
            'mobile' => $this->nullablePost('mobile'),
            'email' => $this->nullablePost('email'),
            'status' => $status,
            'started_on' => $startedOn,
            'ended_on' => $endedOn,
            'notes' => $this->nullablePost('notes'),
            'updated_at' => now(),
            'id' => (int) $staffId,
            'school_id' => (int) $id,
        ]);

        AuditLog::write('update', 'schools', 'school_staff', (int) $staffId);
        flash('success', '人員資料已更新。');
        redirect('/schools/' . $id);
    }

    /** 標示為卸任(前任)／恢復為現任。 */
    public function toggleStaff(string $id, string $staffId): void
    {
        $this->requirePermission('schools.manage');
        $this->findSchool((int) $id);
        $staff = $this->findStaff((int) $id, (int) $staffId);
        $status = $staff['status'] === 'current' ? 'former' : 'current';

        Database::pdo()->prepare(
            'UPDATE school_staff
             SET status = :status,
                 ended_on = CASE WHEN :status_for_end = "former" THEN COALESCE(ended_on, :today) ELSE NULL END,
                 updated_at = :updated_at
             WHERE id = :id AND school_id = :school_id'
        )->execute([
            'status' => $status,
            'status_for_end' => $status,
            'today' => date('Y-m-d'),
            'updated_at' => now(),
            'id' => (int) $staffId,
            'school_id' => (int) $id,
        ]);

        AuditLog::write('toggle', 'schools', 'school_staff', (int) $staffId);
        flash('success', $status === 'former' ? '已標示為卸任(前任)。' : '已恢復為現任。');
        redirect('/schools/' . $id);
    }

    public function destroyStaff(string $id, string $staffId): void
    {
        $this->requirePermission('schools.manage');
        $this->findSchool((int) $id);
        $staff = $this->findStaff((int) $id, (int) $staffId);

        Database::pdo()->prepare('DELETE FROM school_staff WHERE id = :id AND school_id = :school_id')
            ->execute(['id' => (int) $staffId, 'school_id' => (int) $id]);

        AuditLog::write('delete', 'schools', 'school_staff', (int) $staffId, ['name' => $staff['name']]);
        flash('success', '人員資料已刪除。');
        redirect('/schools/' . $id);
    }

    // ---- 內部輔助 ----

    private function validateSchool(string $path): void
    {
        if ($error = Validator::required($_POST, ['name' => '學校名稱'])) {
            $this->backWithInput($path, $_POST, $error);
        }
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'name' => trim((string) $_POST['name']),
            'category' => $this->nullablePost('category'),
            'address' => $this->nullablePost('address'),
            'phone' => $this->nullablePost('phone'),
            'fax' => $this->nullablePost('fax'),
            'notes' => $this->nullablePost('notes'),
            'status' => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];
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
    private function blankSchool(): array
    {
        return [
            'name' => '', 'category' => '', 'address' => '', 'phone' => '', 'fax' => '',
            'notes' => '', 'status' => 'active',
        ];
    }

    /** @return array<string, mixed> */
    private function findSchool(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT schools.*, users.name AS created_by_name
             FROM schools
             LEFT JOIN users ON users.id = schools.created_by
             WHERE schools.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $school = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$school) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到學校資料']);
            exit;
        }

        return $school;
    }

    /** @return array<string, mixed> */
    private function findStaff(int $schoolId, int $staffId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM school_staff WHERE id = :id AND school_id = :school_id LIMIT 1'
        );
        $stmt->execute(['id' => $staffId, 'school_id' => $schoolId]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staff) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到人員資料']);
            exit;
        }

        return $staff;
    }

    /** @return array<int, array<string, mixed>> */
    private function staff(int $schoolId, string $status): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM school_staff
             WHERE school_id = :school_id AND status = :status
             ORDER BY FIELD(title, "校長") DESC, started_on DESC, id DESC'
        );
        $stmt->execute(['school_id' => $schoolId, 'status' => $status]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    private function relatedActivities(int $schoolId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, title, starts_at, status
             FROM activities
             WHERE school_id = :school_id
             ORDER BY starts_at DESC, id DESC
             LIMIT 50'
        );
        $stmt->execute(['school_id' => $schoolId]);
        return $stmt->fetchAll();
    }

    /** 合併常用類別與資料庫既有類別,供下拉建議。 @return array<int, string> */
    private function categoryOptions(): array
    {
        $existing = Database::pdo()
            ->query('SELECT DISTINCT category FROM schools WHERE category IS NOT NULL AND category <> "" ORDER BY category')
            ->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_unique(array_merge(self::CATEGORIES, array_map('strval', $existing))));
    }

    /** 合併常用職稱與資料庫既有職稱,供下拉建議。 @return array<int, string> */
    private function titleOptions(): array
    {
        $existing = Database::pdo()
            ->query('SELECT DISTINCT title FROM school_staff WHERE title IS NOT NULL AND title <> "" ORDER BY title')
            ->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_unique(array_merge(self::TITLES, array_map('strval', $existing))));
    }
}
