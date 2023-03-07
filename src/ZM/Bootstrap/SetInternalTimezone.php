<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\HasRuntimeInfo;

class SetInternalTimezone implements Bootstrapper
{
    public function bootstrap(HasRuntimeInfo $runtime_info): void
    {
        date_default_timezone_set(config('global.runtime.timezone', 'UTC'));
    }
}
