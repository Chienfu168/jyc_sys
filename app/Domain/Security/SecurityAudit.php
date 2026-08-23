<?php

namespace App\Domain\Security;

/**
 * 系統安全檢查的純評估邏輯(不依賴資料庫或檔案系統)。
 *
 * Controller 蒐集環境事實(PHP 版本、擴充、設定、檔案保護狀態等)傳入,
 * 本類別套用規則產出檢查結果;上傳檔案則以副檔名判斷是否為需特別處理
 * 的危險檔。設計刻意精簡:每項只回報 通過 / 注意 / 危險 與一句處置建議。
 */
final class SecurityAudit
{
    public const PASS = 'pass';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /** 上傳目錄不應出現的可執行 / 腳本類副檔名。 */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phar', 'pht',
        'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'cgi', 'pl',
        'js', 'mjs', 'html', 'htm', 'svg', 'htaccess',
    ];

    /**
     * 依環境事實評估各項安全檢查。
     *
     * @param array{
     *     php_version?: string,
     *     missing_extensions?: array<int, string>,
     *     app_env?: string,
     *     app_debug?: bool,
     *     installed_locked?: bool,
     *     storage_writable?: bool,
     *     env_exists?: bool,
     *     htaccess_protects_env?: bool,
     *     htaccess_protects_storage?: bool,
     *     app_url?: string
     * } $facts
     * @return array<int, array{key: string, label: string, status: string, detail: string, recommendation: string}>
     */
    public static function evaluate(array $facts): array
    {
        $isProduction = ($facts['app_env'] ?? 'production') === 'production';
        $checks = [];

        // PHP 版本
        $phpVersion = (string) ($facts['php_version'] ?? PHP_VERSION);
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');
        $checks[] = [
            'key' => 'php_version',
            'label' => 'PHP 版本',
            'status' => $phpOk ? self::PASS : self::FAIL,
            'detail' => '目前版本 ' . $phpVersion,
            'recommendation' => $phpOk ? '' : '請升級至 PHP 8.2 以上版本。',
        ];

        // 必要擴充套件
        $missing = array_values($facts['missing_extensions'] ?? []);
        $checks[] = [
            'key' => 'extensions',
            'label' => '必要擴充套件',
            'status' => $missing === [] ? self::PASS : self::FAIL,
            'detail' => $missing === [] ? 'pdo、pdo_mysql、zip 皆已啟用' : '缺少:' . implode('、', $missing),
            'recommendation' => $missing === [] ? '' : '請於主機啟用上述 PHP 擴充套件。',
        ];

        // 正式環境除錯模式
        $debugInProd = $isProduction && ($facts['app_debug'] ?? false);
        $checks[] = [
            'key' => 'debug',
            'label' => '正式環境除錯模式',
            'status' => $debugInProd ? self::FAIL : self::PASS,
            'detail' => $debugInProd ? '正式環境開啟了 APP_DEBUG,錯誤訊息可能外洩' : '除錯模式已關閉',
            'recommendation' => $debugInProd ? '請將 .env 的 APP_DEBUG 設為 false。' : '',
        ];

        // 安裝鎖定
        $installed = (bool) ($facts['installed_locked'] ?? false);
        $checks[] = [
            'key' => 'installed_lock',
            'label' => '安裝程序鎖定',
            'status' => $installed ? self::PASS : self::WARN,
            'detail' => $installed ? '已建立 storage/installed.lock' : '找不到安裝鎖定檔',
            'recommendation' => $installed ? '' : '請確認安裝已完成,避免 install.php 被重複執行。',
        ];

        // storage 可寫
        $storageWritable = (bool) ($facts['storage_writable'] ?? false);
        $checks[] = [
            'key' => 'storage_writable',
            'label' => 'storage 目錄可寫',
            'status' => $storageWritable ? self::PASS : self::FAIL,
            'detail' => $storageWritable ? 'storage 目錄可正常寫入' : 'storage 目錄無法寫入',
            'recommendation' => $storageWritable ? '' : '請調整 storage 目錄權限(例如 775)。',
        ];

        // .env 保護
        $envExists = (bool) ($facts['env_exists'] ?? false);
        $envProtected = !$envExists || (bool) ($facts['htaccess_protects_env'] ?? false);
        $checks[] = [
            'key' => 'env_protected',
            'label' => '.env 檔案保護',
            'status' => $envProtected ? self::PASS : self::FAIL,
            'detail' => $envProtected ? '.env 未直接對外開放' : '.env 可能可被瀏覽器存取',
            'recommendation' => $envProtected ? '' : '請確認 .htaccess 已封鎖 .env,或將網站根目錄指向 public。',
        ];

        // 上傳 / 系統目錄保護
        $storageProtected = (bool) ($facts['htaccess_protects_storage'] ?? false);
        $checks[] = [
            'key' => 'storage_protected',
            'label' => '系統與上傳目錄保護',
            'status' => $storageProtected ? self::PASS : self::WARN,
            'detail' => $storageProtected ? 'storage 等目錄已封鎖直接存取' : '未偵測到目錄封鎖規則',
            'recommendation' => $storageProtected ? '' : '請確認 .htaccess 已封鎖 storage 等目錄,或網站根目錄指向 public。',
        ];

        // HTTPS
        $https = str_starts_with(strtolower((string) ($facts['app_url'] ?? '')), 'https://');
        $checks[] = [
            'key' => 'https',
            'label' => 'HTTPS 連線',
            'status' => $https ? self::PASS : ($isProduction ? self::WARN : self::PASS),
            'detail' => $https ? 'APP_URL 使用 HTTPS' : 'APP_URL 未使用 HTTPS',
            'recommendation' => $https ? '' : '正式環境建議啟用 HTTPS,並將 APP_URL 設為 https 網址。',
        ];

        return $checks;
    }

    /**
     * 檢查單一上傳檔是否為需特別處理的危險檔;安全則回傳 null,否則回傳問題說明。
     *
     * @param array<int, string> $allowedExtensions
     */
    public static function inspectUpload(string $extension, array $allowedExtensions): ?string
    {
        $extension = strtolower(ltrim($extension, '.'));

        if ($extension === '') {
            return '檔案沒有副檔名,無法辨識類型';
        }

        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return '潛在危險檔案(可執行或腳本類型 .' . $extension . ')';
        }

        $allowed = array_map(static fn (string $e): string => strtolower(ltrim($e, '.')), $allowedExtensions);
        if ($allowed !== [] && !in_array($extension, $allowed, true)) {
            return '副檔名 .' . $extension . ' 不在允許清單內';
        }

        return null;
    }

    /**
     * 統計檢查結果中各狀態的數量。
     *
     * @param array<int, array{status?: string}> $checks
     * @return array{pass: int, warn: int, fail: int}
     */
    public static function summarize(array $checks): array
    {
        $summary = [self::PASS => 0, self::WARN => 0, self::FAIL => 0];
        foreach ($checks as $check) {
            $status = $check['status'] ?? self::PASS;
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }

        return $summary;
    }
}
