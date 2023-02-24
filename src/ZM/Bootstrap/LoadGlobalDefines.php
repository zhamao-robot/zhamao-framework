<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\Kernel;

class LoadGlobalDefines implements Bootstrapper
{
    public function bootstrap(Kernel $kernel): void
    {
        require FRAMEWORK_ROOT_DIR . '/src/Globals/global_defines_framework.php';
    }
}
