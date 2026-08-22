<div class="purchase-line-grid">
    <label>
        <span>品名</span>
        <input type="text" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
    </label>
    <label>
        <span>規格</span>
        <input type="text" name="items[<?= e((string) $index) ?>][specification]" value="<?= e((string) ($item['specification'] ?? '')) ?>">
    </label>
    <label>
        <span>數量</span>
        <input type="number" step="0.01" min="0" data-purchase-field="quantity" data-purchase-auto name="items[<?= e((string) $index) ?>][quantity]" value="<?= e((string) ($item['quantity'] ?? '1')) ?>">
    </label>
    <label>
        <span>單價</span>
        <input type="number" step="1" min="0" data-purchase-field="unit_price" data-purchase-auto name="items[<?= e((string) $index) ?>][unit_price]" value="<?= e((string) ($item['unit_price'] ?? '')) ?>">
    </label>
    <label>
        <span>金額</span>
        <input type="number" step="1" min="0" data-purchase-field="amount" name="items[<?= e((string) $index) ?>][amount]" value="<?= e((string) ($item['amount'] ?? '')) ?>">
    </label>
    <label>
        <span>備註</span>
        <input type="text" name="items[<?= e((string) $index) ?>][notes]" value="<?= e((string) ($item['notes'] ?? '')) ?>">
    </label>
    <button class="btn small" type="button" onclick="removePurchaseLine(this)">刪除</button>
</div>
