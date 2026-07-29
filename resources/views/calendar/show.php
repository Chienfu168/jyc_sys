<?php
$active = 'calendar';
$documentTitle = '行事曆事件紀錄';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($event['title']) ?></h2>
            <p class="muted-text"><?= e(calendar_show_datetime($event['starts_at'])) ?> / <?= e(calendar_show_type_label($event['event_type'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/calendar">返回行事曆</a>
            <?php if (\App\Core\Permission::can('calendar.manage')): ?>
                <a class="btn" href="/calendar/<?= e((string) $event['id']) ?>/edit">編輯</a>
                <form method="post" action="/calendar/<?= e((string) $event['id']) ?>/status">
                    <?= csrf_field() ?>
                    <select name="status">
                        <option value="scheduled" <?= $event['status'] === 'scheduled' ? 'selected' : '' ?>>排程中</option>
                        <option value="done" <?= $event['status'] === 'done' ? 'selected' : '' ?>>已完成</option>
                        <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>已取消</option>
                    </select>
                    <button class="btn" type="submit">更新狀態</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>事件標題</th>
            <td><?= e($event['title']) ?></td>
            <th>事件類型</th>
            <td><?= e(calendar_show_type_label($event['event_type'])) ?></td>
        </tr>
        <tr>
            <th>開始時間</th>
            <td><?= e(calendar_show_datetime($event['starts_at'])) ?></td>
            <th>結束時間</th>
            <td><?= e(calendar_show_datetime($event['ends_at'])) ?></td>
        </tr>
        <tr>
            <th>全天事件</th>
            <td><?= (int) $event['all_day'] === 1 ? '是' : '否' ?></td>
            <th>狀態</th>
            <td><?= e(calendar_show_status_label($event['status'])) ?></td>
        </tr>
        <tr>
            <th>地點</th>
            <td><?= e($event['location'] ?: '-') ?></td>
            <th>承辦人</th>
            <td><?= e($event['owner_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>提醒時間</th>
            <td><?= e(calendar_show_reminder_label((int) $event['reminder_minutes'])) ?></td>
            <th>建檔人</th>
            <td><?= e($event['created_by_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>來源模組</th>
            <td><?= e($event['source_module'] ?: '-') ?></td>
            <th>來源編號</th>
            <td><?= e($event['source_id'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>事件說明</th>
            <td colspan="3"><?= nl2br(e($event['description'] ?: '-')) ?></td>
        </tr>
        </tbody>
    </table>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function calendar_show_status_label(string $status): string
{
    return ['scheduled' => '排程中', 'done' => '已完成', 'cancelled' => '已取消'][$status] ?? $status;
}
function calendar_show_type_label(string $type): string
{
    return ['meeting' => '會議', 'activity' => '活動', 'project' => '專案', 'deadline' => '期限', 'leave' => '請假', 'reminder' => '提醒', 'other' => '其他'][$type] ?? $type;
}
function calendar_show_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return roc_date(substr($value, 0, 10)) . ' ' . substr($value, 11, 5);
}
function calendar_show_reminder_label(int $minutes): string
{
    if ($minutes === 0) {
        return '不提醒';
    }
    if ($minutes >= 1440 && $minutes % 1440 === 0) {
        return (string) ($minutes / 1440) . ' 天前';
    }
    if ($minutes >= 60 && $minutes % 60 === 0) {
        return (string) ($minutes / 60) . ' 小時前';
    }
    return $minutes . ' 分鐘前';
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
