<?php


namespace ZM\Exception;


use Throwable;

class DbException extends ZMException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct(zm_internal_errcode("E00043") . $message, $code, $previous);
    }
}
