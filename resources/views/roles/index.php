<?php
$active = 'roles';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <h2>角色清單</h2>
        <?php if (\App\Core\Permission::can('roles.manage')): ?>
            <a class="btn primary" href="/roles/create">新增角色</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>角色</th>
                <th>說明</th>
                <th>權限數</th>
                <th>建立時間</th>
                <?php if (\App\Core\Permission::can('roles.manage')): ?>
                    <th class="actions">操作</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td><?= e($role['name']) ?></td>
                    <td><?= e($role['description'] ?? '') ?></td>
                    <td><?= e((string) $role['permission_count']) ?></td>
                    <td><?= e($role['created_at']) ?></td>
                    <?php if (\App\Core\Permission::can('roles.manage')): ?>
                        <td class="actions">
                            <a class="btn small" href="/roles/<?= e((string) $role['id']) ?>/edit">編輯權限</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
