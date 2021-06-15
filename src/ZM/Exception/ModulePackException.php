<?php


namespace ZM\Exception;


use Throwable;

class ModulePackException extends ZMException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct(zm_internal_errcode("E00044") . $message, $code, $previous);
    }
}