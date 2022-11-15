<?php

declare(strict_types=1);

namespace ZM\Exception;

class InvalidArgumentException extends ZMException
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        // TODO: change this to a better error message
        parent::__construct($message, '', 74, $previous);
    }
}
