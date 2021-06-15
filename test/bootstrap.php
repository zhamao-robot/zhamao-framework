<?php
/**
 * @since 2.5
 */

set_coroutine_params([]);

// 模拟define
chdir(__DIR__.'/../');
define("WORKING_DIR", getcwd());
define("SOURCE_ROOT_DIR", WORKING_DIR);
define("LOAD_MODE", 0);
define("FRAMEWORK_ROOT_DIR", realpath(__DIR__ . "/../"));
