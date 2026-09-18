<?php
$active = 'payment-receipts';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <p class="eyebrow">領款收據</p>
            <h2><?= !empty($duplicateFromNo) ? '複製領款收據' : '新增領款收據' ?></h2>
            <?php if (!empty($duplicateFromNo)): ?>
                <p class="muted-text">由 <?= e($duplicateFromNo) ?> 複製;日期已預設為今天,請確認內容後建立,將另產生新單號。</p>
            <?php endif; ?>
        </div>
    </div>
    <?php require base_path('resources/views/payment-receipts/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
