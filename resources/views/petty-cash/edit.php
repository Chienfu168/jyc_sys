<?php
$active = 'petty-cash';
ob_start();
?>
<section class="panel narrow">
    <div class="panel-header">
        <h2>編輯零用金紀錄</h2>
        <a class="btn" href="/petty-cash?month=<?= e(substr((string) $entry['occurred_on'], 0, 7)) ?>">返回清單</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
