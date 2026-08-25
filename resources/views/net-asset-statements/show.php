<?php
$active = 'net-asset-statements';
$documentTitle = '淨值變動表';
$canManage = \App\Core\Permission::can('net_asset_statements.manage');
$year = (int) $statement['fiscal_year'];
$components = [
    'founding_fund' => '設立基金',
    'other_fund' => '其他基金',
    'capital_reserve' => '公積',
    'accumulated_surplus' => '累積賸餘(短絀)',
    'other_equity' => '淨值其他項目',
];
$money = static fn ($v) => number_format((float) $v, 0);
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
            <a class="btn" href="/net-asset-statements">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/net-asset-statements/<?= e((string) $statement['id']) ?>/edit">編輯</a>
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
                <?php foreach ($components as $label): ?>
                    <th class="amount"><?= e($label) ?></th>
                <?php endforeach; ?>
                <th class="amount">合計</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $isBalance = str_contains((string) $row['row_label'], '餘額'); ?>
                <tr class="<?= $isBalance ? 'os-grand' : '' ?>">
                    <td><?= e($row['row_label']) ?></td>
                    <?php foreach ($components as $key => $label): ?>
                        <td class="amount"><?= e($money($row[$key])) ?></td>
                    <?php endforeach; ?>
                    <td class="amount"><?= e($money(\App\Domain\NetAssetStatements\NetAssetSummary::rowTotal($row))) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="<?= count($components) + 2 ?>" class="empty-state">尚未輸入淨值變動明細。</td></tr>
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
