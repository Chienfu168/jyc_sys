<?php
$active = 'approvals';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card"><span>待審</span><strong><?= e(number_format((int) $stats['pending'])) ?></strong></div>
    <div class="stat-card"><span>逾期待審</span><strong><?= e(number_format((int) ($stats['overdue'] ?? 0))) ?></strong></div>
    <div class="stat-card"><span>已核准</span><strong><?= e(number_format((int) $stats['approved'])) ?></strong></div>
    <div class="stat-card"><span>已退回</span><strong><?= e(number_format((int) $stats['rejected'])) ?></strong></div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/approvals">
            <select name="status">
                <option value="" <?= $status === '' ? 'selected' : '' ?>>全部狀態</option>
                <?php foreach (['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="module">
                <option value="">全部模組</option>
                <?php foreach ($sources as $source): ?>
                    <option value="<?= e($source['module']) ?>" <?= $module === $source['module'] ? 'selected' : '' ?>><?= e($source['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="scope">
                <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>全部可見</option>
                <option value="mine" <?= $scope === 'mine' ? 'selected' : '' ?>>我的送審</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/">工作台</a>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
            <tr>
                <th>狀態</th>
                <th>來源</th>
                <th>送審時間</th>
                <th>待處理</th>
                <th>日期</th>
                <th>類型</th>
                <th>分類</th>
                <th>主旨</th>
                <th class="amount">金額/時數</th>
                <th>送審人</th>
                <th>核決人</th>
                <th>核決時間</th>
                <th class="no-print">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $days = approval_pending_days($row); ?>
                <tr>
                    <td><span class="badge <?= $row['status'] === 'approved' ? 'ok' : ($row['status'] === 'rejected' ? 'danger' : 'muted') ?>"><?= e(approval_status_label($row['status'])) ?></span></td>
                    <td><?= e($row['source_label']) ?></td>
                    <td><?= e(approval_datetime($row['requested_at'] ?? $row['created_at'] ?? '')) ?></td>
                    <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <span class="badge <?= $days >= 3 ? 'danger' : ($days >= 1 ? 'warning' : 'muted') ?>"><?= e($days === 0 ? '今日' : $days . ' 天') ?></span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= e(roc_date($row['occurred_on'] ?? '')) ?></td>
                    <td><?= e(approval_type_label($row)) ?></td>
                    <td><?= e($row['category_name'] ?: '-') ?></td>
                    <td><a class="text-link" href="<?= e($row['show_url']) ?>"><?= e($row['subject']) ?></a></td>
                    <td class="amount"><?= e(approval_amount_label($row)) ?></td>
                    <td><?= e($row['requested_by_name'] ?? '-') ?></td>
                    <td><?= e($row['reviewed_by_name'] ?? '-') ?></td>
                    <td><?= e(approval_datetime($row['reviewed_at'] ?? '')) ?></td>
                    <td class="table-actions no-print">
                        <a class="btn small" href="<?= e($row['show_url']) ?>">查看</a>
                        <?php if (!empty($row['can_approve']) && $row['status'] === 'pending'): ?>
                            <form method="post" action="<?= e($row['approve_url']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn small primary" type="submit">核准</button>
                            </form>
                            <form method="post" action="<?= e($row['reject_url']) ?>">
                                <?= csrf_field() ?>
                                <button class="btn small" type="submit">退回</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="13" class="empty-state">沒有符合條件的簽核紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
function approval_status_label(string $status): string
{
    return ['pending' => '待審', 'approved' => '已核准', 'rejected' => '已退回', 'cancelled' => '已取消'][$status] ?? $status;
}

function approval_type_label(array $row): string
{
    $source = (string) ($row['source_label'] ?? '');
    if ($source === '人事請假') {
        return '請假';
    }
    if (in_array($source, ['年度預算', '工作計畫'], true)) {
        return '文件';
    }
    if ($source === '採購申請') {
        return '採購';
    }

    return ($row['item_type'] ?? '') === 'income' ? '收入' : '支出';
}

function approval_amount_label(array $row): string
{
    if (($row['source_label'] ?? '') === '人事請假') {
        return number_format((float) $row['amount'], 2);
    }
    if (in_array(($row['source_label'] ?? ''), ['年度預算', '工作計畫'], true)) {
        return roc_year_label($row['amount']);
    }

    return number_format((float) $row['amount'], 0);
}

function approval_pending_days(array $row): int
{
    $date = substr((string) ($row['requested_at'] ?: ($row['created_at'] ?? '')), 0, 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return 0;
    }

    $requested = new DateTimeImmutable($date);
    $today = new DateTimeImmutable(date('Y-m-d'));

    return max(0, (int) $requested->diff($today)->days);
}

function approval_datetime(?string $datetime): string
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
