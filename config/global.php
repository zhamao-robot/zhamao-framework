<?php

declare(strict_types=1);

/* 启动框架的底层驱动（原生支持 swoole、workerman 两种） */
$config['driver'] = 'workerman';

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
    [
        'host' => '0.0.0.0',
        'port' => 20003,
        'type' => 'http',
        'flag' => 20003,
    ],
];

/* Workerman 驱动相关配置 */
$config['workerman_options'] = [
    'worker_num' => 1,          // 如果你只有一个 OneBot 实例连接到框架并且代码没有复杂的CPU密集计算，则可把这里改为1使用全局变量
];

/* Swoole 驱动相关配置 */
$config['swoole_options'] = [
    'coroutine_hook_flags' => SWOOLE_HOOK_ALL & (~SWOOLE_HOOK_CURL),    // 协程 Hook 内容
    'swoole_set' => [
        'worker_num' => 1,                  // 如果你只有一个 OneBot 实例连接到框架并且代码没有复杂的CPU密集计算，则可把这里改为1使用全局变量
        'dispatch_mode' => 2,               // 包分配原则，见 https://wiki.swoole.com/#/server/setting?id=dispatch_mode
        'max_coroutine' => 300000,          // 允许最大的协程数
        'max_wait_time' => 5,               // 安全退出模式下允许等待 Worker 的最长秒数
        // 'task_worker_num' => 4,          // 启动 TaskWorker 进程的数量（默认不启动）
        // 'task_enable_coroutine' => true  // TaskWorker 是否开启协程
    ],
    'swoole_server_mode' => SWOOLE_PROCESS,        // Swoole Server 启动模式，默认为 SWOOLE_PROCESS
];

/* 框架本体运行时的一些可调配置 */
$config['runtime'] = [
    'reload_delay_time' => 800,
    'annotation_reader_ignore' => [ // 设置注解解析器忽略的注解名或命名空间，防止解析到不该解析的
        'name' => [
            'mixin',
        ],
        'namespace' => [],
    ],
    'timezone' => 'Asia/Shanghai',
];

return $config;
