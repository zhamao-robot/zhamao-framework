<?php

declare(strict_types=1);

namespace ZM\Container;

use ZM\Utils\SingletonTrait;

class WorkerContainer implements ContainerInterface
{
    use SingletonTrait;
    use ContainerTrait;
}
