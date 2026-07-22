<?php
ob_start();
?>
<section class="auth-panel">
    <div class="auth-card">
        <div class="auth-heading">
            <div class="brand-mark">基</div>
            <h1>忘記密碼</h1>
        </div>
        <form method="post" action="/forgot-password" class="form">
            <?= csrf_field() ?>
            <label>
                <span>Email</span>
                <input type="email" name="email" autocomplete="email" required>
            </label>
            <button class="btn primary full" type="submit">送出</button>
            <a class="text-link" href="/login">返回登入</a>
        </form>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
