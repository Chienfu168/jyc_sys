<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * env() / env_bool() / e() 的測試,包含 env() 對 falsy 值的回歸測試。
 */
final class EnvHelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        foreach (['TEST_ENV_KEY', 'TEST_BOOL_KEY'] as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }
    }

    public function test_env_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', env('TEST_ENV_KEY_MISSING', 'fallback'));
        $this->assertNull(env('TEST_ENV_KEY_MISSING'));
    }

    public function test_env_returns_value_when_present(): void
    {
        $_ENV['TEST_ENV_KEY'] = 'hello';
        $this->assertSame('hello', env('TEST_ENV_KEY', 'fallback'));
    }

    /**
     * 回歸測試:合法的 "0" 值不可被誤判為 falsy 而回退預設值。
     */
    public function test_env_preserves_zero_string_value(): void
    {
        $_ENV['TEST_ENV_KEY'] = '0';
        $this->assertSame('0', env('TEST_ENV_KEY', 'fallback'));
    }

    public function test_env_preserves_empty_string_value(): void
    {
        $_ENV['TEST_ENV_KEY'] = '';
        $this->assertSame('', env('TEST_ENV_KEY', 'fallback'));
    }

    public function test_env_bool_truthy_values(): void
    {
        foreach (['1', 'true', 'TRUE', 'yes', 'on'] as $truthy) {
            $_ENV['TEST_BOOL_KEY'] = $truthy;
            $this->assertTrue(env_bool('TEST_BOOL_KEY'), "expected '{$truthy}' to be true");
        }
    }

    public function test_env_bool_falsy_values(): void
    {
        foreach (['0', 'false', 'no', 'off', 'anything'] as $falsy) {
            $_ENV['TEST_BOOL_KEY'] = $falsy;
            $this->assertFalse(env_bool('TEST_BOOL_KEY'), "expected '{$falsy}' to be false");
        }
    }

    public function test_env_bool_defaults_when_missing(): void
    {
        $this->assertTrue(env_bool('TEST_BOOL_KEY_MISSING', true));
        $this->assertFalse(env_bool('TEST_BOOL_KEY_MISSING', false));
    }

    public function test_e_escapes_html_special_characters(): void
    {
        $this->assertSame('&lt;script&gt;', e('<script>'));
        $this->assertSame('&quot;&#039;&amp;', e('"\'&'));
        $this->assertSame('', e(null));
    }
}
