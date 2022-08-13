<?php

declare(strict_types=1);

namespace ZM\Container;

use OneBot\Util\Singleton;

class WorkerContainer implements ContainerInterface
{
    use Singleton;
    use ContainerTrait;
}
