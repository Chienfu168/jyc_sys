<?php
$active = 'projects';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢筆數</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>執行中專案</span>
        <strong><?= e(number_format((int) $summary['active'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>預算合計</span>
        <strong><?= e(project_money($summary['budget_total'])) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/projects">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="專案名稱、代碼、承辦、部門">
            <input type="number" name="year" min="1912" max="2100" value="<?= e($year) ?>" placeholder="西元年度">
            <select name="status">
                <option value="">全部狀態</option>
                <option value="planning" <?= $status === 'planning' ? 'selected' : '' ?>>規劃中</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>執行中</option>
                <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>已結案</option>
                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">返回業務與人事</a>
            <?php if (\App\Core\Permission::can('projects.manage')): ?>
                <a class="btn primary" href="/projects/create">新增專案</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>專案代碼</th>
                <th>專案名稱</th>
                <th>類型</th>
                <th>期間</th>
                <th>承辦 / 部門</th>
                <th class="amount">預算金額</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td class="mono"><?= e($project['project_code'] ?: '-') ?></td>
                    <td>
                        <strong><?= e($project['name']) ?></strong>
                        <?php if (!empty($project['funding_source'])): ?>
                            <div class="muted-text">經費來源：<?= e($project['funding_source']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e(project_type_label($project['project_type'])) ?></td>
                    <td><?= e(roc_date_range($project['start_date'], $project['end_date'])) ?></td>
                    <td>
                        <?= e($project['owner_name'] ?: '-') ?>
                        <?php if (!empty($project['department'])): ?>
                            <div class="muted-text"><?= e($project['department']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="amount"><?= e(project_money($project['budget_amount'])) ?></td>
                    <td><span class="badge <?= $project['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e(project_status_label($project['status'])) ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/projects/<?= e((string) $project['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('projects.manage')): ?>
                            <a class="btn small" href="/projects/<?= e((string) $project['id']) ?>/edit">編輯</a>
                            <form method="post" action="/projects/<?= e((string) $project['id']) ?>/delete" onsubmit="return confirm('確定要刪除此專案？此操作無法復原。');">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
                <tr><td colspan="8" class="empty">查無專案資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function project_money($value): string
{
    return number_format((float) $value, 0);
}
function project_status_label(string $status): string
{
    return ['planning' => '規劃中', 'active' => '執行中', 'closed' => '已結案', 'cancelled' => '已取消'][$status] ?? $status;
}
function project_type_label(string $type): string
{
    return ['program' => '服務方案', 'grant' => '補助計畫', 'administration' => '行政專案', 'event' => '活動專案', 'other' => '其他'][$type] ?? $type;
}
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
