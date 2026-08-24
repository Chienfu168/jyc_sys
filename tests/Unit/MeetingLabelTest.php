<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\BoardMeetings\MeetingLabel;
use PHPUnit\Framework\TestCase;

/**
 * 董事會議顯示文字格式的測試。
 */
final class MeetingLabelTest extends TestCase
{
    public function test_session_title_matches_reference_format(): void
    {
        $this->assertSame('第2屆第4次董事會', MeetingLabel::sessionTitle(2, 4));
    }

    public function test_full_title_prefixes_foundation_name(): void
    {
        $this->assertSame(
            '財團法人新北市○○教育基金會第2屆第4次董事會',
            MeetingLabel::fullTitle('財團法人新北市○○教育基金會', 2, 4)
        );
    }

    public function test_status_labels(): void
    {
        $this->assertSame('已確認紀錄', MeetingLabel::statusLabel('confirmed'));
        $this->assertSame('草稿(議程)', MeetingLabel::statusLabel('draft'));
        $this->assertSame('草稿(議程)', MeetingLabel::statusLabel('unknown'));
    }

    public function test_role_labels(): void
    {
        $this->assertSame('列席', MeetingLabel::roleLabel('observer'));
        $this->assertSame('出席', MeetingLabel::roleLabel('director'));
    }

    public function test_attendance_status_labels(): void
    {
        $this->assertSame('請假', MeetingLabel::attendanceStatusLabel('leave'));
        $this->assertSame('委託出席', MeetingLabel::attendanceStatusLabel('proxy'));
        $this->assertSame('出席', MeetingLabel::attendanceStatusLabel('present'));
    }
}
