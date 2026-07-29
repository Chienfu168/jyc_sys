<?php
$active = 'calendar';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯行事曆事件</h2>
            <p class="muted-text"><?= e(roc_date(substr((string) $event['starts_at'], 0, 10)) . ' ' . substr((string) $event['starts_at'], 11, 5)) ?> / <?= e($event['title']) ?></p>
        </div>
    </div>
    <?php require base_path('resources/views/calendar/form.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
