<?php

declare(strict_types=1);

namespace Tests\ZM\Config;

use PHPUnit\Framework\TestCase;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Exception\ConfigException;
use ZM\Utils\DataProvider;

/**
 * @internal
 */
class ZMConfigTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $mock_dir = __DIR__ . '/config_mock';
        ZMConfig::reload();
        ZMConfig::setDirectory(__DIR__ . '/config_mock');
        if (!is_dir($mock_dir)) {
            mkdir($mock_dir, 0755, true);
        }
        // 下方测试需要临时写入的文件
        file_put_contents($mock_dir . '/global.patch.php', '<?php return ["port" => 30055];');
        file_put_contents($mock_dir . '/php_exception.php', '<?php return true;');
        file_put_contents($mock_dir . '/json_exception.json', '"string"');
        file_put_contents($mock_dir . '/global.development.patch.php', '<?php return ["port" => 30055];');
        file_put_contents($mock_dir . '/global.invalid.development.php', '<?php return ["port" => 30055];');
        file_put_contents($mock_dir . '/fake.development.json', '{"multi":{"level":"test"}}');
        file_put_contents($mock_dir . '/no_main_only_patch.patch.json', '{"multi":{"level":"test"}}');
    }

    public static function tearDownAfterClass(): void
    {
        ZMConfig::reload();
        ZMConfig::restoreDirectory();
        foreach (DataProvider::scanDirFiles(__DIR__ . '/config_mock', true, false) as $file) {
            unlink($file);
        }
        rmdir(__DIR__ . '/config_mock');
    }

    /**
     * @throws ConfigException
     */
    public function testReload()
    {
        $this->markTestIncomplete('logger level change in need');
        $this->expectOutputRegex('/没读取过，正在从文件加载/');
        $this->assertEquals('0.0.0.0', ZMConfig::get('global.host'));
        ZMConfig::reload();
        Console::setLevel(4);
        $this->assertEquals('0.0.0.0', ZMConfig::get('global.host'));
        Console::setLevel(0);
    }

    public function testSetAndRestoreDirectory()
    {
        $origin = ZMConfig::getDirectory();
        ZMConfig::setDirectory('.');
        $this->assertEquals('.', ZMConfig::getDirectory());
        ZMConfig::restoreDirectory();
        $this->assertEquals($origin, ZMConfig::getDirectory());
    }

    public function testSetAndGetEnv()
    {
        $this->expectException(ConfigException::class);
        ZMConfig::setEnv('production');
        $this->assertEquals('production', ZMConfig::getEnv());
        ZMConfig::setEnv();
        ZMConfig::setEnv('reee');
    }

    /**
     * @dataProvider providerTestGet
     * @param  mixed           $expected
     * @throws ConfigException
     */
    public function testGet(array $data_params, $expected)
    {
        $this->assertEquals($expected, ZMConfig::get(...$data_params));
    }

    public function providerTestGet(): array
    {
        return [
            'get port' => [['global.port'], 30055],
            'get port key 2' => [['global', 'port'], 30055],
            'get invalid key' => [['global', 'invalid'], null],
            'get another environment' => [['fake.multi.level'], 'test'],
        ];
    }

    public function testGetPhpException()
    {
        $this->expectException(ConfigException::class);
        ZMConfig::get('php_exception');
    }

    public function testGetJsonException()
    {
        $this->expectException(ConfigException::class);
        ZMConfig::get('json_exception');
    }

    public function testOnlyPatchException()
    {
        $this->expectException(ConfigException::class);
        ZMConfig::get('no_main_only_patch.test');
    }

    public function testSmartPatch()
    {
        $array = [
            'key-1-1' => 'value-1-1',
            'key-1-2' => [
                'key-2-1' => [
                    'key-3-1' => [
                        'value-3-1',
                        'value-3-2',
                    ],
                ],
            ],
            'key-1-3' => [
                'key-4-1' => 'value-4-1',
            ],
        ];
        $patch = [
            'key-1-2' => [
                'key-2-1' => [
                    'key-3-1' => [
                        'value-3-3',
                    ],
                ],
            ],
            'key-1-3' => [
                'key-4-2' => [
                    'key-5-1' => 'value-5-1',
                ],
            ],
        ];
        $expected = [
            'key-1-1' => 'value-1-1',
            'key-1-2' => [
                'key-2-1' => [
                    'key-3-1' => [
                        'value-3-3',
                    ],
                ],
            ],
            'key-1-3' => [
                'key-4-1' => 'value-4-1',
                'key-4-2' => [
                    'key-5-1' => 'value-5-1',
                ],
            ],
        ];
        $this->assertEquals($expected, ZMConfig::smartPatch($array, $patch));
    }
}
