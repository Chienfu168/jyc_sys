<?php
$active = 'donations';
$documentTitle = '捐款收據';
$canManage = \App\Core\Permission::can('donations.manage');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">捐款收據</p>
            <h2><?= e($donation['receipt_no'] ?: ($donation['donation_no'] ?? '') ?: '未填收據號碼') ?></h2>
            <?php if (empty($donation['receipt_no']) && !empty($donation['donation_no'])): ?>
                <p class="muted-text">尚未開立正式收據號碼，暫以捐款編號列印；點「開立收據」可產生正式收據號碼。</p>
            <?php endif; ?>
            <p class="muted-text"><?= e($donation['donor_name']) ?> / <?= e(roc_date($donation['donated_at'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/donations/<?= e((string) $donation['id']) ?>">返回明細</a>
            <?php if ($canManage && $donation['receipt_status'] === 'pending'): ?>
                <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/issue-receipt">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">開立收據</button>
                </form>
            <?php endif; ?>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <?php if ($donation['receipt_status'] === 'voided'): ?>
        <div class="alert warning no-print">此捐款紀錄已作廢。</div>
    <?php elseif ($donation['receipt_status'] === 'pending'): ?>
        <div class="alert warning no-print">此筆收據仍為待處理狀態。</div>
    <?php endif; ?>

    <?php require base_path('resources/views/donations/_official-receipt.php'); ?>
</section>

<?php require base_path('resources/views/donations/receipt-deliveries.php'); ?>
<?php
function donation_receipt_money($value): string
{
    return number_format((float) $value, 0);
}

function donation_receipt_status_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
