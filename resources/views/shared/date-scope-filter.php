<?php
/**
 * 查詢範圍共用欄位(月份 / 年度 / 全部),供各列表頁篩選表單套用。
 * 呼叫前需提供 $scope、$month(YYYY-MM)、$year(西元年)。
 */
?>
<select name="scope">
    <option value="month" <?= $scope === 'month' ? 'selected' : '' ?>>依月份</option>
    <option value="year" <?= $scope === 'year' ? 'selected' : '' ?>>依年度</option>
    <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>全部（不限期間）</option>
</select>
<input type="month" name="month" value="<?= e($month) ?>">
<input type="number" name="year" min="1912" max="2100" value="<?= e((string) $year) ?>" placeholder="年度" style="max-width:100px">
