<?php

namespace App\Domain\Navigation;

use App\Core\Database;
use App\Support\NavCatalog;

/**
 * 使用者自訂常用連結的存取。表格尚未建立(migration 未跑)時全程降級為空,不影響側邊選單。
 */
final class UserQuickLinks
{
    /**
     * 取得使用者釘選、且目前仍有檢視權限的常用連結項目,依 sort_order 排序。
     *
     * @return array<int, array{perm:string, key:string, href:string, icon:string, label:string}>
     */
    public static function resolvedFor(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $keys = self::keysFor($userId);
        if ($keys === []) {
            return [];
        }

        $catalog = NavCatalog::flatByKey();
        $out = [];
        foreach ($keys as $key) {
            $item = $catalog[$key] ?? null;
            if ($item !== null && NavCatalog::canView($item)) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * 取得使用者已釘選的 nav_key(依 sort_order),即使目前無權限也回傳(供設定頁比對勾選)。
     *
     * @return array<int, string>
     */
    public static function keysFor(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $stmt = Database::pdo()->prepare(
                'SELECT nav_key FROM user_quick_links WHERE user_id = :uid ORDER BY sort_order, id'
            );
            $stmt->execute(['uid' => $userId]);
            return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * 以指定的 nav_key 順序覆寫使用者的常用連結(只接受目錄中且有權限的 key)。
     *
     * @param array<int, string> $keys
     */
    public static function replaceFor(int $userId, array $keys): void
    {
        if ($userId <= 0) {
            return;
        }

        $catalog = NavCatalog::flatByKey();
        $valid = [];
        foreach ($keys as $key) {
            $key = trim((string) $key);
            if ($key === '' || isset($valid[$key])) {
                continue;
            }
            $item = $catalog[$key] ?? null;
            if ($item !== null && NavCatalog::canView($item)) {
                $valid[$key] = true;
            }
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM user_quick_links WHERE user_id = :uid')->execute(['uid' => $userId]);
            if ($valid !== []) {
                $insert = $pdo->prepare(
                    'INSERT INTO user_quick_links (user_id, nav_key, sort_order, created_at)
                     VALUES (:uid, :nav_key, :sort_order, :created_at)'
                );
                $sort = 0;
                foreach (array_keys($valid) as $key) {
                    $insert->execute([
                        'uid' => $userId,
                        'nav_key' => $key,
                        'sort_order' => $sort++,
                        'created_at' => now(),
                    ]);
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
