<?php
$role = $role ?? null;
$isSystemAdminRole = isset($role['id']) && (int) $role['id'] === 1;
$selected = old('permissions', $selectedPermissions ?? []);
if (!is_array($selected)) {
    $selected = [];
}
$selected = array_flip(array_map('strval', $selected));
?>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>角色名稱</span>
            <input
                type="text"
                name="name"
                value="<?= e(old('name', $role['name'] ?? '')) ?>"
                <?= $isSystemAdminRole ? 'readonly' : '' ?>
                required
            >
        </label>
        <label>
            <span>說明</span>
            <input type="text" name="description" value="<?= e(old('description', $role['description'] ?? '')) ?>">
        </label>
    </div>

    <div>
        <div class="panel-header">
            <div>
                <h2>權限勾選</h2>
                <p class="muted-text">依模組勾選此角色可使用的功能。系統管理員名稱固定，仍保留全系統通行權。</p>
            </div>
        </div>

        <div class="module-grid">
            <?php foreach ($permissions as $module => $items): ?>
                <div class="feature-card">
                    <span><?= e($module) ?></span>
                    <?php foreach ($items as $permission): ?>
                        <?php $code = (string) $permission['code']; ?>
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="<?= e($code) ?>"
                                <?= isset($selected[$code]) ? 'checked' : '' ?>
                                <?= $isSystemAdminRole ? 'disabled' : '' ?>
                            >
                            <?= e($permission['name'] . '（' . $code . '）') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn primary" type="submit">儲存角色權限</button>
    </div>
</form>
