<?php


namespace ZM\Utils;


use PHPUnit\Framework\TestCase;
use ZM\Config\ZMConfig;

class DataProviderTest extends TestCase
{
    protected function setUp(): void {
        ZMConfig::setDirectory(realpath(__DIR__ . "/../Mock"));
        if (!defined('ZM_DATA'))
            define("ZM_DATA", DataProvider::getWorkingDir() . "/zm_data/");

    }

    public function testScanDirFiles() {
        zm_dump(DataProvider::scanDirFiles("/fwef/wegweg"));
        $this->assertContains("Example/Hello.php", DataProvider::scanDirFiles(DataProvider::getSourceRootDir() . '/src/Module', true, true));
    }

    public function testGetDataFolder() {
        DataProvider::getDataFolder("testFolder");
        $this->assertDirectoryExists(DataProvider::getWorkingDir() . "/zm_data/testFolder");
        rmdir(DataProvider::getWorkingDir() . "/zm_data/testFolder");
    }
}