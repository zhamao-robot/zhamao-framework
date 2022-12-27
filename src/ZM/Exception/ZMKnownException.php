<?php

declare(strict_types=1);

namespace ZM\Exception;

use JetBrains\PhpStorm\Deprecated;

#[Deprecated(reason: '建议使用具体的异常类')]
class ZMKnownException extends ZMException
{
    public function __construct($err_code, $message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode($err_code) . $message, $code, $previous);
        if ($err_code === 'E99999') {
            $code = 0;
        // 这也太懒了吧
        } else {
            // 取最后两数
            $code = (int) substr($err_code, -2);
        }
        parent::__construct($message, $code, $previous);
    }
}
