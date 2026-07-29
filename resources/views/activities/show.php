<?php
$active = 'activities';
$documentTitle = '活動資料表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($activity['title']) ?></h2>
            <p class="muted-text"><?= e(activity_show_datetime($activity['starts_at'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/activities">返回列表</a>
            <?php if (\App\Core\Permission::can('activities.manage')): ?>
                <a class="btn" href="/activities/<?= e((string) $activity['id']) ?>/edit">編輯</a>
                <form method="post" action="/activities/<?= e((string) $activity['id']) ?>/status">
                    <?= csrf_field() ?>
                    <select name="status">
                        <option value="draft" <?= $activity['status'] === 'draft' ? 'selected' : '' ?>>草稿</option>
                        <option value="published" <?= $activity['status'] === 'published' ? 'selected' : '' ?>>已發布</option>
                        <option value="closed" <?= $activity['status'] === 'closed' ? 'selected' : '' ?>>結案</option>
                        <option value="cancelled" <?= $activity['status'] === 'cancelled' ? 'selected' : '' ?>>取消</option>
                    </select>
                    <button class="btn" type="submit">更新狀態</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>活動名稱</th>
            <td colspan="3"><?= e($activity['title']) ?></td>
        </tr>
        <tr>
            <th>開始時間</th>
            <td><?= e(activity_show_datetime($activity['starts_at'])) ?></td>
            <th>結束時間</th>
            <td><?= e(activity_show_datetime($activity['ends_at'])) ?></td>
        </tr>
        <tr>
            <th>地點</th>
            <td><?= e($activity['location'] ?: '-') ?></td>
            <th>狀態</th>
            <td><?= e(activity_show_status_label($activity['status'])) ?></td>
        </tr>
        <tr>
            <th>所屬專案</th>
            <td>
                <?php if (!empty($activity['project_id'])): ?>
                    <a class="text-link" href="/projects/<?= e((string) $activity['project_id']) ?>">
                        <?= e(($activity['project_code'] ? $activity['project_code'] . ' / ' : '') . $activity['project_name']) ?>
                    </a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <th>行事曆連動</th>
            <td>
                <?php if ($calendarEvent): ?>
                    <a class="text-link" href="/calendar/<?= e((string) $calendarEvent['id']) ?>">已建立行事曆事件</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($activity['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($activity['updated_at'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>活動說明</th>
            <td colspan="3"><?= nl2br(e($activity['description'] ?: '-')) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>志工</th>
                <th>服務日期</th>
                <th>服務內容</th>
                <th class="amount">時數</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($volunteerLogs as $log): ?>
                <tr>
                    <td><?= e($log['volunteer_name']) ?></td>
                    <td><?= e(roc_date($log['served_on'])) ?></td>
                    <td><?= e($log['description'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $log['hours'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$volunteerLogs): ?>
                <tr><td colspan="4" class="empty">尚無志工服務紀錄。</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="3">志工時數合計</th>
                <th class="amount"><?= e(number_format(array_sum(array_map(static fn (array $log): float => (float) $log['hours'], $volunteerLogs)), 2)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function activity_show_status_label(string $status): string
{
    return ['draft' => '草稿', 'published' => '已發布', 'closed' => '結案', 'cancelled' => '取消'][$status] ?? $status;
}
function activity_show_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return roc_date(substr($value, 0, 10)) . ' ' . substr($value, 11, 5);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
