<?php

declare(strict_types=1);

/*
此文件用于定义一些常量，是框架运行的前提常量，例如定义全局版本、全局方法等。
*/

use ZM\Framework;

/** 全局版本ID */
const ZM_VERSION_ID = Framework::VERSION_ID;

/** 全局版本名称 */
const ZM_VERSION = Framework::VERSION;

/** 机器人用的，用于判断二元语意 */
const TRUE_LIST = ['yes', 'y', 'true', 'on', '是', '对', true];
const FALSE_LIST = ['no', 'n', 'false', 'off', '否', '错', false];

/** 定义多进程的全局变量 */
const ZM_PROCESS_MASTER = ONEBOT_PROCESS_MASTER;
const ZM_PROCESS_MANAGER = ONEBOT_PROCESS_MANAGER;
const ZM_PROCESS_WORKER = ONEBOT_PROCESS_WORKER;
const ZM_PROCESS_USER = ONEBOT_PROCESS_USER;
const ZM_PROCESS_TASKWORKER = ONEBOT_PROCESS_TASKWORKER;

/** 定义一些内部引用的错误ID */
const ZM_ERR_NONE = 0;                  // 正常
const ZM_ERR_METHOD_NOT_FOUND = 1;      // 找不到方法
const ZM_ERR_ROUTE_NOT_FOUND = 2;       // 找不到路由
const ZM_ERR_ROUTE_METHOD_NOT_ALLOWED = 3; // 路由方法不允许

/* 定义工作目录 */
define('WORKING_DIR', getcwd());

/* 定义源码根目录，如果是 Phar 打包框架运行的话，就是 Phar 文件本身 */
define('SOURCE_ROOT_DIR', Phar::running() !== '' ? Phar::running() : WORKING_DIR);

/* 定义启动模式，这里指的是框架本身的源码目录是通过 composer 加入 vendor 加载的还是直接放到 src 目录加载的，前者为 1，后者为 0 */
define('LOAD_MODE', is_dir(zm_dir(SOURCE_ROOT_DIR . '/src/ZM')) ? 0 : 1);

/* 定义框架本身所处的根目录，此处如果 LOAD_MODE 为 1 的话，框架自身的根目录在 vendor/zhamao/framework 子目录下 */
if (Phar::running() !== '') {
    define('FRAMEWORK_ROOT_DIR', zm_dir(LOAD_MODE == 1 ? (SOURCE_ROOT_DIR . '/vendor/zhamao/framework') : SOURCE_ROOT_DIR));
} else {
    define('FRAMEWORK_ROOT_DIR', realpath(zm_dir(__DIR__ . '/../../')));
}

/* 定义用于存放框架运行状态的目录（Windows 不可用） */
if (DIRECTORY_SEPARATOR !== '\\') {
    define('ZM_PID_DIR', '/tmp/.zm_' . sha1(FRAMEWORK_ROOT_DIR));
}

/* 对 global.php 在 Windows 下的兼容性考虑，因为 Windows 或者无 Swoole 环境时候无法运行 */
!defined('SWOOLE_BASE') && define('SWOOLE_BASE', 1) && define('SWOOLE_PROCESS', 2);
!defined('SWOOLE_HOOK_ALL') && (
    define('SWOOLE_HOOK_TCP', 2)
    && define('SWOOLE_HOOK_UDP', 4)
    && define('SWOOLE_HOOK_UNIX', 8)
    && define('SWOOLE_HOOK_UDG', 16)
    && define('SWOOLE_HOOK_SSL', 32)
    && define('SWOOLE_HOOK_TLS', 64)
    && define('SWOOLE_HOOK_STREAM_FUNCTION', 128)
    && define('SWOOLE_HOOK_STREAM_SELECT', 128)
    && define('SWOOLE_HOOK_FILE', 256)
    && define('SWOOLE_HOOK_STDIO', 32768)
    && define('SWOOLE_HOOK_SLEEP', 512)
    && define('SWOOLE_HOOK_PROC', 1024)
    && define('SWOOLE_HOOK_CURL', 2048)
    && define('SWOOLE_HOOK_NATIVE_CURL', 4096)
    && define('SWOOLE_HOOK_BLOCKING_FUNCTION', 8192)
    && define('SWOOLE_HOOK_SOCKETS', 16384)
    && define('SWOOLE_HOOK_ALL', 2147481599)
);
