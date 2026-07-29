<?php
$active = 'personnel';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢筆數</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>在職人員</span>
        <strong><?= e(number_format((int) $summary['active'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>月薪資基準</span>
        <strong><?= e(personnel_money($summary['salary_total'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/personnel">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、編號、職稱、電話">
            <select name="department">
                <option value="">全部部門</option>
                <?php foreach ($departments as $item): ?>
                    <option value="<?= e($item) ?>" <?= $department === $item ? 'selected' : '' ?>><?= e($item) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">全部狀態</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>在職</option>
                <option value="on_leave" <?= $status === 'on_leave' ? 'selected' : '' ?>>留職停薪</option>
                <option value="resigned" <?= $status === 'resigned' ? 'selected' : '' ?>>離職</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">返回業務與人事</a>
            <?php if (\App\Core\Permission::can('personnel.manage')): ?>
                <a class="btn primary" href="/personnel/create">新增人事資料</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>員工編號</th>
                <th>姓名</th>
                <th>部門 / 職稱</th>
                <th>任用類型</th>
                <th>到職日</th>
                <th class="amount">薪資基準</th>
                <th>聯絡方式</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td class="mono"><?= e($employee['employee_no'] ?: '-') ?></td>
                    <td><strong><?= e($employee['name']) ?></strong></td>
                    <td>
                        <?= e($employee['department'] ?: '-') ?>
                        <?php if (!empty($employee['job_title'])): ?>
                            <div class="muted-text"><?= e($employee['job_title']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e(personnel_employment_type_label($employee['employment_type'])) ?></td>
                    <td><?= e(roc_date($employee['hire_date'])) ?></td>
                    <td class="amount"><?= e(personnel_money($employee['base_salary'])) ?></td>
                    <td>
                        <?= e($employee['mobile'] ?: ($employee['phone'] ?: '-')) ?>
                        <?php if (!empty($employee['email'])): ?>
                            <div class="muted-text"><?= e($employee['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $employee['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e(personnel_status_label($employee['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/personnel/<?= e((string) $employee['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('personnel.manage')): ?>
                            <a class="btn small" href="/personnel/<?= e((string) $employee['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$employees): ?>
                <tr><td colspan="9" class="empty">查無人事資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function personnel_money($value): string
{
    return number_format((float) $value, 0);
}
function personnel_status_label(string $status): string
{
    return ['active' => '在職', 'on_leave' => '留職停薪', 'resigned' => '離職'][$status] ?? $status;
}
function personnel_employment_type_label(string $type): string
{
    return ['full_time' => '正職', 'part_time' => '兼職', 'contract' => '約聘', 'volunteer_staff' => '志工職', 'other' => '其他'][$type] ?? $type;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
