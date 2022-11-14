<?php

declare(strict_types=1);

namespace ZM\Exception;

use OneBot\Driver\ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function __construct()
    {
        // 我们知道此处没有调用父类的构造函数，这是设计上的缺陷
        // 将会在稍后修复
    }

    public function handle(\Throwable $e): void
    {
        if ($e instanceof ZMKnownException) {
            // 如果是已知异常，则可以输出问题说明和解决方案
            // TODO
        }

        if (is_null($this->whoops)) {
            ob_logger()->error('Uncaught ' . get_class($e) . ': ' . $e->getMessage() . ' at ' . $e->getFile() . '(' . $e->getLine() . ')');
            ob_logger()->error($e->getTraceAsString());
            return;
        }

//        $this->whoops->handleException($e);
    }
}
