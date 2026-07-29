<?php
$active = 'accounting';
$documentTitle = '明細帳';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/accounting/reports/detail-ledger">
            <input type="date" name="start_date" value="<?= e($startDate) ?>">
            <input type="date" name="end_date" value="<?= e($endDate) ?>">
            <select name="account_id">
                <option value="0">請選擇科目</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= e((string) $account['id']) ?>" <?= (int) $accountId === (int) $account['id'] ? 'selected' : '' ?>>
                        <?= e($account['code'] . ' / ' . $account['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/accounting/reports/general-ledger">總帳</a>
            <a class="btn" href="/accounting/reports/trial-balance">試算表</a>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>報表期間</th>
            <td><?= e(roc_date($startDate)) ?> 至 <?= e(roc_date($endDate)) ?></td>
            <th>會計科目</th>
            <td><?= e($selected ? $selected['code'] . ' / ' . $selected['name'] : '-') ?></td>
        </tr>
        <tr>
            <th>期初餘額</th>
            <td class="amount"><?= e(accounting_detail_money($opening)) ?></td>
            <th>期末餘額</th>
            <td class="amount"><?= e(accounting_detail_money($ending)) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>傳票號碼</th>
                <th>摘要 / 說明</th>
                <th class="amount">借方</th>
                <th class="amount">貸方</th>
                <th class="amount">餘額</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($selected): ?>
                <tr>
                    <td><?= e(roc_date($startDate)) ?></td>
                    <td class="mono">期初</td>
                    <td>期初餘額</td>
                    <td class="amount">-</td>
                    <td class="amount">-</td>
                    <td class="amount"><?= e(accounting_detail_money($opening)) ?></td>
                </tr>
            <?php endif; ?>
            <?php foreach ($lines as $line): ?>
                <tr>
                    <td><?= e(roc_date($line['voucher_date'])) ?></td>
                    <td class="mono"><a class="text-link" href="/accounting/vouchers/<?= e((string) $line['voucher_id']) ?>"><?= e($line['voucher_no']) ?></a></td>
                    <td>
                        <?= e($line['summary']) ?>
                        <?php if (!empty($line['description'])): ?>
                            <div class="muted-text"><?= e($line['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(accounting_detail_money($line['debit'])) ?></td>
                    <td class="amount"><?= e(accounting_detail_money($line['credit'])) ?></td>
                    <td class="amount"><?= e(accounting_detail_money($line['running_balance'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$selected): ?>
                <tr><td colspan="6" class="empty">請先選擇會計科目。</td></tr>
            <?php elseif (!$lines): ?>
                <tr><td colspan="6" class="empty">此期間尚無分錄。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3">本期合計</th>
                <th class="amount"><?= e(accounting_detail_money($totals['debit'])) ?></th>
                <th class="amount"><?= e(accounting_detail_money($totals['credit'])) ?></th>
                <th class="amount"><?= e(accounting_detail_money($ending)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function accounting_detail_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
