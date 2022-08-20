<?php

namespace Tests\ZM\Config;

use ZM\Config\RefactoredConfig;
use PHPUnit\Framework\TestCase;

class RefactoredConfigTest extends TestCase
{
    private static $config;

    public static function setUpBeforeClass(): void
    {
        $mock_dir = __DIR__ . '/config_mock';
        if (!is_dir($mock_dir)) {
            mkdir($mock_dir, 0755, true);
        }

        $test_config = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'boolean' => true,
            'associate' => [
                'x' => 'xxx',
                'y' => 'yyy',
            ],
            'array' => [
                'aaa',
                'zzz',
            ],
            'x' => [
                'z' => 'zoo',
            ],
            'a.b' => 'c',
            'a' => [
                'b.c' => 'd',
            ],
            'from' => 'global',
        ];

        // 下方测试需要临时写入的文件
        file_put_contents($mock_dir . '/test.php', '<?php return ' . var_export($test_config, true) . ';');
        file_put_contents($mock_dir . '/test.development.php',
            '<?php return ["from" => "environment", "env" => "dev"];');
        file_put_contents($mock_dir . '/test.production.php',
            '<?php return ["from" => "environment", "env" => "prod"];');
        file_put_contents($mock_dir . '/test.invalid.php', '<?php return ["from" => "invalid"];');

        $config = new RefactoredConfig([
            __DIR__ . '/config_mock',
        ], 'development');
        self::$config = $config;
    }

    public static function tearDownAfterClass(): void
    {
        foreach (scandir(__DIR__ . '/config_mock') as $file) {
            if ($file !== '.' && $file !== '..') {
                unlink(__DIR__ . '/config_mock/' . $file);
            }
        }
        rmdir(__DIR__ . '/config_mock');
    }

    public function testGetValueWhenKeyContainsDot(): void
    {
        $this->markTestSkipped('should it be supported?');
        $this->assertEquals('c', self::$config->get('test.a.b'));
        $this->assertEquals('d', self::$config->get('test.a.b.c'));
    }

    public function testGetBooleanValue(): void
    {
        $this->assertTrue(self::$config->get('test.boolean'));
    }

    public function testGetValue(): void
    {
        $this->assertSame('bar', self::$config->get('test.foo'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('default', self::$config->get('not_exist', 'default'));
    }

    public function testSetValue(): void
    {
        self::$config->set('key', 'value');
        $this->assertSame('value', self::$config->get('key'));
    }

    public function testSetArrayValue(): void
    {
        self::$config->set('array', ['a', 'b']);
        $this->assertSame(['a', 'b'], self::$config->get('array'));
        $this->assertSame('a', self::$config->get('array.0'));
    }
}
