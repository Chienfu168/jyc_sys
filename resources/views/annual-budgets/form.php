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

    <div class="panel-header">
        <div>
            <h2>經費明細</h2>
            <p class="muted-text">依主管機關格式建立款、項、目、次、節與本年度/上年度預算比較。</p>
        </div>
        <button class="btn small" type="button" onclick="addBudgetLine()">新增明細</button>
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

    <div class="budget-lines nonprofit-lines" id="budget-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line nonprofit-line">
                <?php require __DIR__ . '/line-fields.php'; ?>
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
        <?php $index = '__INDEX__'; $item = []; require __DIR__ . '/line-fields.php'; ?>
    </div>
</template>

<script>
let budgetLineIndex = <?= count($items ?? []) ?>;
function addBudgetLine() {
    const template = document.getElementById('budget-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[name]').forEach((field) => {
        field.name = field.name.replace('__INDEX__', budgetLineIndex);
    });
    budgetLineIndex += 1;
    document.getElementById('budget-lines').appendChild(line);
}
</script>
