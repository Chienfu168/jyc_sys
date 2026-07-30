<label>
    <span>類型</span>
    <select name="items[<?= e((string) $index) ?>][item_type]">
        <option value="income" <?= ($item['item_type'] ?? '') === 'income' ? 'selected' : '' ?>>收益</option>
        <option value="expense" <?= ($item['item_type'] ?? '') === 'expense' ? 'selected' : '' ?>>費損</option>
    </select>
</label>
<label>
    <span>款</span>
    <input type="text" name="items[<?= e((string) $index) ?>][gov_level1]" value="<?= e((string) ($item['gov_level1'] ?? '')) ?>">
</label>
<label>
    <span>項</span>
    <input type="text" name="items[<?= e((string) $index) ?>][gov_level2]" value="<?= e((string) ($item['gov_level2'] ?? '')) ?>">
</label>
<label>
    <span>目</span>
    <input type="text" name="items[<?= e((string) $index) ?>][gov_level3]" value="<?= e((string) ($item['gov_level3'] ?? '')) ?>">
</label>
<label>
    <span>次</span>
    <input type="text" name="items[<?= e((string) $index) ?>][gov_level4]" value="<?= e((string) ($item['gov_level4'] ?? '')) ?>">
</label>
<label>
    <span>節</span>
    <input type="text" name="items[<?= e((string) $index) ?>][gov_level5]" value="<?= e((string) ($item['gov_level5'] ?? '')) ?>">
</label>
<label>
    <span>分類</span>
    <input type="text" list="annual-budget-common-categories" name="items[<?= e((string) $index) ?>][category]" value="<?= e((string) ($item['category'] ?? '')) ?>">
</label>
<label>
    <span>項目名稱</span>
    <input type="text" list="annual-budget-common-items" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
</label>
<label>
    <span>會計科目</span>
    <?php $selectedAccountId = (int) ($item['account_id'] ?? 0); ?>
    <select name="items[<?= e((string) $index) ?>][account_id]">
        <option value="">未對應</option>
        <?php foreach (($accounts ?? []) as $account): ?>
            <option value="<?= e((string) $account['id']) ?>" <?= $selectedAccountId === (int) $account['id'] ? 'selected' : '' ?>>
                <?= e($account['code'] . ' ' . $account['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</label>
<label>
    <span>單位</span>
    <input type="text" name="items[<?= e((string) $index) ?>][unit]" value="<?= e((string) ($item['unit'] ?? '')) ?>">
</label>
<label>
    <span>數量</span>
    <input type="number" step="0.01" min="0" name="items[<?= e((string) $index) ?>][quantity]" value="<?= e((string) ($item['quantity'] ?? '1')) ?>">
</label>
<label>
    <span>單價</span>
    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][unit_price]" value="<?= e((string) ($item['unit_price'] ?? '')) ?>">
</label>
<label>
    <span>本年度預算</span>
    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][amount]" value="<?= e((string) ($item['amount'] ?? '')) ?>">
</label>
<label>
    <span>上年度預算</span>
    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][previous_amount]" value="<?= e((string) ($item['previous_amount'] ?? '')) ?>">
</label>
<label>
    <span>經費來源</span>
    <input type="text" name="items[<?= e((string) $index) ?>][funding_source]" value="<?= e((string) ($item['funding_source'] ?? '')) ?>">
</label>
<label>
    <span>比較備註</span>
    <input type="text" name="items[<?= e((string) $index) ?>][comparison_note]" value="<?= e((string) ($item['comparison_note'] ?? '')) ?>">
</label>
<label class="span-wide">
    <span>說明</span>
    <input type="text" name="items[<?= e((string) $index) ?>][description]" value="<?= e((string) ($item['description'] ?? '')) ?>">
</label>
<label class="span-wide">
    <span>備註</span>
    <input type="text" name="items[<?= e((string) $index) ?>][notes]" value="<?= e((string) ($item['notes'] ?? '')) ?>">
</label>
<label class="checkbox-label">
    <input type="checkbox" name="items[<?= e((string) $index) ?>][is_subtotal]" value="1" <?= !empty($item['is_subtotal']) ? 'checked' : '' ?>>
    <span>小計 / 合計列</span>
</label>
<button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
