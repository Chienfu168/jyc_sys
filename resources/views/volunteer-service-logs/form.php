<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>服務日期</span>
        <input type="date" name="served_on" value="<?= e((string) old('served_on', $log['served_on'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>服務時數</span>
        <input type="number" min="0" step="0.5" name="hours" value="<?= e((string) old('hours', $log['hours'] ?? '')) ?>" required>
    </label>
    <label class="span-2">
        <span>活動</span>
        <?php $selectedActivity = (string) old('activity_id', $log['activity_id'] ?? ''); ?>
        <select name="activity_id">
            <option value="">未指定活動</option>
            <?php foreach ($activities as $activity): ?>
                <option value="<?= e((string) $activity['id']) ?>" <?= $selectedActivity === (string) $activity['id'] ? 'selected' : '' ?>>
                    <?= e($activity['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="span-2">
        <span>服務內容</span>
        <input type="text" name="description" value="<?= e((string) old('description', $log['description'] ?? '')) ?>">
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/volunteers/<?= e((string) $volunteer['id']) ?>">返回志工資料</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
