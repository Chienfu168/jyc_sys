<?php
$active = 'leave-requests';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= !empty($duplicateFromName) ? '複製請假申請' : '新增請假申請' ?></h2>
            <?php if (!empty($duplicateFromName)): ?>
                <p class="muted-text">由「<?= e($duplicateFromName) ?>」的請假單複製;日期已預設為今天,請確認內容後建立。</p>
            <?php else: ?>
                <p class="muted-text">建立請假期間、假別、代理人與審核狀態。</p>
            <?php endif; ?>
        </div>
    </div>
    <?php require base_path('resources/views/leave-requests/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
