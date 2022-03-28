<?php

declare(strict_types=1);

namespace Tests\ZM\Utils;

use ZM\Utils\DataProvider;
use PHPUnit\Framework\TestCase;

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
        $files = DataProvider::scanDirFiles(SOURCE_ROOT_DIR . '/src/Module');
        $this->assertContains(SOURCE_ROOT_DIR . '/src/Module/Example/Hello.php', $files);
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
