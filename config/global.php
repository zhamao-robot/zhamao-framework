<?php

declare(strict_types=1);

/* 启动框架的底层驱动（原生支持 swoole、workerman 两种） */
$config['driver'] = env('DRIVER', 'workerman');

/* 要启动的服务器监听端口及协议 */
$config['servers'] = [
    [
        'host' => '0.0.0.0',
        'port' => 20001,
        'type' => 'websocket',
    ],
    [
        'host' => '0.0.0.0',
        'port' => 20002,
        'type' => 'http',
        'flag' => 20002,
    ],
];

/* Workerman 驱动相关配置 */
$config['workerman_options'] = [
    'worker_num' => env('WORKER_NUM', 1),          // 如果你只有一个 OneBot 实例连接到框架并且代码没有复杂的CPU密集计算，则可把这里改为1使用全局变量
];

/* Swoole 驱动相关配置 */
$config['swoole_options'] = [
    'coroutine_hook_flags' => SWOOLE_HOOK_ALL & (~SWOOLE_HOOK_CURL),    // 协程 Hook 内容
    'swoole_set' => [
        'worker_num' => env('WORKER_NUM', 1),                  // 如果你只有一个 OneBot 实例连接到框架并且代码没有复杂的CPU密集计算，则可把这里改为1使用全局变量
        'dispatch_mode' => 2,               // 包分配原则，见 https://wiki.swoole.com/#/server/setting?id=dispatch_mode
        'max_coroutine' => 300000,          // 允许最大的协程数
        'max_wait_time' => 5,               // 安全退出模式下允许等待 Worker 的最长秒数
        // 'task_worker_num' => 4,          // 启动 TaskWorker 进程的数量（默认不启动）
        // 'task_enable_coroutine' => true  // TaskWorker 是否开启协程
    ],
    'swoole_server_mode' => SWOOLE_PROCESS,        // Swoole Server 启动模式，默认为 SWOOLE_PROCESS
];

/* 默认存取炸毛数据的目录（相对目录时，代表WORKING_DIR下的目录，绝对目录按照绝对目录来） */
$config['data_dir'] = WORKING_DIR . '/zm_data';

/* 框架本体运行时的一些可调配置 */
$config['runtime'] = [
    'reload_delay_time' => 800,
    'annotation_reader_ignore' => [ // 设置注解解析器忽略的注解名或命名空间，防止解析到不该解析的
        'name' => [
            'mixin',
        ],
        'namespace' => [],
    ],
    'timezone' => env('TIMEZONE', 'Asia/Shanghai'),
];

/* 允许加载插件形式 */
$config['plugin'] = [
    'enable' => true,                   // 是否启动插件系统，默认为 true，如果为否则只能使用 src 模式编写用户代码
    'load_dir' => 'plugins',            // 插件目录，相对目录时，代表WORKING_DIR下的目录，绝对目录按照绝对目录来
    'composer_plugin_enable' => true,   // 是否加载 Composer 依赖的插件，如果为 true 则读取 vendor/composer/installed.json 遍历加载
];

/* 内部默认启用的插件 */
$config['native_plugin'] = [
    'onebot12' => true,                 // OneBot v12 协议支持
    'onebot12-ban-other-ws' => true,    // OneBot v12 协议支持，禁止其他 WebSocket 连接
    'command-manual' => true,
];

/* 静态文件读取器 */
$config['file_server'] = [
    'enable' => true,
    'document_root' => $config['data_dir'] . '/public/',
    'document_index' => 'index.html',
    'document_code_page' => [
        '404' => '404.html',
        '500' => '500.html',
    ],
];

/* MySQL 和 SQLite3 数据库连接配置，框架将自动生成连接池，支持多个连接池 */
$config['database'] = [
    'sqlite_db1' => [
        'enable' => false,
        'type' => 'sqlite',
        'dbname' => 'a.db',
        'pool_size' => 10,
    ],
    'default' => [
        'enable' => false,
        'type' => 'mysql',
        'host' => '127.0.0.1', // 填写数据库服务器地址后才会创建数据库连接
        'port' => 3306,
        'username' => 'root',
        'password' => 'ZhamaoTEST',
        'dbname' => 'zm',
        'charset' => 'utf8mb4',
        'pool_size' => 64,
    ],
];

/* Redis 连接配置，框架将自动生成连接池，支持多个连接池 */
$config['redis'] = [
    'default' => [
        'enable' => false,
        'host' => '127.0.0.1',
        'port' => 6379,
        'index' => 0,
        'auth' => '',
        'pool_size' => 10,
    ],
];

/* KV 数据库的配置 */
$config['kv'] = [
    'use' => \LightCache::class,                        // 默认在单进程模式下使用 LightCache，多进程需要使用 ZMRedis
    'light_cache_dir' => $config['data_dir'] . '/lc',   // 默认的 LightCache 保存持久化数据的位置
    'light_cache_autosave_time' => 600,                 // LightCache 自动保存时间（秒）
    'redis_config' => 'default',
];

return $config;
