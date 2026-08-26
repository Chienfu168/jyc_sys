<?php
$active = 'calendar';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>本月事件</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>待執行</span>
        <strong><?= e(number_format((int) $summary['upcoming'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>已完成</span>
        <strong><?= e(number_format((int) $summary['done'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/calendar">
            <input type="month" name="month" value="<?= e($month) ?>">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="標題、地點、承辦">
            <select name="type">
                <option value="">全部類型</option>
                <option value="meeting" <?= $type === 'meeting' ? 'selected' : '' ?>>會議</option>
                <option value="activity" <?= $type === 'activity' ? 'selected' : '' ?>>活動</option>
                <option value="project" <?= $type === 'project' ? 'selected' : '' ?>>專案</option>
                <option value="deadline" <?= $type === 'deadline' ? 'selected' : '' ?>>期限</option>
                <option value="leave" <?= $type === 'leave' ? 'selected' : '' ?>>請假</option>
                <option value="reminder" <?= $type === 'reminder' ? 'selected' : '' ?>>提醒</option>
                <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>其他</option>
            </select>
            <select name="status">
                <option value="">全部狀態</option>
                <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>排程中</option>
                <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>已完成</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/calendar?month=<?= e($prevMonth) ?>">上個月</a>
            <a class="btn" href="/calendar?month=<?= e($nextMonth) ?>">下個月</a>
            <?php if (\App\Core\Permission::can('calendar.manage')): ?>
                <a class="btn" href="/calendar-feeds">連結外部日曆</a>
                <a class="btn primary" href="/calendar/create">新增事件</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($feeds ?? [])): ?>
        <div class="calendar-legend">
            <span class="muted-text">外部日曆：</span>
            <?php foreach ($feeds as $feed): ?>
                <span class="calendar-legend-item">
                    <span class="calendar-legend-dot" style="background: <?= e($feed['color']) ?>;"></span>
                    <?= e($feed['name']) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="calendar-grid">
        <?php foreach (['日', '一', '二', '三', '四', '五', '六'] as $dayName): ?>
            <div class="calendar-weekday"><?= e($dayName) ?></div>
        <?php endforeach; ?>
        <?php foreach ($weeks as $week): ?>
            <?php foreach ($week as $day): ?>
                <?php $inMonth = substr($day['date'], 0, 7) === $month; ?>
                <div class="calendar-day <?= $inMonth ? '' : 'outside' ?>">
                    <div class="calendar-date">
                        <strong><?= e($day['day']) ?></strong>
                        <span><?= e(roc_date($day['date'])) ?></span>
                    </div>
                    <div class="calendar-events">
                        <?php foreach (array_slice($day['events'], 0, 3) as $event): ?>
                            <?php if (!empty($event['external'])): ?>
                                <span class="calendar-event external" style="border-left-color: <?= e($event['feed_color'] ?? '#4285F4') ?>;" title="<?= e(($event['feed_name'] ?? '外部日曆') . '：' . $event['title']) ?>">
                                    <span><?= e(calendar_time($event)) ?></span>
                                    <?= e($event['title']) ?>
                                </span>
                            <?php else: ?>
                                <a class="calendar-event <?= e($event['event_type']) ?>" href="/calendar/<?= e((string) $event['id']) ?>">
                                    <span><?= e(calendar_time($event)) ?></span>
                                    <?= e($event['title']) ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (count($day['events']) > 3): ?>
                            <span class="calendar-more">另有 <?= e((string) (count($day['events']) - 3)) ?> 筆</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>事件清單</h2>
            <p class="muted-text"><?= e(roc_date($month . '-01')) ?> 起，本月符合查詢條件的事件。</p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>時間</th>
                <th>事件</th>
                <th>類型</th>
                <th>地點</th>
                <th>承辦</th>
                <th>提醒</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td>
                        <?= e(calendar_datetime($event['starts_at'])) ?>
                        <?php if (!empty($event['ends_at'])): ?>
                            <div class="muted-text">至 <?= e(calendar_datetime($event['ends_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($event['title']) ?></strong></td>
                    <td><?= e(calendar_type_label($event['event_type'])) ?></td>
                    <td><?= e($event['location'] ?: '-') ?></td>
                    <td><?= e($event['owner_name'] ?: '-') ?></td>
                    <td><?= e(calendar_reminder_label((int) $event['reminder_minutes'])) ?></td>
                    <td><span class="badge <?= $event['status'] === 'scheduled' ? 'ok' : 'muted' ?>"><?= e(calendar_status_label($event['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/calendar/<?= e((string) $event['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('calendar.manage')): ?>
                            <a class="btn small" href="/calendar/<?= e((string) $event['id']) ?>/edit">編輯</a>
                            <?php if (empty($event['source_module'])): ?>
                                <form method="post" action="/calendar/<?= e((string) $event['id']) ?>/delete" onsubmit="return confirm('確定要刪除此事件？此操作無法復原。');">
                                    <?= csrf_field() ?>
                                    <button class="btn small" type="submit">刪除</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$events): ?>
                <tr><td colspan="8" class="empty">查無行事曆事件。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function calendar_status_label(string $status): string
{
    return ['scheduled' => '排程中', 'done' => '已完成', 'cancelled' => '已取消'][$status] ?? $status;
}
function calendar_type_label(string $type): string
{
    return ['meeting' => '會議', 'activity' => '活動', 'project' => '專案', 'deadline' => '期限', 'leave' => '請假', 'reminder' => '提醒', 'other' => '其他'][$type] ?? $type;
}
function calendar_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return roc_date(substr($value, 0, 10)) . ' ' . substr($value, 11, 5);
}
function calendar_time(array $event): string
{
    if ((int) $event['all_day'] === 1) {
        return '全天';
    }
    return substr($event['starts_at'], 11, 5);
}
function calendar_reminder_label(int $minutes): string
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
