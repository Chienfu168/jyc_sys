<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Donations\AmountInWords;
use PHPUnit\Framework\TestCase;

/**
 * 收據國字大寫分位格式的測試。
 */
final class AmountInWordsTest extends TestCase
{
    public function test_six_million_matches_foundation_receipt(): void
    {
        // 基金會實體收據:6,000,000 → 零仟陸佰零拾零萬零仟零佰零拾零元。
        $this->assertSame('零仟陸佰零拾零萬零仟零佰零拾零元', AmountInWords::text(6000000));
    }

    public function test_pads_to_eight_positions_for_small_amounts(): void
    {
        $grid = AmountInWords::grid(1234);
        $this->assertCount(8, $grid);
        // 高位補零,低四位為 壹仟 貳佰 參拾 肆元。
        $this->assertSame('壹', $grid[4]['digit']);
        $this->assertSame('仟', $grid[4]['unit']);
        $this->assertSame('肆', $grid[7]['digit']);
        $this->assertSame('元', $grid[7]['unit']);
    }

    public function test_units_follow_fixed_positions(): void
    {
        // 由高位到低位單位固定:仟 佰 拾 萬 仟 佰 拾 元。
        $units = array_column(AmountInWords::grid(0), 'unit');
        $this->assertSame(['仟', '佰', '拾', '萬', '仟', '佰', '拾', '元'], $units);
    }

    public function test_zero_is_all_zero_digits(): void
    {
        $this->assertSame('零仟零佰零拾零萬零仟零佰零拾零元', AmountInWords::text(0));
    }

    public function test_extends_beyond_ten_million_into_yi(): void
    {
        // 1 億(100,000,000)需 9 位,最高位單位為 億。
        $grid = AmountInWords::grid(100000000);
        $this->assertCount(9, $grid);
        $this->assertSame('壹', $grid[0]['digit']);
        $this->assertSame('億', $grid[0]['unit']);
    }

    public function test_full_number_maps_each_digit(): void
    {
        // 12,345,678 → 壹仟貳佰參拾肆萬伍仟陸佰柒拾捌元。
        $this->assertSame('壹仟貳佰參拾肆萬伍仟陸佰柒拾捌元', AmountInWords::text(12345678));
    }
}
