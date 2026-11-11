<?php

namespace App\Domain\Certificates;

/**
 * 聘書／感謝狀範本目錄:固定部分(稱謂、用詞)寫死於此,開立時僅需挑選範本與
 * 彈性文字版本、填入姓名與(視範本而定)屆次/聘期,其餘屬特定情境的變動內容
 * (計畫名稱、學校、活動名稱等)以中文方括號佔位,維持原始範本「可直接看到
 * 待填欄位」的可讀性,交由使用者在正文框內逕行修改。
 *
 * 新增範本或彈性文字版本:編輯本檔 self::TEMPLATES 即可,不需改資料庫結構。
 */
final class CertificateTemplates
{
    /**
     * @var array<string, array{
     *     label: string,
     *     title: string,
     *     has_term: bool,
     *     needs_term_no: bool,
     *     term_hint: ?string,
     *     closing_fixed: ?string,
     *     variants: array<string, array{label: string, body: string}>
     * }>
     */
    private const TEMPLATES = [
        'board_director' => [
            'label' => '董事聘書',
            'title' => '聘書',
            'has_term' => true,
            'needs_term_no' => true,
            'term_hint' => null,
            'closing_fixed' => null,
            'variants' => [
                'standard' => [
                    'label' => '標準版',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會第{{term_no}}屆董事，參與董事會會務及重大事項之審議，共同推動本會教育公益業務。\n\n聘期自中華民國{{start_roc}}起至{{end_roc}}止。",
                ],
                'brief' => [
                    'label' => '版面需要精簡',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會第{{term_no}}屆董事。",
                ],
                'renew' => [
                    'label' => '續任',
                    'body' => "茲續聘\n\n{{name}}\n\n擔任本會第{{term_no}}屆董事。",
                ],
                'fill_term' => [
                    'label' => '補聘至本屆任期屆滿',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會第{{term_no}}屆董事，任期自中華民國{{start_roc}}起至本屆董事任期屆滿日止。",
                ],
            ],
        ],
        'lecturer' => [
            'label' => '基金會講師聘書',
            'title' => '聘書',
            'has_term' => true,
            'needs_term_no' => false,
            'term_hint' => '單次活動若已在正文寫明日期，可省略聘期欄。',
            'closing_fixed' => null,
            'variants' => [
                'standard' => [
                    'label' => '標準版',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會「［計畫名稱］」［課程名稱］講師，負責課程教學與學習引導。\n\n聘期自中華民國{{start_roc}}起至{{end_roc}}止。",
                ],
                'deep_education' => [
                    'label' => '深耕教育計畫(可加授課學校)',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會「深耕教育計畫」於［學校名稱］開設之「［課程名稱］」講師，負責課程教學與學習引導。\n\n聘期自中華民國{{start_roc}}起至{{end_roc}}止。",
                ],
                'single_event' => [
                    'label' => '單次活動(可直接列授課日期)',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會中華民國［年］年［月］月［日］日辦理之「［活動名稱］」講師，講授「［主題］」。",
                ],
                'workshop' => [
                    'label' => '教師研習或工作坊',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任本會「［研習或工作坊名稱］」講師，帶領［參與對象］進行［主題］之實作與交流。\n\n聘期自中華民國{{start_roc}}起至{{end_roc}}止。",
                ],
            ],
        ],
        'community_college_principal' => [
            'label' => '樹林社區大學校長聘書',
            'title' => '聘書',
            'has_term' => true,
            'needs_term_no' => false,
            'term_hint' => null,
            'closing_fixed' => null,
            'variants' => [
                'standard' => [
                    'label' => '標準版',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任樹林社區大學校長\n\n綜理校務，推動終身學習與社區教育。\n\n聘期自中華民國{{start_roc}}起至{{end_roc}}止。",
                ],
                'brief' => [
                    'label' => '版面需要精簡',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任樹林社區大學校長。",
                ],
                'emphasize' => [
                    'label' => '強調校務職責',
                    'body' => "茲聘請\n\n{{name}}\n\n擔任樹林社區大學校長\n\n綜理校務發展、課程規劃及社區合作，推動在地終身學習。\n\n聘期自中華民國{{start_roc}}起至{{end_roc}}止。",
                ],
                'renew' => [
                    'label' => '續聘',
                    'body' => "茲續聘\n\n{{name}}\n\n擔任樹林社區大學校長\n\n持續推動校務發展及社區教育。",
                ],
            ],
        ],
        'thank_you' => [
            'label' => '感謝狀',
            'title' => '感謝狀',
            'has_term' => false,
            'needs_term_no' => false,
            'term_hint' => null,
            'closing_fixed' => '特頒此狀，以申謝忱。',
            'variants' => [
                'standard' => [
                    'label' => '標準版',
                    'body' => "感謝\n\n{{name}}\n\n協助本會辦理「［計畫或活動名稱］」，使活動順利進行。",
                ],
                'school' => [
                    'label' => '合作學校',
                    'body' => "感謝\n\n{{name}}\n\n與本會合作推動「［計畫名稱］」，協助課程安排與校內行政聯繫，使課程順利開展。",
                ],
                'principal_admin' => [
                    'label' => '校長或學校行政團隊',
                    'body' => "感謝\n\n{{name}}\n\n支持本會「［活動名稱］」，協助［場地安排／師生聯繫／活動執行］，使活動順利進行。",
                ],
                'lecturer' => [
                    'label' => '講師',
                    'body' => "感謝\n\n{{name}}\n\n擔任本會「［課程或活動名稱］」講師，以專業教學帶領學員進行［學習主題或實作內容］。",
                ],
                'volunteer' => [
                    'label' => '志工',
                    'body' => "感謝\n\n{{name}}\n\n於本會「［活動名稱］」期間，協助［報到引導／課程陪伴／現場支援］，使活動順利進行。",
                ],
                'donor' => [
                    'label' => '捐贈者或贊助單位',
                    'body' => "感謝\n\n{{name}}\n\n捐贈［物資名稱／經費用途］，支持本會推動「［計畫名稱］」。",
                ],
                'co_organizer' => [
                    'label' => '協辦單位',
                    'body' => "感謝\n\n{{name}}\n\n協辦本會「［活動名稱］」，協助［具體合作事項］，共同推動［教育主題］。",
                ],
            ],
        ],
    ];

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return self::TEMPLATES;
    }

    public static function exists(string $templateKey): bool
    {
        return isset(self::TEMPLATES[$templateKey]);
    }

    /** @return array<string, mixed> */
    public static function get(string $templateKey): array
    {
        return self::TEMPLATES[$templateKey] ?? self::TEMPLATES['thank_you'];
    }

    public static function variantExists(string $templateKey, string $variantKey): bool
    {
        return isset(self::TEMPLATES[$templateKey]['variants'][$variantKey]);
    }

    /** 供下拉選單使用的範本清單。 @return array<int, array{key: string, label: string}> */
    public static function templateOptions(): array
    {
        $options = [];
        foreach (self::TEMPLATES as $key => $tpl) {
            $options[] = ['key' => $key, 'label' => $tpl['label']];
        }
        return $options;
    }

    /**
     * 將範本的彈性文字版本代入變數,產生最終正文(仍可能含待填的中文方括號,
     * 供使用者於正文框內手動修改)。{{name}}/{{term_no}}/{{start_roc}}/
     * {{end_roc}} 四個通用變數會自動代入;未填寫時保留可辨識的方括號佔位,
     * 避免輸出半成品卻讓人誤以為已完稿。
     */
    public static function mergeBody(
        string $templateKey,
        string $variantKey,
        string $name,
        ?int $termNo,
        ?string $startDate,
        ?string $endDate
    ): string {
        $tpl = self::get($templateKey);
        $variant = $tpl['variants'][$variantKey] ?? reset($tpl['variants']);

        $replacements = [
            '{{name}}' => $name !== '' ? $name : '［姓名］',
            '{{term_no}}' => $termNo !== null ? (string) $termNo : '［屆次］',
            '{{start_roc}}' => $startDate ? self::rocPlain($startDate) : '［年］年［月］月［日］日',
            '{{end_roc}}' => $endDate ? self::rocPlain($endDate) : '［年］年［月］月［日］日',
        ];

        $body = strtr($variant['body'], $replacements);

        if ($tpl['closing_fixed']) {
            $body .= "\n\n" . $tpl['closing_fixed'];
        }

        return $body;
    }

    /** 中華民國日期(不含「民國」字樣,供接在「中華民國」固定字樣之後)。 */
    private static function rocPlain(string $date): string
    {
        return (string) str_replace('民國 ', '', roc_date($date));
    }
}
