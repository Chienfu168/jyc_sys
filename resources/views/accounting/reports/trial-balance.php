<?php
$active = 'accounting';
$documentTitle = '試算表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/accounting/reports/trial-balance">
            <input type="date" name="start_date" value="<?= e($startDate) ?>">
            <input type="date" name="end_date" value="<?= e($endDate) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/accounting/reports/general-ledger">總帳</a>
            <a class="btn" href="/accounting/reports/detail-ledger">明細帳</a>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>報表期間</th>
            <td><?= e(roc_date($startDate)) ?> 至 <?= e(roc_date($endDate)) ?></td>
            <th>本期借貸差額</th>
            <td class="amount"><?= e(accounting_trial_money($totals['period_debit'] - $totals['period_credit'])) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>科目代碼</th>
                <th>科目名稱</th>
                <th class="amount">期初餘額</th>
                <th class="amount">本期借方</th>
                <th class="amount">本期貸方</th>
                <th class="amount">期末借方</th>
                <th class="amount">期末貸方</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $endingDebit = $row['normal_balance'] === 'debit' ? max(0, (float) $row['ending_balance']) : max(0, -(float) $row['ending_balance']);
                $endingCredit = $row['normal_balance'] === 'credit' ? max(0, (float) $row['ending_balance']) : max(0, -(float) $row['ending_balance']);
                ?>
                <tr>
                    <td class="mono"><?= e($row['code']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="amount"><?= e(accounting_trial_money($row['opening_balance'])) ?></td>
                    <td class="amount"><?= e(accounting_trial_money($row['period_debit'])) ?></td>
                    <td class="amount"><?= e(accounting_trial_money($row['period_credit'])) ?></td>
                    <td class="amount"><?= e(accounting_trial_money($endingDebit)) ?></td>
                    <td class="amount"><?= e(accounting_trial_money($endingCredit)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3">合計</th>
                <th class="amount"><?= e(accounting_trial_money($totals['period_debit'])) ?></th>
                <th class="amount"><?= e(accounting_trial_money($totals['period_credit'])) ?></th>
                <th class="amount"><?= e(accounting_trial_money($totals['ending_debit'])) ?></th>
                <th class="amount"><?= e(accounting_trial_money($totals['ending_credit'])) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_trial_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
