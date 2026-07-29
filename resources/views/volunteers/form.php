<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>姓名</span>
        <input type="text" name="name" value="<?= e((string) old('name', $volunteer['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $volunteer['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>停用</option>
        </select>
    </label>
    <label>
        <span>電話</span>
        <input type="text" name="phone" value="<?= e((string) old('phone', $volunteer['phone'] ?? '')) ?>">
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="<?= e((string) old('email', $volunteer['email'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $volunteer['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/volunteers">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
