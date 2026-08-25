<?php

namespace App\Modules\NetAssetStatements\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Validator;
use App\Domain\NetAssetStatements\NetAssetSummary;
use PDO;

/**
 * 淨值變動表(獨立手動輸入,暫不連結實際帳務):矩陣結構,
 * 欄為淨值組成(設立基金、其他基金、公積、累積賸餘、淨值其他項目),合計為列總和;
 * 列為各期異動與餘額。供陳報主管機關(教育局)核備。
 */
final class NetAssetStatementController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('net_asset_statements.view');

        $statements = Database::pdo()->query(
            'SELECT net_asset_statements.*, users.name AS created_by_name
             FROM net_asset_statements
             LEFT JOIN users ON users.id = net_asset_statements.created_by
             ORDER BY fiscal_year DESC, id DESC'
        )->fetchAll();

        $this->render('net-asset-statements.index', [
            'title' => '淨值變動表',
            'section' => '主管機關核備',
            'active' => 'net-asset-statements',
            'statements' => $statements,
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('net_asset_statements.manage');
        $year = (int) date('Y');

        $this->render('net-asset-statements.form', [
            'title' => '新增淨值變動表',
            'section' => '主管機關核備',
            'active' => 'net-asset-statements',
            'statement' => [
                'fiscal_year' => $year,
                'title' => roc_year($year) . '年度淨值變動表',
                'status' => 'draft',
                'notes' => '',
            ],
            'rows' => $this->defaultRows($year),
            'action' => '/net-asset-statements',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('net_asset_statements.manage');
        $this->validateStatement('/net-asset-statements/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO net_asset_statements (fiscal_year, title, status, notes, created_by, created_at, updated_at)
                 VALUES (:fiscal_year, :title, :status, :notes, :created_by, :created_at, :updated_at)'
            )->execute($this->payload() + [
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $id = (int) Database::pdo()->lastInsertId();
            $this->replaceRows($id, $_POST['rows'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/net-asset-statements/create', $_POST, '淨值變動表儲存失敗，請確認年度是否重複：' . $e->getMessage());
        }

        AuditLog::write('create', 'net_asset_statements', 'net_asset_statements', $id);
        flash('success', '淨值變動表已建立。');
        redirect('/net-asset-statements/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('net_asset_statements.view');
        $statement = $this->findStatement((int) $id);
        $rows = $this->rows((int) $id);

        $this->render('net-asset-statements.show', [
            'title' => $statement['title'],
            'section' => '主管機關核備',
            'active' => 'net-asset-statements',
            'statement' => $statement,
            'rows' => $rows,
            'columnTotals' => NetAssetSummary::columnTotals($rows),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('net_asset_statements.manage');
        $statement = $this->findStatement((int) $id);

        $this->render('net-asset-statements.form', [
            'title' => '編輯淨值變動表',
            'section' => '主管機關核備',
            'active' => 'net-asset-statements',
            'statement' => $statement,
            'rows' => $this->rows((int) $id) ?: $this->defaultRows((int) $statement['fiscal_year']),
            'action' => '/net-asset-statements/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('net_asset_statements.manage');
        $statement = $this->findStatement((int) $id);
        $this->validateStatement('/net-asset-statements/' . $id . '/edit');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE net_asset_statements
                 SET fiscal_year = :fiscal_year, title = :title, status = :status, notes = :notes, updated_at = :updated_at
                 WHERE id = :id'
            )->execute($this->payload() + [
                'updated_at' => now(),
                'id' => (int) $statement['id'],
            ]);

            $this->replaceRows((int) $id, $_POST['rows'] ?? []);
            Database::pdo()->commit();
        } catch (\Throwable $e) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/net-asset-statements/' . $id . '/edit', $_POST, '淨值變動表更新失敗：' . $e->getMessage());
        }

        AuditLog::write('update', 'net_asset_statements', 'net_asset_statements', (int) $id);
        flash('success', '淨值變動表已更新。');
        redirect('/net-asset-statements/' . $id);
    }

    public function destroy(string $id): void
    {
        $this->requirePermission('net_asset_statements.manage');
        $statement = $this->findStatement((int) $id);

        Database::pdo()->prepare('DELETE FROM net_asset_statements WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'net_asset_statements', 'net_asset_statements', (int) $id, [
            'title' => $statement['title'],
        ]);
        flash('success', '淨值變動表已刪除。');
        redirect('/net-asset-statements');
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

    private function replaceRows(int $statementId, array $rows): void
    {
        Database::pdo()->prepare('DELETE FROM net_asset_statement_rows WHERE net_asset_statement_id = :id')
            ->execute(['id' => $statementId]);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO net_asset_statement_rows
             (net_asset_statement_id, row_label, founding_fund, other_fund, capital_reserve, accumulated_surplus, other_equity, sort_order, created_at, updated_at)
             VALUES (:net_asset_statement_id, :row_label, :founding_fund, :other_fund, :capital_reserve, :accumulated_surplus, :other_equity, :sort_order, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($rows as $row) {
            $label = trim((string) ($row['row_label'] ?? ''));
            if ($label === '') {
                continue;
            }
            $stmt->execute([
                'net_asset_statement_id' => $statementId,
                'row_label' => $label,
                'founding_fund' => round((float) ($row['founding_fund'] ?? 0), 2),
                'other_fund' => round((float) ($row['other_fund'] ?? 0), 2),
                'capital_reserve' => round((float) ($row['capital_reserve'] ?? 0), 2),
                'accumulated_surplus' => round((float) ($row['accumulated_surplus'] ?? 0), 2),
                'other_equity' => round((float) ($row['other_equity'] ?? 0), 2),
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findStatement(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM net_asset_statements WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $statement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$statement) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到淨值變動表']);
            exit;
        }

        return $statement;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(int $statementId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM net_asset_statement_rows WHERE net_asset_statement_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute(['id' => $statementId]);

        return $stmt->fetchAll();
    }

    /**
     * 依教育局範例預帶標準列(本年及上年之期初/賸餘/未實現/綜合/期末),金額留空。
     *
     * @return array<int, array<string, mixed>>
     */
    private function defaultRows(int $fiscalYear): array
    {
        $current = roc_year($fiscalYear);
        $prior = $current - 1;
        $blank = ['founding_fund' => '', 'other_fund' => '', 'capital_reserve' => '', 'accumulated_surplus' => '', 'other_equity' => ''];

        $labels = [
            $prior . '年1月1日餘額',
            $prior . '年度稅後賸餘(短絀)',
            $prior . '年度金融資產未實現餘絀',
            $prior . '年12月31日綜合餘絀總額',
            $prior . '年12月31日餘額',
            $current . '年度稅後賸餘(短絀)',
            $current . '年度金融資產未實現餘絀',
            $current . '年12月31日綜合餘絀總額',
            $current . '年12月31日餘額',
        ];

        return array_map(static fn (string $label): array => ['row_label' => $label] + $blank, $labels);
    }
}
