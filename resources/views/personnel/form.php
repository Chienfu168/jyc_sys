<form class="form" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="form-section">
        <h3>基本資料</h3>
        <div class="grid-form">
            <label>
                <span>姓名</span>
                <input type="text" name="name" id="personnel-name" value="<?= e((string) old('name', $employee['name'] ?? '')) ?>" required>
            </label>
            <label>
                <span>員工編號</span>
                <input type="text" name="employee_no" value="<?= e((string) old('employee_no', $employee['employee_no'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>對應登入帳號</span>
                <?php $selectedUser = (string) old('user_id', $employee['user_id'] ?? ''); ?>
                <select name="user_id" id="personnel-user">
                    <option value="">未對應</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= e((string) $user['id']) ?>"
                                data-name="<?= e($user['name']) ?>"
                                data-email="<?= e($user['email']) ?>"
                                <?= $selectedUser === (string) $user['id'] ? 'selected' : '' ?>>
                            <?= e($user['name'] . ' / ' . $user['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>部門</span>
                <input type="text" name="department" list="personnel-departments" value="<?= e((string) old('department', $employee['department'] ?? '')) ?>">
                <datalist id="personnel-departments">
                    <option value="行政管理組"></option>
                    <option value="教育推廣組"></option>
                    <option value="財務會計組"></option>
                    <option value="社區合作組"></option>
                </datalist>
            </label>
            <label>
                <span>職稱</span>
                <input type="text" name="job_title" value="<?= e((string) old('job_title', $employee['job_title'] ?? '')) ?>">
            </label>
            <label>
                <span>任用類型</span>
                <?php $employmentType = old('employment_type', $employee['employment_type'] ?? 'full_time'); ?>
                <select name="employment_type" required>
                    <option value="full_time" <?= $employmentType === 'full_time' ? 'selected' : '' ?>>正職</option>
                    <option value="part_time" <?= $employmentType === 'part_time' ? 'selected' : '' ?>>兼職</option>
                    <option value="contract" <?= $employmentType === 'contract' ? 'selected' : '' ?>>約聘</option>
                    <option value="volunteer_staff" <?= $employmentType === 'volunteer_staff' ? 'selected' : '' ?>>志工職</option>
                    <option value="other" <?= $employmentType === 'other' ? 'selected' : '' ?>>其他</option>
                </select>
            </label>
            <label>
                <span>狀態</span>
                <?php $status = old('status', $employee['status'] ?? 'active'); ?>
                <select name="status" required>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>在職</option>
                    <option value="on_leave" <?= $status === 'on_leave' ? 'selected' : '' ?>>留職停薪</option>
                    <option value="resigned" <?= $status === 'resigned' ? 'selected' : '' ?>>離職</option>
                </select>
            </label>
            <label>
                <span>到職日期</span>
                <input type="date" name="hire_date" value="<?= e((string) old('hire_date', $employee['hire_date'] ?? '')) ?>">
            </label>
            <label>
                <span>離職日期</span>
                <input type="date" name="leave_date" value="<?= e((string) old('leave_date', $employee['leave_date'] ?? '')) ?>">
            </label>
        </div>
    </div>

    <details class="form-section">
        <summary>個人資料(選填)</summary>
        <div class="grid-form">
            <label>
                <span>身分證字號</span>
                <input type="text" name="identity_no" value="<?= e((string) old('identity_no', $employee['identity_no'] ?? '')) ?>">
            </label>
            <label>
                <span>出生日期</span>
                <input type="date" name="birth_date" value="<?= e((string) old('birth_date', $employee['birth_date'] ?? '')) ?>">
            </label>
            <label>
                <span>性別</span>
                <?php $gender = old('gender', $employee['gender'] ?? 'undisclosed'); ?>
                <select name="gender">
                    <option value="undisclosed" <?= $gender === 'undisclosed' ? 'selected' : '' ?>>未提供</option>
                    <option value="female" <?= $gender === 'female' ? 'selected' : '' ?>>女</option>
                    <option value="male" <?= $gender === 'male' ? 'selected' : '' ?>>男</option>
                    <option value="other" <?= $gender === 'other' ? 'selected' : '' ?>>其他</option>
                </select>
            </label>
            <label>
                <span>電話</span>
                <input type="text" name="phone" value="<?= e((string) old('phone', $employee['phone'] ?? '')) ?>">
            </label>
            <label>
                <span>手機</span>
                <input type="text" name="mobile" value="<?= e((string) old('mobile', $employee['mobile'] ?? '')) ?>">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" id="personnel-email" value="<?= e((string) old('email', $employee['email'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>地址</span>
                <input type="text" name="address" value="<?= e((string) old('address', $employee['address'] ?? '')) ?>">
            </label>
        </div>
    </details>

    <details class="form-section">
        <summary>薪資與投保(選填)</summary>
        <div class="grid-form">
            <label>
                <span>薪資基準</span>
                <input type="number" min="0" step="1" name="base_salary" value="<?= e((string) old('base_salary', $employee['base_salary'] ?? '')) ?>">
            </label>
            <label>
                <span>勞保投保額</span>
                <input type="number" min="0" step="1" name="labor_insurance_amount" value="<?= e((string) old('labor_insurance_amount', $employee['labor_insurance_amount'] ?? '')) ?>">
            </label>
            <label>
                <span>健保投保額</span>
                <input type="number" min="0" step="1" name="health_insurance_amount" value="<?= e((string) old('health_insurance_amount', $employee['health_insurance_amount'] ?? '')) ?>">
            </label>
            <label>
                <span>退休金提繳率 %</span>
                <input type="number" min="0" step="0.01" name="pension_rate" value="<?= e((string) old('pension_rate', $employee['pension_rate'] ?? '6.00')) ?>">
            </label>
        </div>
    </details>

    <details class="form-section">
        <summary>薪轉帳戶(選填)</summary>
        <div class="grid-form">
            <label>
                <span>薪轉銀行</span>
                <input type="text" name="bank_name" value="<?= e((string) old('bank_name', $employee['bank_name'] ?? '')) ?>">
            </label>
            <label>
                <span>分行</span>
                <input type="text" name="bank_branch" value="<?= e((string) old('bank_branch', $employee['bank_branch'] ?? '')) ?>">
            </label>
            <label>
                <span>薪轉帳號</span>
                <input type="text" name="bank_account_no" value="<?= e((string) old('bank_account_no', $employee['bank_account_no'] ?? '')) ?>">
            </label>
            <label>
                <span>戶名</span>
                <input type="text" name="bank_account_name" value="<?= e((string) old('bank_account_name', $employee['bank_account_name'] ?? '')) ?>">
            </label>
        </div>
    </details>

    <details class="form-section">
        <summary>緊急聯絡與備註(選填)</summary>
        <div class="grid-form">
            <label>
                <span>緊急聯絡人</span>
                <input type="text" name="emergency_contact" value="<?= e((string) old('emergency_contact', $employee['emergency_contact'] ?? '')) ?>">
            </label>
            <label>
                <span>緊急聯絡電話</span>
                <input type="text" name="emergency_phone" value="<?= e((string) old('emergency_phone', $employee['emergency_phone'] ?? '')) ?>">
            </label>
            <label class="span-2">
                <span>備註</span>
                <textarea name="notes"><?= e((string) old('notes', $employee['notes'] ?? '')) ?></textarea>
            </label>
        </div>
    </details>

    <div class="form-actions">
        <a class="btn" href="/personnel">返回</a>
        <button class="btn primary" type="submit">儲存</button>
    </div>
</form>

<script>
(() => {
    const user = document.getElementById('personnel-user');
    const name = document.getElementById('personnel-name');
    const email = document.getElementById('personnel-email');

    user?.addEventListener('change', () => {
        const selected = user.selectedOptions[0];
        if (!selected || !selected.value) {
            return;
        }
        if (name && !name.value) {
            name.value = selected.dataset.name || '';
        }
        if (email && !email.value) {
            email.value = selected.dataset.email || '';
        }
    });
})();
</script>
