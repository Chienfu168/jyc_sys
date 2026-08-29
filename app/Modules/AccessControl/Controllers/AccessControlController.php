<?php

namespace App\Modules\AccessControl\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Core\GeoAccess;

/**
 * 「僅限台灣 IP 連線」管制設定。
 * 權限沿用系統設定類頁面的 system_updates.manage。
 */
final class AccessControlController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('system_updates.manage');

        $settings = GeoAccess::loadSettings(true);
        $currentIp = GeoAccess::clientIp($settings);
        $currentDecision = GeoAccess::decide($currentIp, ['enabled' => true] + $settings);

        $this->render('access-control.index', [
            'title' => '連線來源管制',
            'section' => '系統設定',
            'active' => 'access-control',
            'settings' => $settings,
            'currentIp' => $currentIp,
            'currentDecision' => $currentDecision,
            'killSwitch' => (bool) config('security.access_control_disabled', false),
            'printable' => false,
        ]);
    }

    public function update(): void
    {
        $this->requirePermission('system_updates.manage');

        $enabled = ($_POST['enabled'] ?? '') === '1';
        $trustHeader = (string) ($_POST['trust_proxy_header'] ?? '');
        $allowIps = $this->parseAllowList((string) ($_POST['allow_ips'] ?? ''));

        $draft = [
            'enabled' => $enabled,
            'trust_proxy_header' => $trustHeader,
            'allow_ips' => $allowIps,
        ];

        // 防呆:啟用時,若操作者「當下的連線來源」在此設定下會被擋,拒絕儲存,
        // 避免立即把自己鎖在門外。請先把自己的 IP 加入允許清單,或確認來源為台灣。
        if ($enabled) {
            $myIp = GeoAccess::clientIp($draft);
            $decision = GeoAccess::decide($myIp, ['enabled' => true] + $draft);
            if (!$decision['allowed']) {
                $this->backWithInput(
                    '/access-control',
                    $_POST,
                    '為避免將您自己鎖在門外,無法啟用:您目前的連線來源(' . $myIp . ')在此設定下會被阻擋。請先將此 IP 加入允許清單,或確認自台灣連線後再試。'
                );
            }
        }

        if (!GeoAccess::saveSettings($draft)) {
            $this->backWithInput('/access-control', $_POST, '設定儲存失敗,請確認 storage 目錄是否可寫入。');
        }

        AuditLog::write($enabled ? 'access_control_enable' : 'access_control_disable', 'access_control');
        flash('success', $enabled ? '已啟用「僅限台灣 IP」連線管制。' : '已停用連線來源管制。');
        redirect('/access-control');
    }

    /**
     * 解析允許清單文字(每行一筆,支援單一 IP 或 CIDR),只保留合法項目。
     *
     * @return array<int, string>
     */
    private function parseAllowList(string $raw): array
    {
        $out = [];
        foreach (preg_split('/[\r\n,]+/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if ($this->isValidIpOrCidr($line)) {
                $out[$line] = $line; // 去重
            }
        }
        return array_values($out);
    }

    private function isValidIpOrCidr(string $entry): bool
    {
        if (!str_contains($entry, '/')) {
            return filter_var($entry, FILTER_VALIDATE_IP) !== false;
        }
        [$net, $bits] = explode('/', $entry, 2);
        if (filter_var($net, FILTER_VALIDATE_IP) === false || !ctype_digit($bits)) {
            return false;
        }
        $max = str_contains($net, ':') ? 128 : 32;
        return (int) $bits >= 0 && (int) $bits <= $max;
    }
}
