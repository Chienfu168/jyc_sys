<?php

namespace App\Modules\BoardDirectors\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

/**
 * 董事名冊:以「人」為中心,獨立於各次董事會議的出列席紀錄之外,維護
 * 董事／監察人的聯絡資料(含地址,供日後列印標籤等用途)與任期狀態,
 * 保留歷任名冊而非逐次重新輸入。沿用董事會議(board_meetings)權限,
 * 與董事會議同一群使用者維護。
 */
final class BoardDirectorController extends Controller
{
    /** 常見職稱(供下拉建議,亦可自行輸入)。 */
    private const TITLES = ['董事長', '常務董事', '董事', '監察人'];

    public function index(): void
    {
        $this->requirePermission('board_meetings.view');

        // 狀態篩選:未帶 status 參數時預設僅顯示現任;帶了但值非 current/former
        // (例如下拉選單「全部」送出的空字串)則視為不篩選,顯示全部。
        $status = 'current';
        if (isset($_GET['status'])) {
            $status = in_array($_GET['status'], ['current', 'former'], true) ? (string) $_GET['status'] : '';
        }
        $keyword = trim((string) ($_GET['q'] ?? ''));

        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($keyword !== '') {
            $where[] = '(name LIKE :kw OR organization LIKE :kw2 OR address LIKE :kw3)';
            $like = '%' . $keyword . '%';
            $params += ['kw' => $like, 'kw2' => $like, 'kw3' => $like];
        }

        $sql = 'SELECT * FROM board_directors';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY FIELD(title, "董事長") DESC, sort_order, id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $directors = $stmt->fetchAll();

        $this->render('board-directors.index', [
            'title' => '董事名冊',
            'section' => '主管機關核備',
            'active' => 'board-directors',
            'directors' => $directors,
            'status' => $status,
            'keyword' => $keyword,
            'titles' => $this->titleOptions(),
            'canManage' => Permission::can('board_meetings.manage'),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('board_meetings.manage');

        $this->render('board-directors.form', [
            'title' => '新增董事',
            'section' => '主管機關核備',
            'active' => 'board-directors',
            'director' => $this->blankDirector(),
            'titles' => $this->titleOptions(),
            'action' => '/board-directors',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('board_meetings.manage');
        $this->validateDirector('/board-directors/create');

        Database::pdo()->prepare(
            'INSERT INTO board_directors
             (term_no, name, title, organization, job_title, phone, mobile, email, address,
              status, started_on, ended_on, notes, created_by, created_at, updated_at)
             VALUES
             (:term_no, :name, :title, :organization, :job_title, :phone, :mobile, :email, :address,
              :status, :started_on, :ended_on, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => $this->currentUserId() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'board_directors', 'board_directors', $id);
        flash('success', '董事資料已建立。');
        redirect('/board-directors/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('board_meetings.view');

        $this->render('board-directors.show', [
            'title' => '董事資料',
            'section' => '主管機關核備',
            'active' => 'board-directors',
            'director' => $this->findDirector((int) $id),
            'canManage' => Permission::can('board_meetings.manage'),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('board_meetings.manage');

        $this->render('board-directors.form', [
            'title' => '編輯董事',
            'section' => '主管機關核備',
            'active' => 'board-directors',
            'director' => $this->findDirector((int) $id),
            'titles' => $this->titleOptions(),
            'action' => '/board-directors/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $this->findDirector((int) $id);
        $this->validateDirector('/board-directors/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE board_directors SET
                term_no = :term_no, name = :name, title = :title, organization = :organization,
                job_title = :job_title, phone = :phone, mobile = :mobile, email = :email, address = :address,
                status = :status, started_on = :started_on, ended_on = :ended_on, notes = :notes,
                updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'board_directors', 'board_directors', (int) $id);
        flash('success', '董事資料已更新。');
        redirect('/board-directors/' . $id);
    }

    /** 標示為卸任(前任)／恢復為現任。 */
    public function toggle(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $director = $this->findDirector((int) $id);
        $status = $director['status'] === 'current' ? 'former' : 'current';

        Database::pdo()->prepare(
            'UPDATE board_directors
             SET status = :status,
                 ended_on = CASE WHEN :status_for_end = "former" THEN COALESCE(ended_on, :today) ELSE NULL END,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'status' => $status,
            'status_for_end' => $status,
            'today' => date('Y-m-d'),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('toggle', 'board_directors', 'board_directors', (int) $id);
        flash('success', $status === 'former' ? '已標示為卸任(前任)。' : '已恢復為現任。');
        redirect('/board-directors/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('board_meetings.manage');
        $director = $this->findDirector((int) $id);

        Database::pdo()->prepare('DELETE FROM board_directors WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'board_directors', 'board_directors', (int) $id, ['name' => $director['name']]);
        flash('success', '董事資料已刪除。');
        redirect('/board-directors');
    }

    // ---- 內部輔助 ----

    private function validateDirector(string $path): void
    {
        if ($error = Validator::required($_POST, ['name' => '姓名'])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput($path, $_POST, 'Email 格式不正確。');
        }

        $termNo = trim((string) ($_POST['term_no'] ?? ''));
        if ($termNo !== '' && !ctype_digit($termNo)) {
            $this->backWithInput($path, $_POST, '屆次須為正整數。');
        }
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $termNo = trim((string) ($_POST['term_no'] ?? ''));
        $status = ($_POST['status'] ?? 'current') === 'former' ? 'former' : 'current';
        $startedOn = $this->nullableDate('started_on');
        $endedOn = $this->nullableDate('ended_on');
        if ($status === 'former' && $endedOn === null) {
            $endedOn = date('Y-m-d');
        }
        if ($status === 'current') {
            $endedOn = null;
        }

        return [
            'term_no' => ($termNo !== '' && ctype_digit($termNo)) ? (int) $termNo : null,
            'name' => trim((string) $_POST['name']),
            'title' => $this->nullablePost('title'),
            'organization' => $this->nullablePost('organization'),
            'job_title' => $this->nullablePost('job_title'),
            'phone' => $this->nullablePost('phone'),
            'mobile' => $this->nullablePost('mobile'),
            'email' => $this->nullablePost('email'),
            'address' => $this->nullablePost('address'),
            'status' => $status,
            'started_on' => $startedOn,
            'ended_on' => $endedOn,
            'notes' => $this->nullablePost('notes'),
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
    private function blankDirector(): array
    {
        return [
            'term_no' => '', 'name' => '', 'title' => '', 'organization' => '', 'job_title' => '',
            'phone' => '', 'mobile' => '', 'email' => '', 'address' => '',
            'status' => 'current', 'started_on' => '', 'ended_on' => '', 'notes' => '',
        ];
    }

    /** @return array<string, mixed> */
    private function findDirector(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT board_directors.*, users.name AS created_by_name
             FROM board_directors
             LEFT JOIN users ON users.id = board_directors.created_by
             WHERE board_directors.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $director = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$director) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到董事資料']);
            exit;
        }

        return $director;
    }

    /** 合併常用職稱與資料庫既有職稱,供下拉建議。 @return array<int, string> */
    private function titleOptions(): array
    {
        $existing = Database::pdo()
            ->query('SELECT DISTINCT title FROM board_directors WHERE title IS NOT NULL AND title <> "" ORDER BY title')
            ->fetchAll(PDO::FETCH_COLUMN) ?: [];

        return array_values(array_unique(array_merge(self::TITLES, array_map('strval', $existing))));
    }
}
