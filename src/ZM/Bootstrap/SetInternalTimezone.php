<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Config\RuntimePreferences;

class SetInternalTimezone implements Bootstrapper
{
    public function bootstrap(RuntimePreferences $preferences): void
    {
        date_default_timezone_set(config('global.runtime.timezone', 'UTC'));
    }
}
