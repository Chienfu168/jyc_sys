<?php
$active = 'donations';
$documentTitle = '捐款紀錄明細';
$canManage = \App\Core\Permission::can('donations.manage');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">捐款紀錄</p>
            <h2><?= e($donation['donor_name']) ?></h2>
            <p class="muted-text"><?= e(roc_date($donation['donated_at'])) ?> / <?= e(donation_show_money($donation['amount'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/donations">返回列表</a>
            <a class="btn" href="/donors/<?= e((string) $donation['donor_id']) ?>">捐款人資料</a>
            <?php if ($canManage && $donation['receipt_status'] === 'pending'): ?>
                <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/issue-receipt">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">開立收據</button>
                </form>
            <?php endif; ?>
            <?php if ($donation['receipt_status'] !== 'not_required'): ?>
                <a class="btn" href="/donations/<?= e((string) $donation['id']) ?>/receipt">列印收據</a>
            <?php endif; ?>
            <?php if ($canManage && empty($donation['accounting_voucher_id']) && $donation['receipt_status'] !== 'voided'): ?>
                <a class="btn" href="/donations/<?= e((string) $donation['id']) ?>/edit">編輯</a>
                <?php if (\App\Core\Permission::can('accounting.manage')): ?>
                    <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/voucher">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">建立會計傳票</button>
                    </form>
                <?php endif; ?>
                <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/void" onsubmit="return confirm('確定要作廢整筆捐款紀錄？');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">捐款作廢</button>
                </form>
            <?php endif; ?>
            <?php if (!empty($donation['accounting_voucher_id'])): ?>
                <a class="btn" href="/accounting/vouchers/<?= e((string) $donation['accounting_voucher_id']) ?>">查看傳票</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('donations.delete')): ?>
                <?php $deleteWarn = !empty($donation['accounting_voucher_id'])
                    ? '此捐款已建立會計傳票，刪除後傳票分錄仍會保留於帳冊。確定要「刪除」整筆捐款紀錄？刪除後無法復原，且不會保留作廢軌跡。'
                    : '確定要「刪除」整筆捐款紀錄？刪除後無法復原，且不會保留作廢軌跡。若僅需保留紀錄請改用「捐款作廢」。'; ?>
                <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/delete" onsubmit="return confirm('<?= e($deleteWarn) ?>');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>捐款編號</th><td colspan="3" class="mono"><?= e($donation['donation_no'] ?? '') ?: '-' ?></td></tr>
        <tr><th>捐款日期</th><td><?= e(roc_date($donation['donated_at'])) ?></td><th>金額</th><td><?= e(donation_show_money($donation['amount'])) ?></td></tr>
        <tr><th>捐款人</th><td><?= e($donation['donor_name']) ?></td><th>捐款方式</th><td><?= e($donation['payment_method']) ?></td></tr>
        <tr><th>收據抬頭</th><td><?= e($donation['receipt_title'] ?: $donation['donor_name']) ?></td><th>統編 / 身分證字號</th><td><?= e($donation['tax_id'] ?: '-') ?></td></tr>
        <tr><th>收據狀態</th><td><?= e(donation_show_receipt_label($donation['receipt_status'])) ?></td><th>收據號碼</th><td><?= e($donation['receipt_no'] ?: '-') ?></td></tr>
        <tr><th>指定專案 / 用途</th><td colspan="3"><?= e($donation['project_name'] ?: '-') ?></td></tr>
        <tr>
            <th>會計傳票</th>
            <td colspan="3">
                <?php if (!empty($donation['accounting_voucher_id'])): ?>
                    <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $donation['accounting_voucher_id']) ?>">
                        <?= e(($donation['voucher_no'] ?: '未編號') . ' / ' . donation_show_voucher_label($donation['voucher_status'] ?? '')) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <tr><th>備註</th><td colspan="3"><?= nl2br(e($donation['notes'] ?: '-')) ?></td></tr>
        <tr><th>建立人</th><td><?= e($donation['created_by_name'] ?? '-') ?></td><th>建立時間</th><td><?= e($donation['created_at'] ?? '-') ?></td></tr>
        </tbody>
    </table>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>

<?php require base_path('resources/views/donations/receipt-deliveries.php'); ?>
<?php require base_path('resources/views/donations/receipt-workflow.php'); ?>
<?php
function donation_show_money($value): string
{
    return number_format((float) $value, 0);
}

function donation_show_receipt_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

function donation_show_voucher_label(string $status): string
{
    return ['draft' => '草稿', 'posted' => '已入帳', 'voided' => '已作廢'][$status] ?? $status;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
