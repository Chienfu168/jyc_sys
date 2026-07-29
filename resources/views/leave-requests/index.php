<?php
$active = 'leave-requests';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢筆數</span>
        <strong><?= e(number_format((int) $totals['count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>申請時數</span>
        <strong><?= e(number_format((float) $totals['hours'], 2)) ?></strong>
    </div>
    <div class="stat-card">
        <span>核准時數</span>
        <strong><?= e(number_format((float) $totals['approved_hours'], 2)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/leave-requests">
            <input type="month" name="month" value="<?= e($month) ?>">
            <select name="status">
                <option value="">全部狀態</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>待審</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>已核准</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>退回</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>取消</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">返回業務與人事</a>
            <?php if (\App\Core\Permission::can('leave_requests.manage')): ?>
                <a class="btn primary" href="/leave-requests/create">新增請假</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>期間</th>
                <th>請假人</th>
                <th>假別</th>
                <th class="amount">時數</th>
                <th>事由</th>
                <th>職務代理</th>
                <th>狀態</th>
                <th class="actions">操作</th>
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
                    <td class="actions">
                        <a class="btn small" href="/leave-requests/<?= e((string) $request['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('leave_requests.manage')): ?>
                            <a class="btn small" href="/leave-requests/<?= e((string) $request['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$requests): ?>
                <tr><td colspan="8" class="empty">尚無請假資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function leave_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '待審', 'approved' => '已核准', 'rejected' => '退回', 'cancelled' => '取消'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
