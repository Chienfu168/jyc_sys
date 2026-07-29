<?php
$active = 'volunteers';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增服務時數</h2>
            <p class="muted-text"><?= e($volunteer['name']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/volunteer-service-logs/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
