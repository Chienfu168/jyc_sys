<?php
$active = 'accounting';
$documentTitle = '總帳';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/accounting/reports/general-ledger">
            <input type="date" name="start_date" value="<?= e($startDate) ?>">
            <input type="date" name="end_date" value="<?= e($endDate) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/accounting">返回會計系統</a>
            <a class="btn" href="/accounting/reports/detail-ledger">明細帳</a>
            <a class="btn" href="/accounting/reports/trial-balance">試算表</a>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>報表期間</th>
            <td><?= e(roc_date($startDate)) ?> 至 <?= e(roc_date($endDate)) ?></td>
            <th>借貸差額</th>
            <td class="amount"><?= e(accounting_report_money($totals['debit'] - $totals['credit'])) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>科目代碼</th>
                <th>科目名稱</th>
                <th>類型</th>
                <th>借貸方向</th>
                <th class="amount">借方發生額</th>
                <th class="amount">貸方發生額</th>
                <th class="amount">本期餘額</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <?php $balance = accounting_report_balance($account); ?>
                <tr>
                    <td class="mono"><?= e($account['code']) ?></td>
                    <td><?= e($account['name']) ?></td>
                    <td><?= e(accounting_report_type_label($account['account_type'])) ?></td>
                    <td><?= e(accounting_report_normal_label($account['normal_balance'])) ?></td>
                    <td class="amount"><?= e(accounting_report_money($account['debit_total'])) ?></td>
                    <td class="amount"><?= e(accounting_report_money($account['credit_total'])) ?></td>
                    <td class="amount"><?= e(accounting_report_money($balance)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="4">合計</th>
                <th class="amount"><?= e(accounting_report_money($totals['debit'])) ?></th>
                <th class="amount"><?= e(accounting_report_money($totals['credit'])) ?></th>
                <th></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_report_money($value): string
{
    return number_format((float) $value, 0);
}
function accounting_report_balance(array $account): float
{
    $debit = (float) $account['debit_total'];
    $credit = (float) $account['credit_total'];
    return $account['normal_balance'] === 'credit' ? $credit - $debit : $debit - $credit;
}
function accounting_report_type_label(string $type): string
{
    return ['asset' => '資產', 'liability' => '負債', 'net_asset' => '淨資產', 'income' => '收入', 'expense' => '支出'][$type] ?? $type;
}
function accounting_report_normal_label(string $normal): string
{
    return ['debit' => '借方', 'credit' => '貸方'][$normal] ?? $normal;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
