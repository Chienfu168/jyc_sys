<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Projects\CostSummary;
use PHPUnit\Framework\TestCase;

/**
 * 專案成本彙總與預算執行率的測試。
 */
final class CostSummaryTest extends TestCase
{
    private function sampleSources(): array
    {
        return [
            ['label' => '收支紀錄', 'count' => 3, 'amount' => 1000],
            ['label' => '零用金', 'count' => 2, 'amount' => 500],
            ['label' => '講師費', 'count' => 1, 'amount' => 2000],
        ];
    }

    public function test_actual_is_sum_of_sources(): void
    {
        $summary = CostSummary::build(10000.0, $this->sampleSources());
        $this->assertSame(3500.0, $summary['actual']);
    }

    public function test_remaining_is_budget_minus_actual(): void
    {
        $summary = CostSummary::build(10000.0, $this->sampleSources());
        $this->assertSame(6500.0, $summary['remaining']);
    }

    public function test_remaining_can_be_negative_when_over_budget(): void
    {
        $summary = CostSummary::build(1000.0, $this->sampleSources());
        $this->assertSame(-2500.0, $summary['remaining']);
    }

    public function test_execution_rate_is_percentage_rounded_to_two_decimals(): void
    {
        $summary = CostSummary::build(10000.0, $this->sampleSources());
        $this->assertSame(35.0, $summary['execution_rate']);

        $summary = CostSummary::build(3000.0, [['label' => 'x', 'count' => 1, 'amount' => 1000]]);
        $this->assertSame(33.33, $summary['execution_rate']);
    }

    public function test_execution_rate_is_zero_when_budget_is_zero(): void
    {
        $summary = CostSummary::build(0.0, $this->sampleSources());
        $this->assertSame(0.0, $summary['execution_rate']);
    }

    public function test_execution_rate_is_zero_when_budget_negative(): void
    {
        $summary = CostSummary::build(-5.0, $this->sampleSources());
        $this->assertSame(0.0, $summary['execution_rate']);
    }

    public function test_sources_are_normalized_and_order_preserved(): void
    {
        $summary = CostSummary::build(10000.0, [
            ['label' => 'A', 'count' => '4', 'amount' => '250.5'],
            ['label' => 'B', 'count' => 0, 'amount' => 0],
        ]);

        $this->assertSame('A', $summary['sources'][0]['label']);
        $this->assertSame(4, $summary['sources'][0]['count']);
        $this->assertSame(250.5, $summary['sources'][0]['amount']);
        $this->assertSame('B', $summary['sources'][1]['label']);
    }

    public function test_empty_sources_gives_zero_actual(): void
    {
        $summary = CostSummary::build(10000.0, []);
        $this->assertSame(0.0, $summary['actual']);
        $this->assertSame(10000.0, $summary['remaining']);
        $this->assertSame(0.0, $summary['execution_rate']);
        $this->assertSame([], $summary['sources']);
    }
}
