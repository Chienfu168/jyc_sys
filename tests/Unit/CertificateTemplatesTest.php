<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Certificates\CertificateTemplates;
use PHPUnit\Framework\TestCase;

/**
 * 聘書／感謝狀範本合併邏輯的測試:確認通用變數({{name}}/{{term_no}}/
 * {{start_roc}}/{{end_roc}})正確代入,未填寫時保留可辨識的方括號佔位,
 * 且「感謝狀」範本的固定結尾正確附加。
 */
final class CertificateTemplatesTest extends TestCase
{
    public function test_board_director_standard_variant_fills_known_placeholders(): void
    {
        $body = CertificateTemplates::mergeBody('board_director', 'standard', '王小明', 3, '2026-03-20', '2030-03-19');

        $this->assertStringContainsString('茲聘請 王小明 擔任本會第3屆董事', $body);
        $this->assertStringContainsString('聘期自中華民國115 年 3 月 20 日起至119 年 3 月 19 日止。', $body);
    }

    public function test_missing_values_keep_bracket_placeholders(): void
    {
        $body = CertificateTemplates::mergeBody('board_director', 'standard', '', null, null, null);

        $this->assertStringContainsString('［姓名］', $body);
        $this->assertStringContainsString('第［屆次］屆董事', $body);
        $this->assertStringContainsString('［年］年［月］月［日］日', $body);
    }

    public function test_brief_variant_omits_term_sentence(): void
    {
        $body = CertificateTemplates::mergeBody('board_director', 'brief', '王小明', 3, '2026-03-20', '2030-03-19');

        $this->assertSame('茲聘請王小明擔任本會第3屆董事。', $body);
    }

    public function test_thank_you_appends_fixed_closing_line(): void
    {
        $body = CertificateTemplates::mergeBody('thank_you', 'volunteer', '李小華', null, null, null);

        $this->assertStringContainsString('感謝李小華於本會', $body);
        $this->assertStringEndsWith('特頒此狀，以申謝忱。', $body);
    }

    public function test_unknown_variant_falls_back_to_first_variant(): void
    {
        $body = CertificateTemplates::mergeBody('thank_you', 'no_such_variant', '李小華', null, null, null);

        $this->assertStringContainsString('感謝 李小華 協助本會辦理', $body);
    }

    public function test_template_options_include_all_four_templates(): void
    {
        $keys = array_column(CertificateTemplates::templateOptions(), 'key');

        $this->assertSame(
            ['board_director', 'lecturer', 'community_college_principal', 'thank_you'],
            $keys
        );
    }
}
