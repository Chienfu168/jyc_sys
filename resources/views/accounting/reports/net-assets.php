<?php
$active = 'accounting';
$documentTitle = '淨值變動表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>淨值變動表</h2>
            <p class="muted-text"><?= e(roc_date($startDate)) ?> 至 <?= e(roc_date($endDate)) ?>，比較期間 <?= e(roc_date($previousStartDate)) ?> 至 <?= e(roc_date($previousEndDate)) ?>。</p>
        </div>
        <div class="actions">
            <form class="search bank-filter" method="get" action="/accounting/reports/net-assets">
                <input type="date" name="start_date" value="<?= e($startDate) ?>">
                <input type="date" name="end_date" value="<?= e($endDate) ?>">
                <button class="btn" type="submit">查詢</button>
            </form>
            <a class="btn" href="/accounting/reports/cash-flow?start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>">現金流量表</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table financial-statement-table">
            <thead>
            <tr>
                <th>項目</th>
                <th class="amount">本年度</th>
                <th class="amount">上年度</th>
                <th class="amount">比較增(減)</th>
                <th class="amount">比率</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td class="amount"><?= e(accounting_statement_money($row['current'])) ?></td>
                    <td class="amount"><?= e(accounting_statement_money($row['previous'])) ?></td>
                    <td class="amount"><?= e(accounting_statement_money($row['variance'])) ?></td>
                    <td class="amount"><?= e(accounting_statement_rate($row['rate'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_statement_money($value): string
{
    return abs((float) $value) < 0.005 ? '-' : number_format((float) $value, 0);
}

function accounting_statement_rate($value): string
{
    return $value === null ? '新增' : number_format((float) $value, 2) . '%';
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
