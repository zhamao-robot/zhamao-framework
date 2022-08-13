<?php

declare(strict_types=1);

use ZM\Utils\ZMUtil;

/* 定义炸毛框架初始启动时间 */
if (!defined('ZM_START_TIME')) {
    define('ZM_START_TIME', microtime(true));
}

/* 定义使用炸毛框架应用的版本 */
if (!defined('APP_VERSION')) {
    define('APP_VERSION', LOAD_MODE == 1 ? (ZMUtil::getComposerMetadata()['version'] ?? ZM_VERSION) : ZM_VERSION);
}
