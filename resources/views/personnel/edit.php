<?php
$active = 'personnel';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯人事資料</h2>
            <p class="muted-text"><?= e($employee['employee_no'] ?: '-') ?> / <?= e($employee['name']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/personnel/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
