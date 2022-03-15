<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class NotInitializedException extends RedisException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode('E00046') . $message, $code, $previous);
    }
}
