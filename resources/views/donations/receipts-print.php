<?php
$active = 'donations';
$documentTitle = '批次捐款收據';
$scopeText = donation_receipts_print_scope((int) $year, (string) $month);
$backQuery = http_build_query([
    'year' => $year,
    'month' => $month,
    'receipt_status' => 'issued',
    'q' => $keyword,
]);
$receiptCount = count($donations);
ob_start();
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <p class="eyebrow">批次列印收據</p>
            <h2><?= e($scopeText) ?></h2>
            <p class="muted-text">已開立收據 <?= e(number_format($receiptCount)) ?> 張<?= $keyword !== '' ? ' / 關鍵字：' . e($keyword) : '' ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/donations?<?= e($backQuery) ?>">返回已開立清單</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<section class="receipt-batch">
    <?php foreach ($donations as $index => $donation): ?>
        <div class="receipt-page">
            <?php require base_path('resources/views/donations/_official-receipt.php'); ?>
        </div>
    <?php endforeach; ?>
</section>
<?php
function donation_receipts_print_money($value): string
{
    return number_format((float) $value, 0);
}

function donation_receipts_print_scope(int $year, string $month): string
{
    return $month !== ''
        ? roc_date($year . '-' . $month . '-01') . ' 所屬月份'
        : roc_year_label($year);
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
