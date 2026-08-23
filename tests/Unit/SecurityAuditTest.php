<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Security\SecurityAudit;
use PHPUnit\Framework\TestCase;

/**
 * 系統安全檢查評估邏輯的測試。
 */
final class SecurityAuditTest extends TestCase
{
    private function statusOf(array $checks, string $key): string
    {
        foreach ($checks as $check) {
            if ($check['key'] === $key) {
                return $check['status'];
            }
        }
        $this->fail("check {$key} not found");
    }

    private function healthyFacts(): array
    {
        return [
            'php_version' => '8.3.0',
            'missing_extensions' => [],
            'app_env' => 'production',
            'app_debug' => false,
            'installed_locked' => true,
            'storage_writable' => true,
            'env_exists' => true,
            'htaccess_protects_env' => true,
            'htaccess_protects_storage' => true,
            'app_url' => 'https://sys.example.org',
        ];
    }

    public function test_healthy_environment_passes_all(): void
    {
        $checks = SecurityAudit::evaluate($this->healthyFacts());
        $summary = SecurityAudit::summarize($checks);

        $this->assertSame(0, $summary['warn']);
        $this->assertSame(0, $summary['fail']);
        $this->assertGreaterThan(0, $summary['pass']);
    }

    public function test_old_php_version_fails(): void
    {
        $facts = ['php_version' => '8.1.0'] + $this->healthyFacts();
        $this->assertSame(SecurityAudit::FAIL, $this->statusOf(SecurityAudit::evaluate($facts), 'php_version'));
    }

    public function test_missing_extensions_fail(): void
    {
        $facts = $this->healthyFacts();
        $facts['missing_extensions'] = ['zip'];
        $this->assertSame(SecurityAudit::FAIL, $this->statusOf(SecurityAudit::evaluate($facts), 'extensions'));
    }

    public function test_debug_in_production_fails_but_ok_in_local(): void
    {
        $prod = $this->healthyFacts();
        $prod['app_debug'] = true;
        $this->assertSame(SecurityAudit::FAIL, $this->statusOf(SecurityAudit::evaluate($prod), 'debug'));

        $local = $prod;
        $local['app_env'] = 'local';
        $this->assertSame(SecurityAudit::PASS, $this->statusOf(SecurityAudit::evaluate($local), 'debug'));
    }

    public function test_unprotected_env_fails(): void
    {
        $facts = $this->healthyFacts();
        $facts['htaccess_protects_env'] = false;
        $this->assertSame(SecurityAudit::FAIL, $this->statusOf(SecurityAudit::evaluate($facts), 'env_protected'));
    }

    public function test_absent_env_is_not_flagged(): void
    {
        // 沒有 .env 檔就沒有外洩風險,不應判為危險。
        $facts = $this->healthyFacts();
        $facts['env_exists'] = false;
        $facts['htaccess_protects_env'] = false;
        $this->assertSame(SecurityAudit::PASS, $this->statusOf(SecurityAudit::evaluate($facts), 'env_protected'));
    }

    public function test_missing_installed_lock_warns(): void
    {
        $facts = $this->healthyFacts();
        $facts['installed_locked'] = false;
        $this->assertSame(SecurityAudit::WARN, $this->statusOf(SecurityAudit::evaluate($facts), 'installed_lock'));
    }

    public function test_non_writable_storage_fails(): void
    {
        $facts = $this->healthyFacts();
        $facts['storage_writable'] = false;
        $this->assertSame(SecurityAudit::FAIL, $this->statusOf(SecurityAudit::evaluate($facts), 'storage_writable'));
    }

    public function test_http_warns_in_production_only(): void
    {
        $prod = $this->healthyFacts();
        $prod['app_url'] = 'http://sys.example.org';
        $this->assertSame(SecurityAudit::WARN, $this->statusOf(SecurityAudit::evaluate($prod), 'https'));

        $local = $prod;
        $local['app_env'] = 'local';
        $this->assertSame(SecurityAudit::PASS, $this->statusOf(SecurityAudit::evaluate($local), 'https'));
    }

    public function test_inspect_upload_flags_script_extensions(): void
    {
        $allowed = ['pdf', 'jpg', 'png'];
        $this->assertNotNull(SecurityAudit::inspectUpload('php', $allowed));
        $this->assertNotNull(SecurityAudit::inspectUpload('PHTML', $allowed));
        $this->assertNotNull(SecurityAudit::inspectUpload('svg', $allowed));
    }

    public function test_inspect_upload_flags_disallowed_extension(): void
    {
        $this->assertNotNull(SecurityAudit::inspectUpload('zip', ['pdf', 'jpg']));
    }

    public function test_inspect_upload_flags_missing_extension(): void
    {
        $this->assertNotNull(SecurityAudit::inspectUpload('', ['pdf']));
    }

    public function test_inspect_upload_allows_permitted_file(): void
    {
        $this->assertNull(SecurityAudit::inspectUpload('pdf', ['pdf', 'jpg', 'png']));
        $this->assertNull(SecurityAudit::inspectUpload('.JPG', ['pdf', 'jpg', 'png']));
    }
}
