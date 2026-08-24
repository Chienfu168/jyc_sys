<?php
$active = 'leave-requests';
$canManage = \App\Core\Permission::can('leave_requests.manage');
$canApprove = \App\Core\Permission::can('leave_requests.approve');
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢筆數</span>
        <strong><?= e(number_format((int) $totals['count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>請假時數</span>
        <strong><?= e(number_format((float) $totals['hours'], 2)) ?></strong>
    </div>
    <div class="stat-card">
        <span>已核准時數</span>
        <strong><?= e(number_format((float) $totals['approved_hours'], 2)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/leave-requests">
            <input type="month" name="month" value="<?= e($month) ?>">
            <select name="status">
                <option value="">全部狀態</option>
                <?php foreach (['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">業務與人事</a>
            <?php if ($canManage): ?>
                <a class="btn primary" href="/leave-requests/create">新增請假</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>期間</th>
                <th>請假人</th>
                <th>假別</th>
                <th class="amount">時數</th>
                <th>事由</th>
                <th>代理人</th>
                <th>狀態</th>
                <th class="no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= e(roc_date($request['start_date'])) ?> ~ <?= e(roc_date($request['end_date'])) ?></td>
                    <td>
                        <strong><?= e($request['employee_name']) ?></strong>
                        <div class="muted-text"><?= e(trim(($request['department'] ?: '-') . ' / ' . ($request['job_title'] ?: '-'))) ?></div>
                    </td>
                    <td><?= e($request['leave_type_name']) ?></td>
                    <td class="amount"><?= e(number_format((float) $request['total_hours'], 2)) ?></td>
                    <td><?= e($request['reason']) ?></td>
                    <td><?= e($request['handover_person'] ?: '-') ?></td>
                    <td><span class="badge <?= $request['status'] === 'approved' ? 'ok' : 'muted' ?>"><?= e(leave_status_label($request['status'])) ?></span></td>
                    <td class="table-actions no-print">
                        <a class="btn small" href="/leave-requests/<?= e((string) $request['id']) ?>">查看</a>
                        <?php if ($canManage): ?>
                            <a class="btn small" href="/leave-requests/<?= e((string) $request['id']) ?>/edit">編輯</a>
                            <?php if (in_array($request['status'], ['draft', 'rejected'], true)): ?>
                                <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/submit">
                                    <?= csrf_field() ?>
                                    <button class="btn small primary" type="submit">送審</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/delete" onsubmit="return confirm('確定要刪除此筆請假申請？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($canApprove && $request['status'] === 'submitted'): ?>
                            <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/approve">
                                <?= csrf_field() ?>
                                <button class="btn small primary" type="submit">核准</button>
                            </form>
                            <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/reject">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">退回</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?>
                <tr><td colspan="8" class="empty-state">目前沒有請假資料</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function leave_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
