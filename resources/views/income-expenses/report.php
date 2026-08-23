<?php
$active = 'income-expenses';
$documentTitle = '收支統計表';
$periodLabel = $month === '' ? roc_year_label($year) : roc_year_label($year) . ' ' . (int) $month . ' 月';
ob_start();
?>
<section class="panel no-print">
    <form class="form grid-form" method="get" action="/income-expenses/report">
        <label>
            <span>民國年度</span>
            <input type="number" name="year" min="1" max="2100" value="<?= e((string) roc_year($year)) ?>">
        </label>
        <label>
            <span>月份</span>
            <select name="month">
                <option value="">全年</option>
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <?php $value = str_pad((string) $i, 2, '0', STR_PAD_LEFT); ?>
                    <option value="<?= e($value) ?>" <?= $month === $value ? 'selected' : '' ?>><?= e((string) $i) ?> 月</option>
                <?php endfor; ?>
            </select>
        </label>
        <div class="form-actions">
            <button class="btn primary" type="submit">產生報表</button>
        </div>
    </form>
</section>

<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($periodLabel) ?>收支統計表</h2>
            <p class="muted-text">收入、支出、科目小計與總計。</p>
        </div>
        <a class="btn no-print" href="/income-expenses">返回收支紀錄</a>
    </div>

    <section class="stats-grid budget-summary">
        <div class="stat-card"><span>收入合計</span><strong><?= e(income_expense_report_money($totals['income'])) ?></strong></div>
        <div class="stat-card"><span>支出合計</span><strong><?= e(income_expense_report_money($totals['expense'])) ?></strong></div>
        <div class="stat-card"><span>餘絀</span><strong><?= e(income_expense_report_money($totals['balance'])) ?></strong></div>
    </section>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>科目</th>
                <th class="amount">筆數</th>
                <th class="amount">小計</th>
                <th class="amount">比例</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($summary as $row): ?>
                <tr>
                    <td><?= e($row['item_type'] === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($row['category_name']) ?></td>
                    <td class="amount"><?= e((string) $row['record_count']) ?></td>
                    <td class="amount"><?= e(income_expense_report_money($row['subtotal'])) ?></td>
                    <td class="amount"><?= e(number_format((float) ($row['ratio'] ?? 0), 1)) ?>%</td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$summary): ?>
                <tr><td colspan="5" class="empty">此期間沒有資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function income_expense_report_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
