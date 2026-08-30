<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\NavCatalog;
use PHPUnit\Framework\TestCase;

/**
 * 側邊選單／常用連結功能目錄的測試。
 */
final class NavCatalogTest extends TestCase
{
    public function testFlatByKeyHasUniqueKeysAndKnownEntries(): void
    {
        $flat = NavCatalog::flatByKey();

        $this->assertArrayHasKey('donations', $flat);
        $this->assertArrayHasKey('petty-cash', $flat);
        $this->assertArrayHasKey('petty-cash-quick', $flat);
        $this->assertSame('/petty-cash/quick', $flat['petty-cash-quick']['href']);

        // 每筆都應有 key/href/icon/label 欄位。
        foreach ($flat as $key => $item) {
            $this->assertSame($key, $item['key']);
            $this->assertArrayHasKey('href', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('label', $item);
            $this->assertArrayHasKey('perm', $item);
        }
    }

    public function testKeysAreUniqueAcrossGroups(): void
    {
        $keys = [];
        foreach (NavCatalog::groups() as $group) {
            foreach ($group['items'] as $item) {
                $keys[] = $item['key'];
            }
        }
        $this->assertSame(array_values(array_unique($keys)), $keys, '目錄 key 不可重複');
    }

    public function testCanViewTreatsEmptyPermissionAsPublic(): void
    {
        $this->assertTrue(NavCatalog::canView(['perm' => '']));
        // 有權限碼且未登入(測試環境無 session)時應為 false。
        $this->assertFalse(NavCatalog::canView(['perm' => 'donations.view']));
    }
}
