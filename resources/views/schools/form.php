<?php
$active = 'schools';
$school = $school ?? [];
$categories = $categories ?? [];
$isEdit = !empty($school['id']);
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= $isEdit ? '編輯學校' : '新增學校' ?></h2>
            <p class="muted-text">學校通訊錄資料，建立後全機構具檢視權限者皆可查閱。</p>
        </div>
        <a class="btn" href="/schools">返回學校通訊錄</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label>
                <span>學校名稱 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="name" maxlength="120" required value="<?= e((string) old('name', $school['name'] ?? '')) ?>">
            </label>
            <label>
                <span>類別</span>
                <input type="text" name="category" maxlength="60" list="schoolCategories" placeholder="例如：國小、國中" value="<?= e((string) old('category', $school['category'] ?? '')) ?>">
                <datalist id="schoolCategories">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label>
                <span>地區類別</span>
                <?php $areaType = (string) old('area_type', $school['area_type'] ?? ''); ?>
                <select name="area_type">
                    <option value="">未分類</option>
                    <?php foreach ($areaTypes as $at): ?>
                        <option value="<?= e($at) ?>" <?= $areaType === $at ? 'selected' : '' ?>><?= e($at) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>互動狀態</span>
                <?php $engagementStatus = (string) old('engagement_status', $school['engagement_status'] ?? '尚未接觸'); ?>
                <select name="engagement_status">
                    <?php foreach ($engagementStatuses as $es): ?>
                        <option value="<?= e($es) ?>" <?= $engagementStatus === $es ? 'selected' : '' ?>><?= e($es) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>電話</span>
                <input type="text" name="phone" maxlength="60" value="<?= e((string) old('phone', $school['phone'] ?? '')) ?>">
            </label>
            <label>
                <span>傳真</span>
                <input type="text" name="fax" maxlength="60" value="<?= e((string) old('fax', $school['fax'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>地址</span>
                <input type="text" name="address" maxlength="255" value="<?= e((string) old('address', $school['address'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="3"><?= e((string) old('notes', $school['notes'] ?? '')) ?></textarea>
            </label>
            <label>
                <span>狀態</span>
                <select name="status">
                    <?php $st = (string) old('status', $school['status'] ?? 'active'); ?>
                    <option value="active" <?= $st !== 'inactive' ? 'selected' : '' ?>>啟用</option>
                    <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>已封存</option>
                </select>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/schools">取消</a>
            <button class="btn primary" type="submit"><?= $isEdit ? '儲存變更' : '建立學校' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
