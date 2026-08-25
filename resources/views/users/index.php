<?php
$active = 'users';
ob_start();
?>
<section class="panel">
    <div class="panel-header">
        <form method="get" action="/users" class="search">
            <input type="search" name="q" value="<?= e($keyword) ?>" placeholder="搜尋姓名或 Email">
            <button class="btn" type="submit">搜尋</button>
        </form>
        <?php if (\App\Core\Permission::can('users.create')): ?>
            <a class="btn primary" href="/users/create">新增使用者</a>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>姓名</th>
                <th>Email</th>
                <th>部門 / 職稱</th>
                <th>角色</th>
                <th>狀態</th>
                <th>最後登入</th>
                <th class="actions">操作</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['name']) ?></td>
                    <td><?= e($user['email']) ?></td>
                    <td><?= e(trim(($user['department'] ?? '') . ' ' . ($user['job_title'] ?? '')) ?: '-') ?></td>
                    <td><?= e($user['role_name'] ?? '-') ?></td>
                    <td><span class="badge <?= $user['status'] === 'active' ? 'ok' : 'muted' ?>"><?= e($user['status'] === 'active' ? '啟用' : '停用') ?></span></td>
                    <td><?= e($user['last_login_at'] ?? '-') ?></td>
                    <td class="actions">
                        <?php if (\App\Core\Permission::can('users.update')): ?>
                            <a class="btn small" href="/users/<?= e((string) $user['id']) ?>/edit">編輯</a>
                            <form method="post" action="/users/<?= e((string) $user['id']) ?>/toggle">
                                <?= csrf_field() ?>
                                <button class="btn small ghost" type="submit"><?= $user['status'] === 'active' ? '停用' : '啟用' ?></button>
                            </form>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('users.delete')): ?>
                            <form method="post" action="/users/<?= e((string) $user['id']) ?>/delete" onsubmit="return confirm('確定要「刪除」此使用者帳號？刪除後無法復原，若僅需暫停請改用「停用」。');">
                                <?= csrf_field() ?>
                                <button class="btn small danger" type="submit">刪除</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="7" class="empty">沒有符合條件的資料</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('resources/views/layouts/main.php');
