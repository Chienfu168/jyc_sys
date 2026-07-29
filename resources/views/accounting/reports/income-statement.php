<?php
$active = 'accounting';
$documentTitle = '收支餘絀表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/accounting/reports/income-statement">
            <input type="date" name="start_date" value="<?= e($startDate) ?>">
            <input type="date" name="end_date" value="<?= e($endDate) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/accounting/reports/balance-sheet?end_date=<?= e($endDate) ?>">資產負債表</a>
            <a class="btn" href="/accounting/reports/trial-balance?start_date=<?= e($startDate) ?>&end_date=<?= e($endDate) ?>">試算表</a>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>報表期間</th>
            <td><?= e(roc_date($startDate)) ?> 至 <?= e(roc_date($endDate)) ?></td>
            <th>本期餘絀</th>
            <td class="amount"><?= e(accounting_income_statement_money($totals['surplus'])) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="two-column-report">
        <div>
            <h3>收入</h3>
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
                    <?php foreach ($incomeRows as $row): ?>
                        <tr>
                            <td class="mono"><?= e($row['code']) ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td class="amount"><?= e(accounting_income_statement_money($row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$incomeRows): ?>
                        <tr><td colspan="3" class="empty">本期間尚無收入。</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="2">收入合計</th>
                        <th class="amount"><?= e(accounting_income_statement_money($totals['income'])) ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div>
            <h3>支出</h3>
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
                    <?php foreach ($expenseRows as $row): ?>
                        <tr>
                            <td class="mono"><?= e($row['code']) ?></td>
                            <td><?= e($row['name']) ?></td>
                            <td class="amount"><?= e(accounting_income_statement_money($row['amount'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$expenseRows): ?>
                        <tr><td colspan="3" class="empty">本期間尚無支出。</td></tr>
                    <?php endif; ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="2">支出合計</th>
                        <th class="amount"><?= e(accounting_income_statement_money($totals['expense'])) ?></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <table class="meta-table report-total-table">
        <tbody>
        <tr>
            <th>收入合計</th>
            <td class="amount"><?= e(accounting_income_statement_money($totals['income'])) ?></td>
            <th>支出合計</th>
            <td class="amount"><?= e(accounting_income_statement_money($totals['expense'])) ?></td>
        </tr>
        <tr>
            <th>本期餘絀</th>
            <td colspan="3" class="amount"><strong><?= e(accounting_income_statement_money($totals['surplus'])) ?></strong></td>
        </tr>
        </tbody>
    </table>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_income_statement_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
