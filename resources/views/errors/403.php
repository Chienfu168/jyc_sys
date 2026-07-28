<?php
ob_start();
?>
<section class="panel narrow">
    <h2>沒有權限</h2>
    <p class="muted-text">你目前沒有權限檢視這個頁面。</p>
    <a class="btn" href="/">返回總儀表板</a>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
