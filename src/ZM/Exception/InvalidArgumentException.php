<?php

declare(strict_types=1);

namespace ZM\Exception;

class InvalidArgumentException extends ZMException
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode('E00074') . $message, $code, $previous);
    }
}
