<?php

declare(strict_types=1);

/** 定义炸毛框架初始启动时间 */
if (!defined('ZM_START_TIME')) {
    define('ZM_START_TIME', microtime(true));
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', LOAD_MODE == 1 ? (json_decode(file_get_contents(SOURCE_ROOT_DIR . '/composer.json'), true)['version'] ?? 'unknown') : 'unknown');
}
