<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label class="span-2">
        <span>事件標題</span>
        <input type="text" name="title" value="<?= e((string) old('title', $event['title'] ?? '')) ?>" required>
    </label>
    <label>
        <span>事件類型</span>
        <?php $type = old('event_type', $event['event_type'] ?? 'meeting'); ?>
        <select name="event_type" required>
            <option value="meeting" <?= $type === 'meeting' ? 'selected' : '' ?>>會議</option>
            <option value="activity" <?= $type === 'activity' ? 'selected' : '' ?>>活動</option>
            <option value="project" <?= $type === 'project' ? 'selected' : '' ?>>專案</option>
            <option value="deadline" <?= $type === 'deadline' ? 'selected' : '' ?>>期限</option>
            <option value="leave" <?= $type === 'leave' ? 'selected' : '' ?>>請假</option>
            <option value="reminder" <?= $type === 'reminder' ? 'selected' : '' ?>>提醒</option>
            <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>其他</option>
        </select>
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $event['status'] ?? 'scheduled'); ?>
        <select name="status" required>
            <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>排程中</option>
            <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>已完成</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
        </select>
    </label>
    <label>
        <span>開始時間</span>
        <input type="datetime-local" name="starts_at" value="<?= e(calendar_form_datetime((string) old('starts_at', $event['starts_at'] ?? date('Y-m-d H:i')))) ?>" required>
    </label>
    <label>
        <span>結束時間</span>
        <input type="datetime-local" name="ends_at" value="<?= e(calendar_form_datetime((string) old('ends_at', $event['ends_at'] ?? ''))) ?>">
    </label>
    <label>
        <span>地點</span>
        <input type="text" name="location" value="<?= e((string) old('location', $event['location'] ?? '')) ?>">
    </label>
    <label>
        <span>承辦人</span>
        <input type="text" name="owner_name" value="<?= e((string) old('owner_name', $event['owner_name'] ?? '')) ?>">
    </label>
    <label>
        <span>提醒時間</span>
        <?php $reminder = (string) old('reminder_minutes', $event['reminder_minutes'] ?? '60'); ?>
        <select name="reminder_minutes">
            <option value="0" <?= $reminder === '0' ? 'selected' : '' ?>>不提醒</option>
            <option value="15" <?= $reminder === '15' ? 'selected' : '' ?>>15 分鐘前</option>
            <option value="30" <?= $reminder === '30' ? 'selected' : '' ?>>30 分鐘前</option>
            <option value="60" <?= $reminder === '60' ? 'selected' : '' ?>>1 小時前</option>
            <option value="120" <?= $reminder === '120' ? 'selected' : '' ?>>2 小時前</option>
            <option value="1440" <?= $reminder === '1440' ? 'selected' : '' ?>>1 天前</option>
        </select>
    </label>
    <label class="checkbox-field">
        <span>全天事件</span>
        <label class="inline-check">
            <input type="checkbox" name="all_day" value="1" <?= old('all_day', $event['all_day'] ?? 0) ? 'checked' : '' ?>>
            <span>標記為全天</span>
        </label>
    </label>
    <label class="span-2">
        <span>事件說明</span>
        <textarea name="description"><?= e((string) old('description', $event['description'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/calendar">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
<?php
function calendar_form_datetime(string $value): string
{
    if ($value === '') {
        return '';
    }
    return str_replace(' ', 'T', substr($value, 0, 16));
}
