<?php
ob_start();
?>
<section class="panel narrow">
    <h2>找不到頁面</h2>
    <p class="muted-text">請確認網址是否正確。</p>
    <a class="btn" href="/">返回儀表板</a>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
