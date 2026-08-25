<?php
$active = 'balance-sheets';
$documentTitle = '資產負債表';
$canManage = \App\Core\Permission::can('balance_sheets.manage');
$year = (int) $sheet['fiscal_year'];
$assetItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'asset'));
$liabilityItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'liability'));
$equityItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'equity'));

$money = static fn ($v) => number_format((float) $v, 0);
$pct = static fn ($current, $prior) => number_format(\App\Domain\BalanceSheets\BalanceSheetSummary::variancePercent((float) $current, (float) $prior), 0);

$renderItem = static function (array $item, callable $money, callable $pct): void {
    ?>
    <tr>
        <td>　<?= e($item['item_name']) ?></td>
        <td class="amount"><?= e($money($item['current_amount'])) ?></td>
        <td class="amount"><?= e($money($item['prior_amount'])) ?></td>
        <td class="amount"><?= e($money((float) $item['current_amount'] - (float) $item['prior_amount'])) ?></td>
        <td class="amount"><?= e($pct($item['current_amount'], $item['prior_amount'])) ?></td>
    </tr>
    <?php
};
$renderTotal = static function (string $label, array $t, callable $money, callable $pct, string $class = 'os-total'): void {
    ?>
    <tr class="<?= e($class) ?>">
        <td><?= e($label) ?></td>
        <td class="amount"><?= e($money($t['current'])) ?></td>
        <td class="amount"><?= e($money($t['prior'])) ?></td>
        <td class="amount"><?= e($money($t['current'] - $t['prior'])) ?></td>
        <td class="amount"><?= e($pct($t['current'], $t['prior'])) ?></td>
    </tr>
    <?php
};
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">主管機關核備</p>
            <h2><?= e($sheet['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($year)) ?> / <?= $sheet['status'] === 'confirmed' ? '已確認' : '草稿' ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/balance-sheets">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/balance-sheets/<?= e((string) $sheet['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <p class="muted-text no-print">中華民國 <?= e((string) roc_year($year)) ?> 年及 <?= e((string) (roc_year($year) - 1)) ?> 年 12 月 31 日　單位：新臺幣元</p>

    <div class="table-wrap">
        <table class="data-table operating-statement-table">
            <thead>
            <tr>
                <th>項目名稱</th>
                <th class="amount">本年底決算數(1)</th>
                <th class="amount">上年底決算數(2)</th>
                <th class="amount">比較增(減)金額(3)=(1)-(2)</th>
                <th class="amount">％(4)=(3)/(2)*100</th>
            </tr>
            </thead>
            <tbody>
            <tr class="os-section"><td colspan="5">資產</td></tr>
            <?php foreach ($assetItems as $item) { $renderItem($item, $money, $pct); } ?>
            <?php $renderTotal('資產總計', $totals['asset'], $money, $pct, 'os-grand'); ?>

            <tr class="os-section"><td colspan="5">負債</td></tr>
            <?php foreach ($liabilityItems as $item) { $renderItem($item, $money, $pct); } ?>
            <?php $renderTotal('負債總計', $totals['liability'], $money, $pct); ?>

            <tr class="os-section"><td colspan="5">淨值</td></tr>
            <?php foreach ($equityItems as $item) { $renderItem($item, $money, $pct); } ?>
            <?php $renderTotal('淨值總計', $totals['equity'], $money, $pct); ?>

            <?php $renderTotal('負債及淨值總計', $totals['liability_equity'], $money, $pct, 'os-grand'); ?>
            <?php if (!$items): ?>
                <tr><td colspan="5" class="empty-state">尚未輸入科目明細。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (abs($totals['balance_check']['current']) > 0.005 || abs($totals['balance_check']['prior']) > 0.005): ?>
        <p class="print-notes" style="color:#b00020;">注意：資產總計與負債及淨值總計不平衡（本年底差額 <?= e($money($totals['balance_check']['current'])) ?>、上年底差額 <?= e($money($totals['balance_check']['prior'])) ?>），請檢查各科目金額。</p>
    <?php endif; ?>

    <?php if (!empty($sheet['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($sheet['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
