<?php
$active = 'payment-receipts';
$printable = false;
$categories = ['人事費', '獎助金', '鐘點費', '工讀金', '稿費', '出席費', '評審費', '審查費', '獎金', '支援金', '交通補貼', '其他'];
$dateParts = payment_receipt_print_roc_parts((string) $receipt['receipt_date']);
ob_start();
?>
<section class="panel no-print">
    <div class="panel-header">
        <div>
            <p class="eyebrow">領款收據列印</p>
            <h2><?= e($receipt['receipt_no'] ?: '未編號') ?></h2>
            <p class="muted-text"><?= e($receipt['payee_name']) ?> / <?= e(roc_date($receipt['receipt_date'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/payment-receipts/<?= e((string) $receipt['id']) ?>">返回明細</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<section class="payment-receipt-paper">
    <header class="payment-receipt-heading">
        <h2><?= e($profile['foundation_name'] ?? foundation_name()) ?></h2>
        <h1>領款收據</h1>
        <p>更新版本：<?= e($receipt['template_version'] ?: '2026年1月23日') ?></p>
    </header>

    <div class="payment-receipt-meta-row">
        <span>收據編號：<?= e($receipt['receipt_no'] ?: '-') ?></span>
        <span>付款方式：<?= e(payment_receipt_print_payment_label((string) $receipt['payment_type'])) ?></span>
        <span>狀態：<?= e(payment_receipt_print_status_label((string) $receipt['status'])) ?></span>
    </div>

    <h3 class="payment-receipt-section-title">領款者<?= $receipt['payment_type'] === 'cash' ? '（領現金，不需填寫銀行資訊）' : '' ?></h3>
    <div class="payment-receipt-person-grid">
        <div><span>姓名</span><strong><?= e($receipt['payee_name']) ?></strong></div>
        <div><span>身分證字號</span><strong><?= e($receipt['payee_tax_id'] ?: '') ?></strong></div>
        <div><span>連絡電話</span><strong><?= e($receipt['phone'] ?: '') ?></strong></div>
        <div class="span-2"><span>戶籍地址</span><strong><?= e($receipt['household_address'] ?: '') ?></strong></div>
        <?php if ($receipt['payment_type'] === 'bank'): ?>
            <div><span>銀行</span><strong><?= e($receipt['bank_name'] ?: '') ?></strong></div>
            <div><span>分行</span><strong><?= e($receipt['bank_branch'] ?: '') ?></strong></div>
            <div><span>銀行帳號</span><strong><?= e($receipt['bank_account'] ?: '') ?></strong></div>
            <div><span>銀行戶名</span><strong><?= e($receipt['bank_account_name'] ?: '') ?></strong></div>
        <?php endif; ?>
        <div class="span-2"><span>備註</span><strong><?= nl2br(e($receipt['remark'] ?: '')) ?></strong></div>
    </div>

    <table class="payment-receipt-table">
        <tbody>
        <tr>
            <th>領款日期</th>
            <td>中華民國 <?= e((string) $dateParts['year']) ?> 年 <?= e((string) $dateParts['month']) ?> 月 <?= e((string) $dateParts['day']) ?> 日</td>
        </tr>
        <tr>
            <th>費別或摘要</th>
            <td>
                <div class="payment-receipt-category-line">
                    <?php foreach ($categories as $category): ?>
                        <span class="receipt-check <?= $receipt['fee_category'] === $category ? 'checked' : '' ?>"><?= e($category) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php if ($receipt['fee_category'] === '其他' && !empty($receipt['fee_category_other'])): ?>
                    <p class="payment-receipt-inline-text">其他：<?= e($receipt['fee_category_other']) ?></p>
                <?php endif; ?>
                <?php if (!empty($receipt['summary'])): ?>
                    <p class="payment-receipt-inline-text">摘要：<?= e($receipt['summary']) ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>金額</th>
            <td>
                <strong><?= e(payment_receipt_print_money_upper($receipt['amount'])) ?></strong>
                <span class="payment-receipt-number-money">新臺幣：<?= e(number_format((float) $receipt['amount'], 0)) ?> 元整</span>
            </td>
        </tr>
        <tr>
            <th>收訖文字</th>
            <td class="payment-receipt-attestation">
                以上金額業已如領到無訛　此據<br>
                謹致　<?= e($profile['foundation_name'] ?? foundation_name()) ?>
            </td>
        </tr>
        <?php if (!empty($receipt['appendix_note'])): ?>
            <tr>
                <th>附註</th>
                <td><?= nl2br(e($receipt['appendix_note'])) ?></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <section class="payment-receipt-signature">
        <div>
            <span>領款人簽名或蓋章</span>
            <div class="payment-receipt-stamp"></div>
        </div>
    </section>

    <section class="payment-receipt-print-notes">
        <?php foreach (payment_receipt_print_notes($receipt) as $note): ?>
            <p><?= nl2br(e($note)) ?></p>
        <?php endforeach; ?>
    </section>
</section>
<?php
function payment_receipt_print_payment_label(string $type): string
{
    return ['bank' => '匯款', 'cash' => '現金'][$type] ?? $type;
}

function payment_receipt_print_status_label(string $status): string
{
    return ['draft' => '草稿', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}

function payment_receipt_print_roc_parts(string $date): array
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
        return ['year' => '', 'month' => '', 'day' => ''];
    }

    return [
        'year' => ((int) $matches[1]) - 1911,
        'month' => (int) $matches[2],
        'day' => (int) $matches[3],
    ];
}

function payment_receipt_print_money_upper($value): string
{
    $amount = max(0, (int) round((float) $value));
    return '新臺幣 ' . payment_receipt_print_chinese_integer($amount) . ' 元整';
}

function payment_receipt_print_chinese_integer(int $number): string
{
    if ($number === 0) {
        return '零';
    }

    $groups = [];
    while ($number > 0) {
        $groups[] = $number % 10000;
        $number = intdiv($number, 10000);
    }

    $bigUnits = ['', '萬', '億', '兆'];
    $text = '';
    $zeroPending = false;
    for ($index = count($groups) - 1; $index >= 0; $index--) {
        $section = $groups[$index];
        if ($section === 0) {
            $zeroPending = $text !== '';
            continue;
        }
        if ($zeroPending || ($text !== '' && $section < 1000)) {
            $text .= '零';
        }
        $text .= payment_receipt_print_chinese_section($section) . ($bigUnits[$index] ?? '');
        $zeroPending = false;
    }

    return $text;
}

function payment_receipt_print_chinese_section(int $section): string
{
    $digits = ['零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖'];
    $units = ['仟', '佰', '拾', ''];
    $chars = str_split(str_pad((string) $section, 4, '0', STR_PAD_LEFT));
    $text = '';
    $zeroPending = false;

    foreach ($chars as $index => $char) {
        $digit = (int) $char;
        if ($digit === 0) {
            if ($text !== '') {
                $zeroPending = true;
            }
            continue;
        }
        if ($zeroPending) {
            $text .= '零';
            $zeroPending = false;
        }
        $text .= $digits[$digit] . $units[$index];
    }

    return $text;
}

function payment_receipt_print_notes(array $receipt): array
{
    $notes = [];
    foreach (['health_insurance_note', 'payment_note', 'tax_note'] as $key) {
        $text = trim((string) ($receipt[$key] ?? ''));
        if ($text !== '') {
            $notes[] = $text;
        }
    }

    return $notes;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
