<?php

declare(strict_types=1);

namespace ZM\Exception;

use OneBot\Exception\ExceptionHandler;
use OneBot\Exception\ExceptionHandlerInterface;

class Handler extends ExceptionHandler implements ExceptionHandlerInterface
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(\Throwable $e): void
    {
        if ($e instanceof ZMKnownException) {
            // 如果是已知异常，则可以输出问题说明和解决方案
            // TODO
        }

        $this->handle0($e);
    }
}
