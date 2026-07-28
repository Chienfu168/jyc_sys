<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>日期</span>
        <input type="date" name="occurred_on" value="<?= e((string) old('occurred_on', $entry['occurred_on'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>類型</span>
        <?php $type = old('item_type', $entry['item_type'] ?? 'expense'); ?>
        <select name="item_type" id="petty-cash-type" required>
            <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>支出</option>
            <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>收入</option>
        </select>
    </label>
    <label class="span-2">
        <span>常用項目</span>
        <?php $selectedItem = (string) old('petty_cash_item_id', $entry['petty_cash_item_id'] ?? ''); ?>
        <select name="petty_cash_item_id" id="petty-cash-item">
            <option value="">自行輸入項目</option>
            <?php foreach ($items as $item): ?>
                <option
                    value="<?= e((string) $item['id']) ?>"
                    data-type="<?= e($item['item_type']) ?>"
                    data-name="<?= e($item['name']) ?>"
                    data-amount="<?= e((string) ($item['default_amount'] ?? '')) ?>"
                    <?= $selectedItem === (string) $item['id'] ? 'selected' : '' ?>
                >
                    <?= e(($item['item_type'] === 'income' ? '收入' : '支出') . ' - ' . $item['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>項目名稱</span>
        <input type="text" name="item_name" id="petty-cash-name" value="<?= e((string) old('item_name', $entry['item_name'] ?? '')) ?>">
    </label>
    <label>
        <span>金額</span>
        <input type="number" step="1" min="1" name="amount" id="petty-cash-amount" value="<?= e((string) old('amount', $entry['amount'] ?? '')) ?>" required>
    </label>
    <label>
        <span>支付 / 收款對象</span>
        <input type="text" name="payment_to" value="<?= e((string) old('payment_to', $entry['payment_to'] ?? '')) ?>">
    </label>
    <label>
        <span>單據號碼</span>
        <input type="text" name="receipt_no" value="<?= e((string) old('receipt_no', $entry['receipt_no'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>登錄帳號</span>
        <input type="text" value="<?= e((string) ($entry['created_by_name'] ?? auth()->user()['name'] ?? '')) ?>" readonly>
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $entry['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
document.getElementById('petty-cash-item')?.addEventListener('change', function () {
    const option = this.selectedOptions[0];
    if (!option || !option.value) {
        return;
    }
    document.getElementById('petty-cash-type').value = option.dataset.type || 'expense';
    document.getElementById('petty-cash-name').value = option.dataset.name || '';
    if (option.dataset.amount) {
        document.getElementById('petty-cash-amount').value = option.dataset.amount;
    }
});
</script>
