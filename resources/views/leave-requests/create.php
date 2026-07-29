<?php
$active = 'leave-requests';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增請假申請</h2>
            <p class="muted-text">建立請假期間、假別、代理人與審核狀態。</p>
        </div>
    </div>
    <?php require base_path('resources/views/leave-requests/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
