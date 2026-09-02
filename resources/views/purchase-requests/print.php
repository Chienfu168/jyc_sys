<?php
$active = 'purchase-requests';
$documentTitle = '採購申請單';
$supervisingDepartment = purchase_print_supervising_department($request);
$procurementOwner = purchase_print_procurement_owner($request, $supervisingDepartment);
$signatureSteps = purchase_print_signature_steps($request, $profile, $supervisingDepartment, $procurementOwner);
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
        <span>請購單位：<?= e($request['request_unit']) ?></span>
        <span>主管部門：<?= e($supervisingDepartment) ?></span>
        <span>採購承辦：<?= e($procurementOwner) ?></span>
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
            <th>主管部門：</th>
            <td><strong><?= e($supervisingDepartment) ?></strong><span class="purchase-print-inline-note">依請購屬性與申請單位歸納</span></td>
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
        <?php foreach ($signatureSteps as $step): ?>
            <div class="purchase-signature-box">
                <span><?= e($step['role']) ?></span>
                <small><?= e($step['department']) ?></small>
                <div class="purchase-signature-stamp" aria-hidden="true"></div>
                <strong><?= e($step['name'] !== '' ? $step['name'] : '　') ?></strong>
            </div>
        <?php endforeach; ?>
    </section>
</section>

<?php
$attachments = $attachments ?? [];
$attachmentImages = [];
$attachmentOthers = [];
foreach ($attachments as $attachment) {
    if (purchase_print_is_image((array) $attachment)) {
        $attachmentImages[] = $attachment;
    } else {
        $attachmentOthers[] = $attachment;
    }
}
$attachmentSeq = 0;
?>
<?php foreach ($attachmentImages as $img): ?>
    <?php $dataUri = purchase_print_image_data_uri((string) ($img['stored_path'] ?? '')); ?>
    <?php if ($dataUri !== null): ?>
        <?php $attachmentSeq++; ?>
        <article class="panel purchase-print-form purchase-print-attachment">
            <h3 class="purchase-print-attachment-caption">
                附件<?= e(purchase_print_cjk_no($attachmentSeq)) ?>：<?= e(purchase_print_attachment_label((array) $img)) ?>
            </h3>
            <img class="purchase-print-attachment-img" src="<?= e($dataUri) ?>" alt="">
        </article>
    <?php endif; ?>
<?php endforeach; ?>

<?php if ($attachmentOthers): ?>
    <article class="panel purchase-print-form purchase-print-attachment">
        <h3 class="purchase-print-attachment-caption">附件清單（PDF／文件檔，請附正本一併送核）</h3>
        <table class="purchase-form-table">
            <tbody>
            <?php foreach ($attachmentOthers as $other): ?>
                <?php $attachmentSeq++; ?>
                <tr>
                    <th>附件<?= e(purchase_print_cjk_no($attachmentSeq)) ?></th>
                    <td>
                        <?= e(purchase_print_attachment_label((array) $other)) ?>
                        <span class="purchase-print-inline-note"><?= e((string) ($other['original_name'] ?? '')) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </article>
<?php endif; ?>
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

function purchase_print_supervising_department(array $request): string
{
    $text = implode(' ', [
        (string) ($request['purchase_category'] ?? ''),
        (string) ($request['request_unit'] ?? ''),
        (string) ($request['purchase_method'] ?? ''),
        (string) ($request['subject'] ?? ''),
        (string) ($request['reason'] ?? ''),
        (string) ($request['purpose'] ?? ''),
    ]);

    $unit = (string) ($request['request_unit'] ?? '');
    $rules = [
        '資訊主管部門' => ['資訊', '電腦', '網站', '網域', '主機', '系統', '軟體', '雲端', '資安', '網路', 'IT'],
        '財務會計主管部門' => ['會計', '財務', '稅務', '銀行', '帳務', '憑證', '發票'],
        '企劃主管部門' => ['企劃', '計畫', '專案', '補助', '成果', '行銷', '宣傳'],
        '活動主管部門' => ['活動', '課程', '場地', '講師', '招生', '志工'],
        '總務主管部門' => ['總務', '辦公', '設備', '雜項', '修繕', '庶務', '耗材', '家具'],
    ];

    foreach ($rules as $department => $keywords) {
        if (purchase_print_contains_any($text, $keywords)) {
            return $department;
        }
    }

    return $unit !== '' ? $unit . '主管' : '主管部門';
}

function purchase_print_procurement_owner(array $request, string $supervisingDepartment): string
{
    $text = implode(' ', [
        (string) ($request['purchase_method'] ?? ''),
        (string) ($request['purchase_category'] ?? ''),
        $supervisingDepartment,
    ]);

    if (purchase_print_contains_any($text, ['資訊', '電腦', '網站', '網域', '系統', '軟體'])) {
        return '資訊採購';
    }

    if (purchase_print_contains_any($text, ['總務', '辦公', '設備', '雜項', '修繕', '庶務'])) {
        return '總務採購';
    }

    return '採購承辦';
}

function purchase_print_signature_steps(array $request, array $profile, string $supervisingDepartment, string $procurementOwner): array
{
    return [
        [
            'role' => '申請人',
            'department' => (string) ($request['request_unit'] ?? ''),
            'name' => (string) ($request['requester_name'] ?? ''),
        ],
        [
            'role' => '主管部門',
            'department' => $supervisingDepartment,
            'name' => '',
        ],
        [
            'role' => '採購承辦',
            'department' => $procurementOwner,
            'name' => '',
        ],
        [
            'role' => '會計',
            'department' => '財務會計',
            'name' => '',
        ],
        [
            'role' => '執行長',
            'department' => '',
            'name' => (string) ($profile['executive_director'] ?? ''),
        ],
        [
            'role' => '董事長',
            'department' => '',
            'name' => (string) ($profile['representative'] ?? ''),
        ],
    ];
}

function purchase_print_contains_any(string $text, array $keywords): bool
{
    foreach ($keywords as $keyword) {
        if ($keyword !== '' && str_contains($text, $keyword)) {
            return true;
        }
    }

    return false;
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

/** 附件類型與標題文字。 */
function purchase_print_attachment_label(array $attachment): string
{
    $labels = [
        'quotation' => '報價單',
        'invoice' => '發票／收據',
        'contract' => '合約／訂單',
        'delivery' => '驗收文件',
        'payment' => '付款憑證',
        'other' => '其他',
    ];
    $category = $labels[(string) ($attachment['category'] ?? 'other')] ?? '附件';
    $title = trim((string) ($attachment['title'] ?? '')) ?: trim((string) ($attachment['original_name'] ?? ''));

    return $title !== '' ? $category . '（' . $title . '）' : $category;
}

/** 是否為可內嵌列印的圖片附件。 */
function purchase_print_is_image(array $attachment): bool
{
    $mime = strtolower((string) ($attachment['mime_type'] ?? ''));
    if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
        return true;
    }
    $ext = strtolower(pathinfo((string) ($attachment['original_name'] ?? ''), PATHINFO_EXTENSION));

    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

/** 讀取採購上傳的圖片附件為 data: URI,供列印時內嵌於文件之後。 */
function purchase_print_image_data_uri(string $storedPath): ?string
{
    $storedPath = trim($storedPath);
    if ($storedPath === '' || !str_starts_with($storedPath, 'private_uploads/purchase_requests/')) {
        return null;
    }
    if (str_contains($storedPath, '..')) {
        return null;
    }
    $full = storage_path($storedPath);
    if (!is_file($full) || !is_readable($full)) {
        return null;
    }
    $mimeByExt = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    if (!isset($mimeByExt[$ext])) {
        return null;
    }
    $data = @file_get_contents($full);
    if ($data === false || $data === '') {
        return null;
    }

    return 'data:' . $mimeByExt[$ext] . ';base64,' . base64_encode($data);
}

/** 阿拉伯數字轉國字(一、二…),供附件編號用。 */
function purchase_print_cjk_no(int $n): string
{
    $digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九'];
    if ($n <= 0) {
        return (string) $n;
    }
    if ($n < 10) {
        return $digits[$n];
    }
    if ($n < 20) {
        return '十' . ($n % 10 === 0 ? '' : $digits[$n % 10]);
    }
    if ($n < 100) {
        return $digits[intdiv($n, 10)] . '十' . ($n % 10 === 0 ? '' : $digits[$n % 10]);
    }

    return (string) $n;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
