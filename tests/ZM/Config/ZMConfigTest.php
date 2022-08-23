<?php

declare(strict_types=1);

namespace Tests\ZM\Config;

use PHPUnit\Framework\TestCase;
use ZM\Config\ZMConfig;
use ZM\Utils\ReflectionUtil;

/**
 * @internal
 */
class ZMConfigTest extends TestCase
{
    private static ZMConfig $config;

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
            'default' => 'yes',
            'another array' => [
                'foo', 'bar',
            ],
        ];

        // 下方测试需要临时写入的文件
        file_put_contents($mock_dir . '/test.php', '<?php return ' . var_export($test_config, true) . ';');
        file_put_contents(
            $mock_dir . '/test.development.php',
            '<?php return ["environment" => "yes", "env" => "development"];'
        );
        file_put_contents(
            $mock_dir . '/test.production.php',
            '<?php return ["environment" => "yes", "env" => "production"];'
        );
        file_put_contents(
            $mock_dir . '/test.patch.php',
            '<?php return ["patch" => "yes", "another array" => ["far", "baz"]];'
        );

        $config = new ZMConfig([
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

    /**
     * @dataProvider providerTestGetValue
     * @param mixed $expected
     */
    public function testGetValue(string $key, $expected): void
    {
        $this->assertSame($expected, self::$config->get($key));
    }

    public function providerTestGetValue(): array
    {
        return [
            'null' => ['test.null', null],
            'boolean' => ['test.boolean', true],
            'associate' => ['test.associate', ['x' => 'xxx', 'y' => 'yyy']],
            'array' => ['test.array', ['aaa', 'zzz']],
            'dot access' => ['test.x.z', 'zoo'],
        ];
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

    public function testGetEnvironmentSpecifiedValue(): void
    {
        $this->assertSame('yes', self::$config->get('test.environment'));
        $this->assertSame('development', self::$config->get('test.env'));
    }

    public function testGetPatchSpecifiedValue(): void
    {
        $this->assertSame('yes', self::$config->get('test.patch'));
    }

    /**
     * @dataProvider providerTestGetFileLoadType
     */
    public function testGetFileLoadType(string $name, string $type): void
    {
        $method = ReflectionUtil::getMethod(ZMConfig::class, 'getFileLoadType');
        $actual = $method->invokeArgs(self::$config, [$name]);
        $this->assertSame($type, $actual);
    }

    public function providerTestGetFileLoadType(): array
    {
        return [
            'default' => ['test', 'default'],
            'environment' => ['test.development', 'environment'],
            'patch' => ['test.patch', 'patch'],
            // complex case are not supported yet
            'invalid' => ['test.patch.development', 'undefined'],
        ];
    }

    public function testArrayReplaceInsteadOfMerge(): void
    {
        // using of space inside config key is not an officially supported feature,
        // it may be removed in the future, please avoid using it in your project.
        $this->assertSame(['far', 'baz'], self::$config->get('test.another array'));
    }
}
