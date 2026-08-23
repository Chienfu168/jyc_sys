<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>人員</span>
                <?php $selectedEmployee = (string) old('employee_id', $record['employee_id'] ?? ''); ?>
                <select name="employee_id" id="payroll-employee" required>
                    <option value="">選擇人員</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= e((string) $employee['id']) ?>"
                                data-salary="<?= e((string) $employee['base_salary']) ?>"
                                data-pension="<?= e((string) $employee['pension_rate']) ?>"
                                <?= $selectedEmployee === (string) $employee['id'] ? 'selected' : '' ?>>
                            <?= e($employee['name'] . ' / ' . ($employee['department'] ?: '-') . ' / ' . ($employee['job_title'] ?: '-')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>薪資月份</span>
                <input type="month" name="payroll_month" value="<?= e((string) old('payroll_month', $record['payroll_month'] ?? date('Y-m'))) ?>" required>
            </label>
            <label>
                <span>發薪日期</span>
                <input type="date" name="pay_date" value="<?= e((string) old('pay_date', $record['pay_date'] ?? date('Y-m-t'))) ?>">
            </label>
            <label>
                <span>付款狀態</span>
                <?php $paymentStatus = old('payment_status', $record['payment_status'] ?? 'draft'); ?>
                <select name="payment_status">
                    <option value="draft" <?= $paymentStatus === 'draft' ? 'selected' : '' ?>>草稿</option>
                    <option value="confirmed" <?= $paymentStatus === 'confirmed' ? 'selected' : '' ?>>已確認</option>
                    <option value="paid" <?= $paymentStatus === 'paid' ? 'selected' : '' ?>>已付款</option>
                    <option value="voided" <?= $paymentStatus === 'voided' ? 'selected' : '' ?>>作廢</option>
                </select>
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>應發項目</h3>
        <div class="grid-form">
            <label>
                <span>本薪</span>
                <input data-payroll-calc type="number" min="0" step="1" name="base_salary" id="payroll-base-salary" value="<?= e((string) old('base_salary', $record['base_salary'] ?? 0)) ?>">
            </label>
            <label>
                <span>津貼</span>
                <input data-payroll-calc type="number" min="0" step="1" name="allowance_total" value="<?= e((string) old('allowance_total', $record['allowance_total'] ?? 0)) ?>">
            </label>
            <label>
                <span>加班費</span>
                <input data-payroll-calc type="number" min="0" step="1" name="overtime_pay" value="<?= e((string) old('overtime_pay', $record['overtime_pay'] ?? 0)) ?>">
            </label>
            <label>
                <span>獎金</span>
                <input data-payroll-calc type="number" min="0" step="1" name="bonus" value="<?= e((string) old('bonus', $record['bonus'] ?? 0)) ?>">
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>應扣項目</h3>
        <div class="grid-form">
            <label>
                <span>勞保扣款</span>
                <input data-payroll-calc type="number" min="0" step="1" name="labor_insurance_deduction" value="<?= e((string) old('labor_insurance_deduction', $record['labor_insurance_deduction'] ?? 0)) ?>">
            </label>
            <label>
                <span>健保扣款</span>
                <input data-payroll-calc type="number" min="0" step="1" name="health_insurance_deduction" value="<?= e((string) old('health_insurance_deduction', $record['health_insurance_deduction'] ?? 0)) ?>">
            </label>
            <label>
                <span>自提退休金</span>
                <input data-payroll-calc type="number" min="0" step="1" name="pension_self_deduction" value="<?= e((string) old('pension_self_deduction', $record['pension_self_deduction'] ?? 0)) ?>">
            </label>
            <label>
                <span>所得稅</span>
                <input data-payroll-calc type="number" min="0" step="1" name="income_tax" value="<?= e((string) old('income_tax', $record['income_tax'] ?? 0)) ?>">
            </label>
            <label>
                <span>請假扣款</span>
                <input data-payroll-calc type="number" min="0" step="1" name="leave_deduction" value="<?= e((string) old('leave_deduction', $record['leave_deduction'] ?? 0)) ?>">
            </label>
            <label>
                <span>其他扣款</span>
                <input data-payroll-calc type="number" min="0" step="1" name="other_deduction" value="<?= e((string) old('other_deduction', $record['other_deduction'] ?? 0)) ?>">
            </label>
        </div>
    </div>

    <div class="form-section">
        <h3>試算</h3>
        <div class="calc-summary">
            <span>應發 <strong id="payroll-gross">0</strong></span>
            <span>扣款 <strong id="payroll-deduction">0</strong></span>
            <span>實發 <strong id="payroll-net">0</strong></span>
        </div>
    </div>

    <details class="form-section">
        <summary>付款與其他(選填)</summary>
        <div class="grid-form">
            <label>
                <span>雇主退休金提繳</span>
                <input type="number" min="0" step="1" name="employer_pension" id="payroll-employer-pension" value="<?= e((string) old('employer_pension', $record['employer_pension'] ?? 0)) ?>">
            </label>
            <label>
                <span>付款方式</span>
                <input type="text" name="payment_method" list="payroll-payment-methods" value="<?= e((string) old('payment_method', $record['payment_method'] ?? '匯款')) ?>">
                <datalist id="payroll-payment-methods">
                    <option value="匯款"></option>
                    <option value="現金"></option>
                    <option value="支票"></option>
                </datalist>
            </label>
            <label>
                <span>付款日期</span>
                <input type="date" name="paid_on" value="<?= e((string) old('paid_on', $record['paid_on'] ?? '')) ?>">
            </label>
            <label>
                <span>基金會付款銀行</span>
                <?php $selectedBank = (string) old('bank_account_id', $record['bank_account_id'] ?? ''); ?>
                <select name="bank_account_id">
                    <option value="">未指定</option>
                    <?php foreach ($bankAccounts as $account): ?>
                        <option value="<?= e((string) $account['id']) ?>" <?= $selectedBank === (string) $account['id'] ? 'selected' : '' ?>>
                            <?= e($account['bank_name'] . ' / ' . $account['account_no']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">
                <span>專案歸屬</span>
                <?php $selectedProjectId = (string) old('project_id', $record['project_id'] ?? ''); ?>
                <select name="project_id">
                    <option value="">未指定專案</option>
                    <?php foreach (($projects ?? []) as $project): ?>
                        <option value="<?= e((string) $project['id']) ?>" <?= $selectedProjectId === (string) $project['id'] ? 'selected' : '' ?>>
                            <?= e(($project['project_code'] ? $project['project_code'] . ' / ' : '') . $project['name']) ?>
                        </option>
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
        <a class="btn" href="/payroll">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(() => {
    const employee = document.getElementById('payroll-employee');
    const salary = document.getElementById('payroll-base-salary');
    const employerPension = document.getElementById('payroll-employer-pension');
    const fields = [...document.querySelectorAll('[data-payroll-calc]')];
    const grossEl = document.getElementById('payroll-gross');
    const deductionEl = document.getElementById('payroll-deduction');
    const netEl = document.getElementById('payroll-net');

    function amount(name) {
        const field = document.querySelector(`[name="${name}"]`);
        return Number.parseFloat(field?.value || '0') || 0;
    }

    function format(value) {
        return Math.round(value).toLocaleString('zh-TW');
    }

    function calculate() {
        const gross = amount('base_salary') + amount('allowance_total') + amount('overtime_pay') + amount('bonus');
        const deduction = amount('labor_insurance_deduction') + amount('health_insurance_deduction') + amount('pension_self_deduction') + amount('income_tax') + amount('leave_deduction') + amount('other_deduction');
        grossEl.textContent = format(gross);
        deductionEl.textContent = format(deduction);
        netEl.textContent = format(gross - deduction);
    }

    employee?.addEventListener('change', () => {
        const selected = employee.selectedOptions[0];
        if (!selected || !selected.value) {
            return;
        }
        if ((!salary.value || Number(salary.value) === 0) && selected.dataset.salary) {
            salary.value = selected.dataset.salary;
        }
        if ((!employerPension.value || Number(employerPension.value) === 0) && selected.dataset.pension) {
            employerPension.value = Math.round(Number(salary.value || 0) * Number(selected.dataset.pension || 0) / 100);
        }
        calculate();
    });

    fields.forEach((field) => field.addEventListener('input', calculate));
    calculate();
})();
</script>
