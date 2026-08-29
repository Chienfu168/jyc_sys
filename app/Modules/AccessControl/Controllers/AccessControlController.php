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
        $currentDecision = GeoAccess::decide($currentIp, $settings);

        $this->render('access-control.index', [
            'title' => '連線來源管制',
            'section' => '系統設定',
            'active' => 'access-control',
            'settings' => $settings,
            'currentIp' => $currentIp,
            'currentDecision' => $currentDecision,
            'logRows' => GeoAccess::readLog(),
            'killSwitch' => (bool) config('security.access_control_disabled', false),
            'printable' => false,
        ]);
    }

    public function update(): void
    {
        $this->requirePermission('system_updates.manage');

        $mode = GeoAccess::normalizeMode($_POST['mode'] ?? null);
        $trustHeader = (string) ($_POST['trust_proxy_header'] ?? '');
        $allowIps = $this->parseAllowList((string) ($_POST['allow_ips'] ?? ''));

        $draft = [
            'mode' => $mode,
            'trust_proxy_header' => $trustHeader,
            'allow_ips' => $allowIps,
        ];

        // 防呆:僅在「啟用阻擋」時,若操作者「當下的連線來源」在此設定下會被擋,拒絕儲存,
        // 避免立即把自己鎖在門外。觀察模式不阻擋,故不需此檢查。
        if ($mode === GeoAccess::MODE_ENFORCE) {
            $myIp = GeoAccess::clientIp($draft);
            $decision = GeoAccess::decide($myIp, $draft);
            if (!$decision['allowed']) {
                $this->backWithInput(
                    '/access-control',
                    $_POST,
                    '為避免將您自己鎖在門外,無法啟用阻擋:您目前的連線來源(' . $myIp . ')在此設定下會被阻擋。請先將此 IP 加入允許清單,或先用「僅記錄(觀察模式)」確認後再啟用。'
                );
            }
        }

        if (!GeoAccess::saveSettings($draft)) {
            $this->backWithInput('/access-control', $_POST, '設定儲存失敗,請確認 storage 目錄是否可寫入。');
        }

        AuditLog::write('access_control_mode_' . $mode, 'access_control');
        $labels = [
            GeoAccess::MODE_OFF => '已關閉連線來源管制。',
            GeoAccess::MODE_MONITOR => '已切換為「僅記錄(觀察模式)」:不會阻擋任何連線,但會記錄會被擋的連線供日後判斷。',
            GeoAccess::MODE_ENFORCE => '已啟用「僅限台灣 IP」連線管制,將阻擋國外連線。',
        ];
        flash('success', $labels[$mode] ?? '設定已更新。');
        redirect('/access-control');
    }

    public function clearLog(): void
    {
        $this->requirePermission('system_updates.manage');

        if (!GeoAccess::clearLog()) {
            $this->backWithInput('/access-control', [], '清除記錄失敗,請確認 storage 目錄權限。');
        }
        AuditLog::write('access_control_log_clear', 'access_control');
        flash('success', '已清除連線記錄。');
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
