<?php
$active = 'dashboard';
ob_start();
?>
<section class="stats-grid">
    <div class="stat-card">
        <span><?= e($canApproveAny ? '待我簽核' : '我的送審') ?></span>
        <strong><?= e((string) $stats['pending_approvals']) ?></strong>
    </div>
    <div class="stat-card">
        <span>逾期待審</span>
        <strong><?= e((string) $stats['overdue_approvals']) ?></strong>
    </div>
    <div class="stat-card">
        <span>啟用帳號</span>
        <strong><?= e((string) $stats['active_users']) ?></strong>
    </div>
    <div class="stat-card">
        <span>角色</span>
        <strong><?= e((string) $stats['roles']) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= e($canApproveAny ? '待簽核項目' : '我的送審項目') ?></h2>
            <p class="muted-text">列出最近待處理簽核。待審超過 3 天會標示為逾期，完整紀錄可至簽核中心查詢。</p>
        </div>
        <div class="actions">
            <a class="btn primary" href="/approvals">簽核中心</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>來源</th>
                <th>送審時間</th>
                <th>待處理</th>
                <th>日期</th>
                <th>類型</th>
                <th>分類</th>
                <th>主旨</th>
                <th>金額/時數</th>
                <th>送審人</th>
                <th class="no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingApprovals as $approval): ?>
                <?php $days = dashboard_pending_days($approval); ?>
                <tr>
                    <td><?= e($approval['source_label'] ?? '-') ?></td>
                    <td><?= e(dashboard_datetime($approval['requested_at'] ?? $approval['created_at'] ?? '')) ?></td>
                    <td><span class="badge <?= $days >= 3 ? 'danger' : ($days >= 1 ? 'warning' : 'muted') ?>"><?= e($days === 0 ? '今日' : $days . ' 天') ?></span></td>
                    <td><?= e(roc_date($approval['occurred_on'] ?? '')) ?></td>
                    <td><?= e(dashboard_approval_type_label($approval)) ?></td>
                    <td><?= e($approval['category_name'] ?: '-') ?></td>
                    <td><a class="text-link" href="<?= e($approval['show_url']) ?>"><?= e($approval['subject']) ?></a></td>
                    <td><?= e(dashboard_amount_label($approval)) ?></td>
                    <td><?= e($approval['requested_by_name'] ?? '-') ?></td>
                    <td class="table-actions no-print">
                        <a class="btn small" href="<?= e($approval['show_url']) ?>">查看</a>
                        <?php if (!empty($approval['can_approve'])): ?>
                            <form method="post" action="<?= e($approval['approve_url']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn small primary" type="submit">核准</button>
                            </form>
                            <form method="post" action="<?= e($approval['reject_url']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">退回</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pendingApprovals): ?>
                <tr><td colspan="10" class="empty-state">目前沒有待處理的簽核項目</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>最近操作紀錄</h2>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>時間</th>
                <th>人員</th>
                <th>模組</th>
                <th>動作</th>
                <th>目標</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e(dashboard_datetime($log['created_at'] ?? '')) ?></td>
                    <td><?= e($log['user_name'] ?? '系統') ?></td>
                    <td><?= e($log['module']) ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e(($log['target_type'] ?? '') . ' #' . ($log['target_id'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="5" class="empty-state">尚無操作紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function dashboard_datetime(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }

    $date = substr($datetime, 0, 10);
    $time = substr($datetime, 11, 5);

    return roc_date($date) . ($time ? ' ' . $time : '');
}

function dashboard_pending_days(array $approval): int
{
    $date = substr((string) ($approval['requested_at'] ?: ($approval['created_at'] ?? '')), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return 0;
    }

    $requested = new DateTimeImmutable($date);
    $today = new DateTimeImmutable(date('Y-m-d'));

    return max(0, (int) $requested->diff($today)->days);
}

function dashboard_approval_type_label(array $approval): string
{
    $source = (string) ($approval['source_label'] ?? '');
    if ($source === '人事請假') {
        return '請假';
    }
    if (in_array($source, ['年度預算', '工作計畫'], true)) {
        return '文件';
    }

    return ($approval['item_type'] ?? '') === 'income' ? '收入' : '支出';
}

function dashboard_amount_label(array $approval): string
{
    $source = (string) ($approval['source_label'] ?? '');
    if ($source === '人事請假') {
        return number_format((float) $approval['amount'], 2);
    }
    if (in_array($source, ['年度預算', '工作計畫'], true)) {
        return roc_year_label($approval['amount']);
    }

    return number_format((float) $approval['amount'], 0);
}

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
