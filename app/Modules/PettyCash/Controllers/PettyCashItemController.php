<?php

namespace App\Modules\PettyCash\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class PettyCashItemController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('petty_cash.manage');

        $items = Database::pdo()->query(
            'SELECT * FROM petty_cash_items ORDER BY item_type, status, sort_order, name'
        )->fetchAll();

        $this->render('petty-cash.items.index', [
            'title' => '零用金常用項目',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'items' => $items,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('petty_cash.manage');

        $this->render('petty-cash.items.create', [
            'title' => '新增常用項目',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'item' => [
                'item_type' => 'expense',
                'name' => '',
                'default_amount' => '',
                'sort_order' => 10,
                'status' => 'active',
            ],
            'action' => '/petty-cash-items',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->validateItem('/petty-cash-items/create');

        $params = $this->params();
        $params['created_at'] = now();

        Database::pdo()->prepare(
            'INSERT INTO petty_cash_items (item_type, name, default_amount, sort_order, status, created_at, updated_at)
             VALUES (:item_type, :name, :default_amount, :sort_order, :status, :created_at, :updated_at)'
        )->execute($params);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'petty_cash', 'petty_cash_items', $id);
        flash('success', '常用項目已建立');
        redirect('/petty-cash-items');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('petty_cash.manage');

        $this->render('petty-cash.items.edit', [
            'title' => '編輯常用項目',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'item' => $this->findItem((int) $id),
            'action' => '/petty-cash-items/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->findItem((int) $id);
        $this->validateItem('/petty-cash-items/' . $id . '/edit');

        $params = $this->params();
        $params['id'] = (int) $id;

        Database::pdo()->prepare(
            'UPDATE petty_cash_items
             SET item_type = :item_type,
                 name = :name,
                 default_amount = :default_amount,
                 sort_order = :sort_order,
                 status = :status,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute($params);

        AuditLog::write('update', 'petty_cash', 'petty_cash_items', (int) $id);
        flash('success', '常用項目已更新');
        redirect('/petty-cash-items');
    }

    public function toggle(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $item = $this->findItem((int) $id);
        $status = $item['status'] === 'active' ? 'disabled' : 'active';

        Database::pdo()->prepare('UPDATE petty_cash_items SET status = :status, updated_at = :updated_at WHERE id = :id')
            ->execute(['status' => $status, 'updated_at' => now(), 'id' => (int) $id]);

        AuditLog::write('toggle_status', 'petty_cash', 'petty_cash_items', (int) $id, ['status' => $status]);
        flash('success', '常用項目狀態已更新');
        redirect('/petty-cash-items');
    }

    private function validateItem(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'item_type' => '類型',
            'name' => '項目名稱',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }
    }

    private function params(): array
    {
        $defaultAmount = trim((string) ($_POST['default_amount'] ?? ''));

        return [
            'item_type' => ($_POST['item_type'] ?? '') === 'income' ? 'income' : 'expense',
            'name' => trim((string) $_POST['name']),
            'default_amount' => $defaultAmount === '' ? null : round((float) $defaultAmount, 2),
            'sort_order' => max(0, (int) ($_POST['sort_order'] ?? 0)),
            'status' => ($_POST['status'] ?? '') === 'disabled' ? 'disabled' : 'active',
            'updated_at' => now(),
        ];
    }

    private function findItem(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM petty_cash_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到常用項目']);
            exit;
        }

        return $item;
    }
}
