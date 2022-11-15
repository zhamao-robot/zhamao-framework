<?php

declare(strict_types=1);

/**
 * CLI Application 入口文件，先引入 Composer 组件
 *
 * @noinspection PhpIncludeInspection
 */

use OneBot\Exception\ExceptionHandler;

require_once((!is_dir(__DIR__ . '/../vendor')) ? getcwd() : (__DIR__ . '/..')) . '/vendor/autoload.php';

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
