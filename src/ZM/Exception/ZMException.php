<?php

declare(strict_types=1);

namespace ZM\Exception;

abstract class ZMException extends \Exception
{
    public function __construct(string $description, string $solution = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($description . PHP_EOL . $solution, $code, $previous);
    }
}
