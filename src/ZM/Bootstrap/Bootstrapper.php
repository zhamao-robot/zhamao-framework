<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Config\RuntimePreferences;

interface Bootstrapper
{
    public function bootstrap(RuntimePreferences $preferences): void;
}
