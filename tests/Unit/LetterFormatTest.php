<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\OfficialLetters\LetterFormat;
use PHPUnit\Framework\TestCase;

final class LetterFormatTest extends TestCase
{
    public function test_ordinal_converts_single_digits(): void
    {
        $this->assertSame('一', LetterFormat::ordinal(1));
        $this->assertSame('九', LetterFormat::ordinal(9));
    }

    public function test_ordinal_converts_tens(): void
    {
        $this->assertSame('十', LetterFormat::ordinal(10));
        $this->assertSame('十一', LetterFormat::ordinal(11));
        $this->assertSame('二十', LetterFormat::ordinal(20));
        $this->assertSame('二十一', LetterFormat::ordinal(21));
        $this->assertSame('九十九', LetterFormat::ordinal(99));
    }

    public function test_ordinal_falls_back_to_arabic_numeral_out_of_range(): void
    {
        $this->assertSame('0', LetterFormat::ordinal(0));
        $this->assertSame('100', LetterFormat::ordinal(100));
    }

    public function test_lines_splits_and_trims_non_empty_lines(): void
    {
        $text = "依據「財團法人法」辦理。\n\n  本會年度工作計畫業經董事會議審定通過。  \n";
        $this->assertSame([
            '依據「財團法人法」辦理。',
            '本會年度工作計畫業經董事會議審定通過。',
        ], LetterFormat::lines($text));
    }

    public function test_lines_of_empty_text_is_empty_array(): void
    {
        $this->assertSame([], LetterFormat::lines(null));
        $this->assertSame([], LetterFormat::lines("  \n  "));
    }
}
