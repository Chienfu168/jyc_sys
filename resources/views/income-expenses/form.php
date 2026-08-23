<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>日期</span>
                <input type="date" name="occurred_on" value="<?= e((string) old('occurred_on', $record['occurred_on'] ?? date('Y-m-d'))) ?>" required>
            </label>
            <label>
                <span>類型</span>
                <?php $selectedType = old('item_type', $record['item_type'] ?? 'expense'); ?>
                <select name="item_type" id="income-expense-type" required>
                    <option value="income" <?= $selectedType === 'income' ? 'selected' : '' ?>>收入</option>
                    <option value="expense" <?= $selectedType === 'expense' ? 'selected' : '' ?>>支出</option>
                </select>
            </label>
            <label>
                <span>常用科目</span>
                <?php $selectedCategory = (string) old('category_id', $record['category_id'] ?? ''); ?>
                <select name="category_id" id="income-expense-category">
                    <option value="">手動輸入科目</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e((string) $category['id']) ?>"
                                data-type="<?= e($category['item_type']) ?>"
                                data-name="<?= e($category['name']) ?>"
                                <?= $selectedCategory === (string) $category['id'] ? 'selected' : '' ?>>
                            <?= e(($category['item_type'] === 'income' ? '收入' : '支出') . ' - ' . $category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>科目名稱</span>
                <input type="text" name="category_name" id="income-expense-category-name" value="<?= e((string) old('category_name', $record['category_name'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>摘要</span>
                <input type="text" name="subject" value="<?= e((string) old('subject', $record['subject'] ?? '')) ?>" required>
            </label>
            <label>
                <span>金額</span>
                <input type="number" step="1" min="1" name="amount" value="<?= e((string) old('amount', $record['amount'] ?? '')) ?>" required>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>對象與付款</h3>
        <div class="grid-form">
            <label>
                <span>對象 / 廠商 / 捐款人</span>
                <input type="text" name="counterparty" value="<?= e((string) old('counterparty', $record['counterparty'] ?? '')) ?>">
            </label>
            <label>
                <span>對象統編</span>
                <input type="text" name="counterparty_tax_id" value="<?= e((string) old('counterparty_tax_id', $record['counterparty_tax_id'] ?? '')) ?>">
            </label>
            <label>
                <span>付款方式</span>
                <input type="text" name="payment_method" list="payment-methods" value="<?= e((string) old('payment_method', $record['payment_method'] ?? '')) ?>">
                <datalist id="payment-methods">
                    <option value="匯款"></option>
                    <option value="現金"></option>
                    <option value="支票"></option>
                    <option value="信用卡"></option>
                    <option value="轉帳"></option>
                </datalist>
            </label>
            <label>
                <span>銀行帳戶</span>
                <?php $selectedBank = (string) old('bank_account_id', $record['bank_account_id'] ?? ''); ?>
                <select name="bank_account_id">
                    <option value="">不指定</option>
                    <?php foreach ($bankAccounts as $account): ?>
                        <option value="<?= e((string) $account['id']) ?>" <?= $selectedBank === (string) $account['id'] ? 'selected' : '' ?>>
                            <?= e($account['bank_name'] . ' / ' . $account['account_no']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">
                <span>專案名稱</span>
                <?php $selectedProjectId = (string) old('project_id', $record['project_id'] ?? ''); ?>
                <select name="project_id" id="income-expense-project">
                    <option value="">未指定專案</option>
                    <?php foreach (($projects ?? []) as $project): ?>
                        <option value="<?= e((string) $project['id']) ?>"
                                data-name="<?= e($project['name']) ?>"
                                <?= $selectedProjectId === (string) $project['id'] ? 'selected' : '' ?>>
                            <?= e(($project['project_code'] ? $project['project_code'] . ' / ' : '') . $project['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="project_name" value="<?= e((string) old('project_name', $record['project_name'] ?? '')) ?>" placeholder="專案名稱備註">
            </label>
        </div>
    </div>

    <details class="form-section">
        <summary>收據與狀態(選填)</summary>
        <div class="grid-form">
            <label>
                <span>收據 / 憑證號碼</span>
                <input type="text" name="receipt_no" value="<?= e((string) old('receipt_no', $record['receipt_no'] ?? '')) ?>">
            </label>
            <label>
                <span>收據狀態</span>
                <?php $receiptStatus = old('receipt_status', $record['receipt_status'] ?? 'pending'); ?>
                <select name="receipt_status">
                    <?php foreach (['not_required' => '免開', 'pending' => '待處理', 'issued' => '已開立', 'voided' => '作廢'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $receiptStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>紀錄狀態</span>
                <?php $recordStatus = old('status', $record['status'] ?? 'confirmed'); ?>
                <select name="status">
                    <?php foreach (['draft' => '草稿', 'confirmed' => '確認', 'voided' => '作廢'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $recordStatus === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $record['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <div class="form-actions">
        <a class="btn" href="/income-expenses">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
document.getElementById('income-expense-category')?.addEventListener('change', function () {
    const option = this.selectedOptions[0];
    if (!option || !option.value) {
        return;
    }
    document.getElementById('income-expense-type').value = option.dataset.type || 'expense';
    document.getElementById('income-expense-category-name').value = option.dataset.name || '';
});
document.getElementById('income-expense-project')?.addEventListener('change', function () {
    const option = this.selectedOptions[0];
    const input = document.querySelector('input[name="project_name"]');
    if (option && input && !input.value) {
        input.value = option.dataset.name || '';
    }
});
</script>
