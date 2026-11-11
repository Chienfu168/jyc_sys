<?php

namespace App\Domain\Certificates;

/**
 * 聘書／感謝狀外框樣式目錄:開立時可挑選不同外觀,預設為既有的
 * 正式金色雙框樣式,其餘樣式僅調整外框與角飾,不影響正文內容。
 */
final class CertificateFrameStyles
{
    private const STYLES = [
        'classic' => '正式(金色雙框)',
        'award' => '獎狀(酒紅實框)',
        'simple' => '簡約(細框)',
    ];

    public static function exists(string $key): bool
    {
        return isset(self::STYLES[$key]);
    }

    public static function normalize(string $key): string
    {
        return self::exists($key) ? $key : 'classic';
    }

    /** @return array<int, array{key: string, label: string}> */
    public static function options(): array
    {
        $options = [];
        foreach (self::STYLES as $key => $label) {
            $options[] = ['key' => $key, 'label' => $label];
        }
        return $options;
    }
}
