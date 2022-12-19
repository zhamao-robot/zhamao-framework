<?php

declare(strict_types=1);

namespace ZM\Exception;

class InterruptException extends ZMException
{
    public function __construct(public $return_var = null, $message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, '', $code, $previous);
    }
}
