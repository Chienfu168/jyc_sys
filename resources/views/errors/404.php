<?php
ob_start();
?>
<section class="panel narrow">
    <h2>頁面不存在</h2>
    <p class="muted-text">請確認網址是否正確，或返回總儀表板。</p>
    <a class="btn" href="/">返回總儀表板</a>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
