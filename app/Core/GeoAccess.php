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

    /** 管制模式:關閉／僅記錄(觀察)／啟用阻擋。 */
    public const MODE_OFF = 'off';
    public const MODE_MONITOR = 'monitor';
    public const MODE_ENFORCE = 'enforce';

    /** 記錄檔最多保留多少個不同來源 IP(避免檔案無限增長)。 */
    private const LOG_MAX_ENTRIES = 1000;

    public static function settingsPath(): string
    {
        return storage_path('access_control.json');
    }

    public static function logPath(): string
    {
        return storage_path('access_control_log.json');
    }

    /**
     * 讀取管制設定(含預設值)。
     *
     * @return array{mode:string, trust_proxy_header:string, allow_ips:array<int,string>, updated_at:?string, updated_by:mixed}
     */
    public static function loadSettings(bool $fresh = false): array
    {
        if (!$fresh && self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        $defaults = [
            'mode' => self::MODE_OFF,
            'trust_proxy_header' => '',
            'allow_ips' => [],
            'updated_at' => null,
            'updated_by' => null,
        ];

        $path = self::settingsPath();
        if (is_file($path)) {
            $decoded = json_decode((string) @file_get_contents($path), true);
            if (is_array($decoded)) {
                $defaults['mode'] = self::normalizeMode($decoded['mode'] ?? null, $decoded['enabled'] ?? null);
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
     * 將設定內容正規化為 off／monitor／enforce。相容舊版布林 enabled 欄位。
     */
    public static function normalizeMode(mixed $mode, mixed $legacyEnabled = null): string
    {
        if (is_string($mode)) {
            $mode = strtolower(trim($mode));
            if (in_array($mode, [self::MODE_OFF, self::MODE_MONITOR, self::MODE_ENFORCE], true)) {
                return $mode;
            }
        }
        // 舊版只有布林 enabled:true 視為阻擋。
        if ($legacyEnabled !== null) {
            return $legacyEnabled ? self::MODE_ENFORCE : self::MODE_OFF;
        }
        return self::MODE_OFF;
    }

    /**
     * 寫入管制設定。
     *
     * @param array{mode?:string, enabled?:bool, trust_proxy_header:string, allow_ips:array<int,string>} $settings
     */
    public static function saveSettings(array $settings): bool
    {
        $payload = [
            'mode' => self::normalizeMode($settings['mode'] ?? null, $settings['enabled'] ?? null),
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
     * 在請求最前端執行管制。
     * - 關閉:不動作。
     * - 僅記錄(觀察):把「會被擋的連線」記錄下來,但一律放行。
     * - 啟用阻擋:記錄並回應 403 結束程式。
     * 任何內部錯誤都採放行(fail-open),不影響正常服務。
     */
    public static function enforce(): void
    {
        try {
            if ((bool) config('security.access_control_disabled', false)) {
                return;
            }

            $settings = self::loadSettings();
            $mode = self::normalizeMode($settings['mode'] ?? null);
            if ($mode === self::MODE_OFF) {
                return;
            }

            $ip = self::clientIp($settings);
            $decision = self::decide($ip, $settings);
            if ($decision['allowed']) {
                return;
            }

            // 會被擋的連線:兩種模式都記錄。
            self::recordBlocked($ip, $decision['reason']);

            if ($mode === self::MODE_ENFORCE) {
                self::blockResponse($ip);
            }
            // 觀察模式:記錄後放行。
        } catch (\Throwable $e) {
            // 出錯一律放行,避免因程式問題造成全站無法連入。
            error_log('[GeoAccess] enforce failed, fail-open: ' . $e->getMessage());
        }
    }

    /** 記錄一筆「會被擋」的連線(以來源 IP 聚合,避免記錄爆量)。 */
    private static function recordBlocked(string $ip, string $reason): void
    {
        $path = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        $fp = @fopen(self::logPath(), 'c+');
        if ($fp === false) {
            return;
        }
        try {
            if (!flock($fp, LOCK_EX)) {
                return;
            }
            $raw = stream_get_contents($fp);
            $log = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($log)) {
                $log = [];
            }
            $log = self::mergeLogEntry($log, $ip, $reason, $path, $ua, self::LOG_MAX_ENTRIES, now());
            $encoded = json_encode($log, JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, $encoded);
                fflush($fp);
            }
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    /**
     * 純函式:把一筆連線併入記錄陣列(以 IP 為鍵聚合)。
     *
     * @param array<string, array<string, mixed>> $log
     * @return array<string, array<string, mixed>>
     */
    public static function mergeLogEntry(array $log, string $ip, string $reason, string $path, string $ua, int $cap, string $now): array
    {
        if (isset($log[$ip]) && is_array($log[$ip])) {
            $log[$ip]['count'] = (int) ($log[$ip]['count'] ?? 0) + 1;
            $log[$ip]['last_seen'] = $now;
            $log[$ip]['reason'] = $reason;
            $log[$ip]['last_path'] = mb_substr($path, 0, 200);
            $log[$ip]['last_ua'] = mb_substr($ua, 0, 200);
            return $log;
        }

        // 已達上限時不再新增不同 IP,只保留既有統計,避免檔案無限增長。
        if (count($log) >= $cap) {
            return $log;
        }

        $log[$ip] = [
            'ip' => $ip,
            'reason' => $reason,
            'count' => 1,
            'first_seen' => $now,
            'last_seen' => $now,
            'last_path' => mb_substr($path, 0, 200),
            'last_ua' => mb_substr($ua, 0, 200),
        ];
        return $log;
    }

    /**
     * 讀取連線記錄,依最後出現時間新到舊排序。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function readLog(): array
    {
        $path = self::logPath();
        if (!is_file($path)) {
            return [];
        }
        $decoded = json_decode((string) @file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [];
        }
        $rows = array_values($decoded);
        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($b['last_seen'] ?? ''), (string) ($a['last_seen'] ?? '')));
        return $rows;
    }

    /** 清除連線記錄。 */
    public static function clearLog(): bool
    {
        $path = self::logPath();
        if (!is_file($path)) {
            return true;
        }
        return @unlink($path);
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
