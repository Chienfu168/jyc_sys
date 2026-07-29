<?php
$active = 'projects';
$documentTitle = '專案計畫資料表';
ob_start();
?>
<?php require base_path('resources/views/shared/print-header.php'); ?>

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
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
