<?php
$active = 'bank-slips';
$slip = $slip ?? [];
$amountUppercase = $amountUppercase ?? '';

$rocDate = ['y' => '', 'm' => '', 'd' => ''];
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) ($slip['slip_date'] ?? ''), $md)) {
    $rocDate = ['y' => (int) $md[1] - 1911, 'm' => (int) $md[2], 'd' => (int) $md[3]];
}

// 存摺存款扣帳帳號:取款帳戶號碼逐字填入格線(保留數字與符號),固定 16 格。
$acctChars = preg_split('//u', preg_replace('/\s+/', '', (string) ($slip['withdrawal_account_no'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$acctBoxes = 16;

$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

ob_start();
?>
<style>
@media print {
    @page { size: A4 portrait; margin: 8mm; }
    .no-print { display: none !important; }
}
.chb-doc {
    width: 100%;
    max-width: 194mm;
    margin: 0 auto;
    color: #000;
    background: #fff;
    font-family: "DFKai-SB", "BiauKai", "標楷體", "KaiTi", "TW-Kai", "Noto Serif TC", "PMingLiU", serif;
    font-size: 12px;
    line-height: 1.35;
}
.chb-doc .chb-topline {
    display: flex; justify-content: space-between; align-items: flex-end;
    border-bottom: 1px dotted #000; padding-bottom: 2px; margin-bottom: 3px; font-size: 11px;
}
.chb-doc .chb-title { text-align: center; margin: 2px 0 5px; }
.chb-doc .chb-title .bank { font-size: 20px; font-weight: 700; letter-spacing: 4px; }
.chb-doc .chb-title .kind { font-size: 15px; font-weight: 700; letter-spacing: 2px; margin-left: 8px; }
.chb-box { border: 2px solid #000; }
table.chb { width: 100%; border-collapse: collapse; table-layout: fixed; }
.chb td, .chb th { border: 1px solid #000; padding: 2px 4px; vertical-align: middle; word-break: break-all; }
.chb .lbl { background: #fdf6c9; text-align: center; white-space: nowrap; font-weight: 600; }
.chb .lbl small { font-weight: 400; }
.chb .vlbl { background: #fdf6c9; text-align: center; font-weight: 600; width: 22px; }
.chb .vlbl span { writing-mode: vertical-rl; text-orientation: upright; letter-spacing: 2px; }
.chb .val { text-align: left; }
.chb .amt-guide { letter-spacing: 6px; color: #333; font-size: 12px; }
.chb .amt-upper { font-size: 14px; font-weight: 700; }
.chb-acct { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 2px; }
.chb-acct td { border: 1px solid #000; height: 20px; text-align: center; font-size: 13px; }
.chb-seal-area { min-height: 66mm; position: relative; }
.chb-seal-note { position: absolute; left: 6px; bottom: 6px; color: #c00; font-weight: 700; font-size: 12px; }
.chb-mid td { height: 16px; }
.chb-mid .mid-lbl { background: #fdf6c9; text-align: center; white-space: nowrap; font-weight: 600; width: 46px; }
.chb-stamp { min-height: 66mm; }
.chb-auth td { height: 15px; }
.chb-auth .lbl { font-weight: 600; }
.chb-sign { display: flex; justify-content: space-between; margin-top: 6px; font-size: 12px; }
.chb-sign .red { color: #c00; font-weight: 700; }
.chb-foot { text-align: right; margin-top: 8px; font-size: 12px; }
</style>

<section class="panel no-print">
    <div class="panel-header">
        <div>
            <h2>彰化銀行匯款單(取款憑條)</h2>
            <p class="muted-text">收款人：<?= $e($slip['payee_name'] ?? '') ?> ／ 金額：<?= $e(number_format((float) ($slip['amount'] ?? 0), 0)) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/bank-slips">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>
</section>

<div class="chb-doc">
    <div class="chb-topline">
        <span>第一聯　銀行存查聯</span>
        <span>※粗線框內請匯款人正確填寫，並請詳閱第二聯注意事項。</span>
    </div>
    <div class="chb-title">
        <span class="bank">彰化銀行</span><span class="kind">匯款申請書(兼取款憑條)</span>
    </div>

    <div class="chb-box">
        <!-- 匯款日期 / 種類 / 解款行 / 傳票 -->
        <table class="chb">
            <colgroup>
                <col style="width:27%"><col style="width:7%"><col style="width:20%">
                <col style="width:9%"><col style="width:14%"><col style="width:7%"><col style="width:16%">
            </colgroup>
            <tr>
                <td class="val">中華民國　<?= $e($rocDate['y']) ?>　年　<?= $e($rocDate['m']) ?>　月　<?= $e($rocDate['d']) ?>　日</td>
                <td class="lbl">匯款<br>種類</td>
                <td class="val"><?= $e($slip['remittance_type'] ?: '一般跨行匯款(11)') ?></td>
                <td class="lbl">解款行<br>名　稱</td>
                <td class="val"><?= $e($slip['paying_bank_name'] ?? '') ?></td>
                <td class="lbl">傳票<br>編號</td>
                <td class="val">&nbsp;</td>
            </tr>
        </table>
        <!-- 收款人帳號 / 戶名 -->
        <table class="chb">
            <colgroup><col style="width:11%"><col style="width:44%"><col style="width:11%"><col style="width:34%"></colgroup>
            <tr>
                <td class="lbl">收款人<br>帳　號</td>
                <td class="val"><?= $e($slip['payee_account'] ?? '') ?></td>
                <td class="lbl">收款人<br>戶　名</td>
                <td class="val"><?= $e($slip['payee_name'] ?? '') ?></td>
            </tr>
        </table>
        <!-- 匯款人 / 代理人 -->
        <table class="chb">
            <colgroup>
                <col style="width:22px"><col style="width:9%"><col style="width:27%"><col style="width:8%"><col style="width:14%">
                <col style="width:22px"><col style="width:9%"><col style="width:18%"><col style="width:8%"><col>
            </colgroup>
            <tr>
                <td class="vlbl" rowspan="2"><span>匯款人</span></td>
                <td class="lbl">姓　名</td>
                <td class="val" colspan="3"><?= $e($slip['remitter_name'] ?? '') ?></td>
                <td class="vlbl" rowspan="2"><span>代理人</span></td>
                <td class="lbl">姓　名</td>
                <td class="val" colspan="3"><?= $e($slip['agent_name'] ?? '') ?></td>
            </tr>
            <tr>
                <td class="lbl">統一編號</td>
                <td class="val"><?= $e($slip['remitter_id_no'] ?? '') ?></td>
                <td class="lbl">電　話</td>
                <td class="val"><?= $e($slip['remitter_phone'] ?? '') ?></td>
                <td class="lbl">統一編號</td>
                <td class="val">&nbsp;</td>
                <td class="lbl">電　話</td>
                <td class="val">&nbsp;</td>
            </tr>
        </table>
        <!-- 附言 / 簡訊通知 -->
        <table class="chb">
            <colgroup><col style="width:16%"><col style="width:37%"><col style="width:19%"><col style="width:28%"></colgroup>
            <tr>
                <td class="lbl">附　言<br><small>(最多30個全形字)</small></td>
                <td class="val"><?= $e($slip['message'] ?? '') ?></td>
                <td class="lbl">簡訊通知收款人服務<br><small>(手機號碼)</small></td>
                <td class="val"><?= $e($slip['sms_mobile'] ?? '') ?></td>
            </tr>
        </table>
        <!-- 匯款金額(大寫) -->
        <table class="chb">
            <colgroup><col style="width:16%"><col></colgroup>
            <tr>
                <td class="lbl">匯款金額<br>新臺幣(大寫)</td>
                <td class="val">
                    <div class="amt-upper"><?= $e($amountUppercase) ?></div>
                    <div class="amt-guide">拾　億　仟　佰　拾　萬　仟　佰　拾　元整</div>
                </td>
            </tr>
        </table>
        <!-- 取款帳戶原留印鑑 / 存摺扣帳帳號 / 中間欄 / 戳記欄 -->
        <table class="chb">
            <colgroup><col style="width:22px"><col style="width:57%"><col style="width:9%"><col style="width:9%"><col></colgroup>
            <tr>
                <td class="vlbl" rowspan="1"><span>取款帳戶原留印鑑</span></td>
                <td style="vertical-align:top">
                    <div style="text-align:center;font-weight:600">存摺存款扣帳帳號（　請選擇扣款項目　　）</div>
                    <table class="chb-acct">
                        <tr>
                            <?php for ($i = 0; $i < $acctBoxes; $i++): ?>
                                <td><?= $e($acctChars[$i] ?? '') ?></td>
                            <?php endfor; ?>
                        </tr>
                    </table>
                    <div class="chb-seal-area">
                        <div class="chb-seal-note">本匯款申請書跨行匯款金額上限為新臺幣伍仟萬元。</div>
                    </div>
                </td>
                <td class="mid-lbl">(貸)<br>匯出<br>匯款</td>
                <td colspan="1" style="vertical-align:top">
                    <table class="chb chb-mid" style="border:0">
                        <tr><td style="border:0;height:22px">&nbsp;</td></tr>
                    </table>
                    <table class="chb chb-mid">
                        <tr><td class="mid-lbl">手續費<br>收入</td><td>□現金<br>□轉帳</td></tr>
                        <tr><td class="mid-lbl">應付<br>帳款</td><td>□現金<br>□轉帳</td></tr>
                        <tr><td class="mid-lbl">合　計</td><td>&nbsp;</td></tr>
                    </table>
                </td>
                <td style="vertical-align:top">
                    <div style="text-align:center;font-weight:600">戳　記　欄</div>
                    <div class="chb-stamp"></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- 認證欄(銀行作業填寫) -->
    <table class="chb chb-auth" style="margin-top:2px">
        <colgroup><col style="width:22px"><col><col><col><col><col><col><col></colgroup>
        <tr>
            <td class="vlbl" rowspan="4"><span>認證欄</span></td>
            <td class="lbl">匯款日期</td><td class="lbl">登錄時間</td><td class="lbl">櫃台機號</td>
            <td class="lbl">登錄櫃員</td><td class="lbl">發信號碼</td><td class="lbl">會簽主管</td><td class="lbl">放行序號</td>
        </tr>
        <tr>
            <td class="lbl">應收手續費</td><td class="lbl">應付財金</td><td class="lbl">金　額</td>
            <td class="lbl">匯款人統編</td><td class="lbl" colspan="3">臨櫃作業關懷提問表情形</td>
        </tr>
        <tr>
            <td class="lbl">交易序號</td><td colspan="3">&nbsp;<span style="color:#c00">（扣款帳號）</span></td>
            <td class="lbl" colspan="3">解款行／收款帳號</td>
        </tr>
        <tr>
            <td class="lbl">附　言</td><td colspan="6">&nbsp;</td>
        </tr>
    </table>
    <div class="chb-box" style="border-width:1px;border-top:0;padding:1px 4px">更正欄</div>

    <div class="chb-sign">
        <span>經副襄理</span>
        <span class="red">請務必確認本匯款已扣款</span>
        <span>會計</span>
        <span>記帳</span>
        <span>驗印</span>
        <span>經辦製票</span>
    </div>
    <div class="chb-foot">（會638-4電）</div>
</div>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
