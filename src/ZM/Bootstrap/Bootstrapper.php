<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Kernel;

interface Bootstrapper
{
    public function bootstrap(Kernel $kernel): void;
}
