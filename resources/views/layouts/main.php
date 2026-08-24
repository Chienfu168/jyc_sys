<?php
$currentUser = auth()->user();
$activeKey = $active ?? '';
$approvalsActive = $activeKey === 'approvals';

// 依基金會工作流程分組的側邊欄模組(捐贈收入 → 業務執行 → 人事 → 支出核銷 → 帳務)。
// 每個項目依 view 權限顯示;群組內全無可見項目時整組隱藏。
$navWorkflow = [
    ['title' => '捐贈與收入', 'items' => [
        ['perm' => 'donors.view', 'key' => 'donors', 'href' => '/donors', 'icon' => '捐', 'label' => '捐款人管理'],
        ['perm' => 'donations.view', 'key' => 'donations', 'href' => '/donations', 'icon' => '款', 'label' => '捐款紀錄'],
    ]],
    ['title' => '業務推動', 'items' => [
        ['perm' => 'work_plans.view', 'key' => 'work-plans', 'href' => '/work-plans', 'icon' => '計', 'label' => '工作計畫'],
        ['perm' => 'projects.view', 'key' => 'projects', 'href' => '/projects', 'icon' => '專', 'label' => '專案管理'],
        ['perm' => 'activities.view', 'key' => 'activities', 'href' => '/activities', 'icon' => '活', 'label' => '活動管理'],
        ['perm' => 'lecturers.view', 'key' => 'lecturers', 'href' => '/lecturers', 'icon' => '師', 'label' => '講師管理'],
        ['perm' => 'volunteers.view', 'key' => 'volunteers', 'href' => '/volunteers', 'icon' => '志', 'label' => '志工管理'],
        ['perm' => 'calendar.view', 'key' => 'calendar', 'href' => '/calendar', 'icon' => '曆', 'label' => '行事曆管理'],
    ]],
    ['title' => '人事差勤', 'items' => [
        ['perm' => 'personnel.view', 'key' => 'personnel', 'href' => '/personnel', 'icon' => '人', 'label' => '人事管理'],
        ['perm' => 'leave_requests.view', 'key' => 'leave-requests', 'href' => '/leave-requests', 'icon' => '假', 'label' => '人事請假'],
        ['perm' => 'payroll.view', 'key' => 'payroll', 'href' => '/payroll', 'icon' => '薪', 'label' => '薪資管理'],
    ]],
    ['title' => '支出與核銷', 'items' => [
        ['perm' => 'purchase_requests.view', 'key' => 'purchase-requests', 'href' => '/purchase-requests', 'icon' => '購', 'label' => '採購申請'],
        ['perm' => 'income_expenses.view', 'key' => 'income-expenses', 'href' => '/income-expenses', 'icon' => '收', 'label' => '收支紀錄'],
        ['perm' => 'lecturer_expenses.view', 'key' => 'lecturer-expenses', 'href' => '/lecturer-expenses', 'icon' => '鐘', 'label' => '講師支出費用'],
        ['perm' => 'travel_expenses.view', 'key' => 'travel-expenses', 'href' => '/travel-expenses', 'icon' => '差', 'label' => '出差費用'],
        ['perm' => 'petty_cash.view', 'key' => 'petty-cash', 'href' => '/petty-cash', 'icon' => '零', 'label' => '零用金'],
        ['perm' => 'payment_receipts.view', 'key' => 'payment-receipts', 'href' => '/payment-receipts', 'icon' => '領', 'label' => '領款收據'],
    ]],
    ['title' => '會計與帳務', 'items' => [
        ['perm' => 'board_meetings.view', 'key' => 'board-meetings', 'href' => '/board-meetings', 'icon' => '董', 'label' => '董事會議'],
        ['perm' => 'annual_budgets.view', 'key' => 'annual-budgets', 'href' => '/annual-budgets', 'icon' => '預', 'label' => '年度預算'],
        ['perm' => 'official_letters.view', 'key' => 'official-letters', 'href' => '/official-letters', 'icon' => '函', 'label' => '陳報公文'],
        ['perm' => 'accounting.view', 'key' => 'accounting', 'href' => '/accounting', 'icon' => '會', 'label' => '會計系統'],
        ['perm' => 'bank_accounts.view', 'key' => 'bank-accounts', 'href' => '/bank-accounts', 'icon' => '銀', 'label' => '銀行帳戶'],
        ['perm' => 'opening_balances.view', 'key' => 'opening-balances', 'href' => '/opening-balances', 'icon' => '初', 'label' => '期初餘額'],
    ]],
];

// 依權限過濾各群組項目,僅保留使用者可檢視的項目與非空群組。
$navWorkflow = array_values(array_filter(array_map(static function (array $group) use ($currentUser): array {
    $group['items'] = $currentUser
        ? array_values(array_filter($group['items'], static fn (array $item): bool => \App\Core\Permission::can($item['perm'])))
        : [];
    return $group;
}, $navWorkflow), static fn (array $group): bool => $group['items'] !== []));
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') . ' | ' . config('app.name')) ?></title>
    <?php $assetVersion = asset_version(); ?>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/app.css')) ?>?v=<?= e($assetVersion) ?>">
    <?php if (asset_url('assets/css/app.css') !== '/assets/css/app.css'): ?>
        <link rel="stylesheet" href="/assets/css/app.css?v=<?= e($assetVersion) ?>">
    <?php endif; ?>
    <style>
        <?php require base_path('resources/views/shared/critical-css.php'); ?>
        .sidebar .nav { display: block !important; }
        .sidebar .nav-section { display: block !important; margin-bottom: 22px !important; }
        .sidebar .nav-section-title { display: block !important; margin: 0 0 8px !important; padding: 0 10px !important; }
        .sidebar .nav a { display: flex !important; width: 100% !important; align-items: center !important; margin: 0 0 8px !important; white-space: nowrap !important; }
        .sidebar .nav a span:last-child { display: block !important; min-width: 0 !important; overflow: hidden !important; text-overflow: ellipsis !important; }
        .sidebar .nav-section-title { display: flex !important; align-items: center !important; justify-content: space-between !important; cursor: pointer; user-select: none; }
        .sidebar .nav-section-title::after { content: "\25BE"; font-size: 11px; opacity: 0.55; margin-left: 8px; }
        .sidebar .nav-section.collapsed .nav-section-title::after { content: "\25B8"; }
        .sidebar .nav-section.collapsed a { display: none !important; }
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
                    <span class="nav-section-title">總覽</span>
                    <a class="<?= $activeKey === 'dashboard' ? 'active' : '' ?>" href="/">
                        <span class="nav-icon">總</span>
                        <span>總儀表板</span>
                    </a>
                    <a class="<?= $approvalsActive ? 'active' : '' ?>" href="/approvals">
                        <span class="nav-icon">核</span>
                        <span>簽核中心</span>
                    </a>
                </div>

                <?php foreach ($navWorkflow as $group): ?>
                    <div class="nav-section">
                        <span class="nav-section-title"><?= e($group['title']) ?></span>
                        <?php foreach ($group['items'] as $item): ?>
                            <a class="<?= $activeKey === $item['key'] ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                                <span class="nav-icon"><?= e($item['icon']) ?></span>
                                <span><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

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
                            <a class="<?= $activeKey === 'system-security' ? 'active' : '' ?>" href="/system-security">
                                <span class="nav-icon">安</span>
                                <span>安全檢查</span>
                            </a>
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
<?php if ($currentUser): ?>
<script>
(function () {
    var nav = document.querySelector('.sidebar .nav');
    if (!nav) {
        return;
    }
    var KEY = 'nav-collapsed-groups';
    var store = {};
    try {
        var raw = JSON.parse(localStorage.getItem(KEY) || '{}');
        if (raw && typeof raw === 'object') {
            store = raw;
        }
    } catch (e) {
        store = {};
    }

    function setState(section, title, collapsed) {
        section.classList.toggle('collapsed', collapsed);
        title.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    }

    nav.querySelectorAll('.nav-section').forEach(function (section) {
        var title = section.querySelector('.nav-section-title');
        if (!title) {
            return;
        }
        var name = (title.textContent || '').trim();
        var hasActive = !!section.querySelector('a.active');
        title.setAttribute('role', 'button');
        title.setAttribute('tabindex', '0');
        setState(section, title, store[name] === true && !hasActive);

        function toggle() {
            var collapsed = !section.classList.contains('collapsed');
            setState(section, title, collapsed);
            store[name] = collapsed;
            try {
                localStorage.setItem(KEY, JSON.stringify(store));
            } catch (e) {}
        }

        title.addEventListener('click', toggle);
        title.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggle();
            }
        });
    });
})();
</script>
<?php endif; ?>
</body>
</html>
