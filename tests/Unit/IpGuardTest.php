<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\IpGuard;
use PHPUnit\Framework\TestCase;

/**
 * 自動封鎖累犯 IP 的豁免判定(純函式,不需資料庫)。
 * 內網／回送位址與不合法輸入一律豁免,避免誤鎖自己人。
 */
final class IpGuardTest extends TestCase
{
    public function testInvalidOrEmptyIpIsExempt(): void
    {
        $this->assertTrue(IpGuard::isExempt(''));
        $this->assertTrue(IpGuard::isExempt('not-an-ip'));
        $this->assertTrue(IpGuard::isExempt('999.999.999.999'));
    }

    public function testPrivateAndLoopbackAreExempt(): void
    {
        $this->assertTrue(IpGuard::isExempt('127.0.0.1'));
        $this->assertTrue(IpGuard::isExempt('192.168.1.10'));
        $this->assertTrue(IpGuard::isExempt('10.0.0.5'));
        $this->assertTrue(IpGuard::isExempt('::1'));
    }

    public function testPublicIpIsNotExemptByDefault(): void
    {
        // 未在允許清單內的公網 IP 不豁免,才可被封鎖。
        $this->assertFalse(IpGuard::isExempt('8.8.8.8'));
        $this->assertFalse(IpGuard::isExempt('203.0.113.9'));
    }
}
