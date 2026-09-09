<?php
$active = 'contacts';
$contact = $contact ?? [];
$categories = $categories ?? [];
$isEdit = !empty($contact['id']);
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= $isEdit ? '編輯聯絡人' : '新增聯絡人' ?></h2>
            <p class="muted-text">共用通訊錄資料,建立後全機構具檢視權限者皆可查閱。</p>
        </div>
        <a class="btn" href="/contacts">返回通訊錄</a>
    </div>

    <form method="post" action="<?= e($action) ?>" class="form">
        <?= csrf_field() ?>

        <div class="grid-form">
            <label>
                <span>姓名 <span style="color:#b32d2d">*</span></span>
                <input type="text" name="name" maxlength="120" required value="<?= e((string) old('name', $contact['name'] ?? '')) ?>">
            </label>
            <label>
                <span>分類</span>
                <input type="text" name="category" maxlength="60" list="contactCategories" placeholder="例如：學校、政府機關" value="<?= e((string) old('category', $contact['category'] ?? '')) ?>">
                <datalist id="contactCategories">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label>
                <span>單位／學校名稱</span>
                <input type="text" name="organization" maxlength="160" value="<?= e((string) old('organization', $contact['organization'] ?? '')) ?>">
            </label>
            <label>
                <span>職稱</span>
                <input type="text" name="job_title" maxlength="120" value="<?= e((string) old('job_title', $contact['job_title'] ?? '')) ?>">
            </label>
            <label>
                <span>電話</span>
                <input type="text" name="phone" maxlength="60" value="<?= e((string) old('phone', $contact['phone'] ?? '')) ?>">
            </label>
            <label>
                <span>手機</span>
                <input type="text" name="mobile" maxlength="60" value="<?= e((string) old('mobile', $contact['mobile'] ?? '')) ?>">
            </label>
            <label>
                <span>傳真</span>
                <input type="text" name="fax" maxlength="60" value="<?= e((string) old('fax', $contact['fax'] ?? '')) ?>">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" maxlength="190" value="<?= e((string) old('email', $contact['email'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>地址</span>
                <input type="text" name="address" maxlength="255" value="<?= e((string) old('address', $contact['address'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes" rows="3"><?= e((string) old('notes', $contact['notes'] ?? '')) ?></textarea>
            </label>
            <label>
                <span>狀態</span>
                <select name="status">
                    <?php $st = (string) old('status', $contact['status'] ?? 'active'); ?>
                    <option value="active" <?= $st !== 'inactive' ? 'selected' : '' ?>>啟用</option>
                    <option value="inactive" <?= $st === 'inactive' ? 'selected' : '' ?>>已封存</option>
                </select>
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/contacts">取消</a>
            <button class="btn primary" type="submit"><?= $isEdit ? '儲存變更' : '建立聯絡人' ?></button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
