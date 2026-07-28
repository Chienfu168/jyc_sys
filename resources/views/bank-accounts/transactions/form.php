<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label class="span-2">
        <span>銀行帳戶</span>
        <?php $selectedAccount = (string) old('bank_account_id', $transaction['bank_account_id'] ?? ''); ?>
        <select name="bank_account_id" required>
            <option value="">請選擇銀行帳戶</option>
            <?php foreach ($accounts as $account): ?>
                <option value="<?= e((string) $account['id']) ?>" <?= $selectedAccount === (string) $account['id'] ? 'selected' : '' ?>>
                    <?= e($account['bank_name'] . ' / ' . $account['account_name'] . ' / ' . $account['account_no']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>日期</span>
        <input type="date" name="transacted_on" value="<?= e((string) old('transacted_on', $transaction['transacted_on'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>交易類型</span>
        <?php $selectedType = old('transaction_type', $transaction['transaction_type'] ?? 'deposit'); ?>
        <select name="transaction_type" id="bank-transaction-type" required>
            <?php foreach ($types as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label class="span-2">
        <span>摘要</span>
        <input type="text" name="subject" id="bank-transaction-subject" value="<?= e((string) old('subject', $transaction['subject'] ?? '')) ?>" placeholder="例：銀行撥補零用金、捐款入帳、銀行手續費">
    </label>
    <label>
        <span>金額</span>
        <input type="number" step="1" min="1" name="amount" value="<?= e((string) old('amount', $transaction['amount'] ?? '')) ?>" required>
    </label>
    <label>
        <span>憑證 / 交易序號</span>
        <input type="text" name="reference_no" value="<?= e((string) old('reference_no', $transaction['reference_no'] ?? '')) ?>">
    </label>
    <label>
        <span>往來對象</span>
        <input type="text" name="counterparty" value="<?= e((string) old('counterparty', $transaction['counterparty'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $transaction['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/bank-transactions">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
document.getElementById('bank-transaction-type')?.addEventListener('change', function () {
    const subject = document.getElementById('bank-transaction-subject');
    if (this.value === 'transfer_to_petty_cash' && subject && subject.value.trim() === '') {
        subject.value = '銀行撥補零用金';
    }
});
</script>
