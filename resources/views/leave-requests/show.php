<?php
$active = 'leave-requests';
$documentTitle = '請假單明細';
$canManage = \App\Core\Permission::can('leave_requests.manage');
$canApprove = \App\Core\Permission::can('leave_requests.approve');
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <p class="eyebrow">業務與人事</p>
            <h2><?= e($request['employee_name']) ?> / <?= e($request['leave_type_name']) ?></h2>
            <p class="muted-text"><?= e(roc_date_range($request['start_date'], $request['end_date'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/leave-requests">返回列表</a>
            <?php if ($canManage): ?>
                <a class="btn" href="/leave-requests/<?= e((string) $request['id']) ?>/edit">編輯</a>
                <?php if (in_array($request['status'], ['draft', 'rejected'], true)): ?>
                    <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/submit">
                        <?= csrf_field() ?>
                        <button class="btn primary" type="submit">送審</button>
                    </form>
                <?php endif; ?>
                <?php if ($request['status'] !== 'cancelled'): ?>
                    <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/cancel">
                        <?= csrf_field() ?>
                        <input type="hidden" name="review_notes" value="">
                        <button class="btn" type="submit">取消</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr><th>請假人</th><td><?= e($request['employee_name']) ?></td><th>員工編號</th><td><?= e($request['employee_no'] ?: '-') ?></td></tr>
        <tr><th>部門</th><td><?= e($request['department'] ?: '-') ?></td><th>職稱</th><td><?= e($request['job_title'] ?: '-') ?></td></tr>
        <tr><th>假別</th><td><?= e($request['leave_type_name']) ?></td><th>薪資計算</th><td><?= e(leave_show_paid_label($request['paid'])) ?></td></tr>
        <tr><th>請假期間</th><td><?= e(roc_date_range($request['start_date'], $request['end_date'])) ?></td><th>時間</th><td><?= e(substr((string) $request['start_time'], 0, 5) ?: '-') ?> ~ <?= e(substr((string) $request['end_time'], 0, 5) ?: '-') ?></td></tr>
        <tr><th>請假時數</th><td><?= e(number_format((float) $request['total_hours'], 2)) ?></td><th>狀態</th><td><?= e(leave_show_status_label($request['status'])) ?></td></tr>
        <tr><th>請假事由</th><td colspan="3"><?= e($request['reason']) ?></td></tr>
        <tr><th>代理人</th><td><?= e($request['handover_person'] ?: '-') ?></td><th>填寫人</th><td><?= e($request['created_by_name'] ?: '-') ?></td></tr>
        <tr><th>核決人</th><td><?= e($request['reviewed_by_name'] ?: '-') ?></td><th>核決時間</th><td><?= e(leave_show_datetime($request['reviewed_at'] ?? '')) ?></td></tr>
        </tbody>
    </table>

    <?php if (!empty($request['review_notes'])): ?>
        <p class="print-notes">核決備註：<?= nl2br(e($request['review_notes'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($request['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($request['notes'])) ?></p>
    <?php endif; ?>

    <div class="section-title">
        <h3>簽核流程</h3>
        <p class="muted-text">送審後由主管或具核准權限的人員核准或退回。</p>
    </div>

    <?php if ($canApprove && $request['status'] === 'submitted'): ?>
        <form class="form approval-form no-print" method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/approve">
            <?= csrf_field() ?>
            <label>
                <span>核准備註</span>
                <textarea name="review_notes" rows="2"></textarea>
            </label>
            <div class="form-actions">
                <button class="btn primary" type="submit">核准</button>
                <button class="btn" type="submit" formaction="/leave-requests/<?= e((string) $request['id']) ?>/reject">退回</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>時間</th>
                <th>動作</th>
                <th>狀態</th>
                <th>送審人</th>
                <th>核決人</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($approvalHistory ?? []) as $history): ?>
                <tr>
                    <td><?= e(leave_show_datetime($history['created_at'] ?? '')) ?></td>
                    <td><?= e(leave_show_approval_action((string) $history['action'])) ?></td>
                    <td><?= e(leave_show_request_status((string) $history['status'])) ?></td>
                    <td><?= e($history['requested_by_name'] ?? '-') ?></td>
                    <td><?= e($history['reviewed_by_name'] ?? '-') ?></td>
                    <td><?= e($history['review_notes'] ?: ($history['request_notes'] ?: '-')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($approvalHistory)): ?>
                <tr><td colspan="6" class="empty-state">尚無簽核紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function leave_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '送審中', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
}

function leave_show_approval_action(string $action): string
{
    return ['submit' => '送審', 'approved' => '核准', 'rejected' => '退回'][$action] ?? $action;
}

function leave_show_request_status(string $status): string
{
    return ['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
}

function leave_show_paid_label(string $paid): string
{
    return ['yes' => '有薪', 'no' => '無薪', 'partial' => '部分有薪'][$paid] ?? $paid;
}

function leave_show_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $date = substr($datetime, 0, 10);
    $time = substr($datetime, 11, 5);

    return roc_date($date) . ($time ? ' ' . $time : '');
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
