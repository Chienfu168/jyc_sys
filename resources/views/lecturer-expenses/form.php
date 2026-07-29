<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label>
        <span>講師</span>
        <?php $selectedLecturer = (string) old('lecturer_id', $expense['lecturer_id'] ?? ''); ?>
        <select name="lecturer_id" id="lecturer-expense-lecturer" required>
            <option value="">選擇講師</option>
            <?php foreach ($lecturers as $lecturer): ?>
                <option value="<?= e((string) $lecturer['id']) ?>"
                        data-rate="<?= e((string) $lecturer['hourly_rate']) ?>"
                        <?= $selectedLecturer === (string) $lecturer['id'] ? 'selected' : '' ?>>
                    <?= e(($lecturer['display_name'] ?: $lecturer['name']) . ' / ' . number_format((float) $lecturer['hourly_rate'], 0)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>費用日期</span>
        <input type="date" name="expense_date" value="<?= e((string) old('expense_date', $expense['expense_date'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label class="span-2">
        <span>服務內容</span>
        <input type="text" name="service_title" value="<?= e((string) old('service_title', $expense['service_title'] ?? '')) ?>" required>
    </label>
    <label>
        <span>承辦單位</span>
        <input type="text" name="service_unit" value="<?= e((string) old('service_unit', $expense['service_unit'] ?? '')) ?>">
    </label>
    <label>
        <span>專案名稱</span>
        <?php $selectedProjectId = (string) old('project_id', $expense['project_id'] ?? ''); ?>
        <select name="project_id" id="lecturer-expense-project">
            <option value="">未指定專案</option>
            <?php foreach (($projects ?? []) as $project): ?>
                <option value="<?= e((string) $project['id']) ?>"
                        data-name="<?= e($project['name']) ?>"
                        <?= $selectedProjectId === (string) $project['id'] ? 'selected' : '' ?>>
                    <?= e(($project['project_code'] ? $project['project_code'] . ' / ' : '') . $project['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="project_name" value="<?= e((string) old('project_name', $expense['project_name'] ?? '')) ?>" placeholder="專案名稱備註">
    </label>
    <label>
        <span>活動名稱</span>
        <input type="text" name="activity_name" value="<?= e((string) old('activity_name', $expense['activity_name'] ?? '')) ?>">
    </label>
    <label>
        <span>付款方式</span>
        <input type="text" name="payment_method" list="lecturer-expense-payment-methods" value="<?= e((string) old('payment_method', $expense['payment_method'] ?? '匯款')) ?>">
        <datalist id="lecturer-expense-payment-methods">
            <option value="匯款"></option>
            <option value="現金"></option>
            <option value="支票"></option>
        </datalist>
    </label>
    <label>
        <span>時數</span>
        <input data-calc type="number" min="0" step="0.5" name="hours" value="<?= e((string) old('hours', $expense['hours'] ?? '')) ?>">
    </label>
    <label>
        <span>鐘點費</span>
        <input data-calc type="number" min="0" step="1" name="hourly_rate" id="lecturer-expense-rate" value="<?= e((string) old('hourly_rate', $expense['hourly_rate'] ?? '')) ?>">
    </label>
    <label>
        <span>交通費</span>
        <input data-calc type="number" min="0" step="1" name="transportation_fee" value="<?= e((string) old('transportation_fee', $expense['transportation_fee'] ?? 0)) ?>">
    </label>
    <label>
        <span>其他費用</span>
        <input data-calc type="number" min="0" step="1" name="other_fee" value="<?= e((string) old('other_fee', $expense['other_fee'] ?? 0)) ?>">
    </label>
    <label>
        <span>代扣稅額</span>
        <input data-calc type="number" min="0" step="1" name="withholding_tax" value="<?= e((string) old('withholding_tax', $expense['withholding_tax'] ?? 0)) ?>">
    </label>
    <label>
        <span>付款狀態</span>
        <?php $paymentStatus = old('payment_status', $expense['payment_status'] ?? 'pending'); ?>
        <select name="payment_status">
            <option value="pending" <?= $paymentStatus === 'pending' ? 'selected' : '' ?>>待付款</option>
            <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>已付款</option>
            <option value="voided" <?= $paymentStatus === 'voided' ? 'selected' : '' ?>>作廢</option>
        </select>
    </label>
    <label>
        <span>付款日期</span>
        <input type="date" name="paid_on" value="<?= e((string) old('paid_on', $expense['paid_on'] ?? '')) ?>">
    </label>
    <label>
        <span>基金會付款銀行</span>
        <?php $selectedBank = (string) old('bank_account_id', $expense['bank_account_id'] ?? ''); ?>
        <select name="bank_account_id">
            <option value="">未指定</option>
            <?php foreach ($bankAccounts as $account): ?>
                <option value="<?= e((string) $account['id']) ?>" <?= $selectedBank === (string) $account['id'] ? 'selected' : '' ?>>
                    <?= e($account['bank_name'] . ' / ' . $account['account_no']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>憑證 / 請款編號</span>
        <input type="text" name="receipt_no" value="<?= e((string) old('receipt_no', $expense['receipt_no'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>試算</span>
        <div class="calc-summary">
            <span>鐘點費 <strong id="calc-lecture-fee">0</strong></span>
            <span>應付 <strong id="calc-gross-total">0</strong></span>
            <span>實付 <strong id="calc-net-total">0</strong></span>
        </div>
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $expense['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/lecturer-expenses">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(() => {
    const lecturer = document.getElementById('lecturer-expense-lecturer');
    const rate = document.getElementById('lecturer-expense-rate');
    const fields = [...document.querySelectorAll('[data-calc]')];
    const lectureFee = document.getElementById('calc-lecture-fee');
    const grossTotal = document.getElementById('calc-gross-total');
    const netTotal = document.getElementById('calc-net-total');

    function amount(name) {
        const field = document.querySelector(`[name="${name}"]`);
        return Number.parseFloat(field?.value || '0') || 0;
    }

    function format(value) {
        return Math.round(value).toLocaleString('zh-TW');
    }

    function calculate() {
        const lecture = amount('hours') * amount('hourly_rate');
        const gross = lecture + amount('transportation_fee') + amount('other_fee');
        const net = gross - amount('withholding_tax');
        lectureFee.textContent = format(lecture);
        grossTotal.textContent = format(gross);
        netTotal.textContent = format(net);
    }

    lecturer?.addEventListener('change', () => {
        const selected = lecturer.selectedOptions[0];
        if (selected?.dataset.rate && (!rate.value || Number(rate.value) === 0)) {
            rate.value = selected.dataset.rate;
        }
        calculate();
    });

    document.getElementById('lecturer-expense-project')?.addEventListener('change', function () {
        const option = this.selectedOptions[0];
        const input = document.querySelector('input[name="project_name"]');
        if (option && input && !input.value) {
            input.value = option.dataset.name || '';
        }
    });

    fields.forEach((field) => field.addEventListener('input', calculate));
    calculate();
})();
</script>
