<?php

declare(strict_types=1);

namespace Tests\ZM\Store;

use org\bovigo\vfs\vfsStream;
use Tests\TestCase;
use Tests\Trait\HasLogger;
use Tests\Trait\HasVirtualFileSystem;
use ZM\Store\FileSystem;

/**
 * @internal
 */
class FileSystemTest extends TestCase
{
    use HasVirtualFileSystem;
    use HasLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpVfs();
        $this->startMockLogger();
    }

    /**
     * @dataProvider provideTestDetermineRelativePath
     */
    public function testDetermineRelativePath(string $path, bool $expected): void
    {
        $this->assertSame($expected, FileSystem::isRelativePath($path));
    }

    public function provideTestDetermineRelativePath(): array
    {
        return [
            'relative' => ['relative/path', true],
            'absolute' => ['/absolute/path', false],
            'windows' => ['C:\Windows', !(DIRECTORY_SEPARATOR === '\\')],
        ];
    }

    public function testCreateDirectoryWithNoPerm(): void
    {
        $old_perm = $this->vfs->getPermissions();
        $this->vfs->chmod(0000);
        $this->assertFalse($this->vfs->hasChild('test'));
        $this->expectExceptionMessageMatches('/无法建立目录/');
        FileSystem::createDir($this->vfs->url() . '/test');
        $this->vfs->chmod($old_perm);
    }

    public function testCreateDirectory(): void
    {
        $this->assertFalse($this->vfs->hasChild('test'));
        FileSystem::createDir($this->vfs->url() . '/test');
        $this->assertTrue($this->vfs->hasChild('test'));
    }

    public function testGetReloadableFiles(): void
    {
        $files = FileSystem::getReloadableFiles();
        $this->assertIsArray($files);
        $this->assertNotEmpty($files);
    }

    public function testGetClassesPsr4(): void
    {
        vfsStream::create([
            'Foo' => [
                'Bar.php' => '<?php namespace Foo; class Bar {}',
                'Baz.php' => '<?php namespace Foo; class Baz {}',
                'Qux' => [
                    'Quux.php' => '<?php namespace Bar\Qux; class Quux {}',
                ],
                'Baz.php.ignore' => '',
            ],
            'Chore' => [
                'global.php' => '<?php function global_function() {}',
                'global_classes.php' => '<?php class GlobalClass {}',
            ],
        ], $this->vfs);
        $classes = FileSystem::getClassesPsr4($this->vfs->url(), '');
        $this->assertSame([
            '\Foo\Bar',
            '\Foo\Qux\Quux',
        ], $classes);
    }

    public function testGetClassesPsr4WithCustomRule(): void
    {
        vfsStream::create([
            'Foo' => [
                'Bar.php' => '<?php namespace Foo; class Bar {}',
                'Baz.php' => '<?php namespace Foo; class Baz {}',
            ],
        ], $this->vfs);
        $classes = FileSystem::getClassesPsr4($this->vfs->url(), '', fn (string $dir, array $pathinfo) => $pathinfo['filename'] === 'Bar');
        $this->assertSame(['\Foo\Bar'], $classes);
    }

    public function testGetClassesPsr4WithReturnPath(): void
    {
        vfsStream::create([
            'Foo' => [
                'Bar.php' => '<?php namespace Foo; class Bar {}',
                'Baz.php' => '<?php namespace Foo; class Baz {}',
            ],
        ], $this->vfs);
        $classes = FileSystem::getClassesPsr4($this->vfs->url(), '', return_path_value: 'my_path');
        $this->assertSame([
            '\Foo\Bar' => 'my_path/Foo/Bar.php',
            '\Foo\Baz' => 'my_path/Foo/Baz.php',
        ], $classes);
    }

    public function testScanDirFilesWithNotExistsDir(): void
    {
        FileSystem::scanDirFiles($this->vfs->url() . '/not_exists');
        $this->assertLogged('warning', zm_internal_errcode('E00080') . '扫描目录失败，目录不存在');
    }

    public function testScanDirFilesWithNoPerm(): void
    {
        $old_perm = $this->vfs->getPermissions();
        $this->vfs->chmod(0000);
        FileSystem::scanDirFiles($this->vfs->url());
        $this->assertLogged('warning', zm_internal_errcode('E00080') . '扫描目录失败，目录无法读取: ' . $this->vfs->url());
        $this->vfs->chmod($old_perm);
    }
}
