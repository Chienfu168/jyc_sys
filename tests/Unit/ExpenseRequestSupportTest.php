<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\ExpenseRequests\ExpenseRequestSupport;
use PHPUnit\Framework\TestCase;

/**
 * 員工費用申請純邏輯輔助的測試。
 */
final class ExpenseRequestSupportTest extends TestCase
{
    public function testStatusLabels(): void
    {
        $this->assertSame('草稿', ExpenseRequestSupport::statusLabel('draft'));
        $this->assertSame('待核定', ExpenseRequestSupport::statusLabel('submitted'));
        $this->assertSame('已核定待付款', ExpenseRequestSupport::statusLabel('approved'));
        $this->assertSame('已退回', ExpenseRequestSupport::statusLabel('rejected'));
        $this->assertSame('已付款', ExpenseRequestSupport::statusLabel('paid'));
        $this->assertSame('unknown', ExpenseRequestSupport::statusLabel('unknown'));
    }

    public function testPaymentLabels(): void
    {
        $this->assertSame('匯款', ExpenseRequestSupport::paymentLabel('bank'));
        $this->assertSame('現金', ExpenseRequestSupport::paymentLabel('cash'));
        $this->assertSame('-', ExpenseRequestSupport::paymentLabel(null));
        $this->assertSame('-', ExpenseRequestSupport::paymentLabel(''));
    }

    public function testFormatNoPadsSequenceAndStripsDashes(): void
    {
        $this->assertSame('ER20260830-001', ExpenseRequestSupport::formatNo('2026-08-30', 1));
        $this->assertSame('ER20260830-042', ExpenseRequestSupport::formatNo('2026-08-30', 42));
        $this->assertSame('ER20260830-001', ExpenseRequestSupport::formatNo('2026-08-30', 0));
        // 只取日期前 10 碼,忽略時間部分。
        $this->assertSame('ER20260830-007', ExpenseRequestSupport::formatNo('2026-08-30 12:34:56', 7));
    }
}
