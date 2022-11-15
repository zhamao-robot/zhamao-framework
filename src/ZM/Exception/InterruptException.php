<?php

declare(strict_types=1);

namespace ZM\Exception;

class InterruptException extends ZMException
{
    public $return_var;

    public function __construct($return_var = null, $message = '', $code = 0, \Throwable $previous = null)
    {
        // TODO: change this to a better error message
        parent::__construct($message, '', $code, $previous);
        $this->return_var = $return_var;
    }
}
