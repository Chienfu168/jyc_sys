<?php
$active = 'cash-flow-statements';
$sectionLabels = ['operating' => '業務活動', 'investing' => '投資活動', 'financing' => '籌資活動'];
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
        </div>
    </div>

    <div class="form-section">
        <h3>匯率變動與期初餘額</h3>
        <div class="grid-form">
            <label>
                <span>匯率變動影響（本年度）</span>
                <input type="number" step="0.01" name="exchange_current" value="<?= e((string) old('exchange_current', $statement['exchange_current'] ?? '')) ?>">
            </label>
            <label>
                <span>匯率變動影響（上年度）</span>
                <input type="number" step="0.01" name="exchange_prior" value="<?= e((string) old('exchange_prior', $statement['exchange_prior'] ?? '')) ?>">
            </label>
            <label>
                <span>期初現金及約當現金餘額（本年度）</span>
                <input type="number" step="0.01" name="opening_current" value="<?= e((string) old('opening_current', $statement['opening_current'] ?? '')) ?>">
            </label>
            <label>
                <span>期初現金及約當現金餘額（上年度）</span>
                <input type="number" step="0.01" name="opening_prior" value="<?= e((string) old('opening_prior', $statement['opening_prior'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $statement['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </div>

    <div class="panel-header">
        <div>
            <h2>現金流量項目</h2>
            <p class="muted-text">依活動別輸入各項現金流量（流出以負數輸入）；各活動淨額、現金增減數與期末餘額由系統自動計算。</p>
        </div>
        <button class="btn small" type="button" onclick="addCashFlowLine()">新增項目</button>
    </div>
    <div class="budget-lines cash-flow-lines" id="cash-flow-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line cash-flow-line">
                <label>
                    <span>活動別</span>
                    <select name="items[<?= e((string) $index) ?>][section]">
                        <?php foreach ($sectionLabels as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($item['section'] ?? 'operating') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="span-wide">
                    <span>項目名稱</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
                </label>
                <label>
                    <span>本年度決算數</span>
                    <input type="number" step="0.01" name="items[<?= e((string) $index) ?>][current_amount]" value="<?= e((string) ($item['current_amount'] ?? '')) ?>">
                </label>
                <label>
                    <span>上年度決算數</span>
                    <input type="number" step="0.01" name="items[<?= e((string) $index) ?>][prior_amount]" value="<?= e((string) ($item['prior_amount'] ?? '')) ?>">
                </label>
                <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <a class="btn" href="/cash-flow-statements">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="cash-flow-line-template">
    <div class="budget-line cash-flow-line">
        <label><span>活動別</span>
            <select data-name="section">
                <?php foreach ($sectionLabels as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="span-wide"><span>項目名稱</span><input type="text" data-name="item_name"></label>
        <label><span>本年度決算數</span><input type="number" step="0.01" data-name="current_amount"></label>
        <label><span>上年度決算數</span><input type="number" step="0.01" data-name="prior_amount"></label>
        <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
    </div>
</template>

<script>
let cashFlowLineIndex = <?= count($items ?? []) ?>;
function addCashFlowLine() {
    const template = document.getElementById('cash-flow-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[data-name]').forEach((field) => {
        field.name = `items[${cashFlowLineIndex}][${field.dataset.name}]`;
    });
    cashFlowLineIndex += 1;
    document.getElementById('cash-flow-lines').appendChild(line);
}
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
