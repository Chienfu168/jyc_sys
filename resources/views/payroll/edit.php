<?php
$active = 'payroll';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯薪資紀錄</h2>
            <p class="muted-text"><?= e(($record['payroll_month'] ?? '') . ' / ' . ($record['employee_name'] ?? '')) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/payroll/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
