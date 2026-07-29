<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>科目代碼</span>
        <input type="text" name="code" value="<?= e((string) old('code', $account['code'] ?? '')) ?>" required>
    </label>
    <label>
        <span>科目名稱</span>
        <input type="text" name="name" value="<?= e((string) old('name', $account['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>科目類型</span>
        <?php $type = old('account_type', $account['account_type'] ?? 'expense'); ?>
        <select name="account_type" required>
            <?php foreach (['asset' => '資產', 'liability' => '負債', 'net_asset' => '淨資產', 'income' => '收入', 'expense' => '支出'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $type === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>上層科目</span>
        <?php $parentId = (string) old('parent_id', $account['parent_id'] ?? ''); ?>
        <select name="parent_id">
            <option value="">無</option>
            <?php foreach ($parents as $parent): ?>
                <option value="<?= e((string) $parent['id']) ?>" <?= $parentId === (string) $parent['id'] ? 'selected' : '' ?>>
                    <?= e($parent['code'] . ' ' . $parent['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>借貸方向</span>
        <?php $normal = old('normal_balance', $account['normal_balance'] ?? 'debit'); ?>
        <select name="normal_balance" required>
            <option value="debit" <?= $normal === 'debit' ? 'selected' : '' ?>>借方</option>
            <option value="credit" <?= $normal === 'credit' ? 'selected' : '' ?>>貸方</option>
        </select>
    </label>
    <label>
        <span>排序</span>
        <input type="number" min="0" step="1" name="sort_order" value="<?= e((string) old('sort_order', $account['sort_order'] ?? 0)) ?>">
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $account['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>停用</option>
        </select>
    </label>
    <label class="span-2">
        <span>說明</span>
        <textarea name="description"><?= e((string) old('description', $account['description'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/accounting/accounts">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
