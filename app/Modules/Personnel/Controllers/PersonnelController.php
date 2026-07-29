<?php

namespace App\Modules\Personnel\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class PersonnelController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('personnel.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['active', 'on_leave', 'resigned'], true) ? (string) $_GET['status'] : '';
        $department = trim((string) ($_GET['department'] ?? ''));

        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(name LIKE :keyword OR employee_no LIKE :keyword OR job_title LIKE :keyword OR email LIKE :keyword OR mobile LIKE :keyword)';
            $params['keyword'] = '%' . $keyword . '%';
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }
        if ($department !== '') {
            $where[] = 'department = :department';
            $params['department'] = $department;
        }

        $sql = 'SELECT * FROM personnel_employees';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY status, department, name, id DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        $this->render('personnel.index', [
            'title' => '人事管理',
            'section' => '業務與人事',
            'active' => 'personnel',
            'employees' => $employees,
            'keyword' => $keyword,
            'status' => $status,
            'department' => $department,
            'departments' => $this->departments(),
            'summary' => $this->summary($employees),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('personnel.manage');

        $this->render('personnel.create', [
            'title' => '新增人事資料',
            'section' => '業務與人事',
            'active' => 'personnel',
            'employee' => $this->blankEmployee(),
            'users' => $this->users(),
            'action' => '/personnel',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('personnel.manage');
        $this->validateEmployee('/personnel/create');

        Database::pdo()->prepare(
            'INSERT INTO personnel_employees
             (user_id, employee_no, name, identity_no, birth_date, gender, phone, mobile, email, address, department, job_title, employment_type, hire_date, leave_date, base_salary, labor_insurance_amount, health_insurance_amount, pension_rate, bank_name, bank_branch, bank_account_no, bank_account_name, emergency_contact, emergency_phone, status, notes, created_by, created_at, updated_at)
             VALUES
             (:user_id, :employee_no, :name, :identity_no, :birth_date, :gender, :phone, :mobile, :email, :address, :department, :job_title, :employment_type, :hire_date, :leave_date, :base_salary, :labor_insurance_amount, :health_insurance_amount, :pension_rate, :bank_name, :bank_branch, :bank_account_no, :bank_account_name, :emergency_contact, :emergency_phone, :status, :notes, :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'personnel', 'personnel_employees', $id);
        flash('success', '人事資料已建立。');
        redirect('/personnel/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('personnel.view');

        $this->render('personnel.show', [
            'title' => '人事資料',
            'section' => '業務與人事',
            'active' => 'personnel',
            'employee' => $this->findEmployee((int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('personnel.manage');

        $this->render('personnel.edit', [
            'title' => '編輯人事資料',
            'section' => '業務與人事',
            'active' => 'personnel',
            'employee' => $this->findEmployee((int) $id),
            'users' => $this->users(),
            'action' => '/personnel/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('personnel.manage');
        $employee = $this->findEmployee((int) $id);
        $this->validateEmployee('/personnel/' . $id . '/edit', (int) $id);

        Database::pdo()->prepare(
            'UPDATE personnel_employees
             SET user_id = :user_id,
                 employee_no = :employee_no,
                 name = :name,
                 identity_no = :identity_no,
                 birth_date = :birth_date,
                 gender = :gender,
                 phone = :phone,
                 mobile = :mobile,
                 email = :email,
                 address = :address,
                 department = :department,
                 job_title = :job_title,
                 employment_type = :employment_type,
                 hire_date = :hire_date,
                 leave_date = :leave_date,
                 base_salary = :base_salary,
                 labor_insurance_amount = :labor_insurance_amount,
                 health_insurance_amount = :health_insurance_amount,
                 pension_rate = :pension_rate,
                 bank_name = :bank_name,
                 bank_branch = :bank_branch,
                 bank_account_no = :bank_account_no,
                 bank_account_name = :bank_account_name,
                 emergency_contact = :emergency_contact,
                 emergency_phone = :emergency_phone,
                 status = :status,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => $employee['id'],
        ]);

        AuditLog::write('update', 'personnel', 'personnel_employees', (int) $id);
        flash('success', '人事資料已更新。');
        redirect('/personnel/' . $id);
    }

    public function updateStatus(string $id): void
    {
        $this->requirePermission('personnel.manage');
        $this->findEmployee((int) $id);

        $status = in_array(($_POST['status'] ?? ''), ['active', 'on_leave', 'resigned'], true)
            ? (string) $_POST['status']
            : 'active';

        Database::pdo()->prepare('UPDATE personnel_employees SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => (int) $id,
            ]);

        AuditLog::write('status', 'personnel', 'personnel_employees', (int) $id);
        flash('success', '人事狀態已更新。');
        redirect('/personnel/' . $id);
    }

    private function validateEmployee(string $path, ?int $ignoreId = null): void
    {
        if ($error = Validator::required($_POST, [
            'name' => '姓名',
            'employment_type' => '任用類型',
            'status' => '狀態',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $employeeNo = trim((string) ($_POST['employee_no'] ?? ''));
        if ($employeeNo !== '') {
            $sql = 'SELECT id FROM personnel_employees WHERE employee_no = :employee_no';
            $params = ['employee_no' => $employeeNo];
            if ($ignoreId !== null) {
                $sql .= ' AND id != :ignore_id';
                $params['ignore_id'] = $ignoreId;
            }
            $sql .= ' LIMIT 1';
            $stmt = Database::pdo()->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetchColumn()) {
                $this->backWithInput($path, $_POST, '員工編號已存在。');
            }
        }

        foreach (['birth_date', 'hire_date', 'leave_date'] as $key) {
            $date = trim((string) ($_POST[$key] ?? ''));
            if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $this->backWithInput($path, $_POST, '日期格式不正確。');
            }
        }

        if ((string) ($_POST['leave_date'] ?? '') !== ''
            && (string) ($_POST['hire_date'] ?? '') !== ''
            && (string) $_POST['leave_date'] < (string) $_POST['hire_date']) {
            $this->backWithInput($path, $_POST, '離職日期不可早於到職日期。');
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput($path, $_POST, 'Email 格式不正確。');
        }

        foreach (['base_salary', 'labor_insurance_amount', 'health_insurance_amount', 'pension_rate'] as $key) {
            if ($this->amountValue($key) < 0) {
                $this->backWithInput($path, $_POST, '薪資、保費與提繳率不可小於 0。');
            }
        }

        if (!in_array($_POST['gender'] ?? '', ['female', 'male', 'other', 'undisclosed'], true)) {
            $this->backWithInput($path, $_POST, '性別選項不正確。');
        }
        if (!in_array($_POST['employment_type'] ?? '', ['full_time', 'part_time', 'contract', 'volunteer_staff', 'other'], true)) {
            $this->backWithInput($path, $_POST, '任用類型不正確。');
        }
        if (!in_array($_POST['status'] ?? '', ['active', 'on_leave', 'resigned'], true)) {
            $this->backWithInput($path, $_POST, '狀態不正確。');
        }
    }

    private function payload(): array
    {
        $userId = (int) ($_POST['user_id'] ?? 0);

        return [
            'user_id' => $userId > 0 ? $userId : null,
            'employee_no' => $this->nullableText('employee_no'),
            'name' => trim((string) $_POST['name']),
            'identity_no' => $this->nullableText('identity_no'),
            'birth_date' => $this->nullableText('birth_date'),
            'gender' => (string) ($_POST['gender'] ?? 'undisclosed'),
            'phone' => $this->nullableText('phone'),
            'mobile' => $this->nullableText('mobile'),
            'email' => $this->nullableText('email'),
            'address' => $this->nullableText('address'),
            'department' => $this->nullableText('department'),
            'job_title' => $this->nullableText('job_title'),
            'employment_type' => (string) $_POST['employment_type'],
            'hire_date' => $this->nullableText('hire_date'),
            'leave_date' => $this->nullableText('leave_date'),
            'base_salary' => $this->amountValue('base_salary'),
            'labor_insurance_amount' => $this->amountValue('labor_insurance_amount'),
            'health_insurance_amount' => $this->amountValue('health_insurance_amount'),
            'pension_rate' => $this->amountValue('pension_rate'),
            'bank_name' => $this->nullableText('bank_name'),
            'bank_branch' => $this->nullableText('bank_branch'),
            'bank_account_no' => $this->nullableText('bank_account_no'),
            'bank_account_name' => $this->nullableText('bank_account_name'),
            'emergency_contact' => $this->nullableText('emergency_contact'),
            'emergency_phone' => $this->nullableText('emergency_phone'),
            'status' => (string) $_POST['status'],
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function findEmployee(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT personnel_employees.*, users.name AS user_name, creators.name AS created_by_name
             FROM personnel_employees
             LEFT JOIN users ON users.id = personnel_employees.user_id
             LEFT JOIN users AS creators ON creators.id = personnel_employees.created_by
             WHERE personnel_employees.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到人事資料']);
            exit;
        }

        return $employee;
    }

    private function users(): array
    {
        return Database::pdo()->query('SELECT id, name, email FROM users WHERE status = "active" ORDER BY name')->fetchAll();
    }

    private function departments(): array
    {
        return Database::pdo()->query(
            'SELECT DISTINCT department FROM personnel_employees
             WHERE department IS NOT NULL AND department != ""
             ORDER BY department'
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    private function summary(array $employees): array
    {
        return [
            'total' => count($employees),
            'active' => count(array_filter($employees, static fn (array $employee): bool => $employee['status'] === 'active')),
            'salary_total' => array_sum(array_map(static fn (array $employee): float => (float) $employee['base_salary'], $employees)),
        ];
    }

    private function blankEmployee(): array
    {
        return [
            'user_id' => '',
            'employee_no' => '',
            'name' => '',
            'identity_no' => '',
            'birth_date' => '',
            'gender' => 'undisclosed',
            'phone' => '',
            'mobile' => '',
            'email' => '',
            'address' => '',
            'department' => '',
            'job_title' => '',
            'employment_type' => 'full_time',
            'hire_date' => date('Y-m-d'),
            'leave_date' => '',
            'base_salary' => '',
            'labor_insurance_amount' => '',
            'health_insurance_amount' => '',
            'pension_rate' => '6.00',
            'bank_name' => '',
            'bank_branch' => '',
            'bank_account_no' => '',
            'bank_account_name' => '',
            'emergency_contact' => '',
            'emergency_phone' => '',
            'status' => 'active',
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
