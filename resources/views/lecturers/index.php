<?php
$active = 'lecturers';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢筆數</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>啟用講師</span>
        <strong><?= e(number_format((int) $summary['active'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>平均鐘點費</span>
        <strong><?= e(lecturer_money($summary['avg_rate'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/lecturers">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、專長、單位">
            <select name="status">
                <option value="">全部狀態</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>停用</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">返回業務與人事</a>
            <?php if (\App\Core\Permission::can('lecturers.manage')): ?>
                <a class="btn primary" href="/lecturers/create">新增講師</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>姓名</th>
                <th>服務單位</th>
                <th>專長</th>
                <th>聯絡方式</th>
                <th class="amount">鐘點費</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($lecturers as $lecturer): ?>
                <tr>
                    <td>
                        <strong><?= e($lecturer['display_name'] ?: $lecturer['name']) ?></strong>
                        <div class="muted-text"><?= e($lecturer['name']) ?></div>
                    </td>
                    <td>
                        <?= e($lecturer['organization'] ?: '-') ?>
                        <?php if (!empty($lecturer['job_title'])): ?>
                            <div class="muted-text"><?= e($lecturer['job_title']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($lecturer['specialty']) ?></td>
                    <td>
                        <?= e($lecturer['mobile'] ?: ($lecturer['phone'] ?: '-')) ?>
                        <?php if (!empty($lecturer['email'])): ?>
                            <div class="muted-text"><?= e($lecturer['email']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(lecturer_money($lecturer['hourly_rate'])) ?></td>
                    <td><span class="badge <?= $lecturer['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($lecturer['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/lecturers/<?= e((string) $lecturer['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('lecturers.manage')): ?>
                            <a class="btn small" href="/lecturers/<?= e((string) $lecturer['id']) ?>/edit">編輯</a>
                            <form method="post" action="/lecturers/<?= e((string) $lecturer['id']) ?>/delete" onsubmit="return confirm('確定要刪除此講師資料？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$lecturers): ?>
                <tr><td colspan="7" class="empty">查無講師資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function lecturer_money($value): string
{
    return number_format((float) $value, 0);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
