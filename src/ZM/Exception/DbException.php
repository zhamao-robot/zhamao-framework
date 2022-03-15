<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class DbException extends ZMKnownException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('E00043', $message, $code, $previous);
    }
}
