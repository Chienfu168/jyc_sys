<?php
$active = 'bank-slips';
$slip = $slip ?? [];
$bankAccounts = $bankAccounts ?? [];
$selectedWithdrawal = (string) old('withdrawal_bank_account_id', $slip['withdrawal_bank_account_id'] ?? '');
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>製作<?= e($bank['name'] ?? '') ?>匯款單</h2>
            <p class="muted-text">填寫收款人與金額即可輸出;匯款人與取款帳戶已由設定帶入,可視需要調整。金額將自動轉國字大寫。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/bank-slips">返回列表</a>
        </div>
    </div>

    <form class="form" method="post" action="/bank-slips">
        <?= csrf_field() ?>
        <input type="hidden" name="bank_code" value="<?= e($bankCode) ?>">

        <div class="form-section">
            <h3>匯款基本資料</h3>
            <div class="grid-form">
                <label>
                    <span>匯款日期</span>
                    <input type="date" name="slip_date" value="<?= e((string) old('slip_date', $slip['slip_date'] ?? date('Y-m-d'))) ?>" required>
                </label>
                <label>
                    <span>匯款種類</span>
                    <input type="text" name="remittance_type" value="<?= e((string) old('remittance_type', $slip['remittance_type'] ?? '')) ?>" placeholder="一般跨行匯款(11)">
                </label>
                <label class="span-2">
                    <span>取款帳戶(基金會付款帳戶)</span>
                    <select name="withdrawal_bank_account_id">
                        <option value="">未指定</option>
                        <?php foreach ($bankAccounts as $account): ?>
                            <option value="<?= e((string) $account['id']) ?>" <?= $selectedWithdrawal === (string) $account['id'] ? 'selected' : '' ?>>
                                <?= e($account['bank_name'] . ' ' . ($account['branch_name'] ?: '') . ' / ' . $account['account_no'] . ' / ' . $account['account_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="field-hint">此帳戶將列於取款憑條的「存款扣帳帳號」欄。</span>
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3>收款人</h3>
            <div class="grid-form">
                <label>
                    <span>收款人戶名</span>
                    <input type="text" name="payee_name" value="<?= e((string) old('payee_name', $slip['payee_name'] ?? '')) ?>" required>
                </label>
                <label>
                    <span>收款人帳號</span>
                    <input type="text" name="payee_account" value="<?= e((string) old('payee_account', $slip['payee_account'] ?? '')) ?>" required>
                </label>
                <label>
                    <span>解款行名稱</span>
                    <input type="text" name="paying_bank_name" value="<?= e((string) old('paying_bank_name', $slip['paying_bank_name'] ?? '')) ?>" placeholder="收款人的銀行/分行">
                </label>
                <label>
                    <span>簡訊通知手機(選填)</span>
                    <input type="text" name="sms_mobile" value="<?= e((string) old('sms_mobile', $slip['sms_mobile'] ?? '')) ?>" placeholder="09xxxxxxxx">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3>金額與附言</h3>
            <div class="grid-form">
                <label>
                    <span>匯款金額(元)</span>
                    <input type="number" min="1" step="1" name="amount" id="bank-slip-amount" value="<?= e((string) old('amount', $slip['amount'] ?? '')) ?>" required>
                    <span class="field-hint">國字大寫:<strong id="bank-slip-amount-upper">-</strong></span>
                </label>
                <label>
                    <span>附言(最多 30 全形字,選填)</span>
                    <input type="text" name="message" maxlength="60" value="<?= e((string) old('message', $slip['message'] ?? '')) ?>">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3>匯款人</h3>
            <div class="grid-form">
                <label>
                    <span>匯款人姓名/名稱</span>
                    <input type="text" name="remitter_name" value="<?= e((string) old('remitter_name', $slip['remitter_name'] ?? '')) ?>">
                </label>
                <label>
                    <span>統一編號</span>
                    <input type="text" name="remitter_id_no" value="<?= e((string) old('remitter_id_no', $slip['remitter_id_no'] ?? '')) ?>">
                </label>
                <label>
                    <span>電話</span>
                    <input type="text" name="remitter_phone" value="<?= e((string) old('remitter_phone', $slip['remitter_phone'] ?? '')) ?>">
                </label>
                <label>
                    <span>代理人姓名(選填)</span>
                    <input type="text" name="agent_name" value="<?= e((string) old('agent_name', $slip['agent_name'] ?? '')) ?>">
                </label>
                <label class="span-2">
                    <span>內部備註(不列印,選填)</span>
                    <input type="text" name="notes" maxlength="255" value="<?= e((string) old('notes', $slip['notes'] ?? '')) ?>">
                </label>
            </div>
        </div>

        <div class="form-actions">
            <a class="btn" href="/bank-slips">取消</a>
            <button class="btn primary" type="submit">建立並前往列印</button>
        </div>
    </form>
</section>

<script>
(() => {
    const digits = ['零','壹','貳','參','肆','伍','陸','柒','捌','玖'];
    const amountEl = document.getElementById('bank-slip-amount');
    const upperEl = document.getElementById('bank-slip-amount-upper');

    function fourDigits(v) {
        const units = ['仟','佰','拾',''];
        const ds = [Math.floor(v/1000)%10, Math.floor(v/100)%10, Math.floor(v/10)%10, v%10];
        let out = '', pendingZero = false;
        ds.forEach((d, i) => {
            if (d === 0) { if (out !== '') pendingZero = true; return; }
            if (pendingZero) { out += '零'; pendingZero = false; }
            out += digits[d] + units[i];
        });
        return out;
    }

    function uppercase(amount) {
        amount = Math.abs(Math.floor(amount));
        if (!amount) return '零元整';
        const sections = [
            { value: Math.floor(amount/100000000)%10000, unit: '億' },
            { value: Math.floor(amount/10000)%10000, unit: '萬' },
            { value: amount%10000, unit: '' },
        ];
        let result = '', prevZero = false;
        sections.forEach(s => {
            if (s.value === 0) { prevZero = result !== ''; return; }
            if (result !== '' && (prevZero || s.value < 1000)) result += '零';
            result += fourDigits(s.value) + s.unit;
            prevZero = false;
        });
        return result + '元整';
    }

    function refresh() {
        const v = Number.parseInt(amountEl.value || '0', 10) || 0;
        upperEl.textContent = v > 0 ? uppercase(v) : '-';
    }
    amountEl?.addEventListener('input', refresh);
    refresh();
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
