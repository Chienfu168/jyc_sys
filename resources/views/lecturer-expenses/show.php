<?php
$active = 'lecturer-expenses';
$documentTitle = '講師費用請款單';
$paymentReceipt = $paymentReceipt ?? null;
$canViewPaymentReceipts = \App\Core\Permission::can('payment_receipts.view');
$canManagePaymentReceipts = \App\Core\Permission::can('payment_receipts.manage');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($expense['service_title']) ?></h2>
            <p class="muted-text"><?= e($expense['display_name'] ?: $expense['lecturer_name']) ?>，<?= e(roc_date($expense['expense_date'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/lecturer-expenses">返回列表</a>
            <?php if (\App\Core\Permission::can('lecturer_expenses.manage')): ?>
                <a class="btn" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>/edit">編輯</a>
                <?php if ($expense['payment_status'] === 'pending'): ?>
                    <form method="post" action="/lecturer-expenses/<?= e((string) $expense['id']) ?>/mark-paid">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">標記已付款</button>
                    </form>
                <?php endif; ?>
                <?php if (\App\Core\Permission::can('accounting.manage') && $expense['payment_status'] !== 'voided' && empty($expense['accounting_voucher_id'])): ?>
                    <form method="post" action="/lecturer-expenses/<?= e((string) $expense['id']) ?>/voucher">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">建立會計傳票</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($expense['accounting_voucher_id'])): ?>
                    <a class="btn" href="/accounting/vouchers/<?= e((string) $expense['accounting_voucher_id']) ?>">查看傳票</a>
                <?php endif; ?>
                <?php if ($expense['payment_status'] !== 'voided'): ?>
                    <form method="post" action="/lecturer-expenses/<?= e((string) $expense['id']) ?>/void">
                        <?= csrf_field() ?>
                        <button class="btn" type="submit">作廢</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('lecturer_expenses.delete')): ?>
                <form method="post" action="/lecturer-expenses/<?= e((string) $expense['id']) ?>/delete" onsubmit="return confirm('確定要刪除此講師支出？此操作無法復原（已建立會計傳票者無法刪除）。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
            <?php if ($paymentReceipt && $canViewPaymentReceipts): ?>
                <a class="btn" href="/payment-receipts/<?= e((string) $paymentReceipt['id']) ?>">查看領據</a>
            <?php elseif ($expense['payment_status'] !== 'voided' && $canManagePaymentReceipts): ?>
                <form method="post" action="/lecturer-expenses/<?= e((string) $expense['id']) ?>/payment-receipt">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">產生領據</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>講師</th>
            <td><?= e($expense['display_name'] ?: $expense['lecturer_name']) ?></td>
            <th>身分證字號 / 統編</th>
            <td><?= e($expense['tax_id'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>費用日期</th>
            <td><?= e(roc_date($expense['expense_date'])) ?></td>
            <th>付款狀態</th>
            <td><?= e(lecturer_expense_show_status($expense['payment_status'])) ?></td>
        </tr>
        <tr>
            <th>服務內容</th>
            <td colspan="3"><?= e($expense['service_title']) ?></td>
        </tr>
        <tr>
            <th>承辦單位</th>
            <td><?= e($expense['service_unit'] ?: '-') ?></td>
            <th>專案 / 活動</th>
            <td><?= e(trim(($expense['project_name'] ?: '-') . ' / ' . ($expense['activity_name'] ?: '-'))) ?></td>
        </tr>
        <tr>
            <th>時數</th>
            <td><?= e(number_format((float) $expense['hours'], 2)) ?></td>
            <th>鐘點費單價</th>
            <td><?= e(number_format((float) $expense['hourly_rate'], 0)) ?></td>
        </tr>
        <tr>
            <th>會計傳票</th>
            <td colspan="3">
                <?php if (!empty($expense['accounting_voucher_id'])): ?>
                    <a class="link" href="/accounting/vouchers/<?= e((string) $expense['accounting_voucher_id']) ?>"><?= e($expense['voucher_no'] ?: '查看傳票') ?></a>
                    <span class="muted-text">（<?= e(lecturer_expense_show_voucher_status((string) ($expense['voucher_status'] ?? ''))) ?>）</span>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
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
                <td>鐘點費</td>
                <td class="amount"><?= e(number_format((float) $expense['lecture_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>交通費</td>
                <td class="amount"><?= e(number_format((float) $expense['transportation_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>其他費用</td>
                <td class="amount"><?= e(number_format((float) $expense['other_fee'], 0)) ?></td>
            </tr>
            <tr>
                <td>代扣稅額</td>
                <td class="amount">-<?= e(number_format((float) $expense['withholding_tax'], 0)) ?></td>
            </tr>
            </tbody>
            <tfoot>
            <tr>
                <th>應付總額</th>
                <th class="amount"><?= e(number_format((float) $expense['gross_total'], 0)) ?></th>
            </tr>
            <tr>
                <th>實付金額</th>
                <th class="amount"><?= e(number_format((float) $expense['net_total'], 0)) ?></th>
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
            <th>基金會付款銀行</th>
            <td><?= e($expense['bank_name'] ? $expense['bank_name'] . ' ' . $expense['account_no'] : '-') ?></td>
            <th>講師匯款帳戶</th>
            <td><?= e(trim(($expense['lecturer_bank_name'] ?: '-') . ' ' . ($expense['lecturer_bank_branch'] ?: '') . ' ' . ($expense['lecturer_bank_account_no'] ?: ''))) ?></td>
        </tr>
        <tr>
            <th>憑證 / 請款編號</th>
            <td><?= e($expense['receipt_no'] ?: '-') ?></td>
            <th>建檔人</th>
            <td><?= e($expense['created_by_name'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($expense['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($expense['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function lecturer_expense_show_status(string $status): string
{
    return ['pending' => '待付款', 'paid' => '已付款', 'voided' => '作廢'][$status] ?? $status;
}
function lecturer_expense_show_voucher_status(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已過帳', 'voided' => '作廢'][$status] ?? ($status ?: '-');
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
