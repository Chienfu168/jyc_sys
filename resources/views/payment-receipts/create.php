<?php
$active = 'payment-receipts';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">領款收據</p>
            <h2>新增領款收據</h2>
        </div>
    </div>
    <?php require base_path('resources/views/payment-receipts/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
