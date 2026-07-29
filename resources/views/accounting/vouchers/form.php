<?php
$formLines = old('lines', $voucher['lines'] ?? []);
if (!is_array($formLines) || !$formLines) {
    $formLines = [
        ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''],
        ['account_id' => '', 'description' => '', 'debit' => '', 'credit' => ''],
    ];
}
?>
<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="grid-form">
        <label>
            <span>傳票號碼</span>
            <input type="text" name="voucher_no" value="<?= e((string) old('voucher_no', $voucher['voucher_no'] ?? '')) ?>" required>
        </label>
        <label>
            <span>傳票日期</span>
            <input type="date" name="voucher_date" value="<?= e((string) old('voucher_date', $voucher['voucher_date'] ?? date('Y-m-d'))) ?>" required>
        </label>
        <label class="span-2">
            <span>摘要</span>
            <input type="text" name="summary" value="<?= e((string) old('summary', $voucher['summary'] ?? '')) ?>" required>
        </label>
        <label>
            <span>資料來源</span>
            <input type="text" name="source_type" list="accounting-source-types" value="<?= e((string) old('source_type', $voucher['source_type'] ?? '')) ?>">
            <datalist id="accounting-source-types">
                <option value="manual"></option>
                <option value="income_expense"></option>
                <option value="petty_cash"></option>
                <option value="bank_transaction"></option>
            </datalist>
        </label>
        <label>
            <span>來源 ID</span>
            <input type="number" min="0" step="1" name="source_id" value="<?= e((string) old('source_id', $voucher['source_id'] ?? '')) ?>">
        </label>
        <label>
            <span>狀態</span>
            <?php $status = old('status', $voucher['status'] ?? 'draft'); ?>
            <select name="status">
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="voided" <?= $status === 'voided' ? 'selected' : '' ?>>作廢</option>
            </select>
        </label>
        <label class="span-2">
            <span>備註</span>
            <textarea name="notes"><?= e((string) old('notes', $voucher['notes'] ?? '')) ?></textarea>
        </label>
    </div>

    <div class="voucher-lines" id="voucher-lines">
        <div class="voucher-line voucher-line-head">
            <span>會計科目</span>
            <span>分錄說明</span>
            <span>借方</span>
            <span>貸方</span>
            <span></span>
        </div>
        <?php foreach (array_values($formLines) as $index => $line): ?>
            <div class="voucher-line" data-line>
                <label>
                    <span class="sr-only">會計科目</span>
                    <select name="lines[<?= e((string) $index) ?>][account_id]">
                        <option value="">選擇科目</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?= e((string) $account['id']) ?>" <?= (string) ($line['account_id'] ?? '') === (string) $account['id'] ? 'selected' : '' ?>>
                                <?= e($account['code'] . ' ' . $account['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span class="sr-only">分錄說明</span>
                    <input type="text" name="lines[<?= e((string) $index) ?>][description]" value="<?= e((string) ($line['description'] ?? '')) ?>">
                </label>
                <label>
                    <span class="sr-only">借方</span>
                    <input type="number" min="0" step="1" name="lines[<?= e((string) $index) ?>][debit]" value="<?= e((string) ($line['debit'] ?? '')) ?>">
                </label>
                <label>
                    <span class="sr-only">貸方</span>
                    <input type="number" min="0" step="1" name="lines[<?= e((string) $index) ?>][credit]" value="<?= e((string) ($line['credit'] ?? '')) ?>">
                </label>
                <button class="btn small" type="button" data-remove-line>移除</button>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="voucher-toolbar">
        <button class="btn" type="button" id="add-voucher-line">新增分錄列</button>
        <div class="voucher-balance muted-text">
            借方 <strong id="voucher-debit-total">0</strong>
            貸方 <strong id="voucher-credit-total">0</strong>
            差額 <strong id="voucher-balance-total">0</strong>
        </div>
    </div>

    <div class="form-actions">
        <a class="btn" href="/accounting/vouchers">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<template id="voucher-line-template">
    <div class="voucher-line" data-line>
        <label>
            <span class="sr-only">會計科目</span>
            <select data-name="account_id">
                <option value="">選擇科目</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?= e((string) $account['id']) ?>"><?= e($account['code'] . ' ' . $account['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span class="sr-only">分錄說明</span>
            <input type="text" data-name="description">
        </label>
        <label>
            <span class="sr-only">借方</span>
            <input type="number" min="0" step="1" data-name="debit">
        </label>
        <label>
            <span class="sr-only">貸方</span>
            <input type="number" min="0" step="1" data-name="credit">
        </label>
        <button class="btn small" type="button" data-remove-line>移除</button>
    </div>
</template>

<script>
(() => {
    const lines = document.getElementById('voucher-lines');
    const template = document.getElementById('voucher-line-template');
    const add = document.getElementById('add-voucher-line');
    const debitTotal = document.getElementById('voucher-debit-total');
    const creditTotal = document.getElementById('voucher-credit-total');
    const balanceTotal = document.getElementById('voucher-balance-total');

    function renumber() {
        [...lines.querySelectorAll('[data-line]')].forEach((line, index) => {
            line.querySelectorAll('[data-name]').forEach((field) => {
                field.name = `lines[${index}][${field.dataset.name}]`;
            });
            line.querySelectorAll('[name^="lines["]').forEach((field) => {
                field.name = field.name.replace(/lines\[\d+\]/, `lines[${index}]`);
            });
        });
    }

    function amount(field) {
        return Number.parseFloat(field.value || '0') || 0;
    }

    function calculate() {
        let debit = 0;
        let credit = 0;
        lines.querySelectorAll('[name$="[debit]"]').forEach((field) => debit += amount(field));
        lines.querySelectorAll('[name$="[credit]"]').forEach((field) => credit += amount(field));
        debitTotal.textContent = debit.toLocaleString('zh-TW');
        creditTotal.textContent = credit.toLocaleString('zh-TW');
        balanceTotal.textContent = (debit - credit).toLocaleString('zh-TW');
    }

    add?.addEventListener('click', () => {
        lines.appendChild(template.content.firstElementChild.cloneNode(true));
        renumber();
        calculate();
    });

    lines?.addEventListener('click', (event) => {
        if (!event.target.matches('[data-remove-line]')) {
            return;
        }
        if (lines.querySelectorAll('[data-line]').length <= 2) {
            return;
        }
        event.target.closest('[data-line]').remove();
        renumber();
        calculate();
    });

    lines?.addEventListener('input', calculate);
    renumber();
    calculate();
})();
</script>
