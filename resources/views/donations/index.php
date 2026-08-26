<?php
$active = 'donations';
$query = http_build_query([
    'year' => $year,
    'month' => $month,
    'receipt_status' => $receiptStatus,
    'delivery_status' => $deliveryStatus,
    'q' => $keyword,
]);
$reportQuery = http_build_query(['year' => $year, 'month' => $month]);
$receiptPrintQuery = http_build_query([
    'year' => $year,
    'month' => $month,
    'receipt_status' => 'issued',
    'delivery_status' => $deliveryStatus,
    'q' => $keyword,
]);
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>有效筆數</span>
        <strong><?= e(number_format((int) $summary['count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>年度捐款</span>
        <strong><?= e(donation_index_money($summary['amount'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>待處理收據</span>
        <strong><?= e(number_format((int) $summary['pending_receipts'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>未寄送收據</span>
        <strong><?= e(number_format((int) $summary['undelivered_receipts'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/donations">
            <input type="number" name="year" min="1912" max="2100" value="<?= e((string) $year) ?>" placeholder="西元年度">
            <select name="month">
                <option value="">全年</option>
                <?php foreach (range(1, 12) as $monthNo): ?>
                    <?php $value = str_pad((string) $monthNo, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= e($value) ?>" <?= $month === $value ? 'selected' : '' ?>><?= e($monthNo . ' 月') ?></option>
                <?php endforeach; ?>
            </select>
            <select name="receipt_status">
                <option value="">全部收據</option>
                <?php foreach (['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $receiptStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="delivery_status">
                <option value="">全部寄送</option>
                <?php foreach (['undelivered' => '未寄送', 'delivered' => '已寄送'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $deliveryStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="捐款人、收據、專案、方式">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/donors">捐款人</a>
            <a class="btn" href="/donations/report?<?= e($reportQuery) ?>">捐款台帳</a>
            <a class="btn" href="/donations/income-list?<?= e(http_build_query(['year' => $year])) ?>">捐贈收入清冊</a>
            <?php if ($canExport): ?>
                <a class="btn" href="/donations/export?<?= e($query) ?>">匯出 CSV</a>
            <?php endif; ?>
            <?php if ((int) $summary['issued_receipts'] > 0): ?>
                <a class="btn" href="/donations/receipts/print?<?= e($receiptPrintQuery) ?>">批次列印收據</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('donations.manage') && $receiptStatus === 'pending' && (int) $summary['pending_receipts'] > 0): ?>
                <form method="post" action="/donations/bulk-issue-receipts" onsubmit="return confirm('確定要批次開立目前查詢結果中的待處理收據？');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="year" value="<?= e((string) $year) ?>">
                    <input type="hidden" name="month" value="<?= e($month) ?>">
                    <input type="hidden" name="receipt_status" value="pending">
                    <input type="hidden" name="q" value="<?= e($keyword) ?>">
                    <button class="btn" type="submit">批次開立收據</button>
                </form>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('donations.manage')): ?>
                <a class="btn primary" href="/donations/create">新增捐款</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>捐款編號</th>
                <th>日期</th>
                <th>捐款人</th>
                <th>方式</th>
                <th>收據</th>
                <th>專案</th>
                <th>傳票</th>
                <th class="amount">金額</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($donations as $donation): ?>
                <tr>
                    <td class="mono"><?= e($donation['donation_no'] ?? '') ?: '-' ?></td>
                    <td><?= e(roc_date($donation['donated_at'])) ?></td>
                    <td><a class="text-link" href="/donors/<?= e((string) $donation['donor_id']) ?>"><?= e($donation['donor_name']) ?></a></td>
                    <td><?= e($donation['payment_method']) ?></td>
                    <td>
                        <?= e(donation_index_receipt_label($donation['receipt_status'])) ?>
                        <?php if (!empty($donation['receipt_no'])): ?>
                            <div class="muted-text mono"><?= e($donation['receipt_no']) ?></div>
                        <?php endif; ?>
                        <?php if ($donation['receipt_status'] === 'issued' && !empty($donation['receipt_no'])): ?>
                            <div class="muted-text"><?= e(donation_index_delivery_label($donation)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($donation['project_name'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($donation['accounting_voucher_id'])): ?>
                            <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $donation['accounting_voucher_id']) ?>"><?= e($donation['voucher_no'] ?: '已建立') ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(donation_index_money($donation['amount'])) ?></td>
                    <td class="actions">
                        <a class="btn small" href="/donations/<?= e((string) $donation['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('donations.manage') && $donation['receipt_status'] === 'pending'): ?>
                            <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/issue-receipt">
                                <?= csrf_field() ?>
                                <button class="btn small primary" type="submit">開立</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($donation['receipt_status'] !== 'not_required'): ?>
                            <a class="btn small" href="/donations/<?= e((string) $donation['id']) ?>/receipt">收據</a>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('donations.manage') && empty($donation['accounting_voucher_id'])): ?>
                            <a class="btn small" href="/donations/<?= e((string) $donation['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('donations.delete') || owns_record($donation['created_by'] ?? null)): ?>
                            <form method="post" action="/donations/<?= e((string) $donation['id']) ?>/delete" onsubmit="return confirm('確定要「刪除」此捐款紀錄（<?= e($donation['donation_no'] ?? '') ?: (string) $donation['id'] ?>）？刪除後無法復原，且不保留作廢軌跡。');">
                                <?= csrf_field() ?>
                                <button class="btn small danger" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$donations): ?>
                <tr><td colspan="9" class="empty">本年度尚無捐款紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function donation_index_money($value): string
{
    return number_format((float) $value, 0);
}

function donation_index_receipt_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

function donation_index_delivery_label(array $donation): string
{
    if ((int) ($donation['receipt_delivery_count'] ?? 0) <= 0) {
        return '未寄送';
    }

    $date = (string) ($donation['latest_receipt_delivered_on'] ?? '');

    return '已寄送' . ($date !== '' ? ' / ' . roc_date($date) : '');
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
