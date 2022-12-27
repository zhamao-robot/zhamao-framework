<?php

declare(strict_types=1);

namespace ZM\Store\Database;

use ZM\Exception\ZMException;

class DBException extends ZMException
{
    public function __construct(string $description, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($description, $code, $previous);
    }
}
