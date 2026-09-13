<?php
$active = 'expense-requests';
$request = $request ?? [];
$items = $items ?? [];
$requestItems = $requestItems ?? [];
$paymentType = old('payment_type', $request['payment_type'] ?? 'cash');

// 組出要顯示的費用明細列:優先採用驗證失敗回填的陣列,其次為編輯時既有明細,最後給一列空白。
$blankRow = ['petty_cash_item_id' => '', 'item_name' => '', 'payee' => '', 'receipt_type' => 'none', 'amount' => ''];
$oldNames = old('item_name');
if (is_array($oldNames)) {
    $oldAmounts = is_array(old('amount')) ? old('amount') : [];
    $oldItemIds = is_array(old('petty_cash_item_id')) ? old('petty_cash_item_id') : [];
    $oldPayees = is_array(old('payee')) ? old('payee') : [];
    $oldReceiptTypes = is_array(old('receipt_type')) ? old('receipt_type') : [];
    $rows = [];
    foreach ($oldNames as $i => $n) {
        $rows[] = [
            'petty_cash_item_id' => $oldItemIds[$i] ?? '',
            'item_name' => $n,
            'payee' => $oldPayees[$i] ?? '',
            'receipt_type' => $oldReceiptTypes[$i] ?? 'none',
            'amount' => $oldAmounts[$i] ?? '',
        ];
    }
} elseif ($requestItems) {
    $rows = $requestItems;
} else {
    $rows = [$blankRow];
}
if (!$rows) {
    $rows = [$blankRow];
}

$renderRow = static function (array $row, array $items): void { ?>
    <tr class="er-item-row">
        <td data-label="常用項目（選填）">
            <select name="petty_cash_item_id[]" class="er-item-select">
                <option value="">— 自行輸入 —</option>
                <?php foreach ($items as $it): ?>
                    <option value="<?= e((string) $it['id']) ?>"<?= (string) ($row['petty_cash_item_id'] ?? '') === (string) $it['id'] ? ' selected' : '' ?>
                        data-name="<?= e($it['name']) ?>"<?= $it['default_amount'] !== null ? ' data-amount="' . e((string) (int) $it['default_amount']) . '"' : '' ?>><?= e($it['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td data-label="費用項目名稱">
            <input type="text" name="item_name[]" class="er-item-name" maxlength="160" placeholder="例如：車資、郵資、文具" value="<?= e((string) ($row['item_name'] ?? '')) ?>">
        </td>
        <td data-label="給誰（廠商／對象）">
            <input type="text" name="payee[]" class="er-item-payee" maxlength="160" placeholder="例如：遠振資訊科技" value="<?= e((string) ($row['payee'] ?? '')) ?>">
        </td>
        <td data-label="憑證">
            <?php $rt = (string) ($row['receipt_type'] ?? 'none'); ?>
            <select name="receipt_type[]" class="er-item-receipt">
                <option value="none"<?= $rt === 'none' ? ' selected' : '' ?>>無</option>
                <option value="invoice"<?= $rt === 'invoice' ? ' selected' : '' ?>>發票</option>
                <option value="receipt"<?= $rt === 'receipt' ? ' selected' : '' ?>>收據</option>
            </select>
        </td>
        <td data-label="金額" class="amount">
            <input type="number" name="amount[]" class="er-item-amount" inputmode="decimal" step="1" min="0" placeholder="0" value="<?= e((string) ($row['amount'] ?? '')) ?>" style="text-align:right">
        </td>
        <td class="er-item-remove-cell">
            <button type="button" class="btn er-item-remove" title="刪除此列" aria-label="刪除此列">✕</button>
        </td>
    </tr>
<?php };

ob_start();
?>
<style>
.er-items { width: 100%; border-collapse: collapse; margin-top: 4px; }
.er-items th, .er-items td { border: 1px solid #d9dfdc; padding: 6px 8px; vertical-align: middle; }
.er-items thead th { background: #f1f4f3; text-align: left; font-size: 13px; }
.er-items th.amount, .er-items td.amount { text-align: right; width: 130px; }
.er-items td.er-item-remove-cell { width: 46px; text-align: center; }
.er-items input, .er-items select { width: 100%; box-sizing: border-box; }
.er-item-remove { padding: 2px 10px; }
.er-items tfoot td { background: #f6f8f7; font-weight: 700; }
.er-items-total { text-align: right; }
.er-item-add { margin-top: 8px; }
</style>

<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= empty($request['id']) ? '新增費用申請' : '編輯費用申請' ?></h2>
            <p class="muted-text">員工代墊的小額費用（如車資、郵資、文具等）於此申請;可一次申請多筆費用。核定後併入零用金,由會計確認後付款。</p>
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
            <label class="span-2">
                <span>事由／說明（選填）</span>
                <textarea name="reason" rows="2"><?= e((string) old('reason', $request['reason'] ?? '')) ?></textarea>
            </label>
        </div>

        <div class="form-section">
            <h3>費用明細</h3>
            <p class="muted-text">可新增多筆費用（例:車資 1、車資 2…）,金額將自動加總。</p>
            <table class="er-items entry-table" id="erItems">
                <thead>
                    <tr>
                        <th style="width:22%">常用項目（選填）</th>
                        <th>費用項目名稱</th>
                        <th>給誰（廠商／對象）</th>
                        <th style="width:96px">憑證</th>
                        <th class="amount">金額</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="erItemsBody">
                    <?php foreach ($rows as $row) { $renderRow($row, $items); } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="er-items-total">合計</td>
                        <td class="amount"><span id="erTotal">0</span> 元</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <button type="button" class="btn er-item-add" id="erAddItem">＋ 新增一列</button>
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

<template id="erRowTemplate">
    <?php $renderRow($blankRow, $items); ?>
</template>

<script>
(function () {
    var body = document.getElementById('erItemsBody');
    var tpl = document.getElementById('erRowTemplate');
    var totalEl = document.getElementById('erTotal');

    function recalc() {
        var sum = 0;
        body.querySelectorAll('.er-item-amount').forEach(function (inp) {
            var v = parseFloat(inp.value);
            if (!isNaN(v)) { sum += v; }
        });
        totalEl.textContent = sum.toLocaleString('en-US');
    }

    function bindRow(row) {
        var select = row.querySelector('.er-item-select');
        var name = row.querySelector('.er-item-name');
        var amount = row.querySelector('.er-item-amount');
        if (select) {
            select.addEventListener('change', function () {
                var opt = select.options[select.selectedIndex];
                var n = opt.getAttribute('data-name');
                if (n) { name.value = n; }
                if (amount && !amount.value && opt.getAttribute('data-amount')) {
                    amount.value = opt.getAttribute('data-amount');
                    recalc();
                }
            });
        }
        if (amount) { amount.addEventListener('input', recalc); }
        var remove = row.querySelector('.er-item-remove');
        if (remove) {
            remove.addEventListener('click', function () {
                if (body.querySelectorAll('.er-item-row').length <= 1) {
                    // 至少保留一列:清空而非移除。
                    if (select) { select.value = ''; }
                    if (name) { name.value = ''; }
                    if (amount) { amount.value = ''; }
                    var payee = row.querySelector('.er-item-payee');
                    if (payee) { payee.value = ''; }
                    var receipt = row.querySelector('.er-item-receipt');
                    if (receipt) { receipt.value = 'none'; }
                } else {
                    row.parentNode.removeChild(row);
                }
                recalc();
            });
        }
    }

    Array.prototype.forEach.call(body.querySelectorAll('.er-item-row'), bindRow);
    recalc();

    document.getElementById('erAddItem').addEventListener('click', function () {
        var frag = tpl.content.cloneNode(true);
        var row = frag.querySelector('.er-item-row');
        body.appendChild(row);
        bindRow(row);
        var nm = row.querySelector('.er-item-name');
        if (nm) { nm.focus(); }
    });

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
