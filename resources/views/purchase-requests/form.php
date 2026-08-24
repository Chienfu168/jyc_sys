<?php
$lineItems = old('items', $items ?? []);
if (!$lineItems) {
    $lineItems = [['item_name' => '', 'specification' => '', 'quantity' => '1', 'unit_price' => '', 'amount' => '', 'notes' => '']];
}
$categoryOptions = ['辦公設備', '電腦設備', '雜項設備', '資訊工具', '其他'];
$unitOptions = ['總務', '活動組', '業務推廣組', '志工組', '企劃', '其他'];
$methodOptions = ['總務採購', '資訊採購', '自行採購', '指定廠商', '其他'];
$quotationAttached = (string) old('quotation_attached', (string) ($request['quotation_attached'] ?? '1'));
$quotationMissing = $quotationAttached === '0';
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>申請日期</span>
                <input type="date" name="requested_on" value="<?= e((string) old('requested_on', $request['requested_on'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>申請人</span>
                <input type="text" name="requester_name" value="<?= e((string) old('requester_name', $request['requester_name'] ?? '')) ?>" required>
            </label>
            <label class="span-2">
                <span>申請項目</span>
                <input type="text" name="subject" value="<?= e((string) old('subject', $request['subject'] ?? '')) ?>" required>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>採購資訊</h3>
        <div class="grid-form">
            <label>
                <span>請購類別</span>
                <input type="text" name="purchase_category" list="purchase-category-options" value="<?= e((string) old('purchase_category', $request['purchase_category'] ?? '')) ?>" required>
            </label>
            <label>
                <span>請購單位</span>
                <input type="text" name="request_unit" list="purchase-unit-options" value="<?= e((string) old('request_unit', $request['request_unit'] ?? '')) ?>" required>
            </label>
            <label>
                <span>採購方式</span>
                <input type="text" name="purchase_method" list="purchase-method-options" value="<?= e((string) old('purchase_method', $request['purchase_method'] ?? '')) ?>" required>
            </label>
            <label>
                <span>廠商名稱</span>
                <input type="text" name="vendor_name" value="<?= e((string) old('vendor_name', $request['vendor_name'] ?? '')) ?>">
            </label>
            <label>
                <span>是否檢附報價單</span>
                <select name="quotation_attached" id="quotation-attached">
                    <option value="1" <?= $quotationAttached !== '0' ? 'selected' : '' ?>>是</option>
                    <option value="0" <?= $quotationAttached === '0' ? 'selected' : '' ?>>否</option>
                </select>
            </label>
            <label id="quotation-reason-field" class="<?= $quotationMissing ? '' : 'hidden' ?>">
                <span>未附報價單原因</span>
                <input type="text" name="quotation_missing_reason" value="<?= e((string) old('quotation_missing_reason', $request['quotation_missing_reason'] ?? '')) ?>" <?= $quotationMissing ? 'required' : '' ?>>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>事由說明</h3>
        <div class="grid-form">
            <label class="span-2">
                <span>請購事由</span>
                <textarea name="reason" rows="4" required><?= e((string) old('reason', $request['reason'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>申請目的</span>
                <textarea name="purpose" rows="4" required><?= e((string) old('purpose', $request['purpose'] ?? '')) ?></textarea>
            </label>
        </div>
    </div>

    <details class="form-section"<?= trim((string) old('accounting_no', $request['accounting_no'] ?? '')) !== '' || trim((string) old('notes', $request['notes'] ?? '')) !== '' ? ' open' : '' ?>>
        <summary>其他資訊(選填)</summary>
        <div class="grid-form">
            <label>
                <span>會計編號</span>
                <input type="text" name="accounting_no" value="<?= e((string) old('accounting_no', $request['accounting_no'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="3"><?= e((string) old('notes', $request['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <datalist id="purchase-category-options">
        <?php foreach ($categoryOptions as $option): ?><option value="<?= e($option) ?>"></option><?php endforeach; ?>
    </datalist>
    <datalist id="purchase-unit-options">
        <?php foreach ($unitOptions as $option): ?><option value="<?= e($option) ?>"></option><?php endforeach; ?>
    </datalist>
    <datalist id="purchase-method-options">
        <?php foreach ($methodOptions as $option): ?><option value="<?= e($option) ?>"></option><?php endforeach; ?>
    </datalist>

    <div class="panel-header budget-editor-header">
        <div>
            <h2>請購明細</h2>
            <p class="muted-text">依採購單格式填寫品名、規格、數量、單價與金額；金額可手動填寫。</p>
        </div>
        <div class="actions">
            <button class="btn small" type="button" onclick="addPurchaseLine()">新增明細</button>
        </div>
    </div>

    <div class="purchase-lines-editor" id="purchase-lines">
        <?php foreach ($lineItems as $index => $item): ?>
            <div class="purchase-line" data-purchase-line>
                <?php require base_path('resources/views/purchase-requests/item-fields.php'); ?>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="empty-state hidden" id="purchase-empty-state">請至少保留一筆請購明細。</p>

    <div class="form-actions">
        <a class="btn" href="/purchase-requests">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="purchase-line-template">
    <div class="purchase-line" data-purchase-line>
        <?php $index = '__INDEX__'; $item = ['item_name' => '', 'specification' => '', 'quantity' => '1', 'unit_price' => '', 'amount' => '', 'notes' => '']; require base_path('resources/views/purchase-requests/item-fields.php'); ?>
    </div>
</template>

<script>
let purchaseLineIndex = <?= count($lineItems) ?>;

function addPurchaseLine() {
    const template = document.getElementById('purchase-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[name]').forEach((field) => {
        field.name = field.name.replace('__INDEX__', purchaseLineIndex);
    });
    purchaseLineIndex += 1;
    document.getElementById('purchase-lines').appendChild(line);
    bindPurchaseLine(line);
    updatePurchaseEmptyState();
}

function removePurchaseLine(button) {
    const lines = document.querySelectorAll('[data-purchase-line]');
    if (lines.length <= 1) {
        updatePurchaseEmptyState();
        return;
    }
    button.closest('[data-purchase-line]').remove();
    updatePurchaseEmptyState();
}

function bindPurchaseLine(line) {
    line.querySelectorAll('[data-purchase-auto]').forEach((field) => {
        field.addEventListener('input', () => updatePurchaseLineAmount(line));
    });
}

function updatePurchaseLineAmount(line) {
    const quantity = Number(line.querySelector('[data-purchase-field="quantity"]')?.value || 0);
    const unitPrice = Number(line.querySelector('[data-purchase-field="unit_price"]')?.value || 0);
    const amount = line.querySelector('[data-purchase-field="amount"]');
    if (amount && quantity > 0 && unitPrice > 0) {
        amount.value = Math.round(quantity * unitPrice);
    }
}

function updatePurchaseEmptyState() {
    const empty = document.getElementById('purchase-empty-state');
    empty.classList.toggle('hidden', document.querySelectorAll('[data-purchase-line]').length > 0);
}

function updateQuotationReason() {
    const select = document.getElementById('quotation-attached');
    const field = document.getElementById('quotation-reason-field');
    if (!select || !field) {
        return;
    }
    const input = field.querySelector('input[name="quotation_missing_reason"]');
    const missing = select.value === '0';
    field.classList.toggle('hidden', !missing);
    if (input) {
        input.required = missing;
        if (!missing) {
            input.value = '';
        }
    }
}

document.getElementById('quotation-attached')?.addEventListener('change', updateQuotationReason);
document.querySelectorAll('[data-purchase-line]').forEach(bindPurchaseLine);
updatePurchaseEmptyState();
updateQuotationReason();
</script>
