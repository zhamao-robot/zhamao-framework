<?php

declare(strict_types=1);

namespace Tests\ZM\Config;

use Tests\TestCase;
use Tests\Trait\HasVirtualFileSystem;
use ZM\Config\ZMConfig;
use ZM\Exception\ConfigException;
use ZM\Utils\ReflectionUtil;

/**
 * @internal
 */
class ZMConfigTest extends TestCase
{
    use HasVirtualFileSystem;

    private ZMConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->setUpVfs('config', [
            'test.php' => '<?php return ' . var_export($test_config, true) . ';',
            'test.development.php' => '<?php return ["environment" => "yes", "env" => "development"];',
            'test.production.php' => '<?php return ["environment" => "yes", "env" => "production"];',
            'test.patch.php' => '<?php return ["patch" => "yes", "another array" => ["far", "baz"]];',
        ]);

        try {
            $init_conf = require SOURCE_ROOT_DIR . '/config/config.php';
            $init_conf['source']['paths'] = [$this->vfs->url()];
            $config = new ZMConfig($init_conf);
        } catch (ConfigException $e) {
            $this->fail($e->getMessage());
        }
        $this->config = $config;
    }

    public function testGetValueWhenKeyContainsDot(): void
    {
        $this->markTestSkipped('should it be supported?');
//        $this->assertEquals('c', $this->config->get('test.a.b'));
//        $this->assertEquals('d', $this->config->get('test.a.b.c'));
    }

    public function testGetBooleanValue(): void
    {
        $this->assertTrue($this->config->get('test.boolean'));
    }

    /**
     * @dataProvider providerTestGetValue
     */
    public function testGetValue(string $key, mixed $expected): void
    {
        $this->assertSame($expected, $this->config->get($key));
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
        $this->assertSame('default', $this->config->get('not_exist', 'default'));
    }

    public function testSetValue(): void
    {
        $this->config->set('key', 'value');
        $this->assertSame('value', $this->config->get('key'));
    }

    public function testSetArrayValue(): void
    {
        $this->config->set('array', ['a', 'b']);
        $this->assertSame(['a', 'b'], $this->config->get('array'));
        $this->assertSame('a', $this->config->get('array.0'));
    }

    public function testGetEnvironmentSpecifiedValue(): void
    {
        $this->assertSame('yes', $this->config->get('test.environment'));
        $this->assertSame('development', $this->config->get('test.env'));
    }

    public function testGetPatchSpecifiedValue(): void
    {
        $this->assertSame('yes', $this->config->get('test.patch'));
    }

    /**
     * @dataProvider providerTestGetFileLoadType
     */
    public function testGetFileLoadType(string $name, string $type): void
    {
        $method = ReflectionUtil::getMethod(ZMConfig::class, 'getFileLoadType');
        $actual = $method->invokeArgs($this->config, [$name]);
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
        $this->assertSame(['far', 'baz'], $this->config->get('test.another array'));
    }
}
