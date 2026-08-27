<?php

namespace App\Modules\Calendar\Services;

use App\Core\Database;
use App\Domain\Calendar\ICalParser;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * 外部日曆(iCal 訂閱)服務:抓取訂閱網址、快取 ICS,並展開為指定範圍的事件。
 */
final class CalendarFeedService
{
    /** 抓取逾時秒數(僅用於手動/建立時同步,不在頁面載入時觸發)。 */
    private const TIMEOUT = 12;

    /** @return array<int, array<string, mixed>> */
    public static function activeFeeds(): array
    {
        try {
            return Database::pdo()->query(
                'SELECT * FROM calendar_feeds WHERE status = "active" ORDER BY sort_order, id'
            )->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * 抓取單一訂閱網址並更新快取,回傳是否成功。
     */
    public static function sync(int $feedId): bool
    {
        try {
            $stmt = Database::pdo()->prepare('SELECT * FROM calendar_feeds WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $feedId]);
            $feed = $stmt->fetch();
            if (!$feed) {
                return false;
            }

            [$ics, $error] = self::fetch((string) $feed['ics_url']);
            if ($ics === null) {
                Database::pdo()->prepare(
                    'UPDATE calendar_feeds SET last_error = :err, updated_at = :now WHERE id = :id'
                )->execute(['err' => mb_substr($error ?? '抓取失敗', 0, 250), 'now' => now(), 'id' => $feedId]);
                return false;
            }

            Database::pdo()->prepare(
                'UPDATE calendar_feeds
                 SET cached_ics = :ics, last_synced_at = :now, last_error = NULL, updated_at = :now
                 WHERE id = :id'
            )->execute(['ics' => $ics, 'now' => now(), 'id' => $feedId]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** 同步所有啟用中的訂閱,回傳成功筆數。 */
    public static function syncAllActive(): int
    {
        $ok = 0;
        foreach (self::activeFeeds() as $feed) {
            if (self::sync((int) $feed['id'])) {
                $ok++;
            }
        }
        return $ok;
    }

    /**
     * 取得指定範圍內、所有啟用訂閱的外部事件(唯讀),供行事曆合併顯示。
     * 快取過期或從未同步者會嘗試即時抓取一次(失敗不影響頁面)。
     *
     * @return array<int, array<string, mixed>>
     */
    public static function eventsInRange(string $rangeStartDate, string $rangeEndDate): array
    {
        $tz = new DateTimeZone((string) config('app.timezone', 'Asia/Taipei'));
        try {
            $rangeStart = new DateTimeImmutable($rangeStartDate . ' 00:00:00', $tz);
            $rangeEnd = new DateTimeImmutable($rangeEndDate . ' 23:59:59', $tz);
        } catch (Throwable) {
            return [];
        }

        $events = [];
        foreach (self::activeFeeds() as $feed) {
            // 僅使用既有快取展開,不於頁面載入時做同步網路抓取,
            // 避免外部網址逾時或被主機阻擋時拖垮整個行事曆頁面。
            // 更新快取改由「同步／全部同步」按鈕或排程觸發。
            $ics = (string) ($feed['cached_ics'] ?? '');
            if (trim($ics) === '') {
                continue;
            }

            try {
                $parsed = ICalParser::expand($ics, $rangeStart, $rangeEnd, $tz);
            } catch (Throwable) {
                continue;
            }

            foreach ($parsed as $event) {
                $event['feed_id'] = (int) $feed['id'];
                $event['feed_name'] = (string) $feed['name'];
                $event['feed_color'] = (string) $feed['color'];
                $event['external'] = true;
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * 將訂閱網址正規化:webcal:// 轉為 https://。
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (preg_match('#^webcal://#i', $url)) {
            return 'https://' . substr($url, strlen('webcal://'));
        }
        return $url;
    }

    /**
     * 抓取 URL 內容,回傳 [ics, error]。僅接受 http(s)(webcal 會先轉 https)。
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function fetch(string $url): array
    {
        $url = self::normalizeUrl($url);
        if (!preg_match('#^https?://#i', $url)) {
            return [null, '網址格式不正確(需 http/https 或 webcal)'];
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_USERAGENT => 'jyc-sys-calendar/1.0',
                CURLOPT_ENCODING => '', // 接受並自動解壓 gzip/deflate。
                CURLOPT_HTTPHEADER => ['Accept: text/calendar, text/plain, */*'],
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false || $body === '') {
                return [null, $err !== '' ? self::friendlyCurlError($err) : '無回應內容(可能被主機阻擋對外連線)'];
            }
            if ($status >= 400) {
                return [null, 'HTTP ' . $status . '(請確認為公開且有效的 iCal 網址)'];
            }
            return self::validateIcs(self::normalize((string) $body));
        }

        $context = stream_context_create(['http' => [
            'timeout' => self::TIMEOUT,
            'follow_location' => 1,
            'header' => "Accept: text/calendar, text/plain, */*\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false || $body === '') {
            return [null, '無法連線或內容為空(可能被主機阻擋對外連線)'];
        }

        return self::validateIcs(self::normalize($body));
    }

    /**
     * 確認回應內容確實是 iCal(避免使用者貼到嵌入/分享頁而非 .ics 網址)。
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function validateIcs(string $body): array
    {
        if (stripos($body, 'BEGIN:VCALENDAR') === false) {
            return [null, '回應內容不是 iCal 格式(請確認貼的是「iCal 格式的公開網址」,結尾為 .ics,而非嵌入或分享連結)'];
        }
        return [$body, null];
    }

    private static function friendlyCurlError(string $err): string
    {
        $lower = strtolower($err);
        if (str_contains($lower, 'ssl') || str_contains($lower, 'certificate')) {
            return 'SSL 憑證驗證失敗,請主機商更新 CA 憑證庫:' . $err;
        }
        if (str_contains($lower, 'timed out') || str_contains($lower, 'timeout')) {
            return '連線逾時(主機可能無法對外連線或網址回應過慢):' . $err;
        }
        if (str_contains($lower, "couldn't resolve") || str_contains($lower, 'could not resolve')) {
            return '無法解析網域(請確認網址正確且主機可對外連線):' . $err;
        }
        return $err;
    }

    private static function normalize(string $body): string
    {
        // 確保為 UTF-8(部分來源可能非 UTF-8),並保留原始換行供解析器折行處理。
        if (!mb_check_encoding($body, 'UTF-8')) {
            $converted = @mb_convert_encoding($body, 'UTF-8', 'UTF-8, BIG5, GB2312, ISO-8859-1');
            if ($converted !== false) {
                $body = $converted;
            }
        }
        return $body;
    }
}
