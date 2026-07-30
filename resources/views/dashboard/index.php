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
        <span>使用者</span>
        <strong><?= e((string) $stats['users']) ?></strong>
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
            <p class="muted-text">目前整合收支紀錄與零用金，後續請假、預算與專案會逐步接入同一個簽核入口。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/income-expenses">收支紀錄</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>來源</th>
                <th>送審時間</th>
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
                <tr>
                    <td><?= e($approval['source_label'] ?? '-') ?></td>
                    <td><?= e(dashboard_datetime($approval['requested_at'] ?? $approval['created_at'] ?? '')) ?></td>
                    <td><?= e(roc_date($approval['occurred_on'] ?? '')) ?></td>
                    <td><?= e(dashboard_approval_type_label($approval)) ?></td>
                    <td><?= e($approval['category_name'] ?: '-') ?></td>
                    <td>
                        <a class="text-link" href="<?= e($approval['show_url']) ?>">
                            <?= e($approval['subject']) ?>
                        </a>
                    </td>
                    <td><?= e(number_format((float) $approval['amount'], 0)) ?></td>
                    <td><?= e($approval['requested_by_name'] ?? '-') ?></td>
                    <td class="table-actions no-print">
                        <a class="btn" href="<?= e($approval['show_url']) ?>">&#26597;&#30475;</a>
                        <?php if (!empty($approval['can_approve'])): ?>
                            <form method="post" action="<?= e($approval['approve_url']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn primary" type="submit">&#26680;&#20934;</button>
                            </form>
                            <form method="post" action="<?= e($approval['reject_url']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn" type="submit">&#36864;&#22238;</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pendingApprovals): ?>
                <tr><td colspan="9" class="empty-state">目前沒有待處理的簽核項目</td></tr>
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

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
