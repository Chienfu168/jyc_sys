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
                <div class="brand-text">
                    <strong><?= e(config('app.name')) ?></strong>
                    <span>內部管理</span>
                </div>
            </div>

            <nav class="nav" aria-label="主選單">
                <div class="nav-section">
                    <span class="nav-section-title">工作台</span>
                    <a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="/">
                        <span class="nav-icon">總</span>
                        <span>總儀表板</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">主要業務</span>
                    <?php if (\App\Core\Permission::can('annual_budgets.view')): ?>
                        <a class="<?= ($active ?? '') === 'annual-budgets' ? 'active' : '' ?>" href="/annual-budgets">
                            <span class="nav-icon">預</span>
                            <span>年度預算</span>
                        </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Permission::can('work_plans.view')): ?>
                        <a class="<?= ($active ?? '') === 'work-plans' ? 'active' : '' ?>" href="/work-plans">
                            <span class="nav-icon">計</span>
                            <span>工作計畫</span>
                        </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Permission::can('projects.view')): ?>
                        <a class="<?= ($active ?? '') === 'projects' ? 'active' : '' ?>" href="/projects">
                            <span class="nav-icon">案</span>
                            <span>專案管理</span>
                        </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Permission::can('activities.view')): ?>
                        <a class="<?= ($active ?? '') === 'activities' ? 'active' : '' ?>" href="/activities">
                            <span class="nav-icon">活</span>
                            <span>活動管理</span>
                        </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Permission::can('lecturers.view')): ?>
                        <a class="<?= ($active ?? '') === 'lecturers' ? 'active' : '' ?>" href="/lecturers">
                            <span class="nav-icon">師</span>
                            <span>講師管理</span>
                        </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Permission::can('personnel.view')): ?>
                        <a class="<?= ($active ?? '') === 'personnel' ? 'active' : '' ?>" href="/personnel">
                            <span class="nav-icon">人</span>
                            <span>人事管理</span>
                        </a>
                    <?php endif; ?>
                    <?php if (\App\Core\Permission::can('calendar.view')): ?>
                        <a class="<?= ($active ?? '') === 'calendar' ? 'active' : '' ?>" href="/calendar">
                            <span class="nav-icon">曆</span>
                            <span>行事曆管理</span>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (\App\Core\Permission::can('users.view') || \App\Core\Permission::can('roles.view')): ?>
                    <div class="nav-section">
                        <span class="nav-section-title">後台管理</span>
                        <?php if (\App\Core\Permission::can('users.view')): ?>
                            <a class="<?= ($active ?? '') === 'users' ? 'active' : '' ?>" href="/users">
                                <span class="nav-icon">帳</span>
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
