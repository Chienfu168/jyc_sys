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
        <article class="receipt-page">
            <div class="statement-title">
                <h2><?= e($profile['foundation_name'] ?? foundation_name()) ?></h2>
                <h3>捐款收據</h3>
                <p>第 <?= e(number_format($index + 1)) ?> / <?= e(number_format($receiptCount)) ?> 張　收據號碼：<?= e($donation['receipt_no']) ?></p>
            </div>

            <table class="meta-table receipt-table">
                <tbody>
                <tr>
                    <th>收據號碼</th>
                    <td><?= e($donation['receipt_no']) ?></td>
                    <th>捐款日期</th>
                    <td><?= e(roc_date($donation['donated_at'])) ?></td>
                </tr>
                <tr>
                    <th>捐款人</th>
                    <td><?= e($donation['donor_name']) ?></td>
                    <th>收據抬頭</th>
                    <td><?= e($donation['receipt_title'] ?: $donation['donor_name']) ?></td>
                </tr>
                <tr>
                    <th>統編 / 身分證字號</th>
                    <td><?= e($donation['tax_id'] ?: '-') ?></td>
                    <th>捐款方式</th>
                    <td><?= e($donation['payment_method']) ?></td>
                </tr>
                <tr>
                    <th>電話</th>
                    <td><?= e($donation['donor_phone'] ?: '-') ?></td>
                    <th>Email</th>
                    <td><?= e($donation['donor_email'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>地址</th>
                    <td colspan="3"><?= e($donation['donor_address'] ?: '-') ?></td>
                </tr>
                <tr>
                    <th>指定專案 / 用途</th>
                    <td colspan="3"><?= e($donation['project_name'] ?: '一般捐款') ?></td>
                </tr>
                <tr>
                    <th>捐款金額</th>
                    <td colspan="3" class="amount">
                        <strong>新台幣 <?= e(donation_receipts_print_money($donation['amount'])) ?> 元整</strong>
                    </td>
                </tr>
                <tr>
                    <th>備註</th>
                    <td colspan="3"><?= nl2br(e($donation['notes'] ?: '-')) ?></td>
                </tr>
                </tbody>
            </table>

            <p class="print-notes">
                本收據依系統捐款紀錄產生；正式寄送前請確認收據號碼、抬頭、統編與金額。
            </p>

            <?php require base_path('resources/views/shared/signatures.php'); ?>
        </article>
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
