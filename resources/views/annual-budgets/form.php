<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>年度</span>
            <input type="number" name="fiscal_year" min="2000" max="2100" value="<?= e((string) old('fiscal_year', $budget['fiscal_year'] ?? '')) ?>" required>
        </label>
        <label>
            <span>狀態</span>
            <select name="status">
                <?php foreach (['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定'] as $value => $label): ?>
                    <?php if ($value !== 'approved' || \App\Core\Permission::can('annual_budgets.approve')): ?>
                        <option value="<?= e($value) ?>" <?= old('status', $budget['status'] ?? 'draft') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="span-2">
            <span>預算名稱</span>
            <input type="text" name="title" value="<?= e((string) old('title', $budget['title'] ?? '')) ?>" required>
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="notes"><?= e((string) old('notes', $budget['notes'] ?? '')) ?></textarea>
        </label>
    </div>

    <div class="panel-header">
        <h2>預算項目</h2>
        <button class="btn small" type="button" onclick="addBudgetLine()">新增項目</button>
    </div>

    <div class="budget-lines" id="budget-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line">
                <label>
                    <span>類型</span>
                    <select name="items[<?= e((string) $index) ?>][item_type]">
                        <option value="income" <?= ($item['item_type'] ?? '') === 'income' ? 'selected' : '' ?>>收入</option>
                        <option value="expense" <?= ($item['item_type'] ?? '') === 'expense' ? 'selected' : '' ?>>支出</option>
                    </select>
                </label>
                <label>
                    <span>分類</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][category]" value="<?= e((string) ($item['category'] ?? '')) ?>">
                </label>
                <label>
                    <span>項目名稱</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
                </label>
                <label>
                    <span>金額</span>
                    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][amount]" value="<?= e((string) ($item['amount'] ?? '')) ?>">
                </label>
                <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">移除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="budget-line-template">
    <div class="budget-line">
        <label>
            <span>類型</span>
            <select data-name="item_type">
                <option value="income">收入</option>
                <option value="expense">支出</option>
            </select>
        </label>
        <label>
            <span>分類</span>
            <input type="text" data-name="category">
        </label>
        <label>
            <span>項目名稱</span>
            <input type="text" data-name="item_name">
        </label>
        <label>
            <span>金額</span>
            <input type="number" step="1" min="0" data-name="amount">
        </label>
        <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">移除</button>
    </div>
</template>

<script>
let budgetLineIndex = <?= count($items ?? []) ?>;
function addBudgetLine() {
    const template = document.getElementById('budget-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[data-name]').forEach((field) => {
        field.name = `items[${budgetLineIndex}][${field.dataset.name}]`;
    });
    budgetLineIndex += 1;
    document.getElementById('budget-lines').appendChild(line);
}
</script>
