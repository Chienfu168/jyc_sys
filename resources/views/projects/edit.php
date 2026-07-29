<?php
$active = 'projects';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯專案</h2>
            <p class="muted-text"><?= e($project['project_code'] ?: '-') ?> / <?= e($project['name']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/projects/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
