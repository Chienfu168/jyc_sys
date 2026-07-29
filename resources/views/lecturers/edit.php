<?php
$active = 'lecturers';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯講師</h2>
            <p class="muted-text"><?= e($lecturer['display_name'] ?: $lecturer['name']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/lecturers/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
