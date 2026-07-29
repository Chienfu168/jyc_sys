<?php

namespace App\Modules\Lecturers\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class LecturerController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('lecturers.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['active', 'inactive'], true) ? (string) $_GET['status'] : '';

        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(name LIKE :keyword OR display_name LIKE :keyword OR specialty LIKE :keyword OR organization LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT * FROM lecturers';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY status, name, id DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $lecturers = $stmt->fetchAll();

        $summary = [
            'total' => count($lecturers),
            'active' => count(array_filter($lecturers, static fn (array $lecturer): bool => $lecturer['status'] === 'active')),
            'avg_rate' => $this->averageRate($lecturers),
        ];

        $this->render('lecturers.index', [
            'title' => '講師管理',
            'section' => '業務與人事',
            'active' => 'lecturers',
            'lecturers' => $lecturers,
            'keyword' => $keyword,
            'status' => $status,
            'summary' => $summary,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('lecturers.manage');

        $this->render('lecturers.create', [
            'title' => '新增講師',
            'section' => '業務與人事',
            'active' => 'lecturers',
            'lecturer' => [
                'name' => '',
                'display_name' => '',
                'tax_id' => '',
                'phone' => '',
                'mobile' => '',
                'email' => '',
                'address' => '',
                'organization' => '',
                'job_title' => '',
                'specialty' => '',
                'hourly_rate' => '',
                'bank_name' => '',
                'bank_branch' => '',
                'bank_account_no' => '',
                'bank_account_name' => '',
                'status' => 'active',
                'notes' => '',
            ],
            'action' => '/lecturers',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('lecturers.manage');
        $this->validateLecturer('/lecturers/create');

        Database::pdo()->prepare(
            'INSERT INTO lecturers
             (name, display_name, tax_id, phone, mobile, email, address, organization, job_title, specialty, hourly_rate, bank_name, bank_branch, bank_account_no, bank_account_name, status, notes, created_by, created_at, updated_at)
             VALUES
             (:name, :display_name, :tax_id, :phone, :mobile, :email, :address, :organization, :job_title, :specialty, :hourly_rate, :bank_name, :bank_branch, :bank_account_no, :bank_account_name, :status, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'lecturers', 'lecturers', $id);
        flash('success', '講師資料已建立。');
        redirect('/lecturers/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('lecturers.view');

        $this->render('lecturers.show', [
            'title' => '講師資料',
            'section' => '業務與人事',
            'active' => 'lecturers',
            'lecturer' => $this->findLecturer((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('lecturers.manage');

        $this->render('lecturers.edit', [
            'title' => '編輯講師',
            'section' => '業務與人事',
            'active' => 'lecturers',
            'lecturer' => $this->findLecturer((int) $id),
            'action' => '/lecturers/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('lecturers.manage');
        $lecturer = $this->findLecturer((int) $id);
        $this->validateLecturer('/lecturers/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE lecturers
             SET name = :name,
                 display_name = :display_name,
                 tax_id = :tax_id,
                 phone = :phone,
                 mobile = :mobile,
                 email = :email,
                 address = :address,
                 organization = :organization,
                 job_title = :job_title,
                 specialty = :specialty,
                 hourly_rate = :hourly_rate,
                 bank_name = :bank_name,
                 bank_branch = :bank_branch,
                 bank_account_no = :bank_account_no,
                 bank_account_name = :bank_account_name,
                 status = :status,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => $lecturer['id'],
        ]);

        AuditLog::write('update', 'lecturers', 'lecturers', (int) $id);
        flash('success', '講師資料已更新。');
        redirect('/lecturers/' . $id);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('lecturers.manage');
        $lecturer = $this->findLecturer((int) $id);
        $status = $lecturer['status'] === 'active' ? 'inactive' : 'active';

        Database::pdo()->prepare('UPDATE lecturers SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => $lecturer['id'],
            ]);

        AuditLog::write('toggle', 'lecturers', 'lecturers', (int) $id);
        flash('success', '講師狀態已更新。');
        redirect('/lecturers/' . $id);
    }

    private function validateLecturer(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'name' => '姓名',
            'specialty' => '專長',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (($this->amountValue('hourly_rate')) < 0) {
            $this->backWithInput($path, $_POST, '常用鐘點費不可小於 0。');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput($path, $_POST, 'Email 格式不正確。');
        }
    }

    private function payload(): array
    {
        $displayName = trim((string) ($_POST['display_name'] ?? ''));

        return [
            'name' => trim((string) $_POST['name']),
            'display_name' => $displayName,
            'tax_id' => trim((string) ($_POST['tax_id'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'mobile' => trim((string) ($_POST['mobile'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'organization' => trim((string) ($_POST['organization'] ?? '')),
            'job_title' => trim((string) ($_POST['job_title'] ?? '')),
            'specialty' => trim((string) $_POST['specialty']),
            'hourly_rate' => $this->amountValue('hourly_rate'),
            'bank_name' => trim((string) ($_POST['bank_name'] ?? '')),
            'bank_branch' => trim((string) ($_POST['bank_branch'] ?? '')),
            'bank_account_no' => trim((string) ($_POST['bank_account_no'] ?? '')),
            'bank_account_name' => trim((string) ($_POST['bank_account_name'] ?? '')),
            'status' => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? (string) $_POST['status'] : 'active',
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findLecturer(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT lecturers.*, users.name AS created_by_name
             FROM lecturers
             LEFT JOIN users ON users.id = lecturers.created_by
             WHERE lecturers.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $lecturer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lecturer) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到講師資料']);
            exit;
        }

        return $lecturer;
    }

    private function amountValue(string $key): float
    {
        return round((float) ($_POST[$key] ?? 0), 2);
    }

    private function averageRate(array $lecturers): float
    {
        $rates = array_filter(
            array_map(static fn (array $lecturer): float => (float) $lecturer['hourly_rate'], $lecturers),
            static fn (float $rate): bool => $rate > 0
        );

        return $rates ? array_sum($rates) / count($rates) : 0.0;
    }
}
