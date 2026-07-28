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
<div class="app-shell <?= $currentUser ? '' : 'guest-shell' ?>">
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
                <div class="nav-section">
                    <span class="nav-section-title">工作台</span>
                    <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="/">
                        <span class="nav-icon">總</span>
                        <span>儀表板</span>
                    </a>
                </div>

                <?php if (\App\Core\Permission::can('annual_budgets.view')): ?>
                    <div class="nav-section">
                        <span class="nav-section-title">主要業務</span>
                        <a class="<?= ($active ?? '') === 'annual-budgets' ? 'active' : '' ?>" href="/annual-budgets">
                            <span class="nav-icon">預</span>
                            <span>年度預算</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (\App\Core\Permission::can('users.view') || \App\Core\Permission::can('roles.view')): ?>
                    <div class="nav-section">
                        <span class="nav-section-title">後台管理</span>
                        <?php if (\App\Core\Permission::can('users.view')): ?>
                            <a class="<?= ($active ?? '') === 'users' ? 'active' : '' ?>" href="/users">
                                <span class="nav-icon">人</span>
                                <span>使用者管理</span>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('roles.view')): ?>
                            <a class="<?= ($active ?? '') === 'roles' ? 'active' : '' ?>" href="/roles">
                                <span class="nav-icon">權</span>
                                <span>角色權限</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (\App\Core\Permission::can('system_updates.manage')): ?>
                    <div class="nav-section">
                        <span class="nav-section-title">系統設定</span>
                        <a class="<?= ($active ?? '') === 'system-update' ? 'active' : '' ?>" href="/system-update">
                            <span class="nav-icon">更</span>
                            <span>系統更新</span>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </aside>
    <?php endif; ?>

    <main class="main">
        <?php if ($currentUser): ?>
            <header class="topbar">
                <div class="page-title">
                    <?php if (!empty($section ?? '')): ?>
                        <span><?= e($section) ?></span>
                    <?php endif; ?>
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

        <div class="content-area">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>
</body>
</html>
