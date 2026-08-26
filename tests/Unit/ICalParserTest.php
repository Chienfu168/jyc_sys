<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Calendar\ICalParser;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * iCal(.ics)解析器測試。
 */
final class ICalParserTest extends TestCase
{
    private DateTimeZone $tz;
    private DateTimeImmutable $rangeStart;
    private DateTimeImmutable $rangeEnd;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('Asia/Taipei');
        $this->rangeStart = new DateTimeImmutable('2026-01-01 00:00:00', $this->tz);
        $this->rangeEnd = new DateTimeImmutable('2026-01-31 23:59:59', $this->tz);
    }

    /** @param array<int, array<string, mixed>> $events */
    private function parse(string $body): array
    {
        $ics = "BEGIN:VCALENDAR\r\n" . $body . "END:VCALENDAR\r\n";
        return ICalParser::expand($ics, $this->rangeStart, $this->rangeEnd, $this->tz);
    }

    public function test_all_day_holiday_event(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:h1\r\nSUMMARY:元旦\r\n" .
            "DTSTART;VALUE=DATE:20260101\r\nDTEND;VALUE=DATE:20260102\r\nEND:VEVENT\r\n"
        );

        $this->assertCount(1, $events);
        $this->assertSame('元旦', $events[0]['title']);
        $this->assertTrue($events[0]['all_day']);
        $this->assertSame('2026-01-01 00:00:00', $events[0]['starts_at']);
        $this->assertSame('2026-01-01 00:00:00', $events[0]['ends_at']);
    }

    public function test_timed_utc_event_converts_to_taipei(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:m1\r\nSUMMARY:會議\r\n" .
            "DTSTART:20260115T010000Z\r\nDTEND:20260115T020000Z\r\nEND:VEVENT\r\n"
        );

        $this->assertCount(1, $events);
        $this->assertFalse($events[0]['all_day']);
        // 01:00 UTC = 09:00 台北。
        $this->assertSame('2026-01-15 09:00:00', $events[0]['starts_at']);
        $this->assertSame('2026-01-15 10:00:00', $events[0]['ends_at']);
    }

    public function test_weekly_recurrence_with_count(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:w1\r\nSUMMARY:週會\r\n" .
            "DTSTART;TZID=Asia/Taipei:20260105T100000\r\nRRULE:FREQ=WEEKLY;COUNT=3\r\nEND:VEVENT\r\n"
        );

        $starts = array_column($events, 'starts_at');
        $this->assertSame(
            ['2026-01-05 10:00:00', '2026-01-12 10:00:00', '2026-01-19 10:00:00'],
            $starts
        );
    }

    public function test_exdate_excludes_occurrence(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:w2\r\nSUMMARY:週會\r\n" .
            "DTSTART;TZID=Asia/Taipei:20260105T100000\r\nRRULE:FREQ=WEEKLY;COUNT=3\r\n" .
            "EXDATE;TZID=Asia/Taipei:20260112T100000\r\nEND:VEVENT\r\n"
        );

        $starts = array_column($events, 'starts_at');
        $this->assertSame(['2026-01-05 10:00:00', '2026-01-19 10:00:00'], $starts);
    }

    public function test_line_folding_is_unfolded(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:f1\r\nSUMMARY:很長的標題\r\n 接續部分\r\n" .
            "DTSTART;VALUE=DATE:20260110\r\nDTEND;VALUE=DATE:20260111\r\nEND:VEVENT\r\n"
        );

        $this->assertCount(1, $events);
        $this->assertSame('很長的標題接續部分', $events[0]['title']);
    }

    public function test_events_outside_range_are_dropped(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:o1\r\nSUMMARY:去年活動\r\n" .
            "DTSTART;VALUE=DATE:20251201\r\nDTEND;VALUE=DATE:20251202\r\nEND:VEVENT\r\n"
        );

        $this->assertSame([], $events);
    }

    public function test_escaped_text_is_decoded(): void
    {
        $events = $this->parse(
            "BEGIN:VEVENT\r\nUID:e1\r\nSUMMARY:研討會\\, 第一場\r\n" .
            "DTSTART;VALUE=DATE:20260120\r\nDTEND;VALUE=DATE:20260121\r\nEND:VEVENT\r\n"
        );

        $this->assertSame('研討會, 第一場', $events[0]['title']);
    }
}
