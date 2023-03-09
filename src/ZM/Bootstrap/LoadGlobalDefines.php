<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Config\RuntimePreferences;

class LoadGlobalDefines implements Bootstrapper
{
    public function bootstrap(RuntimePreferences $preferences): void
    {
        require FRAMEWORK_ROOT_DIR . '/src/Globals/global_defines_framework.php';
    }
}
