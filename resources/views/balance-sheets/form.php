<?php
$active = 'balance-sheets';
$sectionLabels = ['asset' => '資產', 'liability' => '負債', 'equity' => '淨值'];
ob_start();
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>民國年度</span>
                <input type="number" name="fiscal_year" min="1" max="2100" value="<?= e((string) old('fiscal_year', roc_year($sheet['fiscal_year'] ?? date('Y')))) ?>" required>
            </label>
            <label class="span-2">
                <span>表冊名稱</span>
                <input type="text" name="title" value="<?= e((string) old('title', $sheet['title'] ?? '')) ?>" required>
            </label>
            <label>
                <span>狀態</span>
                <?php $status = old('status', $sheet['status'] ?? 'draft'); ?>
                <select name="status">
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                    <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>已確認</option>
                </select>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $sheet['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </div>

    <div class="panel-header">
        <div>
            <h2>科目明細</h2>
            <p class="muted-text">依序輸入資產、負債、淨值各科目金額；比較增減與比率由系統自動計算。負債及淨值總計應等於資產總計。</p>
        </div>
        <button class="btn small" type="button" onclick="addBalanceLine()">新增科目</button>
    </div>
    <div class="budget-lines balance-sheet-lines" id="balance-sheet-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line balance-sheet-line">
                <label>
                    <span>類別</span>
                    <select name="items[<?= e((string) $index) ?>][section]">
                        <?php foreach ($sectionLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($item['section'] ?? 'asset') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-wide">
                    <span>科目名稱</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
                </label>
                <label>
                    <span>本年底決算數</span>
                    <input type="number" step="0.01" name="items[<?= e((string) $index) ?>][current_amount]" value="<?= e((string) ($item['current_amount'] ?? '')) ?>">
                </label>
                <label>
                    <span>上年底決算數</span>
                    <input type="number" step="0.01" name="items[<?= e((string) $index) ?>][prior_amount]" value="<?= e((string) ($item['prior_amount'] ?? '')) ?>">
                </label>
                <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <a class="btn" href="/balance-sheets">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="balance-sheet-line-template">
    <div class="budget-line balance-sheet-line">
        <label><span>類別</span>
            <select data-name="section">
                <?php foreach ($sectionLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="span-wide"><span>科目名稱</span><input type="text" data-name="item_name"></label>
        <label><span>本年底決算數</span><input type="number" step="0.01" data-name="current_amount"></label>
        <label><span>上年底決算數</span><input type="number" step="0.01" data-name="prior_amount"></label>
        <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
    </div>
</template>

<script>
let balanceSheetLineIndex = <?= count($items ?? []) ?>;
function addBalanceLine() {
    const template = document.getElementById('balance-sheet-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[data-name]').forEach((field) => {
        field.name = `items[${balanceSheetLineIndex}][${field.dataset.name}]`;
    });
    balanceSheetLineIndex += 1;
    document.getElementById('balance-sheet-lines').appendChild(line);
}
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
