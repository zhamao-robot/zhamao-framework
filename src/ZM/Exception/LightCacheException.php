<?php


namespace ZM\Exception;


use Throwable;

class LightCacheException extends ZMKnownException
{
    public function __construct($err_code, $message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($err_code, $message, $code, $previous);
    }
}