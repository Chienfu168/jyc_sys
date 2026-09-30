<?php
$active = 'projects';
$documentTitle = '專案列表報表';
$profile = $profile ?? foundation_profile();
ob_start();
?>
<style>
/* 寬度採 100%,直接填滿當下紙張的可列印寬度,不依賴 JS 於列印當下才運算縮放
   (瀏覽器以「另存 PDF」或系統列印觸發時,beforeprint 事件不一定可靠執行);
   紙張大小另以下方 @media print 的 @page 提供 CSS 層級的預設值,同樣不依賴 JS。 */
.project-report-table { width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
.project-report-table th,
.project-report-table td { border: 1px solid #666; padding: 6px 8px; vertical-align: top; white-space: normal; word-break: break-word; }
.project-report-table thead th { background: #f1f4f3; text-align: center; }
.project-report-table td.col-text { text-align: left; }
/* 表頭抬頭列:跨頁時 thead 會於每頁重複顯示(含機構抬頭與欄位標頭)。 */
.project-report-table thead { display: table-header-group; }
.project-report-table tr.report-heading th { border: 0; padding: 0 4px 10px; }
.report-heading-inner { text-align: center; }
.report-heading-inner .rh-foundation { font-size: 20px; font-weight: 700; letter-spacing: 1px; margin-bottom: 4px; }
.report-heading-inner .rh-title { font-size: 16px; font-weight: 700; margin-bottom: 3px; }
.report-heading-inner .rh-filters { font-size: 12px; color: #333333; }
@media print {
    /* CSS 層級的預設紙張(不需 JS 執行即生效);點擊上方列印選項工具列仍可另外調整。 */
    @page { size: A3 landscape; margin: 10mm; }
    .project-report-table { font-size: 11px; }
    .project-report-table th, .project-report-table td { padding: 3px 5px; }
    /* .print-only 預設 display:none,列印時才需要顯示為表格列(而非 block,否則破壞表格排版)。 */
    .project-report-table tr.report-heading { display: table-row; }
}
</style>

<section class="panel print-scale-root">
    <div class="panel-header no-print">
        <div>
            <h2>專案列表報表</h2>
            <p class="muted-text">依目前查詢條件（關鍵字／狀態／年度）彙整之專案清單；可調整紙張、方向與縮放後列印，超過一頁時每頁自動重複表頭。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/projects">返回專案列表</a>
            <?php $printDefaultPaper = 'A3'; $printDefaultOrient = 'landscape'; require base_path('resources/views/shared/print-options.php'); ?>
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
            <!-- 抬頭置於表頭:跨頁時每頁自動重複顯示(僅列印顯示)。 -->
            <tr class="report-heading print-only">
                <th colspan="9">
                    <div class="report-heading-inner">
                        <div class="rh-foundation"><?= e($profile['foundation_name'] ?? foundation_name()) ?></div>
                        <div class="rh-title">專案列表報表</div>
                        <div class="rh-filters">
                            關鍵字：<?= e($keyword ?: '（未篩選）') ?>
                            狀態：<?= e($status !== '' ? project_status_label_report($status) : '（全部）') ?>
                            年度：<?= e($year !== '' ? $year : '（全部）') ?>
                        </div>
                    </div>
                </th>
            </tr>
            <tr>
                <th style="width:14%">專案名稱</th>
                <th style="width:8%">次項目</th>
                <th style="width:8%">執行對象</th>
                <th style="width:8%">負責人／校長</th>
                <th style="width:10%">主要接洽（主任／專員）</th>
                <th style="width:8%">授課老師</th>
                <th style="width:7%">上課星期幾</th>
                <th style="width:13%">執行期間</th>
                <th style="width:24%">專案目的</th>
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
