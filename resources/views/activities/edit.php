<?php
$active = 'activities';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯活動</h2>
            <p class="muted-text"><?= e($activity['title']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/activities/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
