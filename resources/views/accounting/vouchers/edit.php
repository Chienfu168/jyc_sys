<?php
$active = 'accounting';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯會計傳票</h2>
            <p class="muted-text"><?= e($voucher['voucher_no']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/accounting/vouchers/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
