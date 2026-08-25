<?php
$active = 'net-asset-statements';
$components = [
    'founding_fund' => '設立基金',
    'other_fund' => '其他基金',
    'capital_reserve' => '公積',
    'accumulated_surplus' => '累積賸餘(短絀)',
    'other_equity' => '淨值其他項目',
];
ob_start();
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>民國年度</span>
                <input type="number" name="fiscal_year" min="1" max="2100" value="<?= e((string) old('fiscal_year', roc_year($statement['fiscal_year'] ?? date('Y')))) ?>" required>
            </label>
            <label class="span-2">
                <span>表冊名稱</span>
                <input type="text" name="title" value="<?= e((string) old('title', $statement['title'] ?? '')) ?>" required>
            </label>
            <label>
                <span>狀態</span>
                <?php $status = old('status', $statement['status'] ?? 'draft'); ?>
                <select name="status">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>已確認</option>
                </select>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $statement['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </div>

    <div class="panel-header">
        <div>
            <h2>淨值變動明細</h2>
            <p class="muted-text">每列填寫項目名稱與各淨值組成金額（減項以負數輸入）；合計欄由系統自動計算列總和。</p>
        </div>
        <button class="btn small" type="button" onclick="addNetAssetRow()">新增列</button>
    </div>
    <div class="budget-lines net-asset-lines" id="net-asset-lines">
        <?php foreach (($rows ?? []) as $index => $row): ?>
            <div class="budget-line net-asset-line">
                <label class="span-wide">
                    <span>項目名稱</span>
                    <input type="text" name="rows[<?= e((string) $index) ?>][row_label]" value="<?= e((string) ($row['row_label'] ?? '')) ?>">
                </label>
                <?php foreach ($components as $key => $label): ?>
                    <label>
                        <span><?= e($label) ?></span>
                        <input type="number" step="0.01" name="rows[<?= e((string) $index) ?>][<?= e($key) ?>]" value="<?= e((string) ($row[$key] ?? '')) ?>">
                    </label>
                <?php endforeach; ?>
                <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <a class="btn" href="/net-asset-statements">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="net-asset-row-template">
    <div class="budget-line net-asset-line">
        <label class="span-wide"><span>項目名稱</span><input type="text" data-name="row_label"></label>
        <?php foreach ($components as $key => $label): ?>
            <label><span><?= e($label) ?></span><input type="number" step="0.01" data-name="<?= e($key) ?>"></label>
        <?php endforeach; ?>
        <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
    </div>
</template>

<script>
let netAssetRowIndex = <?= count($rows ?? []) ?>;
function addNetAssetRow() {
    const template = document.getElementById('net-asset-row-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[data-name]').forEach((field) => {
        field.name = `rows[${netAssetRowIndex}][${field.dataset.name}]`;
    });
    netAssetRowIndex += 1;
    document.getElementById('net-asset-lines').appendChild(line);
}
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
