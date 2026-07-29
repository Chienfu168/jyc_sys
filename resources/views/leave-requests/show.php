<?php
$active = 'leave-requests';
$documentTitle = '請假申請單';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($request['employee_name']) ?> / <?= e($request['leave_type_name']) ?></h2>
            <p class="muted-text"><?= e(roc_date_range($request['start_date'], $request['end_date'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/leave-requests">返回列表</a>
            <?php if (\App\Core\Permission::can('leave_requests.manage')): ?>
                <a class="btn" href="/leave-requests/<?= e((string) $request['id']) ?>/edit">編輯</a>
                <?php if ($request['status'] === 'submitted' || $request['status'] === 'draft'): ?>
                    <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/approve">
                        <?= csrf_field() ?>
                        <input type="hidden" name="review_notes" value="">
                        <button class="btn primary" type="submit">核准</button>
                    </form>
                    <form method="post" action="/leave-requests/<?= e((string) $request['id']) ?>/reject">
                        <?= csrf_field() ?>
                        <input type="hidden" name="review_notes" value="">
                        <button class="btn" type="submit">退回</button>
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
        <tr>
            <th>請假人</th>
            <td><?= e($request['employee_name']) ?></td>
            <th>員工編號</th>
            <td><?= e($request['employee_no'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>部門</th>
            <td><?= e($request['department'] ?: '-') ?></td>
            <th>職稱</th>
            <td><?= e($request['job_title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>假別</th>
            <td><?= e($request['leave_type_name']) ?></td>
            <th>給薪設定</th>
            <td><?= e(leave_show_paid_label($request['paid'])) ?></td>
        </tr>
        <tr>
            <th>請假期間</th>
            <td><?= e(roc_date_range($request['start_date'], $request['end_date'])) ?></td>
            <th>時間</th>
            <td><?= e(substr((string) $request['start_time'], 0, 5) ?: '-') ?> ~ <?= e(substr((string) $request['end_time'], 0, 5) ?: '-') ?></td>
        </tr>
        <tr>
            <th>請假時數</th>
            <td><?= e(number_format((float) $request['total_hours'], 2)) ?></td>
            <th>狀態</th>
            <td><?= e(leave_show_status_label($request['status'])) ?></td>
        </tr>
        <tr>
            <th>請假事由</th>
            <td colspan="3"><?= e($request['reason']) ?></td>
        </tr>
        <tr>
            <th>職務代理人</th>
            <td><?= e($request['handover_person'] ?: '-') ?></td>
            <th>建檔人</th>
            <td><?= e($request['created_by_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>審核人</th>
            <td><?= e($request['reviewed_by_name'] ?: '-') ?></td>
            <th>審核時間</th>
            <td><?= e($request['reviewed_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($request['review_notes'])): ?>
        <p class="print-notes">審核備註：<?= nl2br(e($request['review_notes'])) ?></p>
    <?php endif; ?>
    <?php if (!empty($request['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($request['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function leave_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'submitted' => '待審', 'approved' => '已核准', 'rejected' => '退回', 'cancelled' => '取消'][$status] ?? $status;
}
function leave_show_paid_label(string $paid): string
{
    return ['yes' => '給薪', 'no' => '不給薪', 'partial' => '部分給薪'][$paid] ?? $paid;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
