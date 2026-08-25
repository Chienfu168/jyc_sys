<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Donations\AmountInWords;
use PHPUnit\Framework\TestCase;

/**
 * 收據國字大寫金額的測試。
 */
final class AmountInWordsTest extends TestCase
{
    public function test_three_million(): void
    {
        $this->assertSame('參佰萬', AmountInWords::formal(3000000));
    }

    public function test_leading_zero_in_group_gets_zero(): void
    {
        // 1,050,000 → 壹佰零伍萬(萬級 105,個級 0)。
        $this->assertSame('壹佰零伍萬', AmountInWords::formal(1050000));
    }

    public function test_zero_between_groups(): void
    {
        // 10,001 → 壹萬零壹(跨組零)。
        $this->assertSame('壹萬零壹', AmountInWords::formal(10001));
    }

    public function test_internal_zero_within_group(): void
    {
        // 1,005 → 壹仟零伍(組內零)。
        $this->assertSame('壹仟零伍', AmountInWords::formal(1005));
    }

    public function test_full_number_maps_each_digit(): void
    {
        $this->assertSame('壹仟貳佰參拾肆萬伍仟陸佰柒拾捌', AmountInWords::formal(12345678));
    }

    public function test_hundred_million(): void
    {
        $this->assertSame('壹億', AmountInWords::formal(100000000));
    }

    public function test_hundred_million_with_gap(): void
    {
        // 100,050,000 → 壹億零伍萬。
        $this->assertSame('壹億零伍萬', AmountInWords::formal(100050000));
    }

    public function test_zero_and_negative(): void
    {
        $this->assertSame('零', AmountInWords::formal(0));
        $this->assertSame('零', AmountInWords::formal(-100));
    }

    public function test_text_appends_yuan(): void
    {
        $this->assertSame('參佰萬元整', AmountInWords::text(3000000));
        $this->assertSame('零元整', AmountInWords::text(0));
    }
}
