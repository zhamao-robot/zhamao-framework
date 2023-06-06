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
const TRUE_LIST = ['yes', 'ok', 'y', 'true', 'on', '是', '对', true];
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

/** 定义 BotContext 下 reply 回复的模式 */
const ZM_REPLY_NONE = 0;                // 默认回复，不带任何东西
const ZM_REPLY_MENTION = 1;             // 回复时 @ 该用户
const ZM_REPLY_QUOTE = 2;               // 回复时引用该消息

const ZM_PROMPT_NONE = 0;                       // 使用 prompt() 不附加任何内容
const ZM_PROMPT_MENTION_USER = 1;               // 回复提示语句时 at 该用户
const ZM_PROMPT_QUOTE_USER = 2;                 // 回复提示语句时引用该用户的消息
const ZM_PROMPT_TIMEOUT_MENTION_USER = 4;       // 回复超时时 at 该用户
const ZM_PROMPT_TIMEOUT_QUOTE_SELF = 8;         // 回复超时时引用自己回复的提示语句
const ZM_PROMPT_TIMEOUT_QUOTE_USER = 16;        // 回复超时时引用用户的消息
const ZM_PROMPT_RETURN_STRING = 32;             // 回复返回 string 格式
const ZM_PROMPT_RETURN_ARRAY = 0;               // 回复返回消息段格式
const ZM_PROMPT_RETURN_EVENT = 64;              // 回复返回 prompt 消息所对应的事件对象格式
const ZM_PROMPT_UPDATE_EVENT = 128;             // 在接收到 prompt 后，更新容器内绑定的事件对象

const ZM_PLUGIN_TYPE_NATIVE = 0;                // 原生类型的插件，特指内部插件、ZMApplication 继承的插件
const ZM_PLUGIN_TYPE_PHAR = 1;                  // Phar 类型的插件
const ZM_PLUGIN_TYPE_SOURCE = 2;                // 源码类型的插件
const ZM_PLUGIN_TYPE_COMPOSER = 3;              // Composer 依赖的插件

const LOAD_MODE_SRC = 0;    // 从 src 加载
const LOAD_MODE_VENDOR = 1; // 从 vendor 加载

const ZM_DB_POOL = 1;       // 数据库连接池
const ZM_DB_PORTABLE = 2;   // SQLite 便携数据库

/* 定义工作目录 */
define('WORKING_DIR', getcwd());

/* 定义源码根目录，如果是 Phar 打包框架运行的话，就是 Phar 文件本身 */
define('SOURCE_ROOT_DIR', Phar::running() !== '' ? Phar::running() : WORKING_DIR);

if (DIRECTORY_SEPARATOR === '\\') {
    define('TMP_DIR', 'C:\\Windows\\Temp');
} elseif (!empty(getenv('TMPDIR'))) {
    define('TMP_DIR', getenv('TMPDIR'));
} elseif (is_writable('/tmp')) {
    define('TMP_DIR', '/tmp');
} else {
    define('TMP_DIR', getcwd() . '/.zm-tmp');
}

/* 定义启动模式，这里指的是框架本身的源码目录是通过 composer 加入 vendor 加载的还是直接放到 src 目录加载的 */
define('LOAD_MODE', is_dir(zm_dir(SOURCE_ROOT_DIR . '/src/ZM')) ? LOAD_MODE_SRC : LOAD_MODE_VENDOR);

/* 定义框架本身所处的根目录，此处如果 LOAD_MODE 为 VENDOR 的话，框架自身的根目录在 vendor/zhamao/framework 子目录下 */
if (Phar::running() !== '') {
    define('FRAMEWORK_ROOT_DIR', zm_dir(LOAD_MODE === LOAD_MODE_VENDOR ? (SOURCE_ROOT_DIR . '/vendor/zhamao/framework') : SOURCE_ROOT_DIR));
} else {
    define('FRAMEWORK_ROOT_DIR', realpath(zm_dir(__DIR__ . '/../../')));
}

define('ZM_INIT_TIME', microtime(true));

/* 定义用于存放框架运行状态的目录（Windows 可用） */
define('ZM_STATE_DIR', TMP_DIR . '/.zm_' . sha1(ZM_INIT_TIME . FRAMEWORK_ROOT_DIR));

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
    && define('SWOOLE_HOOK_ALL', 2_147_481_599)
);
