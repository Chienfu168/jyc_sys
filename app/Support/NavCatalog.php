<?php

namespace App\Support;

use App\Core\Permission;

/**
 * 側邊選單與「常用連結」共用的功能目錄。
 * 集中定義各功能的 key／連結／圖示／標籤／所需權限,供側邊欄渲染與常用連結挑選共用。
 */
final class NavCatalog
{
    /**
     * 依基金會工作流程分組的模組清單(未過濾權限)。
     *
     * @return array<int, array{title:string, items:array<int, array{perm:string, key:string, href:string, icon:string, label:string}>}>
     */
    public static function groups(): array
    {
        return [
            ['title' => '總覽', 'items' => [
                ['perm' => '', 'key' => 'dashboard', 'href' => '/', 'icon' => '總', 'label' => '總儀表板'],
            ]],
            ['title' => '捐贈與收入', 'items' => [
                ['perm' => 'donors.view', 'key' => 'donors', 'href' => '/donors', 'icon' => '捐', 'label' => '捐款人管理'],
                ['perm' => 'donations.view', 'key' => 'donations', 'href' => '/donations', 'icon' => '款', 'label' => '捐款紀錄'],
            ]],
            ['title' => '業務推動', 'items' => [
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
                ['perm' => 'petty_cash.manage', 'key' => 'petty-cash-quick', 'href' => '/petty-cash/quick', 'icon' => '快', 'label' => '零用金快速記帳'],
                ['perm' => 'expense_requests.view', 'key' => 'expense-requests', 'href' => '/expense-requests', 'icon' => '請', 'label' => '費用申請'],
                ['perm' => 'payment_receipts.view', 'key' => 'payment-receipts', 'href' => '/payment-receipts', 'icon' => '領', 'label' => '領款收據'],
            ]],
            ['title' => '會計與帳務', 'items' => [
                ['perm' => 'accounting.view', 'key' => 'accounting', 'href' => '/accounting', 'icon' => '會', 'label' => '會計系統'],
                ['perm' => 'bank_accounts.view', 'key' => 'bank-accounts', 'href' => '/bank-accounts', 'icon' => '銀', 'label' => '銀行帳戶'],
                ['perm' => 'opening_balances.view', 'key' => 'opening-balances', 'href' => '/opening-balances', 'icon' => '初', 'label' => '期初餘額'],
            ]],
            ['title' => '主管機關核備', 'items' => [
                ['perm' => 'board_meetings.view', 'key' => 'board-meetings', 'href' => '/board-meetings', 'icon' => '董', 'label' => '董事會議'],
                ['perm' => 'work_plans.view', 'key' => 'work-plans', 'href' => '/work-plans', 'icon' => '計', 'label' => '工作計畫'],
                ['perm' => 'annual_budgets.view', 'key' => 'annual-budgets', 'href' => '/annual-budgets', 'icon' => '預', 'label' => '年度預算'],
                ['perm' => 'operating_statements.view', 'key' => 'operating-statements', 'href' => '/operating-statements', 'icon' => '營', 'label' => '收支營運表'],
                ['perm' => 'balance_sheets.view', 'key' => 'balance-sheets', 'href' => '/balance-sheets', 'icon' => '資', 'label' => '資產負債表'],
                ['perm' => 'cash_flow_statements.view', 'key' => 'cash-flow-statements', 'href' => '/cash-flow-statements', 'icon' => '流', 'label' => '現金流量表'],
                ['perm' => 'net_asset_statements.view', 'key' => 'net-asset-statements', 'href' => '/net-asset-statements', 'icon' => '淨', 'label' => '淨值變動表'],
                ['perm' => 'official_letters.view', 'key' => 'official-letters', 'href' => '/official-letters', 'icon' => '函', 'label' => '陳報公文'],
            ]],
        ];
    }

    /**
     * 攤平為單一清單(未過濾權限),key => item。
     *
     * @return array<string, array{perm:string, key:string, href:string, icon:string, label:string}>
     */
    public static function flatByKey(): array
    {
        $out = [];
        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                $out[$item['key']] = $item;
            }
        }
        return $out;
    }

    /** 目前使用者是否有權限檢視某目錄項目(空權限視為一律可見)。 */
    public static function canView(array $item): bool
    {
        $perm = (string) ($item['perm'] ?? '');
        return $perm === '' || Permission::can($perm);
    }

    /**
     * 依權限過濾各群組項目,只保留可見項目與非空群組。
     *
     * @return array<int, array{title:string, items:array<int, array<string, mixed>>}>
     */
    public static function visibleGroups(): array
    {
        return array_values(array_filter(array_map(static function (array $group): array {
            $group['items'] = array_values(array_filter($group['items'], [self::class, 'canView']));
            return $group;
        }, self::groups()), static fn (array $group): bool => $group['items'] !== []));
    }
}
