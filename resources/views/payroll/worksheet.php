<?php
$active = 'payroll';
$documentTitle = '薪資表';
$records = $records ?? [];
$totals = $totals ?? [];

$employmentLabels = [
    'full_time' => '正職',
    'part_time' => '兼職',
    'contract' => '約聘',
    'volunteer_staff' => '志工人員',
    'other' => '其他',
];

$roc = null;
if (preg_match('/^(\d{4})-(\d{2})$/', (string) $month, $m)) {
    $roc = ((int) $m[1] - 1911) . '年' . (int) $m[2] . '月';
}

ob_start();
?>
<style>
@media print {
    /* 月薪資表欄位多,預設 A3 橫向以完整呈現;如需 A4 可於列印視窗調整紙張。 */
    @page { size: A3 landscape; margin: 10mm; }
}
.payroll-ws-wrap { overflow-x: auto; }
.payroll-ws {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    white-space: nowrap;
}
.payroll-ws th,
.payroll-ws td {
    border: 1px solid #666;
    padding: 4px 6px;
    text-align: right;
}
.payroll-ws thead th { background: #f1f4f3; text-align: center; }
.payroll-ws .col-text { text-align: left; white-space: normal; }
.payroll-ws tfoot th,
.payroll-ws tfoot td { background: #f6f8f7; font-weight: 700; }
.payroll-ws .group-emp { background: #eef4fb; }
.payroll-ws .group-ded { background: #fbf3ee; }
@media print {
    .payroll-ws { font-size: 10px; }
    .payroll-ws th, .payroll-ws td { padding: 2px 3px; }
}
</style>

<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>薪資表 / <?= e($month) ?></h2>
            <p class="muted-text">單一月份全體薪資彙總,含代扣項目、實發與雇主負擔,可列印或另存 PDF 陳核。</p>
        </div>
        <div class="actions">
            <form class="search" method="get" action="/payroll/worksheet">
                <input type="month" name="month" value="<?= e($month) ?>">
                <button class="btn" type="submit">查詢</button>
            </form>
            <a class="btn" href="/payroll">返回列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <div class="print-only" style="text-align:center;margin-bottom:8px">
        <strong style="font-size:16px"><?= e($roc ?? $month) ?> 薪資表</strong>
    </div>

    <?php if (!$records): ?>
        <p class="muted-text"><?= e($month) ?> 尚無薪資紀錄。</p>
    <?php else: ?>
        <div class="payroll-ws-wrap">
            <table class="payroll-ws">
                <thead>
                    <tr>
                        <th rowspan="2">序</th>
                        <th rowspan="2">姓名</th>
                        <th rowspan="2">職稱</th>
                        <th rowspan="2">類屬</th>
                        <th colspan="4">應發</th>
                        <th colspan="7" class="group-ded">代扣(員工負擔)</th>
                        <th rowspan="2">實發</th>
                        <th colspan="6" class="group-emp">雇主負擔</th>
                        <th rowspan="2">備註</th>
                    </tr>
                    <tr>
                        <th>本薪</th>
                        <th>加給</th>
                        <th>獎金</th>
                        <th>應發合計</th>
                        <th class="group-ded">所得稅</th>
                        <th class="group-ded">勞保</th>
                        <th class="group-ded">健保</th>
                        <th class="group-ded">自提退休</th>
                        <th class="group-ded">二代健保</th>
                        <th class="group-ded">其他扣</th>
                        <th class="group-ded">扣款合計</th>
                        <th class="group-emp">勞保</th>
                        <th class="group-emp">健保</th>
                        <th class="group-emp">職保</th>
                        <th class="group-emp">保險小計(B)</th>
                        <th class="group-emp">勞退6%(C)</th>
                        <th class="group-emp">總負擔(B+C)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $i => $row): ?>
                    <?php
                    $allowance = (float) $row['allowance_total'] + (float) $row['overtime_pay'];
                    $otherDed = (float) $row['leave_deduction'] + (float) $row['other_deduction'];
                    $empSubtotal = (float) $row['employer_labor_insurance'] + (float) $row['employer_health_insurance'] + (float) $row['occupational_insurance'];
                    $empBurden = $empSubtotal + (float) $row['employer_pension'];
                    ?>
                    <tr>
                        <td style="text-align:center"><?= e((string) ($i + 1)) ?></td>
                        <td class="col-text"><?= e($row['employee_name']) ?></td>
                        <td class="col-text"><?= e($row['job_title'] ?: '-') ?></td>
                        <td class="col-text"><?= e($employmentLabels[$row['employment_type'] ?? ''] ?? ($row['employment_type'] ?? '')) ?></td>
                        <td><?= e(payroll_ws_money($row['base_salary'])) ?></td>
                        <td><?= e(payroll_ws_money($allowance)) ?></td>
                        <td><?= e(payroll_ws_money($row['bonus'])) ?></td>
                        <td><?= e(payroll_ws_money($row['gross_pay'])) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($row['income_tax'])) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($row['labor_insurance_deduction'])) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($row['health_insurance_deduction'])) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($row['pension_self_deduction'])) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($row['supplementary_premium'] ?? 0)) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($otherDed)) ?></td>
                        <td class="group-ded"><?= e(payroll_ws_money($row['deduction_total'])) ?></td>
                        <td><strong><?= e(payroll_ws_money($row['net_pay'])) ?></strong></td>
                        <td class="group-emp"><?= e(payroll_ws_money($row['employer_labor_insurance'] ?? 0)) ?></td>
                        <td class="group-emp"><?= e(payroll_ws_money($row['employer_health_insurance'] ?? 0)) ?></td>
                        <td class="group-emp"><?= e(payroll_ws_money($row['occupational_insurance'] ?? 0)) ?></td>
                        <td class="group-emp"><?= e(payroll_ws_money($empSubtotal)) ?></td>
                        <td class="group-emp"><?= e(payroll_ws_money($row['employer_pension'])) ?></td>
                        <td class="group-emp"><?= e(payroll_ws_money($empBurden)) ?></td>
                        <td class="col-text"><?= e($row['notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" style="text-align:center">合計</th>
                        <td><?= e(payroll_ws_money($totals['base_salary'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money(($totals['allowance_total'] ?? 0) + ($totals['overtime_pay'] ?? 0))) ?></td>
                        <td><?= e(payroll_ws_money($totals['bonus'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['gross_pay'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['income_tax'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['labor_insurance_deduction'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['health_insurance_deduction'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['pension_self_deduction'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['supplementary_premium'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money(($totals['leave_deduction'] ?? 0) + ($totals['other_deduction'] ?? 0))) ?></td>
                        <td><?= e(payroll_ws_money($totals['deduction_total'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['net_pay'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['employer_labor_insurance'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['employer_health_insurance'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['occupational_insurance'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['employer_insurance_subtotal'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['employer_pension'] ?? 0)) ?></td>
                        <td><?= e(payroll_ws_money($totals['employer_burden_total'] ?? 0)) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <p class="field-hint" style="margin-top:10px">
            應發合計 = 本薪 + 加給(津貼／加班) + 獎金;扣款合計為員工代扣(含二代健保);實發 = 應發 − 扣款。
            雇主負擔(總負擔)= 勞健保與職保雇主負擔(B) + 勞退6%(C),為基金會實際用人成本,僅供彙整參考。
        </p>

        <?php require base_path('resources/views/shared/signatures.php'); ?>
    <?php endif; ?>
</section>
<?php
function payroll_ws_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
