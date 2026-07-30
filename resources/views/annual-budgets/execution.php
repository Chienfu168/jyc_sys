<?php
$active = 'annual-budgets';
$documentTitle = '預算執行報表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card"><span>收益預算</span><strong><?= e(number_format((float) $totals['income_budget'], 0)) ?></strong></div>
    <div class="stat-card"><span>收益實際</span><strong><?= e(number_format((float) $totals['income_actual'], 0)) ?></strong></div>
    <div class="stat-card"><span>費損預算</span><strong><?= e(number_format((float) $totals['expense_budget'], 0)) ?></strong></div>
    <div class="stat-card"><span>費損實際</span><strong><?= e(number_format((float) $totals['expense_actual'], 0)) ?></strong></div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($budget['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($budget['fiscal_year'])) ?>，期間 <?= e(roc_date_range($startDate, $endDate)) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>">回預算表</a>
            <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>/statement">經費預算表</a>
            <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
                <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>/edit">編輯科目對應</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ((int) $totals['unmapped'] > 0): ?>
        <div class="alert warning no-print">
            仍有 <?= e((string) $totals['unmapped']) ?> 筆預算項目尚未對應會計科目，實際數會暫列為 0。
        </div>
    <?php endif; ?>

    <?php if ((int) $totals['over_budget'] > 0): ?>
        <div class="alert error no-print">
            有 <?= e((string) $totals['over_budget']) ?> 筆支出項目已超過預算，請檢視明細。
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>預算分類</th>
                <th>項目名稱</th>
                <th>會計科目</th>
                <th class="amount">預算數</th>
                <th class="amount">實際數</th>
                <th class="amount">餘額</th>
                <th class="amount">執行率</th>
                <th>狀態</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                $remaining = (float) $item['remaining_amount'];
                $rate = (float) $item['execution_rate'];
                $isExpenseOver = $item['item_type'] === 'expense' && $remaining < 0;
                $accountLabel = trim((string) (($item['account_code'] ?? '') . ' ' . ($item['account_name'] ?? '')));
                ?>
                <tr>
                    <td><?= e($item['item_type'] === 'income' ? '收益' : '費損') ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td><?= e($item['item_name']) ?></td>
                    <td><?= e($accountLabel !== '' ? $accountLabel : '未對應') ?></td>
                    <td class="amount"><?= e(number_format((float) $item['amount'], 0)) ?></td>
                    <td class="amount"><?= e(number_format((float) $item['actual_amount'], 0)) ?></td>
                    <td class="amount <?= $isExpenseOver ? 'danger-text' : '' ?>"><?= e(number_format($remaining, 0)) ?></td>
                    <td class="amount"><?= e(number_format($rate, 2)) ?>%</td>
                    <td>
                        <?php if (empty($item['account_id'])): ?>
                            <span class="badge muted">未對應</span>
                        <?php elseif ($isExpenseOver): ?>
                            <span class="badge danger">超支</span>
                        <?php else: ?>
                            <span class="badge ok">正常</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="9" class="empty">尚無預算項目。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4">收益合計</th>
                <th class="amount"><?= e(number_format((float) $totals['income_budget'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['income_actual'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['income_budget'] - (float) $totals['income_actual'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['income_rate'], 2)) ?>%</th>
                <th></th>
            </tr>
            <tr>
                <th colspan="4">費損合計</th>
                <th class="amount"><?= e(number_format((float) $totals['expense_budget'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['expense_actual'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['expense_budget'] - (float) $totals['expense_actual'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['expense_rate'], 2)) ?>%</th>
                <th></th>
            </tr>
            <tr>
                <th colspan="4">賸餘 / 短絀</th>
                <th class="amount"><?= e(number_format((float) $totals['budget_balance'], 0)) ?></th>
                <th class="amount"><?= e(number_format((float) $totals['actual_balance'], 0)) ?></th>
                <th colspan="3"></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
