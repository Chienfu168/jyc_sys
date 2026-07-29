<?php
$active = 'leave-requests';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯請假申請</h2>
            <p class="muted-text"><?= e($request['employee_name'] ?? '') ?> / <?= e($request['reason'] ?? '') ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/leave-requests/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
