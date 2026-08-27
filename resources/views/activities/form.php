<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label class="span-2">
        <span>活動名稱</span>
        <input type="text" name="title" value="<?= e((string) old('title', $activity['title'] ?? '')) ?>" required>
    </label>
    <label>
        <span>開始時間</span>
        <input type="datetime-local" name="starts_at" value="<?= e(activity_form_datetime((string) old('starts_at', $activity['starts_at'] ?? date('Y-m-d H:i')))) ?>" required>
    </label>
    <label>
        <span>結束時間</span>
        <input type="datetime-local" name="ends_at" value="<?= e(activity_form_datetime((string) old('ends_at', $activity['ends_at'] ?? ''))) ?>">
    </label>
    <label>
        <span>地點</span>
        <input type="text" name="location" value="<?= e((string) old('location', $activity['location'] ?? '')) ?>">
    </label>
    <label>
        <span>所屬專案</span>
        <?php $selectedProject = (string) old('project_id', $activity['project_id'] ?? ''); ?>
        <select name="project_id">
            <option value="">未指定專案</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?= e((string) $project['id']) ?>" <?= $selectedProject === (string) $project['id'] ? 'selected' : '' ?>>
                    <?= e(($project['project_code'] ? $project['project_code'] . ' / ' : '') . $project['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>課程週次<small class="field-hint">（歸屬專案時填，如 1、2…16）</small></span>
        <input type="number" name="session_no" min="1" max="60" value="<?= e((string) old('session_no', $activity['session_no'] ?? '')) ?>" placeholder="非課程可留空">
    </label>
    <label class="span-2">
        <span>單元主題<small class="field-hint">（該週課程主題，可留空）</small></span>
        <input type="text" name="session_topic" value="<?= e((string) old('session_topic', $activity['session_topic'] ?? '')) ?>">
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $activity['status'] ?? 'draft'); ?>
        <select name="status">
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
            <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>已發布</option>
            <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>結案</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>取消</option>
        </select>
    </label>
    <label class="span-2">
        <span>活動說明</span>
        <textarea name="description"><?= e((string) old('description', $activity['description'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/activities">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
<?php
function activity_form_datetime(string $value): string
{
    if ($value === '') {
        return '';
    }
    return str_replace(' ', 'T', substr($value, 0, 16));
}
