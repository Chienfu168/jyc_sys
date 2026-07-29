<?php
$active = 'personnel';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增人事資料</h2>
            <p class="muted-text">建立員工、兼職或約聘人員資料，供薪資、請假與出差作業使用。</p>
        </div>
    </div>
    <?php require base_path('resources/views/personnel/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
