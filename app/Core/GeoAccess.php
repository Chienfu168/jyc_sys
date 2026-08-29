<?php

namespace App\Core;

/**
 * 國別連線管制:僅允許台灣(TW)IP 連入,阻擋國外連線。
 *
 * 設計重點(避免把自己鎖在門外):
 * - 內網／回送位址(127.0.0.1、::1、10./172.16./192.168. 等)一律放行。
 * - 可另設「允許清單」(單一 IP 或 CIDR),供固定的國外辦公室／VPN 例外放行。
 * - 讀取設定或範圍資料若發生任何錯誤,一律「放行」(fail-open),不因程式問題造成全站鎖死。
 * - 提供 .env 緊急停用開關 ACCESS_CONTROL_DISABLED=true;或直接刪除
 *   storage/access_control.json 即可解除管制。
 *
 * 台灣 IP 範圍資料存於 app/Core/GeoData/,由 tools/geoip/build_tw_ranges.py 產生。
 */
final class GeoAccess
{
    /** @var array<int, array{0:int,1:int}>|null */
    private static ?array $v4 = null;

    /** @var array<int, array{0:string,1:string}>|null */
    private static ?array $v6 = null;

    /** @var array<string, mixed>|null */
    private static ?array $settingsCache = null;

    public static function settingsPath(): string
    {
        return storage_path('access_control.json');
    }

    /**
     * 讀取管制設定(含預設值)。
     *
     * @return array{enabled:bool, trust_proxy_header:string, allow_ips:array<int,string>, updated_at:?string, updated_by:mixed}
     */
    public static function loadSettings(bool $fresh = false): array
    {
        if (!$fresh && self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        $defaults = [
            'enabled' => false,
            'trust_proxy_header' => '',
            'allow_ips' => [],
            'updated_at' => null,
            'updated_by' => null,
        ];

        $path = self::settingsPath();
        if (is_file($path)) {
            $decoded = json_decode((string) @file_get_contents($path), true);
            if (is_array($decoded)) {
                $defaults['enabled'] = (bool) ($decoded['enabled'] ?? false);
                $defaults['trust_proxy_header'] = self::normalizeHeaderChoice((string) ($decoded['trust_proxy_header'] ?? ''));
                $allow = $decoded['allow_ips'] ?? [];
                $defaults['allow_ips'] = is_array($allow) ? array_values(array_filter(array_map('strval', $allow), static fn (string $s): bool => trim($s) !== '')) : [];
                $defaults['updated_at'] = isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : null;
                $defaults['updated_by'] = $decoded['updated_by'] ?? null;
            }
        }

        self::$settingsCache = $defaults;
        return $defaults;
    }

    /**
     * 寫入管制設定。
     *
     * @param array{enabled:bool, trust_proxy_header:string, allow_ips:array<int,string>} $settings
     */
    public static function saveSettings(array $settings): bool
    {
        $payload = [
            'enabled' => (bool) $settings['enabled'],
            'trust_proxy_header' => self::normalizeHeaderChoice((string) ($settings['trust_proxy_header'] ?? '')),
            'allow_ips' => array_values(array_filter(array_map('strval', $settings['allow_ips'] ?? []), static fn (string $s): bool => trim($s) !== '')),
            'updated_at' => now(),
            'updated_by' => $settings['updated_by'] ?? (auth()->user()['id'] ?? null),
        ];

        $ok = @file_put_contents(self::settingsPath(), json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
        if ($ok) {
            self::$settingsCache = null;
        }
        return $ok;
    }

    private static function normalizeHeaderChoice(string $choice): string
    {
        $choice = strtolower(trim($choice));
        return in_array($choice, ['cf-connecting-ip', 'x-forwarded-for', 'x-real-ip'], true) ? $choice : '';
    }

    /**
     * 在請求最前端執行管制。若判定為應阻擋,直接回應 403 並結束程式。
     * 任何內部錯誤都採放行(fail-open),不影響正常服務。
     */
    public static function enforce(): void
    {
        try {
            if ((bool) config('security.access_control_disabled', false)) {
                return;
            }

            $settings = self::loadSettings();
            if (empty($settings['enabled'])) {
                return;
            }

            $ip = self::clientIp($settings);
            $decision = self::decide($ip, $settings);
            if ($decision['allowed']) {
                return;
            }

            self::blockResponse($ip);
        } catch (\Throwable $e) {
            // 出錯一律放行,避免因程式問題造成全站無法連入。
            error_log('[GeoAccess] enforce failed, fail-open: ' . $e->getMessage());
        }
    }

    private static function blockResponse(string $ip): never
    {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
        }
        $safeIp = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="zh-Hant"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>拒絕連線</title></head>'
            . '<body style="font-family:-apple-system,\'Segoe UI\',\'Microsoft JhengHei\',Arial,sans-serif;'
            . 'max-width:520px;margin:12vh auto;padding:0 24px;color:#1f2937;text-align:center">'
            . '<h1 style="font-size:22px;margin:0 0 12px">連線遭拒</h1>'
            . '<p style="line-height:1.8;color:#4b5563">本系統僅開放台灣境內連線。<br>'
            . '您目前的連線來源不在允許範圍內,如有需要請聯絡系統管理員。</p>'
            . '<p style="margin-top:20px;font-size:13px;color:#9ca3af">您的連線位址:' . $safeIp . '</p>'
            . '</body></html>';
        exit;
    }

    /**
     * 依設定判定該 IP 是否放行。
     *
     * @param array{enabled:bool, trust_proxy_header:string, allow_ips:array<int,string>} $settings
     * @return array{allowed:bool, reason:string}
     */
    public static function decide(string $ip, array $settings): array
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            // 無法判斷來源位址時放行,交由其他機制(登入)把關。
            return ['allowed' => true, 'reason' => 'unknown-ip'];
        }
        if (self::isPrivateOrReserved($ip)) {
            return ['allowed' => true, 'reason' => 'private'];
        }
        if (self::matchesAllowlist($ip, $settings['allow_ips'] ?? [])) {
            return ['allowed' => true, 'reason' => 'allowlist'];
        }
        if (self::isTaiwan($ip)) {
            return ['allowed' => true, 'reason' => 'taiwan'];
        }
        return ['allowed' => false, 'reason' => 'foreign'];
    }

    /** 取得用戶端連線位址,考慮是否信任反向代理標頭。 */
    public static function clientIp(array $settings): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $header = self::normalizeHeaderChoice((string) ($settings['trust_proxy_header'] ?? ''));
        if ($header === '') {
            return self::normalizeIp($remote);
        }

        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        $raw = (string) ($_SERVER[$serverKey] ?? '');
        if ($raw === '') {
            return self::normalizeIp($remote);
        }

        // X-Forwarded-For 可能是「client, proxy1, proxy2」;取最前面(最原始)的用戶端。
        $first = trim(explode(',', $raw)[0]);
        $first = self::normalizeIp($first);
        return filter_var($first, FILTER_VALIDATE_IP) !== false ? $first : self::normalizeIp($remote);
    }

    /** 正規化位址:去除連接埠、還原 IPv4-mapped IPv6(::ffff:1.2.3.4)。 */
    public static function normalizeIp(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '';
        }
        // [::1]:1234 形式
        if (str_starts_with($ip, '[')) {
            $end = strpos($ip, ']');
            if ($end !== false) {
                $ip = substr($ip, 1, $end - 1);
            }
        } elseif (substr_count($ip, ':') === 1 && str_contains($ip, '.')) {
            // IPv4:port
            $ip = explode(':', $ip)[0];
        }
        if (stripos($ip, '::ffff:') === 0) {
            $tail = substr($ip, 7);
            if (filter_var($tail, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                return $tail;
            }
        }
        return $ip;
    }

    /** 是否為內網／回送／保留位址(一律放行)。 */
    public static function isPrivateOrReserved(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /** 該 IP 是否屬於台灣範圍。 */
    public static function isTaiwan(string $ip): bool
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return false;
        }
        if (strlen($packed) === 4) {
            return self::inV4Ranges(self::ipv4ToInt($packed));
        }
        return self::inV6Ranges(bin2hex($packed));
    }

    private static function ipv4ToInt(string $packed): int
    {
        $parts = unpack('Nip', $packed);
        return $parts['ip'] & 0xFFFFFFFF;
    }

    private static function inV4Ranges(int $needle): bool
    {
        if (self::$v4 === null) {
            self::$v4 = require __DIR__ . '/GeoData/tw_ipv4_ranges.php';
        }
        $ranges = self::$v4;
        $lo = 0;
        $hi = count($ranges) - 1;
        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($needle < $ranges[$mid][0]) {
                $hi = $mid - 1;
            } elseif ($needle > $ranges[$mid][1]) {
                $lo = $mid + 1;
            } else {
                return true;
            }
        }
        return false;
    }

    private static function inV6Ranges(string $needleHex): bool
    {
        if (self::$v6 === null) {
            self::$v6 = require __DIR__ . '/GeoData/tw_ipv6_ranges.php';
        }
        $ranges = self::$v6;
        $lo = 0;
        $hi = count($ranges) - 1;
        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            if (strcmp($needleHex, $ranges[$mid][0]) < 0) {
                $hi = $mid - 1;
            } elseif (strcmp($needleHex, $ranges[$mid][1]) > 0) {
                $lo = $mid + 1;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * 是否符合允許清單(支援單一 IP 與 CIDR,v4/v6 皆可)。
     *
     * @param array<int,string> $list
     */
    public static function matchesAllowlist(string $ip, array $list): bool
    {
        foreach ($list as $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }
            if (self::ipInCidr($ip, $entry)) {
                return true;
            }
        }
        return false;
    }

    /** 判斷 IP 是否落在某個 CIDR 或等於某個單一 IP。 */
    public static function ipInCidr(string $ip, string $cidr): bool
    {
        $ipBin = @inet_pton($ip);
        if ($ipBin === false) {
            return false;
        }

        if (!str_contains($cidr, '/')) {
            $netBin = @inet_pton($cidr);
            return $netBin !== false && strlen($netBin) === strlen($ipBin) && $netBin === $ipBin;
        }

        [$net, $bitsRaw] = explode('/', $cidr, 2);
        $netBin = @inet_pton(trim($net));
        if ($netBin === false || strlen($netBin) !== strlen($ipBin)) {
            return false;
        }
        $bits = (int) $bitsRaw;
        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;
        if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            return false;
        }
        if ($remainder === 0) {
            return true;
        }
        $mask = chr(0xFF << (8 - $remainder) & 0xFF);
        return (ord($ipBin[$bytes]) & ord($mask)) === (ord($netBin[$bytes]) & ord($mask));
    }
}
