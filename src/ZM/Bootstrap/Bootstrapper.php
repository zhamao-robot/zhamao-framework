<?php

declare(strict_types=1);

namespace ZM\Bootstrap;

use ZM\HasRuntimeInfo;

interface Bootstrapper
{
    public function bootstrap(HasRuntimeInfo $runtime_info): void;
}
