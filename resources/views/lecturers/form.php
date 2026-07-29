<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>姓名</span>
        <input type="text" name="name" value="<?= e((string) old('name', $lecturer['name'] ?? '')) ?>" required>
    </label>
    <label>
        <span>顯示名稱</span>
        <input type="text" name="display_name" value="<?= e((string) old('display_name', $lecturer['display_name'] ?? '')) ?>" placeholder="例：王雅婷老師">
    </label>
    <label>
        <span>身分證字號 / 統一編號</span>
        <input type="text" name="tax_id" value="<?= e((string) old('tax_id', $lecturer['tax_id'] ?? '')) ?>">
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $lecturer['status'] ?? 'active'); ?>
        <select name="status">
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>停用</option>
        </select>
    </label>
    <label>
        <span>電話</span>
        <input type="text" name="phone" value="<?= e((string) old('phone', $lecturer['phone'] ?? '')) ?>">
    </label>
    <label>
        <span>手機</span>
        <input type="text" name="mobile" value="<?= e((string) old('mobile', $lecturer['mobile'] ?? '')) ?>">
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="<?= e((string) old('email', $lecturer['email'] ?? '')) ?>">
    </label>
    <label>
        <span>常用鐘點費</span>
        <input type="number" min="0" step="1" name="hourly_rate" value="<?= e((string) old('hourly_rate', $lecturer['hourly_rate'] ?? '')) ?>">
    </label>
    <label>
        <span>服務單位</span>
        <input type="text" name="organization" value="<?= e((string) old('organization', $lecturer['organization'] ?? '')) ?>">
    </label>
    <label>
        <span>職稱</span>
        <input type="text" name="job_title" value="<?= e((string) old('job_title', $lecturer['job_title'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>專長</span>
        <input type="text" name="specialty" list="lecturer-specialties" value="<?= e((string) old('specialty', $lecturer['specialty'] ?? '')) ?>" required>
        <datalist id="lecturer-specialties">
            <option value="親職教育"></option>
            <option value="兒童陪伴"></option>
            <option value="社區營造"></option>
            <option value="閱讀素養"></option>
            <option value="數位學習"></option>
            <option value="健康促進"></option>
        </datalist>
    </label>
    <label class="span-2">
        <span>地址</span>
        <input type="text" name="address" value="<?= e((string) old('address', $lecturer['address'] ?? '')) ?>">
    </label>
    <label>
        <span>匯款銀行</span>
        <input type="text" name="bank_name" value="<?= e((string) old('bank_name', $lecturer['bank_name'] ?? '')) ?>">
    </label>
    <label>
        <span>分行</span>
        <input type="text" name="bank_branch" value="<?= e((string) old('bank_branch', $lecturer['bank_branch'] ?? '')) ?>">
    </label>
    <label>
        <span>帳號</span>
        <input type="text" name="bank_account_no" value="<?= e((string) old('bank_account_no', $lecturer['bank_account_no'] ?? '')) ?>">
    </label>
    <label>
        <span>戶名</span>
        <input type="text" name="bank_account_name" value="<?= e((string) old('bank_account_name', $lecturer['bank_account_name'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $lecturer['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/lecturers">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
