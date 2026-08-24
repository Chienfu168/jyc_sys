<?php
$active = 'income-expenses';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>收入合計</span>
        <strong><?= e(income_expense_money($totals['income'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>支出合計</span>
        <strong><?= e(income_expense_money($totals['expense'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>餘絀</span>
        <strong><?= e(income_expense_money($totals['balance'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/income-expenses">
            <?php require base_path('resources/views/shared/date-scope-filter.php'); ?>
            <select name="type">
                <option value="">全部</option>
                <option value="income" <?= $type === 'income' ? 'selected' : '' ?>>收入</option>
                <option value="expense" <?= $type === 'expense' ? 'selected' : '' ?>>支出</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/income-expenses/report?year=<?= e((string) roc_year((int) substr($month, 0, 4))) ?>&month=<?= e(substr($month, 5, 2)) ?>">統計列印</a>
            <?php if (\App\Core\Permission::can('income_expenses.manage')): ?>
                <a class="btn primary" href="/income-expenses/create">新增收支</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>日期</th>
                <th>類型</th>
                <th>科目</th>
                <th>摘要</th>
                <th>對象</th>
                <th>付款方式</th>
                <th>銀行</th>
                <th>收據</th>
                <th>傳票</th>
                <th class="amount">金額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?= e(roc_date($record['occurred_on'])) ?></td>
                    <td><?= e($record['item_type'] === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($record['category_name']) ?></td>
                    <td><?= e($record['subject']) ?></td>
                    <td><?= e($record['counterparty'] ?: '-') ?></td>
                    <td><?= e($record['payment_method'] ?: '-') ?></td>
                    <td>
                        <?php if (!empty($record['bank_name'])): ?>
                            <?= e($record['bank_name']) ?>
                            <div class="muted-text mono"><?= e($record['account_no'] ?? '') ?></div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= e(income_expense_receipt_label($record['receipt_status'])) ?></td>
                    <td>
                        <?php if (!empty($record['accounting_voucher_id'])): ?>
                            <a class="text-link mono" href="/accounting/vouchers/<?= e((string) $record['accounting_voucher_id']) ?>"><?= e($record['voucher_no'] ?: '已建立') ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(income_expense_money($record['amount'])) ?></td>
                    <td><span class="badge <?= $record['status'] === 'confirmed' ? 'ok' : 'muted' ?>"><?= e(income_expense_status_label($record['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/income-expenses/<?= e((string) $record['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('income_expenses.manage')): ?>
                            <a class="btn small" href="/income-expenses/<?= e((string) $record['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$records): ?>
                <tr><td colspan="12" class="empty">本月份尚無收支紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function income_expense_money($value): string
{
    return number_format((float) $value, 0);
}
function income_expense_status_label(string $status): string
{
    return ['draft' => '草稿', 'confirmed' => '確認', 'voided' => '作廢'][$status] ?? $status;
}
function income_expense_receipt_label(string $status): string
{
    return ['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
