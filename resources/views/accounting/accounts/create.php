<?php
$active = 'accounting';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增會計科目</h2>
            <p class="muted-text">請依照總帳分類設定科目代碼與借貸方向。</p>
        </div>
    </div>
    <?php require base_path('resources/views/accounting/accounts/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
