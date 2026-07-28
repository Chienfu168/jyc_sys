<?php

namespace App\Modules\PettyCash\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use PDO;

final class PettyCashController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('petty_cash.view');

        $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? ''))
            ? (string) $_GET['month']
            : date('Y-m');

        $stmt = Database::pdo()->prepare(
            'SELECT petty_cash_entries.*, users.name AS created_by_name
             FROM petty_cash_entries
             LEFT JOIN users ON users.id = petty_cash_entries.created_by
             WHERE DATE_FORMAT(petty_cash_entries.occurred_on, "%Y-%m") = :month
             ORDER BY petty_cash_entries.occurred_on DESC, petty_cash_entries.id DESC'
        );
        $stmt->execute(['month' => $month]);
        $entries = $stmt->fetchAll();

        $this->render('petty-cash.index', [
            'title' => '零用金',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entries' => $entries,
            'month' => $month,
            'totals' => $this->totals($entries),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('petty_cash.manage');

        $this->render('petty-cash.create', [
            'title' => '新增零用金紀錄',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entry' => [
                'occurred_on' => date('Y-m-d'),
                'item_type' => 'expense',
                'petty_cash_item_id' => '',
                'item_name' => '',
                'amount' => '',
                'payment_to' => '',
                'receipt_no' => '',
                'notes' => '',
            ],
            'items' => $this->items(),
            'action' => '/petty-cash',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->validateEntry('/petty-cash/create');

        $item = $this->selectedItem();
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        if ($item && $itemName === '') {
            $itemName = $item['name'];
        }

        Database::pdo()->prepare(
            'INSERT INTO petty_cash_entries
             (occurred_on, item_type, petty_cash_item_id, item_name, amount, payment_to, receipt_no, notes, created_by, created_at, updated_at)
             VALUES (:occurred_on, :item_type, :petty_cash_item_id, :item_name, :amount, :payment_to, :receipt_no, :notes, :created_by, :created_at, :updated_at)'
        )->execute([
            'occurred_on' => $_POST['occurred_on'],
            'item_type' => $item['item_type'] ?? $this->typeValue(),
            'petty_cash_item_id' => $item['id'] ?? null,
            'item_name' => $itemName,
            'amount' => $this->amountValue(),
            'payment_to' => trim((string) ($_POST['payment_to'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'created_by' => auth()->user()['id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id = (int) Database::pdo()->lastInsertId();
        AuditLog::write('create', 'petty_cash', 'petty_cash_entries', $id);
        flash('success', '零用金紀錄已建立');
        redirect('/petty-cash?month=' . substr((string) $_POST['occurred_on'], 0, 7));
    }

    public function edit(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $entry = $this->findEntry((int) $id);

        $this->render('petty-cash.edit', [
            'title' => '編輯零用金紀錄',
            'section' => '財務會計',
            'active' => 'petty-cash',
            'entry' => $entry,
            'items' => $this->items(),
            'action' => '/petty-cash/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('petty_cash.manage');
        $this->findEntry((int) $id);
        $this->validateEntry('/petty-cash/' . $id . '/edit');

        $item = $this->selectedItem();
        $itemName = trim((string) ($_POST['item_name'] ?? ''));
        if ($item && $itemName === '') {
            $itemName = $item['name'];
        }

        Database::pdo()->prepare(
            'UPDATE petty_cash_entries
             SET occurred_on = :occurred_on,
                 item_type = :item_type,
                 petty_cash_item_id = :petty_cash_item_id,
                 item_name = :item_name,
                 amount = :amount,
                 payment_to = :payment_to,
                 receipt_no = :receipt_no,
                 notes = :notes,
                 updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'occurred_on' => $_POST['occurred_on'],
            'item_type' => $item['item_type'] ?? $this->typeValue(),
            'petty_cash_item_id' => $item['id'] ?? null,
            'item_name' => $itemName,
            'amount' => $this->amountValue(),
            'payment_to' => trim((string) ($_POST['payment_to'] ?? '')),
            'receipt_no' => trim((string) ($_POST['receipt_no'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('update', 'petty_cash', 'petty_cash_entries', (int) $id);
        flash('success', '零用金紀錄已更新');
        redirect('/petty-cash?month=' . substr((string) $_POST['occurred_on'], 0, 7));
    }

    private function validateEntry(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'occurred_on' => '日期',
            'item_type' => '類型',
            'amount' => '金額',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST['occurred_on'])) {
            $this->backWithInput($path, $_POST, '日期格式不正確。');
        }

        if ($this->amountValue() <= 0) {
            $this->backWithInput($path, $_POST, '金額必須大於 0。');
        }

        if (!$this->selectedItem() && trim((string) ($_POST['item_name'] ?? '')) === '') {
            $this->backWithInput($path, $_POST, '請選擇常用項目或輸入項目名稱。');
        }
    }

    private function findEntry(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM petty_cash_entries WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到零用金紀錄']);
            exit;
        }

        return $entry;
    }

    private function items(): array
    {
        return Database::pdo()->query(
            'SELECT * FROM petty_cash_items
             WHERE status = "active"
             ORDER BY item_type, sort_order, name'
        )->fetchAll();
    }

    private function selectedItem(): ?array
    {
        $id = (int) ($_POST['petty_cash_item_id'] ?? 0);
        if ($id <= 0) {
            return null;
        }

        $stmt = Database::pdo()->prepare('SELECT * FROM petty_cash_items WHERE id = :id AND status = "active" LIMIT 1');
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        return $item ?: null;
    }

    private function totals(array $entries): array
    {
        $income = 0.0;
        $expense = 0.0;

        foreach ($entries as $entry) {
            if ($entry['item_type'] === 'income') {
                $income += (float) $entry['amount'];
            } else {
                $expense += (float) $entry['amount'];
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    private function typeValue(): string
    {
        return ($_POST['item_type'] ?? '') === 'income' ? 'income' : 'expense';
    }

    private function amountValue(): float
    {
        return round((float) ($_POST['amount'] ?? 0), 2);
    }
}
