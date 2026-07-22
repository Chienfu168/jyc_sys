<?php
$active = 'roles';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>角色清單</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>角色</th>
                <th>說明</th>
                <th>權限數</th>
                <th>建立時間</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= e($role['name']) ?></td>
                    <td><?= e($role['description'] ?? '') ?></td>
                    <td><?= e((string) $role['permission_count']) ?></td>
                    <td><?= e($role['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
