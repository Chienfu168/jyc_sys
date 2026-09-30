<?php
$active = 'projects';
$documentTitle = '專案列表報表';
ob_start();
?>
<style>
@media print {
    /* 欄位較多,預設 A3 橫向以完整呈現。 */
    @page { size: A3 landscape; margin: 10mm; }
}
.project-report-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.project-report-table th,
.project-report-table td { border: 1px solid #666; padding: 6px 8px; vertical-align: top; }
.project-report-table thead th { background: #f1f4f3; text-align: center; }
.project-report-table td.col-text { text-align: left; }
@media print {
    .project-report-table { font-size: 11px; }
    .project-report-table th, .project-report-table td { padding: 3px 5px; }
}
</style>

<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2>專案列表報表</h2>
            <p class="muted-text">依目前查詢條件（關鍵字／狀態／年度）彙整之專案清單，A3 橫式列印。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/projects">返回專案列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <table class="meta-table no-print">
        <tbody>
        <tr>
            <th>關鍵字</th>
            <td><?= e($keyword ?: '（未篩選）') ?></td>
            <th>狀態</th>
            <td><?= e($status !== '' ? project_status_label_report($status) : '（全部）') ?></td>
            <th>年度</th>
            <td><?= e($year !== '' ? $year : '（全部）') ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table class="project-report-table">
            <thead>
            <tr>
                <th>專案名稱</th>
                <th>次項目</th>
                <th>執行對象</th>
                <th>負責人／校長</th>
                <th>主要接洽（主任／專員）</th>
                <th>授課老師</th>
                <th>上課星期幾</th>
                <th>執行期間</th>
                <th>專案目的</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td class="col-text">
                        <strong><?= e($project['name']) ?></strong>
                        <?php if (!empty($project['project_code'])): ?>
                            <div class="muted-text"><?= e($project['project_code']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="col-text"><?= e($project['sub_program_name'] ?: '-') ?></td>
                    <td class="col-text"><?= e($project['executing_target'] ?: '-') ?></td>
                    <td class="col-text"><?= e($project['principal_name'] ?: '-') ?></td>
                    <td class="col-text"><?= e($project['contact_name'] ?: '-') ?></td>
                    <td class="col-text"><?= e($project['course_teachers'] ?: '-') ?></td>
                    <td class="col-text"><?= e($project['course_weekdays'] ?: '-') ?></td>
                    <td class="col-text"><?= e(roc_date_range($project['start_date'], $project['end_date'])) ?></td>
                    <td class="col-text"><?= e($project['purpose'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
                <tr><td colspan="9" class="empty">查無符合條件的專案資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function project_status_label_report(string $status): string
{
    return ['planning' => '規劃中', 'active' => '執行中', 'closed' => '已結案', 'cancelled' => '已取消'][$status] ?? $status;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
