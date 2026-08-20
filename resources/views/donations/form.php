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
        <span>捐款日期</span>
        <input type="date" name="donated_at" value="<?= e((string) old('donated_at', $donation['donated_at'] ?? date('Y-m-d'))) ?>" required>
    </label>
    <label>
        <span>金額</span>
        <input type="number" step="1" min="1" name="amount" value="<?= e((string) old('amount', $donation['amount'] ?? '')) ?>" required>
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
        <select name="receipt_status">
            <?php foreach (['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'] as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $receiptStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
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
