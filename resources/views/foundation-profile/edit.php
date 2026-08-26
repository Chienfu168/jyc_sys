<?php
ob_start();
?>
<section class="panel narrow">
    <form class="form grid-form" method="post" action="/foundation-profile" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="span-2">
            <span>基金會名稱</span>
            <input type="text" name="foundation_name" value="<?= e((string) old('foundation_name', $profile['foundation_name'] ?? '')) ?>" required>
        </label>
        <label class="span-2">
            <span>英文名稱</span>
            <input type="text" name="english_name" value="<?= e((string) old('english_name', $profile['english_name'] ?? '')) ?>">
        </label>
        <label>
            <span>統一編號</span>
            <input type="text" name="tax_id" value="<?= e((string) old('tax_id', $profile['tax_id'] ?? '')) ?>">
        </label>
        <label>
            <span>設立 / 登記字號</span>
            <input type="text" name="registration_no" value="<?= e((string) old('registration_no', $profile['registration_no'] ?? '')) ?>">
        </label>
        <label>
            <span>主管機關</span>
            <input type="text" name="competent_authority" value="<?= e((string) old('competent_authority', $profile['competent_authority'] ?? '')) ?>">
        </label>
        <label>
            <span>核准日期</span>
            <input type="date" name="approval_date" value="<?= e((string) old('approval_date', $profile['approval_date'] ?? '')) ?>">
        </label>
        <label class="span-2">
            <span>核准文號</span>
            <input type="text" name="approval_doc_no" value="<?= e((string) old('approval_doc_no', $profile['approval_doc_no'] ?? '')) ?>">
        </label>
        <label>
            <span>董事長</span>
            <input type="text" name="representative" value="<?= e((string) old('representative', $profile['representative'] ?? '')) ?>">
        </label>
        <label>
            <span>執行長</span>
            <input type="text" name="executive_director" value="<?= e((string) old('executive_director', $profile['executive_director'] ?? '')) ?>">
        </label>
        <label>
            <span>承辦</span>
            <input type="text" name="undertaker" value="<?= e((string) old('undertaker', $profile['undertaker'] ?? '')) ?>">
        </label>
        <label>
            <span>會計年度起始月份</span>
            <input type="number" name="fiscal_year_start_month" min="1" max="12" value="<?= e((string) old('fiscal_year_start_month', $profile['fiscal_year_start_month'] ?? '1')) ?>">
        </label>
        <label>
            <span>電話</span>
            <input type="text" name="phone" value="<?= e((string) old('phone', $profile['phone'] ?? '')) ?>">
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?= e((string) old('email', $profile['email'] ?? '')) ?>">
        </label>
        <label>
            <span>網址</span>
            <input type="text" name="website" value="<?= e((string) old('website', $profile['website'] ?? '')) ?>" placeholder="http://www.example.org">
        </label>
        <div class="span-2 logo-field">
            <span class="logo-field__label">機構 LOGO</span>
            <?php if (!empty($profile['logo_path'])): ?>
                <?php $logoUri = foundation_logo_data_uri(); ?>
                <?php if ($logoUri !== null): ?>
                    <div class="logo-field__preview">
                        <img src="<?= e($logoUri) ?>" alt="目前 LOGO">
                    </div>
                    <label class="logo-field__remove">
                        <input type="checkbox" name="remove_logo" value="1">
                        <span>移除目前的 LOGO</span>
                    </label>
                <?php endif; ?>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">
            <small class="logo-field__hint">支援 PNG／JPG／GIF／WEBP,建議寬高比接近正方形,檔案 5MB 以內。上傳後將顯示於各項列印報表抬頭。</small>
        </div>
        <label class="span-2">
            <span>會址</span>
            <input type="text" name="address" value="<?= e((string) old('address', $profile['address'] ?? '')) ?>">
        </label>
        <label class="span-2">
            <span>通訊地址</span>
            <input type="text" name="mailing_address" value="<?= e((string) old('mailing_address', $profile['mailing_address'] ?? '')) ?>" placeholder="未填時文件將使用會址">
        </label>
        <label class="span-2">
            <span>宗旨 / 任務</span>
            <textarea name="mission"><?= e((string) old('mission', $profile['mission'] ?? '')) ?></textarea>
        </label>
        <label class="span-2">
            <span>服務區域 / 服務對象</span>
            <textarea name="service_area"><?= e((string) old('service_area', $profile['service_area'] ?? '')) ?></textarea>
        </label>
        <div class="form-actions span-2">
            <?php if (\App\Core\Permission::can('foundation_profile.manage')): ?>
                <button class="btn primary" type="submit">儲存</button>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
