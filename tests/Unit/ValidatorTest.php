<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_required_flags_blank_fields(): void
    {
        $this->assertSame('姓名不可空白', Validator::required(['name' => '  '], ['name' => '姓名']));
        $this->assertNull(Validator::required(['name' => '王小明'], ['name' => '姓名']));
    }

    public function test_email_rejects_invalid_and_passes_valid_and_blank(): void
    {
        $this->assertSame('Email格式不正確', Validator::email(['email' => 'nope'], ['email' => 'Email']));
        $this->assertNull(Validator::email(['email' => 'a@b.com'], ['email' => 'Email']));
        $this->assertNull(Validator::email(['email' => ''], ['email' => 'Email']));
    }

    public function test_numeric(): void
    {
        $this->assertSame('金額必須是數字', Validator::numeric(['amount' => 'abc'], ['amount' => '金額']));
        $this->assertNull(Validator::numeric(['amount' => '-12.5'], ['amount' => '金額']));
        $this->assertNull(Validator::numeric(['amount' => ''], ['amount' => '金額']));
    }

    public function test_date_requires_valid_calendar_date(): void
    {
        $this->assertNull(Validator::date(['d' => '2024-02-29'], ['d' => '日期'])); // 閏年
        $this->assertSame('日期日期格式不正確', Validator::date(['d' => '2023-02-29'], ['d' => '日期']));
        $this->assertSame('日期日期格式不正確', Validator::date(['d' => '2024/01/01'], ['d' => '日期']));
        $this->assertNull(Validator::date(['d' => ''], ['d' => '日期']));
    }

    public function test_max_length_counts_multibyte_characters(): void
    {
        $this->assertNull(Validator::maxLength(['note' => '中文三字'], ['note' => '備註'], 4));
        $this->assertSame('備註長度不可超過 3 個字元', Validator::maxLength(['note' => '中文三字'], ['note' => '備註'], 3));
    }

    public function test_in_restricts_to_allowed_values(): void
    {
        $this->assertNull(Validator::in(['status' => 'active'], 'status', '狀態', ['active', 'inactive']));
        $this->assertSame('狀態選項不正確', Validator::in(['status' => 'bogus'], 'status', '狀態', ['active', 'inactive']));
        $this->assertNull(Validator::in(['status' => ''], 'status', '狀態', ['active']));
    }

    public function test_first_error_returns_earliest_non_null(): void
    {
        $this->assertNull(Validator::firstError([null, null]));
        $this->assertSame('第一個錯誤', Validator::firstError([null, '第一個錯誤', '第二個錯誤']));
    }
}
