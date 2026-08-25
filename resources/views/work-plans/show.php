<?php
$active = 'work-plans';
$documentTitle = '工作計畫';
$canManage = \App\Core\Permission::can('work_plans.manage');
$canApprove = \App\Core\Permission::can('work_plans.approve');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">治理文件</p>
            <h2><?= e($plan['title']) ?></h2>
            <p class="muted-text"><?= e(roc_year_label($plan['fiscal_year'])) ?> / <?= e(work_plan_show_status_label($plan['status'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/work-plans">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/work-plans/<?= e((string) $plan['id']) ?>/edit">編輯</a>
                <?php if ($plan['status'] === 'draft'): ?>
                    <form method="post" action="/work-plans/<?= e((string) $plan['id']) ?>/submit">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">送審</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (\App\Core\Permission::can('work_plans.delete')): ?>
                <form method="post" action="/work-plans/<?= e((string) $plan['id']) ?>/delete" onsubmit="return confirm('確定要刪除此工作計畫？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn danger" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>計畫名稱</th><td colspan="3"><?= e($plan['title']) ?></td></tr>
        <tr><th>年度</th><td><?= e(roc_year_label($plan['fiscal_year'])) ?></td><th>計畫編號</th><td><?= e($plan['plan_code'] ?: '-') ?></td></tr>
        <tr><th>承辦單位</th><td><?= e($plan['department'] ?: '-') ?></td><th>負責人</th><td><?= e($plan['owner_name'] ?: '-') ?></td></tr>
        <tr><th>執行期間</th><td><?= e(roc_date_range($plan['period_start'] ?? null, $plan['period_end'] ?? null)) ?></td><th>預算總計</th><td><?= e(number_format((float) $budgetTotal, 0)) ?></td></tr>
        <tr><th>計畫目標</th><td colspan="3"><?= nl2br(e($plan['objective'] ?: '-')) ?></td></tr>
        <tr><th>服務對象</th><td colspan="3"><?= nl2br(e($plan['target_audience'] ?: '-')) ?></td></tr>
        <tr><th>服務區域</th><td colspan="3"><?= e($plan['service_area'] ?: '-') ?></td></tr>
        <tr><th>預期成果</th><td colspan="3"><?= nl2br(e($plan['expected_outcomes'] ?: '-')) ?></td></tr>
        <tr><th>績效指標</th><td colspan="3"><?= nl2br(e($plan['performance_indicators'] ?: '-')) ?></td></tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>期程</th>
                <th>工作項目</th>
                <th>執行內容</th>
                <th>預期產出</th>
                <th class="amount">預算金額</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['period_label'] ?: '-') ?></td>
                    <td><?= e($item['activity_name']) ?></td>
                    <td><?= e($item['description'] ?? '') ?></td>
                    <td><?= e($item['expected_output'] ?? '') ?></td>
                    <td class="amount"><?= e(number_format((float) $item['budget_amount'], 0)) ?></td>
                    <td><?= e($item['notes'] ?? '') ?: '-' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?>
                <tr><td colspan="6" class="empty-state">尚無工作項目</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr><th colspan="4">預算總計</th><th class="amount"><?= e(number_format((float) $budgetTotal, 0)) ?></th><th></th></tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($plan['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($plan['notes'])) ?></p>
    <?php endif; ?>

    <?php
    $approvalTargetId = (int) $plan['id'];
    $approvalStatus = (string) $plan['status'];
    $approvalApproveUrl = '/work-plans/' . $approvalTargetId . '/approve';
    $approvalRejectUrl = '/work-plans/' . $approvalTargetId . '/reject';
    $approvalCanApprove = $canApprove;
    ?>
    <?php require base_path('resources/views/shared/approval-section.php'); ?>
    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function work_plan_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'closed' => '已結案'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
