<?php
$active = 'travel-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯出差費用</h2>
            <p class="muted-text"><?= e($expense['traveler_name']) ?> / <?= e($expense['destination']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/travel-expenses/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
