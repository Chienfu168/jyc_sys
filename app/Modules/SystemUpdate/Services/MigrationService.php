<?php

namespace App\Modules\SystemUpdate\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class MigrationService
{
    public function applyPending(): array
    {
        $this->ensureTable();

        $dir = base_path('database/migrations');
        if (!is_dir($dir)) {
            return [];
        }

        $applied = $this->applied();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files);

        $executed = [];
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $this->executeSqlFile($file);
            Database::pdo()->prepare('INSERT INTO schema_migrations (migration, applied_at) VALUES (:migration, :applied_at)')
                ->execute(['migration' => $name, 'applied_at' => now()]);
            $executed[] = $name;
        }

        return $executed;
    }

    private function ensureTable(): void
    {
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function applied(): array
    {
        $stmt = Database::pdo()->query('SELECT migration FROM schema_migrations');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function executeSqlFile(string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('無法讀取 migration：' . basename($path));
        }

        $sql = preg_replace('/CREATE\s+DATABASE\b.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/USE\s+[`"]?[A-Za-z0-9_-]+[`"]?\s*;\s*/is', '', $sql) ?? $sql;

        foreach ($this->splitSql($sql) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                Database::pdo()->exec($statement);
            }
        }
    }

    private function splitSql(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $sql[$i + 1] ?? '';

            if ($quote === null && $char === '-' && $next === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($quote === null && $char === '#') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($quote === null && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length && !($sql[$i] === '*' && ($sql[$i + 1] ?? '') === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i - 1] !== '\\')) {
                $quote = $quote === $char ? null : ($quote ?? $char);
            }

            if ($char === ';' && $quote === null) {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }
}
