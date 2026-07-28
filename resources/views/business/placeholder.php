<?php
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($title) ?></h2>
            <p class="muted-text">此模組入口已建立，後續可依年度預算模組的模式擴充資料表、表單、審核與報表。</p>
        </div>
    </div>

    <div class="feature-grid">
        <?php foreach ($items as $item): ?>
            <div class="feature-card">
                <span>規劃項目</span>
                <strong><?= e($item) ?></strong>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
