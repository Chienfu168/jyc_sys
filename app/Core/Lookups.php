<?php

namespace App\Core;

/**
 * 集中管理跨模組共用的參考資料查詢(下拉選單來源等)。
 *
 * 這些清單在多個 Controller 中原本以完全相同的 SQL 重複實作。集中於此可
 * 移除重複、統一維護,並以單次請求的靜態快取避免同一請求內重複查詢。
 * 每個方法都以 try/catch 保護,資料表尚未建立時回傳空陣列,維持原本行為。
 */
final class Lookups
{
    /** @var array<string, array> */
    private static array $cache = [];

    /**
     * 啟用中的銀行帳戶,依銀行名稱與帳號排序。
     */
    public static function activeBankAccounts(): array
    {
        return self::remember('active_bank_accounts', function (): array {
            return Database::pdo()
                ->query('SELECT * FROM bank_accounts WHERE status = "active" ORDER BY bank_name, account_no')
                ->fetchAll();
        });
    }

    /**
     * 啟用中的使用者(id / name),依姓名排序。
     */
    public static function activeUsers(): array
    {
        return self::remember('active_users', function (): array {
            return Database::pdo()
                ->query('SELECT id, name FROM users WHERE status = "active" ORDER BY name')
                ->fetchAll();
        });
    }

    /**
     * 清除快取(測試或長生命週期程序使用)。
     */
    public static function flush(): void
    {
        self::$cache = [];
    }

    /**
     * @param callable():array $resolver
     */
    private static function remember(string $key, callable $resolver): array
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            return self::$cache[$key] = $resolver();
        } catch (\Throwable) {
            return self::$cache[$key] = [];
        }
    }
}
