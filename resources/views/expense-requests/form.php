<?php
$active = 'expense-requests';
$request = $request ?? [];
$items = $items ?? [];
$isEdit = ($request['status'] ?? 'draft') !== 'draft' || !empty($request['id']);
$paymentType = old('payment_type', $request['payment_type'] ?? 'cash');
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= empty($request['id']) ? '新增費用申請' : '編輯費用申請' ?></h2>
            <p class="muted-text">員工代墊的小額費用（如郵資、文具等）於此申請;核定後會併入零用金,由會計確認後付款。</p>
        </div>
        <a class="btn" href="/expense-requests">返回清單</a>
    </div>

    <form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label>
                <span>費用日期</span>
                <input type="date" name="occurred_on" value="<?= e((string) old('occurred_on', $request['occurred_on'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>金額</span>
                <input type="number" name="amount" inputmode="decimal" step="0.01" min="0" value="<?= e((string) old('amount', $request['amount'] ?? '')) ?>" required>
            </label>
            <label>
                <span>常用項目（選填）</span>
                <select name="petty_cash_item_id" id="erItemSelect">
                    <option value="">— 自行輸入 —</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?= e((string) $it['id']) ?>"<?= (string) old('petty_cash_item_id', $request['petty_cash_item_id'] ?? '') === (string) $it['id'] ? ' selected' : '' ?>
                            data-name="<?= e($it['name']) ?>"<?= $it['default_amount'] !== null ? ' data-amount="' . e((string) (int) $it['default_amount']) . '"' : '' ?>><?= e($it['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>費用項目名稱</span>
                <input type="text" name="item_name" id="erItemName" maxlength="160" placeholder="例如：郵資、文具" value="<?= e((string) old('item_name', $request['item_name'] ?? '')) ?>" required>
            </label>
            <label class="span-2">
                <span>事由／說明（選填）</span>
                <textarea name="reason" rows="2"><?= e((string) old('reason', $request['reason'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="form-section">
            <h3>收款方式</h3>
            <div class="grid-form">
                <label class="span-2">
                    <span>付款方式</span>
                    <span class="er-pay-toggle">
                        <label class="er-pay-opt"><input type="radio" name="payment_type" value="cash" <?= $paymentType !== 'bank' ? 'checked' : '' ?>><span>現金</span></label>
                        <label class="er-pay-opt"><input type="radio" name="payment_type" value="bank" <?= $paymentType === 'bank' ? 'checked' : '' ?>><span>匯款</span></label>
                    </span>
                </label>
                <div class="span-2 er-bank" id="erBank"<?= $paymentType === 'bank' ? '' : ' hidden' ?>>
                    <div class="grid-form">
                        <label>
                            <span>銀行</span>
                            <input type="text" name="bank_name" maxlength="120" value="<?= e((string) old('bank_name', $request['bank_name'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>分行</span>
                            <input type="text" name="bank_branch" maxlength="120" value="<?= e((string) old('bank_branch', $request['bank_branch'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>收款帳號</span>
                            <input type="text" name="bank_account" maxlength="60" value="<?= e((string) old('bank_account', $request['bank_account'] ?? '')) ?>">
                        </label>
                        <label>
                            <span>戶名</span>
                            <input type="text" name="bank_account_name" maxlength="120" value="<?= e((string) old('bank_account_name', $request['bank_account_name'] ?? '')) ?>">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <label class="pcq-field">
            <span class="pcq-label">憑證照片（可多張,會自動壓縮）</span>
            <input type="file" name="receipts[]" accept="image/*,application/pdf" capture="environment" multiple>
            <span class="field-hint">可直接拍照或從相簿選取,照片會在上傳前自動壓縮。PDF 原樣保留。</span>
        </label>

        <div class="form-actions">
            <button class="btn" type="submit" name="action" value="draft">儲存草稿</button>
            <button class="btn primary" type="submit" name="action" value="submit">送出申請</button>
        </div>
    </form>
</section>

<script>
(function () {
    var select = document.getElementById('erItemSelect');
    var name = document.getElementById('erItemName');
    var amount = document.querySelector('input[name="amount"]');
    if (select) {
        select.addEventListener('change', function () {
            var opt = select.options[select.selectedIndex];
            var n = opt.getAttribute('data-name');
            if (n) { name.value = n; }
            if (amount && !amount.value && opt.getAttribute('data-amount')) { amount.value = opt.getAttribute('data-amount'); }
        });
    }
    var bank = document.getElementById('erBank');
    Array.prototype.forEach.call(document.querySelectorAll('input[name="payment_type"]'), function (r) {
        r.addEventListener('change', function () {
            if (bank) { bank.hidden = document.querySelector('input[name="payment_type"]:checked').value !== 'bank'; }
        });
    });
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
