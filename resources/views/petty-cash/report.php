<?php
$active = 'petty-cash';
ob_start();
$periodLabel = $month === '' ? $year . ' 年' : $year . ' 年 ' . (int) $month . ' 月';
?>
<section class="panel no-print">
    <form class="form grid-form" method="get" action="/petty-cash/report">
        <label>
            <span>年度</span>
            <input type="number" name="year" min="2000" max="2100" value="<?= e((string) $year) ?>">
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
        <label>
            <span>輸出版本</span>
            <select name="mode">
                <option value="summary" <?= $mode === 'summary' ? 'selected' : '' ?>>精簡版</option>
                <option value="detail" <?= $mode === 'detail' ? 'selected' : '' ?>>詳細版</option>
            </select>
        </label>
        <div class="form-actions">
            <button class="btn primary" type="submit">產生報表</button>
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($periodLabel) ?>零用金統計表</h2>
            <p class="muted-text"><?= e($mode === 'detail' ? '詳細版' : '精簡版') ?></p>
        </div>
        <a class="btn no-print" href="/petty-cash?month=<?= e($year . '-' . ($month ?: date('m'))) ?>">返回零用金</a>
    </div>

    <section class="stats-grid budget-summary">
        <div class="stat-card">
            <span>收入總計</span>
            <strong><?= e(report_money($totals['income'])) ?></strong>
        </div>
        <div class="stat-card">
            <span>支出總計</span>
            <strong><?= e(report_money($totals['expense'])) ?></strong>
        </div>
        <div class="stat-card">
            <span>收支餘額</span>
            <strong><?= e(report_money($totals['balance'])) ?></strong>
        </div>
    </section>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>項目統計</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>項目</th>
                <th class="amount">筆數</th>
                <th class="amount">小計</th>
                <th class="amount">比例</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($summary as $row): ?>
                <?php
                $base = $row['item_type'] === 'income' ? $incomeTotal : $expenseTotal;
                $ratio = $base > 0 ? ((float) $row['subtotal'] / $base * 100) : 0;
                ?>
                <tr>
                    <td><?= e($row['item_type'] === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($row['item_name']) ?></td>
                    <td class="amount"><?= e((string) $row['entry_count']) ?></td>
                    <td class="amount"><?= e(report_money($row['subtotal'])) ?></td>
                    <td class="amount"><?= e(number_format($ratio, 1)) ?>%</td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$summary): ?>
                <tr><td colspan="5" class="empty">此期間沒有資料</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if ($month === ''): ?>
    <section class="panel">
        <div class="panel-header">
            <h2>月份小計</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>月份</th>
                    <th class="amount">收入</th>
                    <th class="amount">支出</th>
                    <th class="amount">餘額</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($monthly as $row): ?>
                    <tr>
                        <td><?= e($row['report_month']) ?></td>
                        <td class="amount"><?= e(report_money($row['income_total'])) ?></td>
                        <td class="amount"><?= e(report_money($row['expense_total'])) ?></td>
                        <td class="amount"><?= e(report_money((float) $row['income_total'] - (float) $row['expense_total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$monthly): ?>
                    <tr><td colspan="4" class="empty">此年度沒有資料</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if ($mode === 'detail'): ?>
    <section class="panel">
        <div class="panel-header">
            <h2>明細資料</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>日期</th>
                    <th>類型</th>
                    <th>項目</th>
                    <th>對象</th>
                    <th>單據</th>
                    <th class="amount">金額</th>
                    <th>建立人</th>
                    <th>備註</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= e($entry['occurred_on']) ?></td>
                        <td><?= e($entry['item_type'] === 'income' ? '收入' : '支出') ?></td>
                        <td><?= e($entry['item_name']) ?></td>
                        <td><?= e($entry['payment_to'] ?: '-') ?></td>
                        <td><?= e($entry['receipt_no'] ?: '-') ?></td>
                        <td class="amount"><?= e(report_money($entry['amount'])) ?></td>
                        <td><?= e($entry['created_by_name'] ?? '-') ?></td>
                        <td><?= e($entry['notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$entries): ?>
                    <tr><td colspan="8" class="empty">此期間沒有明細資料</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php
function report_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
