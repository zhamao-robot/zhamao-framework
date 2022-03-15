<?php

declare(strict_types=1);

use ZM\Config\ZMConfig;
use ZM\Utils\DataProvider;

define('ZM_START_TIME', microtime(true));
define('ZM_DATA', ZMConfig::get('global', 'zm_data'));
define('APP_VERSION', LOAD_MODE == 1 ? (json_decode(file_get_contents(DataProvider::getSourceRootDir() . '/composer.json'), true)['version'] ?? 'unknown') : 'unknown');
define('CRASH_DIR', ZMConfig::get('global', 'crash_dir'));
define('MAIN_WORKER', ZMConfig::get('global', 'worker_cache')['worker'] ?? 0);
if (!is_dir(ZM_DATA)) {
    @mkdir(ZM_DATA);
}
if (!is_dir(CRASH_DIR)) {
    @mkdir(CRASH_DIR);
}

const CONN_WEBSOCKET = 0;
const CONN_HTTP = 1;
const ZM_MATCH_ALL = 0;
const ZM_MATCH_FIRST = 1;
const ZM_MATCH_NUMBER = 2;
const ZM_MATCH_SECOND = 3;
const ZM_BREAKPOINT = 'if(\ZM\Framework::$argv["debug-mode"]) extract(\Psy\debug(get_defined_vars(), isset($this) ? $this : @get_called_class()));';
const BP = ZM_BREAKPOINT;
const ZM_DEFAULT_FETCH_MODE = 4;

const ZM_LOG_ERROR = 0;
const ZM_LOG_WARNING = 1;
const ZM_LOG_INFO = 2;
const ZM_LOG_VERBOSE = 3;
const ZM_LOG_DEBUG = 4;
