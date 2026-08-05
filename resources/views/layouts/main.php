<?php
$currentUser = auth()->user();
$activeKey = $active ?? '';
$approvalsActive = $activeKey === 'approvals';
$financeActive = in_array($activeKey, [
    'finance',
    'annual-budgets',
    'accounting',
    'foundation-assets',
    'bank-accounts',
    'petty-cash',
    'income-expenses',
    'lecturer-expenses',
    'travel-expenses',
    'payroll',
], true);
$operationsActive = in_array($activeKey, [
    'operations',
    'work-plans',
    'projects',
    'activities',
    'lecturers',
    'personnel',
    'leave-requests',
    'volunteers',
    'calendar',
], true);
$canFinance = $currentUser && (
    \App\Core\Permission::can('annual_budgets.view')
    || \App\Core\Permission::can('accounting.view')
    || \App\Core\Permission::can('foundation_assets.view')
    || \App\Core\Permission::can('bank_accounts.view')
    || \App\Core\Permission::can('petty_cash.view')
    || \App\Core\Permission::can('income_expenses.view')
    || \App\Core\Permission::can('lecturer_expenses.view')
    || \App\Core\Permission::can('travel_expenses.view')
    || \App\Core\Permission::can('payroll.view')
);
$canOperations = $currentUser && (
    \App\Core\Permission::can('work_plans.view')
    || \App\Core\Permission::can('projects.view')
    || \App\Core\Permission::can('activities.view')
    || \App\Core\Permission::can('lecturers.view')
    || \App\Core\Permission::can('personnel.view')
    || \App\Core\Permission::can('leave_requests.view')
    || \App\Core\Permission::can('volunteers.view')
    || \App\Core\Permission::can('calendar.view')
);
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') . ' | ' . config('app.name')) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= e(config('app.version', '0')) ?>">
    <style>
        .sidebar .nav { display: block !important; }
        .sidebar .nav-section { display: block !important; margin-bottom: 22px !important; }
        .sidebar .nav-section-title { display: block !important; margin: 0 0 8px !important; padding: 0 10px !important; }
        .sidebar .nav a { display: flex !important; width: 100% !important; align-items: center !important; margin: 0 0 8px !important; white-space: nowrap !important; }
        .sidebar .nav a span:last-child { display: block !important; min-width: 0 !important; overflow: hidden !important; text-overflow: ellipsis !important; }
        .app-shell { max-width: 100vw !important; overflow-x: clip !important; }
        .main { min-width: 0 !important; max-width: 100% !important; overflow-x: hidden !important; }
        .content-area { min-width: 0 !important; max-width: 1280px !important; }
        .guest-shell .main { width: 100vw !important; max-width: 100vw !important; padding: 0 !important; }
        .guest-shell .content-area { width: 100vw !important; max-width: none !important; display: block !important; }
        .panel, .stat-card, .release-box { min-width: 0 !important; }
    </style>
</head>
<body>
<div class="app-shell <?= $currentUser ? '' : 'guest-shell' ?>">
    <?php if ($currentUser): ?>
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">基</div>
                <div class="brand-text">
                    <strong><?= e(foundation_name()) ?></strong>
                    <span>內部管理</span>
                </div>
            </div>

            <nav class="nav" aria-label="主選單">
                <div class="nav-section">
                    <span class="nav-section-title">主功能</span>
                    <a class="<?= $activeKey === 'dashboard' ? 'active' : '' ?>" href="/">
                        <span class="nav-icon">總</span>
                        <span>總儀表板</span>
                    </a>
                    <a class="<?= $approvalsActive ? 'active' : '' ?>" href="/approvals">
                        <span class="nav-icon">核</span>
                        <span>簽核中心</span>
                    </a>
                    <?php if ($canFinance): ?>
                        <a class="<?= $financeActive ? 'active' : '' ?>" href="/finance">
                            <span class="nav-icon">財</span>
                            <span>財務會計</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($canOperations): ?>
                        <a class="<?= $operationsActive ? 'active' : '' ?>" href="/operations">
                            <span class="nav-icon">業</span>
                            <span>業務與人事</span>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (\App\Core\Permission::can('users.view') || \App\Core\Permission::can('roles.view')): ?>
                    <div class="nav-section">
                        <span class="nav-section-title">後台管理</span>
                        <?php if (\App\Core\Permission::can('users.view')): ?>
                            <a class="<?= $activeKey === 'users' ? 'active' : '' ?>" href="/users">
                                <span class="nav-icon">帳</span>
                                <span>使用者管理</span>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('roles.view')): ?>
                            <a class="<?= $activeKey === 'roles' ? 'active' : '' ?>" href="/roles">
                                <span class="nav-icon">權</span>
                                <span>角色權限</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (\App\Core\Permission::can('system_updates.manage') || \App\Core\Permission::can('foundation_profile.view')): ?>
                    <div class="nav-section">
                        <span class="nav-section-title">系統設定</span>
                        <?php if (\App\Core\Permission::can('foundation_profile.view')): ?>
                            <a class="<?= $activeKey === 'foundation-profile' ? 'active' : '' ?>" href="/foundation-profile">
                                <span class="nav-icon">基</span>
                                <span>基本資料</span>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Permission::can('system_updates.manage')): ?>
                            <a class="<?= $activeKey === 'system-update' ? 'active' : '' ?>" href="/system-update">
                                <span class="nav-icon">更</span>
                                <span>系統更新</span>
                            </a>
                        <?php endif; ?>
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
                    <a class="account-name" href="/account/profile"><?= e($currentUser['name']) ?></a>
                    <small><?= e($currentUser['role_name'] ?? '') ?></small>
                    <form method="post" action="/logout">
                        <?= csrf_field() ?>
                        <button class="btn ghost" type="submit">登出</button>
                    </form>
                </div>
            </header>

            <?php if (($printable ?? ($activeKey !== 'system-update')) !== false): ?>
                <div class="page-tools no-print">
                    <button class="btn" type="button" onclick="window.print()">列印 / 另存 PDF</button>
                </div>
            <?php endif; ?>
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
