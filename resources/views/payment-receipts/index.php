<?php
$active = 'payment-receipts';
$query = http_build_query(['month' => $month, 'status' => $status, 'q' => $keyword]);
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>有效張數</span>
        <strong><?= e(number_format((int) $summary['count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>領款金額</span>
        <strong><?= e(payment_receipt_money($summary['amount'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>已開立</span>
        <strong><?= e(number_format((int) $summary['issued'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>匯款 / 現金</span>
        <strong><?= e(number_format((int) $summary['bank'])) ?> / <?= e(number_format((int) $summary['cash'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/payment-receipts">
            <?php require base_path('resources/views/shared/date-scope-filter.php'); ?>
            <select name="status">
                <option value="">全部狀態</option>
                <?php foreach (payment_receipt_status_options() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="編號、領款者、費別、摘要">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <?php if (\App\Core\Permission::can('payment_receipts.manage')): ?>
                <a class="btn primary" href="/payment-receipts/create">新增領據</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>編號</th>
                <th>領款者</th>
                <th>費別 / 摘要</th>
                <th>付款</th>
                <th class="amount">金額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($receipts as $receipt): ?>
                <tr>
                    <td><?= e(roc_date($receipt['receipt_date'])) ?></td>
                    <td><a class="text-link mono" href="/payment-receipts/<?= e((string) $receipt['id']) ?>"><?= e($receipt['receipt_no'] ?: '-') ?></a></td>
                    <td>
                        <?= e($receipt['payee_name']) ?>
                        <?php if (!empty($receipt['phone'])): ?><div class="muted-text"><?= e($receipt['phone']) ?></div><?php endif; ?>
                    </td>
                    <td>
                        <?= e(payment_receipt_category_label($receipt)) ?>
                        <?php if (!empty($receipt['summary'])): ?><div class="muted-text"><?= e($receipt['summary']) ?></div><?php endif; ?>
                        <?php if (!empty($receipt['source_label'])): ?><div class="muted-text"><?= e($receipt['source_label']) ?></div><?php endif; ?>
                    </td>
                    <td><?= e(payment_receipt_payment_label((string) $receipt['payment_type'])) ?></td>
                    <td class="amount"><?= e(payment_receipt_money($receipt['amount'])) ?></td>
                    <td><span class="badge <?= e(payment_receipt_status_tone((string) $receipt['status'])) ?>"><?= e(payment_receipt_status_label((string) $receipt['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/payment-receipts/<?= e((string) $receipt['id']) ?>">檢視</a>
                        <a class="btn small" href="/payment-receipts/<?= e((string) $receipt['id']) ?>/print">列印</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$receipts): ?>
                <tr><td colspan="8" class="empty">本月份尚無領款收據。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function payment_receipt_money($value): string
{
    return number_format((float) $value, 0);
}

function payment_receipt_status_options(): array
{
    return ['draft' => '草稿', 'issued' => '已開立', 'voided' => '作廢'];
}

function payment_receipt_status_label(string $status): string
{
    return payment_receipt_status_options()[$status] ?? $status;
}

function payment_receipt_status_tone(string $status): string
{
    return ['issued' => 'ok', 'voided' => 'danger'][$status] ?? 'muted';
}

function payment_receipt_payment_label(string $type): string
{
    return ['bank' => '匯款', 'cash' => '現金'][$type] ?? $type;
}

function payment_receipt_category_label(array $receipt): string
{
    if (($receipt['fee_category'] ?? '') === '其他' && !empty($receipt['fee_category_other'])) {
        return '其他：' . $receipt['fee_category_other'];
    }

    return (string) ($receipt['fee_category'] ?? '-');
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
