<?php
$active = 'bank-slips';
$slip = $slip ?? [];
$amountColumns = $amountColumns ?? [];
$columnLabels = $columnLabels ?? [];
$amountUppercase = $amountUppercase ?? '';

$rocDate = ['y' => '', 'm' => '', 'd' => ''];
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) ($slip['slip_date'] ?? ''), $md)) {
    $rocDate = ['y' => (int) $md[1] - 1911, 'm' => (int) $md[2], 'd' => (int) $md[3]];
}

$withdrawalLabel = '';
if (!empty($slip['withdrawal_bank_name'])) {
    $withdrawalLabel = trim(
        (string) $slip['withdrawal_bank_name'] . ' '
        . (string) ($slip['withdrawal_branch_name'] ?? '') . ' '
        . (string) ($slip['withdrawal_account_no'] ?? '')
    );
}

ob_start();
?>
<style>
@media print {
    @page { size: A4 portrait; margin: 14mm; }
    .chb-slip { box-shadow: none !important; }
}
.chb-slip {
    max-width: 190mm;
    margin: 0 auto;
    color: #000;
    font-family: "DFKai-SB", "BiauKai", "標楷體", "KaiTi", "TW-Kai", "Noto Serif TC", "PMingLiU", serif;
}
.chb-slip .chb-title { text-align: center; margin: 0 0 4px; }
.chb-slip .chb-title h2 { font-size: 20px; margin: 0; letter-spacing: 2px; }
.chb-slip .chb-title h3 { font-size: 16px; margin: 4px 0 0; font-weight: 600; }
.chb-slip .chb-date { text-align: right; margin: 6px 0; font-size: 14px; }
.chb-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.chb-table th, .chb-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
.chb-table th { background: #f4f4f4; text-align: center; white-space: nowrap; width: 90px; font-weight: 600; }
.chb-table td { text-align: left; }
.chb-amount { margin-top: 10px; border: 1px solid #000; }
.chb-amount .chb-amount-upper { padding: 8px; font-size: 15px; border-bottom: 1px solid #000; }
.chb-amount-boxes { width: 100%; border-collapse: collapse; text-align: center; }
.chb-amount-boxes td, .chb-amount-boxes th { border-left: 1px solid #000; padding: 4px 0; }
.chb-amount-boxes th { background: #f4f4f4; font-size: 12px; font-weight: 600; }
.chb-amount-boxes td { font-size: 18px; height: 30px; }
.chb-amount-boxes .chb-col-first { border-left: 0; }
.chb-seal { margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.chb-seal .chb-seal-box { border: 1px solid #000; min-height: 70px; padding: 6px 8px; font-size: 13px; }
.chb-seal .chb-seal-box strong { display: block; margin-bottom: 4px; }
.chb-note { margin-top: 10px; font-size: 12px; color: #333; line-height: 1.7; }
</style>

<section class="panel no-print">
    <div class="panel-header">
        <div>
            <h2>彰化銀行匯款單</h2>
            <p class="muted-text">收款人：<?= e($slip['payee_name'] ?? '') ?> ／ 金額：<?= e(number_format((float) ($slip['amount'] ?? 0), 0)) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/bank-slips">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<section class="panel chb-slip">
    <div class="chb-title">
        <h2>彰化商業銀行</h2>
        <h3>匯款申請書(兼取款憑條)</h3>
    </div>
    <div class="chb-date">
        中華民國 <?= e((string) $rocDate['y']) ?> 年 <?= e((string) $rocDate['m']) ?> 月 <?= e((string) $rocDate['d']) ?> 日
    </div>

    <table class="chb-table">
        <tbody>
        <tr>
            <th>匯款種類</th>
            <td colspan="3"><?= e($slip['remittance_type'] ?: '一般跨行匯款(11)') ?></td>
        </tr>
        <tr>
            <th>解款行名稱</th>
            <td colspan="3"><?= e($slip['paying_bank_name'] ?: '') ?></td>
        </tr>
        <tr>
            <th>收款人帳號</th>
            <td><?= e($slip['payee_account'] ?? '') ?></td>
            <th>收款人戶名</th>
            <td><?= e($slip['payee_name'] ?? '') ?></td>
        </tr>
        <tr>
            <th>匯款人姓名</th>
            <td><?= e($slip['remitter_name'] ?: '') ?></td>
            <th>代理人姓名</th>
            <td><?= e($slip['agent_name'] ?: '') ?></td>
        </tr>
        <tr>
            <th>統一編號</th>
            <td><?= e($slip['remitter_id_no'] ?: '') ?></td>
            <th>電話</th>
            <td><?= e($slip['remitter_phone'] ?: '') ?></td>
        </tr>
        <tr>
            <th>附言</th>
            <td colspan="3"><?= e($slip['message'] ?: '') ?><span class="muted-text" style="float:right;font-size:12px">(最多30個全形字)</span></td>
        </tr>
        <tr>
            <th>簡訊通知<br>收款人手機</th>
            <td colspan="3"><?= e($slip['sms_mobile'] ?: '') ?></td>
        </tr>
        </tbody>
    </table>

    <div class="chb-amount">
        <div class="chb-amount-upper">匯款金額　新臺幣(大寫)　<strong><?= e($amountUppercase) ?></strong></div>
        <table class="chb-amount-boxes">
            <thead>
            <tr>
                <?php foreach ($columnLabels as $i => $label): ?>
                    <th class="<?= $i === 0 ? 'chb-col-first' : '' ?>"><?= e($label) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <?php foreach ($columnLabels as $i => $label): ?>
                    <td class="<?= $i === 0 ? 'chb-col-first' : '' ?>"><?= e($amountColumns[$i] ?? '') ?></td>
                <?php endforeach; ?>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="chb-seal">
        <div class="chb-seal-box">
            <strong>存款扣帳帳號(取款帳戶)</strong>
            <?= e($withdrawalLabel !== '' ? $withdrawalLabel : '　') ?>
        </div>
        <div class="chb-seal-box">
            <strong>取款帳戶原留印鑑</strong>
            <span class="muted-text">(請蓋原留印鑑)</span>
        </div>
    </div>

    <div class="chb-note">
        <p>※ 粗線框內請匯款人正確填寫,並請詳閱注意事項。本匯款申請書跨行匯款金額上限為新臺幣伍仟萬元。</p>
        <p>※ 匯款人或代理人請攜帶身分證辦理匯款事宜。如有查詢、更正、退匯,請持本聯來行洽辦。</p>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
