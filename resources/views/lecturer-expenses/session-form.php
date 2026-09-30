<?php
$active = 'lecturer-expenses';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($title) ?></h2>
            <p class="muted-text"><?= e($expense['display_name'] ?: $expense['lecturer_name']) ?>，<?= e(substr((string) $expense['expense_date'], 0, 7)) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>">返回月紀錄</a>
        </div>
    </div>

    <form class="form grid-form" method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>
        <label>
            <span>上課日期</span>
            <input type="date" name="session_date" value="<?= e((string) old('session_date', $session['session_date'] ?? '')) ?>" required>
        </label>
        <label>
            <span>上課地點</span>
            <input type="text" name="location" value="<?= e((string) old('location', $session['location'] ?? '')) ?>">
        </label>
        <label>
            <span>時數</span>
            <input data-calc type="number" min="0" step="0.5" name="hours" value="<?= e((string) old('hours', $session['hours'] ?? '')) ?>">
        </label>
        <label>
            <span>鐘點費單價</span>
            <input data-calc type="number" min="0" step="1" name="hourly_rate" value="<?= e((string) old('hourly_rate', $session['hourly_rate'] ?? '')) ?>">
        </label>
        <label>
            <span>交通費</span>
            <input data-calc type="number" min="0" step="1" name="transportation_fee" value="<?= e((string) old('transportation_fee', $session['transportation_fee'] ?? 0)) ?>">
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="notes"><?= e((string) old('notes', $session['notes'] ?? '')) ?></textarea>
        </label>
        <div class="calc-summary span-2">
            <span>鐘點費 <strong id="calc-lecture-fee">0</strong></span>
            <span>小計(鐘點費＋交通費) <strong id="calc-subtotal">0</strong></span>
        </div>
        <div class="form-actions span-2">
            <a class="btn" href="/lecturer-expenses/<?= e((string) $expense['id']) ?>">返回</a>
            <button class="btn primary" type="submit">儲存</button>
        </div>
    </form>
</section>
<script>
(() => {
    const fields = [...document.querySelectorAll('[data-calc]')];
    const lectureFee = document.getElementById('calc-lecture-fee');
    const subtotal = document.getElementById('calc-subtotal');

    function amount(name) {
        const field = document.querySelector(`[name="${name}"]`);
        return Number.parseFloat(field?.value || '0') || 0;
    }

    function format(value) {
        return Math.round(value).toLocaleString('zh-TW');
    }

    function calculate() {
        const lecture = amount('hours') * amount('hourly_rate');
        lectureFee.textContent = format(lecture);
        subtotal.textContent = format(lecture + amount('transportation_fee'));
    }

    fields.forEach((field) => field.addEventListener('input', calculate));
    calculate();
})();
</script>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
