<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>年度預算清單</h2>
        <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
            <a class="btn primary" href="/annual-budgets/create">新增年度預算</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>年度</th>
                <th>名稱</th>
                <th>狀態</th>
                <th class="amount">收入合計</th>
                <th class="amount">支出合計</th>
                <th class="amount">餘絀</th>
                <th>建立人</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($budgets as $budget): ?>
                <?php $balance = (float) $budget['income_total'] - (float) $budget['expense_total']; ?>
                <tr>
                    <td><?= e((string) $budget['fiscal_year']) ?></td>
                    <td><?= e($budget['title']) ?></td>
                    <td><span class="badge <?= $budget['status'] === 'approved' ? 'ok' : 'muted' ?>"><?= e(status_label($budget['status'])) ?></span></td>
                    <td class="amount"><?= e(money($budget['income_total'])) ?></td>
                    <td class="amount"><?= e(money($budget['expense_total'])) ?></td>
                    <td class="amount"><?= e(money($balance)) ?></td>
                    <td><?= e($budget['created_by_name'] ?? '-') ?></td>
                    <td class="actions">
                        <a class="btn small" href="/annual-budgets/<?= e((string) $budget['id']) ?>">查看</a>
                        <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
                            <a class="btn small" href="/annual-budgets/<?= e((string) $budget['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$budgets): ?>
                <tr><td colspan="8" class="empty">尚無年度預算</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定'][$status] ?? $status;
}
function money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
