<?php
$active = 'lecturer-expenses';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>應付總額</span>
        <strong><?= e(lecturer_expense_money($totals['gross'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>代扣稅額</span>
        <strong><?= e(lecturer_expense_money($totals['tax'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>實付金額</span>
        <strong><?= e(lecturer_expense_money($totals['net'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/lecturer-expenses">
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
            <?php if (\App\Core\Permission::can('lecturer_expenses.manage')): ?>
                <a class="btn primary" href="/lecturer-expenses/create">新增講師費用</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>講師</th>
                <th>服務內容</th>
                <th>專案 / 活動</th>
                <th class="amount">時數</th>
                <th class="amount">應付</th>
                <th class="amount">實付</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($expenses as $expense): ?>
                <tr>
                    <td><?= e(roc_date($expense['expense_date'])) ?></td>
                    <td><?= e($expense['display_name'] ?: $expense['lecturer_name']) ?></td>
                    <td>
                        <strong><?= e($expense['service_title']) ?></strong>
                        <?php if (!empty($expense['service_unit'])): ?>
                            <div class="muted-text"><?= e($expense['service_unit']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($expense['project_name'] ?: '-') ?>
                        <?php if (!empty($expense['activity_name'])): ?>
                            <div class="muted-text"><?= e($expense['activity_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(number_format((float) $expense['hours'], 2)) ?></td>
                    <td class="amount"><?= e(lecturer_expense_money($expense['gross_total'])) ?></td>
                    <td class="amount"><?= e(lecturer_expense_money($expense['net_total'])) ?></td>
                    <td><span class="badge <?= $expense['payment_status'] === 'paid' ? 'ok' : 'muted' ?>"><?= e(lecturer_expense_status_label($expense['payment_status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('lecturer_expenses.manage')): ?>
                            <a class="btn small" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$expenses): ?>
                <tr><td colspan="9" class="empty">尚無講師費用資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function lecturer_expense_money($value): string
{
    return number_format((float) $value, 0);
}
function lecturer_expense_status_label(string $status): string
{
    return ['pending' => '待付款', 'paid' => '已付款', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
