<?php
$active = 'payroll';
$employee = $employee ?? [];
$defaults = $defaults ?? [];
$val = static fn (string $k): string => (string) old($k, $defaults[$k] ?? 0);
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>薪資基本資料 — <?= e($employee['name'] ?? '') ?></h2>
            <p class="muted-text"><?= e(($employee['department'] ?: '-') . ' / ' . ($employee['job_title'] ?: '-')) ?>。此處為固定金額,建立新薪資時自動帶出;變動項目(加班、獎金、所得稅、二代健保、請假扣款)於每月薪資另填。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/payroll/defaults">返回</a>
        </div>
    </div>

    <form class="form" method="post" action="<?= e($action) ?>">
        <?= csrf_field() ?>

        <div class="form-section">
            <h3>固定應發</h3>
            <div class="grid-form">
                <label>
                    <span>本薪</span>
                    <input type="number" min="0" step="1" name="base_salary" value="<?= e($val('base_salary')) ?>">
                </label>
                <label>
                    <span>津貼(固定加給)</span>
                    <input type="number" min="0" step="1" name="allowance_total" value="<?= e($val('allowance_total')) ?>">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3>固定代扣(員工負擔)</h3>
            <div class="grid-form">
                <label>
                    <span>勞保自付</span>
                    <input type="number" min="0" step="1" name="labor_insurance_deduction" value="<?= e($val('labor_insurance_deduction')) ?>">
                </label>
                <label>
                    <span>健保自付</span>
                    <input type="number" min="0" step="1" name="health_insurance_deduction" value="<?= e($val('health_insurance_deduction')) ?>">
                </label>
                <label>
                    <span>自提退休金</span>
                    <input type="number" min="0" step="1" name="pension_self_deduction" value="<?= e($val('pension_self_deduction')) ?>">
                </label>
            </div>
        </div>

        <div class="form-section">
            <h3>固定雇主負擔</h3>
            <div class="grid-form">
                <label>
                    <span>勞保(雇主負擔)</span>
                    <input type="number" min="0" step="1" name="employer_labor_insurance" value="<?= e($val('employer_labor_insurance')) ?>">
                </label>
                <label>
                    <span>健保(雇主負擔)</span>
                    <input type="number" min="0" step="1" name="employer_health_insurance" value="<?= e($val('employer_health_insurance')) ?>">
                </label>
                <label>
                    <span>職業災害保險</span>
                    <input type="number" min="0" step="1" name="occupational_insurance" value="<?= e($val('occupational_insurance')) ?>">
                </label>
                <label>
                    <span>雇主退休金提繳(勞退6%)</span>
                    <input type="number" min="0" step="1" name="employer_pension" value="<?= e($val('employer_pension')) ?>">
                </label>
            </div>
        </div>

        <div class="form-section">
            <label>
                <span>備註(選填)</span>
                <input type="text" name="notes" maxlength="255" value="<?= e((string) old('notes', $defaults['notes'] ?? '')) ?>">
            </label>
        </div>

        <div class="form-actions">
            <a class="btn" href="/payroll/defaults">取消</a>
            <button class="btn primary" type="submit">儲存基本資料</button>
        </div>
    </form>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
