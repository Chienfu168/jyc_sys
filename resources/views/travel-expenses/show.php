<?php
$active = 'travel-expenses';
$documentTitle = '出差費用核銷單';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($expense['traveler_name']) ?> / <?= e($expense['destination']) ?></h2>
            <p class="muted-text"><?= e(roc_date_range($expense['travel_start'], $expense['travel_end'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/travel-expenses">返回列表</a>
            <?php if (\App\Core\Permission::can('travel_expenses.manage')): ?>
                <a class="btn" href="/travel-expenses/<?= e((string) $expense['id']) ?>/edit">編輯</a>
                <?php if ($expense['payment_status'] === 'pending'): ?>
                    <form method="post" action="/travel-expenses/<?= e((string) $expense['id']) ?>/mark-paid">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">標記已付款</button>
                    </form>
                <?php endif; ?>
                <?php if ($expense['payment_status'] !== 'voided'): ?>
                    <form method="post" action="/travel-expenses/<?= e((string) $expense['id']) ?>/void">
                        <?= csrf_field() ?>
                        <button class="btn" type="submit">作廢</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>出差人</th>
            <td><?= e($expense['traveler_name']) ?></td>
            <th>出差期間</th>
            <td><?= e(roc_date_range($expense['travel_start'], $expense['travel_end'])) ?></td>
        </tr>
        <tr>
            <th>出差地點</th>
            <td><?= e($expense['destination']) ?></td>
            <th>付款狀態</th>
            <td><?= e(travel_expense_show_status($expense['payment_status'])) ?></td>
        </tr>
        <tr>
            <th>出差事由</th>
            <td colspan="3"><?= e($expense['purpose']) ?></td>
        </tr>
        <tr>
            <th>專案名稱</th>
            <td><?= e($expense['project_name'] ?: '-') ?></td>
            <th>帳號對應</th>
            <td><?= e($expense['traveler_account_name'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>項目</th>
                <th class="amount">金額</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>交通費</td>
                <td class="amount"><?= e(number_format((float) $expense['transportation_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>住宿費</td>
                <td class="amount"><?= e(number_format((float) $expense['accommodation_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>膳雜費</td>
                <td class="amount"><?= e(number_format((float) $expense['meal_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>其他費用</td>
                <td class="amount"><?= e(number_format((float) $expense['miscellaneous_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>預支金額</td>
                <td class="amount">-<?= e(number_format((float) $expense['advance_amount'], 0)) ?></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>費用總額</th>
                <th class="amount"><?= e(number_format((float) $expense['total_amount'], 0)) ?></th>
            </tr>
            <tr>
                <th>應核銷金額</th>
                <th class="amount"><?= e(number_format((float) $expense['reimbursable_amount'], 0)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>付款方式</th>
            <td><?= e($expense['payment_method'] ?: '-') ?></td>
            <th>付款日期</th>
            <td><?= e(roc_date($expense['paid_on'])) ?></td>
        </tr>
        <tr>
            <th>付款銀行</th>
            <td><?= e($expense['bank_name'] ? $expense['bank_name'] . ' ' . $expense['account_no'] : '-') ?></td>
            <th>憑證 / 核銷編號</th>
            <td><?= e($expense['receipt_no'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($expense['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($expense['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($expense['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($expense['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function travel_expense_show_status(string $status): string
{
    return ['pending' => '待付款', 'paid' => '已付款', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
