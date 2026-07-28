<?php
$active = 'dashboard';
ob_start();
?>
<section class="stats-grid">
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
    <div class="stat-card">
        <span>操作紀錄</span>
        <strong><?= e((string) $stats['logs']) ?></strong>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>最近操作</h2>
    </div>
    <div class="table-wrap">
        <table>
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
                    <td><?= e($log['created_at']) ?></td>
                    <td><?= e($log['user_name'] ?? '系統') ?></td>
                    <td><?= e($log['module']) ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e(($log['target_type'] ?? '') . ' #' . ($log['target_id'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr><td colspan="5" class="empty">目前沒有操作紀錄</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
