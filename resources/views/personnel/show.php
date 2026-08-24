<?php
$active = 'personnel';
$documentTitle = '人事基本資料表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($employee['name']) ?></h2>
            <p class="muted-text"><?= e(($employee['employee_no'] ?: '-') . ' / ' . ($employee['department'] ?: '-') . ' / ' . ($employee['job_title'] ?: '-')) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/personnel">返回列表</a>
            <?php if (\App\Core\Permission::can('personnel.manage')): ?>
                <a class="btn" href="/personnel/<?= e((string) $employee['id']) ?>/edit">編輯</a>
                <form method="post" action="/personnel/<?= e((string) $employee['id']) ?>/status">
                    <?= csrf_field() ?>
                    <select name="status">
                        <option value="active" <?= $employee['status'] === 'active' ? 'selected' : '' ?>>在職</option>
                        <option value="on_leave" <?= $employee['status'] === 'on_leave' ? 'selected' : '' ?>>留職停薪</option>
                        <option value="resigned" <?= $employee['status'] === 'resigned' ? 'selected' : '' ?>>離職</option>
                    </select>
                    <button class="btn" type="submit">更新狀態</button>
                </form>
                <form method="post" action="/personnel/<?= e((string) $employee['id']) ?>/delete" onsubmit="return confirm('確定要刪除此筆人事資料？此操作無法復原。');">
                    <?= csrf_field() ?>
                    <button class="btn" type="submit">刪除</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>員工編號</th>
            <td><?= e($employee['employee_no'] ?: '-') ?></td>
            <th>姓名</th>
            <td><?= e($employee['name']) ?></td>
        </tr>
        <tr>
            <th>身分證字號</th>
            <td><?= e($employee['identity_no'] ?: '-') ?></td>
            <th>性別</th>
            <td><?= e(personnel_show_gender_label($employee['gender'])) ?></td>
        </tr>
        <tr>
            <th>出生日期</th>
            <td><?= e(roc_date($employee['birth_date'])) ?></td>
            <th>狀態</th>
            <td><?= e(personnel_show_status_label($employee['status'])) ?></td>
        </tr>
        <tr>
            <th>部門</th>
            <td><?= e($employee['department'] ?: '-') ?></td>
            <th>職稱</th>
            <td><?= e($employee['job_title'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>任用類型</th>
            <td><?= e(personnel_show_employment_type_label($employee['employment_type'])) ?></td>
            <th>對應帳號</th>
            <td><?= e($employee['user_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>到職日期</th>
            <td><?= e(roc_date($employee['hire_date'])) ?></td>
            <th>離職日期</th>
            <td><?= e(roc_date($employee['leave_date'])) ?></td>
        </tr>
        <tr>
            <th>電話</th>
            <td><?= e($employee['phone'] ?: '-') ?></td>
            <th>手機</th>
            <td><?= e($employee['mobile'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td colspan="3"><?= e($employee['email'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>地址</th>
            <td colspan="3"><?= e($employee['address'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>薪資基準</th>
            <td><?= e(number_format((float) $employee['base_salary'], 0)) ?></td>
            <th>退休金提繳率</th>
            <td><?= e(number_format((float) $employee['pension_rate'], 2)) ?>%</td>
        </tr>
        <tr>
            <th>勞保投保額</th>
            <td><?= e(number_format((float) $employee['labor_insurance_amount'], 0)) ?></td>
            <th>健保投保額</th>
            <td><?= e(number_format((float) $employee['health_insurance_amount'], 0)) ?></td>
        </tr>
        <tr>
            <th>薪轉銀行</th>
            <td><?= e($employee['bank_name'] ?: '-') ?></td>
            <th>分行</th>
            <td><?= e($employee['bank_branch'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>薪轉帳號</th>
            <td><?= e($employee['bank_account_no'] ?: '-') ?></td>
            <th>戶名</th>
            <td><?= e($employee['bank_account_name'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>緊急聯絡人</th>
            <td><?= e($employee['emergency_contact'] ?: '-') ?></td>
            <th>緊急聯絡電話</th>
            <td><?= e($employee['emergency_phone'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($employee['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($employee['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <?php if (!empty($employee['notes'])): ?>
        <p class="print-notes">備註：<?= nl2br(e($employee['notes'])) ?></p>
    <?php endif; ?>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function personnel_show_status_label(string $status): string
{
    return ['active' => '在職', 'on_leave' => '留職停薪', 'resigned' => '離職'][$status] ?? $status;
}
function personnel_show_gender_label(string $gender): string
{
    return ['female' => '女', 'male' => '男', 'other' => '其他', 'undisclosed' => '未提供'][$gender] ?? $gender;
}
function personnel_show_employment_type_label(string $type): string
{
    return ['full_time' => '正職', 'part_time' => '兼職', 'contract' => '約聘', 'volunteer_staff' => '志工職', 'other' => '其他'][$type] ?? $type;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
