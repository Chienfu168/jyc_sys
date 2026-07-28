<?php $items = old('items', $items); ?>
<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>年度</span>
            <input type="number" name="fiscal_year" value="<?= e((string) old('fiscal_year', $budget['fiscal_year'])) ?>" min="2000" max="2100" required>
        </label>
        <label>
            <span>狀態</span>
            <?php $status = old('status', $budget['status'] ?? 'draft'); ?>
            <select name="status">
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>送審</option>
                <?php if (($budget['status'] ?? '') === 'approved' && \App\Core\Permission::can('annual_budgets.approve')): ?>
                    <option value="approved" selected>核定</option>
                <?php endif; ?>
            </select>
        </label>
        <label class="span-2">
            <span>預算名稱</span>
            <input type="text" name="title" value="<?= e(old('title', $budget['title'])) ?>" required>
        </label>
        <label class="span-2">
            <span>備註</span>
            <input type="text" name="notes" value="<?= e(old('notes', $budget['notes'] ?? '')) ?>">
        </label>
    </div>

    <div class="panel-header">
        <h2>預算明細</h2>
        <button class="btn" type="button" id="add-budget-line">新增明細</button>
    </div>

    <div class="budget-lines" id="budget-lines">
        <?php foreach ($items as $index => $item): ?>
            <div class="budget-line">
                <label>
                    <span>類型</span>
                    <select name="items[<?= e((string) $index) ?>][item_type]">
                        <option value="income" <?= ($item['item_type'] ?? '') === 'income' ? 'selected' : '' ?>>收入</option>
                        <option value="expense" <?= ($item['item_type'] ?? '') === 'expense' ? 'selected' : '' ?>>支出</option>
                    </select>
                </label>
                <label>
                    <span>科目</span>
                    <input name="items[<?= e((string) $index) ?>][category]" value="<?= e($item['category'] ?? '') ?>">
                </label>
                <label>
                    <span>項目名稱</span>
                    <input name="items[<?= e((string) $index) ?>][item_name]" value="<?= e($item['item_name'] ?? '') ?>">
                </label>
                <label>
                    <span>金額</span>
                    <input class="amount-input" type="number" min="0" step="1" name="items[<?= e((string) $index) ?>][amount]" value="<?= e((string) ($item['amount'] ?? '')) ?>">
                </label>
                <button class="btn remove-budget-line" type="button">×</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <button class="btn primary" type="submit">儲存年度預算</button>
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
            <span>科目</span>
            <input data-name="category">
        </label>
        <label>
            <span>項目名稱</span>
            <input data-name="item_name">
        </label>
        <label>
            <span>金額</span>
            <input class="amount-input" type="number" min="0" step="1" data-name="amount">
        </label>
        <button class="btn remove-budget-line" type="button">×</button>
    </div>
</template>

<script>
    const lines = document.querySelector('#budget-lines');
    const template = document.querySelector('#budget-line-template');
    const addButton = document.querySelector('#add-budget-line');

    function nextIndex() {
        return lines.querySelectorAll('.budget-line').length;
    }

    function bindRemove(button) {
        button.addEventListener('click', () => {
            if (lines.querySelectorAll('.budget-line').length > 1) {
                button.closest('.budget-line').remove();
            }
        });
    }

    document.querySelectorAll('.remove-budget-line').forEach(bindRemove);
    addButton.addEventListener('click', () => {
        const index = nextIndex();
        const node = template.content.cloneNode(true);
        node.querySelectorAll('[data-name]').forEach((input) => {
            input.name = `items[${index}][${input.dataset.name}]`;
        });
        bindRemove(node.querySelector('.remove-budget-line'));
        lines.appendChild(node);
    });
</script>
