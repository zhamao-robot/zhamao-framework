<?php

use ZM\Config\ZMConfig;

define("ZM_START_TIME", microtime(true));
define("ZM_DATA", ZMConfig::get("global", "zm_data"));
define("ZM_VERSION", json_decode(file_get_contents(__DIR__ . "/../../composer.json"), true)["version"] ?? "unknown");
define("CONFIG_DIR", ZMConfig::get("global", "config_dir"));
define("CRASH_DIR", ZMConfig::get("global", "crash_dir"));
@mkdir(ZM_DATA);
@mkdir(CONFIG_DIR);
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
