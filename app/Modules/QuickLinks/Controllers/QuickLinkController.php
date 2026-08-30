<?php

namespace App\Modules\QuickLinks\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Domain\Navigation\UserQuickLinks;
use App\Support\NavCatalog;

/**
 * 使用者自訂「常用連結」:把常用的功能釘選到側邊選單上方,各使用者各自設定。
 * 只需登入即可設定自己的常用連結;可挑選的項目一律受各自的檢視權限限制。
 */
final class QuickLinkController extends Controller
{
    public function edit(): void
    {
        $this->requireAuth();

        $userId = $this->currentUserId();

        $this->render('quick-links.edit', [
            'title' => '常用連結設定',
            'section' => '系統設定',
            'active' => 'quick-links',
            'groups' => NavCatalog::visibleGroups(),
            'selectedKeys' => UserQuickLinks::keysFor($userId),
            'printable' => false,
        ]);
    }

    public function update(): void
    {
        $this->requireAuth();

        $keys = $_POST['links'] ?? [];
        if (!is_array($keys)) {
            $keys = [];
        }
        // 依「排序輸入」重新排序:欄位 order[key] 為數字,數字小的在前;未填者沿用出現順序。
        $order = is_array($_POST['order'] ?? null) ? $_POST['order'] : [];
        $keys = array_values(array_map('strval', $keys));
        usort($keys, static function (string $a, string $b) use ($order): int {
            $oa = isset($order[$a]) && is_numeric($order[$a]) ? (int) $order[$a] : PHP_INT_MAX;
            $ob = isset($order[$b]) && is_numeric($order[$b]) ? (int) $order[$b] : PHP_INT_MAX;
            return $oa <=> $ob;
        });

        UserQuickLinks::replaceFor($this->currentUserId(), $keys);

        AuditLog::write('update', 'quick_links');
        flash('success', '常用連結已更新。');
        redirect('/quick-links');
    }
}
