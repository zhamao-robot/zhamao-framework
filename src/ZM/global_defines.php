<?php #plain

use ZM\Config\ZMConfig;
use ZM\Utils\DataProvider;

define("ZM_START_TIME", microtime(true));
define("ZM_DATA", ZMConfig::get("global", "zm_data"));
define("APP_VERSION", LOAD_MODE == 1 ? (json_decode(file_get_contents(DataProvider::getWorkingDir() . "/composer.json"), true)["version"] ?? "unknown") : "unknown");
define("CRASH_DIR", ZMConfig::get("global", "crash_dir"));
define("MAIN_WORKER", ZMConfig::get("global", "worker_cache")["worker"] ?? 0);
@mkdir(ZM_DATA);
@mkdir(CRASH_DIR);

define("CONN_WEBSOCKET", 0);
define("CONN_HTTP", 1);
define("ZM_MATCH_ALL", 0);
define("ZM_MATCH_FIRST", 1);
define("ZM_MATCH_NUMBER", 2);
define("ZM_MATCH_SECOND", 3);
define("ZM_BREAKPOINT", 'if(\ZM\Framework::$argv["debug-mode"]) extract(\Psy\debug(get_defined_vars(), isset($this) ? $this : @get_called_class()));');
define("BP", ZM_BREAKPOINT);
define("ZM_DEFAULT_FETCH_MODE", 4);

define("ZM_LOG_ERROR", 0);
define("ZM_LOG_WARNING", 1);
define("ZM_LOG_INFO", 2);
define("ZM_LOG_VERBOSE", 3);
define("ZM_LOG_DEBUG", 4);
