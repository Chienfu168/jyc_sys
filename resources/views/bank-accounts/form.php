<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>銀行名稱</span>
        <input type="text" name="bank_name" value="<?= e((string) old('bank_name', $account['bank_name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>分行</span>
        <input type="text" name="branch_name" value="<?= e((string) old('branch_name', $account['branch_name'] ?? '')) ?>">
    </label>
    <label>
        <span>戶名</span>
        <input type="text" name="account_name" value="<?= e((string) old('account_name', $account['account_name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>帳號</span>
        <input type="text" name="account_no" value="<?= e((string) old('account_no', $account['account_no'] ?? '')) ?>" required>
    </label>
    <label>
        <span>幣別</span>
        <input type="text" name="currency" maxlength="10" value="<?= e((string) old('currency', $account['currency'] ?? 'TWD')) ?>" required>
    </label>
    <label>
        <span>期初餘額</span>
        <input type="number" step="1" min="0" name="opening_balance" value="<?= e((string) old('opening_balance', $account['opening_balance'] ?? '0')) ?>">
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
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $account['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/bank-accounts">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
