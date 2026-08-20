<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>類型</span>
        <?php $type = old('donor_type', $donor['donor_type'] ?? 'person'); ?>
        <select name="donor_type" required>
            <option value="person" <?= $type === 'person' ? 'selected' : '' ?>>個人</option>
            <option value="organization" <?= $type === 'organization' ? 'selected' : '' ?>>組織</option>
        </select>
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $donor['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>封存</option>
        </select>
    </label>
    <label class="span-2">
        <span>姓名 / 單位名稱</span>
        <input type="text" name="name" value="<?= e((string) old('name', $donor['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>電話</span>
        <input type="text" name="phone" value="<?= e((string) old('phone', $donor['phone'] ?? '')) ?>">
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="<?= e((string) old('email', $donor['email'] ?? '')) ?>">
    </label>
    <label>
        <span>收據抬頭</span>
        <input type="text" name="receipt_title" value="<?= e((string) old('receipt_title', $donor['receipt_title'] ?? '')) ?>">
    </label>
    <label>
        <span>統編 / 身分證字號</span>
        <input type="text" name="tax_id" value="<?= e((string) old('tax_id', $donor['tax_id'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>地址</span>
        <input type="text" name="address" value="<?= e((string) old('address', $donor['address'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $donor['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/donors">返回列表</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
