<?php
$active = 'auth';
ob_start();
?>
<section class="auth-panel">
    <div class="auth-card">
        <div class="auth-heading">
            <div class="brand-mark">基</div>
            <h1><?= e(config('app.name')) ?></h1>
        </div>
        <form method="post" action="/login" class="form">
            <?= csrf_field() ?>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required>
            </label>
            <label>
                <span>密碼</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <label>
                <span>驗證碼</span>
                <div class="captcha-row">
                    <strong><?= e($captcha ?? '') ?></strong>
                    <input type="text" name="captcha" inputmode="numeric" autocomplete="off" required>
                </div>
            </label>
            <button class="btn primary full" type="submit">登入</button>
            <a class="text-link" href="/forgot-password">忘記密碼</a>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
