<?php
$active = 'accounting';
$documentTitle = '現金流量表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>現金流量表</h2>
            <p class="muted-text"><?= e(roc_date($startDate)) ?> 至 <?= e(roc_date($endDate)) ?>，比較期間 <?= e(roc_date($previousStartDate)) ?> 至 <?= e(roc_date($previousEndDate)) ?>。</p>
        </div>
        <div class="actions">
            <form class="search bank-filter" method="get" action="/accounting/reports/cash-flow">
                <input type="date" name="start_date" value="<?= e($startDate) ?>">
                <input type="date" name="end_date" value="<?= e($endDate) ?>">
                <button class="btn" type="submit">查詢</button>
            </form>
            <a class="btn" href="/accounting/reports/net-assets?start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>">淨值變動表</a>
        </div>
    </div>

    <div class="alert warning no-print">
        現金流量表目前依會計科目名稱自動彙總現金、銀行存款、應收預付、應付代收、固定資產、利息與折舊項目。若日後科目名稱不同，可再擴充成「財報科目映射」設定。
    </div>

    <div class="table-wrap">
        <table class="data-table financial-statement-table">
            <thead>
            <tr>
                <th>項目名稱</th>
                <th class="amount">本年度決算數</th>
                <th class="amount">上年度決算數</th>
                <th class="amount">比較增(減)</th>
                <th class="amount">比率</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $section): ?>
                <tr class="statement-section">
                    <td colspan="5"><?= e($section['section']) ?></td>
                </tr>
                <?php foreach ($section['items'] as $row): ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td class="amount"><?= e(accounting_cash_flow_money($row['current'])) ?></td>
                        <td class="amount"><?= e(accounting_cash_flow_money($row['previous'])) ?></td>
                        <td class="amount"><?= e(accounting_cash_flow_money($row['variance'])) ?></td>
                        <td class="amount"><?= e(accounting_cash_flow_rate($row['rate'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_cash_flow_money($value): string
{
    return abs((float) $value) < 0.005 ? '-' : number_format((float) $value, 0);
}

function accounting_cash_flow_rate($value): string
{
    return $value === null ? '新增' : number_format((float) $value, 2) . '%';
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
