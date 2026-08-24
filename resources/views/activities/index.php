<?php
$active = 'activities';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢場次</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>已發布</span>
        <strong><?= e(number_format((int) $summary['published'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>志工時數</span>
        <strong><?= e(number_format((float) $summary['volunteer_hours'], 2)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/activities">
            <input type="month" name="month" value="<?= e($month) ?>">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="活動、地點、說明">
            <select name="project_id">
                <option value="">全部專案</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= e((string) $project['id']) ?>" <?= $projectId === (int) $project['id'] ? 'selected' : '' ?>>
                        <?= e(($project['project_code'] ? $project['project_code'] . ' / ' : '') . $project['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">全部狀態</option>
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>已發布</option>
                <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>結案</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>取消</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">返回業務與人事</a>
            <?php if (\App\Core\Permission::can('activities.manage')): ?>
                <a class="btn primary" href="/activities/create">新增活動</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>時間</th>
                <th>活動名稱</th>
                <th>所屬專案</th>
                <th>地點</th>
                <th class="amount">報名 / 出席</th>
                <th class="amount">志工時數</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td>
                        <?= e(activity_datetime($activity['starts_at'])) ?>
                        <?php if (!empty($activity['ends_at'])): ?>
                            <div class="muted-text">至 <?= e(activity_datetime($activity['ends_at'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= e($activity['title']) ?></strong></td>
                    <td>
                        <?php if (!empty($activity['project_id'])): ?>
                            <a class="text-link" href="/projects/<?= e((string) $activity['project_id']) ?>">
                                <?= e(($activity['project_code'] ? $activity['project_code'] . ' / ' : '') . $activity['project_name']) ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= e($activity['location'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((int) $activity['registered_count'])) ?> / <?= e(number_format((int) $activity['attended_count'])) ?></td>
                    <td class="amount"><?= e(number_format((float) $activity['volunteer_hours'], 2)) ?></td>
                    <td><span class="badge <?= $activity['status'] === 'published' ? 'ok' : 'muted' ?>"><?= e(activity_status_label($activity['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/activities/<?= e((string) $activity['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('activities.manage')): ?>
                            <a class="btn small" href="/activities/<?= e((string) $activity['id']) ?>/edit">編輯</a>
                            <form method="post" action="/activities/<?= e((string) $activity['id']) ?>/delete" onsubmit="return confirm('確定要刪除此活動？將一併刪除報名、成果與附件紀錄，此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$activities): ?>
                <tr><td colspan="8" class="empty">尚無活動資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function activity_status_label(string $status): string
{
    return ['draft' => '草稿', 'published' => '已發布', 'closed' => '結案', 'cancelled' => '取消'][$status] ?? $status;
}
function activity_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return roc_date(substr($value, 0, 10)) . ' ' . substr($value, 11, 5);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
