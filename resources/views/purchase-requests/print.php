<?php
$active = 'purchase-requests';
$documentTitle = '採購申請單';
ob_start();
?>
<section class="panel purchase-print-form">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">採購申請單</p>
            <h2><?= e($request['request_no']) ?></h2>
            <p class="muted-text"><?= e($request['subject']) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/purchase-requests/<?= e((string) $request['id']) ?>">返回明細</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <div class="purchase-print-title">
        <h2><?= e($profile['foundation_name'] ?? foundation_name()) ?></h2>
        <h3>採購申請單(適用5萬元以下之採購)</h3>
    </div>

    <div class="purchase-print-meta">
        <span>申請日期：<?= e(purchase_print_ymd($request['requested_on'])) ?></span>
        <span>申請編號：<?= e($request['request_no']) ?></span>
        <span>會計編號：<?= e($request['accounting_no'] ?: '　　　　　　　　') ?></span>
    </div>

    <div class="purchase-print-approvers">
        <span>申請人：<?= e($request['requester_name']) ?></span>
        <span>總務：</span>
        <span>執行長：<?= e($profile['executive_director'] ?? '') ?></span>
        <span>董事長：<?= e($profile['representative'] ?? '') ?></span>
        <span>會計：</span>
    </div>

    <table class="purchase-form-table">
        <tbody>
        <tr>
            <th>請購類別：</th>
            <td><?= purchase_print_options(['辦公設備', '電腦設備', '雜項設備'], $request['purchase_category'], '其他') ?></td>
        </tr>
        <tr>
            <th>請購單位：</th>
            <td><?= purchase_print_options(['總務', '活動組', '業務推廣組', '志工組', '企劃'], $request['request_unit'], '其他') ?></td>
        </tr>
        <tr>
            <th>申請項目</th>
            <td><?= e($request['subject']) ?></td>
        </tr>
        <tr>
            <th>請購事由</th>
            <td><?= nl2br(e($request['reason'])) ?></td>
        </tr>
        <tr>
            <th>申請目的</th>
            <td><?= nl2br(e($request['purpose'])) ?></td>
        </tr>
        <tr>
            <th>採購方式</th>
            <td><?= purchase_print_options(['總務採購', '資訊採購'], $request['purchase_method'], '其他') ?></td>
        </tr>
        <tr>
            <th>廠商名稱：</th>
            <td><?= e($request['vendor_name'] ?: '　　　　　　　　　') ?></td>
        </tr>
        </tbody>
    </table>

    <h3 class="purchase-print-section-title">請購明細</h3>
    <table class="purchase-print-lines">
        <thead>
        <tr>
            <th>序</th>
            <th>品名</th>
            <th>規格</th>
            <th>數量</th>
            <th>單價</th>
            <th>金額</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $index => $item): ?>
            <tr>
                <td><?= e((string) ($index + 1)) ?></td>
                <td><?= e($item['item_name']) ?></td>
                <td><?= e($item['specification'] ?: '-') ?></td>
                <td class="amount"><?= e(purchase_print_number($item['quantity'])) ?></td>
                <td class="amount"><?= e($item['unit_price'] !== null ? purchase_print_money($item['unit_price']) : '') ?></td>
                <td class="amount"><?= e(purchase_print_money($item['amount'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th>總計</th>
            <td colspan="4"></td>
            <th class="amount"><?= e(purchase_print_money($request['total_amount'])) ?></th>
        </tr>
        </tbody>
    </table>

    <div class="purchase-print-note">
        <p>◎ 是否檢附報價單：<?= (int) $request['quotation_attached'] === 1 ? '☑是；□否' : '□是；☑否' ?><?= (int) $request['quotation_attached'] === 1 ? '' : '（原因：' . e($request['quotation_missing_reason'] ?: '　　　　　　　　　') . '）' ?></p>
        <p>◎ 本採購申請單係供採購 5 萬元以下之採購所填寫；五萬元以上，仍送由董事長核准。</p>
        <?php if ((float) $request['total_amount'] >= 50000): ?>
            <p class="purchase-print-warning">本案金額已達新臺幣 5 萬元以上，請確認董事長核准程序。</p>
        <?php endif; ?>
        <?php if (!empty($request['notes'])): ?>
            <p>備註：<?= nl2br(e($request['notes'])) ?></p>
        <?php endif; ?>
    </div>

    <section class="purchase-signature-grid">
        <?php foreach (['申請人' => $request['requester_name'], '總務' => '', '會計' => '', '執行長' => ($profile['executive_director'] ?? ''), '董事長' => ($profile['representative'] ?? '')] as $label => $name): ?>
            <div class="purchase-signature-box">
                <span><?= e($label) ?></span>
                <strong><?= e((string) $name) ?></strong>
            </div>
        <?php endforeach; ?>
    </section>
</section>
<?php
function purchase_print_ymd(string $date): string
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
        return $date;
    }

    return (int) $matches[1] . '/' . (int) $matches[2] . '/' . (int) $matches[3];
}

function purchase_print_options(array $options, string $value, string $otherLabel): string
{
    $matched = in_array($value, $options, true);
    $parts = [];
    foreach ($options as $option) {
        $parts[] = ($value === $option ? '☑' : '□') . e($option);
    }
    $parts[] = ($matched ? '□' : '☑') . e($otherLabel) . '（' . e($matched ? '' : $value) . '）';

    return implode('　', $parts);
}

function purchase_print_money($value): string
{
    return number_format((float) $value, 0);
}

function purchase_print_number($value): string
{
    $number = (float) $value;
    return floor($number) == $number ? number_format($number, 0) : number_format($number, 2);
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
