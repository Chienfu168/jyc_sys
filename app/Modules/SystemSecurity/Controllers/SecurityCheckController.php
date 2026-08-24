<?php

namespace App\Modules\SystemSecurity\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Domain\Security\SecurityAudit;

final class SecurityCheckController extends Controller
{
    /** 上傳檔允許的副檔名(與各模組上傳驗證一致)。 */
    private const ALLOWED_UPLOAD_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    /** 掃描的上傳根目錄(遞迴掃描其下所有子目錄,自動涵蓋未來新增的上傳分類)。 */
    private const UPLOAD_ROOT = 'private_uploads';

    public function index(): void
    {
        $this->requirePermission('system_updates.manage');

        $checks = SecurityAudit::evaluate($this->gatherFacts());
        $flaggedFiles = $this->scanUploads();

        AuditLog::write('security_check', 'system_security');

        $this->render('system-security.index', [
            'title' => '系統安全檢查',
            'section' => '系統設定',
            'active' => 'system-security',
            'checks' => $checks,
            'summary' => SecurityAudit::summarize($checks),
            'flaggedFiles' => $flaggedFiles,
            'scannedDirectories' => [self::UPLOAD_ROOT],
            'printable' => false,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherFacts(): array
    {
        $required = ['pdo', 'pdo_mysql', 'zip'];
        $missing = array_values(array_filter($required, static fn (string $ext): bool => !extension_loaded($ext)));

        $htaccess = @file_get_contents(base_path('.htaccess')) ?: '';
        $envPath = base_path('.env');
        $envExists = is_file($envPath);

        return [
            'php_version' => PHP_VERSION,
            'missing_extensions' => $missing,
            'app_env' => (string) config('app.env', 'production'),
            'app_debug' => (bool) config('app.debug', false),
            'installed_locked' => is_file(storage_path('installed.lock')),
            'storage_writable' => is_writable(storage_path()),
            'env_exists' => $envExists,
            'htaccess_protects_env' => str_contains($htaccess, '.env'),
            'htaccess_protects_storage' => str_contains($htaccess, 'storage'),
            'htaccess_disables_indexes' => (bool) preg_match('/Options[^\n]*-Indexes/i', $htaccess),
            'env_world_readable' => $envExists && $this->isGroupOrWorldReadable($envPath),
            'display_errors' => in_array(strtolower((string) ini_get('display_errors')), ['1', 'on', 'yes', 'true'], true),
            'session_cookie_hardened' => $this->sessionCookieHardened(),
            'app_url' => (string) config('app.url', ''),
        ];
    }

    /** .env 是否可被同群組或其他使用者讀取(僅在 POSIX 權限可判斷時有意義)。 */
    private function isGroupOrWorldReadable(string $path): bool
    {
        $perms = @fileperms($path);
        if ($perms === false) {
            return false;
        }

        // 群組可讀(0040)或其他人可讀(0004)。
        return (bool) ($perms & 0044);
    }

    /** 目前的 session cookie 是否已設定 HttpOnly 與 SameSite。 */
    private function sessionCookieHardened(): bool
    {
        $params = session_get_cookie_params();

        return !empty($params['httponly']) && !empty($params['samesite']);
    }

    /**
     * 掃描上傳目錄,回報需特別處理的危險檔。
     *
     * @return array<int, array{path: string, reason: string}>
     */
    private function scanUploads(): array
    {
        $flagged = [];
        $base = storage_path(self::UPLOAD_ROOT);
        if (!is_dir($base)) {
            return $flagged;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $reason = SecurityAudit::inspectUpload($file->getExtension(), self::ALLOWED_UPLOAD_EXTENSIONS);
            if ($reason !== null) {
                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
                $flagged[] = [
                    'path' => self::UPLOAD_ROOT . '/' . $relative,
                    'reason' => $reason,
                ];
            }
        }

        return $flagged;
    }
}
