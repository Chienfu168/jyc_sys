<?php
$active = 'donations';
$documentTitle = '捐款收據';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">捐款收據</p>
            <h2><?= e($donation['receipt_no'] ?: '未填收據號碼') ?></h2>
            <p class="muted-text"><?= e($donation['donor_name']) ?> / <?= e(roc_date($donation['donated_at'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/donations/<?= e((string) $donation['id']) ?>">返回明細</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <?php if ($donation['receipt_status'] === 'voided'): ?>
        <div class="alert warning no-print">此捐款紀錄已作廢。</div>
    <?php elseif ($donation['receipt_status'] === 'pending'): ?>
        <div class="alert warning no-print">此筆收據仍為待處理狀態。</div>
    <?php endif; ?>

    <div class="statement-title print-only">
        <h2>捐款收據</h2>
        <p><?= e($profile['foundation_name'] ?? foundation_name()) ?></p>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>收據號碼</th>
            <td><?= e($donation['receipt_no'] ?: '-') ?></td>
            <th>收據狀態</th>
            <td><?= e(donation_receipt_status_label((string) $donation['receipt_status'])) ?></td>
        </tr>
        <tr>
            <th>捐款日期</th>
            <td><?= e(roc_date($donation['donated_at'])) ?></td>
            <th>捐款方式</th>
            <td><?= e($donation['payment_method']) ?></td>
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
            <th>電話</th>
            <td><?= e($donation['donor_phone'] ?: '-') ?></td>
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
                <strong>新台幣 <?= e(donation_receipt_money($donation['amount'])) ?> 元整</strong>
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
</section>
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
