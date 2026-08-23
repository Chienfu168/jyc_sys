<?php
$receiptVoids = $receiptVoids ?? [];
$canManage = $canManage ?? \App\Core\Permission::can('donations.manage');
$canChangeReceipt = $canManage && $donation['receipt_status'] === 'issued' && !empty($donation['receipt_no']);
if (!$canChangeReceipt && !$receiptVoids) {
    return;
}

// 輔助函式須在下方 HTML 呼叫前定義(function_exists 條件式宣告不會被 hoisting)。
if (!function_exists('donation_receipt_workflow_datetime')) {
    function donation_receipt_workflow_datetime(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }

        $date = substr($datetime, 0, 10);
        $time = substr($datetime, 11, 5);

        return roc_date($date) . ($time !== '' ? ' ' . $time : '');
    }
}
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <p class="eyebrow">收據異動</p>
            <h2>作廢 / 重開</h2>
        </div>
    </div>

    <?php if ($canChangeReceipt): ?>
        <div class="two-column-report">
            <form class="form compact-form" method="post" action="/donations/<?= e((string) $donation['id']) ?>/void-receipt" onsubmit="return confirm('確定要作廢目前收據？');">
                <?= csrf_field() ?>
                <label>
                    <span>作廢原因</span>
                    <textarea name="reason" rows="2" required></textarea>
                </label>
                <div class="form-actions">
                    <button class="btn" type="submit">作廢收據</button>
                </div>
            </form>
            <form class="form compact-form" method="post" action="/donations/<?= e((string) $donation['id']) ?>/reissue-receipt" onsubmit="return confirm('確定要作廢目前收據並重開新號碼？');">
                <?= csrf_field() ?>
                <label>
                    <span>重開原因</span>
                    <textarea name="reason" rows="2" required></textarea>
                </label>
                <div class="form-actions">
                    <button class="btn primary" type="submit">作廢並重開</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <h3>作廢紀錄</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>作廢收據</th>
                <th>重開收據</th>
                <th>原因</th>
                <th>作廢人 / 時間</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($receiptVoids as $void): ?>
                <tr>
                    <td class="mono"><?= e($void['receipt_no']) ?></td>
                    <td class="mono"><?= e($void['reissued_receipt_no'] ?: '-') ?></td>
                    <td><?= nl2br(e($void['reason'] ?: '-')) ?></td>
                    <td>
                        <?= e($void['voided_by_name'] ?: '-') ?>
                        <div class="muted-text"><?= e(donation_receipt_workflow_datetime($void['voided_at'] ?? '')) ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$receiptVoids): ?>
                <tr><td colspan="4" class="empty">尚無作廢紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
