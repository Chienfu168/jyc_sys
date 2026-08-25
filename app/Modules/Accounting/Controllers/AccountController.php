<?php

namespace App\Modules\Accounting\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class AccountController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('accounting.view');

        $accounts = Database::pdo()->query(
            'SELECT accounting_accounts.*, parents.code AS parent_code, parents.name AS parent_name
             FROM accounting_accounts
             LEFT JOIN accounting_accounts AS parents ON parents.id = accounting_accounts.parent_id
             ORDER BY accounting_accounts.sort_order, accounting_accounts.code'
        )->fetchAll();

        $this->render('accounting.accounts.index', [
            'title' => '會計科目',
            'section' => '財務會計',
            'active' => 'accounting',
            'accounts' => $accounts,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('accounting.manage');

        $this->render('accounting.accounts.create', [
            'title' => '新增會計科目',
            'section' => '財務會計',
            'active' => 'accounting',
            'account' => [
                'code' => '',
                'name' => '',
                'account_type' => 'expense',
                'parent_id' => '',
                'normal_balance' => 'debit',
                'description' => '',
                'sort_order' => 0,
                'status' => 'active',
            ],
            'parents' => $this->parentAccounts(),
            'action' => '/accounting/accounts',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('accounting.manage');
        $this->validateAccount('/accounting/accounts/create');

        Database::pdo()->prepare(
            'INSERT INTO accounting_accounts
             (code, name, account_type, parent_id, normal_balance, description, sort_order, status, created_at, updated_at)
             VALUES
             (:code, :name, :account_type, :parent_id, :normal_balance, :description, :sort_order, :status, :created_at, :updated_at)'
        )->execute($this->payload() + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'accounting', 'accounting_accounts', $id);
        flash('success', '會計科目已建立。');
        redirect('/accounting/accounts');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('accounting.manage');

        $this->render('accounting.accounts.edit', [
            'title' => '編輯會計科目',
            'section' => '財務會計',
            'active' => 'accounting',
            'account' => $this->findAccount((int) $id),
            'parents' => $this->parentAccounts((int) $id),
            'action' => '/accounting/accounts/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $account = $this->findAccount((int) $id);
        $this->validateAccount('/accounting/accounts/' . $id . '/edit', (int) $id);

        Database::pdo()->prepare(
            'UPDATE accounting_accounts
             SET code = :code,
                 name = :name,
                 account_type = :account_type,
                 parent_id = :parent_id,
                 normal_balance = :normal_balance,
                 description = :description,
                 sort_order = :sort_order,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($this->payload() + [
            'updated_at' => now(),
            'id' => $account['id'],
        ]);

        AuditLog::write('update', 'accounting', 'accounting_accounts', (int) $id);
        flash('success', '會計科目已更新。');
        redirect('/accounting/accounts');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('accounting.manage');
        $account = $this->findAccount((int) $id);
        $status = $account['status'] === 'active' ? 'disabled' : 'active';

        Database::pdo()->prepare('UPDATE accounting_accounts SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'status' => $status,
                'updated_at' => now(),
                'id' => $account['id'],
            ]);

        AuditLog::write('toggle', 'accounting', 'accounting_accounts', (int) $id);
        flash('success', '會計科目狀態已更新。');
        redirect('/accounting/accounts');
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('accounting.delete');
        $account = $this->findAccount((int) $id);

        $usage = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_voucher_lines WHERE account_id = :id');
        $usage->execute(['id' => (int) $account['id']]);
        if ((int) $usage->fetchColumn() > 0) {
            flash('error', '此會計科目已用於傳票分錄，無法刪除，可改為「停用」。');
            redirect('/accounting/accounts');
        }

        $children = Database::pdo()->prepare('SELECT COUNT(*) FROM accounting_accounts WHERE parent_id = :id');
        $children->execute(['id' => (int) $account['id']]);
        if ((int) $children->fetchColumn() > 0) {
            flash('error', '此會計科目仍有子科目，請先調整子科目後再刪除。');
            redirect('/accounting/accounts');
        }

        Database::pdo()->prepare('DELETE FROM accounting_accounts WHERE id = :id')->execute(['id' => (int) $account['id']]);

        AuditLog::write('delete', 'accounting', 'accounting_accounts', (int) $account['id'], [
            'code' => $account['code'] ?? null,
            'name' => $account['name'] ?? null,
        ]);
        flash('success', '會計科目已刪除。');
        redirect('/accounting/accounts');
    }

    private function validateAccount(string $path, ?int $ignoreId = null): void
    {
        if ($error = Validator::required($_POST, [
            'code' => '科目代碼',
            'name' => '科目名稱',
            'account_type' => '科目類型',
            'normal_balance' => '借貸方向',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^[A-Za-z0-9._-]{2,30}$/', (string) $_POST['code'])) {
            $this->backWithInput($path, $_POST, '科目代碼請使用 2 到 30 個英數字、底線、橫線或小數點。');
        }

        if (!in_array($_POST['account_type'] ?? '', ['asset', 'liability', 'net_asset', 'income', 'expense'], true)) {
            $this->backWithInput($path, $_POST, '科目類型不正確。');
        }

        if (!in_array($_POST['normal_balance'] ?? '', ['debit', 'credit'], true)) {
            $this->backWithInput($path, $_POST, '借貸方向不正確。');
        }

        $sql = 'SELECT id FROM accounting_accounts WHERE code = :code';
        $params = ['code' => trim((string) $_POST['code'])];
        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetchColumn()) {
            $this->backWithInput($path, $_POST, '科目代碼已存在。');
        }
    }

    private function payload(): array
    {
        $parentId = (int) ($_POST['parent_id'] ?? 0);

        return [
            'code' => trim((string) $_POST['code']),
            'name' => trim((string) $_POST['name']),
            'account_type' => (string) $_POST['account_type'],
            'parent_id' => $parentId > 0 ? $parentId : null,
            'normal_balance' => (string) $_POST['normal_balance'],
            'description' => trim((string) ($_POST['description'] ?? '')),
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'status' => in_array($_POST['status'] ?? '', ['active', 'disabled'], true) ? (string) $_POST['status'] : 'active',
        ];
    }

    private function findAccount(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM accounting_accounts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到會計科目']);
            exit;
        }

        return $account;
    }

    private function parentAccounts(?int $excludeId = null): array
    {
        $sql = 'SELECT id, code, name FROM accounting_accounts WHERE status = "active"';
        $params = [];
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        $sql .= ' ORDER BY sort_order, code';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
