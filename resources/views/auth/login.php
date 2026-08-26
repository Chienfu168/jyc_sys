<?php
$active = 'auth';
ob_start();
?>
<section class="login-shell">
    <div class="login-intro">
        <div class="login-brand">
            <div class="brand-mark">基</div>
            <div>
                <p class="eyebrow">私網管理系統</p>
                <h1><?= e(config('app.name')) ?></h1>
            </div>
        </div>

        <div class="login-copy">
            <h2>非營利組織內部營運管理平台</h2>
            <p>
                本系統供組織內部人員使用，協助基金會處理財務、零用金、銀行帳戶、年度預算、工作計畫、人事請假、活動、專案與簽核流程。
            </p>
        </div>

        <div class="login-feature-grid">
            <div>
                <strong>財務與會計</strong>
                <span>收支紀錄、零用金、銀行帳戶、會計傳票與預算執行。</span>
            </div>
            <div>
                <strong>治理文件</strong>
                <span>年度預算、工作計畫、列印輸出與簽署欄位。</span>
            </div>
            <div>
                <strong>人事與活動</strong>
                <span>人員資料、請假、志工、講師、活動與行事曆管理。</span>
            </div>
            <div>
                <strong>簽核中心</strong>
                <span>集中處理待審、核准、退回與歷史紀錄。</span>
            </div>
        </div>

        <p class="login-built-by">由游榮吉教育基金會建構</p>
    </div>

    <div class="auth-card login-card">
        <div class="auth-heading">
            <div>
                <p class="eyebrow">內部人員登入</p>
                <h2>登入系統</h2>
            </div>
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
            <label class="login-remember">
                <input type="checkbox" name="remember" value="1"<?= old('remember') ? ' checked' : '' ?>>
                <span>記住我（此裝置約 <?= e((string) config('security.remember_lifetime_days', 30)) ?> 天內免重新登入，適合安裝為 App 使用；請勿在公用電腦勾選）</span>
            </label>
            <button class="btn primary full" type="submit">登入</button>
            <a class="text-link" href="/forgot-password">忘記密碼</a>
        </form>
        <p class="login-security-note">本系統屬私網管理用途，僅限授權帳號使用。</p>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
