<?php
$active = 'lecturers';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增講師</h2>
            <p class="muted-text">建立講師基本資料，供活動、專案與講師費作業使用。</p>
        </div>
    </div>
    <?php require base_path('resources/views/lecturers/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
