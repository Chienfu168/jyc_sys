<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * 各模組列印文件簽核鏈的測試(僅驗證職務標籤,不依賴資料庫姓名)。
 */
final class SignatureChainTest extends TestCase
{
    /** @return array<int, string> */
    private function labels(?string $context): array
    {
        return array_map(
            static fn (array $role): string => $role['label'],
            signature_chain($context)
        );
    }

    public function test_leave_request_flow(): void
    {
        $this->assertSame(['申請人', '人事主管', '執行長', '董事長'], $this->labels('leave-requests'));
    }

    public function test_purchase_goes_through_general_affairs(): void
    {
        $this->assertContains('總務', $this->labels('purchase-requests'));
    }

    public function test_finance_goes_through_accountant(): void
    {
        foreach (['accounting', 'income-expenses', 'petty-cash', 'payroll', 'travel-expenses'] as $context) {
            $this->assertContains('會計', $this->labels($context), $context . ' 應有會計簽核');
        }
    }

    public function test_hr_goes_through_hr_manager(): void
    {
        $this->assertContains('人事主管', $this->labels('personnel'));
    }

    public function test_business_goes_through_unit_supervisor(): void
    {
        $this->assertContains('單位主管', $this->labels('activities'));
    }

    public function test_chain_always_ends_with_chair(): void
    {
        foreach (['leave-requests', 'accounting', 'purchase-requests', 'donations', null] as $context) {
            $labels = $this->labels($context);
            $this->assertSame('董事長', end($labels), 'chain 結尾應為董事長');
            $this->assertCount(4, $labels);
        }
    }

    public function test_unknown_context_falls_back_to_default(): void
    {
        $this->assertSame(['承辦', '會計人員', '執行長', '董事長'], $this->labels('something-unknown'));
    }
}
