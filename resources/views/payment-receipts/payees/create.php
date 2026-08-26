<?php
$active = 'payment-receipts';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <h2>新增常用領款人</h2>
        <a class="btn" href="/payment-receipt-payees">返回清單</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
