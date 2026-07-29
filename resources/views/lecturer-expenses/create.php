<?php
$active = 'lecturer-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增講師費用</h2>
            <p class="muted-text">依講師、服務內容與時數建立請款資料。</p>
        </div>
    </div>
    <?php require base_path('resources/views/lecturer-expenses/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
