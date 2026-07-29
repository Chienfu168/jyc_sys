<?php
$active = 'calendar';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>新增行事曆事件</h2>
            <p class="muted-text">建立會議、活動、期限與專案提醒，供內部排程與列印留存。</p>
        </div>
    </div>
    <?php require base_path('resources/views/calendar/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
