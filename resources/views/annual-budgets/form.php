<?php
$commonItems = [
    '捐贈收入',
    '利息收入',
    '業務活動費用',
    '業務推廣費',
    '會議費',
    '捐贈支出',
    '人事薪資',
    '保險費',
    '勞保費用',
    '健保費用',
    '團保意外險',
    '勞退金',
    '年終獎金',
    '辦公室租金',
    '文具印刷費',
    '交通費',
    '差旅費',
    '郵電費',
    '水電費',
    '專業服務費',
    '雜項支出',
    '預備金',
];
$commonCategories = ['收益', '業務費', '人事費用', '辦公行政費', '預備金'];
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>民國年度</span>
            <input type="number" name="fiscal_year" min="1" max="2100" value="<?= e((string) old('fiscal_year', roc_year($budget['fiscal_year'] ?? date('Y')))) ?>" required>
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
            <span>期間起日</span>
            <input type="date" name="period_start" value="<?= e((string) old('period_start', $budget['period_start'] ?? '')) ?>">
        </label>
        <label>
            <span>期間迄日</span>
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
            <span>董事會 / 會議紀錄</span>
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
            <span>法規依據</span>
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

    <div class="panel-header budget-editor-header">
        <div>
            <h2>經費項目</h2>
            <p class="muted-text">項目可自由新增、刪除、複製與排序；款、項、目、次、節皆為選填，需要送主管機關格式時再填即可。</p>
        </div>
        <div class="actions">
            <button class="btn small" type="button" onclick="addBudgetLine('income')">新增收益</button>
            <button class="btn small" type="button" onclick="addBudgetLine('expense')">新增費損</button>
            <button class="btn small" type="button" onclick="addBudgetLine('subtotal')">新增小計</button>
        </div>
    </div>

    <datalist id="annual-budget-common-items">
        <?php foreach ($commonItems as $itemName): ?>
            <option value="<?= e($itemName) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <datalist id="annual-budget-common-categories">
        <?php foreach ($commonCategories as $categoryName): ?>
            <option value="<?= e($categoryName) ?>"></option>
        <?php endforeach; ?>
    </datalist>

    <div class="budget-lines nonprofit-lines flexible-budget-lines" id="budget-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line nonprofit-line flexible-budget-line">
                <?php require __DIR__ . '/line-fields.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="empty-state <?= empty($items ?? []) ? '' : 'hidden' ?>" id="budget-empty-state">尚無經費項目，請先新增收益或費損項目。</p>

    <div class="form-actions">
        <a class="btn" href="/annual-budgets">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="budget-line-template">
    <div class="budget-line nonprofit-line flexible-budget-line">
        <?php $index = '__INDEX__'; $item = []; require __DIR__ . '/line-fields.php'; ?>
    </div>
</template>

<script>
let budgetLineIndex = <?= count($items ?? []) ?>;

function addBudgetLine(kind = 'expense') {
    const line = createBudgetLine(kind);
    document.getElementById('budget-lines').appendChild(line);
    updateBudgetEmptyState();
}

function createBudgetLine(kind = 'expense') {
    const template = document.getElementById('budget-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[name]').forEach((field) => {
        field.name = field.name.replace('__INDEX__', budgetLineIndex);
    });
    budgetLineIndex += 1;
    applyBudgetLineKind(line, kind);
    bindBudgetLineEvents(line);
    return line;
}

function applyBudgetLineKind(line, kind) {
    const type = line.querySelector('[data-budget-field="item_type"]');
    const category = line.querySelector('[data-budget-field="category"]');
    const itemName = line.querySelector('[data-budget-field="item_name"]');
    const subtotal = line.querySelector('[data-budget-field="is_subtotal"]');
    const quantity = line.querySelector('[data-budget-field="quantity"]');

    if (kind === 'income') {
        type.value = 'income';
        category.value = '收益';
        itemName.value = '';
    } else if (kind === 'subtotal') {
        type.value = 'expense';
        category.value = '';
        itemName.value = '小計';
        subtotal.checked = true;
    } else {
        type.value = 'expense';
        category.value = '';
        itemName.value = '';
    }

    if (quantity && quantity.value === '') {
        quantity.value = '1';
    }
}

function bindBudgetLineEvents(line) {
    line.querySelectorAll('[data-auto-amount]').forEach((field) => {
        field.addEventListener('input', () => updateLineAmount(line));
    });
}

function updateLineAmount(line) {
    const quantity = Number(line.querySelector('[data-budget-field="quantity"]')?.value || 0);
    const unitPrice = Number(line.querySelector('[data-budget-field="unit_price"]')?.value || 0);
    const amount = line.querySelector('[data-budget-field="amount"]');
    if (amount && quantity > 0 && unitPrice > 0) {
        amount.value = Math.round(quantity * unitPrice);
    }
}

function removeBudgetLine(button) {
    button.closest('.budget-line').remove();
    updateBudgetEmptyState();
}

function duplicateBudgetLine(button) {
    const source = button.closest('.budget-line');
    const clone = source.cloneNode(true);
    clone.querySelectorAll('[name]').forEach((field) => {
        field.name = field.name.replace(/items\[[^\]]+\]/, `items[${budgetLineIndex}]`);
    });
    budgetLineIndex += 1;
    source.after(clone);
    bindBudgetLineEvents(clone);
    updateBudgetEmptyState();
}

function moveBudgetLine(button, direction) {
    const line = button.closest('.budget-line');
    if (direction < 0 && line.previousElementSibling) {
        line.parentElement.insertBefore(line, line.previousElementSibling);
    }
    if (direction > 0 && line.nextElementSibling) {
        line.parentElement.insertBefore(line.nextElementSibling, line);
    }
}

function updateBudgetEmptyState() {
    const empty = document.getElementById('budget-empty-state');
    empty.classList.toggle('hidden', document.querySelectorAll('#budget-lines .budget-line').length > 0);
}

document.querySelectorAll('#budget-lines .budget-line').forEach(bindBudgetLineEvents);
updateBudgetEmptyState();
</script>
