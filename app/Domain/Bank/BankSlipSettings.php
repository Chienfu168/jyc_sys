<?php

namespace App\Domain\Bank;

use App\Support\BankSlipCatalog;

/**
 * 各銀行匯款單的專屬設定(依 bank_code 分開儲存),存於 storage/bank_slip_settings.json。
 *
 * 每家銀行可設定:是否啟用、預設取款帳戶(基金會既有銀行帳戶)、匯款人統編/電話預設值。
 * 採 JSON 檔儲存,不需額外資料表,並天然支援未來擴充其他銀行。
 */
final class BankSlipSettings
{
    private const FILE = 'bank_slip_settings.json';

    /** 單一銀行設定的預設值。 */
    public static function blank(): array
    {
        return [
            'enabled' => true,
            'withdrawal_bank_account_id' => null,
            'remitter_name' => '',
            'remitter_id_no' => '',
            'remitter_phone' => '',
            'default_remittance_type' => '一般跨行匯款(11)',
        ];
    }

    /** 讀取全部設定(依 bank_code)。 */
    public static function all(): array
    {
        $path = storage_path(self::FILE);
        if (!is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** 取得單一銀行設定(套用預設值)。 */
    public static function forBank(string $bankCode): array
    {
        $all = self::all();
        $settings = is_array($all[$bankCode] ?? null) ? $all[$bankCode] : [];
        return $settings + self::blank();
    }

    /** 該銀行是否可製單(目錄啟用且設定啟用)。 */
    public static function isUsable(string $bankCode): bool
    {
        $bank = BankSlipCatalog::get($bankCode);
        if ($bank === null || !$bank['enabled']) {
            return false;
        }
        return (bool) self::forBank($bankCode)['enabled'];
    }

    /** 儲存單一銀行設定,回傳是否成功。 */
    public static function save(string $bankCode, array $settings): bool
    {
        $all = self::all();
        $all[$bankCode] = array_intersect_key($settings + self::blank(), self::blank());

        $path = storage_path(self::FILE);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $json = json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        return file_put_contents($path, $json, LOCK_EX) !== false;
    }
}
