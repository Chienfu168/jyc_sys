<?php

namespace App\Modules\SystemUpdate\Services;

use RuntimeException;
use ZipArchive;

final class UpdateService
{
    private array $replaceDirectories = ['app', 'config', 'database', 'docs', 'public', 'resources'];
    private array $replaceFiles = ['.env.example', '.htaccess', 'README.md', 'composer.json', 'composer.lock'];

    public function applyLatestPackage(): array
    {
        $this->extendRuntime();
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension 未啟用，無法解壓更新包');
        }

        $logService = new UpdateLogService();
        $package = $logService->latestSuccessfulDownload();
        if (!$package) {
            throw new RuntimeException('尚無可套用的已下載更新包');
        }

        $packagePath = base_path($package['package_path']);
        $this->assertInside(storage_path('updates'), $packagePath);
        if (!is_file($packagePath)) {
            throw new RuntimeException('找不到更新包：' . $package['package_path']);
        }

        $maintenance = storage_path('maintenance.lock');
        $backup = null;
        $extractDir = null;
        $sourceRoot = null;

        try {
            file_put_contents($maintenance, json_encode([
                'started_at' => date('c'),
                'version_to' => $package['version_to'],
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $backup = (new BackupService())->create();
            $extracted = $this->extractPackage($packagePath);
            $extractDir = $extracted['extract_dir'];
            $sourceRoot = $extracted['source_root'];
            $this->validatePackage($sourceRoot);
            $this->replaceApplicationFiles($sourceRoot);
            $migrations = (new MigrationService())->applyPending();
            $this->updateEnvVersion((string) $package['version_to']);

            return [
                'version_to' => $package['version_to'],
                'package_path' => $package['package_path'],
                'package_sha256' => $package['package_sha256'],
                'backup_app' => $this->relativePath($backup['app']),
                'backup_database' => $this->relativePath($backup['database']),
                'migrations' => $migrations,
            ];
        } catch (\Throwable $e) {
            if ($backup && isset($backup['app'])) {
                try {
                    (new BackupService())->restoreFiles($backup['app']);
                } catch (\Throwable $restoreError) {
                    throw new RuntimeException(
                        $e->getMessage() . '；程式備份自動還原失敗：' . $restoreError->getMessage()
                    );
                }
            }

            throw $e;
        } finally {
            if ($extractDir) {
                $this->removeDirectory($extractDir);
            }
            if (is_file($maintenance)) {
                unlink($maintenance);
            }
        }
    }

    private function extractPackage(string $packagePath): array
    {
        $target = storage_path('updates' . DIRECTORY_SEPARATOR . 'extract_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)));
        if (!mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException('無法建立更新暫存目錄');
        }

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            throw new RuntimeException('無法開啟更新包');
        }
        $zip->extractTo($target);
        $zip->close();

        $children = array_values(array_filter(glob($target . DIRECTORY_SEPARATOR . '*') ?: [], 'is_dir'));
        $sourceRoot = $children[0] ?? $target;

        return [
            'extract_dir' => $target,
            'source_root' => $sourceRoot,
        ];
    }

    private function validatePackage(string $sourceRoot): void
    {
        $required = [
            'app',
            'config',
            'database/schema.sql',
            'public/index.php',
            'resources/views',
        ];

        foreach ($required as $path) {
            if (!file_exists($sourceRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path))) {
                throw new RuntimeException('更新包缺少必要檔案：' . $path);
            }
        }
    }

    private function replaceApplicationFiles(string $sourceRoot): void
    {
        foreach ($this->replaceDirectories as $dir) {
            $source = $sourceRoot . DIRECTORY_SEPARATOR . $dir;
            $target = base_path($dir);
            if (!is_dir($source)) {
                continue;
            }

            $this->assertInside(base_path(), $target);
            if (is_dir($target)) {
                $this->removeDirectory($target);
            }
            $this->copyDirectory($source, $target);
        }

        foreach ($this->replaceFiles as $file) {
            $source = $sourceRoot . DIRECTORY_SEPARATOR . $file;
            $target = base_path($file);
            if (!is_file($source)) {
                continue;
            }

            $this->assertInside(base_path(), $target);
            copy($source, $target);
        }
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (!mkdir($target, 0755, true) && !is_dir($target)) {
            throw new RuntimeException('無法建立目錄：' . $target);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $target . DIRECTORY_SEPARATOR . substr($item->getPathname(), strlen($source) + 1);
            if ($item->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, 0755, true) && !is_dir($targetPath)) {
                    throw new RuntimeException('無法建立目錄：' . $targetPath);
                }
                continue;
            }

            $parent = dirname($targetPath);
            if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new RuntimeException('無法建立目錄：' . $parent);
            }
            copy($item->getPathname(), $targetPath);
        }
    }

    private function removeDirectory(string $directory): void
    {
        $this->assertInside(base_path(), $directory);

        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }

    private function updateEnvVersion(string $version): void
    {
        $path = base_path('.env');
        if (!is_file($path)) {
            return;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('無法讀取 .env 更新版本號');
        }

        if (preg_match('/^APP_VERSION=.*$/m', $content)) {
            $content = preg_replace('/^APP_VERSION=.*$/m', 'APP_VERSION=' . $version, $content);
        } else {
            $content .= PHP_EOL . 'APP_VERSION=' . $version . PHP_EOL;
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('無法寫入 .env 更新版本號');
        }
    }

    private function assertInside(string $base, string $path): void
    {
        $baseReal = realpath($base);
        $pathReal = realpath($path) ?: $path;

        if (!$baseReal) {
            throw new RuntimeException('基準路徑不存在：' . $base);
        }

        $baseFull = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
        $pathFull = str_replace('\\', '/', $pathReal);

        if (!str_starts_with($pathFull, $baseFull) && $pathFull !== rtrim($baseFull, '/')) {
            throw new RuntimeException('拒絕操作非系統路徑：' . $path);
        }
    }

    private function relativePath(string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen(base_path()) + 1));
    }

    private function extendRuntime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }
    }
}
