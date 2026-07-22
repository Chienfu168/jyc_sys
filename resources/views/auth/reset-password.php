<?php
ob_start();
?>
<section class="auth-panel">
    <div class="auth-card">
        <div class="auth-heading">
            <div class="brand-mark">基</div>
            <h1>重設密碼</h1>
        </div>
        <form method="post" action="/reset-password" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($email) ?>" required>
            </label>
            <label>
                <span>新密碼</span>
                <input type="password" name="password" autocomplete="new-password" required>
            </label>
            <label>
                <span>確認密碼</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>
            <button class="btn primary full" type="submit">更新密碼</button>
            <a class="text-link" href="/login">返回登入</a>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
