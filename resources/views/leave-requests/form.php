<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>請假人</span>
        <?php $selectedEmployee = (string) old('employee_id', $request['employee_id'] ?? ''); ?>
        <select name="employee_id" required>
            <option value="">選擇人員</option>
            <?php foreach ($employees as $employee): ?>
                <option value="<?= e((string) $employee['id']) ?>" <?= $selectedEmployee === (string) $employee['id'] ? 'selected' : '' ?>>
                    <?= e($employee['name'] . ' / ' . ($employee['department'] ?: '-') . ' / ' . ($employee['job_title'] ?: '-')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>假別</span>
        <?php $selectedType = (string) old('leave_type_id', $request['leave_type_id'] ?? ''); ?>
        <select name="leave_type_id" required>
            <option value="">選擇假別</option>
            <?php foreach ($leaveTypes as $type): ?>
                <option value="<?= e((string) $type['id']) ?>" <?= $selectedType === (string) $type['id'] ? 'selected' : '' ?>>
                    <?= e($type['name'] . ' / ' . leave_paid_label($type['paid'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>起始日期</span>
        <input data-calc type="date" name="start_date" value="<?= e((string) old('start_date', $request['start_date'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>結束日期</span>
        <input data-calc type="date" name="end_date" value="<?= e((string) old('end_date', $request['end_date'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>起始時間</span>
        <input data-calc type="time" name="start_time" value="<?= e(substr((string) old('start_time', $request['start_time'] ?? '09:00'), 0, 5)) ?>">
    </label>
    <label>
        <span>結束時間</span>
        <input data-calc type="time" name="end_time" value="<?= e(substr((string) old('end_time', $request['end_time'] ?? '18:00'), 0, 5)) ?>">
    </label>
    <label>
        <span>請假時數</span>
        <input type="number" min="0" step="0.5" name="total_hours" id="leave-total-hours" value="<?= e((string) old('total_hours', $request['total_hours'] ?? '8')) ?>">
    </label>
    <label>
        <span>狀態</span>
        <?php $status = old('status', $request['status'] ?? 'submitted'); ?>
        <select name="status">
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
            <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>待審</option>
            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>已核准</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>退回</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>取消</option>
        </select>
    </label>
    <label class="span-2">
        <span>請假事由</span>
        <input type="text" name="reason" value="<?= e((string) old('reason', $request['reason'] ?? '')) ?>" required>
    </label>
    <label>
        <span>職務代理人</span>
        <input type="text" name="handover_person" value="<?= e((string) old('handover_person', $request['handover_person'] ?? '')) ?>">
    </label>
    <label>
        <span>試算</span>
        <div class="calc-summary">
            <span>估算時數 <strong id="leave-estimated-hours">0</strong></span>
        </div>
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $request['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/leave-requests">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(() => {
    const fields = [...document.querySelectorAll('[data-calc]')];
    const totalHours = document.getElementById('leave-total-hours');
    const estimated = document.getElementById('leave-estimated-hours');

    function calculate() {
        const startDate = document.querySelector('[name="start_date"]').value;
        const endDate = document.querySelector('[name="end_date"]').value;
        const startTime = document.querySelector('[name="start_time"]').value;
        const endTime = document.querySelector('[name="end_time"]').value;
        if (!startDate || !endDate || endDate < startDate) {
            return;
        }
        let hours = 8;
        const days = Math.max(1, Math.round((new Date(endDate) - new Date(startDate)) / 86400000) + 1);
        if (days === 1 && startTime && endTime && endTime > startTime) {
            hours = (new Date(`${startDate}T${endTime}`) - new Date(`${startDate}T${startTime}`)) / 3600000;
        } else {
            hours = days * 8;
        }
        estimated.textContent = Number(hours.toFixed(2)).toLocaleString('zh-TW');
        if (!totalHours.value || Number(totalHours.value) === 0) {
            totalHours.value = Number(hours.toFixed(2));
        }
    }

    fields.forEach((field) => field.addEventListener('input', calculate));
    calculate();
})();
</script>
<?php
function leave_paid_label(string $paid): string
{
    return ['yes' => '給薪', 'no' => '不給薪', 'partial' => '部分給薪'][$paid] ?? $paid;
}
