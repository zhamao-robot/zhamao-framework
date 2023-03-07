<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\HasRuntimeInfo;

class LoadGlobalDefines implements Bootstrapper
{
    public function bootstrap(HasRuntimeInfo $runtime_info): void
    {
        require FRAMEWORK_ROOT_DIR . '/src/Globals/global_defines_framework.php';
    }
}
