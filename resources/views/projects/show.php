<?php
$active = 'projects';
$documentTitle = '專案計畫資料表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

<section class="stats-grid budget-summary no-print">
    <div class="stat-card">
        <span>專案預算</span>
        <strong><?= e(number_format((float) $costSummary['budget'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>實際支出</span>
        <strong><?= e(number_format((float) $costSummary['actual'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>剩餘預算</span>
        <strong class="<?= (float) $costSummary['remaining'] < 0 ? 'danger-text' : '' ?>"><?= e(number_format((float) $costSummary['remaining'], 0)) ?></strong>
    </div>
    <div class="stat-card">
        <span>執行率</span>
        <strong><?= e(number_format((float) $costSummary['execution_rate'], 2)) ?>%</strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header no-print">
        <div>
            <h2><?= e($project['name']) ?></h2>
            <p class="muted-text"><?= e(($project['project_code'] ?: '-') . ' / ' . project_show_status_label($project['status'])) ?></p>
        </div>
        <div class="actions">
            <a class="btn" href="/projects">返回列表</a>
            <?php if (\App\Core\Permission::can('projects.manage')): ?>
                <a class="btn" href="/projects/<?= e((string) $project['id']) ?>/edit">編輯</a>
                <form method="post" action="/projects/<?= e((string) $project['id']) ?>/status">
                    <?= csrf_field() ?>
                    <select name="status">
                        <option value="planning" <?= $project['status'] === 'planning' ? 'selected' : '' ?>>規劃中</option>
                        <option value="active" <?= $project['status'] === 'active' ? 'selected' : '' ?>>執行中</option>
                        <option value="closed" <?= $project['status'] === 'closed' ? 'selected' : '' ?>>已結案</option>
                        <option value="cancelled" <?= $project['status'] === 'cancelled' ? 'selected' : '' ?>>已取消</option>
                    </select>
                    <button class="btn" type="submit">更新狀態</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>專案代碼</th>
            <td><?= e($project['project_code'] ?: '-') ?></td>
            <th>專案名稱</th>
            <td><?= e($project['name']) ?></td>
        </tr>
        <tr>
            <th>專案類型</th>
            <td><?= e(project_show_type_label($project['project_type'])) ?></td>
            <th>狀態</th>
            <td><?= e(project_show_status_label($project['status'])) ?></td>
        </tr>
        <tr>
            <th>承辦人</th>
            <td><?= e($project['owner_name'] ?: '-') ?></td>
            <th>部門</th>
            <td><?= e($project['department'] ?: '-') ?></td>
        </tr>
        <tr>
            <th>經費來源</th>
            <td><?= e($project['funding_source'] ?: '-') ?></td>
            <th>預算金額</th>
            <td><?= e(number_format((float) $project['budget_amount'], 0)) ?></td>
        </tr>
        <tr>
            <th>執行期間</th>
            <td colspan="3"><?= e(roc_date_range($project['start_date'], $project['end_date'])) ?></td>
        </tr>
        <tr>
            <th>建檔人</th>
            <td><?= e($project['created_by_name'] ?: '-') ?></td>
            <th>更新時間</th>
            <td><?= e($project['updated_at'] ?: '-') ?></td>
        </tr>
        </tbody>
    </table>

    <table class="meta-table">
        <tbody>
        <tr>
            <th>專案目的</th>
            <td><?= nl2br(e($project['purpose'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>預期成果</th>
            <td><?= nl2br(e($project['expected_outcome'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>備註</th>
            <td><?= nl2br(e($project['notes'] ?: '-')) ?></td>
        </tr>
        </tbody>
    </table>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>成本來源</th>
                <th class="amount">筆數</th>
                <th class="amount">金額</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach (($costSummary['sources'] ?? []) as $source): ?>
                <tr>
                    <td><?= e($source['label']) ?></td>
                    <td class="amount"><?= e(number_format((int) $source['count'])) ?></td>
                    <td class="amount"><?= e(number_format((float) $source['amount'], 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th>合計</th>
                <th></th>
                <th class="amount"><?= e(number_format((float) $costSummary['actual'], 0)) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>活動時間</th>
                <th>活動名稱</th>
                <th>地點</th>
                <th class="amount">志工時數</th>
                <th>狀態</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?= e(project_show_activity_datetime($activity['starts_at'])) ?></td>
                    <td><a class="text-link" href="/activities/<?= e((string) $activity['id']) ?>"><?= e($activity['title']) ?></a></td>
                    <td><?= e($activity['location'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $activity['volunteer_hours'], 2)) ?></td>
                    <td><?= e(project_show_activity_status_label($activity['status'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$activities): ?>
                <tr><td colspan="5" class="empty">尚無連結活動。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php require base_path('resources/views/shared/signatures.php'); ?>
</section>
<?php
function project_show_status_label(string $status): string
{
    return ['planning' => '規劃中', 'active' => '執行中', 'closed' => '已結案', 'cancelled' => '已取消'][$status] ?? $status;
}
function project_show_type_label(string $type): string
{
    return ['program' => '服務方案', 'grant' => '補助計畫', 'administration' => '行政專案', 'event' => '活動專案', 'other' => '其他'][$type] ?? $type;
}
function project_show_activity_status_label(string $status): string
{
    return ['draft' => '草稿', 'published' => '已發布', 'closed' => '結案', 'cancelled' => '取消'][$status] ?? $status;
}
function project_show_activity_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }
    return roc_date(substr($value, 0, 10)) . ' ' . substr($value, 11, 5);
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
