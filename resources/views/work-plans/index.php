<?php
$active = 'work-plans';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <form class="search" method="get" action="/work-plans">
            <input type="number" name="year" min="2000" max="2100" value="<?= e((string) $year) ?>">
            <button class="btn" type="submit">查詢</button>
        </form>
        <?php if (\App\Core\Permission::can('work_plans.manage')): ?>
            <a class="btn primary" href="/work-plans/create">新增工作計畫</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>年度</th>
                <th>計畫名稱</th>
                <th>承辦單位</th>
                <th>負責人</th>
                <th>期程</th>
                <th class="amount">預算</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($plans as $plan): ?>
                <tr>
                    <td><?= e((string) $plan['fiscal_year']) ?></td>
                    <td><?= e($plan['title']) ?></td>
                    <td><?= e($plan['department'] ?: '-') ?></td>
                    <td><?= e($plan['owner_name'] ?: '-') ?></td>
                    <td><?= e(($plan['period_start'] ?: '-') . ' ~ ' . ($plan['period_end'] ?: '-')) ?></td>
                    <td class="amount"><?= e(number_format((float) $plan['budget_total'], 0)) ?></td>
                    <td><span class="badge <?= $plan['status'] === 'approved' ? 'ok' : 'muted' ?>"><?= e(work_plan_status_label($plan['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/work-plans/<?= e((string) $plan['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('work_plans.manage')): ?>
                            <a class="btn small" href="/work-plans/<?= e((string) $plan['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$plans): ?>
                <tr><td colspan="8" class="empty">本年度尚未建立工作計畫。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function work_plan_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審', 'approved' => '核定', 'closed' => '結案'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
