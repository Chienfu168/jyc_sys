<?php
$categoryOptions = ['人事費', '獎助金', '鐘點費', '工讀金', '稿費', '出席費', '評審費', '審查費', '獎金', '支援金', '交通補貼', '其他'];
$paymentType = (string) old('payment_type', $receipt['payment_type'] ?? 'bank');
$selectedCategory = (string) old('fee_category', $receipt['fee_category'] ?? '人事費');
$selectedStatus = (string) old('status', $receipt['status'] ?? 'draft');
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>領款日期</span>
            <input type="date" name="receipt_date" value="<?= e((string) old('receipt_date', $receipt['receipt_date'] ?? date('Y-m-d'))) ?>" required>
        </label>
        <label>
            <span>收據編號</span>
            <input type="text" name="receipt_no" value="<?= e((string) old('receipt_no', $receipt['receipt_no'] ?? '')) ?>" placeholder="空白時自動產生">
        </label>
        <label>
            <span>表單版本</span>
            <input type="text" name="template_version" value="<?= e((string) old('template_version', $receipt['template_version'] ?? '2026年1月23日')) ?>">
        </label>
        <label>
            <span>狀態</span>
            <select name="status">
                <?php foreach (['draft' => '草稿', 'issued' => '已開立'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $selectedStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span>領款者姓名</span>
            <input type="text" name="payee_name" value="<?= e((string) old('payee_name', $receipt['payee_name'] ?? '')) ?>" required>
        </label>
        <label>
            <span>身分證字號</span>
            <input type="text" name="payee_tax_id" value="<?= e((string) old('payee_tax_id', $receipt['payee_tax_id'] ?? '')) ?>">
        </label>
        <label>
            <span>連絡電話</span>
            <input type="text" name="phone" value="<?= e((string) old('phone', $receipt['phone'] ?? '')) ?>">
        </label>
        <label>
            <span>付款方式</span>
            <select name="payment_type" id="payment-receipt-type">
                <option value="bank" <?= $paymentType === 'bank' ? 'selected' : '' ?>>匯款</option>
                <option value="cash" <?= $paymentType === 'cash' ? 'selected' : '' ?>>現金</option>
            </select>
        </label>
        <label class="span-2">
            <span>戶籍地址</span>
            <input type="text" name="household_address" value="<?= e((string) old('household_address', $receipt['household_address'] ?? '')) ?>">
        </label>

        <label data-bank-field>
            <span>銀行</span>
            <input type="text" name="bank_name" value="<?= e((string) old('bank_name', $receipt['bank_name'] ?? '')) ?>">
        </label>
        <label data-bank-field>
            <span>分行</span>
            <input type="text" name="bank_branch" value="<?= e((string) old('bank_branch', $receipt['bank_branch'] ?? '')) ?>">
        </label>
        <label data-bank-field>
            <span>銀行帳號</span>
            <input type="text" name="bank_account" value="<?= e((string) old('bank_account', $receipt['bank_account'] ?? '')) ?>">
        </label>
        <label data-bank-field>
            <span>銀行戶名</span>
            <input type="text" name="bank_account_name" value="<?= e((string) old('bank_account_name', $receipt['bank_account_name'] ?? '')) ?>" placeholder="需與本人相同">
        </label>

        <label>
            <span>費別</span>
            <select name="fee_category" required>
                <?php foreach ($categoryOptions as $option): ?>
                    <option value="<?= e($option) ?>" <?= $selectedCategory === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>其他費別</span>
            <input type="text" name="fee_category_other" value="<?= e((string) old('fee_category_other', $receipt['fee_category_other'] ?? '')) ?>">
        </label>
        <label>
            <span>金額</span>
            <input type="number" step="1" min="1" name="amount" value="<?= e((string) old('amount', $receipt['amount'] ?? '')) ?>" required>
        </label>
        <label>
            <span>摘要</span>
            <input type="text" name="summary" value="<?= e((string) old('summary', $receipt['summary'] ?? '')) ?>">
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="remark" rows="3"><?= e((string) old('remark', $receipt['remark'] ?? '')) ?></textarea>
        </label>
    </div>

    <div class="panel-header receipt-note-editor-header">
        <div>
            <h2>備註與稅費說明</h2>
        </div>
    </div>
    <div class="grid-form">
        <label class="span-2">
            <span>二代健保補充保費</span>
            <textarea name="health_insurance_note" rows="4"><?= e((string) old('health_insurance_note', $receipt['health_insurance_note'] ?? '')) ?></textarea>
        </label>
        <label class="span-2" data-bank-field>
            <span>費用匯款與領據處理</span>
            <textarea name="payment_note" rows="3"><?= e((string) old('payment_note', $receipt['payment_note'] ?? '')) ?></textarea>
        </label>
        <label class="span-2">
            <span>所得稅與補充保費代扣</span>
            <textarea name="tax_note" rows="3"><?= e((string) old('tax_note', $receipt['tax_note'] ?? '')) ?></textarea>
        </label>
        <label class="span-2">
            <span>附註</span>
            <textarea name="appendix_note" rows="3"><?= e((string) old('appendix_note', $receipt['appendix_note'] ?? '')) ?></textarea>
        </label>
    </div>

    <div class="form-actions">
        <a class="btn" href="/payment-receipts">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
function updatePaymentReceiptBankFields() {
    const type = document.getElementById('payment-receipt-type')?.value || 'bank';
    document.querySelectorAll('[data-bank-field]').forEach((field) => {
        field.classList.toggle('hidden', type !== 'bank');
    });
}

document.getElementById('payment-receipt-type')?.addEventListener('change', updatePaymentReceiptBankFields);
updatePaymentReceiptBankFields();
</script>
