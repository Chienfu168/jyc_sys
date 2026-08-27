<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Calendar\Services\CalendarFeedService;
use PHPUnit\Framework\TestCase;

/**
 * 外部日曆訂閱網址正規化測試(純字串,不連線)。
 */
final class CalendarFeedUrlTest extends TestCase
{
    public function test_webcal_is_rewritten_to_https(): void
    {
        self::assertSame(
            'https://calendar.google.com/calendar/ical/x/public/basic.ics',
            CalendarFeedService::normalizeUrl('webcal://calendar.google.com/calendar/ical/x/public/basic.ics')
        );
    }

    public function test_webcal_is_case_insensitive(): void
    {
        self::assertSame('https://a.example/b.ics', CalendarFeedService::normalizeUrl('WEBCAL://a.example/b.ics'));
    }

    public function test_https_and_http_are_trimmed_but_kept(): void
    {
        self::assertSame('https://a.example/b.ics', CalendarFeedService::normalizeUrl('  https://a.example/b.ics  '));
        self::assertSame('http://a.example/b.ics', CalendarFeedService::normalizeUrl('http://a.example/b.ics'));
    }

    public function test_non_webcal_scheme_is_left_unchanged(): void
    {
        self::assertSame('ftp://a.example/b.ics', CalendarFeedService::normalizeUrl('ftp://a.example/b.ics'));
    }
}
