<?php
$active = 'accounting';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增會計傳票</h2>
            <p class="muted-text">分錄借貸合計必須相等，才能儲存並過帳。</p>
        </div>
    </div>
    <?php require base_path('resources/views/accounting/vouchers/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
