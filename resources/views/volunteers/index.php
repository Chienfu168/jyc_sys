<?php
$active = 'volunteers';
ob_start();
?>
<section class="stats-grid budget-summary">
    <div class="stat-card">
        <span>查詢筆數</span>
        <strong><?= e(number_format((int) $summary['total'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>啟用志工</span>
        <strong><?= e(number_format((int) $summary['active'])) ?></strong>
    </div>
    <div class="stat-card">
        <span>本月服務時數</span>
        <strong><?= e(number_format((float) $summary['month_hours'], 2)) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <form class="search bank-filter" method="get" action="/volunteers">
            <input type="month" name="month" value="<?= e($month) ?>">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="姓名、電話、Email">
            <select name="status">
                <option value="">全部狀態</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>啟用</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>停用</option>
            </select>
            <button class="btn" type="submit">查詢</button>
        </form>
        <div class="actions">
            <a class="btn" href="/operations">返回業務與人事</a>
            <?php if (\App\Core\Permission::can('volunteers.manage')): ?>
                <a class="btn primary" href="/volunteers/create">新增志工</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>姓名</th>
                <th>電話</th>
                <th>Email</th>
                <th class="amount">本月時數</th>
                <th class="amount">累計時數</th>
                <th>狀態</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($volunteers as $volunteer): ?>
                <tr>
                    <td><strong><?= e($volunteer['name']) ?></strong></td>
                    <td><?= e($volunteer['phone'] ?: '-') ?></td>
                    <td><?= e($volunteer['email'] ?: '-') ?></td>
                    <td class="amount"><?= e(number_format((float) $volunteer['month_hours'], 2)) ?></td>
                    <td class="amount"><?= e(number_format((float) $volunteer['total_hours'], 2)) ?></td>
                    <td><span class="badge <?= $volunteer['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($volunteer['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td class="actions">
                        <a class="btn small" href="/volunteers/<?= e((string) $volunteer['id']) ?>">檢視</a>
                        <?php if (\App\Core\Permission::can('volunteers.manage')): ?>
                            <a class="btn small" href="/volunteers/<?= e((string) $volunteer['id']) ?>/edit">編輯</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$volunteers): ?>
                <tr><td colspan="7" class="empty">尚無志工資料。</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
