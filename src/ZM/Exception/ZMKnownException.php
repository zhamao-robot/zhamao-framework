<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class ZMKnownException extends ZMException
{
    public function __construct($err_code, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode($err_code) . $message, $code, $previous);
    }
}
