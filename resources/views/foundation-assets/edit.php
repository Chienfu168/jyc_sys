<?php
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>編輯財產</h2>
            <p class="muted-text"><?= e($asset['asset_name']) ?></p>
        </div>
        <a class="btn" href="/foundation-assets/<?= e((string) $asset['id']) ?>">返回檢視</a>
    </div>
    <?php require __DIR__ . '/form.php'; ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
