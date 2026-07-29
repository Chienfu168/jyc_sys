<?php
$active = 'lecturer-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯講師費用</h2>
            <p class="muted-text"><?= e($expense['service_title']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/lecturer-expenses/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
