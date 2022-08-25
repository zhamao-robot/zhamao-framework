<?php

declare(strict_types=1);

namespace Tests\ZM\Config;

use ZM\Config\ZMConfig;
use ZM\Utils\ReflectionUtil;

$config = null;

beforeAll(static function () {
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

    $GLOBALS['config'] = new ZMConfig([$mock_dir], 'development');
});

afterAll(static function () {
    foreach (scandir(__DIR__ . '/config_mock') as $file) {
        if ($file !== '.' && $file !== '..') {
            unlink(__DIR__ . '/config_mock/' . $file);
        }
    }
    rmdir(__DIR__ . '/config_mock');
});

function test_get($key, $default = null)
{
    return $GLOBALS['config']->get($key, $default);
}

test('get value when key contains dot', function () {
    expect(test_get('test.a.b'))->toBe('c');
    expect(test_get('test.a.b.c'))->toBe('d');
})->skip('should it be supported?');

test('get boolean value', function () {
    expect(test_get('test.boolean'))->toBeTrue();
});

test('get value', function (string $key, $expected) {
    expect(test_get($key))->toBe($expected);
})->with([
    'null' => ['test.null', null],
    'boolean' => ['test.boolean', true],
    'associate' => ['test.associate', ['x' => 'xxx', 'y' => 'yyy']],
    'array' => ['test.array', ['aaa', 'zzz']],
    'dot access' => ['test.x.z', 'zoo'],
]);

test('get with default', function () {
    expect(test_get('not_exist', 'default'))->toBe('default');
});

test('set value', function () {
    $GLOBALS['config']->set('test.new', 'new');
    expect(test_get('test.new'))->toBe('new');
});

test('set array value', function () {
    $GLOBALS['config']->set('test.new.array', ['new', 'array']);
    expect(test_get('test.new.array'))->toBe(['new', 'array']);
    expect(test_get('test.new.array.0'))->toBe('new');
});

test('get environment specified value', function () {
    expect(test_get('test.environment'))->toBe('yes');
    expect(test_get('test.env'))->toBe('development');
});

test('get patch specified value', function () {
    expect(test_get('test.patch'))->toBe('yes');
});

test('get file load type', function (string $name, string $type) {
    $method = ReflectionUtil::getMethod(ZMConfig::class, 'getFileLoadType');
    $actual = $method->invokeArgs($GLOBALS['config'], [$name]);
    expect($actual)->toBe($type);
})->with([
    'default' => ['test', 'default'],
    'environment' => ['test.development', 'environment'],
    'patch' => ['test.patch', 'patch'],
    // complex case are not supported yet
    'invalid' => ['test.patch.development', 'undefined'],
]);

test('use array replace instead of merge', function () {
    // using of space inside config key is not an officially supported feature,
    // it may be removed in the future, please avoid using it in your project.
    expect(test_get('test.another array'))->toBe(['far', 'baz']);
});
