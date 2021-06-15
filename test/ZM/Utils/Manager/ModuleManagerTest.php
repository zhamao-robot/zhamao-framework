<?php

namespace ZM\Utils\Manager;

use PHPUnit\Framework\TestCase;
use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Utils\DataProvider;

class ModuleManagerTest extends TestCase
{
    public function setUp(): void {
        file_put_contents(DataProvider::getSourceRootDir()."/src/Module/zm.json", json_encode([
            "name" => "示例模块2"
        ]));

        ZMConfig::setDirectory(DataProvider::getSourceRootDir() . '/config');
        ZMConfig::setEnv($args["env"] ?? "");
        if (ZMConfig::get("global") === false) {
            die ("Global config load failed: " . ZMConfig::$last_error . "\nPlease init first!\n");
        }

        //定义常量
        include_once DataProvider::getFrameworkRootDir()."/src/ZM/global_defines.php";

        Console::init(
            ZMConfig::get("global", "info_level") ?? 2,
            null,
            $args["log-theme"] ?? "default",
            ($o = ZMConfig::get("console_color")) === false ? [] : $o
        );

        $timezone = ZMConfig::get("global", "timezone") ?? "Asia/Shanghai";
        date_default_timezone_set($timezone);
    }

    public function tearDown(): void {
        unlink(DataProvider::getSourceRootDir()."/src/Module/zm.json");
    }

    public function testGetConfiguredModules() {
        zm_dump(ModuleManager::getConfiguredModules());
        $this->assertArrayHasKey("示例模块", ModuleManager::getConfiguredModules());
    }

    public function testPackModule() {
        $list = ModuleManager::getConfiguredModules();
        $this->assertTrue(ModuleManager::packModule(current($list)));
    }
}
