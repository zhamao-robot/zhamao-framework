<?php


namespace ZM\Exception;


use Exception;
use Throwable;
use ZM\ModBase;

class WaitTimeoutException extends Exception
{
    /** @var ModBase */
    public $module;

    public function __construct($module, $message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->module = $module;
    }
}