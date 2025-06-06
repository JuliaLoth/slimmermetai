<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Infrastructure\Config\Config;

/**
 * Config Unit Tests
 * 
 * Test de Config klasse functionaliteit
 */
class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    public function testConfigCanGetValues(): void
    {
        $this->assertIsString($this->config->get('site_name'));
        $this->assertEquals('SlimmerMetAI', $this->config->get('site_name'));
    }

    public function testConfigCanGetDefaultValues(): void
    {
        $default = 'default_value';
        $result = $this->config->get('non_existent_key', $default);
        $this->assertEquals($default, $result);
    }

    public function testConfigCanSetValues(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        $this->config->set($key, $value);
        $this->assertEquals($value, $this->config->get($key));
    }

    public function testConfigCanCheckIfKeyExists(): void
    {
        $this->assertTrue($this->config->has('site_name'));
        $this->assertFalse($this->config->has('non_existent_key'));
    }

    public function testConfigCanGetAllValues(): void
    {
        $all = $this->config->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('site_name', $all);
        $this->assertArrayHasKey('db_host', $all);
    }

    public function testConfigCanGetTypedValues(): void
    {
        // Test integer casting
        $this->config->set('test_int', '123');
        $result = $this->config->getTyped('test_int', 'int');
        $this->assertIsInt($result);
        $this->assertEquals(123, $result);

        // Test boolean casting
        $this->config->set('test_bool', 'true');
        $result = $this->config->getTyped('test_bool', 'bool');
        $this->assertIsBool($result);
        $this->assertTrue($result);

        // Test array casting
        $this->config->set('test_array', 'one,two,three');
        $result = $this->config->getTyped('test_array', 'array');
        $this->assertIsArray($result);
        $this->assertEquals(['one', 'two', 'three'], $result);
    }

    public function testEnvironmentValues(): void
    {
        // Test that environment-specific values are loaded
        $this->assertEquals('testing', $this->config->get('app_env'));
    }

    public function testSecurityDefaults(): void
    {
        // Test security-related defaults
        $this->assertGreaterThanOrEqual(8, $this->config->get('password_min_length'));
        $this->assertGreaterThanOrEqual(10, $this->config->get('bcrypt_cost'));
        $this->assertTrue($this->config->get('cookie_httponly'));
    }
} 