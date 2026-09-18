<?php
$active = 'donations';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <div>
            <h2><?= !empty($duplicateFromName) ? '複製捐款' : '新增捐款' ?></h2>
            <?php if (!empty($duplicateFromName)): ?>
                <p class="muted-text">由「<?= e($duplicateFromName) ?>」的捐款複製;日期已預設為今天,收據狀態重設為未開立,請確認內容後建立。</p>
            <?php endif; ?>
        </div>
        <a class="btn" href="/donations">返回</a>
    </div>
    <?php require base_path('resources/views/donations/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
