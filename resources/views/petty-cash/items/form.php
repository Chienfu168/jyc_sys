<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>類型</span>
        <?php $type = old('item_type', $item['item_type'] ?? 'expense'); ?>
        <select name="item_type" required>
            <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>支出</option>
            <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>收入</option>
        </select>
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $item['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>停用</option>
        </select>
    </label>
    <label class="span-2">
        <span>項目名稱</span>
        <input type="text" name="name" value="<?= e((string) old('name', $item['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>預設金額</span>
        <input type="number" step="1" min="0" name="default_amount" value="<?= e((string) old('default_amount', $item['default_amount'] ?? '')) ?>">
    </label>
    <label>
        <span>排序</span>
        <input type="number" min="0" name="sort_order" value="<?= e((string) old('sort_order', $item['sort_order'] ?? 10)) ?>">
    </label>
    <div class="form-actions span-2">
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
