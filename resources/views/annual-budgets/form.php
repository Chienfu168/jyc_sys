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
            <p class="muted-text">可按「套用 115 年度預算範本」一鍵帶入主管機關格式的完整款／項／目／次／節與金額,再依實際調整。項目可自由新增、刪除、複製與排序。按「新增小計」或勾選某列的「小計 / 合計列」,該列金額會自動加總其上方(同收益／費損、至上一小計止)之明細,並排除於下方的收益／費損合計。</p>
        </div>
        <div class="actions">
            <?php if (!empty($budgetTemplate)): ?>
                <button class="btn small primary" type="button" onclick="applyBudgetTemplate()">套用 115 年度預算範本</button>
            <?php endif; ?>
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

    <div class="budget-lines-totals" id="budgetLinesTotals" aria-live="polite">
        <span>本年度：收益合計 <strong id="budgetTotalIncome">0</strong> ／ 費損合計 <strong id="budgetTotalExpense">0</strong> ／ 賸餘(短絀) <strong id="budgetTotalBalance">0</strong></span>
        <span class="muted-text">合計自動加總,已排除勾選「小計 / 合計列」之明細。</span>
    </div>

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

// 款／項／目／次／節 階層樹(收益 income／費損 expense),供「半自動選擇」使用;使用者仍可自行輸入。
const GOV_TREE = <?= json_encode($govHierarchy ?? ['income' => new stdClass(), 'expense' => new stdClass()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
// 115 年度經費預算表範本(依主管機關格式;小計列 is_subtotal 僅顯示不計入加總)。
const BUDGET_TEMPLATE = <?= json_encode($budgetTemplate ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let govListSeq = 0;

// 依已選父層取得該層可用的下層節點;父層未選或非已知值則回傳 null(該層無建議、可自由輸入)。
function govNodeFor(type, values, uptoLevel) {
    let node = (GOV_TREE && GOV_TREE[type]) || {};
    for (let i = 1; i < uptoLevel; i++) {
        const v = (values[i] || '').trim();
        if (v && node && typeof node === 'object' && Object.prototype.hasOwnProperty.call(node, v)) {
            node = node[v];
        } else {
            return null;
        }
    }
    return node;
}

// 依目前各層輸入值,重新計算每一層的建議清單(cascade)。
function repopulateGov(line) {
    const typeSel = line.querySelector('[data-budget-field="item_type"]');
    const type = (typeSel && typeSel.value === 'income') ? 'income' : 'expense';
    const inputs = line.querySelectorAll('.gov-level-input');
    const values = {};
    inputs.forEach((inp) => { values[parseInt(inp.dataset.govLevel, 10)] = inp.value; });
    inputs.forEach((inp) => {
        const lvl = parseInt(inp.dataset.govLevel, 10);
        const node = govNodeFor(type, values, lvl);
        const opts = (node && typeof node === 'object') ? Object.keys(node) : [];
        const dl = inp.__govList;
        if (!dl) { return; }
        dl.innerHTML = '';
        opts.forEach((o) => { const op = document.createElement('option'); op.value = o; dl.appendChild(op); });
    });
}

// 將一列的 5 個層級輸入接上動態 datalist(選擇即帶出、可自行輸入),並隨父層變動更新下層建議。
function wireGovLevels(line) {
    line.querySelectorAll('datalist.gov-datalist').forEach((d) => d.remove()); // 移除 clone 帶入的舊 datalist,避免 id 重複。
    const inputs = line.querySelectorAll('.gov-level-input');
    inputs.forEach((inp) => {
        const dl = document.createElement('datalist');
        dl.className = 'gov-datalist';
        dl.id = 'govlist-' + (govListSeq++);
        inp.setAttribute('list', dl.id);
        inp.parentNode.appendChild(dl);
        inp.__govList = dl;
        ['input', 'focus', 'change'].forEach((ev) => inp.addEventListener(ev, () => repopulateGov(line)));
    });
    const typeSel = line.querySelector('[data-budget-field="item_type"]');
    if (typeSel) { typeSel.addEventListener('change', () => repopulateGov(line)); }
    repopulateGov(line);
}

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

    if (kind === 'income') {
        type.value = 'income';
        category.value = '收益';
        itemName.value = '';
    } else if (kind === 'subtotal') {
        type.value = 'expense';
        category.value = '';
        itemName.value = '合計';
        subtotal.checked = true;
        line.dataset.autosum = '1'; // 新增的小計／合計列預設自動加總
    } else {
        type.value = 'expense';
        category.value = '';
        itemName.value = '';
    }
}

// ── 小計／合計列自動加總 ───────────────────────────────────────────────
function budgetLineType(line) {
    const t = line.querySelector('[data-budget-field="item_type"]');
    return (t && t.value === 'income') ? 'income' : 'expense';
}
function budgetLineAmountInputs(line) {
    return {
        amount: line.querySelector('[data-budget-field="amount"]'),
        previous: line.querySelector('[name$="[previous_amount]"]'),
    };
}
function budgetLineIsSubtotal(line) {
    const c = line.querySelector('[data-budget-field="is_subtotal"]');
    return !!(c && c.checked);
}
function setSubtotalReadonly(line, readonly) {
    const io = budgetLineAmountInputs(line);
    [io.amount, io.previous].forEach((inp) => {
        if (!inp) { return; }
        inp.readOnly = readonly;
        inp.classList.toggle('is-autosum', readonly);
    });
}

// 重新計算:自動加總的小計列 = 其上方(同收益／費損類別、自上一小計列後)之明細加總;
// 並更新底部「本年度合計」footer(排除所有小計／合計列)。
function recalcBudgetTotals() {
    const lines = Array.prototype.slice.call(document.querySelectorAll('#budget-lines .budget-line'));
    const running = { income: { a: 0, p: 0 }, expense: { a: 0, p: 0 } };
    lines.forEach((line) => {
        const type = budgetLineType(line);
        const io = budgetLineAmountInputs(line);
        if (budgetLineIsSubtotal(line)) {
            if (line.dataset.autosum === '1') {
                if (io.amount) { io.amount.value = running[type].a ? running[type].a : '0'; }
                if (io.previous) { io.previous.value = running[type].p ? running[type].p : '0'; }
            }
            running[type] = { a: 0, p: 0 }; // 小計後,該類別重新起算
        } else {
            running[type].a += parseFloat(io.amount && io.amount.value) || 0;
            running[type].p += parseFloat(io.previous && io.previous.value) || 0;
        }
    });

    let inc = 0, exp = 0, incP = 0, expP = 0;
    lines.forEach((line) => {
        if (budgetLineIsSubtotal(line)) { return; } // 合計排除小計列
        const type = budgetLineType(line);
        const io = budgetLineAmountInputs(line);
        const a = parseFloat(io.amount && io.amount.value) || 0;
        const p = parseFloat(io.previous && io.previous.value) || 0;
        if (type === 'income') { inc += a; incP += p; } else { exp += a; expP += p; }
    });
    const fmt = (n) => n.toLocaleString('en-US');
    const set = (id, v) => { const el = document.getElementById(id); if (el) { el.textContent = v; } };
    set('budgetTotalIncome', fmt(inc));
    set('budgetTotalExpense', fmt(exp));
    set('budgetTotalBalance', fmt(inc - exp));
}

function bindBudgetLineEvents(line) {
    wireGovLevels(line);

    const cb = line.querySelector('[data-budget-field="is_subtotal"]');
    if (cb) {
        // 載入時即為自動加總者(新增的小計列)套用唯讀;範本帶入的小計列維持可編輯。
        if (cb.checked && line.dataset.autosum === '1') { setSubtotalReadonly(line, true); }
        cb.addEventListener('change', () => {
            if (cb.checked) { line.dataset.autosum = '1'; setSubtotalReadonly(line, true); }
            else { line.dataset.autosum = ''; setSubtotalReadonly(line, false); }
            recalcBudgetTotals();
        });
    }
    const io = budgetLineAmountInputs(line);
    [io.amount, io.previous].forEach((inp) => { if (inp) { inp.addEventListener('input', recalcBudgetTotals); } });
    const typeSel = line.querySelector('[data-budget-field="item_type"]');
    if (typeSel) { typeSel.addEventListener('change', recalcBudgetTotals); }
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
    recalcBudgetTotals();
}

function updateBudgetEmptyState() {
    const empty = document.getElementById('budget-empty-state');
    empty.classList.toggle('hidden', document.querySelectorAll('#budget-lines .budget-line').length > 0);
    recalcBudgetTotals();
}

// 一鍵套用 115 年度預算表範本:清空現有項目並帶入範本各列(款／項／目／次／節、金額、小計),再供編輯。
function applyBudgetTemplate() {
    if (!Array.isArray(BUDGET_TEMPLATE) || BUDGET_TEMPLATE.length === 0) { return; }
    const container = document.getElementById('budget-lines');
    if (container.querySelector('.budget-line') &&
        !confirm('套用範本將「取代」目前所有經費項目，確定要繼續嗎？')) {
        return;
    }
    container.innerHTML = '';
    BUDGET_TEMPLATE.forEach((r) => {
        const kind = (r.item_type === 'income') ? 'income' : 'expense';
        const line = createBudgetLine(kind); // 已 clone 並綁定事件
        const setField = (sel, val) => { const el = line.querySelector(sel); if (el) { el.value = val; } };
        setField('[data-budget-field="item_type"]', r.item_type || 'expense');
        setField('[data-budget-field="category"]', r.category || '');
        setField('[data-budget-field="item_name"]', r.item_name || '');
        setField('[data-budget-field="amount"]', (r.amount === 0 || r.amount) ? r.amount : '');
        setField('[name$="[previous_amount]"]', r.previous_amount ? r.previous_amount : '');
        setField('[name$="[comparison_note]"]', r.comparison_note || '');
        for (let i = 1; i <= 5; i++) {
            setField('.gov-level-input[data-gov-level="' + i + '"]', r['gov_level' + i] || '');
        }
        const sub = line.querySelector('[data-budget-field="is_subtotal"]');
        if (sub) { sub.checked = (r.is_subtotal === true || r.is_subtotal === 1 || r.is_subtotal === '1'); }
        container.appendChild(line);
        repopulateGov(line); // 依帶入的層級值刷新下層建議清單
    });
    updateBudgetEmptyState();

    // 帶入年度與名稱(115 年度)。
    const yearInput = document.querySelector('[name="fiscal_year"]');
    if (yearInput) { yearInput.value = '115'; }
    const titleInput = document.querySelector('[name="title"]');
    if (titleInput && (!titleInput.value.trim() || /年度經費預算表$/.test(titleInput.value.trim()))) {
        titleInput.value = '115年度經費預算表';
    }
}

document.querySelectorAll('#budget-lines .budget-line').forEach(bindBudgetLineEvents);
updateBudgetEmptyState();
</script>
