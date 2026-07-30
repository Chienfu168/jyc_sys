<?php
$active = 'dashboard';
ob_start();
?>
<section class="stats-grid">
    <div class="stat-card">
        <span><?= e($canApproveIncomeExpenses ? '待我簽核' : '我的送審') ?></span>
        <strong><?= e((string) $stats['pending_income_expenses']) ?></strong>
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
            <h2><?= e($canApproveIncomeExpenses ? '待簽核項目' : '我的送審項目') ?></h2>
            <p class="muted-text">目前先整合收支紀錄，後續零用金、請假、預算與專案會逐步接入同一個簽核入口。</p>
        </div>
        <div class="actions">
            <a class="btn" href="/income-expenses">收支紀錄</a>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>送審時間</th>
                <th>日期</th>
                <th>類型</th>
                <th>分類</th>
                <th>主旨</th>
                <th>金額</th>
                <th>送審人</th>
                <th class="no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($pendingApprovals as $approval): ?>
                <tr>
                    <td><?= e(dashboard_datetime($approval['requested_at'] ?? $approval['created_at'] ?? '')) ?></td>
                    <td><?= e(roc_date($approval['occurred_on'] ?? '')) ?></td>
                    <td><?= e(($approval['item_type'] ?? '') === 'income' ? '收入' : '支出') ?></td>
                    <td><?= e($approval['category_name'] ?: '-') ?></td>
                    <td>
                        <a class="text-link" href="/income-expenses/<?= e((string) $approval['target_id']) ?>">
                            <?= e($approval['subject']) ?>
                        </a>
                    </td>
                    <td><?= e(number_format((float) $approval['amount'], 0)) ?></td>
                    <td><?= e($approval['requested_by_name'] ?? '-') ?></td>
                    <td class="table-actions no-print">
                        <a class="btn" href="/income-expenses/<?= e((string) $approval['target_id']) ?>">查看</a>
                        <?php if ($canApproveIncomeExpenses): ?>
                            <form method="post" action="/income-expenses/<?= e((string) $approval['target_id']) ?>/approve">
                                <?= csrf_field() ?>
                                <button class="btn primary" type="submit">核准</button>
                            </form>
                            <form method="post" action="/income-expenses/<?= e((string) $approval['target_id']) ?>/reject">
                                <?= csrf_field() ?>
                                <button class="btn" type="submit">退回</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$pendingApprovals): ?>
                <tr><td colspan="8" class="empty-state">目前沒有待處理的簽核項目</td></tr>
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

$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
