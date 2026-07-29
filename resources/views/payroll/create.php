<?php
$active = 'payroll';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增薪資紀錄</h2>
            <p class="muted-text">建立單一人員月份薪資，計算應發、扣款與實發。</p>
        </div>
    </div>
    <?php require base_path('resources/views/payroll/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
