<?php

declare(strict_types=1);

namespace ZM\Exception;

class WaitTimeoutException extends ZMException
{
    public $module;

    public function __construct($module, $message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, '', $code, $previous);
        $this->module = $module;
    }
}
