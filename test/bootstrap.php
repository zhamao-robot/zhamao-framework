<?php
/**
 * @since 2.5
 */

use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;
use ZM\Utils\DataProvider;
use ZM\Utils\Terminal;

set_coroutine_params([]);

// 模拟define
chdir(__DIR__ . '/../');
define("WORKING_DIR", getcwd());
define("SOURCE_ROOT_DIR", WORKING_DIR);
define("ZM_DATA", WORKING_DIR . "/zm_data/");
define("LOAD_MODE", 0);
define("FRAMEWORK_ROOT_DIR", realpath(__DIR__ . "/../"));

ZMConfig::setDirectory(WORKING_DIR."/config/");
ZMConfig::setEnv("");
if (ZMConfig::get("global") === false) {
    die (zm_internal_errcode("E00007") . "Global config load failed: " . ZMConfig::$last_error . "\nError path: " . DataProvider::getSourceRootDir() . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n");
}
LightCacheInside::init();
ZMAtomic::init();
Terminal::init();
Console::setLevel(4);