<?php
/**
 * 共用列印標籤頁面:將選取的項目(通訊錄、學校通訊錄、董事名冊等)輸出為
 * 三用電腦標籤規格(100mm×45mm,2欄×6列,每頁12格)。
 * 呼叫端(各模組 labels() 方法)需提供 $labels,每筆為
 * ['line1' => string, 'line2' => ?string, 'address' => ?string]。
 */
$active = $active ?? '';
$labels = $labels ?? [];
$backHref = $backHref ?? '/';
$documentTitle = $documentTitle ?? '列印標籤';
ob_start();
?>
<style>
/* 三用電腦標籤 100mm×45mm,2欄×6列,每頁12格:上邊界1.35cm、側邊界0.4cm、
   水平點距10.2cm(欄間縫2mm)、垂直點距4.5cm(列間無縫)。僅此文件採用,
   不影響其他列印頁面的 @page 邊界設定。 */
@media print {
    @page { size: A4; margin: 0; }
}

.label-toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.label-sheet-wrap {
    overflow-x: auto;
}

.label-page {
    width: 210mm;
    box-sizing: border-box;
    padding: 13.5mm 0 0 4mm;
    background: #fff;
}

.label-page + .label-page {
    page-break-before: always;
}

.label-grid {
    display: grid;
    grid-template-columns: 100mm 100mm;
    column-gap: 2mm;
    grid-auto-rows: 45mm;
    row-gap: 0;
}

.label-cell {
    width: 100mm;
    height: 45mm;
    box-sizing: border-box;
    padding: 5mm 6mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: center;
    border: 1px dashed #ccc;
}

.label-cell .label-line1 {
    font-size: 16px;
    font-weight: 700;
    line-height: 1.3;
}

.label-cell .label-line2 {
    font-size: 13px;
    margin-top: 3px;
}

.label-cell .label-address {
    font-size: 13px;
    margin-top: 6px;
    line-height: 1.5;
}

@media print {
    .label-cell {
        border: none;
    }
}
</style>

<div class="panel no-print">
    <div class="panel-header">
        <div>
            <h2>列印標籤</h2>
            <p class="muted-text">標籤規格：寬 100mm × 高 45mm，A4 紙 2 欄 × 6 列，每頁 12 格（上邊界 1.35cm、側邊界 0.4cm）。請依此規格購買對應的三用電腦標籤貼紙。</p>
        </div>
    </div>
    <div class="label-toolbar">
        <a class="btn" href="<?= e($backHref) ?>">返回</a>
        <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        <span class="muted-text">共 <?= e((string) count($labels)) ?> 張標籤，<?= e((string) max(1, (int) ceil(count($labels) / 12))) ?> 頁。</span>
    </div>
</div>

<div class="label-sheet-wrap">
<?php if (!$labels): ?>
    <p class="muted-text">尚未選擇任何項目，請返回列表勾選後再試一次。</p>
<?php else: ?>
    <?php foreach (array_chunk($labels, 12) as $chunk): ?>
        <div class="label-page">
            <div class="label-grid">
                <?php foreach ($chunk as $label): ?>
                    <div class="label-cell">
                        <div class="label-line1"><?= e((string) $label['line1']) ?></div>
                        <?php if (!empty($label['line2'])): ?>
                            <div class="label-line2"><?= e((string) $label['line2']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($label['address'])): ?>
                            <div class="label-address"><?= nl2br(e((string) $label['address'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php for ($i = count($chunk); $i < 12; $i++): ?>
                    <div class="label-cell"></div>
                <?php endfor; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
