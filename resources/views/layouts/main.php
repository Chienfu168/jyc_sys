<?php $currentUser = auth()->user(); ?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') . ' | ' . config('app.name')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="app-shell">
    <?php if ($currentUser): ?>
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">基</div>
                <div>
                    <strong><?= e(config('app.name')) ?></strong>
                    <span>內部管理</span>
                </div>
            </div>
            <nav class="nav">
                <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="/">儀表板</a>
                <?php if (\App\Core\Permission::can('users.view')): ?>
                    <a class="<?= ($active ?? '') === 'users' ? 'active' : '' ?>" href="/users">使用者管理</a>
                <?php endif; ?>
                <?php if (\App\Core\Permission::can('roles.view')): ?>
                    <a class="<?= ($active ?? '') === 'roles' ? 'active' : '' ?>" href="/roles">角色權限</a>
                <?php endif; ?>
                <?php if (\App\Core\Permission::can('system_updates.manage')): ?>
                    <a class="<?= ($active ?? '') === 'system-update' ? 'active' : '' ?>" href="/system-update">系統更新</a>
                <?php endif; ?>
            </nav>
        </aside>
    <?php endif; ?>

    <main class="main">
        <?php if ($currentUser): ?>
            <header class="topbar">
                <div>
                    <h1><?= e($title ?? '') ?></h1>
                </div>
                <div class="account">
                    <span><?= e($currentUser['name']) ?></span>
                    <small><?= e($currentUser['role_name'] ?? '') ?></small>
                    <form method="post" action="/logout">
                        <?= csrf_field() ?>
                        <button class="btn ghost" type="submit">登出</button>
                    </form>
                </div>
            </header>
        <?php endif; ?>

        <?php if ($message = flash('success')): ?>
            <div class="alert success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($message = flash('error')): ?>
            <div class="alert error"><?= e($message) ?></div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>
</div>
</body>
</html>
