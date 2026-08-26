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
    /** 快取視為過期的分鐘數;超過才於背景重新抓取。 */
    private const STALE_MINUTES = 180;

    /** 抓取逾時秒數,避免拖慢頁面。 */
    private const TIMEOUT = 8;

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
            if (self::isStale($feed)) {
                self::sync((int) $feed['id']);
                // 重新讀取本筆最新快取。
                $stmt = Database::pdo()->prepare('SELECT * FROM calendar_feeds WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => (int) $feed['id']]);
                $feed = $stmt->fetch() ?: $feed;
            }

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

    /** @param array<string, mixed> $feed */
    private static function isStale(array $feed): bool
    {
        if (empty($feed['last_synced_at'])) {
            return true;
        }
        $syncedTs = strtotime((string) $feed['last_synced_at']);
        return $syncedTs === false || (time() - $syncedTs) > self::STALE_MINUTES * 60;
    }

    /**
     * 抓取 URL 內容,回傳 [ics, error]。僅接受 http(s)。
     *
     * @return array{0: ?string, 1: ?string}
     */
    private static function fetch(string $url): array
    {
        if (!preg_match('#^https?://#i', $url)) {
            return [null, '網址格式不正確(需 http/https)'];
        }

        // Google 的 webcal:// 會以 https 提供,呼叫端請填 https 網址。
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 4,
                CURLOPT_TIMEOUT => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_USERAGENT => 'jyc-sys-calendar/1.0',
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false || $body === '') {
                return [null, $err !== '' ? $err : '無回應內容'];
            }
            if ($status >= 400) {
                return [null, 'HTTP ' . $status];
            }
            return [self::normalize((string) $body), null];
        }

        $context = stream_context_create(['http' => ['timeout' => self::TIMEOUT, 'follow_location' => 1]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false || $body === '') {
            return [null, '無法連線或內容為空'];
        }

        return [self::normalize($body), null];
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
