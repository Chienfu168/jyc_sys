<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>預算經費表</h2>
            <p class="muted-text">非營利組織年度預算、專案預算與補助計畫經費表。</p>
        </div>
        <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
            <a class="btn primary" href="/annual-budgets/create">新增預算</a>
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
                    <td><span class="badge <?= $budget['status'] === 'approved' ? 'ok' : 'muted' ?>"><?= e(budget_status_label($budget['status'])) ?></span></td>
                    <td class="amount"><?= e(budget_money($budget['income_total'])) ?></td>
                    <td class="amount"><?= e(budget_money($budget['expense_total'])) ?></td>
                    <td class="amount"><?= e(budget_money($balance)) ?></td>
                    <td><?= e($budget['created_by_name'] ?? '-') ?></td>
                    <td class="actions">
                        <a class="btn small" href="/annual-budgets/<?= e((string) $budget['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
                            <a class="btn small" href="/annual-budgets/<?= e((string) $budget['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$budgets): ?>
                <tr><td colspan="8" class="empty">尚未建立預算經費表。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function budget_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定'][$status] ?? $status;
}
function budget_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
