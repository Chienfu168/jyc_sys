<?php

namespace App\Modules\BalanceSheets\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\BalanceSheets\BalanceSheetSummary;
use PDO;

/**
 * 資產負債表(獨立手動輸入,暫不連結實際帳務):
 * 逐列輸入本年底決算數、上年底決算數,系統自動計算比較增減與比率,並彙總資產/負債/淨值合計,
 * 供陳報主管機關(教育局)核備。
 */
final class BalanceSheetController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('balance_sheets.view');

        $sheets = Database::pdo()->query(
            'SELECT balance_sheets.*, users.name AS created_by_name
             FROM balance_sheets
             LEFT JOIN users ON users.id = balance_sheets.created_by
             ORDER BY fiscal_year DESC, id DESC'
        )->fetchAll();

        $this->render('balance-sheets.index', [
            'title' => '資產負債表',
            'section' => '主管機關核備',
            'active' => 'balance-sheets',
            'sheets' => $sheets,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('balance_sheets.manage');
        $year = (int) date('Y');

        $this->render('balance-sheets.form', [
            'title' => '新增資產負債表',
            'section' => '主管機關核備',
            'active' => 'balance-sheets',
            'sheet' => [
                'fiscal_year' => $year,
                'title' => roc_year($year) . '年度資產負債表',
                'status' => 'draft',
                'notes' => '',
            ],
            'items' => $this->defaultItems(),
            'action' => '/balance-sheets',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('balance_sheets.manage');
        $this->validateSheet('/balance-sheets/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO balance_sheets (fiscal_year, title, status, notes, created_by, created_at, updated_at)
                 VALUES (:fiscal_year, :title, :status, :notes, :created_by, :created_at, :updated_at)'
            )->execute($this->payload() + [
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id = (int) Database::pdo()->lastInsertId();
            $this->replaceItems($id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/balance-sheets/create', $_POST, '資產負債表儲存失敗，請確認年度是否重複：' . $e->getMessage());
        }

        AuditLog::write('create', 'balance_sheets', 'balance_sheets', $id);
        flash('success', '資產負債表已建立。');
        redirect('/balance-sheets/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('balance_sheets.view');
        $sheet = $this->findSheet((int) $id);
        $items = $this->items((int) $id);

        $this->render('balance-sheets.show', [
            'title' => $sheet['title'],
            'section' => '主管機關核備',
            'active' => 'balance-sheets',
            'sheet' => $sheet,
            'items' => $items,
            'totals' => BalanceSheetSummary::totals($items),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('balance_sheets.manage');
        $sheet = $this->findSheet((int) $id);

        $this->render('balance-sheets.form', [
            'title' => '編輯資產負債表',
            'section' => '主管機關核備',
            'active' => 'balance-sheets',
            'sheet' => $sheet,
            'items' => $this->items((int) $id) ?: $this->defaultItems(),
            'action' => '/balance-sheets/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('balance_sheets.manage');
        $sheet = $this->findSheet((int) $id);
        $this->validateSheet('/balance-sheets/' . $id . '/edit');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE balance_sheets
                 SET fiscal_year = :fiscal_year, title = :title, status = :status, notes = :notes, updated_at = :updated_at
                 WHERE id = :id'
            )->execute($this->payload() + [
                'updated_at' => now(),
                'id' => (int) $sheet['id'],
            ]);

            $this->replaceItems((int) $id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/balance-sheets/' . $id . '/edit', $_POST, '資產負債表更新失敗：' . $e->getMessage());
        }

        AuditLog::write('update', 'balance_sheets', 'balance_sheets', (int) $id);
        flash('success', '資產負債表已更新。');
        redirect('/balance-sheets/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('balance_sheets.manage');
        $sheet = $this->findSheet((int) $id);

        Database::pdo()->prepare('DELETE FROM balance_sheets WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'balance_sheets', 'balance_sheets', (int) $id, [
            'title' => $sheet['title'],
        ]);
        flash('success', '資產負債表已刪除。');
        redirect('/balance-sheets');
    }

    private function validateSheet(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'fiscal_year' => '民國年度',
            'title' => '表冊名稱',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $year = normalize_fiscal_year($_POST['fiscal_year']);
        if ($year < 1912 || $year > 2100) {
            $this->backWithInput($path, $_POST, '年度格式不正確。');
        }
    }

    private function payload(): array
    {
        return [
            'fiscal_year' => normalize_fiscal_year($_POST['fiscal_year']),
            'title' => trim((string) $_POST['title']),
            'status' => in_array($_POST['status'] ?? '', ['draft', 'confirmed'], true) ? (string) $_POST['status'] : 'draft',
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function replaceItems(int $sheetId, array $items): void
    {
        Database::pdo()->prepare('DELETE FROM balance_sheet_items WHERE balance_sheet_id = :id')
            ->execute(['id' => $sheetId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO balance_sheet_items
             (balance_sheet_id, section, item_name, current_amount, prior_amount, sort_order, created_at, updated_at)
             VALUES (:balance_sheet_id, :section, :item_name, :current_amount, :prior_amount, :sort_order, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $section = in_array($item['section'] ?? '', ['asset', 'liability', 'equity'], true) ? (string) $item['section'] : 'asset';
            $stmt->execute([
                'balance_sheet_id' => $sheetId,
                'section' => $section,
                'item_name' => $name,
                'current_amount' => round((float) ($item['current_amount'] ?? 0), 2),
                'prior_amount' => round((float) ($item['prior_amount'] ?? 0), 2),
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findSheet(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM balance_sheets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $sheet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$sheet) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到資產負債表']);
            exit;
        }

        return $sheet;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(int $sheetId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM balance_sheet_items WHERE balance_sheet_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $sheetId]);

        return $stmt->fetchAll();
    }

    /**
     * 依教育局範例預帶常見科目(金額留空)。
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultItems(): array
    {
        $blank = ['current_amount' => '', 'prior_amount' => ''];

        return [
            ['section' => 'asset', 'item_name' => '現金及約當現金'] + $blank,
            ['section' => 'asset', 'item_name' => '透過其他綜合損益按公允價值衡量之金融資產'] + $blank,
            ['section' => 'asset', 'item_name' => '其他應收款'] + $blank,
            ['section' => 'asset', 'item_name' => '基金存款'] + $blank,
            ['section' => 'asset', 'item_name' => '不動產、廠房及設備淨額'] + $blank,
            ['section' => 'liability', 'item_name' => '應付費用'] + $blank,
            ['section' => 'liability', 'item_name' => '其他流動負債'] + $blank,
            ['section' => 'equity', 'item_name' => '設立基金'] + $blank,
            ['section' => 'equity', 'item_name' => '其他基金'] + $blank,
            ['section' => 'equity', 'item_name' => '累積賸餘(短絀)'] + $blank,
            ['section' => 'equity', 'item_name' => '淨值其他項目'] + $blank,
        ];
    }
}
