<?php
$active = 'annual-budgets';
$documentTitle = '預算經費表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card">
        <span>收入合計</span>
        <strong><?= e(number_format((float) $totals['income'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>支出合計</span>
        <strong><?= e(number_format((float) $totals['expense'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>收支餘絀</span>
        <strong><?= e(number_format((float) $totals['balance'], 0)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($budget['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($budget['fiscal_year'])) ?>，狀態：<?= e(annual_budget_status_label($budget['status'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/annual-budgets">返回列表</a>
            <a class="btn primary" href="/annual-budgets/<?= e((string) $budget['id']) ?>/execution">預算執行表</a>
            <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
                <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('annual_budgets.approve') && $budget['status'] !== 'approved'): ?>
                <form method="post" action="/annual-budgets/<?= e((string) $budget['id']) ?>/approve">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">核定</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>預算名稱</th>
            <td colspan="3"><?= e($budget['title']) ?></td>
        </tr>
        <tr>
            <th>年度</th>
            <td><?= e(roc_year_label($budget['fiscal_year'])) ?></td>
            <th>類型</th>
            <td><?= e(annual_budget_type_label($budget['budget_type'] ?? 'annual')) ?></td>
        </tr>
        <tr>
            <th>期間</th>
            <td><?= e(roc_date_range($budget['period_start'] ?? null, $budget['period_end'] ?? null)) ?></td>
            <th>核定會議 / 文號</th>
            <td><?= e($budget['board_meeting_no'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>計畫目的</th>
            <td colspan="3"><?= nl2br(e($budget['purpose'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>辦理依據</th>
            <td colspan="3"><?= nl2br(e($budget['legal_basis'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>預期效益</th>
            <td colspan="3"><?= nl2br(e($budget['expected_benefit'] ?: '-')) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>科目</th>
                <th>項目</th>
                <th>用途說明</th>
                <th>單位</th>
                <th class="amount">數量</th>
                <th class="amount">單價</th>
                <th class="amount">金額</th>
                <th>經費來源</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['item_type'] === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td><?= e($item['item_name']) ?></td>
                    <td><?= e($item['description'] ?? '') ?></td>
                    <td><?= e($item['unit'] ?? '') ?></td>
                    <td class="amount"><?= e(number_format((float) ($item['quantity'] ?? 0), 2)) ?></td>
                    <td class="amount"><?= e(number_format((float) ($item['unit_price'] ?? 0), 0)) ?></td>
                    <td class="amount"><?= e(number_format((float) $item['amount'], 0)) ?></td>
                    <td><?= e($item['funding_source'] ?? '') ?></td>
                    <td><?= e($item['notes'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="10" class="empty">尚無預算經費明細。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="7">收入合計</th>
                <th class="amount"><?= e(number_format((float) $totals['income'], 0)) ?></th>
                <th colspan="2"></th>
            </tr>
            <tr>
                <th colspan="7">支出合計</th>
                <th class="amount"><?= e(number_format((float) $totals['expense'], 0)) ?></th>
                <th colspan="2"></th>
            </tr>
            <tr>
                <th colspan="7">收支餘絀</th>
                <th class="amount"><?= e(number_format((float) $totals['balance'], 0)) ?></th>
                <th colspan="2"></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($budget['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($budget['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function annual_budget_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定'][$status] ?? $status;
}
function annual_budget_type_label(string $type): string
{
    return ['annual' => '年度預算', 'project' => '專案預算', 'grant' => '補助計畫'][$type] ?? $type;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
