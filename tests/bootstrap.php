<?php

use ZM\Config\ZMConfig;
use ZM\Console\Console;
use ZM\Store\LightCacheInside;
use ZM\Store\ZMAtomic;
use ZM\Utils\DataProvider;
use ZM\Utils\Terminal;

require_once __DIR__ . '/../vendor/autoload.php';

// 模拟define
chdir(__DIR__ . '/../');
define('WORKING_DIR', getcwd());
const SOURCE_ROOT_DIR = WORKING_DIR;
const ZM_DATA = WORKING_DIR . '/zm_data/';
const LOAD_MODE = 0;
define('FRAMEWORK_ROOT_DIR', dirname(__DIR__) . '/');

ZMConfig::setDirectory(WORKING_DIR . '/config/');
ZMConfig::setEnv();
if (ZMConfig::get('global') === false) {
    die (zm_internal_errcode('E00007') . 'Global config load failed: ' . ZMConfig::$last_error . "\nError path: " . DataProvider::getSourceRootDir() . "\nPlease init first!\nSee: https://github.com/zhamao-robot/zhamao-framework/issues/37\n");
}
LightCacheInside::init();
ZMAtomic::init();
Terminal::init();
Console::setLevel(4);
