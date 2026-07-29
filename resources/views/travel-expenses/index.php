<?php
$active = 'travel-expenses';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>費用總額</span>
        <strong><?= e(travel_expense_money($totals['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>預支金額</span>
        <strong><?= e(travel_expense_money($totals['advance'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>應核銷金額</span>
        <strong><?= e(travel_expense_money($totals['reimbursable'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/travel-expenses">
            <input type="month" name="month" value="<?= e($month) ?>">
            <select name="status">
                <option value="">全部狀態</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>待付款</option>
                <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>已付款</option>
                <option value="voided" <?= $status === 'voided' ? 'selected' : '' ?>>作廢</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/finance">返回財務會計</a>
            <?php if (\App\Core\Permission::can('travel_expenses.manage')): ?>
                <a class="btn primary" href="/travel-expenses/create">新增出差費用</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>期間</th>
                <th>出差人</th>
                <th>地點</th>
                <th>事由</th>
                <th>專案</th>
                <th class="amount">總額</th>
                <th class="amount">應核銷</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= e(roc_date($expense['travel_start'])) ?> ~ <?= e(roc_date($expense['travel_end'])) ?></td>
                    <td><?= e($expense['traveler_name']) ?></td>
                    <td><?= e($expense['destination']) ?></td>
                    <td><?= e($expense['purpose']) ?></td>
                    <td><?= e($expense['project_name'] ?: '-') ?></td>
                    <td class="amount"><?= e(travel_expense_money($expense['total_amount'])) ?></td>
                    <td class="amount"><?= e(travel_expense_money($expense['reimbursable_amount'])) ?></td>
                    <td><span class="badge <?= $expense['payment_status'] === 'paid' ? 'ok' : 'muted' ?>"><?= e(travel_expense_status_label($expense['payment_status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/travel-expenses/<?= e((string) $expense['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('travel_expenses.manage')): ?>
                            <a class="btn small" href="/travel-expenses/<?= e((string) $expense['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$expenses): ?>
                <tr><td colspan="9" class="empty">尚無出差費用資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function travel_expense_money($value): string
{
    return number_format((float) $value, 0);
}
function travel_expense_status_label(string $status): string
{
    return ['pending' => '待付款', 'paid' => '已付款', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
