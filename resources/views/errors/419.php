<?php
ob_start();
?>
<section class="panel narrow">
    <h2>表單已失效</h2>
    <p class="muted-text">請返回上一頁重新送出。</p>
    <a class="btn" href="/">返回儀表板</a>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
