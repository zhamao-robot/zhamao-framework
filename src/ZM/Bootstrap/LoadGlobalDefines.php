<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

class LoadGlobalDefines
{
    public function bootstrap(array $config): void
    {
        require FRAMEWORK_ROOT_DIR . '/src/Globals/global_defines_framework.php';
    }
}
