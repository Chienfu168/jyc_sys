<?php

namespace App\Modules\AnnualBudgets\Controllers;

use App\Core\AuditLog;
use App\Core\ApprovalFlow;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Permission;
use App\Core\Validator;
use App\Domain\AnnualBudgets\BudgetSummary;
use PDO;

final class AnnualBudgetController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('annual_budgets.view');

        $stmt = Database::pdo()->query(
            'SELECT annual_budgets.*,
                    users.name AS created_by_name,
                    COALESCE(budget_totals.income_total, 0) AS income_total,
                    COALESCE(budget_totals.expense_total, 0) AS expense_total
             FROM annual_budgets
             LEFT JOIN users ON users.id = annual_budgets.created_by
             LEFT JOIN (
                SELECT annual_budget_id,
                       SUM(CASE WHEN item_type = "income" THEN amount ELSE 0 END) AS income_total,
                       SUM(CASE WHEN item_type = "expense" THEN amount ELSE 0 END) AS expense_total
                FROM annual_budget_items
                GROUP BY annual_budget_id
             ) AS budget_totals ON budget_totals.annual_budget_id = annual_budgets.id
             ORDER BY annual_budgets.fiscal_year DESC, annual_budgets.id DESC'
        );

        $this->render('annual-budgets.index', [
            'title' => '年度預算',
            'section' => '主管機關核備',
            'active' => 'annual-budgets',
            'budgets' => $stmt->fetchAll(),
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('annual_budgets.manage');

        // 由清單「以 115 範本新增」進入時,直接於伺服器端帶入完整 115 年度範本(款/項/目/次/節與金額)。
        $useTemplate = (string) ($_GET['template'] ?? '') === '115';
        $year = $useTemplate ? 2026 : ((int) date('Y') + 1); // 民國115年 = 西元2026年

        $this->render('annual-budgets.create', [
            'title' => $useTemplate ? '以 115 年度範本新增預算' : '新增年度預算',
            'section' => '主管機關核備',
            'active' => 'annual-budgets',
            'budget' => [
                'fiscal_year' => $year,
                'budget_type' => 'annual',
                'title' => $useTemplate ? '115年度經費預算表' : roc_year($year) . '年度經費預算表',
                'period_start' => $year . '-01-01',
                'period_end' => $year . '-12-31',
                'status' => 'draft',
                'notes' => '',
                'purpose' => '依基金會年度工作計畫編列收入及支出預算，作為董事會審議、主管機關核備及年度執行控管依據。',
                'legal_basis' => '依財團法人法、主管機關相關規定、本會捐助章程及會計制度辦理。',
                'expected_benefit' => '建立年度經費規劃、執行追蹤與決算比較基礎，提升非營利組織治理透明度。',
                'board_meeting_no' => '',
            ],
            'items' => $useTemplate ? $this->budgetTemplate115() : $this->defaultItems(),
            'govHierarchy' => $this->govHierarchy(),
            'budgetTemplate' => $this->budgetTemplate115(),
            'templateApplied' => $useTemplate,
            'action' => '/annual-budgets',
        ]);
    }

    /**
     * 以既有年度預算為範本開新的預算表單並帶入原資料(款/項/目/次/節與金額),
     * 供「以舊年度為基礎、修改後另存新年度」。年度預設為原年度＋1(需與既有年度不同),
     * 僅預填不建立資料;送出後才由 store() 建立新的年度預算。
     */
    public function duplicate(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $source = $this->findBudget((int) $id);
        $items = $this->items((int) $id);

        $sourceYear = (int) $source['fiscal_year'];
        $nextYear = $sourceYear + 1;
        $source['fiscal_year'] = $nextYear;
        $source['status'] = 'draft';
        $source['title'] = roc_year($nextYear) . '年度經費預算表';
        unset($source['id']);

        $this->render('annual-budgets.create', [
            'title' => '複製年度預算',
            'section' => '主管機關核備',
            'active' => 'annual-budgets',
            'budget' => $source,
            'items' => $items,
            'govHierarchy' => $this->govHierarchy(),
            'budgetTemplate' => $this->budgetTemplate115(),
            'duplicateFromYear' => $sourceYear,
            'action' => '/annual-budgets',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('annual_budgets.manage');
        $this->validateBudget('/annual-budgets/create');

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'INSERT INTO annual_budgets
                 (fiscal_year, budget_type, title, period_start, period_end, status, notes, purpose, legal_basis, expected_benefit, board_meeting_no, created_by, created_at, updated_at)
                 VALUES
                 (:fiscal_year, :budget_type, :title, :period_start, :period_end, :status, :notes, :purpose, :legal_basis, :expected_benefit, :board_meeting_no, :created_by, :created_at, :updated_at)'
            )->execute($this->budgetPayload() + [
                'created_by' => auth()->user()['id'] ?? null,
                'created_at' => now(),
            ]);

            $id = (int) Database::pdo()->lastInsertId();
            $this->replaceItems($id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\PDOException) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/annual-budgets/create', $_POST, '年度預算儲存失敗，請確認年度是否重複，或資料庫欄位是否已完成更新。');
        }

        if ($this->statusValue() === 'submitted') {
            ApprovalFlow::submit('annual_budgets', 'annual_budgets', $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('create', 'annual_budgets', 'annual_budgets', $id);
        flash('success', '年度預算已建立。');
        redirect('/annual-budgets/' . $id);
    }

    public function show(string $id): void
    {
        $this->requirePermission('annual_budgets.view');
        $budget = $this->findBudget((int) $id);
        $items = $this->items((int) $id);

        $this->render('annual-budgets.show', [
            'title' => $budget['title'],
            'section' => '主管機關核備',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $items,
            'totals' => $this->totals($items),
            'approvalHistory' => ApprovalFlow::history('annual_budgets', 'annual_budgets', (int) $id),
            'profile' => foundation_profile(),
        ]);
    }

    public function statement(string $id): void
    {
        $this->requirePermission('annual_budgets.view');
        $budget = $this->findBudget((int) $id);
        $items = $this->items((int) $id);

        $this->render('annual-budgets.statement', [
            'title' => '經費預算表',
            'section' => '主管機關核備',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $items,
            'statement' => $this->statementSummary($items),
            'profile' => foundation_profile(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved' && !Permission::can('annual_budgets.approve')) {
            flash('error', '已核定預算僅具核定權限者可調整。');
            redirect('/annual-budgets/' . $id);
        }

        $this->render('annual-budgets.edit', [
            'title' => '編輯年度預算',
            'section' => '主管機關核備',
            'active' => 'annual-budgets',
            'budget' => $budget,
            'items' => $this->items((int) $id),
            'govHierarchy' => $this->govHierarchy(),
            'budgetTemplate' => $this->budgetTemplate115(),
            'action' => '/annual-budgets/' . $id,
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved' && !Permission::can('annual_budgets.approve')) {
            flash('error', '已核定預算僅具核定權限者可調整。');
            redirect('/annual-budgets/' . $id);
        }

        $this->validateBudget('/annual-budgets/' . $id . '/edit');
        $status = $this->statusValue();

        try {
            Database::pdo()->beginTransaction();
            Database::pdo()->prepare(
                'UPDATE annual_budgets
                 SET fiscal_year = :fiscal_year,
                     budget_type = :budget_type,
                     title = :title,
                     period_start = :period_start,
                     period_end = :period_end,
                     status = :status,
                     notes = :notes,
                     purpose = :purpose,
                     legal_basis = :legal_basis,
                     expected_benefit = :expected_benefit,
                     board_meeting_no = :board_meeting_no,
                     updated_at = :updated_at
                 WHERE id = :id'
            )->execute($this->budgetPayload() + ['id' => (int) $id]);

            $this->replaceItems((int) $id, $_POST['items'] ?? []);
            Database::pdo()->commit();
        } catch (\PDOException) {
            if (Database::pdo()->inTransaction()) {
                Database::pdo()->rollBack();
            }
            $this->backWithInput('/annual-budgets/' . $id . '/edit', $_POST, '年度預算更新失敗，請確認資料格式或資料庫欄位是否已完成更新。');
        }

        if ($status === 'submitted' && $budget['status'] !== 'submitted') {
            ApprovalFlow::submit('annual_budgets', 'annual_budgets', (int) $id, trim((string) ($_POST['notes'] ?? '')));
        }
        AuditLog::write('update', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已更新。');
        redirect('/annual-budgets/' . $id);
    }

    public function submit(string $id): void
    {
        $this->requirePermission('annual_budgets.manage');
        $budget = $this->findBudget((int) $id);

        if ($budget['status'] === 'approved') {
            flash('error', '已核定年度預算不可送審。');
            redirect('/annual-budgets/' . $id);
        }

        ApprovalFlow::submit('annual_budgets', 'annual_budgets', (int) $budget['id'], trim((string) ($_POST['request_notes'] ?? '')));
        Database::pdo()->prepare(
            'UPDATE annual_budgets
             SET status = "submitted", updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $budget['id'],
        ]);

        AuditLog::write('submit', 'annual_budgets', 'annual_budgets', (int) $budget['id']);
        flash('success', '年度預算已送出簽核。');
        redirect('/annual-budgets/' . $id);
    }

    public function approve(string $id): void
    {
        $this->requirePermission('annual_budgets.approve');
        ApprovalFlow::review('annual_budgets', 'annual_budgets', (int) $id, 'approved', trim((string) ($_POST['review_notes'] ?? '')));

        Database::pdo()->prepare(
            'UPDATE annual_budgets
             SET status = "approved", approved_by = :approved_by, approved_at = :approved_at, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'approved_by' => auth()->user()['id'] ?? null,
            'approved_at' => now(),
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('approve', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已核定。');
        redirect('/annual-budgets/' . $id);
    }

    public function reject(string $id): void
    {
        $this->requirePermission('annual_budgets.approve');
        ApprovalFlow::review('annual_budgets', 'annual_budgets', (int) $id, 'rejected', trim((string) ($_POST['review_notes'] ?? '')));

        Database::pdo()->prepare(
            'UPDATE annual_budgets
             SET status = "draft", approved_by = NULL, approved_at = NULL, updated_at = :updated_at
             WHERE id = :id'
        )->execute([
            'updated_at' => now(),
            'id' => (int) $id,
        ]);

        AuditLog::write('reject', 'annual_budgets', 'annual_budgets', (int) $id);
        flash('success', '年度預算已退回。');
        redirect('/annual-budgets/' . $id);
    }

    public function destroy(string $id): void
    {
        $budget = $this->findBudget((int) $id);
        $this->requireManageOrOwner('annual_budgets.manage', $budget['created_by'] ?? null);

        Database::pdo()->prepare('DELETE FROM annual_budgets WHERE id = :id')->execute(['id' => (int) $id]);

        AuditLog::write('delete', 'annual_budgets', 'annual_budgets', (int) $id, ['title' => $budget['title'] ?? null]);
        flash('success', '年度預算已刪除。');
        redirect('/annual-budgets');
    }

    private function validateBudget(string $path): void
    {
        if ($error = Validator::required($_POST, [
            'fiscal_year' => '年度',
            'title' => '預算名稱',
        ])) {
            $this->backWithInput($path, $_POST, $error);
        }

        $year = normalize_fiscal_year($_POST['fiscal_year']);
        if ($year < 1912 || $year > 2100) {
            $this->backWithInput($path, $_POST, '年度格式不正確。');
        }
    }

    private function budgetPayload(): array
    {
        return [
            'fiscal_year' => normalize_fiscal_year($_POST['fiscal_year']),
            'budget_type' => $this->budgetTypeValue(),
            'title' => trim((string) $_POST['title']),
            'period_start' => $this->dateOrNull('period_start'),
            'period_end' => $this->dateOrNull('period_end'),
            'status' => $this->statusValue(),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'purpose' => trim((string) ($_POST['purpose'] ?? '')),
            'legal_basis' => trim((string) ($_POST['legal_basis'] ?? '')),
            'expected_benefit' => trim((string) ($_POST['expected_benefit'] ?? '')),
            'board_meeting_no' => trim((string) ($_POST['board_meeting_no'] ?? '')),
            'updated_at' => now(),
        ];
    }

    private function statusValue(): string
    {
        $status = (string) ($_POST['status'] ?? 'draft');
        if ($status === 'approved' && Permission::can('annual_budgets.approve')) {
            return 'approved';
        }

        return in_array($status, ['draft', 'submitted'], true) ? $status : 'draft';
    }

    private function replaceItems(int $budgetId, array $items): void
    {
        Database::pdo()->prepare('DELETE FROM annual_budget_items WHERE annual_budget_id = :annual_budget_id')
            ->execute(['annual_budget_id' => $budgetId]);

        // 小計／合計列一律以其對應明細自動加總,避免手動維護造成誤差(即使前端未計算亦以此為準)。
        $items = BudgetSummary::applySubtotals(array_values($items));

        $stmt = Database::pdo()->prepare(
            'INSERT INTO annual_budget_items
             (annual_budget_id, item_type, gov_level1, gov_level2, gov_level3, gov_level4, gov_level5, category, item_name, description, unit, quantity, unit_price, amount, previous_amount, comparison_note, is_subtotal, funding_source, sort_order, notes, created_at, updated_at)
             VALUES (:annual_budget_id, :item_type, :gov_level1, :gov_level2, :gov_level3, :gov_level4, :gov_level5, :category, :item_name, :description, :unit, :quantity, :unit_price, :amount, :previous_amount, :comparison_note, :is_subtotal, :funding_source, :sort_order, :notes, :created_at, :updated_at)'
        );

        $sort = 1;
        foreach ($items as $item) {
            $name = trim((string) ($item['item_name'] ?? ''));
            $category = trim((string) ($item['category'] ?? ''));
            $quantity = max(0, round((float) ($item['quantity'] ?? 1), 2));
            $unitPrice = max(0, round((float) ($item['unit_price'] ?? 0), 2));
            $amount = round((float) ($item['amount'] ?? 0), 2);
            if ($amount <= 0 && $quantity > 0 && $unitPrice > 0) {
                $amount = $quantity * $unitPrice;
            }

            $isSubtotal = !empty($item['is_subtotal']);
            $hasHierarchy = trim(implode('', [
                (string) ($item['gov_level1'] ?? ''),
                (string) ($item['gov_level2'] ?? ''),
                (string) ($item['gov_level3'] ?? ''),
                (string) ($item['gov_level4'] ?? ''),
                (string) ($item['gov_level5'] ?? ''),
            ])) !== '';
            $hasText = $name !== ''
                || $category !== ''
                || trim((string) ($item['description'] ?? '')) !== ''
                || trim((string) ($item['notes'] ?? '')) !== ''
                || trim((string) ($item['comparison_note'] ?? '')) !== '';

            if (!$hasHierarchy && !$hasText && $amount == 0.0 && !$isSubtotal) {
                continue;
            }

            $stmt->execute([
                'annual_budget_id' => $budgetId,
                'item_type' => ($item['item_type'] ?? '') === 'expense' ? 'expense' : 'income',
                'gov_level1' => $this->shortText($item['gov_level1'] ?? ''),
                'gov_level2' => $this->shortText($item['gov_level2'] ?? ''),
                'gov_level3' => $this->shortText($item['gov_level3'] ?? ''),
                'gov_level4' => $this->shortText($item['gov_level4'] ?? ''),
                'gov_level5' => $this->shortText($item['gov_level5'] ?? ''),
                'category' => $category ?: ($isSubtotal ? '小計' : '未分類'),
                'item_name' => $name ?: ($isSubtotal ? '小計' : '未命名項目'),
                'description' => trim((string) ($item['description'] ?? '')),
                'unit' => trim((string) ($item['unit'] ?? '')),
                'quantity' => $quantity ?: 1,
                'unit_price' => $unitPrice,
                'amount' => max(0, $amount),
                'previous_amount' => max(0, round((float) ($item['previous_amount'] ?? 0), 2)),
                'comparison_note' => trim((string) ($item['comparison_note'] ?? '')),
                'is_subtotal' => $isSubtotal ? 1 : 0,
                'funding_source' => trim((string) ($item['funding_source'] ?? '')),
                'sort_order' => $sort++,
                'notes' => trim((string) ($item['notes'] ?? '')),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function findBudget(int $id): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT annual_budgets.*, users.name AS created_by_name, approvers.name AS approved_by_name
             FROM annual_budgets
             LEFT JOIN users ON users.id = annual_budgets.created_by
             LEFT JOIN users AS approvers ON approvers.id = annual_budgets.approved_by
             WHERE annual_budgets.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$budget) {
            http_response_code(404);
            view('errors.404', ['title' => '找不到年度預算']);
            exit;
        }

        return $budget;
    }

    private function items(int $budgetId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT annual_budget_items.*
             FROM annual_budget_items
             WHERE annual_budget_id = :annual_budget_id
             ORDER BY annual_budget_items.sort_order, annual_budget_items.id'
        );
        $stmt->execute(['annual_budget_id' => $budgetId]);

        return $stmt->fetchAll();
    }

    /**
     * 款／項／目／次／節階層樹(依 收益 income／費損 expense 分開)。
     * 以常用財團法人收支科目為預設種子,並合併本機構既有預算項目實際使用過的組合,
     * 供表單以「半自動選擇」呈現階層;使用者仍可自行輸入未列出的層級。
     *
     * @return array{income: array<string, mixed>, expense: array<string, mixed>}
     */
    private function govHierarchy(): array
    {
        $tree = $this->govDefaults();

        try {
            $rows = Database::pdo()->query(
                'SELECT DISTINCT item_type, gov_level1, gov_level2, gov_level3, gov_level4, gov_level5
                 FROM annual_budget_items
                 WHERE COALESCE(gov_level1, "") <> ""'
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $rows = [];
        }

        foreach ($rows as $row) {
            $type = ($row['item_type'] ?? '') === 'income' ? 'income' : 'expense';
            $path = [];
            foreach (['gov_level1', 'gov_level2', 'gov_level3', 'gov_level4', 'gov_level5'] as $col) {
                $seg = trim((string) ($row[$col] ?? ''));
                if ($seg === '') {
                    break; // 階層不跳層,遇空即止。
                }
                $path[] = $seg;
            }
            if ($path === []) {
                continue;
            }
            $node = &$tree[$type];
            foreach ($path as $seg) {
                if (!isset($node[$seg]) || !is_array($node[$seg])) {
                    $node[$seg] = [];
                }
                $node = &$node[$seg];
            }
            unset($node);
        }

        return $tree;
    }

    /**
     * 常用財團法人收支科目階層種子(收益／費損)。空陣列代表該層為葉節點(可再自行輸入)。
     *
     * @return array{income: array<string, mixed>, expense: array<string, mixed>}
     */
    private function govDefaults(): array
    {
        return [
            'income' => [
                '業務收入' => [
                    '捐贈收入' => [],
                    '政府補助收入' => [],
                    '方案服務收入' => [],
                ],
                '非業務收入' => [
                    '利息收入' => [],
                    '其他收入' => [],
                ],
            ],
            'expense' => [
                '業務費用' => [
                    '業務活動費' => [],
                    '業務推廣費' => [],
                    '會議費' => [],
                    '講師鐘點費' => [],
                    '差旅費' => [],
                ],
                '管理費用' => [
                    '人事費用' => [
                        '薪資' => [],
                        '勞保費' => [],
                        '健保費' => [],
                        '勞退金' => [],
                        '獎金' => [],
                    ],
                    '辦公費用' => [
                        '文具印刷費' => [],
                        '郵電費' => [],
                        '水電費' => [],
                    ],
                    '租金支出' => [],
                    '專業服務費' => [],
                ],
                '其他費用' => [
                    '預備金' => [],
                    '雜項支出' => [],
                ],
            ],
        ];
    }

    /**
     * 115 年度經費預算表範本(依主管機關格式:款／項／目／次／節,含各層小計)。
     * 小計列(is_subtotal)金額為其子項之和,僅供顯示,不再計入加總;葉節點金額相加即為各分類與合計。
     * 供新增預算時一鍵套用,使用者再依實際調整年度與金額。
     *
     * @return array<int, array<string, mixed>>
     */
    private function budgetTemplate115(): array
    {
        // 便捷建列:$lvl 為該列所屬層級欄(1=款 2=項 3=目 4=次 5=節),僅填該欄數字,其餘留白。
        $row = static function (string $type, string $cat, int $lvl, string $no, string $name, float $amt, float $prev, bool $sub = false, string $note = ''): array {
            $r = [
                'item_type' => $type,
                'category' => $cat,
                'gov_level1' => '', 'gov_level2' => '', 'gov_level3' => '', 'gov_level4' => '', 'gov_level5' => '',
                'item_name' => $name,
                'amount' => $amt,
                'previous_amount' => $prev,
                'is_subtotal' => $sub,
                'comparison_note' => $note,
            ];
            $r['gov_level' . $lvl] = $no;
            return $r;
        };

        // is_subtotal(第 8 參數 = true):勾選為「小計 / 合計列」,本年度金額自動加總其子項
        // (下方層級較深之明細),不計入收益／費損合計;上年度金額仍可自行輸入。凡有較深子項
        // 之上層科目(業務活動費用、深耕教育計畫、玩聚倡議活動、學童培力營隊、保險費)即勾選,
        // 其下之葉節點(次／節明細)不勾選,為實際計入合計之金額。
        return [
            // ── 收益(款1)── 項1~5
            $row('income', '收益', 2, '1', '捐贈收入', 9000000, 9000000),
            $row('income', '收益', 2, '2', '投資及財產收益', 0, 0),
            $row('income', '收益', 2, '3', '活動收入', 0, 0),
            $row('income', '收益', 2, '4', '利息收入', 40000, 40000),
            $row('income', '收益', 2, '5', '其他收入', 0, 0),

            // ── 費損(款2)· 項1 業務費 ──
            $row('expense', '業務費', 3, '1', '業務活動費用', 4571000, 4200000, true, '詳見115年度工作計畫'),
            $row('expense', '業務費', 4, '1', '深耕教育計畫', 1341000, 0, true),
            $row('expense', '業務費', 5, '1', '豆腐食農小廚房', 160000, 0),
            $row('expense', '業務費', 5, '2', '兒童表演藝術課程', 441000, 0),
            $row('expense', '業務費', 5, '3', '雙語玩聚學習課程', 300000, 0),
            $row('expense', '業務費', 5, '4', '動畫玩聚養成班', 60000, 0),
            $row('expense', '業務費', 5, '5', '木有新生創意立體拼圖', 80000, 0),
            $row('expense', '業務費', 5, '6', '藝術美學課程', 120000, 0),
            $row('expense', '業務費', 5, '7', '邏輯程式智慧機器人', 180000, 0),
            $row('expense', '業務費', 4, '2', '玩聚倡議活動', 1030000, 0, true),
            $row('expense', '業務費', 5, '1', '倡議玩聚學習', 500000, 0),
            $row('expense', '業務費', 5, '2', '創意立體拼圖', 150000, 0),
            $row('expense', '業務費', 5, '3', '行動藝術館', 300000, 0),
            $row('expense', '業務費', 5, '4', 'STEAM 邏輯思考體驗課程', 80000, 0),
            $row('expense', '業務費', 4, '3', '新北市學童培力暨延伸交流營隊計畫', 1500000, 0, true),
            $row('expense', '業務費', 5, '1', '東海夏令營', 600000, 0),
            $row('expense', '業務費', 5, '2', '新加坡冬令營', 800000, 0),
            $row('expense', '業務費', 5, '3', '國內夏令營', 100000, 0),
            $row('expense', '業務費', 4, '4', '115年度玩聚節', 300000, 0),
            $row('expense', '業務費', 4, '5', '快樂溫度計-偏鄉教育論壇', 200000, 0),
            $row('expense', '業務費', 4, '6', '基金會紀實與刊物出版計畫', 150000, 0),
            $row('expense', '業務費', 4, '7', '安全教育課程', 50000, 0),
            $row('expense', '業務費', 3, '2', '業務推廣費', 500000, 350000),
            $row('expense', '業務費', 3, '3', '會議費', 150000, 150000),
            $row('expense', '業務費', 3, '4', '捐贈（支出）', 100000, 200000),

            // ── 費損(款2)· 項2 人事費用 ──
            $row('expense', '人事費用', 3, '1', '人事薪資', 2268000, 2712000),
            $row('expense', '人事費用', 3, '2', '保險費', 248616, 298628, true),
            $row('expense', '人事費用', 4, '1', '勞保費用', 148128, 176172),
            $row('expense', '人事費用', 4, '2', '健保費用', 88488, 108456),
            $row('expense', '人事費用', 4, '3', '團保意外險', 12000, 14000),
            $row('expense', '人事費用', 3, '3', '勞退金', 114480, 162720),
            $row('expense', '人事費用', 3, '4', '年終獎金', 283500, 324000),

            // ── 費損(款2)· 項3 辦公行政費 ──
            $row('expense', '辦公行政費', 3, '1', '辦公室租金', 300000, 300000),
            $row('expense', '辦公行政費', 3, '2', '文具印刷費', 50000, 54000),
            $row('expense', '辦公行政費', 3, '3', '交通費', 80000, 150000),
            $row('expense', '辦公行政費', 3, '4', '差旅費', 50000, 40000),
            $row('expense', '辦公行政費', 3, '5', '郵電費', 30000, 22000),
            $row('expense', '辦公行政費', 3, '6', '水電費', 30000, 30000),
            $row('expense', '辦公行政費', 3, '7', '專業服務費', 168000, 0),
            $row('expense', '辦公行政費', 3, '8', '雜項支出', 96404, 46652),
        ];
    }

    private function totals(array $items): array
    {
        return BudgetSummary::totals($items);
    }

    private function statementSummary(array $items): array
    {
        $summary = [
            'income' => ['current' => 0.0, 'previous' => 0.0],
            'expense' => ['current' => 0.0, 'previous' => 0.0],
            'groups' => ['income' => [], 'expense' => []],
        ];

        // 依主管機關(新北市教育局)經費預算表格式:增(減)比率% = (C)/(A)*100(以本年度預算數 A 為分母)。
        $rateByCurrent = static fn (float $current, float $previous): float =>
            $current != 0.0 ? round((($current - $previous) / $current) * 100, 2) : 0.0;

        foreach ($items as $item) {
            $type = $item['item_type'] === 'expense' ? 'expense' : 'income';
            $current = (float) $item['amount'];
            $previous = (float) ($item['previous_amount'] ?? 0);

            // 小計／合計列僅供顯示,金額為其子項之和,不再計入區段與分類加總(避免重複計算)。
            $isSubtotal = !empty($item['is_subtotal']);
            if (!$isSubtotal) {
                $summary[$type]['current'] += $current;
                $summary[$type]['previous'] += $previous;
            }

            $groupKey = trim((string) ($item['category'] ?? '')) ?: '未分類';
            if (!isset($summary['groups'][$type][$groupKey])) {
                $summary['groups'][$type][$groupKey] = [
                    'name' => $groupKey,
                    'current' => 0.0,
                    'previous' => 0.0,
                    'items' => [],
                ];
            }

            $item['variance_amount'] = $current - $previous;
            $item['variance_rate'] = $rateByCurrent($current, $previous);
            if (!$isSubtotal) {
                $summary['groups'][$type][$groupKey]['current'] += $current;
                $summary['groups'][$type][$groupKey]['previous'] += $previous;
            }
            $summary['groups'][$type][$groupKey]['items'][] = $item;
        }

        foreach (['income', 'expense'] as $type) {
            $summary[$type]['variance'] = $summary[$type]['current'] - $summary[$type]['previous'];
            $summary[$type]['variance_rate'] = $rateByCurrent($summary[$type]['current'], $summary[$type]['previous']);

            foreach ($summary['groups'][$type] as &$group) {
                $group['variance'] = $group['current'] - $group['previous'];
                $group['variance_rate'] = $rateByCurrent($group['current'], $group['previous']);
            }
            unset($group);
        }

        $summary['balance'] = [
            'current' => $summary['income']['current'] - $summary['expense']['current'],
            'previous' => $summary['income']['previous'] - $summary['expense']['previous'],
        ];
        $summary['balance']['variance'] = $summary['balance']['current'] - $summary['balance']['previous'];
        $summary['balance']['variance_rate'] = $rateByCurrent($summary['balance']['current'], $summary['balance']['previous']);

        return $summary;
    }

    private function defaultItems(): array
    {
        return [
            ['item_type' => 'income', 'gov_level1' => '1', 'category' => '收益', 'item_name' => '捐贈收入', 'quantity' => 1, 'amount' => '', 'previous_amount' => '', 'funding_source' => '民間捐贈', 'notes' => ''],
            ['item_type' => 'income', 'gov_level1' => '1', 'category' => '收益', 'item_name' => '利息收入', 'quantity' => 1, 'amount' => '', 'previous_amount' => '', 'funding_source' => '銀行帳戶', 'notes' => ''],
            ['item_type' => 'expense', 'gov_level1' => '2', 'category' => '業務費', 'item_name' => '業務活動費用', 'unit' => '式', 'quantity' => 1, 'amount' => '', 'previous_amount' => '', 'comparison_note' => '詳見年度工作計畫', 'notes' => ''],
            ['item_type' => 'expense', 'gov_level1' => '2', 'category' => '人事費用', 'item_name' => '人事薪資', 'unit' => '年', 'quantity' => 1, 'amount' => '', 'previous_amount' => '', 'notes' => ''],
            ['item_type' => 'expense', 'gov_level1' => '2', 'category' => '辦公行政費', 'item_name' => '辦公室租金', 'unit' => '年', 'quantity' => 1, 'amount' => '', 'previous_amount' => '', 'notes' => ''],
            ['item_type' => 'expense', 'gov_level1' => '2', 'category' => '預備金', 'item_name' => '預備金', 'unit' => '年', 'quantity' => 1, 'amount' => '', 'previous_amount' => '', 'notes' => ''],
        ];
    }

    private function budgetTypeValue(): string
    {
        $type = (string) ($_POST['budget_type'] ?? 'annual');
        return in_array($type, ['annual', 'project', 'grant'], true) ? $type : 'annual';
    }

    private function dateOrNull(string $key): ?string
    {
        $value = trim((string) ($_POST[$key] ?? ''));
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $id = (int) $value;
        return $id > 0 ? $id : null;
    }

    private function shortText(mixed $value): ?string
    {
        $text = trim((string) $value);
        return $text === '' ? null : mb_substr($text, 0, 20);
    }
}
