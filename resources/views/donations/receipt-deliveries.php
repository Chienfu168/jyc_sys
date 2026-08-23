<?php
$receiptDeliveries = $receiptDeliveries ?? [];
$canManage = $canManage ?? \App\Core\Permission::can('donations.manage');
$canRecordDelivery = $canManage && $donation['receipt_status'] === 'issued' && !empty($donation['receipt_no']);
if (!$canRecordDelivery && !$receiptDeliveries) {
    return;
}
$defaultDeliveredTo = $donation['donor_email'] ?: ($donation['donor_address'] ?: '');

// 輔助函式須在下方 HTML 呼叫前定義(function_exists 條件式宣告不會被 hoisting)。
if (!function_exists('donation_delivery_method_options')) {
    function donation_delivery_method_options(): array
    {
        return [
            'email' => '電子郵件',
            'post' => '郵寄',
            'in_person' => '親送',
            'pickup' => '自取',
            'other' => '其他',
        ];
    }
}

if (!function_exists('donation_delivery_method_label')) {
    function donation_delivery_method_label(string $method): string
    {
        return donation_delivery_method_options()[$method] ?? $method;
    }
}

if (!function_exists('donation_delivery_datetime')) {
    function donation_delivery_datetime(?string $datetime): string
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
            <p class="eyebrow">收據寄送</p>
            <h2>寄送 / 通知紀錄</h2>
        </div>
    </div>

    <?php if ($canRecordDelivery): ?>
        <form class="form grid-form compact-form" method="post" action="/donations/<?= e((string) $donation['id']) ?>/receipt-deliveries">
            <?= csrf_field() ?>
            <label>
                <span>寄送方式</span>
                <select name="delivery_method" required>
                    <?php foreach (donation_delivery_method_options() as $value => $label): ?>
                        <option value="<?= e($value) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>寄送日期</span>
                <input type="date" name="delivered_on" value="<?= e(date('Y-m-d')) ?>" required>
            </label>
            <label class="span-2">
                <span>收件對象</span>
                <input type="text" name="delivered_to" value="<?= e($defaultDeliveredTo) ?>" placeholder="Email、地址、收件人或交付對象">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="2"></textarea>
            </label>
            <div class="form-actions span-2">
                <button class="btn primary" type="submit">新增寄送紀錄</button>
            </div>
        </form>
    <?php endif; ?>

    <h3>寄送紀錄</h3>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>收據號碼</th>
                <th>方式</th>
                <th>寄送日期</th>
                <th>收件對象</th>
                <th>備註</th>
                <th>登錄人 / 時間</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($receiptDeliveries as $delivery): ?>
                <tr>
                    <td class="mono"><?= e($delivery['receipt_no']) ?></td>
                    <td><?= e(donation_delivery_method_label((string) $delivery['delivery_method'])) ?></td>
                    <td><?= e(roc_date($delivery['delivered_on'])) ?></td>
                    <td><?= e($delivery['delivered_to'] ?: '-') ?></td>
                    <td><?= nl2br(e($delivery['notes'] ?: '-')) ?></td>
                    <td>
                        <?= e($delivery['created_by_name'] ?: '-') ?>
                        <div class="muted-text"><?= e(donation_delivery_datetime($delivery['created_at'] ?? '')) ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$receiptDeliveries): ?>
                <tr><td colspan="6" class="empty">尚無寄送紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
