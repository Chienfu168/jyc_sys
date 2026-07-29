<?php
$active = 'accounting';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯會計科目</h2>
            <p class="muted-text"><?= e($account['code'] . ' ' . $account['name']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/accounting/accounts/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
