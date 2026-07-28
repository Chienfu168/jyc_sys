<?php
$active = 'petty-cash';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>本月收入</span>
        <strong><?= e(petty_cash_money($totals['income'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月支出</span>
        <strong><?= e(petty_cash_money($totals['expense'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月餘額</span>
        <strong><?= e(petty_cash_money($totals['balance'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search" method="get" action="/petty-cash">
            <input type="month" name="month" value="<?= e($month) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <?php if (\App\Core\Permission::can('petty_cash.manage')): ?>
                <a class="btn" href="/petty-cash-items">常用項目</a>
                <a class="btn primary" href="/petty-cash/create">新增紀錄</a>
            <?php endif; ?>
        </div>
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
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= e($entry['occurred_on']) ?></td>
                    <td>
                        <span class="badge <?= $entry['item_type'] === 'income' ? 'ok' : 'muted' ?>">
                            <?= e($entry['item_type'] === 'income' ? '收入' : '支出') ?>
                        </span>
                    </td>
                    <td><?= e($entry['item_name']) ?></td>
                    <td><?= e($entry['payment_to'] ?: '-') ?></td>
                    <td><?= e($entry['receipt_no'] ?: '-') ?></td>
                    <td class="amount"><?= e(petty_cash_money($entry['amount'])) ?></td>
                    <td><?= e($entry['created_by_name'] ?? '-') ?></td>
                    <td class="actions">
                        <?php if (\App\Core\Permission::can('petty_cash.manage')): ?>
                            <a class="btn small" href="/petty-cash/<?= e((string) $entry['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$entries): ?>
                <tr><td colspan="8" class="empty">本月份尚無零用金紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function petty_cash_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
