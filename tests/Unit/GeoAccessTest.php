<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\GeoAccess;
use PHPUnit\Framework\TestCase;

/**
 * 台灣 IP 連線管制判定邏輯的測試(純函式,不需資料庫)。
 */
final class GeoAccessTest extends TestCase
{
    public function testTaiwanIpv4IsRecognized(): void
    {
        $this->assertTrue(GeoAccess::isTaiwan('1.34.0.1'));      // HiNet
        $this->assertTrue(GeoAccess::isTaiwan('203.66.0.1'));    // Chunghwa
        $this->assertTrue(GeoAccess::isTaiwan('114.32.0.1'));
    }

    public function testForeignIpv4IsRejected(): void
    {
        $this->assertFalse(GeoAccess::isTaiwan('8.8.8.8'));      // US
        $this->assertFalse(GeoAccess::isTaiwan('1.1.1.1'));      // US
        $this->assertFalse(GeoAccess::isTaiwan('126.0.0.1'));    // JP
    }

    public function testTaiwanIpv6IsRecognized(): void
    {
        $this->assertTrue(GeoAccess::isTaiwan('2001:b000::1'));  // HiNet v6
    }

    public function testForeignIpv6IsRejected(): void
    {
        $this->assertFalse(GeoAccess::isTaiwan('2001:4860:4860::8888')); // Google v6
    }

    public function testPrivateAndReservedAreAlwaysAllowed(): void
    {
        $settings = ['enabled' => true, 'trust_proxy_header' => '', 'allow_ips' => []];
        foreach (['127.0.0.1', '192.168.1.10', '10.0.0.5', '172.16.0.1', '::1'] as $ip) {
            $decision = GeoAccess::decide($ip, $settings);
            $this->assertTrue($decision['allowed'], "private {$ip} should be allowed");
            $this->assertSame('private', $decision['reason']);
        }
    }

    public function testTaiwanIpAllowedAndForeignBlocked(): void
    {
        $settings = ['enabled' => true, 'trust_proxy_header' => '', 'allow_ips' => []];

        $tw = GeoAccess::decide('1.34.0.1', $settings);
        $this->assertTrue($tw['allowed']);
        $this->assertSame('taiwan', $tw['reason']);

        $foreign = GeoAccess::decide('8.8.8.8', $settings);
        $this->assertFalse($foreign['allowed']);
        $this->assertSame('foreign', $foreign['reason']);
    }

    public function testAllowlistSingleIpAndCidr(): void
    {
        $settings = [
            'enabled' => true,
            'trust_proxy_header' => '',
            'allow_ips' => ['203.0.113.7', '198.51.100.0/24', '2001:db8::/32'],
        ];

        $this->assertTrue(GeoAccess::decide('203.0.113.7', $settings)['allowed']);      // exact
        $this->assertTrue(GeoAccess::decide('198.51.100.42', $settings)['allowed']);    // in /24
        $this->assertFalse(GeoAccess::decide('198.51.101.42', $settings)['allowed']);   // outside /24
        $this->assertTrue(GeoAccess::decide('2001:db8:1234::9', $settings)['allowed']); // v6 CIDR
        $this->assertSame('allowlist', GeoAccess::decide('203.0.113.7', $settings)['reason']);
    }

    public function testIpInCidrBoundaries(): void
    {
        $this->assertTrue(GeoAccess::ipInCidr('10.0.0.0', '10.0.0.0/8'));
        $this->assertTrue(GeoAccess::ipInCidr('10.255.255.255', '10.0.0.0/8'));
        $this->assertFalse(GeoAccess::ipInCidr('11.0.0.0', '10.0.0.0/8'));
        $this->assertTrue(GeoAccess::ipInCidr('192.168.1.1', '192.168.1.1'));  // /32 implicit
        $this->assertFalse(GeoAccess::ipInCidr('192.168.1.2', '192.168.1.1'));
        // v4 位址不應誤判進 v6 網段
        $this->assertFalse(GeoAccess::ipInCidr('1.2.3.4', '2001:db8::/32'));
    }

    public function testNormalizeIp(): void
    {
        $this->assertSame('1.34.0.1', GeoAccess::normalizeIp('::ffff:1.34.0.1'));
        $this->assertSame('2001:b000::1', GeoAccess::normalizeIp('[2001:b000::1]:443'));
        $this->assertSame('1.34.0.1', GeoAccess::normalizeIp('1.34.0.1:8080'));
        $this->assertSame('8.8.8.8', GeoAccess::normalizeIp('  8.8.8.8  '));
    }

    public function testInvalidIpIsAllowedByFailOpen(): void
    {
        $settings = ['enabled' => true, 'trust_proxy_header' => '', 'allow_ips' => []];
        $decision = GeoAccess::decide('not-an-ip', $settings);
        $this->assertTrue($decision['allowed']);
        $this->assertSame('unknown-ip', $decision['reason']);
    }

    public function testClientIpPrefersTrustedHeaderWhenConfigured(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.1.2.3';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '1.34.0.1, 10.1.2.3';

        $this->assertSame('10.1.2.3', GeoAccess::clientIp(['trust_proxy_header' => '']));
        $this->assertSame('1.34.0.1', GeoAccess::clientIp(['trust_proxy_header' => 'x-forwarded-for']));

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }
}
