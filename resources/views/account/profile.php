<?php
$active = 'account-profile';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <h2>我的帳號資料</h2>
    </div>
    <form class="form grid-form" method="post" action="/account/profile">
        <?= csrf_field() ?>
        <label>
            <span>姓名</span>
            <input type="text" name="name" value="<?= e((string) old('name', $user['name'] ?? '')) ?>" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" value="<?= e((string) old('email', $user['email'] ?? '')) ?>" required>
        </label>
        <label>
            <span>電話</span>
            <input type="text" name="phone" value="<?= e((string) old('phone', $user['phone'] ?? '')) ?>">
        </label>
        <label>
            <span>員工編號</span>
            <input type="text" name="employee_no" value="<?= e((string) old('employee_no', $user['employee_no'] ?? '')) ?>">
        </label>
        <label>
            <span>部門</span>
            <input type="text" name="department" value="<?= e((string) old('department', $user['department'] ?? '')) ?>">
        </label>
        <label>
            <span>職稱</span>
            <input type="text" name="job_title" value="<?= e((string) old('job_title', $user['job_title'] ?? '')) ?>">
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="profile_notes"><?= e((string) old('profile_notes', $user['profile_notes'] ?? '')) ?></textarea>
        </label>
        <div class="form-actions span-2">
            <button class="btn primary" type="submit">儲存</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
