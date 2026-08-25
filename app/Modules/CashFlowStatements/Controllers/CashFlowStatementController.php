<?php

namespace App\Modules\CashFlowStatements\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\CashFlowStatements\CashFlowSummary;
use PDO;

/**
 * 現金流量表(獨立手動輸入,暫不連結實際帳務):
 * 逐列輸入業務/投資/籌資活動之現金流量項目,加上匯率變動影響與期初餘額,
 * 系統自動計算各活動淨額、現金增減數與期末餘額,供陳報主管機關(教育局)核備。
 */
final class CashFlowStatementController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('cash_flow_statements.view');

        $statements = Database::pdo()->query(
            'SELECT cash_flow_statements.*, users.name AS created_by_name
             FROM cash_flow_statements
             LEFT JOIN users ON users.id = cash_flow_statements.created_by
             ORDER BY fiscal_year DESC, id DESC'
        )->fetchAll();

        $this->render('cash-flow-statements.index', [
            'title' => '現金流量表',
            'section' => '主管機關核備',
            'active' => 'cash-flow-statements',
            'statements' => $statements,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('cash_flow_statements.manage');
        $year = (int) date('Y');

        $this->render('cash-flow-statements.form', [
            'title' => '新增現金流量表',
            'section' => '主管機關核備',
            'active' => 'cash-flow-statements',
            'statement' => [
                'fiscal_year' => $year,
                'title' => roc_year($year) . '年度現金流量表',
                'status' => 'draft',
                'exchange_current' => '',
                'exchange_prior' => '',
                'opening_current' => '',
                'opening_prior' => '',
                'notes' => '',
            ],
            'items' => $this->defaultItems(),
            'action' => '/cash-flow-statements',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('cash_flow_statements.manage');
        $this->validateStatement('/cash-flow-statements/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO cash_flow_statements
                 (fiscal_year, title, status, exchange_current, exchange_prior, opening_current, opening_prior, notes, created_by, created_at, updated_at)
                 VALUES
                 (:fiscal_year, :title, :status, :exchange_current, :exchange_prior, :opening_current, :opening_prior, :notes, :created_by, :created_at, :updated_at)'
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
            $this->backWithInput('/cash-flow-statements/create', $_POST, '現金流量表儲存失敗，請確認年度是否重複：' . $e->getMessage());
        }

        AuditLog::write('create', 'cash_flow_statements', 'cash_flow_statements', $id);
        flash('success', '現金流量表已建立。');
        redirect('/cash-flow-statements/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('cash_flow_statements.view');
        $statement = $this->findStatement((int) $id);
        $items = $this->items((int) $id);

        $this->render('cash-flow-statements.show', [
            'title' => $statement['title'],
            'section' => '主管機關核備',
            'active' => 'cash-flow-statements',
            'statement' => $statement,
            'items' => $items,
            'totals' => CashFlowSummary::totals(
                $items,
                ['current' => $statement['exchange_current'], 'prior' => $statement['exchange_prior']],
                ['current' => $statement['opening_current'], 'prior' => $statement['opening_prior']]
            ),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('cash_flow_statements.manage');
        $statement = $this->findStatement((int) $id);

        $this->render('cash-flow-statements.form', [
            'title' => '編輯現金流量表',
            'section' => '主管機關核備',
            'active' => 'cash-flow-statements',
            'statement' => $statement,
            'items' => $this->items((int) $id) ?: $this->defaultItems(),
            'action' => '/cash-flow-statements/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('cash_flow_statements.manage');
        $statement = $this->findStatement((int) $id);
        $this->validateStatement('/cash-flow-statements/' . $id . '/edit');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE cash_flow_statements
                 SET fiscal_year = :fiscal_year, title = :title, status = :status,
                     exchange_current = :exchange_current, exchange_prior = :exchange_prior,
                     opening_current = :opening_current, opening_prior = :opening_prior,
                     notes = :notes, updated_at = :updated_at
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
            $this->backWithInput('/cash-flow-statements/' . $id . '/edit', $_POST, '現金流量表更新失敗：' . $e->getMessage());
        }

        AuditLog::write('update', 'cash_flow_statements', 'cash_flow_statements', (int) $id);
        flash('success', '現金流量表已更新。');
        redirect('/cash-flow-statements/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('cash_flow_statements.manage');
        $statement = $this->findStatement((int) $id);

        Database::pdo()->prepare('DELETE FROM cash_flow_statements WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'cash_flow_statements', 'cash_flow_statements', (int) $id, [
            'title' => $statement['title'],
        ]);
        flash('success', '現金流量表已刪除。');
        redirect('/cash-flow-statements');
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
            'exchange_current' => round((float) ($_POST['exchange_current'] ?? 0), 2),
            'exchange_prior' => round((float) ($_POST['exchange_prior'] ?? 0), 2),
            'opening_current' => round((float) ($_POST['opening_current'] ?? 0), 2),
            'opening_prior' => round((float) ($_POST['opening_prior'] ?? 0), 2),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];
    }

    private function replaceItems(int $statementId, array $items): void
    {
        Database::pdo()->prepare('DELETE FROM cash_flow_statement_items WHERE cash_flow_statement_id = :id')
            ->execute(['id' => $statementId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO cash_flow_statement_items
             (cash_flow_statement_id, section, item_name, current_amount, prior_amount, sort_order, created_at, updated_at)
             VALUES (:cash_flow_statement_id, :section, :item_name, :current_amount, :prior_amount, :sort_order, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $section = in_array($item['section'] ?? '', ['operating', 'investing', 'financing'], true) ? (string) $item['section'] : 'operating';
            $stmt->execute([
                'cash_flow_statement_id' => $statementId,
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

    private function findStatement(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM cash_flow_statements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $statement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$statement) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到現金流量表']);
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
            'SELECT * FROM cash_flow_statement_items WHERE cash_flow_statement_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $statementId]);

        return $stmt->fetchAll();
    }

    /**
     * 依教育局範例預帶常見項目(金額留空)。
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultItems(): array
    {
        $blank = ['current_amount' => '', 'prior_amount' => ''];

        return [
            ['section' => 'operating', 'item_name' => '本期稅前賸餘(短絀)'] + $blank,
            ['section' => 'operating', 'item_name' => '折舊費用'] + $blank,
            ['section' => 'operating', 'item_name' => '利息收入'] + $blank,
            ['section' => 'operating', 'item_name' => '股利收入'] + $blank,
            ['section' => 'operating', 'item_name' => '與業務活動相關之資產/負債淨變動數'] + $blank,
            ['section' => 'operating', 'item_name' => '支付所得稅'] + $blank,
            ['section' => 'investing', 'item_name' => '收取股利收入'] + $blank,
            ['section' => 'financing', 'item_name' => ''] + $blank,
        ];
    }
}
