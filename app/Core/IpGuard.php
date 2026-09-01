<?php

namespace App\Core;

/**
 * 自動封鎖累犯 IP(fail2ban 式)。
 *
 * - 於請求最前端檢查來源 IP 是否在封鎖名單(未過期);是則回 403 結束。
 * - 登入失敗時累計:同一 IP 在觀察視窗內失敗達門檻,即自動暫時封鎖一段時間。
 * - 內網／回送位址與「連線來源管制」的允許清單一律豁免,避免誤鎖自己人。
 * - 封鎖為暫時性(到期自動失效),亦可由管理者手動封鎖／解除。
 * - 任何內部錯誤一律放行(fail-open);.env 設 IP_AUTOBLOCK_DISABLED=true 可緊急停用。
 *
 * 來源 IP 解析與允許清單沿用 GeoAccess(共用同一組反向代理標頭與允許清單設定)。
 */
final class IpGuard
{
    /** 取得目前用戶端 IP(考慮反向代理設定),與登入失敗記錄、封鎖判定共用同一來源。 */
    public static function currentClientIp(): string
    {
        try {
            return GeoAccess::clientIp(GeoAccess::loadSettings());
        } catch (\Throwable) {
            return GeoAccess::normalizeIp((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        }
    }

    /** 該 IP 是否豁免(內網／回送,或在連線來源管制的允許清單內)。 */
    public static function isExempt(string $ip): bool
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }
        if (GeoAccess::isPrivateOrReserved($ip)) {
            return true;
        }
        try {
            $allow = GeoAccess::loadSettings()['allow_ips'] ?? [];
            return GeoAccess::matchesAllowlist($ip, is_array($allow) ? $allow : []);
        } catch (\Throwable) {
            return false;
        }
    }

    /** 請求最前端執行:若來源 IP 目前被封鎖,回 403 並結束。 */
    public static function enforce(): void
    {
        try {
            if ((bool) config('security.ip_autoblock_disabled', false)) {
                return;
            }
            $ip = self::currentClientIp();
            if (self::isExempt($ip)) {
                return;
            }
            if (self::blockedUntil($ip) !== null) {
                self::blockResponse($ip);
            }
        } catch (\Throwable $e) {
            error_log('[IpGuard] enforce failed, fail-open: ' . $e->getMessage());
        }
    }

    /** 回傳該 IP 的有效封鎖到期時間(未封鎖或已過期則回 null)。 */
    public static function blockedUntil(string $ip): ?string
    {
        $stmt = Database::pdo()->prepare(
            'SELECT blocked_until FROM blocked_ips WHERE ip_address = :ip AND blocked_until > NOW() LIMIT 1'
        );
        $stmt->execute(['ip' => $ip]);
        $until = $stmt->fetchColumn();
        return $until !== false ? (string) $until : null;
    }

    /**
     * 登入失敗後呼叫:累計同一 IP 於觀察視窗內的失敗次數,達門檻即自動封鎖。
     * 需在 login_attempts 已記錄本次失敗之後呼叫。
     */
    public static function registerLoginFailure(string $ip): void
    {
        try {
            if ((bool) config('security.ip_autoblock_disabled', false)) {
                return;
            }
            if (self::isExempt($ip)) {
                return;
            }

            $window = (int) config('security.ip_autoblock_window_minutes', 15);
            $threshold = max(1, (int) config('security.ip_autoblock_fail_threshold', 20));
            $since = date('Y-m-d H:i:s', time() - ($window * 60));

            $stmt = Database::pdo()->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE ip_address = :ip AND success = 0 AND created_at >= :since'
            );
            $stmt->execute(['ip' => $ip, 'since' => $since]);
            $fails = (int) $stmt->fetchColumn();

            if ($fails >= $threshold) {
                $minutes = max(1, (int) config('security.ip_autoblock_minutes', 60));
                self::upsertBlock($ip, 'login_bruteforce', $fails, $minutes, null, null);
            }
        } catch (\Throwable $e) {
            error_log('[IpGuard] registerLoginFailure failed: ' . $e->getMessage());
        }
    }

    /** 手動封鎖某 IP 指定分鐘數。 */
    public static function manualBlock(string $ip, int $minutes, ?string $notes, ?int $byUserId): bool
    {
        $ip = GeoAccess::normalizeIp($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        self::upsertBlock($ip, 'manual', 0, max(1, $minutes), $byUserId, $notes);
        return true;
    }

    public static function unblock(string $ip): void
    {
        Database::pdo()->prepare('DELETE FROM blocked_ips WHERE ip_address = :ip')->execute(['ip' => $ip]);
    }

    /** 清除所有已過期的封鎖列(維護用)。 */
    public static function purgeExpired(): int
    {
        $stmt = Database::pdo()->prepare('DELETE FROM blocked_ips WHERE blocked_until <= NOW()');
        $stmt->execute();
        return $stmt->rowCount();
    }

    /** @return array<int, array<string, mixed>> 目前仍有效的封鎖,近到期排序。 */
    public static function activeBlocks(): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT blocked_ips.*, users.name AS blocked_by_name
             FROM blocked_ips
             LEFT JOIN users ON users.id = blocked_ips.blocked_by
             WHERE blocked_until > NOW()
             ORDER BY blocked_until DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * 近期登入失敗最多的來源 IP(供觀察尚未達封鎖門檻者)。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function recentOffenders(int $limit = 20): array
    {
        $window = (int) config('security.ip_autoblock_window_minutes', 15);
        $since = date('Y-m-d H:i:s', time() - ($window * 60));
        $limit = max(1, min(100, $limit));
        $stmt = Database::pdo()->prepare(
            'SELECT ip_address, COUNT(*) AS fails, MAX(created_at) AS last_seen
             FROM login_attempts
             WHERE success = 0 AND created_at >= :since
             GROUP BY ip_address
             ORDER BY fails DESC, last_seen DESC
             LIMIT ' . $limit
        );
        $stmt->execute(['since' => $since]);
        return $stmt->fetchAll();
    }

    private static function upsertBlock(string $ip, string $reason, int $failCount, int $minutes, ?int $byUserId, ?string $notes): void
    {
        $until = date('Y-m-d H:i:s', time() + ($minutes * 60));
        Database::pdo()->prepare(
            'INSERT INTO blocked_ips (ip_address, reason, fail_count, blocked_until, blocked_by, notes, created_at, updated_at)
             VALUES (:ip, :reason, :fail_count, :blocked_until, :blocked_by, :notes, :created_at, :updated_at)
             ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                fail_count = GREATEST(fail_count, VALUES(fail_count)),
                blocked_until = GREATEST(blocked_until, VALUES(blocked_until)),
                blocked_by = VALUES(blocked_by),
                notes = VALUES(notes),
                updated_at = VALUES(updated_at)'
        )->execute([
            'ip' => $ip,
            'reason' => $reason,
            'fail_count' => $failCount,
            'blocked_until' => $until,
            'blocked_by' => $byUserId,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function blockResponse(string $ip): never
    {
        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            header('Retry-After: 3600');
        }
        $safeIp = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
        echo '<!doctype html><html lang="zh-Hant"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>連線暫時封鎖</title></head>'
            . '<body style="font-family:-apple-system,\'Segoe UI\',\'Microsoft JhengHei\',Arial,sans-serif;'
            . 'max-width:520px;margin:12vh auto;padding:0 24px;color:#1f2937;text-align:center">'
            . '<h1 style="font-size:22px;margin:0 0 12px">連線暫時封鎖</h1>'
            . '<p style="line-height:1.8;color:#4b5563">因偵測到多次登入失敗,您的連線已被暫時封鎖,<br>'
            . '請稍後再試,或聯絡系統管理員。</p>'
            . '<p style="margin-top:20px;font-size:13px;color:#9ca3af">您的連線位址:' . $safeIp . '</p>'
            . '</body></html>';
        exit;
    }
}
