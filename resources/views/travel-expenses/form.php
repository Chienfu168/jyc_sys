<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>出差帳號</span>
                <?php $selectedUser = (string) old('traveler_user_id', $expense['traveler_user_id'] ?? ''); ?>
                <select name="traveler_user_id" id="travel-expense-user">
                    <option value="">手動輸入</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= e((string) $user['id']) ?>"
                                data-name="<?= e($user['name']) ?>"
                                <?= $selectedUser === (string) $user['id'] ? 'selected' : '' ?>>
                            <?= e($user['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>出差人</span>
                <input type="text" name="traveler_name" id="travel-expense-name" value="<?= e((string) old('traveler_name', $expense['traveler_name'] ?? '')) ?>" required>
            </label>
            <label>
                <span>起始日期</span>
                <input type="date" name="travel_start" value="<?= e((string) old('travel_start', $expense['travel_start'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>結束日期</span>
                <input type="date" name="travel_end" value="<?= e((string) old('travel_end', $expense['travel_end'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>出差地點</span>
                <input type="text" name="destination" value="<?= e((string) old('destination', $expense['destination'] ?? '')) ?>" required>
            </label>
            <label>
                <span>專案名稱</span>
                <?php $selectedProjectId = (string) old('project_id', $expense['project_id'] ?? ''); ?>
                <select name="project_id" id="travel-expense-project">
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
            <label class="span-2">
                <span>出差事由</span>
                <input type="text" name="purpose" value="<?= e((string) old('purpose', $expense['purpose'] ?? '')) ?>" required>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>費用明細</h3>
        <div class="grid-form">
            <label>
                <span>交通費</span>
                <input data-calc type="number" min="0" step="1" name="transportation_fee" value="<?= e((string) old('transportation_fee', $expense['transportation_fee'] ?? 0)) ?>">
            </label>
            <label>
                <span>住宿費</span>
                <input data-calc type="number" min="0" step="1" name="accommodation_fee" value="<?= e((string) old('accommodation_fee', $expense['accommodation_fee'] ?? 0)) ?>">
            </label>
            <label>
                <span>膳雜費</span>
                <input data-calc type="number" min="0" step="1" name="meal_fee" value="<?= e((string) old('meal_fee', $expense['meal_fee'] ?? 0)) ?>">
            </label>
            <label>
                <span>其他費用</span>
                <input data-calc type="number" min="0" step="1" name="miscellaneous_fee" value="<?= e((string) old('miscellaneous_fee', $expense['miscellaneous_fee'] ?? 0)) ?>">
            </label>
            <label>
                <span>預支金額</span>
                <input data-calc type="number" min="0" step="1" name="advance_amount" value="<?= e((string) old('advance_amount', $expense['advance_amount'] ?? 0)) ?>">
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>試算</h3>
        <div class="calc-summary">
            <span>費用總額 <strong id="travel-total">0</strong></span>
            <span>預支 <strong id="travel-advance">0</strong></span>
            <span>應核銷 <strong id="travel-reimbursable">0</strong></span>
        </div>
    </div>

    <details class="form-section">
        <summary>付款與其他(選填)</summary>
        <div class="grid-form">
            <label>
                <span>付款方式</span>
                <input type="text" name="payment_method" list="travel-expense-payment-methods" value="<?= e((string) old('payment_method', $expense['payment_method'] ?? '匯款')) ?>">
                <datalist id="travel-expense-payment-methods">
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
                <span>憑證 / 核銷編號</span>
                <input type="text" name="receipt_no" value="<?= e((string) old('receipt_no', $expense['receipt_no'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $expense['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <div class="form-actions">
        <a class="btn" href="/travel-expenses">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(() => {
    const user = document.getElementById('travel-expense-user');
    const travelerName = document.getElementById('travel-expense-name');
    const fields = [...document.querySelectorAll('[data-calc]')];
    const total = document.getElementById('travel-total');
    const advance = document.getElementById('travel-advance');
    const reimbursable = document.getElementById('travel-reimbursable');

    function amount(name) {
        const field = document.querySelector(`[name="${name}"]`);
        return Number.parseFloat(field?.value || '0') || 0;
    }

    function format(value) {
        return Math.round(value).toLocaleString('zh-TW');
    }

    function calculate() {
        const sum = amount('transportation_fee') + amount('accommodation_fee') + amount('meal_fee') + amount('miscellaneous_fee');
        const advanceAmount = amount('advance_amount');
        total.textContent = format(sum);
        advance.textContent = format(advanceAmount);
        reimbursable.textContent = format(sum - advanceAmount);
    }

    user?.addEventListener('change', () => {
        const selected = user.selectedOptions[0];
        if (selected?.dataset.name) {
            travelerName.value = selected.dataset.name;
        }
    });

    document.getElementById('travel-expense-project')?.addEventListener('change', function () {
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
