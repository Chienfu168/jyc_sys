<?php
$active = 'cash-flow-statements';
$documentTitle = '現金流量表';
$canManage = \App\Core\Permission::can('cash_flow_statements.manage');
$year = (int) $statement['fiscal_year'];
$operatingItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'operating'));
$investingItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'investing'));
$financingItems = array_values(array_filter($items, static fn ($i) => $i['section'] === 'financing'));

$money = static fn ($v) => number_format((float) $v, 0);
$pct = static fn ($current, $prior) => number_format(\App\Domain\CashFlowStatements\CashFlowSummary::variancePercent((float) $current, (float) $prior), 0);

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
            <h2><?= e($statement['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($year)) ?> / <?= $statement['status'] === 'confirmed' ? '已確認' : '草稿' ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/cash-flow-statements">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/cash-flow-statements/<?= e((string) $statement['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <p class="muted-text no-print">中華民國 <?= e((string) roc_year($year)) ?> 年及 <?= e((string) (roc_year($year) - 1)) ?> 年 1 月 1 日至 12 月 31 日　單位：新臺幣元</p>

    <div class="table-wrap">
        <table class="data-table operating-statement-table">
            <thead>
            <tr>
                <th>項目</th>
                <th class="amount">本年度決算數(1)</th>
                <th class="amount">上年度決算數(2)</th>
                <th class="amount">比較增減(3)=(1)-(2)</th>
                <th class="amount">％(4)=(3)/(2)*100</th>
            </tr>
            </thead>
            <tbody>
            <tr class="os-section"><td colspan="5">業務活動之現金流量</td></tr>
            <?php foreach ($operatingItems as $item) { $renderItem($item, $money, $pct); } ?>
            <?php $renderTotal('業務活動之淨現金流入(流出)', $totals['operating'], $money, $pct); ?>

            <tr class="os-section"><td colspan="5">投資活動之現金流量</td></tr>
            <?php foreach ($investingItems as $item) { $renderItem($item, $money, $pct); } ?>
            <?php $renderTotal('投資活動之淨現金流入(流出)', $totals['investing'], $money, $pct); ?>

            <tr class="os-section"><td colspan="5">籌資活動之現金流量</td></tr>
            <?php foreach ($financingItems as $item) { $renderItem($item, $money, $pct); } ?>
            <?php $renderTotal('籌資活動之淨現金流入(流出)', $totals['financing'], $money, $pct); ?>

            <?php $renderTotal('匯率變動對現金及約當現金之影響', $totals['exchange'], $money, $pct); ?>
            <?php $renderTotal('現金及約當現金增加(減少)數', $totals['net_change'], $money, $pct, 'os-grand'); ?>
            <?php $renderTotal('期初現金及約當現金餘額', $totals['opening'], $money, $pct); ?>
            <?php $renderTotal('期末現金及約當現金餘額', $totals['ending'], $money, $pct, 'os-grand'); ?>
            <?php if (!$items): ?>
                <tr><td colspan="5" class="empty-state">尚未輸入現金流量項目。</td></tr>
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
