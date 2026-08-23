<?php

namespace App\Modules\SystemSecurity\Controllers;

use App\Core\AuditLog;
use App\Core\Controller;
use App\Domain\Security\SecurityAudit;

final class SecurityCheckController extends Controller
{
    /** 上傳檔允許的副檔名(與各模組上傳驗證一致)。 */
    private const ALLOWED_UPLOAD_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

    /** 掃描的上傳目錄。 */
    private const UPLOAD_DIRECTORIES = ['private_uploads/purchase_requests', 'private_uploads/activities'];

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
            'scannedDirectories' => self::UPLOAD_DIRECTORIES,
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

        return [
            'php_version' => PHP_VERSION,
            'missing_extensions' => $missing,
            'app_env' => (string) config('app.env', 'production'),
            'app_debug' => (bool) config('app.debug', false),
            'installed_locked' => is_file(storage_path('installed.lock')),
            'storage_writable' => is_writable(storage_path()),
            'env_exists' => is_file(base_path('.env')),
            'htaccess_protects_env' => str_contains($htaccess, '.env'),
            'htaccess_protects_storage' => str_contains($htaccess, 'storage'),
            'app_url' => (string) config('app.url', ''),
        ];
    }

    /**
     * 掃描上傳目錄,回報需特別處理的危險檔。
     *
     * @return array<int, array{path: string, reason: string}>
     */
    private function scanUploads(): array
    {
        $flagged = [];

        foreach (self::UPLOAD_DIRECTORIES as $relative) {
            $base = storage_path($relative);
            if (!is_dir($base)) {
                continue;
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
                    $flagged[] = [
                        'path' => $relative . '/' . $file->getFilename(),
                        'reason' => $reason,
                    ];
                }
            }
        }

        return $flagged;
    }
}
