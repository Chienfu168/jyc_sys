<div class="board-meeting-line-grid">
    <label>
        <span>姓名</span>
        <input type="text" name="attendees[<?= e((string) $index) ?>][name]" value="<?= e((string) ($attendee['name'] ?? '')) ?>">
    </label>
    <label>
        <span>身分</span>
        <?php $attendeeRole = (string) ($attendee['role'] ?? 'director'); ?>
        <select name="attendees[<?= e((string) $index) ?>][role]">
            <option value="director" <?= $attendeeRole === 'director' ? 'selected' : '' ?>>出席(董事)</option>
            <option value="observer" <?= $attendeeRole === 'observer' ? 'selected' : '' ?>>列席</option>
        </select>
    </label>
    <label>
        <span>出席狀態</span>
        <?php $attendeeStatus = (string) ($attendee['attendance_status'] ?? 'present'); ?>
        <select name="attendees[<?= e((string) $index) ?>][attendance_status]">
            <option value="present" <?= $attendeeStatus === 'present' ? 'selected' : '' ?>>出席</option>
            <option value="leave" <?= $attendeeStatus === 'leave' ? 'selected' : '' ?>>請假</option>
            <option value="proxy" <?= $attendeeStatus === 'proxy' ? 'selected' : '' ?>>委託出席</option>
        </select>
    </label>
    <button class="btn small" type="button" data-remove-line>移除</button>
</div>
