<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Kernel;

class SetInternalTimezone implements Bootstrapper
{
    public function bootstrap(Kernel $kernel): void
    {
        date_default_timezone_set(config('global.runtime.timezone', 'UTC'));
    }
}
