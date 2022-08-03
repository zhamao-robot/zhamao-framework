<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use PHPUnit\Framework\TestCase;
use ZM\Utils\DataProvider;

/**
 * @internal
 */
class DataProviderTest extends TestCase
{
    public function testGetSourceRootDir(): void
    {
        $this->assertEquals(SOURCE_ROOT_DIR, DataProvider::getSourceRootDir());
    }

    public function testGetDataFolder(): void
    {
        $this->assertEquals(SOURCE_ROOT_DIR . '/zm_data/', DataProvider::getDataFolder());
    }

    public function testGetResourceFolder(): void
    {
        $this->assertEquals(SOURCE_ROOT_DIR . '/resources/', DataProvider::getResourceFolder());
    }

    public function testScanDirFiles(): void
    {
        // 常规测试
        $files = DataProvider::scanDirFiles(SOURCE_ROOT_DIR . '/src/Module');
        $this->assertContains(SOURCE_ROOT_DIR . '/src/Module/Example/Hello.php', $files);
        // 检查文件夹不存在的判断
        $random_id = uuidgen();
        $dir = '/tmp/' . $random_id;
        // 检查目录不存在是否返回false
        $this->assertFalse(DataProvider::scanDirFiles($dir));
        mkdir($dir);
        // 检查目录存在时返回空
        $this->assertEquals([], DataProvider::scanDirFiles($dir));
        chmod($dir, 000);
        // 目录无权限时也返回false
        $this->assertFalse(DataProvider::scanDirFiles($dir));
        chmod($dir, 0755);
        mkdir($dir . '/test');
        file_put_contents($dir . '/test/a.txt', 'Hello world!');
        // 检查目录下文件是否正确返回
        $this->assertEquals([$dir . '/test/a.txt'], DataProvider::scanDirFiles($dir));
        // 检查不递归时包含目录的模式
        $this->assertEquals([], DataProvider::scanDirFiles($dir, false));
        $this->assertEquals([$dir . '/test'], DataProvider::scanDirFiles($dir, false, false, true));
        // 检查相对目录是否能正常返回
        $this->assertEquals(['test/a.txt'], DataProvider::scanDirFiles($dir, true, true));
        // relative传入奇怪的东西就返回false
        $this->assertFalse(DataProvider::scanDirFiles($dir, true, null, true));
    }

    public function testGetFrameworkRootDir(): void
    {
        $this->assertEquals(FRAMEWORK_ROOT_DIR, DataProvider::getFrameworkRootDir());
    }

    public function testGetWorkingDir(): void
    {
        $this->assertEquals(SOURCE_ROOT_DIR, DataProvider::getWorkingDir());
    }

    public function testSaveLoadJson(): void
    {
        $data = [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ];
        $file = 'test.json';
        DataProvider::saveToJson($file, $data);
        $this->assertEquals($data, DataProvider::loadFromJson($file));
    }

    public function testGetFrameworkLink(): void
    {
        $this->assertNotFalse(filter_var(DataProvider::getFrameworkLink(), FILTER_VALIDATE_URL));
    }

    public function testIsRelativePath(): void
    {
        $this->assertTrue(DataProvider::isRelativePath('./'));
        $this->assertTrue(DataProvider::isRelativePath('../'));
        $this->assertFalse(DataProvider::isRelativePath('/'));
        $this->assertTrue(DataProvider::isRelativePath('test.php'));
    }
}
