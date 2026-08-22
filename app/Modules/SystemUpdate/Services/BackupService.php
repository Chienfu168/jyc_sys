<?php

namespace App\Modules\SystemUpdate\Services;

use App\Core\Database;
use PDO;
use RuntimeException;
use ZipArchive;

final class BackupService
{
    public function create(): array
    {
        $this->extendRuntime();
        $this->ensureBackupDirectory();
        $stamp = date('Ymd_His');

        return [
            'app' => $this->backupFiles($stamp),
            'database' => $this->backupDatabase($stamp),
        ];
    }

    public function restoreFiles(string $backupPath): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension 未啟用，無法還原程式備份');
        }

        if (!is_file($backupPath)) {
            throw new RuntimeException('找不到程式備份檔：' . $backupPath);
        }

        $zip = new ZipArchive();
        if ($zip->open($backupPath) !== true) {
            throw new RuntimeException('無法開啟程式備份檔');
        }

        $zip->extractTo(base_path());
        $zip->close();
    }

    private function backupFiles(string $stamp): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP ZipArchive extension 未啟用，無法建立程式備份');
        }

        $target = storage_path('backups' . DIRECTORY_SEPARATOR . "app_{$stamp}.zip");
        $zip = new ZipArchive();
        if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('無法建立程式備份檔');
        }

        $base = base_path();
        $items = [
            '.env',
            '.env.example',
            '.htaccess',
            'README.md',
            'composer.json',
            'composer.lock',
            'app',
            'config',
            'database',
            'docs',
            'public',
            'resources',
        ];

        foreach ($items as $item) {
            $path = $base . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $zip->addFile($path, str_replace('\\', '/', $item));
                continue;
            }

            if (is_dir($path)) {
                $this->addDirectoryToZip($zip, $path, $base);
            }
        }

        $zip->close();

        return $target;
    }

    private function backupDatabase(string $stamp): string
    {
        $target = storage_path('backups' . DIRECTORY_SEPARATOR . "database_{$stamp}.sql");
        $pdo = Database::pdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException('無法建立資料庫備份檔');
        }

        try {
            $this->writeBackup($handle, "-- Foundation system database backup\n");
            $this->writeBackup($handle, "-- Created at " . date('c') . "\n\n");
            $this->writeBackup($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach ($tables as $table) {
                $quotedTable = '`' . str_replace('`', '``', (string) $table) . '`';
                $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_ASSOC);
                $createSql = array_values($create)[1] ?? '';

                $this->writeBackup($handle, "DROP TABLE IF EXISTS {$quotedTable};\n");
                $this->writeBackup($handle, $createSql . ";\n\n");

                $rows = $pdo->query("SELECT * FROM {$quotedTable}");
                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $columns = array_map(
                        static fn (string $column) => '`' . str_replace('`', '``', $column) . '`',
                        array_keys($row)
                    );
                    $values = array_map(
                        fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value),
                        array_values($row)
                    );

                    $this->writeBackup(
                        $handle,
                        "INSERT INTO {$quotedTable} (" . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n"
                    );
                }
                $rows->closeCursor();

                $this->writeBackup($handle, "\n");
            }

            $this->writeBackup($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($handle);
        }

        return $target;
    }

    private function writeBackup($handle, string $content): void
    {
        if (fwrite($handle, $content) === false) {
            throw new RuntimeException('無法寫入資料庫備份檔');
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $base): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $relative = str_replace('\\', '/', substr($path, strlen($base) + 1));

            if ($file->isDir()) {
                $zip->addEmptyDir($relative);
                continue;
            }

            $zip->addFile($path, $relative);
        }
    }

    private function ensureBackupDirectory(): void
    {
        $dir = storage_path('backups');
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('無法建立 storage/backups 目錄');
        }
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
