<?php

namespace App\Modules\Contacts\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use PDO;

/**
 * 共用通訊錄:集中管理各學校、政府機關、合作單位、廠商等外部聯絡人。
 * 屬全機構共用資料——具檢視權限者皆可查閱;具管理權限者可新增／編輯／刪除。
 */
final class ContactController extends Controller
{
    /** 常用分類(供下拉建議,亦可自行輸入)。 */
    private const CATEGORIES = ['學校', '政府機關', '合作單位', '廠商／供應商', '媒體', '個人', '其他'];

    public function index(): void
    {
        $this->requirePermission('contacts.view');

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $status = in_array(($_GET['status'] ?? ''), ['active', 'inactive'], true) ? (string) $_GET['status'] : 'active';

        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(name LIKE :kw OR organization LIKE :kw2 OR job_title LIKE :kw3 OR phone LIKE :kw4 OR mobile LIKE :kw5 OR email LIKE :kw6)';
            $like = '%' . $keyword . '%';
            $params += ['kw' => $like, 'kw2' => $like, 'kw3' => $like, 'kw4' => $like, 'kw5' => $like, 'kw6' => $like];
        }
        if ($category !== '') {
            $where[] = 'category = :category';
            $params['category'] = $category;
        }
        if ($status !== '') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT * FROM contacts';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY category, organization, name, id';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll();

        $this->render('contacts.index', [
            'title' => '通訊錄',
            'section' => '業務推動',
            'active' => 'contacts',
            'contacts' => $contacts,
            'keyword' => $keyword,
            'category' => $category,
            'status' => $status,
            'categories' => $this->categoryOptions(),
            'canManage' => Permission::can('contacts.manage'),
            'profile' => foundation_profile(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('contacts.manage');

        $this->render('contacts.form', [
            'title' => '新增聯絡人',
            'section' => '業務推動',
            'active' => 'contacts',
            'contact' => $this->blankContact(),
            'categories' => $this->categoryOptions(),
            'action' => '/contacts',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('contacts.manage');
        $this->validateContact('/contacts/create');

        Database::pdo()->prepare(
            'INSERT INTO contacts
             (name, organization, job_title, category, phone, mobile, fax, email, address, notes, status,
              created_by, created_at, updated_at)
             VALUES
             (:name, :organization, :job_title, :category, :phone, :mobile, :fax, :email, :address, :notes, :status,
              :created_by, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_by' => $this->currentUserId() ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'contacts', 'contacts', $id);
        flash('success', '聯絡人已建立。');
        redirect('/contacts/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('contacts.view');
        $contact = $this->findContact((int) $id);

        $this->render('contacts.show', [
            'title' => '聯絡人資料',
            'section' => '業務推動',
            'active' => 'contacts',
            'contact' => $contact,
            'canManage' => Permission::can('contacts.manage'),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('contacts.manage');

        $this->render('contacts.form', [
            'title' => '編輯聯絡人',
            'section' => '業務推動',
            'active' => 'contacts',
            'contact' => $this->findContact((int) $id),
            'categories' => $this->categoryOptions(),
            'action' => '/contacts/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $this->findContact((int) $id);
        $this->validateContact('/contacts/' . $id . '/edit');

        Database::pdo()->prepare(
            'UPDATE contacts SET
                name = :name, organization = :organization, job_title = :job_title, category = :category,
                phone = :phone, mobile = :mobile, fax = :fax, email = :email, address = :address, notes = :notes,
                status = :status, updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'contacts', 'contacts', (int) $id);
        flash('success', '聯絡人已更新。');
        redirect('/contacts/' . $id);
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $contact = $this->findContact((int) $id);
        $status = $contact['status'] === 'active' ? 'inactive' : 'active';

        Database::pdo()->prepare('UPDATE contacts SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute(['status' => $status, 'updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('toggle', 'contacts', 'contacts', (int) $id);
        flash('success', $status === 'active' ? '已恢復啟用。' : '已封存(停用)。');
        redirect('/contacts/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $contact = $this->findContact((int) $id);

        Database::pdo()->prepare('DELETE FROM contacts WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'contacts', 'contacts', (int) $id, ['name' => $contact['name']]);
        flash('success', '聯絡人已刪除。');
        redirect('/contacts');
    }

    // ---- 內部輔助 ----

    private function validateContact(string $path): void
    {
        if ($error = Validator::required($_POST, ['name' => '姓名'])) {
            $this->backWithInput($path, $_POST, $error);
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithInput($path, $_POST, 'Email 格式不正確。');
        }
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'name' => trim((string) $_POST['name']),
            'organization' => $this->nullable('organization'),
            'job_title' => $this->nullable('job_title'),
            'category' => $this->nullable('category'),
            'phone' => $this->nullable('phone'),
            'mobile' => $this->nullable('mobile'),
            'fax' => $this->nullable('fax'),
            'email' => $this->nullable('email'),
            'address' => $this->nullable('address'),
            'notes' => $this->nullable('notes'),
            'status' => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
        ];
    }

    private function nullable(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return $value !== '' ? $value : null;
    }

    /** @return array<string, mixed> */
    private function blankContact(): array
    {
        return [
            'name' => '', 'organization' => '', 'job_title' => '', 'category' => '',
            'phone' => '', 'mobile' => '', 'fax' => '', 'email' => '', 'address' => '',
            'notes' => '', 'status' => 'active',
        ];
    }

    /** @return array<string, mixed> */
    private function findContact(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT contacts.*, users.name AS created_by_name
             FROM contacts
             LEFT JOIN users ON users.id = contacts.created_by
             WHERE contacts.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contact) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到聯絡人']);
            exit;
        }

        return $contact;
    }

    /** 合併常用分類與資料庫既有分類,供下拉建議。 @return array<int, string> */
    private function categoryOptions(): array
    {
        $existing = Database::pdo()
            ->query('SELECT DISTINCT category FROM contacts WHERE category IS NOT NULL AND category <> "" ORDER BY category')
            ->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $merged = array_values(array_unique(array_merge(self::CATEGORIES, array_map('strval', $existing))));
        return $merged;
    }
}
