<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

class InitException extends ZMException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        // TODO: change this to a better error message
        parent::__construct($message, '', $code, $previous);
    }
}
