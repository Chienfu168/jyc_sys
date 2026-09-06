<?php

namespace App\Support;

/**
 * 支援匯款單(取款憑條)列印的銀行目錄。
 *
 * 目前僅彰化銀行。未來擴充其他銀行時,於此新增一筆(含專屬列印版面 view),
 * 匯款單模組即會自動於選單與設定頁列出。
 */
final class BankSlipCatalog
{
    /**
     * @return array<string, array{name:string, code:string, layout:string, enabled:bool}>
     *   key 為 bank_code;layout 為列印版面 view 名稱;enabled=false 者列於設定但暫不可製單。
     */
    public static function all(): array
    {
        return [
            'chb' => [
                'name' => '彰化商業銀行',
                'code' => '009',              // 銀行代號(僅供顯示)
                'layout' => 'bank-slips.layouts.chb',
                'enabled' => true,
            ],
        ];
    }

    /** @return array<string, array{name:string, code:string, layout:string, enabled:bool}> */
    public static function enabled(): array
    {
        return array_filter(self::all(), static fn (array $bank): bool => $bank['enabled']);
    }

    public static function has(string $bankCode): bool
    {
        return isset(self::all()[$bankCode]);
    }

    /** @return array{name:string, code:string, layout:string, enabled:bool}|null */
    public static function get(string $bankCode): ?array
    {
        return self::all()[$bankCode] ?? null;
    }

    public static function name(string $bankCode): string
    {
        return self::all()[$bankCode]['name'] ?? $bankCode;
    }

    /** 預設(第一個啟用的)銀行代碼,供新增匯款單預選。 */
    public static function defaultCode(): string
    {
        $enabled = self::enabled();
        return array_key_first($enabled) ?? 'chb';
    }
}
