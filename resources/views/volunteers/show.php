<?php
$active = 'volunteers';
$documentTitle = '志工服務紀錄表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card">
        <span>服務紀錄</span>
        <strong><?= e(number_format((int) $totals['log_count'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>今年時數</span>
        <strong><?= e(number_format((float) $totals['year_hours'], 2)) ?></strong>
    </div>
    <div class="stat-card">
        <span>累計時數</span>
        <strong><?= e(number_format((float) $totals['total_hours'], 2)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($volunteer['name']) ?></h2>
            <p class="muted-text"><?= e($volunteer['status'] === 'active' ? '啟用' : '停用') ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/volunteers">返回列表</a>
            <?php if (\App\Core\Permission::can('volunteers.manage')): ?>
                <a class="btn" href="/volunteers/<?= e((string) $volunteer['id']) ?>/edit">編輯</a>
                <a class="btn primary" href="/volunteers/<?= e((string) $volunteer['id']) ?>/service-logs/create">新增時數</a>
                <form method="post" action="/volunteers/<?= e((string) $volunteer['id']) ?>/toggle">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit"><?= e($volunteer['status'] === 'active' ? '停用' : '啟用') ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>姓名</th>
            <td><?= e($volunteer['name']) ?></td>
            <th>狀態</th>
            <td><?= e($volunteer['status'] === 'active' ? '啟用' : '停用') ?></td>
        </tr>
        <tr>
            <th>電話</th>
            <td><?= e($volunteer['phone'] ?: '-') ?></td>
            <th>Email</th>
            <td><?= e($volunteer['email'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($volunteer['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($volunteer['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($volunteer['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($volunteer['notes'])) ?></p>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>服務日期</th>
                <th>活動</th>
                <th>服務內容</th>
                <th class="amount">時數</th>
                <th class="actions no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e(roc_date($log['served_on'])) ?></td>
                    <td><?= e($log['activity_title'] ?: '-') ?></td>
                    <td><?= e($log['description'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $log['hours'], 2)) ?></td>
                    <td class="actions no-print">
                        <?php if (\App\Core\Permission::can('volunteers.manage')): ?>
                            <a class="btn small" href="/volunteer-service-logs/<?= e((string) $log['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="5" class="empty">尚無服務時數紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3">累計時數</th>
                <th class="amount"><?= e(number_format((float) $totals['total_hours'], 2)) ?></th>
                <th class="no-print"></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
