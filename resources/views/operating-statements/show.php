<?php
$active = 'operating-statements';
$documentTitle = '收支營運表';
$canManage = \App\Core\Permission::can('operating_statements.manage');
$year = (int) $statement['fiscal_year'];
$incomeItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'income'));
$expenseItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'expense'));
$taxItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'tax'));

$money = static fn ($v) => number_format((float) $v, 0);
$pct = static fn ($current, $budget) => number_format(\App\Domain\OperatingStatements\OperatingStatementSummary::variancePercent((float) $current, (float) $budget), 0);
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">主管機關核備</p>
            <h2><?= e($statement['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($year)) ?> / <?= $statement['status'] === 'confirmed' ? '已確認' : '草稿' ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/operating-statements">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/operating-statements/<?= e((string) $statement['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <p class="muted-text no-print">中華民國 <?= e((string) roc_year($year)) ?> 年及 <?= e((string) (roc_year($year) - 1)) ?> 年 1 月 1 日至 12 月 31 日　單位：新臺幣元</p>

    <div class="table-wrap">
        <table class="data-table operating-statement-table">
            <thead>
            <tr>
                <th>上年度決算數</th>
                <th>項目名稱</th>
                <th class="amount">本年度決算數(1)</th>
                <th class="amount">本年度預算數(2)</th>
                <th class="amount">比較增(減)金額(3)=(1)-(2)</th>
                <th class="amount">％(4)=(3)/(2)*100</th>
            </tr>
            </thead>
            <tbody>
            <tr class="os-section"><td></td><td colspan="5">收益</td></tr>
            <?php foreach ($incomeItems as $item): ?>
                <tr>
                    <td class="amount"><?= e($money($item['prior_amount'])) ?></td>
                    <td>　<?= e($item['item_name']) ?></td>
                    <td class="amount"><?= e($money($item['current_amount'])) ?></td>
                    <td class="amount"><?= e($money($item['budget_amount'])) ?></td>
                    <td class="amount"><?= e($money((float) $item['current_amount'] - (float) $item['budget_amount'])) ?></td>
                    <td class="amount"><?= e($pct($item['current_amount'], $item['budget_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="os-total">
                <td class="amount"><?= e($money($totals['income']['prior'])) ?></td>
                <td>收益合計</td>
                <td class="amount"><?= e($money($totals['income']['current'])) ?></td>
                <td class="amount"><?= e($money($totals['income']['budget'])) ?></td>
                <td class="amount"><?= e($money($totals['income']['current'] - $totals['income']['budget'])) ?></td>
                <td class="amount"><?= e($pct($totals['income']['current'], $totals['income']['budget'])) ?></td>
            </tr>

            <tr class="os-section"><td></td><td colspan="5">費損</td></tr>
            <?php foreach ($expenseItems as $item): ?>
                <tr>
                    <td class="amount"><?= e($money($item['prior_amount'])) ?></td>
                    <td>　<?= e($item['item_name']) ?></td>
                    <td class="amount"><?= e($money($item['current_amount'])) ?></td>
                    <td class="amount"><?= e($money($item['budget_amount'])) ?></td>
                    <td class="amount"><?= e($money((float) $item['current_amount'] - (float) $item['budget_amount'])) ?></td>
                    <td class="amount"><?= e($pct($item['current_amount'], $item['budget_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="os-total">
                <td class="amount"><?= e($money($totals['expense']['prior'])) ?></td>
                <td>費損合計</td>
                <td class="amount"><?= e($money($totals['expense']['current'])) ?></td>
                <td class="amount"><?= e($money($totals['expense']['budget'])) ?></td>
                <td class="amount"><?= e($money($totals['expense']['current'] - $totals['expense']['budget'])) ?></td>
                <td class="amount"><?= e($pct($totals['expense']['current'], $totals['expense']['budget'])) ?></td>
            </tr>

            <tr class="os-grand">
                <td class="amount"><?= e($money($totals['pretax']['prior'])) ?></td>
                <td>本期稅前賸餘(短絀)</td>
                <td class="amount"><?= e($money($totals['pretax']['current'])) ?></td>
                <td class="amount"><?= e($money($totals['pretax']['budget'])) ?></td>
                <td class="amount"><?= e($money($totals['pretax']['current'] - $totals['pretax']['budget'])) ?></td>
                <td class="amount"><?= e($pct($totals['pretax']['current'], $totals['pretax']['budget'])) ?></td>
            </tr>

            <?php foreach ($taxItems as $item): ?>
                <tr>
                    <td class="amount"><?= e($money($item['prior_amount'])) ?></td>
                    <td><?= e($item['item_name']) ?></td>
                    <td class="amount"><?= e($money($item['current_amount'])) ?></td>
                    <td class="amount"><?= e($money($item['budget_amount'])) ?></td>
                    <td class="amount"><?= e($money((float) $item['current_amount'] - (float) $item['budget_amount'])) ?></td>
                    <td class="amount"><?= e($pct($item['current_amount'], $item['budget_amount'])) ?></td>
                </tr>
            <?php endforeach; ?>

            <tr class="os-grand">
                <td class="amount"><?= e($money($totals['aftertax']['prior'])) ?></td>
                <td>本期稅後賸餘(短絀)</td>
                <td class="amount"><?= e($money($totals['aftertax']['current'])) ?></td>
                <td class="amount"><?= e($money($totals['aftertax']['budget'])) ?></td>
                <td class="amount"><?= e($money($totals['aftertax']['current'] - $totals['aftertax']['budget'])) ?></td>
                <td class="amount"><?= e($pct($totals['aftertax']['current'], $totals['aftertax']['budget'])) ?></td>
            </tr>
            <?php if (!$items): ?>
                <tr><td colspan="6" class="empty-state">尚未輸入營運項目。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($statement['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($statement['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
