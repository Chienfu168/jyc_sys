<?php
$active = 'projects';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增專案</h2>
            <p class="muted-text">建立專案目標、期間、承辦與預算基礎資料，後續可串接活動、工作計畫與費用資料。</p>
        </div>
    </div>
    <?php require base_path('resources/views/projects/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
