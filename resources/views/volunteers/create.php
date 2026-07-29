<?php
$active = 'volunteers';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增志工</h2>
            <p class="muted-text">建立志工基本資料，供服務時數與活動支援紀錄使用。</p>
        </div>
    </div>
    <?php require base_path('resources/views/volunteers/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
