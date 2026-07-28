<?php
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($title) ?></h2>
            <p class="muted-text">此模組入口已建立。後續可依年度預算模組的模式擴充資料表、表單、審核流程、報表與附件。</p>
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

<section class="panel print-help">
    <h2>輸出與歸檔</h2>
    <p class="muted-text">本功能頁已支援列印。點選上方「列印 / 另存 PDF」後，在瀏覽器列印視窗選擇印表機，或選擇「另存為 PDF」即可產生 PDF 檔。</p>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
