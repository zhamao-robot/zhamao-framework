<?php

namespace ZM\Bootstrap;

class LoadGlobalDefines
{
    public function bootstrap(array $config): void
    {
        require zm_dir(SOURCE_ROOT_DIR . '/src/Globals/global_defines_framework.php');
    }
}
