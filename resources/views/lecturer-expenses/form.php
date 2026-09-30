<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>講師</span>
                <?php $selectedLecturer = (string) old('lecturer_id', $expense['lecturer_id'] ?? ''); ?>
                <select name="lecturer_id" id="lecturer-expense-lecturer" required>
                    <option value="">選擇講師</option>
                    <?php foreach ($lecturers as $lecturer): ?>
                        <option value="<?= e((string) $lecturer['id']) ?>"
                                <?= $selectedLecturer === (string) $lecturer['id'] ? 'selected' : '' ?>>
                            <?= e(($lecturer['display_name'] ?: $lecturer['name']) . ' / ' . number_format((float) $lecturer['hourly_rate'], 0)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>月份</span>
                <input type="month" name="expense_month" value="<?= e((string) old('expense_month', $expense['expense_month'] ?? date('Y-m'))) ?>" required>
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
        </div>
    </div>

    <div class="form-section">
        <h3>其他費用</h3>
        <p class="muted-text">鐘點費、交通費由下方「上課明細」逐筆記錄後自動加總，此處僅填寫每月一次性的其他費用與代扣稅額。</p>
        <div class="grid-form">
            <label>
                <span>其他費用</span>
                <input data-calc type="number" min="0" step="1" name="other_fee" value="<?= e((string) old('other_fee', $expense['other_fee'] ?? 0)) ?>">
            </label>
            <label>
                <span>代扣稅額</span>
                <input data-calc type="number" min="0" step="1" name="withholding_tax" value="<?= e((string) old('withholding_tax', $expense['withholding_tax'] ?? 0)) ?>">
            </label>
        </div>
        <?php if (isset($expense['id'])): ?>
            <div class="calc-summary">
                <span>目前上課明細鐘點費＋交通費合計 <strong><?= e(number_format((float) ($expense['lecture_fee'] ?? 0) + (float) ($expense['transportation_fee'] ?? 0), 0)) ?></strong></span>
                <span>應付總額 <strong><?= e(number_format((float) ($expense['gross_total'] ?? 0), 0)) ?></strong></span>
                <span>實付金額 <strong><?= e(number_format((float) ($expense['net_total'] ?? 0), 0)) ?></strong></span>
            </div>
            <p class="field-hint">儲存後將依上方其他費用／代扣稅額與目前上課明細重新計算應付、實付總額。</p>
        <?php else: ?>
            <p class="field-hint">建立後請於月紀錄詳情頁新增上課明細，系統會自動加總計算應付、實付總額。</p>
        <?php endif; ?>
    </div>

    <details class="form-section">
        <summary>付款與其他(選填)</summary>
        <div class="grid-form">
            <label>
                <span>付款方式</span>
                <input type="text" name="payment_method" list="lecturer-expense-payment-methods" data-payment-method value="<?= e((string) old('payment_method', $expense['payment_method'] ?? '匯款')) ?>">
                <datalist id="lecturer-expense-payment-methods">
                    <option value="匯款"></option>
                    <option value="現金"></option>
                    <option value="支票"></option>
                </datalist>
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
            <label data-bank-field>
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
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $expense['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <div class="form-actions">
        <a class="btn" href="/lecturer-expenses">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(() => {
    document.getElementById('lecturer-expense-project')?.addEventListener('change', function () {
        const option = this.selectedOptions[0];
        const input = document.querySelector('input[name="project_name"]');
        if (option && input && !input.value) {
            input.value = option.dataset.name || '';
        }
    });

    const paymentMethod = document.querySelector('[data-payment-method]');
    function updateBankFields() {
        const cash = (paymentMethod?.value || '').trim() === '現金';
        document.querySelectorAll('[data-bank-field]').forEach((field) => field.classList.toggle('hidden', cash));
    }
    paymentMethod?.addEventListener('input', updateBankFields);
    updateBankFields();
})();
</script>
