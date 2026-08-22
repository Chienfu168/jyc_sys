<?php
$active = 'payment-receipts';
$printable = false;
$canManage = \App\Core\Permission::can('payment_receipts.manage');
$statusLabel = payment_receipt_show_status_label((string) $receipt['status']);
$statusTone = payment_receipt_show_status_tone((string) $receipt['status']);
$sourceUrl = payment_receipt_show_source_url($receipt);
ob_start();
?>
<section class="panel purchase-detail-hero no-print">
    <div class="purchase-detail-title">
        <div class="purchase-title-row">
            <span class="badge <?= e($statusTone) ?>"><?= e($statusLabel) ?></span>
            <span class="mono"><?= e($receipt['receipt_no'] ?: '未編號') ?></span>
        </div>
        <h2><?= e($receipt['payee_name']) ?> / <?= e(payment_receipt_show_money($receipt['amount'])) ?></h2>
        <div class="purchase-hero-meta">
            <span><?= e(roc_date($receipt['receipt_date'])) ?></span>
            <span><?= e(payment_receipt_show_payment_label((string) $receipt['payment_type'])) ?></span>
            <span><?= e(payment_receipt_show_category($receipt)) ?></span>
        </div>
    </div>
    <aside class="purchase-hero-insight">
        <span>領款方式</span>
        <strong><?= e(payment_receipt_show_payment_label((string) $receipt['payment_type'])) ?></strong>
        <small><?= e($receipt['payment_type'] === 'bank' ? ($receipt['bank_name'] ?: '未填銀行') : '現金領款') ?></small>
    </aside>
    <div class="actions purchase-detail-actions">
        <a class="btn" href="/payment-receipts?month=<?= e(substr((string) $receipt['receipt_date'], 0, 7)) ?>">返回列表</a>
        <a class="btn primary" href="/payment-receipts/<?= e((string) $receipt['id']) ?>/print">列印領據</a>
        <?php if ($canManage && $receipt['status'] !== 'voided'): ?>
            <a class="btn" href="/payment-receipts/<?= e((string) $receipt['id']) ?>/edit">編輯</a>
            <?php if ($receipt['status'] === 'draft'): ?>
                <form method="post" action="/payment-receipts/<?= e((string) $receipt['id']) ?>/issue">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">標記開立</button>
                </form>
            <?php endif; ?>
            <form method="post" action="/payment-receipts/<?= e((string) $receipt['id']) ?>/void" onsubmit="return confirm('確定要作廢此領款收據？');">
                <?= csrf_field() ?>
                <button class="btn" type="submit">作廢</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card">
        <span>金額</span>
        <strong><?= e(payment_receipt_show_money($receipt['amount'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>付款方式</span>
        <strong><?= e(payment_receipt_show_payment_label((string) $receipt['payment_type'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>費別</span>
        <strong><?= e(payment_receipt_show_category($receipt)) ?></strong>
    </div>
    <div class="stat-card">
        <span>建立者</span>
        <strong><?= e($receipt['created_by_name'] ?: '-') ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">領款資料</p>
            <h2><?= e($receipt['receipt_no'] ?: '未編號') ?></h2>
        </div>
    </div>
    <table class="meta-table">
        <tbody>
        <tr>
            <th>領款日期</th>
            <td><?= e(roc_date($receipt['receipt_date'])) ?></td>
            <th>狀態</th>
            <td><span class="badge <?= e($statusTone) ?>"><?= e($statusLabel) ?></span></td>
        </tr>
        <?php if (!empty($receipt['source_label'])): ?>
            <tr>
                <th>來源</th>
                <td colspan="3">
                    <?php if ($sourceUrl): ?>
                        <a class="text-link" href="<?= e($sourceUrl) ?>"><?= e($receipt['source_label']) ?></a>
                    <?php else: ?>
                        <?= e($receipt['source_label']) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endif; ?>
        <tr>
            <th>領款者</th>
            <td><?= e($receipt['payee_name']) ?></td>
            <th>身分證字號</th>
            <td><?= e($receipt['payee_tax_id'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>連絡電話</th>
            <td><?= e($receipt['phone'] ?: '-') ?></td>
            <th>付款方式</th>
            <td><?= e(payment_receipt_show_payment_label((string) $receipt['payment_type'])) ?></td>
        </tr>
        <tr>
            <th>戶籍地址</th>
            <td colspan="3"><?= e($receipt['household_address'] ?: '-') ?></td>
        </tr>
        <?php if ($receipt['payment_type'] === 'bank'): ?>
            <tr>
                <th>銀行</th>
                <td><?= e($receipt['bank_name'] ?: '-') ?></td>
                <th>分行</th>
                <td><?= e($receipt['bank_branch'] ?: '-') ?></td>
            </tr>
            <tr>
                <th>銀行帳號</th>
                <td><?= e($receipt['bank_account'] ?: '-') ?></td>
                <th>銀行戶名</th>
                <td><?= e($receipt['bank_account_name'] ?: '-') ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <th>費別</th>
            <td><?= e(payment_receipt_show_category($receipt)) ?></td>
            <th>金額</th>
            <td class="amount"><strong><?= e(payment_receipt_show_money($receipt['amount'])) ?></strong></td>
        </tr>
        <tr>
            <th>摘要</th>
            <td colspan="3"><?= e($receipt['summary'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>備註</th>
            <td colspan="3"><?= nl2br(e($receipt['remark'] ?: '-')) ?></td>
        </tr>
        </tbody>
    </table>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">列印文字</p>
            <h2>備註與稅費說明</h2>
        </div>
    </div>
    <div class="payment-receipt-notes-preview">
        <?php foreach (payment_receipt_show_notes($receipt) as $label => $text): ?>
            <section>
                <h3><?= e($label) ?></h3>
                <p><?= nl2br(e($text)) ?></p>
            </section>
        <?php endforeach; ?>
        <?php if (!payment_receipt_show_notes($receipt)): ?>
            <p class="empty">尚未填寫列印備註。</p>
        <?php endif; ?>
    </div>
</section>
<?php
function payment_receipt_show_money($value): string
{
    return number_format((float) $value, 0);
}

function payment_receipt_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

function payment_receipt_show_status_tone(string $status): string
{
    return ['issued' => 'ok', 'voided' => 'danger'][$status] ?? 'muted';
}

function payment_receipt_show_payment_label(string $type): string
{
    return ['bank' => '匯款', 'cash' => '現金'][$type] ?? $type;
}

function payment_receipt_show_category(array $receipt): string
{
    if (($receipt['fee_category'] ?? '') === '其他' && !empty($receipt['fee_category_other'])) {
        return '其他：' . $receipt['fee_category_other'];
    }

    return (string) ($receipt['fee_category'] ?? '-');
}

function payment_receipt_show_source_url(array $receipt): ?string
{
    $sourceId = (int) ($receipt['source_id'] ?? 0);
    if ($sourceId <= 0) {
        return null;
    }

    return [
        'income_expense_records' => '/income-expenses/' . $sourceId,
        'lecturer_expenses' => '/lecturer-expenses/' . $sourceId,
    ][$receipt['source_type'] ?? ''] ?? null;
}

function payment_receipt_show_notes(array $receipt): array
{
    $notes = [];
    foreach ([
        'health_insurance_note' => '二代健保補充保費',
        'payment_note' => '費用匯款與領據處理',
        'tax_note' => '所得稅與補充保費代扣',
        'appendix_note' => '附註',
    ] as $key => $label) {
        if (trim((string) ($receipt[$key] ?? '')) !== '') {
            $notes[$label] = (string) $receipt[$key];
        }
    }

    return $notes;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
