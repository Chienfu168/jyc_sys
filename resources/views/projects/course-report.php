<?php
$active = 'projects';
$documentTitle = '合作學校課程報表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="panel">
    <div class="panel-header no-print">
        <form class="search bank-filter" method="get" action="/projects/courses/report">
            <select name="scope" onchange="this.form.submit()">
                <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>全部</option>
                <option value="semester" <?= $scope === 'semester' ? 'selected' : '' ?>>指定學期</option>
                <option value="year" <?= $scope === 'year' ? 'selected' : '' ?>>指定學年度</option>
            </select>
            <?php if ($scope === 'semester'): ?>
                <select name="semester">
                    <?php foreach ($semesterOptions as $option): ?>
                        <option value="<?= e($option) ?>" <?= $semester === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($scope === 'year'): ?>
                <select name="year">
                    <?php foreach ($yearOptions as $option): ?>
                        <option value="<?= e((string) $option) ?>" <?= $year === (string) $option ? 'selected' : '' ?>><?= e((string) $option) ?>學年度</option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/projects">返回專案列表</a>
            <button class="btn primary" type="button" onclick="window.print()">列印 / 另存 PDF</button>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>篩選範圍</th>
            <td>
                <?php if ($scope === 'semester' && $semester !== ''): ?>
                    <?= e($semester) ?>
                <?php elseif ($scope === 'year' && $year !== ''): ?>
                    <?= e($year) ?>學年度(第1、2學期)
                <?php else: ?>
                    全部學期
                <?php endif; ?>
            </td>
            <th>課程筆數</th>
            <td><?= e(number_format(count($courses))) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>學校</th>
                <th>次項目</th>
                <th>學年度＋學期</th>
                <th>課程名稱</th>
                <th>授課老師</th>
                <th>負責人／校長</th>
                <th>主要接洽（主任／專員）</th>
                <th>每週幾</th>
                <th>備註</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= e($course['school_name']) ?></td>
                    <td><?= e($course['sub_program_name'] ?: '-') ?></td>
                    <td><?= e($course['semester_label']) ?></td>
                    <td><?= e($course['course_name']) ?></td>
                    <td><?= e($course['teacher_name'] ?: '-') ?></td>
                    <td><?= e($course['principal_name'] ?: '-') ?></td>
                    <td><?= e($course['director_name'] ?: '-') ?></td>
                    <td><?= e($course['weekday'] ?: '-') ?></td>
                    <td class="muted-text"><?= e($course['notes'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$courses): ?>
                <tr><td colspan="9" class="empty">查無符合條件的課程資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
