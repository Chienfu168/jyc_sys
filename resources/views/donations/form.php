<form class="form grid-form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <label class="span-2">
        <span>捐款人</span>
        <?php $selectedDonor = (string) old('donor_id', $donation['donor_id'] ?? ''); ?>
        <select name="donor_id" required>
            <option value="">請選擇</option>
            <?php foreach ($donors as $donor): ?>
                <option value="<?= e((string) $donor['id']) ?>" <?= $selectedDonor === (string) $donor['id'] ? 'selected' : '' ?>>
                    <?= e($donor['name'] . ($donor['status'] === 'archived' ? '（封存）' : '')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span>捐款編號</span>
        <input type="text" value="<?= e((string) ($donation['donation_no'] ?? '')) ?>" placeholder="儲存後依日期自動產生" readonly>
        <small class="muted-text">格式 YYYYMMDD-當日流水號，由系統自動產生。</small>
    </label>
    <label>
        <span>捐款日期</span>
        <input type="date" name="donated_at" value="<?= e((string) old('donated_at', $donation['donated_at'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>捐贈類別</span>
        <?php $donationKind = old('donation_kind', $donation['donation_kind'] ?? 'cash'); ?>
        <select name="donation_kind" id="donation-kind">
            <option value="cash" <?= $donationKind === 'cash' ? 'selected' : '' ?>>樂捐款（現金）</option>
            <option value="in_kind" <?= $donationKind === 'in_kind' ? 'selected' : '' ?>>樂捐實物</option>
        </select>
    </label>
    <label>
        <span id="amount-label"><?= $donationKind === 'in_kind' ? '估計價值' : '金額' ?></span>
        <input type="number" step="1" min="1" name="amount" value="<?= e((string) old('amount', $donation['amount'] ?? '')) ?>" required>
    </label>
    <label class="span-2 <?= $donationKind === 'in_kind' ? '' : 'hidden' ?>" data-in-kind-field>
        <span>實物名稱／說明</span>
        <input type="text" name="in_kind_item" value="<?= e((string) old('in_kind_item', $donation['in_kind_item'] ?? '')) ?>" placeholder="例：白米 100 公斤、電腦 2 台">
    </label>
    <label>
        <span>捐款方式</span>
        <input type="text" name="payment_method" list="donation-payment-methods" value="<?= e((string) old('payment_method', $donation['payment_method'] ?? '')) ?>" required>
        <datalist id="donation-payment-methods">
            <option value="現金"></option>
            <option value="匯款"></option>
            <option value="轉帳"></option>
            <option value="支票"></option>
            <option value="信用卡"></option>
            <option value="線上捐款"></option>
        </datalist>
    </label>
    <label>
        <span>收據狀態</span>
        <?php $receiptStatus = old('receipt_status', $donation['receipt_status'] ?? 'pending'); ?>
        <select name="receipt_status" id="donation-receipt-status">
            <?php foreach (['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $receiptStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label data-receipt-field>
        <span>收據號碼</span>
        <input type="text" name="receipt_no" value="<?= e((string) old('receipt_no', $donation['receipt_no'] ?? '')) ?>">
    </label>
    <label>
        <span>指定專案 / 用途</span>
        <input type="text" name="project_name" value="<?= e((string) old('project_name', $donation['project_name'] ?? '')) ?>">
    </label>
    <label class="span-2">
        <span>備註</span>
        <textarea name="notes"><?= e((string) old('notes', $donation['notes'] ?? '')) ?></textarea>
    </label>
    <div class="form-actions span-2">
        <a class="btn" href="/donations">返回列表</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(function () {
    const status = document.getElementById('donation-receipt-status');
    function updateReceiptFields() {
        const notRequired = status?.value === 'not_required';
        document.querySelectorAll('[data-receipt-field]').forEach((field) => {
            field.classList.toggle('hidden', notRequired);
            if (notRequired) {
                const input = field.querySelector('input');
                if (input) {
                    input.value = '';
                }
            }
        });
    }
    status?.addEventListener('change', updateReceiptFields);
    updateReceiptFields();

    const kind = document.getElementById('donation-kind');
    const amountLabel = document.getElementById('amount-label');
    function updateKindFields() {
        const inKind = kind?.value === 'in_kind';
        document.querySelectorAll('[data-in-kind-field]').forEach((field) => {
            field.classList.toggle('hidden', !inKind);
        });
        if (amountLabel) {
            amountLabel.textContent = inKind ? '估計價值' : '金額';
        }
    }
    kind?.addEventListener('change', updateKindFields);
    updateKindFields();
})();
</script>
