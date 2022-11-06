<?php

declare(strict_types=1);

namespace ZM\Container;

use Psr\Container\ContainerExceptionInterface;

class EntryResolutionException extends \Exception implements ContainerExceptionInterface
{
}
