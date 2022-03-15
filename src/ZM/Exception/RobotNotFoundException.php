<?php

declare(strict_types=1);

namespace ZM\Exception;

use Throwable;

/**
 * Class RobotNotFoundException
 * @since 1.2
 */
class RobotNotFoundException extends ZMException
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(zm_internal_errcode('E00037') . $message, $code, $previous);
    }
}
