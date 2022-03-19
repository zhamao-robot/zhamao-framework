<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class MethodNotFoundException extends ZMException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode('E00073') . $message, $code, $previous);
    }
}
