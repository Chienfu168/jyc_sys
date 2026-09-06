<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Bank\TwCurrency;
use PHPUnit\Framework\TestCase;

/**
 * 台幣金額國字大寫與匯款單數字框拆位的測試。
 */
final class TwCurrencyTest extends TestCase
{
    public function test_zero_is_zero_yuan(): void
    {
        $this->assertSame('零元整', TwCurrency::uppercase(0));
    }

    public function test_basic_amounts(): void
    {
        $this->assertSame('柒元整', TwCurrency::uppercase(7));
        $this->assertSame('陸萬元整', TwCurrency::uppercase(60000));
        $this->assertSame('肆萬玖仟貳佰元整', TwCurrency::uppercase(49200));
    }

    public function test_internal_zero_is_inserted_once(): void
    {
        $this->assertSame('壹佰零伍元整', TwCurrency::uppercase(105));
        $this->assertSame('壹仟零伍元整', TwCurrency::uppercase(1005));
        $this->assertSame('壹萬零伍元整', TwCurrency::uppercase(10005));
    }

    public function test_ten_thousand_uses_full_form(): void
    {
        // 台灣銀行慣例:拾萬而非十萬
        $this->assertSame('壹拾萬元整', TwCurrency::uppercase(100000));
        $this->assertSame('貳拾萬元整', TwCurrency::uppercase(200000));
    }

    public function test_cross_section_zero(): void
    {
        $this->assertSame('壹億元整', TwCurrency::uppercase(100000000));
        $this->assertSame('壹億零伍萬元整', TwCurrency::uppercase(100050000));
        $this->assertSame('壹億貳仟參佰肆拾伍萬陸仟柒佰捌拾玖元整', TwCurrency::uppercase(123456789));
    }

    public function test_payroll_like_amount(): void
    {
        $this->assertSame('伍萬伍仟玖佰壹拾捌元整', TwCurrency::uppercase(55918));
    }

    public function test_digit_columns_right_aligned(): void
    {
        // 334,775 → 拾萬3 萬3 仟4 佰7 拾7 元5,高位空白
        $this->assertSame(
            ['', '', '', '', '3', '3', '4', '7', '7', '5'],
            TwCurrency::digitColumns(334775)
        );
    }

    public function test_digit_columns_zero(): void
    {
        $this->assertSame(['', '', '', '', '', '', '', '', '', '0'], TwCurrency::digitColumns(0));
    }

    public function test_column_labels_are_ten(): void
    {
        $this->assertCount(10, TwCurrency::COLUMN_LABELS);
    }
}
