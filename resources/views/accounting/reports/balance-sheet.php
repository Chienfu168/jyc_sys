<?php
$active = 'accounting';
$documentTitle = '資產負債表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/accounting/reports/balance-sheet">
            <input type="date" name="end_date" value="<?= e($endDate) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/accounting/reports/income-statement?end_date=<?= e($endDate) ?>">收支餘絀表</a>
            <a class="btn" href="/accounting/reports/trial-balance?end_date=<?= e($endDate) ?>">試算表</a>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>報表日期</th>
            <td><?= e(roc_date($endDate)) ?></td>
            <th>平衡差額</th>
            <td class="amount"><?= e(accounting_balance_sheet_money($totals['balance'])) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="two-column-report">
        <div>
            <h3>資產</h3>
            <?php accounting_balance_sheet_table($assetRows, '資產合計', $totals['assets']); ?>
        </div>
        <div>
            <h3>負債及淨資產</h3>
            <?php accounting_balance_sheet_table($liabilityRows, '負債合計', $totals['liabilities']); ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>科目代碼</th>
                        <th>科目名稱</th>
                        <th class="amount">金額</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($netAssetRows as $row): ?>
                        <tr>
                            <td class="mono"><?= e($row['code']) ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td class="amount"><?= e(accounting_balance_sheet_money($row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td class="mono">9999</td>
                        <td>本期餘絀</td>
                        <td class="amount"><?= e(accounting_balance_sheet_money($currentSurplus)) ?></td>
                    </tr>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="2">淨資產合計</th>
                        <th class="amount"><?= e(accounting_balance_sheet_money($totals['net_assets'])) ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <table class="meta-table report-total-table">
        <tbody>
        <tr>
            <th>資產總計</th>
            <td class="amount"><?= e(accounting_balance_sheet_money($totals['assets'])) ?></td>
            <th>負債及淨資產總計</th>
            <td class="amount"><?= e(accounting_balance_sheet_money($totals['liability_and_net_assets'])) ?></td>
        </tr>
        </tbody>
    </table>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_balance_sheet_table(array $rows, string $totalLabel, float $total): void
{
    ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>科目代碼</th>
                <th>科目名稱</th>
                <th class="amount">金額</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="mono"><?= e($row['code']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="amount"><?= e(accounting_balance_sheet_money($row['amount'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="3" class="empty">尚無資料。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="2"><?= e($totalLabel) ?></th>
                <th class="amount"><?= e(accounting_balance_sheet_money($total)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>
    <?php
}
function accounting_balance_sheet_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
