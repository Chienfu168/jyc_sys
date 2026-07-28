<form method="post" action="<?= e($action) ?>" class="form grid-form">
    <?= csrf_field() ?>
    <label>
        <span>姓名</span>
        <input type="text" name="name" value="<?= e(old('name', $user['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="<?= e(old('email', $user['email'] ?? '')) ?>" required>
    </label>
    <label>
        <span>角色</span>
        <select name="role_id" required>
            <option value="">請選擇</option>
            <?php foreach ($roles as $role): ?>
                <?php $selected = (string) old('role_id', $user['role_id'] ?? '') === (string) $role['id']; ?>
                <option value="<?= e((string) $role['id']) ?>" <?= $selected ? 'selected' : '' ?>><?= e($role['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $user['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>停用</option>
        </select>
    </label>
    <label class="span-2">
        <span><?= $user ? '新密碼（不變更可留空）' : '密碼' ?></span>
        <input type="password" name="password" autocomplete="new-password" <?= $user ? '' : 'required' ?>>
    </label>
    <div class="form-actions span-2">
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
