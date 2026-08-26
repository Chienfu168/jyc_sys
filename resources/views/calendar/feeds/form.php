<?php
$selectedStatus = (string) old('status', $feed['status'] ?? 'active');
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="form-section">
        <div class="grid-form">
            <label>
                <span>名稱</span>
                <input type="text" name="name" value="<?= e((string) old('name', $feed['name'] ?? '')) ?>" placeholder="例：國定假日、董事長行程" required>
            </label>
            <label>
                <span>顏色</span>
                <input type="color" name="color" value="<?= e((string) old('color', $feed['color'] ?? '#4285F4')) ?>">
            </label>
            <label class="span-2">
                <span>iCal（.ics）訂閱網址</span>
                <input type="url" name="ics_url" value="<?= e((string) old('ics_url', $feed['ics_url'] ?? '')) ?>" placeholder="https://calendar.google.com/calendar/ical/…/public/basic.ics" required>
                <small class="muted-text">Google 日曆 →「設定和共用」→「整合日曆」→「iCal 格式的公開網址」。日曆需設為公開。</small>
            </label>
            <label>
                <span>排序</span>
                <input type="number" name="sort_order" min="0" value="<?= e((string) old('sort_order', $feed['sort_order'] ?? 10)) ?>">
            </label>
            <label>
                <span>狀態</span>
                <select name="status">
                    <?php foreach (['active' => '啟用', 'disabled' => '停用'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
    </div>
    <div class="form-actions">
        <a class="btn" href="/calendar-feeds">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>
