<?php

declare(strict_types=1);

use OneBot\Exception\ExceptionHandler;

// PHP 8.4 起，第三方依赖（vendor 内）会在 Composer autoload 阶段就抛出「隐式可空参数」等弃用告警，
// 而此时框架的错误处理器（ZM\Bootstrap\HandleExceptions）尚未注册，告警会直接打到终端。这里在引入
// autoload 前临时接管：只丢弃 vendor 内的 deprecation，其它错误仍交回 PHP 默认错误处理器；
// autoload 完成后立即恢复，后续由框架错误处理器接管（vendor 弃用同样降级为 debug 日志）。
if (PHP_VERSION_ID >= 80400) {
    set_error_handler(static function (int $errno, string $errstr, string $errfile): bool {
        return in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED], true)
            && str_contains(str_replace('\\', '/', $errfile), '/vendor/');
    });
}

// CLI Application 入口文件，先引入 Composer 组件
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    // Current: src
    require_once dirname(__DIR__) . '/vendor/autoload.php';
} else {
    // Current: vendor/zhamao/framework/src
    require_once dirname(__DIR__, 3) . '/autoload.php';
}

if (PHP_VERSION_ID >= 80400) {
    restore_error_handler();
}

// 适配 Windows 的 conhost 中文显示，因为使用 micro 打包框架运行的时候在 Windows 运行中文部分会变成乱码
if (DIRECTORY_SEPARATOR === '\\') {
    exec('CHCP 65001');
}

// 开始运行，运行 symfony console 组件并解析命令
try {
    (new ZM\ConsoleApplication('zhamao-framework'))->run();
} catch (Exception $e) {
    ExceptionHandler::getInstance()->handle($e);
    exit(1);
}
