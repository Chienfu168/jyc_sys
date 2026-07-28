<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>年度</span>
            <input type="number" name="fiscal_year" min="2000" max="2100" value="<?= e((string) old('fiscal_year', $budget['fiscal_year'] ?? '')) ?>" required>
        </label>
        <label>
            <span>預算類型</span>
            <?php $budgetType = old('budget_type', $budget['budget_type'] ?? 'annual'); ?>
            <select name="budget_type">
                <?php foreach (['annual' => '年度預算', 'project' => '專案預算', 'grant' => '補助計畫'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $budgetType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>起始日期</span>
            <input type="date" name="period_start" value="<?= e((string) old('period_start', $budget['period_start'] ?? '')) ?>">
        </label>
        <label>
            <span>結束日期</span>
            <input type="date" name="period_end" value="<?= e((string) old('period_end', $budget['period_end'] ?? '')) ?>">
        </label>
        <label>
            <span>狀態</span>
            <?php $status = old('status', $budget['status'] ?? 'draft'); ?>
            <select name="status">
                <?php foreach (['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定'] as $value => $label): ?>
                    <?php if ($value !== 'approved' || \App\Core\Permission::can('annual_budgets.approve')): ?>
                        <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>核定會議 / 文號</span>
            <input type="text" name="board_meeting_no" value="<?= e((string) old('board_meeting_no', $budget['board_meeting_no'] ?? '')) ?>">
        </label>
        <label class="span-2">
            <span>預算名稱</span>
            <input type="text" name="title" value="<?= e((string) old('title', $budget['title'] ?? '')) ?>" required>
        </label>
        <label class="span-2">
            <span>計畫目的</span>
            <textarea name="purpose"><?= e((string) old('purpose', $budget['purpose'] ?? '')) ?></textarea>
        </label>
        <label class="span-2">
            <span>辦理依據</span>
            <textarea name="legal_basis"><?= e((string) old('legal_basis', $budget['legal_basis'] ?? '')) ?></textarea>
        </label>
        <label class="span-2">
            <span>預期效益</span>
            <textarea name="expected_benefit"><?= e((string) old('expected_benefit', $budget['expected_benefit'] ?? '')) ?></textarea>
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="notes"><?= e((string) old('notes', $budget['notes'] ?? '')) ?></textarea>
        </label>
    </div>

    <div class="panel-header">
        <h2>預算經費明細</h2>
        <button class="btn small" type="button" onclick="addBudgetLine()">新增明細</button>
    </div>

    <div class="budget-lines nonprofit-lines" id="budget-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line nonprofit-line">
                <label>
                    <span>類型</span>
                    <select name="items[<?= e((string) $index) ?>][item_type]">
                        <option value="income" <?= ($item['item_type'] ?? '') === 'income' ? 'selected' : '' ?>>收入</option>
                        <option value="expense" <?= ($item['item_type'] ?? '') === 'expense' ? 'selected' : '' ?>>支出</option>
                    </select>
                </label>
                <label>
                    <span>科目</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][category]" value="<?= e((string) ($item['category'] ?? '')) ?>">
                </label>
                <label>
                    <span>項目</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][item_name]" value="<?= e((string) ($item['item_name'] ?? '')) ?>">
                </label>
                <label>
                    <span>單位</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][unit]" value="<?= e((string) ($item['unit'] ?? '')) ?>">
                </label>
                <label>
                    <span>數量</span>
                    <input type="number" step="0.01" min="0" name="items[<?= e((string) $index) ?>][quantity]" value="<?= e((string) ($item['quantity'] ?? '1')) ?>">
                </label>
                <label>
                    <span>單價</span>
                    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][unit_price]" value="<?= e((string) ($item['unit_price'] ?? '')) ?>">
                </label>
                <label>
                    <span>金額</span>
                    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][amount]" value="<?= e((string) ($item['amount'] ?? '')) ?>">
                </label>
                <label>
                    <span>經費來源</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][funding_source]" value="<?= e((string) ($item['funding_source'] ?? '')) ?>">
                </label>
                <label class="span-wide">
                    <span>用途說明</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][description]" value="<?= e((string) ($item['description'] ?? '')) ?>">
                </label>
                <label class="span-wide">
                    <span>備註</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][notes]" value="<?= e((string) ($item['notes'] ?? '')) ?>">
                </label>
                <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <a class="btn" href="/annual-budgets">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="budget-line-template">
    <div class="budget-line nonprofit-line">
        <label>
            <span>類型</span>
            <select data-name="item_type">
                <option value="income">收入</option>
                <option value="expense">支出</option>
            </select>
        </label>
        <label><span>科目</span><input type="text" data-name="category"></label>
        <label><span>項目</span><input type="text" data-name="item_name"></label>
        <label><span>單位</span><input type="text" data-name="unit"></label>
        <label><span>數量</span><input type="number" step="0.01" min="0" data-name="quantity" value="1"></label>
        <label><span>單價</span><input type="number" step="1" min="0" data-name="unit_price"></label>
        <label><span>金額</span><input type="number" step="1" min="0" data-name="amount"></label>
        <label><span>經費來源</span><input type="text" data-name="funding_source"></label>
        <label class="span-wide"><span>用途說明</span><input type="text" data-name="description"></label>
        <label class="span-wide"><span>備註</span><input type="text" data-name="notes"></label>
        <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
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
