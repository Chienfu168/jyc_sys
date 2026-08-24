<?php

namespace App\Modules\OperatingStatements\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\OperatingStatements\OperatingStatementSummary;
use PDO;

/**
 * 收支營運表(獨立手動輸入,暫不連結實際收支):
 * 逐列輸入上年度決算數、本年度決算數、本年度預算數,系統自動計算比較增減與比率,
 * 供陳報主管機關(教育局)核備。因有預支/跨年費用情形,故採手動輸入。
 */
final class OperatingStatementController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('operating_statements.view');

        $statements = Database::pdo()->query(
            'SELECT operating_statements.*, users.name AS created_by_name
             FROM operating_statements
             LEFT JOIN users ON users.id = operating_statements.created_by
             ORDER BY fiscal_year DESC, id DESC'
        )->fetchAll();

        $this->render('operating-statements.index', [
            'title' => '收支營運表',
            'section' => '主管機關核備',
            'active' => 'operating-statements',
            'statements' => $statements,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('operating_statements.manage');
        $year = (int) date('Y');

        $this->render('operating-statements.form', [
            'title' => '新增收支營運表',
            'section' => '主管機關核備',
            'active' => 'operating-statements',
            'statement' => [
                'fiscal_year' => $year,
                'title' => roc_year($year) . '年度收支營運表',
                'status' => 'draft',
                'notes' => '',
            ],
            'items' => $this->defaultItems(),
            'action' => '/operating-statements',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('operating_statements.manage');
        $this->validateStatement('/operating-statements/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO operating_statements (fiscal_year, title, status, notes, created_by, created_at, updated_at)
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
            $this->backWithInput('/operating-statements/create', $_POST, '收支營運表儲存失敗，請確認年度是否重複：' . $e->getMessage());
        }

        AuditLog::write('create', 'operating_statements', 'operating_statements', $id);
        flash('success', '收支營運表已建立。');
        redirect('/operating-statements/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('operating_statements.view');
        $statement = $this->findStatement((int) $id);
        $items = $this->items((int) $id);

        $this->render('operating-statements.show', [
            'title' => $statement['title'],
            'section' => '主管機關核備',
            'active' => 'operating-statements',
            'statement' => $statement,
            'items' => $items,
            'totals' => OperatingStatementSummary::totals($items),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('operating_statements.manage');
        $statement = $this->findStatement((int) $id);

        $this->render('operating-statements.form', [
            'title' => '編輯收支營運表',
            'section' => '主管機關核備',
            'active' => 'operating-statements',
            'statement' => $statement,
            'items' => $this->items((int) $id) ?: $this->defaultItems(),
            'action' => '/operating-statements/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('operating_statements.manage');
        $statement = $this->findStatement((int) $id);
        $this->validateStatement('/operating-statements/' . $id . '/edit');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE operating_statements
                 SET fiscal_year = :fiscal_year, title = :title, status = :status, notes = :notes, updated_at = :updated_at
                 WHERE id = :id'
            )->execute($this->payload() + [
                'updated_at' => now(),
                'id' => (int) $statement['id'],
            ]);

            $this->replaceItems((int) $id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/operating-statements/' . $id . '/edit', $_POST, '收支營運表更新失敗：' . $e->getMessage());
        }

        AuditLog::write('update', 'operating_statements', 'operating_statements', (int) $id);
        flash('success', '收支營運表已更新。');
        redirect('/operating-statements/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('operating_statements.manage');
        $statement = $this->findStatement((int) $id);

        Database::pdo()->prepare('DELETE FROM operating_statements WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'operating_statements', 'operating_statements', (int) $id, [
            'title' => $statement['title'],
        ]);
        flash('success', '收支營運表已刪除。');
        redirect('/operating-statements');
    }

    private function validateStatement(string $path): void
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

    private function replaceItems(int $statementId, array $items): void
    {
        Database::pdo()->prepare('DELETE FROM operating_statement_items WHERE operating_statement_id = :id')
            ->execute(['id' => $statementId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO operating_statement_items
             (operating_statement_id, section, item_name, prior_amount, current_amount, budget_amount, sort_order, created_at, updated_at)
             VALUES (:operating_statement_id, :section, :item_name, :prior_amount, :current_amount, :budget_amount, :sort_order, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $section = in_array($item['section'] ?? '', ['income', 'expense', 'tax'], true) ? (string) $item['section'] : 'income';
            $stmt->execute([
                'operating_statement_id' => $statementId,
                'section' => $section,
                'item_name' => $name,
                'prior_amount' => round((float) ($item['prior_amount'] ?? 0), 2),
                'current_amount' => round((float) ($item['current_amount'] ?? 0), 2),
                'budget_amount' => round((float) ($item['budget_amount'] ?? 0), 2),
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findStatement(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM operating_statements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $statement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$statement) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到收支營運表']);
            exit;
        }

        return $statement;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(int $statementId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM operating_statement_items WHERE operating_statement_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $statementId]);

        return $stmt->fetchAll();
    }

    /**
     * 依教育局範例預帶常見科目(金額留空,費損與稅務以負數輸入)。
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultItems(): array
    {
        $blank = ['prior_amount' => '', 'current_amount' => '', 'budget_amount' => ''];

        return [
            ['section' => 'income', 'item_name' => '受贈收入'] + $blank,
            ['section' => 'income', 'item_name' => '附屬作業組織收入'] + $blank,
            ['section' => 'income', 'item_name' => '財務收入'] + $blank,
            ['section' => 'income', 'item_name' => '其他業務外收入'] + $blank,
            ['section' => 'expense', 'item_name' => '活動費用'] + $blank,
            ['section' => 'expense', 'item_name' => '獎助或捐贈費用'] + $blank,
            ['section' => 'expense', 'item_name' => '附屬作業組織支出'] + $blank,
            ['section' => 'expense', 'item_name' => '管理費用'] + $blank,
            ['section' => 'expense', 'item_name' => '財務費用'] + $blank,
            ['section' => 'tax', 'item_name' => '所得稅利益(費用)'] + $blank,
        ];
    }
}
