<?php
$active = 'payroll';
$rows = $rows ?? [];
ob_start();

$money = static fn ($v): string => number_format((float) $v, 0);
?>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>薪資基本資料</h2>
            <p class="muted-text">設定每位員工固定的月薪與勞健保、勞退、雇主負擔金額。建立新月份薪資時會自動帶出,只需再調整當月變動項目(加班、獎金、所得稅、二代健保、請假扣款等)。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/payroll">返回薪資列表</a>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>員工</th>
                <th>部門 / 職稱</th>
                <th class="amount">本薪</th>
                <th class="amount">津貼</th>
                <th class="amount">勞/健保自付</th>
                <th class="amount">雇主負擔合計</th>
                <th>狀態 / 更新</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                $isSet = $row['d_updated_at'] !== null;
                $selfDeduct = (float) ($row['labor_insurance_deduction'] ?? 0) + (float) ($row['health_insurance_deduction'] ?? 0);
                $employerTotal = (float) ($row['employer_labor_insurance'] ?? 0)
                    + (float) ($row['employer_health_insurance'] ?? 0)
                    + (float) ($row['occupational_insurance'] ?? 0)
                    + (float) ($row['employer_pension'] ?? 0);
                $baseShown = $isSet ? (float) $row['d_base_salary'] : (float) $row['base_salary'];
                ?>
                <tr>
                    <td>
                        <strong><?= e($row['name']) ?></strong>
                        <div class="muted-text"><?= e($row['employee_no'] ?: '-') ?></div>
                    </td>
                    <td>
                        <?= e($row['department'] ?: '-') ?>
                        <div class="muted-text"><?= e($row['job_title'] ?: '-') ?></div>
                    </td>
                    <td class="amount"><?= e($money($baseShown)) ?></td>
                    <td class="amount"><?= e($money($row['allowance_total'] ?? 0)) ?></td>
                    <td class="amount"><?= e($money($selfDeduct)) ?></td>
                    <td class="amount"><?= e($money($employerTotal)) ?></td>
                    <td>
                        <?php if ($isSet): ?>
                            <span class="badge ok">已設定</span>
                            <div class="muted-text"><?= e((string) $row['d_updated_at']) ?></div>
                        <?php else: ?>
                            <span class="badge muted">未設定</span>
                            <div class="muted-text">新增薪資時以人事本薪帶入</div>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a class="btn small" href="/payroll/defaults/<?= e((string) $row['id']) ?>/edit"><?= $isSet ? '編輯' : '設定' ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="empty">尚無在職員工。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
