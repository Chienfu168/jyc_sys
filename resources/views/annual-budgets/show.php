<?php
$active = 'annual-budgets';
$documentTitle = '年度預算表';
$canManage = \App\Core\Permission::can('annual_budgets.manage');
$canApprove = \App\Core\Permission::can('annual_budgets.approve');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card"><span>收益合計</span><strong><?= e(number_format((float) $totals['income'], 0)) ?></strong></div>
    <div class="stat-card"><span>費損合計</span><strong><?= e(number_format((float) $totals['expense'], 0)) ?></strong></div>
    <div class="stat-card"><span>賸餘 / 短絀</span><strong><?= e(number_format((float) $totals['balance'], 0)) ?></strong></div>
    <div class="stat-card"><span>上年度賸餘比較</span><strong><?= e(number_format((float) $totals['previous_balance'], 0)) ?></strong></div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">年度預算</p>
            <h2><?= e($budget['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($budget['fiscal_year'])) ?> / <?= e(annual_budget_status_label($budget['status'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/annual-budgets">返回列表</a>
            <a class="btn primary" href="/annual-budgets/<?= e((string) $budget['id']) ?>/statement">經費預算表</a>
            <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>/execution">執行報表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>/edit">編輯</a>
                <?php if ($budget['status'] === 'draft'): ?>
                    <form method="post" action="/annual-budgets/<?= e((string) $budget['id']) ?>/submit">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">送出簽核</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('annual_budgets.delete')): ?>
                <form method="post" action="/annual-budgets/<?= e((string) $budget['id']) ?>/delete" onsubmit="return confirm('確定要刪除此年度預算？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>預算名稱</th><td colspan="3"><?= e($budget['title']) ?></td></tr>
        <tr><th>年度</th><td><?= e(roc_year_label($budget['fiscal_year'])) ?></td><th>類型</th><td><?= e(annual_budget_type_label($budget['budget_type'] ?? 'annual')) ?></td></tr>
        <tr><th>期間</th><td><?= e(roc_date_range($budget['period_start'] ?? null, $budget['period_end'] ?? null)) ?></td><th>董事會/會議</th><td><?= e($budget['board_meeting_no'] ?: '-') ?></td></tr>
        <tr><th>計畫目的</th><td colspan="3"><?= nl2br(e($budget['purpose'] ?: '-')) ?></td></tr>
        <tr><th>法規依據</th><td colspan="3"><?= nl2br(e($budget['legal_basis'] ?: '-')) ?></td></tr>
        <tr><th>預期效益</th><td colspan="3"><?= nl2br(e($budget['expected_benefit'] ?: '-')) ?></td></tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>類型</th>
                <th>款項目次節</th>
                <th>分類</th>
                <th>項目名稱</th>
                <th>說明</th>
                <th>會計科目</th>
                <th class="amount">本年度</th>
                <th class="amount">上年度</th>
                <th class="amount">增減</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $code = implode('-', array_filter([
                    $item['gov_level1'] ?? '',
                    $item['gov_level2'] ?? '',
                    $item['gov_level3'] ?? '',
                    $item['gov_level4'] ?? '',
                    $item['gov_level5'] ?? '',
                ], static fn ($value) => trim((string) $value) !== ''));
                $accountLabel = trim((string) (($item['account_code'] ?? '') . ' ' . ($item['account_name'] ?? '')));
                $variance = (float) $item['amount'] - (float) ($item['previous_amount'] ?? 0);
                ?>
                <tr>
                    <td><?= e($item['item_type'] === 'income' ? '收益' : '費損') ?></td>
                    <td><?= e($code ?: '-') ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td><?= e($item['item_name']) ?></td>
                    <td><?= e($item['description'] ?? '') ?></td>
                    <td><?= e($accountLabel !== '' ? $accountLabel : '未對應') ?></td>
                    <td class="amount"><?= e(number_format((float) $item['amount'], 0)) ?></td>
                    <td class="amount"><?= e(number_format((float) ($item['previous_amount'] ?? 0), 0)) ?></td>
                    <td class="amount"><?= e(number_format($variance, 0)) ?></td>
                    <td><?= e((string) (($item['comparison_note'] ?? '') ?: ($item['notes'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="10" class="empty-state">尚無預算項目。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr><th colspan="6">收益合計</th><th class="amount"><?= e(number_format((float) $totals['income'], 0)) ?></th><th class="amount"><?= e(number_format((float) $totals['previous_income'], 0)) ?></th><th class="amount"><?= e(number_format((float) $totals['income'] - (float) $totals['previous_income'], 0)) ?></th><th></th></tr>
            <tr><th colspan="6">費損合計</th><th class="amount"><?= e(number_format((float) $totals['expense'], 0)) ?></th><th class="amount"><?= e(number_format((float) $totals['previous_expense'], 0)) ?></th><th class="amount"><?= e(number_format((float) $totals['expense'] - (float) $totals['previous_expense'], 0)) ?></th><th></th></tr>
            <tr><th colspan="6">賸餘 / 短絀</th><th class="amount"><?= e(number_format((float) $totals['balance'], 0)) ?></th><th class="amount"><?= e(number_format((float) $totals['previous_balance'], 0)) ?></th><th class="amount"><?= e(number_format((float) $totals['balance'] - (float) $totals['previous_balance'], 0)) ?></th><th></th></tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($budget['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($budget['notes'])) ?></p>
    <?php endif; ?>

    <?php
    $approvalTargetId = (int) $budget['id'];
    $approvalStatus = (string) $budget['status'];
    $approvalApproveUrl = '/annual-budgets/' . $approvalTargetId . '/approve';
    $approvalRejectUrl = '/annual-budgets/' . $approvalTargetId . '/reject';
    $approvalCanApprove = $canApprove;
    ?>
    <?php require base_path('resources/views/shared/approval-section.php'); ?>
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
