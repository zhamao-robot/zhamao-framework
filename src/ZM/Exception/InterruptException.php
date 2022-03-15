<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class InterruptException extends ZMException
{
    public $return_var;

    public function __construct($return_var = null, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->return_var = $return_var;
    }
}
