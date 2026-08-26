<?php
$feeCategoryOptions = ['', '人事費', '獎助金', '鐘點費', '工讀金', '稿費', '出席費', '評審費', '審查費', '獎金', '支援金', '交通補貼', '其他'];
$paymentType = (string) old('payment_type', $payee['payment_type'] ?? 'bank');
$selectedFee = (string) old('fee_category', $payee['fee_category'] ?? '');
$selectedStatus = (string) old('status', $payee['status'] ?? 'active');
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>領款者姓名</span>
                <input type="text" name="payee_name" value="<?= e((string) old('payee_name', $payee['payee_name'] ?? '')) ?>" required>
            </label>
            <label>
                <span>身分證字號</span>
                <input type="text" name="payee_tax_id" value="<?= e((string) old('payee_tax_id', $payee['payee_tax_id'] ?? '')) ?>">
            </label>
            <label>
                <span>連絡電話</span>
                <input type="text" name="phone" value="<?= e((string) old('phone', $payee['phone'] ?? '')) ?>">
            </label>
            <label>
                <span>付款方式</span>
                <select name="payment_type" id="payee-payment-type">
                    <option value="bank" <?= $paymentType === 'bank' ? 'selected' : '' ?>>匯款</option>
                    <option value="cash" <?= $paymentType === 'cash' ? 'selected' : '' ?>>現金</option>
                </select>
            </label>
            <label class="span-2">
                <span>戶籍地址</span>
                <input type="text" name="household_address" value="<?= e((string) old('household_address', $payee['household_address'] ?? '')) ?>">
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>匯款資料</h3>
        <div class="grid-form">
            <label data-payee-bank-field>
                <span>銀行</span>
                <input type="text" name="bank_name" value="<?= e((string) old('bank_name', $payee['bank_name'] ?? '')) ?>">
            </label>
            <label data-payee-bank-field>
                <span>分行</span>
                <input type="text" name="bank_branch" value="<?= e((string) old('bank_branch', $payee['bank_branch'] ?? '')) ?>">
            </label>
            <label data-payee-bank-field>
                <span>銀行帳號</span>
                <input type="text" name="bank_account" value="<?= e((string) old('bank_account', $payee['bank_account'] ?? '')) ?>">
            </label>
            <label data-payee-bank-field>
                <span>銀行戶名</span>
                <input type="text" name="bank_account_name" value="<?= e((string) old('bank_account_name', $payee['bank_account_name'] ?? '')) ?>" placeholder="需與本人相同">
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>預設與管理</h3>
        <div class="grid-form">
            <label>
                <span>預設費別</span>
                <select name="fee_category">
                    <?php foreach ($feeCategoryOptions as $option): ?>
                        <option value="<?= e($option) ?>" <?= $selectedFee === $option ? 'selected' : '' ?>><?= e($option === '' ? '不指定' : $option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>排序</span>
                <input type="number" name="sort_order" min="0" value="<?= e((string) old('sort_order', $payee['sort_order'] ?? 10)) ?>">
            </label>
            <label>
                <span>狀態</span>
                <select name="status">
                    <?php foreach (['active' => '啟用', 'disabled' => '停用'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">
                <span>備註</span>
                <input type="text" name="note" value="<?= e((string) old('note', $payee['note'] ?? '')) ?>">
            </label>
        </div>
    </div>

    <div class="form-actions">
        <a class="btn" href="/payment-receipt-payees">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(function () {
    function updatePayeeBankFields() {
        const type = document.getElementById('payee-payment-type')?.value || 'bank';
        document.querySelectorAll('[data-payee-bank-field]').forEach((field) => {
            field.classList.toggle('hidden', type !== 'bank');
        });
    }
    document.getElementById('payee-payment-type')?.addEventListener('change', updatePayeeBankFields);
    updatePayeeBankFields();
})();
</script>
