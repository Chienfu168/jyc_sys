<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>民國年度</span>
                <input type="number" name="fiscal_year" min="1" max="2100" value="<?= e((string) old('fiscal_year', roc_year($plan['fiscal_year'] ?? date('Y')))) ?>" required>
            </label>
            <label>
                <span>計畫編號</span>
                <input type="text" name="plan_code" value="<?= e((string) old('plan_code', $plan['plan_code'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>計畫名稱</span>
                <input type="text" name="title" value="<?= e((string) old('title', $plan['title'] ?? '')) ?>" required>
            </label>
            <label>
                <span>承辦單位</span>
                <input type="text" name="department" value="<?= e((string) old('department', $plan['department'] ?? '')) ?>">
            </label>
            <label>
                <span>負責人</span>
                <input type="text" name="owner_name" value="<?= e((string) old('owner_name', $plan['owner_name'] ?? '')) ?>">
            </label>
            <label>
                <span>起始日期</span>
                <input type="date" name="period_start" value="<?= e((string) old('period_start', $plan['period_start'] ?? '')) ?>">
            </label>
            <label>
                <span>結束日期</span>
                <input type="date" name="period_end" value="<?= e((string) old('period_end', $plan['period_end'] ?? '')) ?>">
            </label>
            <label>
                <span>狀態</span>
                <?php $status = old('status', $plan['status'] ?? 'draft'); ?>
                <select name="status">
                    <?php foreach (['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定', 'closed' => '結案'] as $value => $label): ?>
                        <?php if ($value !== 'approved' || \App\Core\Permission::can('work_plans.approve')): ?>
                            <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>服務區域</span>
                <input type="text" name="service_area" value="<?= e((string) old('service_area', $plan['service_area'] ?? '')) ?>">
            </label>
        </div>
    </div>

    <details class="form-section" open>
        <summary>計畫內容(選填)</summary>
        <div class="grid-form">
            <label class="span-2">
                <span>計畫目標</span>
                <textarea name="objective"><?= e((string) old('objective', $plan['objective'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>服務對象</span>
                <textarea name="target_audience"><?= e((string) old('target_audience', $plan['target_audience'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>預期成果</span>
                <textarea name="expected_outcomes"><?= e((string) old('expected_outcomes', $plan['expected_outcomes'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>成果指標</span>
                <textarea name="performance_indicators"><?= e((string) old('performance_indicators', $plan['performance_indicators'] ?? '')) ?></textarea>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $plan['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <div class="panel-header">
        <h2>執行項目</h2>
        <button class="btn small" type="button" onclick="addPlanLine()">新增項目</button>
    </div>
    <div class="budget-lines work-plan-lines" id="work-plan-lines">
        <?php foreach (($items ?? []) as $index => $item): ?>
            <div class="budget-line work-plan-line">
                <label>
                    <span>期程</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][period_label]" value="<?= e((string) ($item['period_label'] ?? '')) ?>">
                </label>
                <label>
                    <span>工作項目</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][activity_name]" value="<?= e((string) ($item['activity_name'] ?? '')) ?>">
                </label>
                <label class="span-wide">
                    <span>執行內容</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][description]" value="<?= e((string) ($item['description'] ?? '')) ?>">
                </label>
                <label>
                    <span>預期產出</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][expected_output]" value="<?= e((string) ($item['expected_output'] ?? '')) ?>">
                </label>
                <label>
                    <span>預算金額</span>
                    <input type="number" step="1" min="0" name="items[<?= e((string) $index) ?>][budget_amount]" value="<?= e((string) ($item['budget_amount'] ?? '')) ?>">
                </label>
                <label>
                    <span>備註</span>
                    <input type="text" name="items[<?= e((string) $index) ?>][notes]" value="<?= e((string) ($item['notes'] ?? '')) ?>">
                </label>
                <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="form-actions">
        <a class="btn" href="/work-plans">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="work-plan-line-template">
    <div class="budget-line work-plan-line">
        <label><span>期程</span><input type="text" data-name="period_label"></label>
        <label><span>工作項目</span><input type="text" data-name="activity_name"></label>
        <label class="span-wide"><span>執行內容</span><input type="text" data-name="description"></label>
        <label><span>預期產出</span><input type="text" data-name="expected_output"></label>
        <label><span>預算金額</span><input type="number" step="1" min="0" data-name="budget_amount"></label>
        <label><span>備註</span><input type="text" data-name="notes"></label>
        <button class="btn" type="button" onclick="this.closest('.budget-line').remove()">刪除</button>
    </div>
</template>

<script>
let workPlanLineIndex = <?= count($items ?? []) ?>;
function addPlanLine() {
    const template = document.getElementById('work-plan-line-template');
    const line = template.content.firstElementChild.cloneNode(true);
    line.querySelectorAll('[data-name]').forEach((field) => {
        field.name = `items[${workPlanLineIndex}][${field.dataset.name}]`;
    });
    workPlanLineIndex += 1;
    document.getElementById('work-plan-lines').appendChild(line);
}
</script>
