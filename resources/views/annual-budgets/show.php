<?php
$active = 'annual-budgets';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>收入合計</span>
        <strong><?= e(number_format($totals['income'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>支出合計</span>
        <strong><?= e(number_format($totals['expense'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>餘絀</span>
        <strong><?= e(number_format($totals['balance'], 0)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($budget['fiscal_year'] . ' 年度預算') ?></h2>
            <p class="muted-text"><?= e($budget['notes'] ?? '') ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/annual-budgets">返回</a>
            <?php if (\App\Core\Permission::can('annual_budgets.manage')): ?>
                <a class="btn" href="/annual-budgets/<?= e((string) $budget['id']) ?>/edit">編輯</a>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('annual_budgets.approve') && $budget['status'] !== 'approved'): ?>
                <form method="post" action="/annual-budgets/<?= e((string) $budget['id']) ?>/approve">
                    <?= csrf_field() ?>
                    <button class="btn primary" type="submit">核定</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>類型</th>
                <th>科目</th>
                <th>項目</th>
                <th class="amount">金額</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['item_type'] === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($item['category']) ?></td>
                    <td><?= e($item['item_name']) ?></td>
                    <td class="amount"><?= e(number_format((float) $item['amount'], 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="4" class="empty">尚無預算明細</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
