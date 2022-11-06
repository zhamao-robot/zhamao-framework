<?php

declare(strict_types=1);

namespace ZM\Container;

use Psr\Container\NotFoundExceptionInterface;

class EntryNotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
